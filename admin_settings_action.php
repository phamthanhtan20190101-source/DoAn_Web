<?php
session_start();
// Chỉ Admin mới có quyền chạy file này
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Từ chối truy cập!']);
    exit;
}

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$action = $_POST['action'] ?? '';

if ($action === 'update_settings') {
    $siteName = $conn->real_escape_string($_POST['site_name'] ?? '');
    $footerInfo = $conn->real_escape_string($_POST['footer_info'] ?? '');
    $maintenance = isset($_POST['maintenance_mode']) ? '1' : '0';

    // Cập nhật từng dòng trong bảng settings dựa trên ConfigKey
    $q1 = "UPDATE settings SET ConfigValue = '$siteName' WHERE ConfigKey = 'site_name'";
    $q2 = "UPDATE settings SET ConfigValue = '$footerInfo' WHERE ConfigKey = 'footer_info'";
    $q3 = "UPDATE settings SET ConfigValue = '$maintenance' WHERE ConfigKey = 'maintenance_mode'";

    if ($conn->query($q1) && $conn->query($q2) && $conn->query($q3)) {
        echo json_encode(['success' => true, 'message' => 'Hệ thống đã cập nhật cấu hình mới!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $conn->error]);
    }
}
$conn->close();
?>