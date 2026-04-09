<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Quản lý Playlist</h2>
<div style="margin-top: 20px; color: white;">
    <button type="button" class="btn-admin">Tạo Playlist</button>
    <button type="button" class="btn-admin">Duyệt Playlist</button>
    <p style="margin-top: 20px;">Danh sách playlist sẽ hiển thị tại đây.</p>
</div>