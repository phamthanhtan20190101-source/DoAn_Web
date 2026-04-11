<?php
session_start();
include_once 'render_helper.php';
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Lấy thông tin chi tiết bài hát
$song = $conn->query("SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, g.Name AS GenreName, al.Title AS AlbumTitle
                      FROM songs s
                      LEFT JOIN song_artist sa ON s.SongID = sa.SongID
                      LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
                      LEFT JOIN genres g ON s.GenreID = g.GenreID
                      LEFT JOIN albums al ON s.AlbumID = al.AlbumID
                      WHERE s.SongID = $id GROUP BY s.SongID")->fetch_assoc();

if (!$song) { echo '<div style="color:white; padding: 20px;">Bài hát không tồn tại.</div>'; exit; }

// Chuẩn bị dữ liệu JSON để phát nhạc
$songsJSON = [['id' => $song['SongID'], 'url' => $song['FilePath_URL'], 'title' => $song['Title'], 'artist' => $song['Artists'], 'cover' => $song['CoverImage_URL']]];
?>
<div style="display: flex; gap: 40px; padding-top: 20px; color: white;">
    <div style="width: 300px; text-align: center; position: sticky; top: 20px; height: fit-content;">
        <div style="width: 300px; height: 300px; border-radius: 15px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.5); margin-bottom: 20px;">
            <img src="<?php echo htmlspecialchars($song['CoverImage_URL'] ?: 'https://via.placeholder.com/300x300.png?text=Lyrx+Music'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <button onclick="playPlaylist(0, 'data-song-view')" style="background: var(--purple-primary); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; width: 100%; gap: 10px;">
            <i class="fa-solid fa-play"></i> PHÁT BÀI HÁT NÀY
        </button>
    </div>

    <div style="flex: 1;">
        <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 10px;"><?php echo htmlspecialchars($song['Title']); ?></h1>
        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 20px; border-radius: 15px; color: var(--text-secondary); font-size: 15px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 15px;">
            <span><b>Nghệ sĩ:</b> <span style="color: var(--purple-primary); cursor: pointer;" onclick="loadContent('search_results.php?q=<?php echo urlencode($song['Artists']); ?>')"><?php echo htmlspecialchars($song['Artists']); ?></span></span>
            <span><b>Thể loại:</b> <?php echo htmlspecialchars($song['GenreName'] ?: 'Đang cập nhật'); ?></span>
            <span><b>Album:</b> <?php echo htmlspecialchars($song['AlbumTitle'] ?: 'Single'); ?></span>
            <span><b>Phát hành:</b> <?php echo $song['ReleaseDate'] ? date('d/m/Y', strtotime($song['ReleaseDate'])) : 'Đang cập nhật'; ?></span>
            <span><b>Lượt nghe:</b> <?php echo number_format($song['PlayCount']); ?></span>
        </div>
        
        <button onclick="playPlaylist(0, 'data-song-view'); setTimeout(() => { openLyricPanel(); }, 300);" style="background: transparent; color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 25px; border-radius: 25px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.borderColor='var(--purple-primary)'; this.style.color='var(--purple-primary)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.3)'; this.style.color='white';">
            <i class="fa-solid fa-microphone-lines"></i> HIỂN THỊ LỜI BÀI HÁT
        </button>
    </div>
</div>
<div id="data-song-view" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, 'UTF-8'); ?>'></div>