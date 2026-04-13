<?php
session_start();
// 1. Kết nối CSDL
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// 2. Lấy toàn bộ cấu hình từ bảng settings
$res = $conn->query("SELECT * FROM settings");
$configs = [];
if ($res) {
    while($row = $res->fetch_assoc()) { 
        $configs[$row['ConfigKey']] = $row['ConfigValue']; 
    }
}

// Đóng kết nối tạm thời để bảo mật
$conn->close();

// Gán giá trị mặc định nếu database chưa có dữ liệu để tránh lỗi trắng ô
$site_name = $configs['site_name'] ?? 'Lyrx Music';
$footer_info = $configs['footer_info'] ?? '© 2026 Lyrx Music';
$maintenance = $configs['maintenance_mode'] ?? '0';
?>

<div style="padding: 20px;">
    <h2 style="color: white; margin-bottom: 30px; font-weight: 800; font-size: 24px;">Cấu hình hệ thống</h2>

    <form action="settings_action.php" method="POST" data-ajax="true" style="max-width: 650px; color: white;">
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="color: var(--text-secondary); font-size: 14px; font-weight: 600;">Tên Website</label>
                <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" 
                       style="width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; outline: none; font-size: 15px;" 
                       placeholder="Ví dụ: Lyrx Music">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="color: var(--text-secondary); font-size: 14px; font-weight: 600;">Thông tin chân trang (Footer)</label>
                <textarea name="footer_info" 
                          style="width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; outline: none; font-size: 15px; height: 100px; resize: vertical;"><?php echo htmlspecialchars($footer_info); ?></textarea>
            </div>

            <div style="padding: 15px; background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); border-radius: 8px;">
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo $maintenance == '1' ? 'checked' : ''; ?> 
                           style="width: 18px; height: 18px; accent-color: var(--purple-primary);">
                    <span style="font-size: 14px; font-weight: 500;">Bật chế độ bảo trì (Người dùng sẽ không thể nghe nhạc)</span>
                </label>
            </div>

            <button type="submit" class="btn-admin" 
                    style="background: var(--purple-primary); color: white; border: none; padding: 12px 40px; border-radius: 25px; font-weight: 700; cursor: pointer; width: fit-content; transition: 0.3s; margin-top: 10px;"
                    onmouseover="this.style.opacity='0.9'; this.style.transform='scale(1.02)'"
                    onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                <i class="fa-solid fa-floppy-disk" style="margin-right: 8px;"></i> LƯU CẤU HÌNH
            </button>
        </div>
    </form>
</div>