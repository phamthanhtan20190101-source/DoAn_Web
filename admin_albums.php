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

$result = $conn->query("SELECT * FROM albums ORDER BY AlbumID DESC");
?>
<h2>Quản lý Albums</h2>
<button type="button" class="btn-admin" onclick="loadContent('add_album.php')">Thêm Album</button>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white; text-align: left;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh Bìa</th>
                <th>Tên Album</th>
                <th>Năm Phát Hành</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['AlbumID']; ?></td>
                        <td>
                            <?php if(!empty($row['CoverImage_URL'])): ?>
                                <img src="<?php echo htmlspecialchars($row['CoverImage_URL'], ENT_QUOTES, 'UTF-8'); ?>" width="50" height="50" style="border-radius:5px; object-fit: cover;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#333; border-radius:5px; display:inline-block;"></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['ReleaseYear'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button type="button" class="btn-admin" onclick="loadContent('edit_album.php?id=<?php echo $row['AlbumID']; ?>')">Sửa</button>
                            <button type="button" class="btn-admin" onclick="deleteCategory('album', <?php echo $row['AlbumID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Không có album nào.</td></tr>
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
            loadContent('admin_albums.php');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi gọi server.');
    });
}
</script>
<?php $conn->close(); ?>