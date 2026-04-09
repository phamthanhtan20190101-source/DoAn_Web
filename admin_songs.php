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

$query = "SELECT s.SongID, s.Title, s.Duration, s.ReleaseDate, s.FilePath_URL, s.PlayCount, g.Name AS GenreName, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists
          FROM songs s
          LEFT JOIN genres g ON g.GenreID = s.GenreID
          LEFT JOIN song_artist sa ON sa.SongID = s.SongID
          LEFT JOIN artists a ON a.ArtistID = sa.ArtistID
          GROUP BY s.SongID
          ORDER BY s.SongID DESC";
$result = $conn->query($query);
?>
<h2>Quản lý Bài hát</h2>
<button type="button" class="btn-admin" onclick="loadContent('add_song.php')">Thêm bài hát</button>
<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.2); color: white;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên bài hát</th>
                <th>Thể loại</th>
                <th>Ca sĩ</th>
                <th>Thời lượng</th>
                <th>Ngày phát hành</th>
                <th>Lượt nghe</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['SongID']; ?></td>
                        <td><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['GenreName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['Artists'] ?? 'Chưa có', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $row['Duration'] !== null ? gmdate('i:s', intval($row['Duration'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($row['ReleaseDate'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['PlayCount'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button type="button" class="btn-admin" onclick="loadContent('edit_song.php?id=<?php echo $row['SongID']; ?>')">Sửa</button>
                            <button type="button" class="btn-admin" onclick="deleteSong(<?php echo $row['SongID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">Không có bài hát.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
function deleteSong(songId) {
    if (!confirm('Bạn có chắc muốn xóa bài hát ID ' + songId + ' ?')) return;

    fetch('song_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({action: 'delete', songId: songId})
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Đã xóa.');
        if (data.success) {
            loadContent('admin_songs.php');
        }
    })
    .catch(() => {
        alert('Lỗi khi xóa bài hát.');
    });
}
</script>
<?php $conn->close(); ?>