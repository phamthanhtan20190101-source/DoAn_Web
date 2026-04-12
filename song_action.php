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

// =========================================================================
// [MỚI] XỬ LÝ TAGS ĐỘNG (TỰ ĐỘNG THÊM THỂ LOẠI / CA SĨ NẾU USER GÕ TAY)
// =========================================================================
if (in_array($action, ['create', 'update'])) {
    $connAuto = getDbConnection();
    
    // 1. Xử lý Thể loại mới
    $genreInput = $_POST['genre_id'] ?? '';
    if (!empty($genreInput) && !is_numeric($genreInput)) {
        $newGenre = trim($genreInput);
        $stmt = $connAuto->prepare("SELECT GenreID FROM genres WHERE Name = ?");
        $stmt->bind_param("s", $newGenre);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_POST['genre_id'] = $row['GenreID']; // Đã có trong DB -> Lấy ID
        } else {
            $stmtIns = $connAuto->prepare("INSERT INTO genres (Name) VALUES (?)");
            $stmtIns->bind_param("s", $newGenre);
            $stmtIns->execute();
            $_POST['genre_id'] = $stmtIns->insert_id; // Thêm mới -> Lấy ID mới
            $stmtIns->close();
        }
        $stmt->close();
    }

    // 2. Xử lý Ca sĩ mới
    $artistInputs = $_POST['artist_ids'] ?? [];
    $finalArtistIds = [];
    if (is_array($artistInputs)) {
        foreach ($artistInputs as $aInput) {
            if (is_numeric($aInput)) {
                $finalArtistIds[] = intval($aInput);
            } elseif (!empty(trim($aInput))) {
                $newArtist = trim($aInput);
                $stmt = $connAuto->prepare("SELECT ArtistID FROM artists WHERE Name = ?");
                $stmt->bind_param("s", $newArtist);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $finalArtistIds[] = $row['ArtistID'];
                } else {
                    $stmtIns = $connAuto->prepare("INSERT INTO artists (Name, Country) VALUES (?, 'Chưa rõ')");
                    $stmtIns->bind_param("s", $newArtist);
                    $stmtIns->execute();
                    $finalArtistIds[] = $stmtIns->insert_id;
                    $stmtIns->close();
                }
                $stmt->close();
            }
        }
        $_POST['artist_ids'] = $finalArtistIds;
    }
    $connAuto->close();
}

