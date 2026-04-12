<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Tự động tạo các bảng CSDL nếu chưa tồn tại
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (Username VARCHAR(100), SongID INT, PRIMARY KEY(Username, SongID))");
$conn->query("CREATE TABLE IF NOT EXISTS user_history (ID INT AUTO_INCREMENT PRIMARY KEY, Username VARCHAR(100), SongID INT, ListenedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS playlists (PlaylistID INT AUTO_INCREMENT PRIMARY KEY, AccountID INT, Title VARCHAR(255), CoverImage VARCHAR(255), IsAdmin TINYINT(1) DEFAULT 0, CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
// BẢNG MỚI: Liên kết Bài hát vào Playlist
$conn->query("CREATE TABLE IF NOT EXISTS playlist_song (PlaylistID INT, SongID INT, AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(PlaylistID, SongID))");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng tính năng!']);
    exit;
}

$username = $conn->real_escape_string($_SESSION['username']);
$accountId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$action = $_POST['action'] ?? '';
$songId = intval($_POST['song_id'] ?? 0);

if ($action === 'toggle_favorite' && $songId > 0) {
    $check = $conn->query("SELECT * FROM user_favorites WHERE Username='$username' AND SongID=$songId");
    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM user_favorites WHERE Username='$username' AND SongID=$songId");
        echo json_encode(['success' => true, 'status' => 'removed']);
    } else {
        $conn->query("INSERT INTO user_favorites (Username, SongID) VALUES ('$username', $songId)");
        echo json_encode(['success' => true, 'status' => 'added']);
    }
} 
elseif ($action === 'log_history' && $songId > 0) {
    // Chỉ lưu vào lịch sử cá nhân (Không cộng PlayCount ở đây nữa)
    $last = $conn->query("SELECT SongID FROM user_history WHERE Username='$username' ORDER BY ListenedAt DESC LIMIT 1");
    $lastSongId = ($last->num_rows > 0) ? $last->fetch_assoc()['SongID'] : 0;
    if ($lastSongId != $songId) {
        $conn->query("INSERT INTO user_history (Username, SongID) VALUES ('$username', $songId)");
    }
    echo json_encode(['success' => true, 'status' => 'logged']);
}
// ===== ĐOẠN MỚI: XỬ LÝ CỘNG VIEW KHI ĐẠT 50% =====
elseif ($action === 'increment_playcount' && $songId > 0) {
    // Cộng 1 lượt nghe vào tổng số view của bài hát
    $conn->query("UPDATE songs SET PlayCount = PlayCount + 1 WHERE SongID = $songId");
    echo json_encode(['success' => true, 'message' => 'Đã cộng 1 lượt nghe']);
}
// ===== ĐOẠN SỬA LẠI: TẠO PLAYLIST (CHO CẢ USER & ADMIN) =====
elseif ($action === 'create_playlist') {
    $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    
    // 1. Kiểm tra và tự động thêm cột IsAdmin nếu database chưa có (Tránh lỗi sập PHP)
    $conn->query("ALTER TABLE playlists ADD COLUMN IF NOT EXISTS IsAdmin TINYINT(1) DEFAULT 0");

    // 2. Xác định xem ai đang tạo (Admin hay User)
    $isAdminFlag = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 1 : 0;

    if (!empty($title) && $accountId > 0) {
        // 3. Thực hiện lưu vào CSDL
        $sql = "INSERT INTO playlists (AccountID, Title, IsAdmin) VALUES ($accountId, '$title', $isAdminFlag)";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Tạo Playlist thành công!']);
        } else {
            // Nếu lỗi SQL, báo lỗi rõ ràng để debug
            echo json_encode(['success' => false, 'message' => 'Lỗi SQL: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ (Tên trống hoặc chưa đăng nhập).']);
    }
}
// ===== ĐOẠN MỚI THÊM: XỬ LÝ THÊM BÀI HÁT VÀO PLAYLIST =====
elseif ($action === 'get_user_playlists') {
    // Lấy danh sách Playlist của User để hiển thị lên Cửa sổ chọn
    $res = $conn->query("SELECT PlaylistID, Title FROM playlists WHERE AccountID = $accountId ORDER BY PlaylistID DESC");
    $pls = [];
    while($r = $res->fetch_assoc()) $pls[] = $r;
    echo json_encode(['success' => true, 'playlists' => $pls]);
}
elseif ($action === 'add_to_playlist') {
    $playlistId = intval($_POST['playlist_id'] ?? 0);
    if ($playlistId > 0 && $songId > 0 && $accountId > 0) {
        // 1. Kiểm tra xem Playlist này có đúng là của User này không (Bảo mật)
        $checkPl = $conn->query("SELECT PlaylistID FROM playlists WHERE PlaylistID = $playlistId AND AccountID = $accountId");
        if ($checkPl->num_rows > 0) {
            // 2. Kiểm tra xem bài này đã có trong Playlist chưa
            $checkExist = $conn->query("SELECT * FROM playlist_song WHERE PlaylistID = $playlistId AND SongID = $songId");
            if ($checkExist->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Bài hát này đã có sẵn trong Playlist!']);
            } else {
                $conn->query("INSERT INTO playlist_song (PlaylistID, SongID) VALUES ($playlistId, $songId)");
                echo json_encode(['success' => true, 'message' => 'Đã thêm bài hát vào Playlist!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa Playlist này.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    }
}
// ===== ĐOẠN MỚI THÊM: XỬ LÝ ĐỔI ẢNH ĐẠI DIỆN =====
elseif ($action === 'update_avatar') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        
        // 1. Tạo thư mục chứa ảnh nếu chưa có
        $uploadDir = 'uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // 2. Đổi tên file ảnh cho khỏi trùng
        $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = "avatar_" . $username . "_" . time() . "." . $fileExtension;
        $uploadFilePath = $uploadDir . $fileName;

        // 3. Upload file vào thư mục
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFilePath)) {
            
            // =========================================================
            // BƯỚC QUAN TRỌNG: TÊN BẢNG TÀI KHOẢN CỦA BẠN LÀ GÌ?
            $tableName = 'account'; 
            // =========================================================

            // Tự động thêm cột Avatar_URL vào bảng nếu chưa có (Tránh lỗi sập PHP)
            $conn->query("ALTER TABLE $tableName ADD COLUMN IF NOT EXISTS Avatar_URL VARCHAR(255) DEFAULT NULL");

            // Cập nhật đường dẫn ảnh vào database
            $stmt = $conn->prepare("UPDATE $tableName SET Avatar_URL = ? WHERE Username = ?");
            
            if ($stmt) { // Nếu câu lệnh SQL chuẩn xác
                $stmt->bind_param("ss", $uploadFilePath, $username);
                
                if ($stmt->execute()) {
                    // Cập nhật thành công, lưu đường dẫn vào session
                    $_SESSION['avatar_path'] = $uploadFilePath;
                    echo json_encode(['success' => true, 'avatar_url' => $uploadFilePath]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lỗi lưu vào Database!']);
                }
                $stmt->close();
            } else {
                // Nếu tên bảng bị sai, báo lỗi thẳng ra màn hình cho dễ sửa
                echo json_encode(['success' => false, 'message' => 'Lỗi SQL (Sai tên bảng?): ' . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi không thể lưu file ảnh vào thư mục!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy file ảnh hoặc file bị lỗi.']);
    }
}
elseif ($action === 'edit_playlist') {
    $playlistId = intval($_POST['playlist_id'] ?? 0);
    $newTitle = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    
    // Chỉ cho phép sửa nếu là Admin
    if ($playlistId > 0 && !empty($newTitle) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $sql = "UPDATE playlists SET Title = '$newTitle' WHERE PlaylistID = $playlistId AND IsAdmin = 1";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật CSDL.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền hoặc dữ liệu sai.']);
    }
}
// ===== ĐOẠN MỚI THÊM: ADMIN GỠ BÀI KHỎI PLAYLIST MẪU =====
elseif ($action === 'remove_from_playlist') {
    $playlistId = intval($_POST['playlist_id'] ?? 0);
    $songId = intval($_POST['song_id'] ?? 0);
    
    // Kiểm tra quyền Admin (Bảo mật)
    if ($playlistId > 0 && $songId > 0 && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $sql = "DELETE FROM playlist_song WHERE PlaylistID = $playlistId AND SongID = $songId";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Đã gỡ bài hát khỏi playlist mẫu!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi xử lý cơ sở dữ liệu.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    }
}
$conn->close();
?>