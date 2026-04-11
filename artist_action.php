<?php
session_start();

// Kiểm tra quyền Admin (Bảo mật)
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện.']);
    exit();
}

// Kết nối Database
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$action = $_POST['action'] ?? '';

// ================= 1. XỬ LÝ THÊM NGHỆ SĨ MỚI =================
if ($action === 'create_artist') {
    $name = $conn->real_escape_string($_POST['name']);
    $country = $conn->real_escape_string($_POST['country']);
    $bio = $conn->real_escape_string($_POST['bio']);
    
    $imagePath = ''; // Mặc định nếu không up ảnh

    // Xử lý Upload Ảnh
    if (isset($_FILES['artist_image']) && $_FILES['artist_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/artists/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Tự động tạo thư mục nếu chưa có
        }
        $imageName = time() . '_artist_' . basename($_FILES['artist_image']['name']);
        $imagePath = $uploadDir . $imageName;
        move_uploaded_file($_FILES['artist_image']['tmp_name'], $imagePath);
    }

    $sql = "INSERT INTO artists (Name, Country, Bio, Image_URL) VALUES ('$name', '$country', '$bio', '$imagePath')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Đã thêm nghệ sĩ thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi Database: ' . $conn->error]);
    }
} 

// ================= 2. XỬ LÝ SỬA/CẬP NHẬT NGHỆ SĨ =================
elseif ($action === 'update_artist') {
    $artistId = intval($_POST['artist_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $country = $conn->real_escape_string($_POST['country']);
    $bio = $conn->real_escape_string($_POST['bio']);

    // Kiểm tra xem Admin có up ảnh MỚI không
    if (isset($_FILES['artist_image']) && $_FILES['artist_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/artists/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $imageName = time() . '_artist_' . basename($_FILES['artist_image']['name']);
        $imagePath = $uploadDir . $imageName;
        move_uploaded_file($_FILES['artist_image']['tmp_name'], $imagePath);

        // SQL: Cập nhật thông tin VÀ đổi ảnh mới
        $sql = "UPDATE artists SET Name='$name', Country='$country', Bio='$bio', Image_URL='$imagePath' WHERE ArtistID=$artistId";
    } else {
        // SQL: Chỉ cập nhật thông tin, GIỮ NGUYÊN ảnh cũ
        $sql = "UPDATE artists SET Name='$name', Country='$country', Bio='$bio' WHERE ArtistID=$artistId";
    }

    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật nghệ sĩ thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi Database: ' . $conn->error]);
    }
}

$conn->close();
?>