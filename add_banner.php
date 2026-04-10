<?php
session_start();
if ($_SESSION['role'] !== 'admin') exit();
?>
<h2 style="color: white;">Thêm Banner Quảng Cáo</h2>
<form action="category_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-reload-url="admin_banners.php">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="type" value="banner">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 600px; margin-top: 20px;">
        <label>Tiêu đề Banner
            <input type="text" name="title" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>
        
        <label>Chọn ảnh Banner (Kích thước khuyên dùng: 1200x400)
            <input type="file" name="banner_image" accept="image/*" required style="margin-top: 5px;">
        </label>
        
        <label>Liên kết khi click (Link bài hát/album)
            <input type="text" name="link_url" placeholder="https://..." style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-admin" style="background: var(--purple-primary);">Tải lên Banner</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_banners.php')" style="background: #4b5563;">Hủy</button>
        </div>
    </div>
</form>