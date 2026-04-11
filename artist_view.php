<?php
session_start();
include_once 'render_helper.php';
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$artist = $conn->query("SELECT * FROM artists WHERE ArtistID = $id")->fetch_assoc();
if (!$artist) { echo '<div style="color:white; padding: 20px;">Nghệ sĩ không tồn tại.</div>'; exit; }

// Lấy bài hát MỚI NHẤT (Mới phát hành)
$latestSong = $conn->query("SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists FROM songs s JOIN song_artist sa ON s.SongID = sa.SongID JOIN artists a ON sa.ArtistID = a.ArtistID WHERE sa.ArtistID = $id GROUP BY s.SongID ORDER BY s.ReleaseDate DESC, s.SongID DESC LIMIT 1")->fetch_assoc();

// Lấy danh sách bài hát NỔI BẬT (Theo lượt nghe)
$songsResult = $conn->query("SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists FROM songs s JOIN song_artist sa ON s.SongID = sa.SongID JOIN artists a ON sa.ArtistID = a.ArtistID WHERE sa.ArtistID = $id GROUP BY s.SongID ORDER BY s.PlayCount DESC");
$songsJSON = []; $index = 0;
?>
<div style="color: white; padding-bottom: 50px;">
    <div style="display: flex; align-items: center; gap: 30px; margin-bottom: 40px; padding-top: 20px;">
        <img src="<?php echo htmlspecialchars($artist['Image_URL'] ?: 'https://via.placeholder.com/150'); ?>" style="width: 140px; height: 140px; border-radius: 50%; object-fit: cover;">
        <div>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <h1 style="font-size: 50px; font-weight: 800; margin: 0;"><?php echo htmlspecialchars($artist['Name']); ?></h1>
                <i class="fa-solid fa-circle-play" onclick="playPlaylist(0, 'data-artist-view')" style="font-size: 40px; color: var(--purple-primary); cursor: pointer; transition: 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"></i>
            </div>
            <div style="display: flex; align-items: center; gap: 20px; font-size: 14px; color: var(--text-secondary);">
                <span><?php echo number_format(rand(10000, 999999)); ?> người quan tâm</span>
                <button style="background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px; text-transform: uppercase; font-size: 12px; font-weight: bold;"><i class="fa-solid fa-user-plus"></i> Quan tâm</button>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 30px;">
        
        <div style="width: 35%;">
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 15px;">Mới Phát Hành</h3>
            <?php if ($latestSong): ?>
                <div onclick="loadContent('song_view.php?id=<?php echo $latestSong['SongID']; ?>')" style="background: rgba(255,255,255,0.05); border-radius: 10px; padding: 15px; display: flex; gap: 15px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                    <img src="<?php echo htmlspecialchars($latestSong['CoverImage_URL'] ?: 'https://via.placeholder.com/100'); ?>" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover;">
                    <div style="display: flex; flex-direction: column; justify-content: center; gap: 5px;">
                        <span style="font-size: 12px; color: var(--text-secondary);">Single</span>
                        <span style="font-size: 16px; font-weight: bold;"><?php echo htmlspecialchars($latestSong['Title']); ?></span>
                        <span style="font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($latestSong['Artists']); ?></span>
                        <span style="font-size: 12px; color: var(--text-secondary); margin-top: 5px;"><?php echo $latestSong['ReleaseDate'] ? date('d/m/Y', strtotime($latestSong['ReleaseDate'])) : ''; ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: gray; font-size: 14px;">Chưa có sản phẩm nào.</p>
            <?php endif; ?>
        </div>

        <div style="width: 65%;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="font-size: 20px; font-weight: 700; margin: 0;">Bài Hát Nổi Bật</h3>
                <span style="font-size: 12px; color: var(--text-secondary); cursor: pointer; text-transform: uppercase;">Tất cả <i class="fa-solid fa-chevron-right"></i></span>
            </div>
            
            <div class="song-list-container" style="display: flex; flex-direction: column; gap: 5px;">
                <?php if ($songsResult && $songsResult->num_rows > 0): while($row = $songsResult->fetch_assoc()): 
                    $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists'], 'cover' => $row['CoverImage_URL']];
                ?>
                    <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-artist-view')">
                        <?php renderSongItem($row); ?>
                    </div>
                <?php endwhile; else: ?>
                    <p style="color: gray; font-style: italic;">Chưa có bài hát nào.</p>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>
<div id="data-artist-view" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, 'UTF-8'); ?>'></div>