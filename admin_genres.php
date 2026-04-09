<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$servername = "localhost";
$username = "root";
$password = "vertrigo";
$dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo '<div style="color:white;">Không thể kết nối cơ sở dữ liệu.</div>';
    exit();
}

$result = $conn->query("SELECT * FROM genres ORDER BY GenreID DESC");
?>
<h2>Quản lý Thể Loại</h2>
<button type="button" class="btn-admin" onclick="loadContent('add_genre.php')">Thêm Thể Loại</button>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white; text-align: left;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Thể Loại</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['GenreID']; ?></td>
                        <td><?php echo htmlspecialchars($row['Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button type="button" class="btn-admin" onclick="loadContent('edit_genre.php?id=<?php echo $row['GenreID']; ?>')">Sửa</button>
                            <button type="button" class="btn-admin" onclick="deleteCategory('genre', <?php echo $row['GenreID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">Không có thể loại nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    function deleteCategory(type, id) {
    if (!confirm('Bạn có chắc chắn muốn xóa bản ghi này? Thao tác này không thể hoàn tác!')) return;

    fetch('category_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({action: 'delete', type: type, id: id})
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            // Tải lại trang tương ứng sau khi xóa thành công
            let reloadUrl = 'admin_genres.php';
            if (type === 'artist') reloadUrl = 'admin_artists.php';
            if (type === 'album') reloadUrl = 'admin_albums.php';
            loadContent(reloadUrl);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi gọi server.');
    });
}
</script>
<?php $conn->close(); ?>