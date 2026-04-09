<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Bạn không có quyền thực hiện hành động này.';
    exit();
}

$servername = "localhost";
$username = "root";
$password = "vertrigo";
$dbname = "song_management";

function getDbConnection() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new RuntimeException('Không thể kết nối cơ sở dữ liệu.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function sendJson($success, $message) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

function getPostValue($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

function validateMp3Upload(array $file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Dữ liệu upload không hợp lệ.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Lỗi upload file: ' . $file['error']);
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'mp3') {
        throw new RuntimeException('Chỉ chấp nhận file MP3.');
    }
    return true;
}

function computeDuration(string $filePath) {
    $getid3Path = __DIR__ . '/getid3/getid3.php';
    if (!file_exists($getid3Path)) {
        throw new RuntimeException('Thư viện getID3 chưa được cài đặt. Vui lòng copy thư mục getid3 vào cạnh file index.php và song_action.php.');
    }
    require_once $getid3Path;
    $getID3 = new getID3();
    $fileInfo = $getID3->analyze($filePath);
    if (isset($fileInfo['playtime_seconds'])) {
        return intval(round($fileInfo['playtime_seconds']));
    }
    if (!empty($fileInfo['error'])) {
        throw new RuntimeException('Không thể phân tích file MP3: ' . implode(' / ', (array)$fileInfo['error']));
    }
    throw new RuntimeException('Không thể xác định thời lượng bài hát.');
}

function safeUnlink(string $path) {
    $uploadsDir = realpath(__DIR__ . '/uploads/songs');
    if (!$uploadsDir) {
        return;
    }
    $realPath = realpath($path);
    if ($realPath && strpos($realPath, $uploadsDir) === 0 && is_file($realPath)) {
        @unlink($realPath);
    }
}

$action = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($payload)) {
        $action = $payload['action'] ?? null;
    }
} else {
    $action = getPostValue('action');
}

