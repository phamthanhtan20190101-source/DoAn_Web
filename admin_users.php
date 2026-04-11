<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

// Kết nối Database
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy toàn bộ danh sách tài khoản
$result = $conn->query("SELECT AccountID, Username, Role, Status FROM account ORDER BY AccountID DESC");
?>
<h2>Quản lý Người dùng</h2>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white; text-align: left;">
        <thead style="background: rgba(255,255,255,0.05);">
            <tr>
                <th>ID</th>
                <th>Tên đăng nhập</th>
                <th>Vai trò (Role)</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['AccountID']; ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['Username']); ?></td>
                        <td>
                            <?php if($row['Role'] === 'admin'): ?>
                                <span style="color: var(--purple-primary); font-weight: bold;">Admin</span>
                            <?php else: ?>
                                <span style="color: #9ca3af;">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['Status'] == 1): ?>
                                <span style="color: #4ade80;">Hoạt động</span>
                            <?php else: ?>
                                <span style="color: #ef4444;">Đã khóa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($row['AccountID'] != $_SESSION['id']): // Không tự khóa chính mình ?>
                                <button type="button" class="btn-admin" style="background: <?php echo $row['Status'] == 1 ? '#ef4444' : '#10b981'; ?>;">
                                    <?php echo $row['Status'] == 1 ? '<i class="fa-solid fa-lock"></i> Khóa' : '<i class="fa-solid fa-unlock"></i> Mở khóa'; ?>
                                </button>
                                
                                <?php if($row['Role'] === 'user'): ?>
                                    <button type="button" class="btn-admin" style="background: var(--purple-primary);"><i class="fa-solid fa-arrow-up"></i> Lên Admin</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: gray; font-size: 13px;">(Tài khoản của bạn)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Chưa có tài khoản nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $conn->close(); ?>