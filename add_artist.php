<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Thêm Nghệ Sĩ Mới</h2>
<form action="category_action.php" method="POST" data-ajax="true" data-reload-url="admin_artists.php">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="type" value="artist">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 20px;">
        <label>
            Tên nghệ sĩ
            <input type="text" name="name" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>
        <label>
            Quốc gia / Thị trường
            <select name="country" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
                <option value="Việt Nam">Việt Nam (V-Pop)</option>
                <option value="US-UK">Âu Mỹ (US-UK)</option>
                <option value="Hàn Quốc">Hàn Quốc (K-Pop)</option>
                <option value="Trung Quốc">Trung Quốc (C-Pop)</option>
                <option value="Nhật Bản">Nhật Bản (J-Pop)</option>
                <option value="Khác">Khác</option>
            </select>
        </label>
        <label>
            Tiểu sử
            <textarea name="bio" rows="4" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"></textarea>
        </label>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-admin">Lưu nghệ sĩ</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_artists.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>