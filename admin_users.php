<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Quản lý Người dùng</h2>
<div style="margin-top: 20px; color: white;">
    <p>Danh sách tài khoản sẽ được hiển thị tại đây.</p>
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>admin</td>
                <td>admin</td>
                <td><button type="button" class="btn-admin">Khóa</button> <button type="button" class="btn-admin">Admin</button></td>
            </tr>
        </tbody>
    </table>
</div>