<?php
session_start();
if (!isset($_SESSION['username'])) exit;
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$playlistId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$username = $conn->real_escape_string($_SESSION['username']);

// 1. Lấy thông tin Playlist kèm danh sách nghệ sĩ (ArtistList)
// Câu lệnh này sẽ tự động tìm tất cả nghệ sĩ của các bài hát có trong playlist này
$plQuery = $conn->query("
    SELECT p.*, 
           (SELECT GROUP_CONCAT(DISTINCT ar.Name SEPARATOR ', ') 
            FROM playlist_song ps 
            JOIN song_artist sa ON ps.SongID = sa.SongID 
            JOIN artists ar ON sa.ArtistID = ar.ArtistID 
            WHERE ps.PlaylistID = p.PlaylistID) as ArtistList
    FROM playlists p 
    WHERE p.PlaylistID = $playlistId
");

if (!$plQuery || $plQuery->num_rows === 0) {
    echo '<div style="color:white; padding: 20px;">Playlist không tồn tại.</div>';
    exit;
}

$playlist = $plQuery->fetch_assoc();

// 2. Lấy danh sách bài hát chi tiết để hiển thị bên dưới
$sqlSongs = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists,
             IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
             FROM playlist_song ps
             JOIN songs s ON ps.SongID = s.SongID
             LEFT JOIN song_artist sa ON s.SongID = sa.SongID
             LEFT JOIN artists ar ON sa.ArtistID = ar.ArtistID
             LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
             LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
             WHERE ps.PlaylistID = $playlistId
             GROUP BY s.SongID";
$result = $conn->query($sqlSongs);

$songsJSON = []; $index = 0;
$totalSongs = ($result) ? $result->num_rows : 0; 
?>

<div style="padding-bottom: 20px;">
    <button onclick="loadContent('library.php');" style="background:transparent; border:none; color:var(--text-secondary); cursor:pointer; margin-bottom:20px; font-size: 15px; font-weight: bold; transition: 0.2s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='var(--text-secondary)'"><i class="fa-solid fa-arrow-left"></i> Quay lại</button>
    
    <div style="display: flex; gap: 20px; align-items: flex-end; margin-bottom: 30px;">
        <div style="width: 180px; height: 180px; background: #231b2e; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <img src="<?php echo !empty($playlist['Image_URL']) ? $playlist['Image_URL'] : 'https://cdn-icons-png.flaticon.com/512/3293/3293810.png'; ?>" 
                 style="width:100%; height:100%; object-fit:cover;" 
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3293/3293810.png'">
        </div>

        <div style="flex: 1;">
            <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; color: var(--purple-primary); margin-bottom: 5px;">Playlist</div>
            <h1 style="color: white; font-size: 45px; font-weight: 900; margin: 0 0 10px 0;"><?php echo htmlspecialchars($playlist['Title']); ?></h1>
            
            <div style="color: rgba(255,255,255,0.6); font-size: 14px; line-height: 1.6;">
                <span style="color: white; font-weight: 600;">
                    <?php 
                        // Nếu có bài hát thì hiện tên các ca sĩ, nếu không thì hiện mặc định
                        echo !empty($playlist['ArtistList']) ? htmlspecialchars($playlist['ArtistList']) : 'Tuyển tập nhạc hay nhất'; 
                    ?>
                </span>
                <br>
                <span><?php echo $totalSongs; ?> bài hát</span>
            </div>
        </div>
    </div>

    <?php if ($totalSongs > 0): ?>
    <div style="margin-bottom: 25px;">
        <button onclick="playPlaylist(0, 'data-playlist-view')" style="background: var(--purple-primary); color: white; border: none; padding: 14px 40px; border-radius: 30px; font-size: 15px; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 5px 15px rgba(155, 77, 224, 0.4);"><i class="fa-solid fa-play"></i> PHÁT TẤT CẢ</button>
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
            <div style="background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.1); border-radius: 12px; padding: 50px; text-align: center; color: rgba(255,255,255,0.4);">
                <i class="fa-solid fa-music" style="font-size: 40px; margin-bottom: 20px; opacity: 0.3;"></i>
                <p style="font-size: 15px;">Playlist này đang trống.<br>Hãy thêm những bài hát yêu thích của bạn vào đây!</p>
            </div>
        <?php endif; ?>
    </div>

    <div id="data-playlist-view" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>
</div>
<?php $conn->close(); ?>