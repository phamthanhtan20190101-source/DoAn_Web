<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// 1. CẬP NHẬT CẤU TRÚC CSDL (Chạy 1 lần ở đầu file cho gọn)
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (Username VARCHAR(100), SongID INT, PRIMARY KEY(Username, SongID))");
$conn->query("CREATE TABLE IF NOT EXISTS user_history (ID INT AUTO_INCREMENT PRIMARY KEY, Username VARCHAR(100), SongID INT, ListenedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS playlist_song (PlaylistID INT, SongID INT, AddedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(PlaylistID, SongID))");
$conn->query("ALTER TABLE playlists ADD COLUMN IF NOT EXISTS IsAdmin TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE playlists ADD COLUMN IF NOT EXISTS Image_URL VARCHAR(255) DEFAULT NULL");
$conn->query("ALTER TABLE account ADD COLUMN IF NOT EXISTS Avatar_URL VARCHAR(255) DEFAULT NULL");

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$username = $conn->real_escape_string($_SESSION['username']);
$accountId = isset($_SESSION['account_id']) ? intval($_SESSION['account_id']) : 0;
if ($accountId <= 0) {
    $accountRes = $conn->query("SELECT AccountID FROM account WHERE Username = '$username' LIMIT 1");
    if ($accountRes && $accountRow = $accountRes->fetch_assoc()) {
        $accountId = intval($accountRow['AccountID']);
    }
}
$action = $_POST['action'] ?? '';
$songId = intval($_POST['song_id'] ?? 0);

// --- 3. XỬ LÝ CÁC HÀNH ĐỘNG ---

// THẢ TIM
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

// LỊCH SỬ NGHE
elseif ($action === 'log_history' && $songId > 0) {
    $last = $conn->query("SELECT SongID FROM user_history WHERE Username='$username' ORDER BY ListenedAt DESC LIMIT 1");
    if (!$last->num_rows || $last->fetch_assoc()['SongID'] != $songId) {
        $conn->query("INSERT INTO user_history (Username, SongID) VALUES ('$username', $songId)");
    }
    echo json_encode(['success' => true, 'status' => 'logged']);
}

// CỘNG VIEW
elseif ($action === 'increment_playcount' && $songId > 0) {
    $conn->query("UPDATE songs SET PlayCount = PlayCount + 1 WHERE SongID = $songId");
    echo json_encode(['success' => true, 'message' => 'View +1']);
}

// TẠO PLAYLIST (CÓ ẢNH)
elseif ($action === 'create_playlist') {
    $title = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 1 : 0;
    $img = 'https://cdn-icons-png.flaticon.com/512/3293/3293810.png';

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên playlist!']);
        exit;
    }

    if ($accountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Không xác định được tài khoản. Vui lòng đăng nhập lại.']);
        exit;
    }

    if (isset($_FILES['playlist_image']) && $_FILES['playlist_image']['error'] == 0) {
        $path = "uploads/playlists/pl_" . time() . "." . pathinfo($_FILES['playlist_image']['name'], PATHINFO_EXTENSION);
        if (!is_dir('uploads/playlists/')) mkdir('uploads/playlists/', 0777, true);
        if (move_uploaded_file($_FILES['playlist_image']['tmp_name'], $path)) $img = $path;
    }

    $stmt = $conn->prepare("INSERT INTO playlists (AccountID, Title, IsAdmin, Image_URL) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $accountId, $title, $isAdmin, $img);
    $success = $stmt->execute();
    echo json_encode(['success' => $success, 'message' => $success ? 'Đã tạo playlist thành công!' : 'Tạo playlist thất bại.']);
    $stmt->close();
}

// LẤY DANH SÁCH PLAYLIST USER (Phần này nãy mình bị thiếu nè!)
elseif ($action === 'get_user_playlists') {
    $res = $conn->query("SELECT PlaylistID, Title FROM playlists WHERE AccountID = $accountId AND IsAdmin = 0 ORDER BY PlaylistID DESC");
    $pls = [];
    while($r = $res->fetch_assoc()) $pls[] = $r;
    echo json_encode(['success' => true, 'playlists' => $pls]);
}

// THÊM BÀI VÀO PLAYLIST
elseif ($action === 'add_to_playlist') {
    $plId = intval($_POST['playlist_id'] ?? 0);
    if ($plId > 0 && $songId > 0) {
        $check = $conn->query("SELECT * FROM playlist_song WHERE PlaylistID = $plId AND SongID = $songId");
        if ($check->num_rows > 0) echo json_encode(['success' => false, 'message' => 'Đã có bài này!']);
        else {
            $conn->query("INSERT INTO playlist_song (PlaylistID, SongID) VALUES ($plId, $songId)");
            echo json_encode(['success' => true, 'message' => 'Đã thêm!']);
        }
    }
}

// SỬA PLAYLIST (ADMIN)
elseif ($action === 'edit_playlist') {
    $plId = intval($_POST['playlist_id'] ?? 0);
    $newT = $conn->real_escape_string(trim($_POST['title'] ?? ''));
    if ($plId > 0 && $_SESSION['role'] === 'admin') {
        $sql = "UPDATE playlists SET Title = '$newT'";
        if (isset($_FILES['playlist_image']) && $_FILES['playlist_image']['error'] == 0) {
            $path = "uploads/playlists/pl_edit_" . time() . "." . pathinfo($_FILES['playlist_image']['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($_FILES['playlist_image']['tmp_name'], $path)) $sql .= ", Image_URL = '$path'";
        }
        $sql .= " WHERE PlaylistID = $plId AND IsAdmin = 1";
        echo json_encode(['success' => $conn->query($sql), 'message' => 'Đã cập nhật!']);
    }
}

// GỠ BÀI KHỎI PLAYLIST
elseif ($action === 'remove_from_playlist') {
    $plId = intval($_POST['playlist_id'] ?? 0);
    if ($plId > 0 && $songId > 0) {
        $conn->query("DELETE FROM playlist_song WHERE PlaylistID = $plId AND SongID = $songId");
        echo json_encode(['success' => true, 'message' => 'Đã gỡ!']);
    }
}

// ĐỔI AVATAR
elseif ($action === 'update_avatar') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $path = "uploads/avatars/av_" . time() . "." . pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        if (!is_dir('uploads/avatars/')) mkdir('uploads/avatars/', 0777, true);
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            $conn->query("UPDATE account SET Avatar_URL = '$path' WHERE Username = '$username'");
            $_SESSION['avatar_path'] = $path;
            echo json_encode(['success' => true, 'avatar_url' => $path]);
        }
    }
}

$conn->close();