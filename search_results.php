<?php
session_start();
include_once 'render_helper.php'; // Gọi hàm vẽ bài hát chuẩn Zing MP3

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy từ khóa từ ô tìm kiếm truyền sang
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($keyword === '') { 
    echo '<div style="color:white; padding: 20px;">Vui lòng nhập từ khóa tìm kiếm.</div>'; 
    exit; 
}
$searchParam = "%{$keyword}%";

// 1. TÌM KIẾM NGHỆ SĨ (Để hiển thị khung Card trên cùng)
$stmtArtist = $conn->prepare("SELECT * FROM artists WHERE Name LIKE ? LIMIT 1");
$stmtArtist->bind_param("s", $searchParam);
$stmtArtist->execute();
$artistMatch = $stmtArtist->get_result()->fetch_assoc();
$stmtArtist->close();

// 2. TÌM KIẾM BÀI HÁT (Giữ nguyên logic SQL chuẩn của bạn)
// Lưu ý: Mình đổi SELECT s.* để hàm renderSongItem lấy được đủ thông tin (ảnh, duration,...)
// 2. TÌM KIẾM BÀI HÁT (ĐÃ TỐI ƯU HÓA HIỆU NĂNG)
$sql = "SELECT s.*, 
               (SELECT GROUP_CONCAT(a2.Name SEPARATOR ', ') 
                FROM song_artist sa2 
                JOIN artists a2 ON sa2.ArtistID = a2.ArtistID 
                WHERE sa2.SongID = s.SongID) AS Artists
        FROM songs s
        WHERE s.Title LIKE ? 
           OR EXISTS (
               SELECT 1 FROM song_artist sa3 
               JOIN artists a3 ON sa3.ArtistID = a3.ArtistID 
               WHERE sa3.SongID = s.SongID AND a3.Name LIKE ?
           )
        ORDER BY s.SongID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$songsJSON = []; 
$index = 0;      
?>

<div style="color: white; padding-top: 10px;">
    <h2 style="font-size: 24px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 25px;">
        Kết quả tìm kiếm cho: "<span style="color: var(--purple-primary);"><?php echo htmlspecialchars($keyword); ?></span>"
    </h2>

    <?php if ($artistMatch): ?>
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Nghệ sĩ quan tâm</h3>
        <div onclick="loadContent('artist_view.php?id=<?php echo $artistMatch['ArtistID']; ?>')" style="background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; display: inline-flex; align-items: center; gap: 20px; cursor: pointer; transition: 0.2s; margin-bottom: 30px;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.03)'">
            <img src="<?php echo htmlspecialchars($artistMatch['Image_URL'] ?: 'https://via.placeholder.com/100'); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
            <div>
                <div style="font-size: 20px; font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($artistMatch['Name']); ?></div>
                <div style="font-size: 13px; color: var(--text-secondary);"><i class="fa-solid fa-user"></i> Nghệ sĩ</div>
            </div>
            <i class="fa-solid fa-chevron-right" style="margin-left: 30px; color: var(--text-secondary);"></i>
        </div>
    <?php endif; ?>

    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 15px;">Bài Hát</h3>
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="song-list-container" style="display: flex; flex-direction: column; gap: 5px;">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                $songsJSON[] = [
                    'id' => $row['SongID'],
                    'url' => htmlspecialchars($row['FilePath_URL'], ENT_QUOTES, 'UTF-8'),
                    'title' => htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'),
                    'artist' => htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'),
                    'cover' => htmlspecialchars($row['CoverImage_URL'] ?? '', ENT_QUOTES, 'UTF-8')
                ];
                ?>
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-search-view')">
                    <?php renderSongItem($row); ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: rgba(255,255,255,0.5); padding: 20px 0;">Không tìm thấy bài hát nào phù hợp với "<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"</p>
    <?php endif; ?>
</div>

<div id="data-search-view" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

<?php 
$stmt->close();
$conn->close(); 
?>