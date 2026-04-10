<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$res = $conn->query("SELECT * FROM settings");
$configs = [];
while($row = $res->fetch_assoc()) { $configs[$row['ConfigKey']] = $row['ConfigValue']; }
?>
<h2 style="color: white; margin-bottom: 20px;">Cấu hình hệ thống</h2>
<form action="settings_action.php" method="POST" data-ajax="true" style="max-width: 600px; color: white;">
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <label>Tên Website
            <input type="text" name="site_name" value="<?php echo $configs['site_name']; ?>" style="width: 100%; padding: 10px; margin-top: 5px;">
        </label>
        
        <label>Thông tin chân trang (Footer)
            <textarea name="footer_info" style="width: 100%; padding: 10px; margin-top: 5px; height: 80px;"><?php echo $configs['footer_info']; ?></textarea>
        </label>
        
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            <input type="checkbox" name="maintenance_mode" value="1" <?php echo $configs['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
            Bật chế độ bảo trì (Người dùng sẽ không thể nghe nhạc)
        </label>
        
        <button type="submit" class="btn-admin" style="background: var(--purple-primary); width: fit-content; padding: 10px 30px;">Lưu cấu hình</button>
    </div>
</form>