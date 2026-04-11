<?php
session_start();
include_once 'render_helper.php';
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$albumId = intval($_GET['id']);
$album = $conn->query("SELECT * FROM albums WHERE AlbumID = $albumId")->fetch_assoc();

// Lấy danh sách bài hát thuộc album này
$songsResult = $conn->query("SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists 
                             FROM songs s 
                             LEFT JOIN song_artist sa ON s.SongID = sa.SongID
                             LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
                             WHERE s.AlbumID = $albumId
                             GROUP BY s.SongID");
$songsJSON = []; $index = 0;
?>

<div style="display: flex; gap: 40px; padding-top: 20px;">
    <div style="width: 300px; text-align: center; position: sticky; top: 20px; height: fit-content;">
        <div style="width: 300px; height: 300px; border-radius: 15px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.5); margin-bottom: 20px;">
            <img src="<?php echo $album['CoverImage_URL']; ?>" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <h2 style="color: white; margin-bottom: 10px;"><?php echo htmlspecialchars($album['Title']); ?></h2>
        <p style="color: gray; font-size: 14px; margin-bottom: 20px;">Năm phát hành: <?php echo $album['ReleaseYear']; ?></p>
        
        <button onclick="playPlaylist(0, 'data-album-view')" style="background: var(--purple-primary); color: white; border: none; padding: 12px 40px; border-radius: 25px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; margin: 0 auto;">
            <i class="fa-solid fa-play"></i> PHÁT TẤT CẢ
        </button>
    </div>

    <div style="flex: 1;">
        <div style="color: rgba(255,255,255,0.5); font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 10px; display: flex; justify-content: space-between;">
            <span>BÀI HÁT</span>
            <span style="margin-right: 50px;">THỜI GIAN</span>
        </div>
        
        <div class="song-list-container">
            <?php if ($songsResult->num_rows > 0): while($row = $songsResult->fetch_assoc()): 
                $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??$album['CoverImage_URL']];
            ?>
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-album-view')">
                    <?php renderSongItem($row); ?>
                </div>
            <?php endwhile; else: ?>
                <p style="color: gray; margin-top: 30px;">Album này đang được cập nhật bài hát...</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="data-album-view" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

<?php $conn->close(); ?>