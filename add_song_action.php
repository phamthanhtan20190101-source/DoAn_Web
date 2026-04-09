<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Bạn không có quyền thực hiện hành động này.';
    exit();
}

// File này chỉ là ví dụ cấu trúc. Ở thực tế, bạn cần xử lý upload file và lưu database.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<div style="color: white;">Thêm bài hát thành công. Bạn có thể quay lại danh sách.</div>';
    exit();
}

http_response_code(400);
echo 'Yêu cầu không hợp lệ.';
?>