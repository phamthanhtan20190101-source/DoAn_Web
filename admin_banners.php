<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') exit('Access Denied');
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$result = $conn->query("SELECT * FROM banners ORDER BY OrderIndex ASC");
?>
<h2 style="color: white; margin-bottom: 20px;">Quản lý Banner quảng cáo</h2>
<div style="margin-bottom: 20px;">
    <button class="btn-admin highlight-green" onclick="loadContent('add_banner.php')" style="width: fit-content;">+ Thêm Banner mới</button>
</div>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; color: white; border-color: rgba(255,255,255,0.1); text-align: left;">
    <thead style="background: rgba(255,255,255,0.05);">
        <tr>
            <th>Ảnh</th>
            <th>Tiêu đề</th>
            <th>Liên kết</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><img src="<?php echo $row['ImageURL']; ?>" width="120" style="border-radius: 5px;"></td>
                <td><?php echo htmlspecialchars($row['Title']); ?></td>
                <td><small><?php echo $row['LinkURL']; ?></small></td>
                <td><?php echo $row['IsActive'] ? '<span style="color: #10b981;">Đang hiện</span>' : '<span style="color: #ef4444;">Đang ẩn</span>'; ?></td>
                <td>
                    <button class="btn-admin" style="padding: 5px 10px;" onclick="deleteCategory('banner', <?php echo $row['BannerID']; ?>)">Xóa</button>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="5">Chưa có banner nào được tạo.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php $conn->close(); ?>