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

$genreResult = $conn->query("SELECT GenreID, Name FROM genres ORDER BY Name ASC");
$artistResult = $conn->query("SELECT ArtistID, Name FROM artists ORDER BY Name ASC");
?>
<h2>Thêm bài hát mới</h2>
<form action="song_action.php" method="POST" data-ajax="true">
<!--<form action="song_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-reload-url="admin_songs.php">-->
    <input type="hidden" name="action" value="create">
    <div style="color: white; display: flex; flex-direction: column; gap: 12px; max-width: 500px;">
        <input type="text" name="title" placeholder="Tên bài hát" required>
        <label>
            File MP3 (.mp3)
            <input type="file" name="audio_file" accept="audio/mp3,audio/mpeg" required>
        </label>
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
            <select name="artist_id" class="search-select" required>
                <option value="">-- Gõ để tìm ca sĩ --</option>
                <?php while ($artist = $artistResult->fetch_assoc()): ?>
                    <option value="<?php echo $artist['ArtistID']; ?>">
                        <?php echo htmlspecialchars($artist['Name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>
        <label>
            Ngày phát hành
            <input type="date" name="release_date" max="<?php echo date('Y-m-d'); ?>" id="releaseDateInput">
        </label>
        <button type="submit" class="btn-admin">Lưu bài hát</button>
    </div>
</form>
<script>
    // Kiểm tra dung lượng file ngay khi vừa chọn xong
    const fileInput = document.querySelector('input[name="audio_file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 50 * 1024 * 1024; // 50MB
                if (file.size > maxSize) {
                    alert("Cảnh báo: File của bạn nặng " + (file.size / (1024*1024)).toFixed(2) + "MB. Vui lòng chọn file dưới 50MB!");
                    this.value = ""; // Lập tức xóa file khỏi ô chọn
                }
            }
        });
    }
</script>
<?php $conn->close(); ?>