<?php
/**
 * Hàm render một dòng bài hát chuẩn giao diện Lyrx
 */
function renderSongItem($row) {
    $songId   = $row['SongID'] ?? 0;
    $title    = htmlspecialchars($row['Title'] ?? 'Không tên');
    $artists  = htmlspecialchars($row['Artists'] ?? ($row['ArtistName'] ?? 'Ẩn danh'));
    $isFav    = !empty($row['IsFavorite']) ? 1 : 0;
    
    // Định dạng thời gian
    if (isset($row['Duration_Formatted'])) {
        $duration = $row['Duration_Formatted'];
    } else {
        $seconds = $row['Duration'] ?? 0;
        $duration = ($seconds > 0) ? gmdate('i:s', intval($seconds)) : '00:00';
    }

    // Logic đổi màu nút tim
    $heartClass = $isFav ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    $heartColor = $isFav ? '#ff4081' : 'white';
    ?>
    <div class="song-item">
        <i class="fa-solid fa-music prefix-music-icon"></i>
        <div class="song-cover-container">
            <?php if (!empty($row['CoverImage_URL'])): ?>
                <img src="<?php echo $row['CoverImage_URL']; ?>" alt="" class="song-cover">
            <?php else: ?>
                <div class="song-cover-placeholder"><i class="fa-solid fa-music"></i></div>
            <?php endif; ?>
            <div class="cover-overlay"><i class="fa-solid fa-play overlay-icon-play-small"></i></div>
        </div>
        <div class="song-details">
            <div class="song-title"><?php echo $title; ?></div>
            <div class="song-artist"><?php echo $artists; ?></div>
        </div>
        <div class="song-action-icons">
            <div class="action-default">
                <span class="duration-text"><?php echo $duration; ?></span>
            </div>
            <div class="action-hover">
                <div class="icon-mv" title="MV">MV</div>
                <i class="fa-solid fa-microphone action-sub-icon" title="Karaoke"></i>
                <i class="<?php echo $heartClass; ?> action-sub-icon btn-heart" style="color: <?php echo $heartColor; ?>;" title="Yêu thích" onclick="toggleFavorite(<?php echo $songId; ?>, this); event.stopPropagation();"></i>
                <i class="fa-solid fa-circle-plus action-sub-icon" title="Thêm vào Playlist" onclick="openAddToPlaylistModal(<?php echo $songId; ?>); event.stopPropagation();"></i>
            </div>
        </div>
    </div>
    <?php
}
?>