// =========================================================================
// CHỐT KIỂM TRA RÀNG BUỘC DỮ LIỆU (VALIDATION CHECKPOINT)
// =========================================================================
if (in_array($action, ['create', 'update'])) {
    try {
        $titleCheck = trim($_POST['title'] ?? '');
        $genreCheck = intval($_POST['genre_id'] ?? 0);
        $artistsCheck = $_POST['artist_ids'] ?? [];
        $dateCheck = trim($_POST['release_date'] ?? '');
        $idCheck = intval($_POST['song_id'] ?? 0);

        // [RÀNG BUỘC 1] Bắt buộc nhập
        if (empty($titleCheck)) throw new Exception("Tên bài hát không được để trống.");
        if (empty($genreCheck)) throw new Exception("Vui lòng chọn Thể loại cho bài hát.");
        if (empty($artistsCheck) || !is_array($artistsCheck)) throw new Exception("Vui lòng chọn ít nhất một Ca sĩ.");

        // [RÀNG BUỘC 2] Ngày phát hành không được lớn hơn hiện tại
        if (!empty($dateCheck)) {
            $currentDate = date('Y-m-d');
            if ($dateCheck > $currentDate) throw new Exception("Ngày phát hành ($dateCheck) không được vượt quá ngày hôm nay.");
            $year = intval(date('Y', strtotime($dateCheck)));
            if ($year < 1900) throw new Exception("Ngày phát hành không hợp lệ (Phải từ năm 1900 trở lên).");
        }

        // [RÀNG BUỘC 3] Kiểm tra định dạng file âm thanh
        if ($action === 'create') {
            if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Vui lòng tải lên file âm thanh (.mp3) cho bài hát mới.");
            }
        }
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $fileExt = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
            if ($fileExt !== 'mp3') {
                throw new Exception("Hệ thống chỉ hỗ trợ file âm thanh định dạng .MP3!");
            }
        }

        // [RÀNG BUỘC 4] Chống trùng lặp (Cùng Tên bài hát + Cùng Ca sĩ)
        $connCheck = getDbConnection();
        $artistInClause = implode(',', array_map('intval', $artistsCheck));
        $checkDupSql = "SELECT s.SongID FROM songs s 
                        JOIN song_artist sa ON s.SongID = sa.SongID 
                        WHERE s.Title = ? AND sa.ArtistID IN ($artistInClause) AND s.SongID != ?";
        $stmtCheck = $connCheck->prepare($checkDupSql);
        $stmtCheck->bind_param("si", $titleCheck, $idCheck);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $stmtCheck->close();
            throw new Exception("Bài hát '$titleCheck' do (các) ca sĩ này thể hiện đã tồn tại trong hệ thống. Vui lòng kiểm tra lại để tránh trùng lặp!");
        }
        $stmtCheck->close();
        $connCheck->close(); // Đóng kết nối tạm thời của khối kiểm tra

    } catch (Exception $e) {
        // NẾU VI PHẠM: IN RA BẢNG ĐỎ VÀ DỪNG CHƯƠNG TRÌNH NGAY LẬP TỨC
        http_response_code(200); 
        echo '<div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 15px;">';
        echo '⚠️ KHÔNG THỂ LƯU: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        
        // [TRICK BẢO VỆ] Đóng băng chức năng tự động chuyển trang trong 1 giây, và bật Popup
        echo '<img src="x" onerror="
            if(typeof window.loadContent === \'function\') {
                var oldFunc = window.loadContent;
                window.loadContent = function(){ console.log(\'Đã chặn auto-reload để hiện lỗi!\'); }; 
                setTimeout(function(){ window.loadContent = oldFunc; }, 1000);
            }
            alert(\'⚠️ LỖI: ' . addslashes($e->getMessage()) . '\');
        " style="display:none;">';

        echo '<button type="button" class="btn-admin" style="margin-top:10px; background: #4b5563;" onclick="loadContent(\'admin_songs.php\')"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</button>';
        exit(); 
    }
}
// =========================================================================

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
        $filePath = 'uploads/songs/' . basename($destinationFile); // Đã sửa biến này để đồng nhất với hàm bind_param bên dưới

        // THÊM LYRICS VÀO CÂU LỆNH INSERT
        $stmt = $conn->prepare('INSERT INTO songs (Title, GenreID, AlbumID, ReleaseDate, FilePath_URL, Duration, Lyrics) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('siissss', $title, $genreId, $albumId, $releaseDate, $filePath, $duration, $lyrics);
        $stmt->execute();
        $newSongId = $stmt->insert_id; $stmt->close();

        $stmt = $conn->prepare('INSERT INTO song_artist (SongID, ArtistID) VALUES (?, ?)');
        foreach ($artistIds as $aId) { $stmt->bind_param('ii', $newSongId, $aId); $stmt->execute(); }
        $stmt->close(); $conn->close();
        
        // Thêm chuyển hướng tự động bằng JS khi thành công
        echo '<div style="color: #4ade80; background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">✅ Thêm bài hát thành công!</div>'; 
        echo '<img src="x" onerror="setTimeout(() => loadContent(\'admin_songs.php\'), 1500)" style="display:none;">';
        exit();
    }

   if ($action === 'update') {
        $songId = intval(getPostValue('song_id'));
        $stmt = $conn->prepare('SELECT FilePath_URL FROM songs WHERE SongID = ?');
        $stmt->bind_param('i', $songId); $stmt->execute(); $stmt->bind_result($existingPath); $stmt->fetch(); $stmt->close();

        $updatedFilePath = $existingPath;
        $updatedDuration = null;

        // ===============================================================
        // 1. XỬ LÝ UPLOAD ẢNH BÌA (COVER IMAGE) NẾU CÓ CHỌN ẢNH MỚI
        // ===============================================================
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverDir = __DIR__ . '/uploads/covers';
            if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
            $coverName = uniqid('cover_', true) . '_' . basename($_FILES['cover_image']['name']);
            $coverPath = $coverDir . '/' . $coverName;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverPath)) {
                $dbCoverPath = 'uploads/covers/' . $coverName;
                // Cập nhật riêng cột ảnh bìa vào CSDL
                $stmtCover = $conn->prepare('UPDATE songs SET CoverImage_URL = ? WHERE SongID = ?');
                $stmtCover->bind_param('si', $dbCoverPath, $songId);
                $stmtCover->execute(); $stmtCover->close();
            }
        }

        // ===============================================================
        // 2. XỬ LÝ UPLOAD FILE NHẠC (MP3) NẾU CÓ CHỌN FILE MỚI
        // ===============================================================
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            validateMp3Upload($_FILES['audio_file']);
            $dest = __DIR__ . '/uploads/songs/' . uniqid('song_', true) . '.mp3';
            move_uploaded_file($_FILES['audio_file']['tmp_name'], $dest);
            $updatedDuration = computeDuration($dest);
            $updatedFilePath = 'uploads/songs/' . basename($dest);
            if ($existingPath) safeUnlink(__DIR__ . '/' . ltrim($existingPath, '/'));
        }

        // ===============================================================
        // 3. CẬP NHẬT THÔNG TIN CHỮ VÀ CA SĨ
        // ===============================================================
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
        
        // Thêm chuyển hướng tự động bằng JS khi thành công
        echo '<div style="color: #4ade80; background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">✅ Cập nhật bài hát và ảnh bìa thành công!</div>'; 
        echo '<img src="x" onerror="setTimeout(() => loadContent(\'admin_songs.php\'), 1500)" style="display:none;">';
        exit();
    }
} catch (Exception $ex) {
    // [TRICK BẢO VỆ] Đóng băng chức năng tự động chuyển trang trong 1 giây, và bật Popup
    echo '<img src="x" onerror="
        if(typeof window.loadContent === \'function\') {
            var oldFunc = window.loadContent;
            window.loadContent = function(){ console.log(\'Đã chặn auto-reload để hiện lỗi!\'); }; 
            setTimeout(function(){ window.loadContent = oldFunc; }, 1000);
        }
        alert(\'⚠️ LỖI: ' . addslashes($ex->getMessage()) . '\');
    " style="display:none;">';
    
    echo '<div style="color: #f87171; background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">Lỗi: ' . htmlspecialchars($ex->getMessage()) . '</div>'; 
    exit();
}