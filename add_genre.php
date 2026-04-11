<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}
?>
<h2>Thêm Thể Loại Mới</h2>
<form action="category_action.php" method="POST" data-ajax="true">
    <input type="hidden" name="action" value="create">
    <input type="hidden" name="type" value="genre">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 20px;">
        <label>
            Tên thể loại
            <input type="text" name="name" placeholder="Ví dụ: Pop, Ballad, Rap, EDM..." required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; outline: none;">
        </label>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary);">Lưu thể loại</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_genres.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>