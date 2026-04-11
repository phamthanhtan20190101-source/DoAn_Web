<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit();

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy danh sách Playlist do Admin tạo (IsAdmin = 1)
$result = $conn->query("SELECT p.*, a.Username FROM playlists p 
                        LEFT JOIN account a ON p.AccountID = a.AccountID 
                        WHERE p.IsAdmin = 1 ORDER BY p.PlaylistID DESC");
?>

<h2 style="color: white; margin-bottom: 20px;">Quản lý Playlist mẫu</h2>

<div style="margin-bottom: 20px;">
    <button type="button" class="btn-admin highlight-green" style="width: fit-content;">+ Tạo Playlist mới</button>
</div>

<div style="color: white;">
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.1); text-align: left;">
        <thead style="background: rgba(255,255,255,0.05);">
            <tr>
                <th style="width: 50px; text-align: center;">STT</th>
                <th>Tên Playlist</th>
                <th>Người tạo</th>
                <th>Ngày tạo</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $stt = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="text-align: center; font-weight: bold;"><?php echo $stt++; ?></td>
                        <td style="font-weight: 600; color: var(--purple-primary);">
                            <?php echo htmlspecialchars($row['Title']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['Username'] ?? 'Admin'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['CreatedAt'])); ?></td>
                        <td>
                            <button type="button" class="btn-admin" style="padding: 5px 10px;" onclick="deleteCategory('playlist', <?php echo $row['PlaylistID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Chưa có playlist mẫu nào được tạo.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $conn->close(); ?>