<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit('Access Denied');

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$banner = $conn->query("SELECT * FROM banners WHERE BannerID = $id")->fetch_assoc();

if (!$banner) {
    echo '<div style="color:white;">Không tìm thấy Banner.</div>';
    exit();
}
?>
<h2 style="color: white; margin-bottom: 20px;">Sửa Banner Quảng Cáo</h2>

<form action="category_action.php" method="POST" enctype="multipart/form-data" data-ajax="true">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="type" value="banner">
    <input type="hidden" name="id" value="<?php echo $banner['BannerID']; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 600px;">
        <label>Tiêu đề Banner
            <input type="text" name="title" value="<?php echo htmlspecialchars($banner['Title']); ?>" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>
        
        <label>Ảnh Banner hiện tại:
            <div style="margin-top: 5px; margin-bottom: 10px;">
                <img src="<?php echo htmlspecialchars($banner['ImageURL']); ?>" style="max-width: 100%; height: 100px; object-fit: cover; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2);">
            </div>
            Chọn ảnh mới (Bỏ trống nếu muốn giữ nguyên ảnh cũ)
            <input type="file" name="banner_image" accept="image/*" style="margin-top: 5px; width: 100%;">
        </label>
        
        <label>Liên kết khi click (Link bài hát/album)
            <input type="text" name="link_url" value="<?php echo htmlspecialchars($banner['LinkURL'] ?? ''); ?>" placeholder="https://..." style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>

        <label>Trạng thái hiển thị
            <select name="is_active" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(0,0,0,0.5); color: white; border: 1px solid rgba(255,255,255,0.2);">
                <option value="1" <?php echo ($banner['IsActive'] == 1) ? 'selected' : ''; ?>>Đang hiện (Active)</option>
                <option value="0" <?php echo ($banner['IsActive'] == 0) ? 'selected' : ''; ?>>Đang ẩn (Inactive)</option>
            </select>
        </label>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary); flex: 1;">Cập nhật Banner</button>
            <button type="button" class="btn-admin" style="background-color: #4b5563; flex: 1;" onclick="loadContent('admin_banners.php')">Hủy bỏ</button>
        </div>
    </div>
</form>
<?php $conn->close(); ?>