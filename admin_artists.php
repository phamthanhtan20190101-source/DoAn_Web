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

$result = $conn->query("SELECT * FROM artists ORDER BY ArtistID DESC");
?>
<h2>Quản lý Nghệ Sĩ</h2>
<button type="button" class="btn-admin" onclick="loadContent('add_artist.php')">Thêm Nghệ Sĩ</button>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white; text-align: left;">
        <thead>
            <tr>
                <th>ID</th>
                <th style="width: 60px; text-align: center;">Ảnh</th>
                <th>Tên Nghệ Sĩ</th>
                <th>Quốc Gia</th>
                <th>Tiểu sử</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['ArtistID']; ?></td>
                        
                        <td style="text-align: center;">
                            <?php if(!empty($row['Image_URL'])): ?>
                                <img src="<?php echo htmlspecialchars($row['Image_URL'], ENT_QUOTES, 'UTF-8'); ?>" width="45" height="45" style="border-radius: 50%; object-fit: cover; border: 2px solid var(--purple-primary);">
                            <?php else: ?>
                                <div style="width:45px; height:45px; background: rgba(255,255,255,0.1); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:white;">
                                    <i class="fa-solid fa-microphone"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td style="font-weight: bold;"><?php echo htmlspecialchars($row['Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['Country'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($row['Bio'] ?? '', 0, 50, '...'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button type="button" class="btn-admin" onclick="loadContent('edit_artist.php?id=<?php echo $row['ArtistID']; ?>')">Sửa</button>
                            <button type="button" class="btn-admin" style="background:#ef4444;" onclick="deleteCategory('artist', <?php echo $row['ArtistID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6">Không có nghệ sĩ nào.</td></tr>
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
            loadContent('admin_artists.php');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Đã xảy ra lỗi khi gọi server.');
    });
}
</script>
<?php $conn->close(); ?>