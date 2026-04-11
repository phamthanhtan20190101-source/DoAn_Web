<?php
session_start();

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy từ khóa từ ô tìm kiếm truyền sang
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchParam = "%{$keyword}%";

// ĐÃ SỬA LỖI Ở ĐÂY: Thêm HAVING để nó thực sự lọc theo Tên bài hát hoặc Tên nhóm ca sĩ
$sql = "SELECT s.SongID, s.Title, s.Duration, s.FilePath_URL, s.CoverImage_URL, GROUP_CONCAT(a.Name SEPARATOR ', ') AS ArtistName 
        FROM songs s
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        GROUP BY s.SongID
        HAVING s.Title LIKE ? OR ArtistName LIKE ?
        ORDER BY s.SongID DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

$songsJSON = []; 
$index = 0;      
?>

<style>
    .song-list-container { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .song-item-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 15px; background: rgba(255, 255, 255, 0.03); border-radius: 8px; transition: 0.3s; }
    .song-item-row:hover { background: rgba(255, 255, 255, 0.1); }
    .song-info-left { display: flex; align-items: center; gap: 15px; flex: 1; }
    .song-cover-mock { width: 45px; height: 45px; background: linear-gradient(135deg, #9b4de0, #ffbaba); border-radius: 5px; display: flex; justify-content: center; align-items: center; font-size: 20px; flex-shrink: 0;}
    .song-cover-img { width: 45px; height: 45px; border-radius: 5px; object-fit: cover; flex-shrink: 0; }
    .song-details h4 { margin: 0; font-size: 15px; color: white; font-weight: 600; }
    .song-details p { margin: 4px 0 0 0; font-size: 12px; color: rgba(255,255,255,0.5); }
    .song-duration { color: rgba(255,255,255,0.5); font-size: 13px; width: 60px; text-align: center; }
    .btn-play-action { background: transparent; border: 1px solid white; color: white; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; transition: 0.2s; }
    .btn-play-action:hover { background: var(--purple-primary); border-color: var(--purple-primary); }
</style>

<div class="song-list-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
            $songsJSON[] = [
                'url' => htmlspecialchars($row['FilePath_URL'], ENT_QUOTES, 'UTF-8'),
                'title' => htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'),
                'artist' => htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'),
                'cover' => htmlspecialchars($row['CoverImage_URL'] ?? '', ENT_QUOTES, 'UTF-8')
            ];
            ?>
            <div class="song-item-row">
                <div class="song-info-left">
                    <?php if (!empty($row['CoverImage_URL'])): ?>
                        <img src="<?php echo htmlspecialchars($row['CoverImage_URL'], ENT_QUOTES, 'UTF-8'); ?>" class="song-cover-img" alt="Cover">
                    <?php else: ?>
                        <div class="song-cover-mock"><i class="fa-solid fa-music"></i></div>
                    <?php endif; ?>
                    
                    <div class="song-details">
                        <h4><?php echo htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p><?php echo htmlspecialchars($row['ArtistName'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
                
                <div class="song-duration">
                    <?php echo $row['Duration'] ? gmdate('i:s', intval($row['Duration'])) : '--:--'; ?>
                </div>
                
                <div class="song-actions-right">
                    <button class="btn-play-action" onclick="playPlaylist(<?php echo $index; ?>)">
                        <i class="fa-solid fa-play"></i> Phát
                    </button>
                </div>
            </div>
            <?php $index++; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color: rgba(255,255,255,0.5); padding: 20px 0;">Không tìm thấy bài hát hoặc ca sĩ nào phù hợp với "<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"</p>
    <?php endif; ?>
</div>

<div id="current-playlist-data" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

<?php 
$stmt->close();
$conn->close(); 
?>