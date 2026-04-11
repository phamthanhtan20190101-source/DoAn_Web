<?php
session_start();
// 1. NHÚNG FILE HELPER ĐỂ DÙNG GIAO DIỆN MỚI
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Cập nhật SQL lấy thêm PlayCount để hiển thị ở giao diện mới (nếu cần)
$sql = "SELECT s.SongID, s.Title, s.Duration, s.FilePath_URL, s.CoverImage_URL, s.PlayCount, 
               GROUP_CONCAT(a.Name SEPARATOR ', ') AS ArtistName 
        FROM songs s
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        GROUP BY s.SongID
        ORDER BY s.SongID DESC";
$result = $conn->query($sql);

$songsJSON = []; // GIỮ NGUYÊN: Mảng bài hát cho JavaScript
$index = 0;      // GIỮ NGUYÊN: Thứ tự bài hát
?>

<style>
    .library-header { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
    .library-header h2 { font-size: 24px; font-weight: 700; color: white; }
    
    /* Container chứa danh sách bài hát mới */
    .song-list-container { display: flex; flex-direction: column; gap: 5px; }

    /* Lớp bọc để khi nhấn vào bài hát thì phát nhạc */
    .song-item-wrapper { cursor: pointer; }
</style>

<div class="library-header">
    <h2>Thư Viện Của Tôi</h2>
</div>

<div class="song-list-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
            // GIỮ NGUYÊN LOGIC CŨ: Đẩy dữ liệu vào mảng JSON
            $songsJSON[] = [
                'url' => htmlspecialchars($row['FilePath_URL'], ENT_QUOTES, 'UTF-8'),
                'title' => htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'),
                'artist' => htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'),
                'cover' => htmlspecialchars($row['CoverImage_URL'] ?? '', ENT_QUOTES, 'UTF-8')
            ];

            // CHUẨN BỊ DỮ LIỆU ĐỂ GỌI HÀM RENDER
            $displayRow = $row;
            $displayRow['Artists'] = $row['ArtistName']; // Khớp tên cột với hàm render
            // Định dạng thời gian
            $displayRow['Duration_Formatted'] = $row['Duration'] ? gmdate('i:s', intval($row['Duration'])) : '--:--';
            ?>
            
            <div class="song-item-wrapper" onclick="if(typeof playPlaylist === 'function') playPlaylist(<?php echo $index; ?>)">
                <?php renderSongItem($displayRow); ?>
            </div>

            <?php $index++; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: rgba(255,255,255,0.5);">Chưa có bài hát nào trong thư viện.</p>
    <?php endif; ?>
</div>

<div id="current-playlist-data" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

<?php $conn->close(); ?>