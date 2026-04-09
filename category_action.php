<?php
session_start();

// 1. Kiểm tra quyền Admin
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit();
}

// 2. Kết nối Database
$servername = "localhost";
$username = "root";
$password = "vertrigo"; // Thay đổi nếu mật khẩu XAMPP của bạn khác
$dbname = "song_management";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.']);
    exit();
}
$conn->set_charset('utf8mb4');

// Hàm trả về JSON cho các request AJAX (Xóa)
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Hàm lấy dữ liệu POST an toàn
function getPostVal($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

// =========================================================================
// XỬ LÝ YÊU CẦU DẠNG AJAX (Thường dùng cho chức năng XÓA)
// =========================================================================
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($contentType, 'application/json') !== false) {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() === JSON_ERROR_NONE && isset($payload['action']) && $payload['action'] === 'delete') {
        
        $type = isset($payload['type']) ? $payload['type'] : '';
        $id = isset($payload['id']) ? intval($payload['id']) : 0;

        if ($id <= 0) sendJsonResponse(false, 'ID không hợp lệ.');

        $table = '';
        $idColumn = '';

        // Xác định bảng và cột khóa chính cần xóa
        if ($type === 'genre') {
            $table = 'genres'; $idColumn = 'GenreID';
        } elseif ($type === 'artist') {
            $table = 'artists'; $idColumn = 'ArtistID';
        } elseif ($type === 'album') {
            $table = 'albums'; $idColumn = 'AlbumID';
            
            // Xóa ảnh bìa vật lý nếu là Album
            $stmtImg = $conn->prepare("SELECT CoverImage_URL FROM albums WHERE AlbumID = ?");
            if ($stmtImg) {
                $stmtImg->bind_param('i', $id);
                $stmtImg->execute();
                $stmtImg->bind_result($coverPath);
                if ($stmtImg->fetch() && !empty($coverPath)) {
                    $realPath = __DIR__ . '/' . ltrim($coverPath, '/');
                    if (file_exists($realPath)) @unlink($realPath);
                }
                $stmtImg->close();
            }
        } else {
            sendJsonResponse(false, 'Loại danh mục không hợp lệ.');
        }

        // Thực thi lệnh xóa
        $stmt = $conn->prepare("DELETE FROM $table WHERE $idColumn = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                sendJsonResponse(true, 'Đã xóa thành công.');
            } else {
                // Lỗi thường do khóa ngoại (ví dụ: Thể loại này đang có bài hát)
                sendJsonResponse(false, 'Không thể xóa. Có thể danh mục này đang chứa dữ liệu (Bài hát). Lỗi: ' . $conn->error);
            }
            $stmt->close();
        }
        sendJsonResponse(false, 'Lỗi hệ thống khi xóa.');
    }
}

// =========================================================================
// XỬ LÝ YÊU CẦU TỪ FORM (THÊM / SỬA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = getPostVal('action');
    $type = getPostVal('type'); // 'genre', 'artist', 'album'
    
    if (empty($action) || empty($type)) {
        echo '<div style="color: red;">Yêu cầu không hợp lệ.</div>';
        exit();
    }

    try {
        // ---------------------------------------------------------
        // A. XỬ LÝ THỂ LOẠI (GENRES)
        // ---------------------------------------------------------
        if ($type === 'genre') {
            $name = getPostVal('name');
            if (empty($name)) throw new Exception("Tên thể loại không được để trống.");

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO genres (Name) VALUES (?)");
                $stmt->bind_param("s", $name);
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Thêm thể loại thành công!</div>';
            } 
            elseif ($action === 'update') {
                $id = intval(getPostVal('id'));
                $stmt = $conn->prepare("UPDATE genres SET Name = ? WHERE GenreID = ?");
                $stmt->bind_param("si", $name, $id);
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Cập nhật thể loại thành công!</div>';
            }
        }
        // ---------------------------------------------------------
        // B. XỬ LÝ NGHỆ SĨ (ARTISTS)
        // ---------------------------------------------------------
        elseif ($type === 'artist') {
            $name = getPostVal('name');
            $country = getPostVal('country');
            $bio = getPostVal('bio');
            
            if (empty($name)) throw new Exception("Tên nghệ sĩ không được để trống.");

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO artists (Name, Country, Bio) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $country, $bio);
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Thêm nghệ sĩ thành công!</div>';
            } 
            elseif ($action === 'update') {
                $id = intval(getPostVal('id'));
                $stmt = $conn->prepare("UPDATE artists SET Name = ?, Country = ?, Bio = ? WHERE ArtistID = ?");
                $stmt->bind_param("sssi", $name, $country, $bio, $id);
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Cập nhật nghệ sĩ thành công!</div>';
            }
        }
        // ---------------------------------------------------------
        // C. XỬ LÝ ALBUM (ALBUMS)
        // ---------------------------------------------------------
        elseif ($type === 'album') {
            $title = getPostVal('title');
            $releaseYear = getPostVal('release_year');
            $releaseYear = !empty($releaseYear) ? intval($releaseYear) : null;

            if (empty($title)) throw new Exception("Tên album không được để trống.");

            // Xử lý upload ảnh bìa (nếu có)
            $coverUrl = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/albums';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                
                $fileName = uniqid('album_') . '_' . basename($_FILES['cover_image']['name']);
                $destPath = $uploadDir . '/' . $fileName;
                
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destPath)) {
                    $coverUrl = 'uploads/albums/' . $fileName;
                }
            }

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO albums (Title, ReleaseYear, CoverImage_URL) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $title, $releaseYear, $coverUrl);
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Thêm album thành công!</div>';
            } 
            elseif ($action === 'update') {
                $id = intval(getPostVal('id'));
                
                if ($coverUrl !== null) {
                    // Xóa ảnh cũ nếu có up ảnh mới
                    $stmtOld = $conn->prepare("SELECT CoverImage_URL FROM albums WHERE AlbumID = ?");
                    $stmtOld->bind_param('i', $id);
                    $stmtOld->execute();
                    $stmtOld->bind_result($oldCover);
                    if ($stmtOld->fetch() && !empty($oldCover)) {
                        @unlink(__DIR__ . '/' . ltrim($oldCover, '/'));
                    }
                    $stmtOld->close();

                    $stmt = $conn->prepare("UPDATE albums SET Title = ?, ReleaseYear = ?, CoverImage_URL = ? WHERE AlbumID = ?");
                    $stmt->bind_param("sisi", $title, $releaseYear, $coverUrl, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE albums SET Title = ?, ReleaseYear = ? WHERE AlbumID = ?");
                    $stmt->bind_param("sii", $title, $releaseYear, $id);
                }
                $stmt->execute();
                echo '<div style="color: #4ade80; margin-bottom: 15px;">Cập nhật album thành công!</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div style="color: #f87171; margin-bottom: 15px;">Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    // Nút quay lại danh sách tương ứng
    $backUrl = "admin_" . $type . "s.php";
    if ($type === 'genre') $backUrl = "admin_genres.php";
    echo '<button type="button" class="btn-admin" onclick="loadContent(\''.$backUrl.'\')">Quay lại danh sách</button>';
}

$conn->close();
?>