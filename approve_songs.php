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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $approveId = intval($_POST['approve_id']);
    $update = $conn->prepare("UPDATE songs SET status = 1 WHERE SongID = ?");
    if ($update) {
        $update->bind_param("i", $approveId);
        $update->execute();
        $update->close();
    }
}

$result = $conn->query(
    "SELECT s.SongID, s.Title, s.Country, s.ReleaseDate, s.PlayCount, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists
     FROM songs s
     LEFT JOIN song_artist sa ON sa.SongID = s.SongID
     LEFT JOIN artists a ON a.ArtistID = sa.ArtistID
     WHERE s.status = 0
     GROUP BY s.SongID
     ORDER BY s.SongID DESC"
);
?>
<h2>Duyệt bài hát</h2>
<?php if ($result && $result->num_rows > 0): ?>
    <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-color: rgba(255,255,255,0.2); color: white;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên bài hát</th>
                <th>Nghệ sĩ</th>
                <th>Quốc gia</th>
                <th>Ngày phát hành</th>
                <th>Lượt nghe</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($song = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $song['SongID']; ?></td>
                    <td><?php echo htmlspecialchars($song['Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($song['Artists'] ?? 'Chưa có', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($song['Country'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($song['ReleaseDate'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($song['PlayCount'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <form method="POST" data-ajax="true" data-reload-url="approve_songs.php" style="display:inline;">
                            <input type="hidden" name="approve_id" value="<?php echo $song['SongID']; ?>">
                            <button type="submit">Duyệt</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Không có bài hát chờ duyệt.</p>
<?php endif; ?>
<?php $conn->close(); ?>