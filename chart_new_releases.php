<?php
session_start();
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$username = isset($_SESSION['username']) ? $conn->real_escape_string($_SESSION['username']) : '';

// LOGIC SQL: 
// 1. Lấy bài hát có ReleaseDate trong vòng 7 ngày qua ( >= DATE_SUB )
// 2. Sắp xếp theo Lượt nghe (PlayCount DESC)
// 3. Giới hạn 100 bài (LIMIT 100)
$sql = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists,
               IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
        FROM songs s
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
        WHERE s.ReleaseDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status = 1
        GROUP BY s.SongID
        ORDER BY s.PlayCount DESC, s.ReleaseDate DESC
        LIMIT 100";

$result = $conn->query($sql);
$songsJSON = [];
$index = 0;
?>

<style>
    .bxh-header {
        position: relative; width: 100%; height: 280px; border-radius: 15px; overflow: hidden; margin-bottom: 30px;
        /* Ảnh nền mờ cho Banner */
        background: url('https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
    }
    .bxh-header-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(23,15,35,0.1) 0%, var(--bg-body) 100%);
        display: flex; flex-direction: column; justify-content: flex-end; padding: 30px;
    }
    .bxh-title { 
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        font-size: 45px; 
        font-weight: 800; 
        color: white; 
        text-transform: uppercase; 
        margin: 0; 
        text-shadow: 0 4px 15px rgba(0,0,0,0.6); 
        letter-spacing: 2px;
    }
    .bxh-subtitle { color: rgba(255,255,255,0.7); font-size: 15px; margin-top: 8px; }
    
    /* CSS CHO SỐ THỨ TỰ BẢNG XẾP HẠNG */
    .bxh-row { display: flex; align-items: center; gap: 15px; border-radius: 8px; transition: 0.3s; padding-right: 10px;}
    .bxh-row:hover { background: rgba(255,255,255,0.02); }
    .rank-number { width: 50px; text-align: center; font-size: 38px; font-weight: 900; color: transparent; -webkit-text-stroke: 1.5px rgba(255,255,255,0.3); font-family: 'Arial Black', sans-serif;}
    
    /* Màu đặc biệt cho Top 1, 2, 3 */
    .rank-1 { -webkit-text-stroke: 1.5px #4a90e2; text-shadow: 0 0 15px rgba(74,144,226,0.6); color: rgba(74,144,226,0.1);}
    .rank-2 { -webkit-text-stroke: 1.5px #50e3c2; text-shadow: 0 0 15px rgba(80,227,194,0.6); color: rgba(80,227,194,0.1);}
    .rank-3 { -webkit-text-stroke: 1.5px #e35050; text-shadow: 0 0 15px rgba(227,80,80,0.6); color: rgba(227,80,80,0.1);}
    
    .song-item-wrapper { flex: 1; min-width: 0; cursor: pointer; }
</style>

<div class="bxh-header">
    <div class="bxh-header-overlay">
        <h1 class="bxh-title">BXH Nhạc Mới</h1>
        <div class="bxh-subtitle">Top 100 bài hát phát hành trong 7 ngày qua có lượt nghe cao nhất</div>
    </div>
</div>

<?php if ($result && $result->num_rows > 0): ?>
    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
        <button onclick="playPlaylist(0, 'data-bxh-new')" style="background: var(--purple-primary); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.2s;"><i class="fa-solid fa-play"></i> PHÁT TẤT CẢ</button>
    </div>
<?php endif; ?>

<div class="song-list-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php 
        $rank = 1;
        while ($row = $result->fetch_assoc()): 
            $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??''];
            
            // Xác định Class CSS cho Top 3
            $rankClass = ($rank <= 3) ? "rank-{$rank}" : "";
        ?>
            <div class="bxh-row">
                <div class="rank-number <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>, 'data-bxh-new')">
                    <?php renderSongItem($row); ?>
                </div>
            </div>
            <?php $rank++; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 60px; color: gray; background: rgba(255,255,255,0.02); border-radius: 15px;">
            <i class="fa-solid fa-calendar-xmark" style="font-size: 50px; opacity: 0.3; margin-bottom: 20px;"></i>
            <h3 style="color: white; margin-bottom: 10px;">Chưa có dữ liệu BXH</h3>
            <p>Không có bài hát nào được phát hành trong 7 ngày qua.</p>
        </div>
    <?php endif; ?>
</div>

<div id="data-bxh-new" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON ?? []), ENT_QUOTES, "UTF-8"); ?>'></div>

<?php $conn->close(); ?>