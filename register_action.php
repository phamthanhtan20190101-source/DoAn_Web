<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "vertrigo";
$dbname = "song_management";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $email = trim($_POST['email']);

    if ($pass !== $confirm) {
        echo "<script>alert('Mật khẩu và xác nhận mật khẩu không khớp. Vui lòng nhập lại.'); window.history.back();</script>";
        exit();
    }

    $sql = "SELECT AccountID FROM account WHERE Username = ? OR Email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Lỗi truy vấn cơ sở dữ liệu: " . $conn->error);
    }
    $stmt->bind_param("ss", $user, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Tên đăng nhập hoặc Email đã được sử dụng. Vui lòng chọn giá trị khác.'); window.history.back();</script>";
        exit();
    }

    $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
    $insert = "INSERT INTO account (Username, Password, Email, Role, Status, Avatar_URL) VALUES (?, ?, ?, 'user', 1, NULL)";
    $stmt = $conn->prepare($insert);
    if ($stmt === false) {
        die("Lỗi truy vấn cơ sở dữ liệu: " . $conn->error);
    }
    $stmt->bind_param("sss", $user, $passwordHash, $email);

    if ($stmt->execute()) {
        echo "<script>alert('Đăng ký thành công. Bạn có thể đăng nhập ngay bây giờ.'); window.location.href='index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Lỗi khi lưu thông tin. Vui lòng thử lại.'); window.history.back();</script>";
        exit();
    }
}

$conn->close();
?>