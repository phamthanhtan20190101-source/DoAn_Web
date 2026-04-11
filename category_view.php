<?php
session_start();
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$type = isset($_GET['type']) ? $_GET['type'] : '';
$val = isset($_GET['val']) ? $conn->real_escape_string($_GET['val']) : '';
$name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
$username = isset($_SESSION['username']) ? $conn->real_escape_string($_SESSION['username']) : '';

$pageTitle = "Danh sách bài hát";
$sql = "";

// 1. Nếu người dùng chọn lọc theo QUỐC GIA (Ví dụ: Việt Nam, US-UK)
if ($type === 'country') {
    $pageTitle = "Top Nhạc " . ($val === 'Việt Nam' ? 'Việt' : ($val === 'Hàn Quốc' ? 'Hàn' : $val));
    $sql = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
            FROM songs s
            JOIN song_artist sa ON s.SongID = sa.SongID
            JOIN artists a ON sa.ArtistID = a.ArtistID
            LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
            WHERE a.Country = '$val'
            GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 50";
} 
// 2. Nếu chọn lọc theo THỂ LOẠI (Ví dụ: Pop, Rap)
elseif ($type === 'genre') {
    $pageTitle = "Thể loại: " . $name;
    $sql = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
            FROM songs s
            LEFT JOIN song_artist sa ON s.SongID = sa.SongID
            LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
            LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
            WHERE s.GenreID = '$val'
            GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 50";
} 
// 3. Nếu chọn lọc theo CA SĨ
elseif ($type === 'artist') {
    $pageTitle = "Tuyển tập: " . $name;
    $sql = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
            FROM songs s
            JOIN song_artist sa ON s.SongID = sa.SongID
            JOIN artists a ON sa.ArtistID = a.ArtistID
            LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
            WHERE sa.ArtistID = '$val'
            GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 50";
}

$result = ($sql !== "") ? $conn->query($sql) : null;
$songsJSON = []; $index = 0;
?>

<div style="padding-bottom: 20px;">
    <button onclick="loadContent('topic_genre.php')" style="background:transparent; border:none; color:var(--text-secondary); cursor:pointer; margin-bottom:20px; font-size: 15px; font-weight: bold; transition: 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='var(--text-secondary)'"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
    
    <h1 style="color: white; font-size: 40px; font-weight: 800; margin-bottom: 30px;"><?php echo $pageTitle; ?></h1>

    <?php if ($result && $result->num_rows > 0): ?>
        <button onclick="playPlaylist(0, 'data-category-view')" style="background: var(--purple-primary); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s; margin-bottom: 20px;"><i class="fa-solid fa-play"></i> PHÁT TẤT CẢ</button>
        
        <div class="song-list-container" style="display: flex; flex-direction: column; gap: 5px;">
            <?php while ($row = $result->fetch_assoc()): 
                $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??''];
            ?>
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-category-view')">
                    <?php renderSongItem($row); ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; padding: 50px; text-align: center; color: gray;">
            <i class="fa-solid fa-compact-disc" style="font-size: 50px; margin-bottom: 15px; opacity: 0.3;"></i>
            <h3>Chưa có bài hát nào</h3>
            <p>Hệ thống đang cập nhật thêm các bài hát thuộc danh mục này.</p>
        </div>
    <?php endif; ?>
    
    <div id="data-category-view" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>
</div>
<?php $conn->close(); ?>