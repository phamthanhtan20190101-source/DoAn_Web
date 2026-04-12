<?php
$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset('utf8mb4');

// 1. LẤY TOP 100 THEO THỂ LOẠI (Từ bảng genres)
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

// 2. [MỚI] LẤY TOP 100 THEO QUỐC GIA CỦA NGHỆ SĨ (Từ bảng artists)
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
?>

<style>
    .top100-wrapper { padding-bottom: 50px; animation: fadeIn 0.5s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .top100-section { margin-bottom: 40px; }
    .top100-section-title { color: white; font-size: 24px; font-weight: 800; margin-bottom: 25px; border-left: 4px solid var(--purple-primary); padding-left: 15px; }
    .top100-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 25px; }
    @media (max-width: 1400px) { .top100-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 1000px) { .top100-grid { grid-template-columns: repeat(3, 1fr); } }

    .top100-card { cursor: pointer; transition: 0.3s; display: flex; flex-direction: column; background: rgba(255,255,255,0.02); padding: 12px; border-radius: 12px; }
    .top100-card:hover { background: rgba(255,255,255,0.08); transform: translateY(-5px); }
    .top100-image-box { position: relative; border-radius: 8px; overflow: hidden; aspect-ratio: 1/1; margin-bottom: 12px; }
    .top100-bg-img { width: 100%; height: 100%; object-fit: cover; transition: 0.6s; }
    
    .top100-text-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background: rgba(0,0,0,0.3); pointer-events: none; }
    .top-text-small { font-size: 14px; font-weight: 700; letter-spacing: 5px; color: white; margin-bottom: -10px; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
    .top-text-big { font-size: 70px; font-weight: 900; color: white; line-height: 1; text-shadow: 0 4px 10px rgba(0,0,0,0.5); }
    .top-text-genre { font-size: 14px; font-weight: 800; color: white; text-transform: uppercase; margin-top: 5px; text-align: center; padding: 0 10px; }

    .top100-hover-action { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; justify-content: center; align-items: center; opacity: 0; transition: 0.3s; }
    .top100-card:hover .top100-hover-action { opacity: 1; }
    .top100-play-circle { border: 2px solid white; border-radius: 50%; width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; color: white; font-size: 20px; }

    .top100-info h4 { color: white; font-size: 15px; font-weight: 700; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .top100-info p { color: rgba(255,255,255,0.5); font-size: 13px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<div class="top100-wrapper">
    <div class="top100-section">
        <h3 class="top100-section-title">BXH Theo Quốc Gia</h3>
        <div class="top100-grid">
            <?php while($row = $countryResult->fetch_assoc()): ?>
            <div class="top100-card" onclick="loadContent('song_list.php?type=artist_country&val=<?php echo urlencode($row['Country']); ?>&sort=top')">
                <div class="top100-image-box">
                    <img src="<?php echo $row['TopCover'] ?: 'assets/default_playlist.jpg'; ?>" class="top100-bg-img">
                    <div class="top100-text-overlay">
                        <span class="top-text-small">TOP</span>
                        <span class="top-text-big">100</span>
                        <span class="top-text-genre">Nhạc <?php echo $row['Country']; ?></span>
                    </div>
                    <div class="top100-hover-action"><div class="top100-play-circle"><i class="fa-solid fa-play"></i></div></div>
                </div>
                <div class="top100-info">
                    <h4>Top 100 Nhạc <?php echo $row['Country']; ?></h4>
                    <p><?php echo $row['TopArtists'] ?: 'Những nghệ sĩ được yêu thích nhất...'; ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="top100-section">
        <h3 class="top100-section-title">BXH Theo Thể Loại</h3>
        <div class="top100-grid">
            <?php while($row = $genreResult->fetch_assoc()): ?>
            <div class="top100-card" onclick="loadContent('song_list.php?type=genre&id=<?php echo $row['GenreID']; ?>&sort=top')">
                <div class="top100-image-box">
                    <img src="<?php echo $row['TopCover'] ?: 'assets/default_playlist.jpg'; ?>" class="top100-bg-img">
                    <div class="top100-text-overlay">
                        <span class="top-text-small">TOP</span>
                        <span class="top-text-big">100</span>
                        <span class="top-text-genre"><?php echo $row['Name']; ?></span>
                    </div>
                    <div class="top100-hover-action"><div class="top100-play-circle"><i class="fa-solid fa-play"></i></div></div>
                </div>
                <div class="top100-info">
                    <h4>Top 100 Nhạc <?php echo $row['Name']; ?></h4>
                    <p><?php echo $row['TopArtists'] ?: 'Tuyển tập những bài hát hay nhất...'; ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php $conn->close(); ?>