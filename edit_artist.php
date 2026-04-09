<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);

$stmt = $conn->prepare("SELECT * FROM artists WHERE ArtistID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$artist = $result->fetch_assoc();
$stmt->close(); $conn->close();

if (!$artist) { echo '<div style="color:white;">Không tìm thấy nghệ sĩ.</div>'; exit(); }
?>
<h2>Sửa Thông Tin Nghệ Sĩ</h2>
<form action="category_action.php" method="POST" data-ajax="true" data-reload-url="admin_artists.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="type" value="artist">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 20px;">
        <label>
            Tên nghệ sĩ
            <input type="text" name="name" value="<?php echo htmlspecialchars($artist['Name'], ENT_QUOTES, 'UTF-8'); ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>
        <label>
            Quốc gia
            <input type="text" name="country" value="<?php echo htmlspecialchars($artist['Country'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>
        <label>
            Tiểu sử
            <textarea name="bio" rows="4" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"><?php echo htmlspecialchars($artist['Bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-admin">Lưu cập nhật</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_artists.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>