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

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);
$albumResult = $conn->query("SELECT AlbumID, Title FROM albums ORDER BY Title ASC");

// Truy vấn lấy dữ liệu bao gồm cả cột Lyrics
$stmt = $conn->prepare('SELECT s.SongID, s.Title, s.ReleaseDate, s.FilePath_URL, s.CoverImage_URL, s.GenreID, s.AlbumID, s.Lyrics, GROUP_CONCAT(sa.ArtistID) AS ArtistIDs
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
    $conn->close(); exit();
}

$genreResult = $conn->query("SELECT GenreID, Name FROM genres ORDER BY Name ASC");
$artistResult = $conn->query("SELECT ArtistID, Name FROM artists ORDER BY Name ASC");
$conn->close();

$currentGenreId = intval($song['GenreID']);
$currentArtistIdsArray = !empty($song['ArtistIDs']) ? explode(',', $song['ArtistIDs']) : [];
?>

<h2>Sửa bài hát</h2>
<style>
    /* ÉP KIỂU GIAO DIỆN DARK MODE CHO SELECT2 (CA SĨ & THỂ LOẠI) */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background-color: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 5px !important;
        min-height: 42px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: white !important;
        line-height: 40px !important;
    }
    .select2-search__field { color: white !important; }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: var(--purple-primary, #9b4de0) !important;
        border: none !important;
        color: white !important;
        margin-top: 6px !important;
        padding: 3px 8px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: white !important;
        margin-right: 8px !important;
    }

    .select2-dropdown {
        background-color: #231b2e !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: white !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--purple-primary, #9b4de0) !important;
    }
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }

    /* Đổi icon Lịch sang màu trắng trên trình duyệt */
    input[type="date"] {
        color-scheme: dark;
    }
    
    /* Làm sáng chữ gợi ý của Lời bài hát */
    textarea::placeholder, input::placeholder {
        color: rgba(255, 255, 255, 0.5) !important;
    }
</style>
<form action="song_action.php" method="POST" enctype="multipart/form-data" data-ajax="true">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="song_id" value="<?php echo $song['SongID']; ?>">
    
    <div style="color: white; display: flex; flex-direction: column; gap: 12px; max-width: 500px; margin-top: 15px;">
        <label>
            Tên bài hát
            <input type="text" name="title" value="<?php echo htmlspecialchars($song['Title'], ENT_QUOTES, 'UTF-8'); ?>" required style="width: 100%; padding: 8px; margin-top: 5px;">
        </label>

        <label>
            Ảnh bìa hiện tại:
            <div style="margin-top: 5px; margin-bottom: 10px;">
                <?php if(!empty($song['CoverImage_URL'])): ?>
                    <img src="<?php echo htmlspecialchars($song['CoverImage_URL']); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; border: 2px solid var(--purple-primary);">
                <?php else: ?>
                    <span style="color: gray; font-style: italic;">Chưa có ảnh bìa</span>
                <?php endif; ?>
            </div>
            Chọn ảnh bìa mới (Bỏ trống nếu muốn giữ nguyên ảnh cũ)
            <input type="file" name="cover_image" accept="image/*" style="margin-top: 5px; width: 100%;">
        </label>
        
        <label>
            File MP3 mới (nếu muốn đổi)
            <input type="file" name="audio_file" accept="audio/mp3,audio/mpeg" style="width: 100%; margin-top: 5px;">
        </label>
        
        <label>
            Thể loại
            <select name="genre_id" class="search-select" required style="width: 100%;">
                <?php while ($genre = $genreResult->fetch_assoc()): ?>
                    <option value="<?php echo $genre['GenreID']; ?>" <?php echo $genre['GenreID'] == $currentGenreId ? 'selected' : ''; ?>><?php echo htmlspecialchars($genre['Name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
            </select>
        </label>
        
        <label>
            Ca sĩ
            <select name="artist_ids[]" class="search-select" multiple="multiple" required style="width: 100%;">
                <?php while ($artist = $artistResult->fetch_assoc()): ?>
                    <option value="<?php echo $artist['ArtistID']; ?>" <?php echo in_array($artist['ArtistID'], $currentArtistIdsArray) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($artist['Name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>

        <label>
            Album (Tùy chọn)
            <select name="album_id" class="search-select" style="width: 100%;">
                <option value="">-- Không thuộc Album nào --</option>
                <?php while ($album = $albumResult->fetch_assoc()): ?>
                    <option value="<?php echo $album['AlbumID']; ?>"><?php echo htmlspecialchars($album['Title'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endwhile; ?>
            </select>
        </label>
        
        <label>
            Ngày phát hành
            <input type="date" name="release_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($song['ReleaseDate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 8px; margin-top: 5px;">
        </label>

        <label>
            Lời bài hát
            <textarea name="lyrics" rows="8" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; resize: vertical;"><?php echo htmlspecialchars($song['Lyrics'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </label>
        
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button type="submit" class="btn-admin" style="background-color: var(--purple-primary);">Lưu cập nhật</button>
            <button type="button" class="btn-admin" onclick="loadContent('admin_songs.php')" style="background-color: #4b5563;">Hủy bỏ</button>
        </div>
    </div>
</form>