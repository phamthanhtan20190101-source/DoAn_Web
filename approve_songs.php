<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit('Access Denied');

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Xử lý Duyệt bài hát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $approveId = intval($_POST['approve_id']);
    $conn->query("UPDATE songs SET status = 1 WHERE SongID = $approveId");
    echo json_encode(['success' => true, 'message' => 'Đã duyệt bài hát thành công!']);
    exit;
}

// Xử lý Xóa/Từ chối bài hát
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    $rejectId = intval($_POST['reject_id']);
    // Lấy link nhạc để xóa file cứng
    $pathRes = $conn->query("SELECT FilePath_URL FROM songs WHERE SongID = $rejectId");
    if($r = $pathRes->fetch_assoc()) @unlink(__DIR__ . '/' . ltrim($r['FilePath_URL'], '/'));
    $conn->query("DELETE FROM songs WHERE SongID = $rejectId");
    echo json_encode(['success' => true, 'message' => 'Đã từ chối và xóa bài hát!']);
    exit;
}

// Lấy danh sách chờ duyệt (status = 0) KÈM Lyrics và File nhạc
$result = $conn->query(
    "SELECT s.SongID, s.Title, s.FilePath_URL, s.Lyrics, a_user.Username AS Uploader, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists
     FROM songs s
     LEFT JOIN account a_user ON s.AccountID = a_user.AccountID
     LEFT JOIN song_artist sa ON sa.SongID = s.SongID
     LEFT JOIN artists a ON a.ArtistID = sa.ArtistID
     WHERE s.status = 0
     GROUP BY s.SongID ORDER BY s.SongID ASC"
);
?>

<h2 style="color: white; margin-bottom: 20px;">Duyệt bài hát chờ cấp phép</h2>

<?php if (!$result): ?>
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
        ⚠️ <b>Lỗi truy vấn Database:</b> <?php echo $conn->error; ?><br><br>
        <i>Bạn cần vào phpMyAdmin, mở bảng <b>songs</b> và thêm 2 cột mới:<br>
        1. Cột <b>status</b> (Kiểu INT, mặc định là 1)<br>
        2. Cột <b>AccountID</b> (Kiểu INT, cho phép Null)</i>
    </div>
<?php elseif ($result->num_rows > 0): ?>
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.1); color: white; text-align: left;">
        <thead style="background: rgba(255,255,255,0.05);">
            <tr>
                <th>Bài hát</th>
                <th>Người đăng</th>
                <th>Kiểm duyệt nội dung</th>
                <th style="width: 180px;">Quyết định</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($song = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($song['Title']); ?></strong><br>
                        <small style="color:gray;"><?php echo htmlspecialchars($song['Artists'] ?? 'Chưa rõ'); ?></small>
                    </td>
                    <td style="color: var(--purple-primary); font-weight: bold;">@<?php echo htmlspecialchars($song['Uploader'] ?? 'Khách'); ?></td>
                    <td>
                        <audio controls style="height: 30px; width: 220px; outline: none; margin-bottom: 5px;">
                            <source src="<?php echo htmlspecialchars($song['FilePath_URL']); ?>" type="audio/mpeg">
                        </audio>
                        <br>
                        <button type="button" class="btn-admin" style="background: rgba(255,255,255,0.1); padding: 5px 10px; font-size: 12px; transition: 0.2s; cursor: pointer;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" data-lyrics="<?php echo htmlspecialchars($song['Lyrics'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" onclick="showAdminLyric(this)">
                            <i class="fa-solid fa-file-lines"></i> Xem Lời bài hát
                        </button>
                    </td>
                    <td>
                        <form action="approve_songs.php" method="POST" data-ajax="true" data-reload-url="approve_songs.php" style="display:inline;">
                            <input type="hidden" name="approve_id" value="<?php echo $song['SongID']; ?>">
                            <button type="submit" class="btn-admin" style="background: #10b981; padding: 6px 12px; margin-bottom: 5px;"><i class="fa-solid fa-check"></i> Duyệt</button>
                        </form>
                        
                        <form action="approve_songs.php" method="POST" data-ajax="true" data-reload-url="approve_songs.php" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn TỪ CHỐI và XÓA bài hát này không?');">
                            <input type="hidden" name="reject_id" value="<?php echo $song['SongID']; ?>">
                            <button type="submit" class="btn-admin" style="background: #ef4444; padding: 6px 12px;"><i class="fa-solid fa-xmark"></i> Hủy</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <div style="text-align: center; padding: 50px; background: rgba(255,255,255,0.02); border-radius: 10px;">
        <i class="fa-solid fa-clipboard-check" style="font-size: 50px; color: gray; margin-bottom: 20px;"></i>
        <h3 style="color: white;">Tuyệt vời!</h3>
        <p style="color: gray;">Hiện không có bài hát nào đang chờ duyệt.</p>
    </div>
<?php endif; ?>
<div id="adminLyricModal" class="modal-overlay" style="display: none; z-index: 4000; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.7); justify-content: center; align-items: center;">
    <div class="modal-content" style="background: #231b2e; width: 550px; max-width: 90vw; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px; text-align: left; display: flex; flex-direction: column; max-height: 80vh; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">
            <h3 style="color: white; margin: 0; font-size: 18px;"><i class="fa-solid fa-file-lines" style="color: var(--purple-primary);"></i> Lời bài hát</h3>
            <i class="fa-solid fa-xmark" style="color: gray; font-size: 24px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='gray'" onclick="document.getElementById('adminLyricModal').style.display='none'"></i>
        </div>
        
        <div id="adminLyricContent" style="color: #ddd; font-size: 14px; line-height: 2; overflow-y: auto; flex: 1; white-space: pre-wrap; font-family: monospace; background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px;">
            </div>
        
        <button type="button" class="btn-admin" style="margin-top: 20px; padding: 10px; background: var(--purple-primary); width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; color: white;" onclick="document.getElementById('adminLyricModal').style.display='none'">
            Đóng
        </button>
    </div>
</div>

<script>
    function showAdminLyric(btn) {
        const lyrics = btn.getAttribute('data-lyrics');
        document.getElementById('adminLyricContent').textContent = lyrics ? lyrics : '🎶 Bài hát này chưa được người đăng cập nhật lời...';
        document.getElementById('adminLyricModal').style.display = 'flex';
    }
</script>

<script>
    (function() {
        // Lấy số lượng bài hát đang chờ từ PHP
        const pendingCount = <?php echo (isset($result) && $result) ? $result->num_rows : 0; ?>;
        
        // Tìm Menu "Duyệt bài hát" ở thanh Sidebar bên trái
        const menuItems = document.querySelectorAll('.sidebar .menu-item');
        menuItems.forEach(item => {
            if (item.getAttribute('onclick') && item.getAttribute('onclick').includes('approve_songs.php')) {
                if (pendingCount > 0) {
                    // Nếu còn bài -> Hiện chấm đỏ và cập nhật số
                    item.innerHTML = '<i class="fa-solid fa-circle-check"></i> Duyệt bài hát <span style="background: #ef4444; color: white; border-radius: 50%; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: auto;">' + pendingCount + '</span>';
                } else {
                    // Nếu hết bài -> Xóa chấm đỏ đi
                    item.innerHTML = '<i class="fa-solid fa-circle-check"></i> Duyệt bài hát';
                }
            }
        });
    })();
</script>