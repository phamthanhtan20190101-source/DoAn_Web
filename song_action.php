<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403); echo 'Bạn không có quyền thực hiện hành động này.'; exit();
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

function safeUnlink(string $path) { if (is_file($path)) @unlink($path); }

$action = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
    $action = $payload['action'] ?? null;
} else {
    $action = getPostValue('action');
}

try {
    if ($action === 'delete') {
        $payload = json_decode(file_get_contents('php://input'), true);
        $songId = intval($payload['songId']);
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId); $stmt->execute(); $stmt->bind_result($filePath); $stmt->fetch(); $stmt->close();
        if ($filePath) safeUnlink(__DIR__ . '/' . ltrim($filePath, '/')); 
        $stmt = $conn->prepare('DELETE FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId); $stmt->execute(); $stmt->close(); $conn->close();
        sendJson(true, 'Xóa bài hát thành công.');
    }

    $conn = getDbConnection();
    $title = getPostValue('title');
    $releaseDate = getPostValue('release_date') ?: null;
    $genreId = intval(getPostValue('genre_id'));
    $lyrics = getPostValue('lyrics'); // LẤY DỮ LIỆU LỜI BÀI HÁT
    $artistIds = isset($_POST['artist_ids']) ? (array)$_POST['artist_ids'] : [];
    $albumId = !empty($_POST['album_id']) ? intval($_POST['album_id']) : null;

    if ($action === 'create') {
        validateMp3Upload($_FILES['audio_file']);
        $uploadDir = __DIR__ . '/uploads/songs';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $destinationFile = $uploadDir . '/' . uniqid('song_', true) . '.mp3';
        move_uploaded_file($_FILES['audio_file']['tmp_name'], $destinationFile);

        $duration = computeDuration($destinationFile);
        $storedPath = 'uploads/songs/' . basename($destinationFile);

        // THÊM LYRICS VÀO CÂU LỆNH INSERT
        $stmt = $conn->prepare('INSERT INTO songs (Title, GenreID, AlbumID, ReleaseDate, FilePath_URL, Duration, Lyrics) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('siissss', $title, $genreId, $albumId, $releaseDate, $filePath, $duration, $lyrics);
        $stmt->execute();
        $newSongId = $stmt->insert_id; $stmt->close();

        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        foreach ($artistIds as $aId) { $stmt->bind_param('ii', $newSongId, $aId); $stmt->execute(); }
        $stmt->close(); $conn->close();
        echo '<div style="color: #4ade80;">✅ Thêm bài hát thành công!</div>'; exit();
    }

    if ($action === 'update') {
        $songId = intval(getPostValue('song_id'));
        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId); $stmt->execute(); $stmt->bind_result($existingPath); $stmt->fetch(); $stmt->close();

        $updatedFilePath = $existingPath;
        $updatedDuration = null;

        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            validateMp3Upload($_FILES['audio_file']);
            $dest = __DIR__ . '/uploads/songs/' . uniqid('song_', true) . '.mp3';
            move_uploaded_file($_FILES['audio_file']['tmp_name'], $dest);
            $updatedDuration = computeDuration($dest);
            $updatedFilePath = 'uploads/songs/' . basename($dest);
            if ($existingPath) safeUnlink(__DIR__ . '/' . ltrim($existingPath, '/'));
        }

        // CẬP NHẬT CẢ ALBUM VÀ LYRICS VÀO CÂU LỆNH UPDATE
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
        echo '<div style="color: #4ade80;">✅ Cập nhật bài hát thành công!</div>'; exit();
    }
} catch (Exception $ex) {
    echo '<div style="color: #f87171;">Lỗi: ' . htmlspecialchars($ex->getMessage()) . '</div>'; exit();
}