<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Tự động tạo các bảng CSDL nếu chưa tồn tại
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (Username VARCHAR(100), SongID INT, PRIMARY KEY(Username, SongID))");
$conn->query("CREATE TABLE IF NOT EXISTS user_history (ID INT AUTO_INCREMENT PRIMARY KEY, Username VARCHAR(100), SongID INT, ListenedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS playlists (PlaylistID INT AUTO_INCREMENT PRIMARY KEY, AccountID INT, Title VARCHAR(255), CoverImage VARCHAR(255), IsAdmin TINYINT(1) DEFAULT 0, CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
// BẢNG MỚI: Liên kết Bài hát vào Playlist
$conn->query("CREATE TABLE IF NOT EXISTS playlist_songs (PlaylistID INT, SongID INT, AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(PlaylistID, SongID))");

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
elseif ($action === 'create_playlist') {
    $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    if (!empty($title) && $accountId > 0) {
        $conn->query("INSERT INTO playlists (AccountID, Title, IsAdmin) VALUES ($accountId, '$title', 0)");
        echo json_encode(['success' => true, 'message' => 'Tạo Playlist thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên Playlist không hợp lệ.']);
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
            $checkExist = $conn->query("SELECT * FROM playlist_songs WHERE PlaylistID = $playlistId AND SongID = $songId");
            if ($checkExist->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Bài hát này đã có sẵn trong Playlist!']);
            } else {
                $conn->query("INSERT INTO playlist_songs (PlaylistID, SongID) VALUES ($playlistId, $songId)");
                echo json_encode(['success' => true, 'message' => 'Đã thêm bài hát vào Playlist!']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sửa Playlist này.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    }
}

$conn->close();
?>