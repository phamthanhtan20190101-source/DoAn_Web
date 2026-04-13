<?php
session_start();
include_once 'render_helper.php'; // TÁI SỬ DỤNG hàm vẽ giao diện chuẩn của Khám phá

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$val = $_GET['val'] ?? '';
$titlePage = "Top 100";
$username = isset($_SESSION['username']) ? $conn->real_escape_string($_SESSION['username']) : '';

if ($type === 'genre') {
    $stmt = $conn->prepare("SELECT Name FROM genres WHERE GenreID = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->bind_result($gName); $stmt->fetch(); $stmt->close();
    $titlePage = "Top 100 Nhạc " . ($gName ?? 'Thể Loại');
    
    $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Artists, 
                   IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite 
            FROM songs s 
            LEFT JOIN song_artist sa ON s.SongID = sa.SongID 
            LEFT JOIN artists a ON sa.ArtistID = a.ArtistID 
            LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
            WHERE s.GenreID = ? AND s.status = 1 
            GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 100";
    $stmt = $conn->prepare($sql); $stmt->bind_param("i", $id);
} elseif ($type === 'artist_country') {
    $titlePage = "Top 100 Nhạc " . htmlspecialchars($val);
    
    $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Artists, 
                   IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite 
            FROM songs s 
            JOIN song_artist sa ON s.SongID = sa.SongID 
            JOIN artists a ON sa.ArtistID = a.ArtistID 
            LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
            WHERE a.Country = ? AND s.status = 1 
            GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 100";
    $stmt = $conn->prepare($sql); $stmt->bind_param("s", $val);
}

$stmt->execute();
$result = $stmt->get_result();
$songsJSON = [];
$index = 0;
?>

<div class="song-list-container" style="padding-bottom: 40px; animation: fadeIn 0.5s ease;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
        <h2 style="font-size: 24px; font-weight: 700; color: white; margin: 0;"><?php echo $titlePage; ?></h2>
        <?php if ($result && $result->num_rows > 0): ?>
        <button class="btn-admin" onclick="playPlaylist(0, 'data-top100-list')" style="background: var(--purple-primary); color: white; border: none; padding: 10px 25px; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s;"><i class="fa-solid fa-play"></i> PHÁT TẤT CẢ</button>
        <?php endif; ?>
    </div>

    <div class="song-items-list" style="display: flex; flex-direction: column; gap: 5px;">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??''];
            ?>
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-top100-list')">
                    <?php renderSongItem($row); ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: gray; background: rgba(255,255,255,0.02); border-radius: 12px;">
                <i class="fa-solid fa-music" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Danh sách này hiện đang trống.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div id="data-top100-list" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>
</div>
<?php include 'footer.php'; ?>