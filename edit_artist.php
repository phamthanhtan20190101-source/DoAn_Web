<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$artistId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = $conn->query("SELECT * FROM artists WHERE ArtistID = $artistId");
$artist = $result->fetch_assoc();

if (!$artist) {
    echo '<div style="color:white;">Không tìm thấy thông tin nghệ sĩ.</div>';
    exit();
}
?>
<h2>Sửa Thông Tin Nghệ Sĩ</h2>

<form action="artist_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-delay-reload-url="admin_artists.php" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Đang lưu...';">
    <input type="hidden" name="action" value="update_artist">
    <input type="hidden" name="artist_id" value="<?php echo $artist['ArtistID']; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 15px;">
        
        <label>
            Tên nghệ sĩ
            <input type="text" name="name" value="<?php echo htmlspecialchars($artist['Name'] ?? ''); ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <label>
            Ảnh nghệ sĩ (.jpg, .png) <span style="color: gray; font-size: 12px;">(Bỏ trống nếu giữ nguyên ảnh cũ)</span>
            <input type="file" name="artist_image" accept="image/jpeg, image/png, image/jpg" style="margin-top: 5px; width: 100%;">
            <?php if (!empty($artist['Image_URL'])): ?>
                <div style="margin-top: 10px; font-size: 12px; color: #aee2ff;">
                    Đang dùng ảnh: <img src="<?php echo $artist['Image_URL']; ?>" style="height: 40px; border-radius: 4px; vertical-align: middle; margin-left: 10px;">
                </div>
            <?php endif; ?>
        </label>

        <label>
            Quốc gia
            <select name="country" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                <option value="Việt Nam" <?php echo (($artist['Country'] ?? '') == 'Việt Nam') ? 'selected' : ''; ?>>Việt Nam (V-Pop)</option>
                <option value="US-UK" <?php echo (($artist['Country'] ?? '') == 'US-UK') ? 'selected' : ''; ?>>Âu Mỹ (US-UK)</option>
                <option value="Hàn Quốc" <?php echo (($artist['Country'] ?? '') == 'Hàn Quốc') ? 'selected' : ''; ?>>Hàn Quốc (K-Pop)</option>
            </select>
        </label>

        <label>
            Tiểu sử
            <textarea name="bio" rows="5" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"><?php echo htmlspecialchars($artist['Bio'] ?? ''); ?></textarea>
        </label>

        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background-color: var(--purple-primary); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Lưu cập nhật</button>
            <button type="button" onclick="loadContent('admin_artists.php')" style="background-color: #4b5563; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Hủy bỏ</button>
        </div>
    </div>
</form>
<?php $conn->close(); ?>