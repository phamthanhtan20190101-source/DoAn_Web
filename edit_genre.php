<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit();

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$res = $conn->query("SELECT * FROM genres WHERE GenreID = $id");
$genre = $res->fetch_assoc();

if (!$genre) {
    echo '<div style="color:white;">Không tìm thấy thể loại này.</div>';
    exit();
}
?>
<h2 style="color: white; margin-bottom: 20px;">Sửa Thể Loại</h2>

<form action="category_action.php" method="POST" data-ajax="true">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="type" value="genre">
    <input type="hidden" name="id" value="<?php echo $genre['GenreID']; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px;">
        <label>Tên thể loại
            <input type="text" name="name" value="<?php echo htmlspecialchars($genre['Name']); ?>" required 
                   style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; background: rgba(0,0,0,0.2); color: white; border: 1px solid rgba(255,255,255,0.2); outline: none;">
        </label>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary); flex: 1;">Cập nhật</button>
            <button type="button" class="btn-admin" style="background-color: #4b5563; flex: 1;" onclick="loadContent('admin_genres.php')">Hủy bỏ</button>
        </div>
    </div>
</form>
<?php $conn->close(); ?>