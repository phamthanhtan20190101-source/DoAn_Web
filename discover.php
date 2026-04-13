<?php
session_start();
// 1. Kéo file render helper để dùng hàm renderSongItem
include_once 'render_helper.php'; 

// 2. Kết nối CSDL
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// 3. KIỂM TRA BẢO TRÌ AN TOÀN (Đã sửa lỗi dòng 10 Vy gặp phải)
$resMaint = $conn->query("SELECT ConfigValue FROM settings WHERE ConfigKey = 'maintenance_mode'");
$maintStatus = ($resMaint) ? $resMaint->fetch_assoc()['ConfigValue'] : '0';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($maintStatus === '1' && $userRole !== 'admin') {
    die("<div style='color:white; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='font-size:50px;'>🛠️</h1>
            <h2>Hệ thống đang bảo trì nâng cấp.</h2>
            <p style='opacity:0.6;'> Vui lòng quay lại sau!</p>
         </div>");
}

// 4. ĐẢM BẢO CÁC BẢNG LUÔN TỒN TẠI (Giữ nguyên ý định của Vy)
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (Username VARCHAR(100), SongID INT, PRIMARY KEY(Username, SongID))");
$conn->query("CREATE TABLE IF NOT EXISTS user_history (ID INT AUTO_INCREMENT PRIMARY KEY, Username VARCHAR(100), SongID INT, ListenedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// 5. LẤY DỮ LIỆU BANNER
$banners = $conn->query("SELECT * FROM banners WHERE IsActive = 1 ORDER BY OrderIndex ASC");
$bannersData = [];
if ($banners) while($row = $banners->fetch_assoc()) $bannersData[] = $row;

// 6. LẤY DỮ LIỆU ALBUM VÀ PLAYLIST ADMIN
$albumsQuery = $conn->query("SELECT * FROM albums ORDER BY AlbumID DESC LIMIT 5");
$adminPlaylists = $conn->query("SELECT * FROM playlists WHERE IsAdmin = 1 ORDER BY CreatedAt DESC LIMIT 5");

// 7. LẤY DANH SÁCH BÀI HÁT MỚI PHÁT HÀNH
$username = isset($_SESSION['username']) ? $conn->real_escape_string($_SESSION['username']) : '';
$sql = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') as Artists 
        FROM songs s 
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID 
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID 
        WHERE s.status = 1 
        GROUP BY s.SongID 
        ORDER BY s.ReleaseDate DESC LIMIT 10";
$result = $conn->query($sql);
$songsJSON = []; 
$index = 0;   
?>

<style>
    /* CSS được giữ nguyên đầy đủ để giao diện không bị lệch */
    .discover-container { width: 100%; }
    .slider-container { width: 100%; position: relative; overflow: hidden; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 40px; }
    .slider-wrapper { display: flex; transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1); width: 100%; }
    .slide { min-width: 100%; position: relative; cursor: pointer; }
    .slide img { width: 100%; height: 380px; object-fit: cover; border-radius: 15px; }
    .slide-title { position: absolute; bottom: 30px; left: 30px; background: rgba(0,0,0,0.6); color: white; padding: 8px 20px; border-radius: 20px; font-size: 18px; font-weight: 600; backdrop-filter: blur(5px); }
    .slider-dots { position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 5; }
    .dot { width: 10px; height: 10px; background: rgba(255,255,255,0.4); border-radius: 50%; cursor: pointer; transition: 0.3s; }
    .dot.active { background: var(--purple-primary); width: 25px; border-radius: 10px; }
    .content-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 25px; margin-bottom: 40px; }
    .grid-item { cursor: pointer; transition: 0.3s; }
    .grid-item:hover .cover-box img { transform: scale(1.1); }
    .cover-box { width: 100%; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; margin-bottom: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .cover-box img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .grid-item h4 { color: white; font-size: 15px; margin: 0 0 5px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .grid-item p { color: rgba(255,255,255,0.5); font-size: 13px; margin: 0; }
    .song-list-container { display: flex; flex-direction: column; gap: 5px; margin-top: 15px; margin-bottom: 50px; }
</style>

<div class="discover-container">
    <div class="slider-container">
        <div class="slider-wrapper" id="sliderWrapper">
            <?php if (count($bannersData) > 0): ?>
                <?php foreach($bannersData as $b): ?>
                    <div class="slide" onclick="window.location.href='<?php echo htmlspecialchars($b['LinkURL']); ?>'">
                        <img src="<?php echo htmlspecialchars($b['ImageURL']); ?>" alt="<?php echo htmlspecialchars($b['Title']); ?>">
                        <div class="slide-title"><?php echo htmlspecialchars($b['Title']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="width:100%; height:300px; display:flex; align-items:center; justify-content:center; color:gray; background:rgba(255,255,255,0.03); border-radius:15px;">Chưa có banner nào.</div>
            <?php endif; ?>
        </div>
        <div class="slider-dots" id="sliderDots"></div>
    </div>

    <h2 style="color:white; margin-bottom:20px; font-weight: 800;">Album Hot</h2>
    <div class="content-grid">
        <?php while($alb = $albumsQuery->fetch_assoc()): ?>
            <div class="grid-item" onclick="loadContent('album_view.php?id=<?php echo $alb['AlbumID']; ?>')">
                <div class="cover-box"><img src="<?php echo $alb['CoverImage_URL']; ?>" onerror="this.src='https://via.placeholder.com/200?text=Album'"></div>
                <h4><?php echo htmlspecialchars($alb['Title']); ?></h4>
                <p>Phát hành: <?php echo $alb['ReleaseYear']; ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <h2 style="color:white; margin-bottom:20px; font-weight: 800;">Playlist Gợi Ý</h2>
    <div class="content-grid">
        <?php if ($adminPlaylists && $adminPlaylists->num_rows > 0): ?>
            <?php while($pl = $adminPlaylists->fetch_assoc()): ?>
                <?php $playlistCover = !empty($pl['Image_URL']) ? $pl['Image_URL'] : 'https://via.placeholder.com/300x300/0f172a/9b4de0/ffffff?text=Playlist'; ?>
                <div class="grid-item" onclick="loadContent('playlist_view.php?id=<?php echo $pl['PlaylistID']; ?>')">
                    <div class="cover-box"><img src="<?php echo htmlspecialchars($playlistCover); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/300x300/0f172a/9b4de0/ffffff?text=Playlist'"></div>
                    <h4><?php echo htmlspecialchars($pl['Title']); ?></h4>
                    <p>Bởi Lyrx Music</p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: gray;">Đang cập nhật playlist...</p>
        <?php endif; ?>
    </div>

    <h2 style="color:white; margin-bottom:15px; font-weight: 800;">Mới Phát Hành</h2>
    <div class="song-list-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                $songsJSON[] = [
                    'id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 
                    'artist' => $row['Artists'] ?? 'Không rõ', 'cover' => $row['CoverImage_URL'] ?? ''
                ];
                ?>
                <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $index++; ?>)">
                    <?php renderSongItem($row); ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: gray;">Chưa có bài hát nào.</p>
        <?php endif; ?>
    </div>

    <div id="current-playlist-data" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

    <?php include 'footer.php'; ?>
</div>

<script>
    // Logic Slider (Giữ nguyên của Vy)
    (() => {
        const wrapper = document.getElementById('sliderWrapper');
        const dotsContainer = document.getElementById('sliderDots');
        if (!wrapper || !dotsContainer) return;
        const slides = wrapper.querySelectorAll('.slide');
        if (slides.length === 0) return;
        let currentIndex = 0;
        slides.forEach((_, i) => {
            const dot = document.createElement('div'); dot.classList.add('dot');
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => { currentIndex = i; updateSlider(); });
            dotsContainer.appendChild(dot);
        });
        function updateSlider() {
            wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
            const dots = dotsContainer.querySelectorAll('.dot');
            dots.forEach((d, i) => d.classList.toggle('active', i === currentIndex));
        }
        setInterval(() => { currentIndex = (currentIndex + 1) % slides.length; updateSlider(); }, 5000);
    })();
</script>
<?php $conn->close(); ?>