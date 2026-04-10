<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);

// Truy vấn kết hợp 3 bảng: comments, account (lấy tên user) và songs (lấy tên bài hát)
$query = "SELECT c.CommentID, c.Content, c.CreatedAt, a.Username, s.Title AS SongTitle 
          FROM comments c 
          JOIN account a ON c.AccountID = a.AccountID 
          JOIN songs s ON c.SongID = s.SongID 
          ORDER BY c.CreatedAt DESC";
$result = $conn->query($query);
?>
<h2>Quản lý Bình luận / Báo cáo</h2>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white; text-align: left;">
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Người dùng</th>
                <th>Bài hát</th>
                <th>Nội dung bình luận</th>
                <th style="width: 100px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-size: 13px; color: #aaa;"><?php echo date('d/m/Y H:i', strtotime($row['CreatedAt'])); ?></td>
                        <td><strong style="color: #9b4de0;"><?php echo htmlspecialchars($row['Username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['SongTitle'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($row['Content'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td>
                            <button type="button" class="btn-admin" style="background-color: #ef4444;" onclick="deleteComment(<?php echo $row['CommentID']; ?>)">Xóa (Spam)</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Hệ thống chưa có bình luận nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function deleteComment(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa bình luận vi phạm này?')) return;
    
    // Gửi AJAX gọi file category_action.php để thực hiện lệnh xóa
    fetch('category_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'delete', type: 'comment', id: id})
    }).then(res => res.json()).then(data => {
        alert(data.message);
        if(data.success) {
            loadContent('admin_comments.php'); // Tải lại danh sách sau khi xóa
        }
    });
}
</script>
<?php $conn->close(); ?>