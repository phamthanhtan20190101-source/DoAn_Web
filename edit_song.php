<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$songId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($songId <= 0) {
    echo '<div style="color:white;">ID bài hát không hợp lệ.</div>';
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

$stmt = $conn->prepare('SELECT s.SongID, s.Title, s.ReleaseDate, s.FilePath_URL, s.Duration, s.GenreID, GROUP_CONCAT(sa.ArtistID) AS ArtistIDs
                        FROM songs s
                        LEFT JOIN song_artist sa ON sa.SongID = s.SongID
                        WHERE s.SongID = ?
                        GROUP BY s.SongID');
$stmt->bind_param('i', $songId);
$stmt->execute();
$result = $stmt->get_result();
$song = $result->fetch_assoc();
$stmt->close();

if (!$song) {
    echo '<div style="color:white;">Không tìm thấy bài hát.</div>';
    $conn->close();
    exit();
}

$genreResult = $conn->query("SELECT GenreID, Name FROM genres ORDER BY Name ASC");
$artistResult = $conn->query("SELECT ArtistID, Name FROM artists ORDER BY Name ASC");
$conn->close();

$currentArtistId = intval(explode(',', $song['ArtistIDs'])[0] ?? 0);
?>
<h2>Sửa bài hát</h2>
<form action="song_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-reload-url="admin_songs.php">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="song_id" value="<?php echo $song['SongID']; ?>">
    <div style="color: white; display: flex; flex-direction: column; gap: 12px; max-width: 500px;">
        <label>
            Tên bài hát
            <input type="text" name="title" value="<?php echo htmlspecialchars($song['Title'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </label>
        <label>
            File MP3 mới (nếu muốn đổi)
            <input type="file" name="audio_file" accept="audio/mp3,audio/mpeg">
        </label>
        <?php if (!empty($song['FilePath_URL'])): ?>
            <div style="font-size: 0.9em;">File hiện tại: <a href="<?php echo htmlspecialchars($song['FilePath_URL'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="color: #b9f">Mở file</a></div>
        <?php endif; ?>
        <label>
            Thể loại
            <select name="genre_id" class="search-select" required style="width: 100%;">
                <option value="">-- Gõ để tìm thể loại --</option>
                <?php while ($genre = $genreResult->fetch_assoc()): ?>
                    <option value="<?php echo $genre['GenreID']; ?>">
                        <?php echo htmlspecialchars($genre['Name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <label>
            Ca sĩ
            <select name="artist_id" required>
                <option value="">Chọn ca sĩ</option>
                <?php while ($artist = $artistResult->fetch_assoc()): ?>
                    <option value="<?php echo $artist['ArtistID']; ?>" <?php echo $artist['ArtistID'] == $currentArtistId ? 'selected' : ''; ?>><?php echo htmlspecialchars($artist['Name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
            </select>
        </label>
        <label>
            Ngày phát hành
            <input type="date" name="release_date" value="<?php echo htmlspecialchars($song['ReleaseDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </label>
        <button type="submit" class="btn-admin">Cập nhật</button>
    </div>
</form>