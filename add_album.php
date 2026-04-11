<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit();
?>
<h2 style="color: white;">Thêm Album Mới</h2>

<form action="category_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-reload-url="admin_albums.php">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="type" value="album">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 20px;">
        <label>Tên Album
            <input type="text" name="title" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>

        <label>Ảnh bìa Album
            <input type="file" name="cover_image" accept="image/*" required style="margin-top: 5px;">
        </label>
        
        <label>Năm phát hành
        
        <input type="number" name="release_year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(255,255,255,0.05); color: white; border: 1px solid rgba(255,255,255,0.2);">
        </label>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-admin" style="background: var(--purple-primary);">Lưu Album</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_albums.php')" style="background: #4b5563;">Hủy</button>
        </div>
    </div>
</form>