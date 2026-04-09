<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Quản lý bài hát</h2>
<div style="margin-top: 20px; color: white;">
    <p>Đây là trang quản lý bài hát động.</p>
</div>