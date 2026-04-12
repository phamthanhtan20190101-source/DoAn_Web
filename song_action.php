<?php
session_start();
// 1. MỞ KHÓA CHO TÀI KHOẢN USER
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(403); echo 'Vui lòng đăng nhập để thực hiện.'; exit();
}

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";

function getDbConnection() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
    $conn->set_charset('utf8mb4'); return $conn;
}

function sendJson($success, $message) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]); exit();
}

function getPostValue($key) { return isset($_POST[$key]) ? trim($_POST[$key]) : null; }

function validateMp3Upload(array $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Lỗi upload file: ' . $file['error']);
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'mp3') throw new RuntimeException('Chỉ chấp nhận file có đuôi .mp3');
    return true;
}

function computeDuration(string $filePath) {
    $getid3Path = __DIR__ . '/getid3/getid3.php';
    if (!file_exists($getid3Path)) return 0;
    require_once $getid3Path;
    $getID3 = new getID3();
    $fileInfo = $getID3->analyze($filePath);
    return isset($fileInfo['playtime_seconds']) ? intval(round($fileInfo['playtime_seconds'])) : 0;
}

$action = getPostValue('action');
if (!$action) {
    $payload = json_decode(file_get_contents('php://input'), true);
    $action = $payload['action'] ?? null;
}

