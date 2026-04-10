<?php
session_start();

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Truy vấn lấy tất cả bài hát và gộp tên ca sĩ
$sql = "SELECT s.SongID, s.Title, s.Duration, s.FilePath_URL, a.Name AS ArtistName 
        FROM songs s
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        ORDER BY s.SongID DESC";
$result = $conn->query($sql);
?>

<style>
    .library-header { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
    .library-header h2 { font-size: 24px; font-weight: 700; color: white; }
    
    .song-list-container { display: flex; flex-direction: column; gap: 8px; }
    
    .song-item-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 15px; background: rgba(255, 255, 255, 0.03);
        border-radius: 8px; transition: 0.3s;
    }
    .song-item-row:hover { background: rgba(255, 255, 255, 0.1); }
    
    .song-info-left { display: flex; align-items: center; gap: 15px; flex: 1; }
    .song-cover-mock { 
        width: 45px; height: 45px; background: linear-gradient(135deg, #9b4de0, #ffbaba); 
        border-radius: 5px; display: flex; justify-content: center; align-items: center; font-size: 20px; 
    }
    
    .song-details h4 { margin: 0; font-size: 15px; color: white; font-weight: 600; }
    .song-details p { margin: 4px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.5); }
    
    .song-duration { color: rgba(255,255,255,0.5); font-size: 13px; width: 60px; text-align: center; }
    
    .btn-play-action {
        background: transparent; border: 1px solid white; color: white;
        padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer;
        transition: 0.2s;
    }
    .btn-play-action:hover { background: var(--purple-primary); border-color: var(--purple-primary); }
</style>

<div class="library-header">
    <h2>Thư Viện Của Tôi</h2>
</div>

<div class="song-list-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="song-item-row">
                <div class="song-info-left">
                    <div class="song-cover-mock"><i class="fa-solid fa-music"></i></div>
                    <div class="song-details">
                        <h4><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p><?php echo htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                
                <div class="song-duration">
                    <?php echo $row['Duration'] ? gmdate('i:s', intval($row['Duration'])) : '--:--'; ?>
                </div>
                
                <div class="song-actions-right">
                    <button class="btn-play-action" onclick="playMusic('<?php echo htmlspecialchars($row['FilePath_URL'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo addslashes(htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8')); ?>', '<?php echo addslashes(htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8')); ?>')">
                        <i class="fa-solid fa-play"></i> Phát
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: rgba(255,255,255,0.5);">Chưa có bài hát nào trong thư viện.</p>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>