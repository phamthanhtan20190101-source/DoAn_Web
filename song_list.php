<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$type = $_GET['type'] ?? ''; $id = $_GET['id'] ?? ''; $val = $_GET['val'] ?? '';
$titlePage = "Top 100";

if ($type === 'genre') {
    $stmt = $conn->prepare("SELECT Name FROM genres WHERE GenreID = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->bind_result($gName); $stmt->fetch(); $stmt->close();
    $titlePage = "Top 100 Nhạc " . ($gName ?? 'Thể Loại');
    $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Artists FROM songs s LEFT JOIN song_artist sa ON s.SongID = sa.SongID LEFT JOIN artists a ON sa.ArtistID = a.ArtistID WHERE s.GenreID = ? AND s.status = 1 GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 100";
    $stmt = $conn->prepare($sql); $stmt->bind_param("i", $id);
} elseif ($type === 'artist_country') {
    $titlePage = "Top 100 Nhạc " . htmlspecialchars($val);
    $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') as Artists FROM songs s JOIN song_artist sa ON s.SongID = sa.SongID JOIN artists a ON sa.ArtistID = a.ArtistID WHERE a.Country = ? AND s.status = 1 GROUP BY s.SongID ORDER BY s.PlayCount DESC LIMIT 100";
    $stmt = $conn->prepare($sql); $stmt->bind_param("s", $val);
}
$stmt->execute(); $result = $stmt->get_result();
?>

<div class="song-list-container">
    <h2 style="font-size: 28px; font-weight: 800; color: white; margin-bottom: 30px;"><?php echo $titlePage; ?></h2>
    <div class="song-items-list">
        <?php if ($result && $result->num_rows > 0): $count = 1; ?>
            <?php while($song = $result->fetch_assoc()): 
                $isFav = false;
                if(isset($_SESSION['id'])) {
                    $uid = $_SESSION['id']; $sid = $song['SongID'];
                    $check = $conn->query("SELECT 1 FROM user_favorites WHERE AccountID = $uid AND SongID = $sid");
                    if($check && $check->num_rows > 0) $isFav = true;
                }
                $coverUrl = $song['CoverImage_URL'];
                $hasCover = (!empty($coverUrl) && file_exists(__DIR__ . '/' . $coverUrl));
            ?>
                <div class="song-item" 
                     data-song-id="<?php echo $song['SongID']; ?>" 
                     data-title="<?php echo htmlspecialchars($song['Title']); ?>"
                     data-artist="<?php echo htmlspecialchars($song['Artists']); ?>"
                     data-cover="<?php echo $hasCover ? $coverUrl : 'default_nốt_nhạc'; ?>"
                     onclick="if(window.playSong) { window.playSong(<?php echo $song['SongID']; ?>); }">
                    
                    <div class="prefix-music-icon"><?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?></div>
                    <div class="song-cover-container">
                        <?php if ($hasCover): ?>
                            <img src="<?php echo $coverUrl; ?>" class="song-cover">
                        <?php else: ?>
                            <div class="song-cover-placeholder" style="width: 100%; height: 100%; background: linear-gradient(135deg, #4e346b, #231b2e); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); font-size: 18px; border-radius: 4px;"><i class="fa-solid fa-music"></i></div>
                        <?php endif; ?>
                        <div class="cover-overlay"><i class="fa-solid fa-play overlay-icon-play-small"></i></div>
                    </div>
                    <div class="song-details">
                        <div class="song-title"><?php echo htmlspecialchars($song['Title']); ?></div>
                        <div class="song-artist"><?php echo htmlspecialchars($song['Artists']); ?></div>
                    </div>
                    <div class="song-action-icons">
                        <div class="action-hover">
                            <i class="fa-solid fa-microphone action-sub-icon" title="Lời bài hát" onclick="event.stopPropagation(); btnOpenLyric(this)"></i>
                            <i class="<?php echo $isFav ? 'fa-solid' : 'fa-regular'; ?> fa-heart action-sub-icon" style="color: <?php echo $isFav ? '#ff4081' : 'white'; ?>" onclick="event.stopPropagation(); toggleFavorite(<?php echo $song['SongID']; ?>, this)"></i>
                            <i class="fa-solid fa-plus action-sub-icon" onclick="event.stopPropagation(); if(window.openAddToPlaylistModal) window.openAddToPlaylistModal(<?php echo $song['SongID']; ?>)"></i>
                        </div>
                        <div class="action-default"><span class="duration-text"><?php echo number_format($song['PlayCount']); ?></span></div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function btnOpenLyric(btnIcon) {
    const songItem = btnIcon.closest('.song-item');
    const songId = songItem.getAttribute('data-song-id');
    const panel = document.getElementById('lyricPanel');
    if (!panel) return;

    // Cập nhật thông tin lên giao diện Lyric ngay lập tức
    document.getElementById('lyricTitle').textContent = songItem.getAttribute('data-title');
    document.getElementById('lyricArtist').textContent = songItem.getAttribute('data-artist');
    const cover = songItem.getAttribute('data-cover');
    const imgLyric = document.getElementById('lyricCover');
    if(cover === 'default_nốt_nhạc') {
        imgLyric.style.display = 'none'; // Hoặc set ảnh nốt nhạc mặc định
    } else {
        imgLyric.src = cover;
        imgLyric.style.display = 'block';
    }

    // Phát nhạc và hiện Panel
    if (window.playSong) window.playSong(songId);
    panel.classList.add('show');
}
</script>