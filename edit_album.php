<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$albumId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = $conn->query("SELECT * FROM albums WHERE AlbumID = $albumId");
$album = $result->fetch_assoc();

if (!$album) {
    echo '<div style="color:white;">Không tìm thấy thông tin Album.</div>';
    exit();
}
?>
<h2>Sửa Thông Tin Album</h2>

<form action="category_action.php" method="POST" enctype="multipart/form-data" data-ajax="true">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="type" value="album">
    <input type="hidden" name="id" value="<?php echo $album['AlbumID']; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 15px;">
        
        <label>
            Tên Album
            <input type="text" name="title" value="<?php echo htmlspecialchars($album['Title'], ENT_QUOTES, 'UTF-8'); ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <label>
            Ảnh bìa hiện tại:
            <div style="margin-top: 5px; margin-bottom: 10px;">
                <?php if(!empty($album['CoverImage_URL'])): ?>
                    <img src="<?php echo htmlspecialchars($album['CoverImage_URL']); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">
                <?php else: ?>
                    <span style="color: gray;">Chưa có ảnh</span>
                <?php endif; ?>
            </div>
            Chọn ảnh mới (Bỏ trống nếu muốn giữ nguyên ảnh cũ)
            <input type="file" name="cover_image" accept="image/*" style="margin-top: 5px; width: 100%;">
        </label>

        <label>
            Năm phát hành
            <input type="number" name="release_year" min="1900" max="<?php echo date('Y'); ?>"  value="<?php echo htmlspecialchars($album['ReleaseYear'] ?? date('Y'), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary);">Cập nhật Album</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_albums.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>

<?php $conn->close(); ?>