try {
    if ($action === 'delete') {
        header('Content-Type: application/json; charset=utf-8');
        $payload = json_decode(file_get_contents('php://input'), true);
        $songId = isset($payload['songId']) ? intval($payload['songId']) : 0;
        if ($songId <= 0) {
            sendJson(false, 'ID bài hát không hợp lệ.');
        }

        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId);
        $stmt->execute();
        $stmt->bind_result($filePath);
        $stmt->fetch();
        $stmt->close();

        if ($filePath) {
            safeUnlink(__DIR__ . '/' . ltrim($filePath, '/')); 
        }

        $stmt = $conn->prepare('DELETE FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        $conn->close();

        if ($affected > 0) {
            sendJson(true, 'Xóa bài hát thành công.');
        }
        sendJson(false, 'Không tìm thấy bài hát cần xóa.');
        exit(); // Dừng thực thi sau khi xóa
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Yêu cầu không hợp lệ.');
    }

    // Mở kết nối Database 1 lần duy nhất để dùng cho toàn bộ quá trình bên dưới
    $conn = getDbConnection();

    $title = getPostValue('title');
    $releaseDate = getPostValue('release_date');
    $releaseDate = $releaseDate !== '' ? $releaseDate : null;

    // --- XỬ LÝ THỂ LOẠI (CŨ HOẶC MỚI GÕ THÊM) ---
    $genreInput = getPostValue('genre_id');
    $genreId = intval($genreInput); 
    // Nếu intval = 0 và chuỗi không rỗng -> Thêm thể loại mới
    if ($genreId === 0 && !empty($genreInput)) {
        $stmtNewGenre = $conn->prepare('INSERT INTO genres (Name) VALUES (?)');
        $stmtNewGenre->bind_param('s', $genreInput);
        $stmtNewGenre->execute();
        $genreId = $stmtNewGenre->insert_id; // Lấy ID vừa tạo
        $stmtNewGenre->close();
    }

    // --- XỬ LÝ CA SĨ (CŨ HOẶC MỚI GÕ THÊM) ---
    $artistInput = getPostValue('artist_id');
    $artistId = intval($artistInput); 
    // Nếu intval = 0 và chuỗi không rỗng -> Thêm nghệ sĩ mới
    if ($artistId === 0 && !empty($artistInput)) {
        $stmtNewArtist = $conn->prepare('INSERT INTO artists (Name) VALUES (?)');
        $stmtNewArtist->bind_param('s', $artistInput);
        $stmtNewArtist->execute();
        $artistId = $stmtNewArtist->insert_id; // Lấy ID vừa tạo
        $stmtNewArtist->close();
    }

    if ($action === 'create') {
        if (empty($title) || $genreId <= 0 || $artistId <= 0) {
            throw new RuntimeException('Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
        if (!isset($_FILES['audio_file'])) {
            throw new RuntimeException('Chưa chọn file MP3.');
        }

        validateMp3Upload($_FILES['audio_file']);
        $uploadDir = __DIR__ . '/uploads/songs';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new RuntimeException('Không tạo được thư mục lưu file.');
        }

        $destinationFile = $uploadDir . '/' . uniqid('song_', true) . '.mp3';
        if (!move_uploaded_file($_FILES['audio_file']['tmp_name'], $destinationFile)) {
            throw new RuntimeException('Không thể lưu file MP3 lên server.');
        }

        $duration = computeDuration($destinationFile);
        $storedPath = 'uploads/songs/' . basename($destinationFile);

        $stmt = $conn->prepare('INSERT INTO songs (Title, Duration, ReleaseDate, FilePath_URL, PlayCount, GenreID) VALUES (?, ?, ?, ?, 0, ?)');
        $stmt->bind_param('sissi', $title, $duration, $releaseDate, $storedPath, $genreId);
        $stmt->execute();
        $newSongId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        $stmt->bind_param('ii', $newSongId, $artistId);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();

        echo '<div style="color: white;">Thêm bài hát thành công.</div>';
        exit();
    }

    if ($action === 'update') {
        $songId = intval(getPostValue('song_id'));
        if ($songId <= 0 || empty($title) || $genreId <= 0 || $artistId <= 0) {
            throw new RuntimeException('Dữ liệu sửa bài hát không hợp lệ.');
        }

        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId);
        $stmt->execute();
        $stmt->bind_result($existingPath);
        $stmt->fetch();
        $stmt->close();

        $updatedFilePath = $existingPath;
        $updatedDuration = null;
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            validateMp3Upload($_FILES['audio_file']);
            $uploadDir = __DIR__ . '/uploads/songs';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new RuntimeException('Không tạo được thư mục lưu file.');
            }
            $destinationFile = $uploadDir . '/' . uniqid('song_', true) . '.mp3';
            if (!move_uploaded_file($_FILES['audio_file']['tmp_name'], $destinationFile)) {
                throw new RuntimeException('Không thể lưu file MP3 mới lên server.');
            }
            $updatedDuration = computeDuration($destinationFile);
            $updatedFilePath = 'uploads/songs/' . basename($destinationFile);
            if ($existingPath) {
                safeUnlink(__DIR__ . '/' . ltrim($existingPath, '/'));
            }
        }

        if ($updatedDuration !== null) {
            $stmt = $conn->prepare('UPDATE songs SET Title = ?, GenreID = ?, ReleaseDate = ?, FilePath_URL = ?, Duration = ? WHERE SongID = ?');
            $stmt->bind_param('sissii', $title, $genreId, $releaseDate, $updatedFilePath, $updatedDuration, $songId);
        } else {
            $stmt = $conn->prepare('UPDATE songs SET Title = ?, GenreID = ?, ReleaseDate = ? WHERE SongID = ?');
            $stmt->bind_param('sisi', $title, $genreId, $releaseDate, $songId);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM song_artist WHERE SongID = ?');
        $stmt->bind_param('i', $songId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        $stmt->bind_param('ii', $songId, $artistId);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();

        echo '<div style="color: white;">Cập nhật bài hát thành công.</div>';
        exit();
    }

    throw new RuntimeException('Hành động không hợp lệ.');
} catch (Exception $ex) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->close(); // Đảm bảo đóng kết nối nếu có lỗi xảy ra
    }
    if ($action === 'delete') {
        sendJson(false, $ex->getMessage());
    }
    echo '<div style="color: red;">Lỗi: ' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
    exit();
}