try {
    $conn = getDbConnection();

    // =========================================================================
    // XỬ LÝ XÓA BÀI HÁT
    // =========================================================================
    if ($action === 'delete') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $songId = intval($payload['songId']);
        if($_SESSION['role'] !== 'admin') throw new Exception("Bạn không có quyền xóa.");
        $conn->query("DELETE FROM songs WHERE SongID = $songId");
        sendJson(true, 'Xóa bài hát thành công.');
    }

    // =========================================================================
    // XỬ LÝ THÊM MỚI (CREATE) HOẶC CẬP NHẬT (UPDATE)
    // =========================================================================
    if ($action === 'create' || $action === 'update') {
        $title = getPostValue('title');
        $genreId = intval(getPostValue('genre_id'));
        $albumId = !empty($_POST['album_id']) ? intval($_POST['album_id']) : null;
        $releaseDate = getPostValue('release_date') ?: date('Y-m-d');
        $lyrics = getPostValue('lyrics');
        $artistIds = isset($_POST['artist_ids']) ? (array)$_POST['artist_ids'] : [];
        
        // Lấy ID người dùng (nếu chưa có thì mặc định là 1 - admin)
        $accountId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 1;

        if (empty($title)) throw new Exception("Tên bài hát không được để trống.");

        // ---------- THÊM BÀI HÁT MỚI ----------
        if ($action === 'create') {
        validateMp3Upload($_FILES['audio_file']);
        $uploadDir = __DIR__ . '/uploads/songs';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $destinationFile = $uploadDir . '/' . uniqid('song_', true) . '.mp3';
        move_uploaded_file($_FILES['audio_file']['tmp_name'], $destinationFile);

        $duration = computeDuration($destinationFile);
        $filePath = 'uploads/songs/' . basename($destinationFile);

        // 1. XỬ LÝ ẢNH BÌA
        $coverPath = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverDir = __DIR__ . '/uploads/covers';
            if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
            $coverName = time() . '_cover_' . basename($_FILES['cover_image']['name']);
            if(move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverDir . '/' . $coverName)) {
                $coverPath = 'uploads/covers/' . $coverName;
            }
        }

        // 2. PHÂN QUYỀN TRẠNG THÁI (ADMIN = 1, USER = 0)
        $status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 1 : 0;
        $accountId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 1;

        // 3. THÊM VÀO DATABASE BẰNG LỆNH ĐẦY ĐỦ CỘT
        $stmt = $conn->prepare('INSERT INTO songs (Title, GenreID, AlbumID, ReleaseDate, FilePath_URL, Duration, Lyrics, CoverImage_URL, status, AccountID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('siisssssii', $title, $genreId, $albumId, $releaseDate, $filePath, $duration, $lyrics, $coverPath, $status, $accountId);
        $stmt->execute();
        $newSongId = $stmt->insert_id; $stmt->close();

        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        foreach ($artistIds as $aId) { $stmt->bind_param('ii', $newSongId, $aId); $stmt->execute(); }
        $stmt->close(); $conn->close();
        
        // 4. HIỂN THỊ THÔNG BÁO TÙY THEO QUYỀN
        if ($status === 1) {
            echo '<div style="color: #4ade80; background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">✅ Thêm bài hát thành công!</div>'; 
            echo '<img src="x" onerror="setTimeout(() => loadContent(\'admin_songs.php\'), 1500)" style="display:none;">';
        } else {
            echo '<div style="color: #60a5fa; background: rgba(96, 165, 250, 0.15); border: 1px solid #60a5fa; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">⏳ Tải lên thành công! Bài hát đang chờ Admin duyệt.</div>'; 
            echo '<img src="x" onerror="setTimeout(() => loadContent(\'discover.php\'), 2500)" style="display:none;">';
        }
        exit();
    }

    if ($action === 'update') {
        $songId = intval(getPostValue('song_id'));
        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId); $stmt->execute(); $stmt->bind_result($existingPath); $stmt->fetch(); $stmt->close();

        $updatedFilePath = $existingPath;
        $updatedDuration = null;

        // Cập nhật File MP3
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            validateMp3Upload($_FILES['audio_file']);
            $dest = __DIR__ . '/uploads/songs/' . uniqid('song_', true) . '.mp3';
            move_uploaded_file($_FILES['audio_file']['tmp_name'], $dest);
            $updatedDuration = computeDuration($dest);
            $updatedFilePath = 'uploads/songs/' . basename($dest);
            if ($existingPath) safeUnlink(__DIR__ . '/' . ltrim($existingPath, '/'));
        }

        // Cập nhật Ảnh bìa
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverDir = __DIR__ . '/uploads/covers';
            if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
            $coverName = time() . '_cover_' . basename($_FILES['cover_image']['name']);
            if(move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverDir . '/' . $coverName)) {
                $dbCover = 'uploads/covers/' . $coverName;
                $conn->query("UPDATE songs SET CoverImage_URL = '$dbCover' WHERE SongID = $songId");
            }
        }

        if ($updatedDuration !== null) {
            $stmt = $conn->prepare('UPDATE songs SET Title = ?, GenreID = ?, AlbumID = ?, ReleaseDate = ?, FilePath_URL = ?, Duration = ?, Lyrics = ? WHERE SongID = ?');
            $stmt->bind_param('siissssi', $title, $genreId, $albumId, $releaseDate, $updatedFilePath, $updatedDuration, $lyrics, $songId);
        } else {
            $stmt = $conn->prepare('UPDATE songs SET Title = ?, GenreID = ?, AlbumID = ?, ReleaseDate = ?, Lyrics = ? WHERE SongID = ?');
            $stmt->bind_param('siissi', $title, $genreId, $albumId, $releaseDate, $lyrics, $songId);
        }
        $stmt->execute(); $stmt->close();

        $conn->query("DELETE FROM song_artist WHERE SongID = $songId");
        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        foreach ($artistIds as $aId) { $stmt->bind_param('ii', $songId, $aId); $stmt->execute(); }
        $stmt->close(); $conn->close();
        
        echo '<div style="color: #4ade80; background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">✅ Cập nhật bài hát thành công!</div>'; 
        echo '<img src="x" onerror="setTimeout(() => loadContent(\'admin_songs.php\'), 1500)" style="display:none;">';
        exit();
    }

        // ---------- SỬA BÀI HÁT ----------
        if ($action === 'update') {
            $songId = intval(getPostValue('song_id'));
            
            $sql = 'UPDATE songs SET Title = ?, GenreID = ?, AlbumID = ?, ReleaseDate = ?, Lyrics = ? WHERE SongID = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("LỖI CƠ SỞ DỮ LIỆU: " . $conn->error);
            $stmt->bind_param('siissi', $title, $genreId, $albumId, $releaseDate, $lyrics, $songId);
            $stmt->execute(); $stmt->close();

            echo '<div style="color: #4ade80; background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">✅ Cập nhật thành công!</div>'; 
            echo '<img src="x" onerror="setTimeout(() => loadContent(\'admin_songs.php\'), 1500)" style="display:none;">';
            exit();
        }
    }
} catch (Exception $ex) {
    echo '<div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">';
    echo '⚠️ ' . htmlspecialchars($ex->getMessage());
    echo '</div>';
    echo '<button class="btn-admin" style="background: #4b5563; padding: 10px;" onclick="loadContent(\'add_song.php\')">Quay lại</button>';
    exit();
}