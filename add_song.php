<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);

$genreResult = $conn->query("SELECT GenreID, Name FROM genres ORDER BY Name ASC");
$artistResult = $conn->query("SELECT ArtistID, Name FROM artists ORDER BY Name ASC");
?>
<h2>Thêm bài hát mới</h2>

<form action="song_action.php" method="POST" enctype="multipart/form-data" data-ajax="true" data-delay-reload-url="add_song.php" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Đang tải lên...';">
    <input type="hidden" name="action" value="create">
    <div style="color: white; display: flex; flex-direction: column; gap: 15px; max-width: 500px; margin-top: 15px;">
        
        <label>
            Tên bài hát
            <input type="text" name="title" placeholder="Nhập tên bài hát" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <label>
            File MP3 (.mp3)
            <input type="file" name="audio_file" accept="audio/mp3,audio/mpeg" required style="margin-top: 5px; width: 100%;">
        </label>

        <label>
            Thể loại 
            <select name="genre_id" class="search-select" required style="width: 100%;">
                <option value="">-- Gõ tên thể loại... --</option>
                <?php while ($genre = $genreResult->fetch_assoc()): ?>
                    <option value="<?php echo $genre['GenreID']; ?>"><?php echo htmlspecialchars($genre['Name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
            </select>
        </label>

       <label>
            Ca sĩ 
            <select name="artist_ids[]" class="search-select" multiple="multiple" required style="width: 100%;">
                <?php while ($artist = $artistResult->fetch_assoc()): ?>
                    <option value="<?php echo $artist['ArtistID']; ?>"><?php echo htmlspecialchars($artist['Name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
            </select>
        </label>

        <label>
            Ngày phát hành
            <input type="date" name="release_date" max="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white;">
        </label>

        <label>
            Lời bài hát
            <textarea name="lyrics" rows="8" placeholder="Dán lời bài hát vào đây..." style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"></textarea>
        </label>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary);">Lưu bài hát</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_songs.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>
<?php $conn->close(); ?>