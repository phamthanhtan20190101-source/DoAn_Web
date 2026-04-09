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

    $sql = "SELECT * FROM account WHERE Username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Lỗi truy vấn cơ sở dữ liệu: " . $conn->error);
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $storedPassword = $row['Password'];
        $passwordValid = password_verify($pass, $storedPassword) || $pass === $storedPassword;

        if ($passwordValid) {
            if ($row['Status'] == 0) {
                echo "<script>alert('Tài khoản của bạn đã bị khóa'); window.location.href='index.php';</script>";
                exit();
            }

            if ($pass === $storedPassword) {
                $newHash = password_hash($pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE account SET Password = ? WHERE AccountID = ?");
                if ($update) {
                    $update->bind_param("si", $newHash, $row['AccountID']);
                    $update->execute();
                    $update->close();
                }
            }

            $_SESSION['is_logged_in'] = true;
            $_SESSION['id'] = $row['AccountID'];
            $_SESSION['username'] = $row['Username'];
            $_SESSION['email'] = $row['Email'];
            $_SESSION['role'] = $row['Role'];
            header("Location: index.php");
            exit();
        }
    }

    echo "<script>alert('Sai tên đăng nhập hoặc mật khẩu'); window.location.href='index.php';</script>";
    exit();
}

$conn->close();
?>