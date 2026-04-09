<?php
// Trang đăng ký dự phòng nếu người dùng truy cập trực tiếp
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
</head>
<body>
    <h1>Đăng ký tài khoản</h1>
    <form action="register_action.php" method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Đăng ký</button>
    </form>
</body>
</html>