<!--lưu ten web và bảo trì-->
<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit('Access Denied');

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Duyệt qua các dữ liệu gửi lên và cập nhật vào bảng settings
    foreach ($_POST as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (ConfigKey, ConfigValue) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE ConfigValue = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    
    // Xử lý riêng cho checkbox maintenance_mode (nếu không check thì post sẽ không gửi lên)
    if (!isset($_POST['maintenance_mode'])) {
        $conn->query("UPDATE settings SET ConfigValue = '0' WHERE ConfigKey = 'maintenance_mode'");
    }

    echo '<div style="color: #4ade80; padding: 10px; border: 1px solid #4ade80; border-radius: 5px; margin-bottom: 15px;">
            ✅ Đã lưu cấu hình hệ thống thành công!
          </div>';
    echo '<button type="button" class="btn-admin" onclick="loadContent(\'admin_settings.php\')">Quay lại</button>';
}
$conn->close();
?>