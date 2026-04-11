<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Thêm Nghệ Sĩ Mới</h2>

<form action="artist_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-delay-reload-url="admin_artists.php" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Đang lưu...';">
    <input type="hidden" name="action" value="create_artist">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 15px;">
        
        <label>
            Tên nghệ sĩ
            <input type="text" name="name" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <label>
            Ảnh nghệ sĩ (.jpg, .png)
            <input type="file" name="artist_image" accept="image/jpeg, image/png, image/jpg" style="margin-top: 5px; width: 100%;">
        </label>

        <label>
            Quốc gia / Thị trường
            <select name="country" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                <option value="Việt Nam">Việt Nam (V-Pop)</option>
                <option value="US-UK">Âu Mỹ (US-UK)</option>
                <option value="Hàn Quốc">Hàn Quốc (K-Pop)</option>
            </select>
        </label>

        <label>
            Tiểu sử
            <textarea name="bio" rows="5" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"></textarea>
        </label>

        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background-color: var(--purple-primary); color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Lưu nghệ sĩ</button>
            <button type="button" onclick="loadContent('admin_artists.php')" style="background-color: #4b5563; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Hủy bỏ</button>
        </div>
    </div>
</form>