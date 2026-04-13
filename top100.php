<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');

// 1. LẤY TOP 100 THEO QUỐC GIA
$countryQuery = "
    SELECT DISTINCT a.Country,
    (SELECT s.CoverImage_URL 
     FROM songs s 
     JOIN song_artist sa2 ON s.SongID = sa2.SongID 
     JOIN artists a2 ON sa2.ArtistID = a2.ArtistID 
     WHERE a2.Country = a.Country AND s.status = 1 
     ORDER BY s.PlayCount DESC LIMIT 1) as TopCover,
    (SELECT GROUP_CONCAT(DISTINCT a3.Name SEPARATOR ', ') 
     FROM artists a3 
     WHERE a3.Country = a.Country 
     ORDER BY (SELECT SUM(PlayCount) FROM songs s3 JOIN song_artist sa3 ON s3.SongID = sa3.SongID WHERE sa3.ArtistID = a3.ArtistID) DESC 
     LIMIT 3) as TopArtists
    FROM artists a
    WHERE a.Country IS NOT NULL AND a.Country != '' AND a.Country != 'no' AND a.Country != 'ko có'
";
$countryResult = $conn->query($countryQuery);

// 2. LẤY TOP 100 THEO THỂ LOẠI
$genreQuery = "
    SELECT g.GenreID, g.Name, 
    (SELECT CoverImage_URL FROM songs WHERE GenreID = g.GenreID AND status = 1 ORDER BY PlayCount DESC LIMIT 1) as TopCover,
    (SELECT GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') 
     FROM songs s 
     JOIN song_artist sa ON s.SongID = sa.SongID 
     JOIN artists a ON sa.ArtistID = a.ArtistID 
     WHERE s.GenreID = g.GenreID AND s.status = 1 
     ORDER BY s.PlayCount DESC LIMIT 3) as TopArtists
    FROM genres g
    WHERE EXISTS (SELECT 1 FROM songs WHERE GenreID = g.GenreID AND status = 1)
";
$genreResult = $conn->query($genreQuery);
?>

<style>
    /* TÁI SỬ DỤNG CSS CARD TỪ DISCOVER.PHP */
    .section-title { font-size: 20px; font-weight: 700; color: white; margin-bottom: 20px; text-transform: capitalize; }
    .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
    .card { background: transparent; border-radius: 8px; cursor: pointer; transition: transform 0.3s; }
    .card:hover { transform: translateY(-5px); }
    .image-container { position: relative; border-radius: 8px; overflow: hidden; aspect-ratio: 1/1; margin-bottom: 10px; }
    .image-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .card:hover .image-container img { transform: scale(1.1); }
    .overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s; z-index: 10; }
    .card:hover .overlay { opacity: 1; }
    .icon-play { width: 45px; height: 45px; border: 1px solid white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; padding-left: 3px; transition: 0.2s; }
    .icon-play:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
    .title { color: white; font-weight: 600; font-size: 14px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .subtitle { color: var(--text-secondary); font-size: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    
    /* CSS Riêng để chèn chữ TOP 100 mờ ảo lên ảnh */
    .top100-text-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background: rgba(0,0,0,0.25); pointer-events: none; z-index: 5;}
    .top-text-small { font-size: 12px; font-weight: 700; letter-spacing: 5px; color: white; margin-bottom: -10px; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
    .top-text-big { font-size: 60px; font-weight: 900; color: white; line-height: 1; text-shadow: 0 4px 10px rgba(0,0,0,0.5); }
    .top-text-genre { font-size: 13px; font-weight: 800; color: white; text-transform: uppercase; margin-top: 5px; text-align: center; padding: 0 10px; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
</style>

<div style="padding-bottom: 20px; animation: fadeIn 0.5s ease;">
    <div>
        <h2 class="section-title">Nổi Bật</h2>
        <div class="card-grid">
            <?php while($row = $countryResult->fetch_assoc()): ?>
            <div class="card" onclick="loadContent('song_list.php?type=artist_country&val=<?php echo urlencode($row['Country']); ?>&sort=top')">
                <div class="image-container">
                    <img src="<?php echo $row['TopCover'] ?: 'assets/default_playlist.jpg'; ?>">
                    <div class="top100-text-overlay">
                        <span class="top-text-small">TOP</span>
                        <span class="top-text-big">100</span>
                        <span class="top-text-genre">Nhạc <?php echo $row['Country']; ?></span>
                    </div>
                    <div class="overlay">
                        <i class="fa-solid fa-play icon-play"></i>
                    </div>
                </div>
                <div class="title">Top 100 Nhạc <?php echo $row['Country']; ?></div>
                <div class="subtitle"><?php echo $row['TopArtists'] ?: 'Những nghệ sĩ được yêu thích nhất...'; ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div>
        <h2 class="section-title">Theo Thể Loại</h2>
        <div class="card-grid">
            <?php while($row = $genreResult->fetch_assoc()): ?>
            <div class="card" onclick="loadContent('song_list.php?type=genre&id=<?php echo $row['GenreID']; ?>&sort=top')">
                <div class="image-container">
                    <img src="<?php echo $row['TopCover'] ?: 'assets/default_playlist.jpg'; ?>">
                    <div class="top100-text-overlay">
                        <span class="top-text-small">TOP</span>
                        <span class="top-text-big">100</span>
                        <span class="top-text-genre"><?php echo $row['Name']; ?></span>
                    </div>
                    <div class="overlay">
                        <i class="fa-solid fa-play icon-play"></i>
                    </div>
                </div>
                <div class="title">Top 100 Nhạc <?php echo $row['Name']; ?></div>
                <div class="subtitle"><?php echo $row['TopArtists'] ?: 'Tuyển tập những bài hát hay nhất...'; ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</div>
<?php $conn->close(); ?>