<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'admin') exit('Access Denied');
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');
$result = $conn->query("SELECT * FROM banners ORDER BY BannerID DESC");
?>
<h2 style="color: white; margin-bottom: 20px;">Quản lý Banner quảng cáo</h2>
<div style="margin-bottom: 20px;">
    <button class="btn-admin highlight-green" onclick="loadContent('add_banner.php')" style="width: fit-content;">+ Thêm Banner mới</button>
</div>

<table border="1" cellpadding="10" cellspacing="0" style="width: 100%; color: white; border-color: rgba(255,255,255,0.1); text-align: left;">
    <thead style="background: rgba(255,255,255,0.05);">
        <tr>
            <th style="width: 50px; text-align: center;">STT</th>
            <th>Ảnh</th>
            <th>Tiêu đề</th>
            <th>Liên kết</th>
            <th>Trạng thái</th>
            <th style="width: 150px;">Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php $stt = 1; ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="text-align: center; font-weight: bold;"><?php echo $stt++; ?></td>
                    <td><img src="<?php echo htmlspecialchars($row['ImageURL']); ?>" width="120" style="border-radius: 5px;"></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['Title']); ?></td>
                    <td><small style="color: #aee2ff;"><?php echo htmlspecialchars($row['LinkURL']); ?></small></td>
                    <td>
                        <?php echo $row['IsActive'] ? '<span style="color: #10b981; font-weight:bold;">Đang hiện</span>' : '<span style="color: #ef4444;">Đang ẩn</span>'; ?>
                    </td>
                    <td>
                        <button type="button" class="btn-admin" onclick="loadContent('edit_banner.php?id=<?php echo $row['BannerID']; ?>')">Sửa</button>
                        <button type="button" class="btn-admin" style="background:#ef4444;" onclick="deleteCategory('banner', <?php echo $row['BannerID']; ?>)">Xóa</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">Chưa có banner nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php $conn->close(); ?>