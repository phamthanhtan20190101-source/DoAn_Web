<?php
session_start();
if (!isset($_SESSION['username'])) exit;
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$playlistId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$accountId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$username = $conn->real_escape_string($_SESSION['username']);

// 1. Lấy thông tin của Playlist
$plQuery = $conn->query("SELECT * FROM playlists WHERE PlaylistID = $playlistId AND (AccountID = $accountId OR IsAdmin = 1)");
if (!$plQuery || $plQuery->num_rows === 0) {
    echo '<div style="color:white; padding: 20px;">Playlist không tồn tại hoặc bạn không có quyền xem.</div>';
    exit;
}
$playlist = $plQuery->fetch_assoc();

// 2. Lấy danh sách bài hát TRONG Playlist này (ĐÃ SỬA LỖI SQL TRÁNH GROUP BY STRICT MODE)
$sqlSongs = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists,
             IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
             FROM playlist_song ps
             JOIN songs s ON ps.SongID = s.SongID
             LEFT JOIN song_artist sa ON s.SongID = sa.SongID
             LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
             LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
             WHERE ps.PlaylistID = $playlistId
             GROUP BY s.SongID";
$result = $conn->query($sqlSongs);

$songsJSON = []; $index = 0;

// ĐÃ SỬA LỖI Ở ĐÂY: Kiểm tra kỹ $result trước khi đếm số dòng
$totalSongs = ($result) ? $result->num_rows : 0; 
?>

<div style="padding-bottom: 20px;">
    <button onclick="loadContent('library.php'); setTimeout(() => { const tabs = document.querySelectorAll('.tab-btn'); if(tabs.length >= 3) switchLibTab('playlist', tabs[2]); }, 100);" style="background:transparent; border:none; color:var(--text-secondary); cursor:pointer; margin-bottom:20px; font-size: 15px; font-weight: bold; transition: 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='var(--text-secondary)'"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
    
    <div style="display: flex; gap: 20px; align-items: flex-end; margin-bottom: 30px;">
        <div style="width: 150px; height: 150px; background: linear-gradient(135deg, #4e346b, #231b2e); border-radius: 10px; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            <i class="fa-solid fa-compact-disc" style="font-size: 70px; color: var(--purple-primary); opacity: 0.8;"></i>
        </div>
        <div>
            <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; color: var(--purple-primary); margin-bottom: 5px;">Playlist</div>
            <h1 style="color: white; font-size: 36px; margin: 0 0 10px 0;"><?php echo htmlspecialchars($playlist['Title']); ?></h1>
            <div style="color: gray; font-size: 14px;">Tạo bởi: <strong style="color: white;"><?php echo $_SESSION['username']; ?></strong> • <?php echo $totalSongs; ?> bài hát</div>
        </div>
    </div>

    <?php if ($totalSongs > 0): ?>
    <div style="margin-bottom: 20px;">
        <button onclick="playPlaylist(0, 'data-playlist-view')" style="background: var(--purple-primary); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s;"><i class="fa-solid fa-play"></i> PHÁT TẤT CẢ</button>
    </div>
    <?php endif; ?>

    <div class="song-list-container" style="display: flex; flex-direction: column; gap: 5px;">
        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): 
            $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??''];
        ?>
            <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-playlist-view')">
                <?php renderSongItem($row); ?>
            </div>
        <?php endwhile; else: ?>
            <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; padding: 40px; text-align: center; color: gray;">
                <i class="fa-solid fa-music" style="font-size: 30px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p>Playlist này chưa có bài hát nào.<br>Hãy dạo quanh Khám Phá và bấm dấu (+) kế bên bài hát để thêm nhạc vào nhé!</p>
            </div>
        <?php endif; ?>
    </div>
    <div id="data-playlist-view" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>
</div>
<?php $conn->close(); ?>