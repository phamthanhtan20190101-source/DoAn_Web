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
$password = "vertrigo"; 
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

        $table = ''; $idColumn = '';

        if ($type === 'genre') { $table = 'genres'; $idColumn = 'GenreID'; } 
        elseif ($type === 'artist') { $table = 'artists'; $idColumn = 'ArtistID'; } 
        elseif ($type === 'album') {
            $table = 'albums'; $idColumn = 'AlbumID';
            $stmtImg = $conn->prepare("SELECT CoverImage_URL FROM albums WHERE AlbumID = ?");
            if ($stmtImg) {
                $stmtImg->bind_param('i', $id); $stmtImg->execute(); $stmtImg->bind_result($coverPath);
                if ($stmtImg->fetch() && !empty($coverPath)) {
                    $realPath = __DIR__ . '/' . ltrim($coverPath, '/');
                    if (file_exists($realPath)) @unlink($realPath);
                }
                $stmtImg->close();
            }
        } 
        elseif ($type === 'comment') { $table = 'comments'; $idColumn = 'CommentID'; } 
        elseif ($type === 'banner') {
            $table = 'banners'; $idColumn = 'BannerID';
            $stmtImg = $conn->prepare("SELECT ImageURL FROM banners WHERE BannerID = ?");
            $stmtImg->bind_param('i', $id); $stmtImg->execute(); $stmtImg->bind_result($bPath);
            if ($stmtImg->fetch() && !empty($bPath)) {
                $realPath = __DIR__ . '/' . ltrim($bPath, '/');
                if (file_exists($realPath)) @unlink($realPath);
            }
            $stmtImg->close();
        } 
        elseif ($type === 'playlist') { $table = 'playlists'; $idColumn = 'PlaylistID'; }
        else { sendJsonResponse(false, 'Loại danh mục không hợp lệ.'); }

        $stmt = $conn->prepare("DELETE FROM $table WHERE $idColumn = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) sendJsonResponse(true, 'Đã xóa thành công.');
            else sendJsonResponse(false, 'Không thể xóa. Lỗi: ' . $conn->error);
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
    $type = getPostVal('type'); 
    
    if (empty($action) || empty($type)) {
        echo '<div style="color: red;">Yêu cầu không hợp lệ.</div>';
        exit();
    }

    $backUrl = "admin_" . $type . "s.php";
    if ($type === 'genre') $backUrl = "admin_genres.php";
    if ($type === 'banner') $backUrl = "admin_banners.php";

    try {
        $successMessage = "Đã lưu thành công!";

        // --- A. XỬ LÝ THỂ LOẠI ---
        if ($type === 'genre') {
            $name = getPostVal('name');
            if (empty($name)) throw new Exception("Tên thể loại không được để trống.");

            $id = ($action === 'update') ? intval(getPostVal('id')) : 0;
            $check = $conn->prepare("SELECT GenreID FROM genres WHERE Name = ? AND GenreID != ?");
            $check->bind_param("si", $name, $id);
            $check->execute(); $check->store_result();
            if ($check->num_rows > 0) throw new Exception("Thể loại '$name' đã tồn tại.");
            $check->close();

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO genres (Name) VALUES (?)");
                $stmt->bind_param("s", $name); $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE genres SET Name = ? WHERE GenreID = ?");
                $stmt->bind_param("si", $name, $id); $stmt->execute();
            }
            $successMessage = ($action === 'create') ? "Thêm thể loại thành công!" : "Cập nhật thành công!";
        }
        
        // --- B. XỬ LÝ NGHỆ SĨ ---
        elseif ($type === 'artist') {
            $name = getPostVal('name'); $country = getPostVal('country'); $bio = getPostVal('bio');
            if (empty($name)) throw new Exception("Tên nghệ sĩ không được để trống.");

            $id = ($action === 'update') ? intval(getPostVal('id')) : 0;
            $check = $conn->prepare("SELECT ArtistID FROM artists WHERE Name = ? AND ArtistID != ?");
            $check->bind_param("si", $name, $id);
            $check->execute(); $check->store_result();
            if ($check->num_rows > 0) throw new Exception("Nghệ sĩ '$name' đã có trong hệ thống.");
            $check->close();

            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO artists (Name, Country, Bio) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $country, $bio); $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE artists SET Name = ?, Country = ?, Bio = ? WHERE ArtistID = ?");
                $stmt->bind_param("sssi", $name, $country, $bio, $id); $stmt->execute();
            }
            $successMessage = ($action === 'create') ? "Thêm nghệ sĩ thành công!" : "Cập nhật thành công!";
        }
        
        // --- C. XỬ LÝ ALBUM ---
        elseif ($type === 'album') {
            $title = getPostVal('title');
            $releaseYear = getPostVal('release_year');
            $releaseYear = !empty($releaseYear) ? intval($releaseYear) : null;

            if (empty($title)) throw new Exception("Tên album không được để trống.");
            if ($releaseYear !== null) {
                $currentYear = intval(date('Y'));
                if ($releaseYear > $currentYear) throw new Exception("Năm phát hành ($releaseYear) không được lớn hơn năm hiện tại ($currentYear).");
                if ($releaseYear < 1900) throw new Exception("Năm phát hành quá cũ hoặc không hợp lệ.");
            }

            $id = ($action === 'update') ? intval(getPostVal('id')) : 0;
            $check = $conn->prepare("SELECT AlbumID FROM albums WHERE Title = ? AND AlbumID != ?");
            $check->bind_param("si", $title, $id);
            $check->execute(); $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                throw new Exception("Album mang tên '$title' đã tồn tại.");
            }
            $check->close();

            $coverUrl = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/albums';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fileName = uniqid('album_') . '_' . basename($_FILES['cover_image']['name']);
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . '/' . $fileName)) {
                    $coverUrl = 'uploads/albums/' . $fileName;
                }
            }

            if ($action === 'create') {
                if (!$coverUrl) throw new Exception("Vui lòng tải lên ảnh bìa cho Album.");
                $stmt = $conn->prepare("INSERT INTO albums (Title, ReleaseYear, CoverImage_URL) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $title, $releaseYear, $coverUrl); $stmt->execute();
            } else {
                if ($coverUrl !== null) {
                    $stmt = $conn->prepare("UPDATE albums SET Title = ?, ReleaseYear = ?, CoverImage_URL = ? WHERE AlbumID = ?");
                    $stmt->bind_param("sisi", $title, $releaseYear, $coverUrl, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE albums SET Title = ?, ReleaseYear = ? WHERE AlbumID = ?");
                    $stmt->bind_param("sii", $title, $releaseYear, $id);
                }
                $stmt->execute();
            }
            $successMessage = ($action === 'create') ? "Thêm album thành công!" : "Cập nhật thành công!";
        }
        
        // --- D. XỬ LÝ BANNER ---
        elseif ($type === 'banner') {
            $title = getPostVal('title'); $link = getPostVal('link_url');
            if (empty($title)) throw new Exception("Tiêu đề banner không được để trống.");

            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/uploads/banners';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $fileName = uniqid('banner_') . '_' . basename($_FILES['banner_image']['name']);
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadDir . '/' . $fileName)) {
                    $imgUrl = 'uploads/banners/' . $fileName;
                    $stmt = $conn->prepare("INSERT INTO banners (Title, ImageURL, LinkURL) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $title, $imgUrl, $link); $stmt->execute();
                }
            } else { throw new Exception("Vui lòng chọn ảnh cho banner."); }
            $successMessage = "Đã tải lên banner thành công!";
        }

        // --- NẾU THÀNH CÔNG (Không bị lỗi) ---
        echo '<div style="background: rgba(74, 222, 128, 0.15); border: 1px solid #4ade80; color: #4ade80; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 15px; display: flex; align-items: center; justify-content: space-between;">';
        echo '<span>✅ ' . $successMessage . '</span>';
        echo '<span style="font-size: 13px; font-weight: normal; opacity: 0.8;"><i class="fa-solid fa-spinner fa-spin"></i> Đang quay lại...</span>';
        echo '</div>';
        
        // TRICK: Dùng thẻ img bị lỗi để chạy JS ngầm, tự chuyển trang sau 1.5 giây
        echo '<img src="x" onerror="setTimeout(() => loadContent(\''.$backUrl.'\'), 1500)" style="display:none;">';

    } catch (Exception $e) {
        // --- NẾU CÓ LỖI (Trùng lặp, sai dữ liệu...) ---
        // Lưu ý: Luôn trả về 200 OK để JS bên ngoài chịu in HTML ra màn hình!
        http_response_code(200); 
        echo '<div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 15px;">';
        echo '⚠️ KHÔNG THỂ LƯU: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        
        // Hiện nút cho phép Admin bấm quay lại bằng tay
        echo '<button type="button" class="btn-admin" style="margin-top:10px; background: #4b5563;" onclick="loadContent(\''.$backUrl.'\')"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</button>';
    }
}
$conn->close();
?>