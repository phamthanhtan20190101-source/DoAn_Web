<?php
session_start();
// Kéo file giao diện chuẩn vào để dùng chung
include_once 'render_helper.php'; 

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Đảm bảo các bảng luôn tồn tại để không bị lỗi
$conn->query("CREATE TABLE IF NOT EXISTS user_favorites (Username VARCHAR(100), SongID INT, PRIMARY KEY(Username, SongID))");
$conn->query("CREATE TABLE IF NOT EXISTS user_history (ID INT AUTO_INCREMENT PRIMARY KEY, Username VARCHAR(100), SongID INT, ListenedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// ================= 1. LẤY DỮ LIỆU BANNER =================
$banners = $conn->query("SELECT * FROM banners WHERE IsActive = 1 ORDER BY OrderIndex ASC");
$bannersData = [];
if ($banners && $banners->num_rows > 0) {
    while($row = $banners->fetch_assoc()) {
        $bannersData[] = $row;
    }
}

// ================= 2. LẤY DỮ LIỆU BÀI HÁT (KÈM TRẠNG THÁI TIM) =================
$username = isset($_SESSION['username']) ? $conn->real_escape_string($_SESSION['username']) : '';
$sql = "SELECT s.SongID, s.Title, s.Duration, s.FilePath_URL, s.CoverImage_URL, 
        GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists,
        IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite
        FROM songs s
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username'
        GROUP BY s.SongID
        ORDER BY s.SongID DESC";
$result = $conn->query($sql);
$songsJSON = []; 
$index = 0;   
?>

<style>
    /* ================= CSS CHO SLIDER BANNER ================= */
    .discover-container { width: 100%; }
    
    .slider-container { width: 100%; position: relative; overflow: hidden; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); margin-bottom: 40px; }
    .slider-wrapper { display: flex; transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1); width: 100%; }
    .slide { min-width: 100%; position: relative; cursor: pointer; }
    .slide img { width: 100%; height: 380px; object-fit: cover; border-radius: 15px; }
    .slide-title { position: absolute; bottom: 30px; left: 30px; background: rgba(0,0,0,0.6); color: white; padding: 8px 20px; border-radius: 20px; font-size: 18px; font-weight: 600; backdrop-filter: blur(5px); }

    .slider-dots { position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 5; }
    .dot { width: 10px; height: 10px; background: rgba(255,255,255,0.4); border-radius: 50%; cursor: pointer; transition: 0.3s; }
    .dot:hover { background: rgba(255,255,255,0.8); }
    .dot.active { background: var(--purple-primary); width: 25px; border-radius: 10px; }

    /* ================= CSS BỌC BÀI HÁT ================= */
    .song-list-container { display: flex; flex-direction: column; gap: 5px; margin-top: 15px; }
    .song-item-wrapper { cursor: pointer; }
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
                <div style="width:100%; height:300px; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.3); background: rgba(255,255,255,0.03); border-radius:15px;">
                    Chưa có banner nào được bật.
                </div>
            <?php endif; ?>
        </div>
        <div class="slider-dots" id="sliderDots"></div>
    </div>

    <h2 style="color:white; margin-bottom:5px;">Mới Phát Hành</h2>
    
    <div class="song-list-container">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                $songsJSON[] = [
                    'id' => $row['SongID'],
                    'url' => htmlspecialchars($row['FilePath_URL'], ENT_QUOTES, 'UTF-8'),
                    'title' => htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8'),
                    'artist' => htmlspecialchars($row['Artists'] ?? 'Không rõ', ENT_QUOTES, 'UTF-8'),
                    'cover' => htmlspecialchars($row['CoverImage_URL'] ?? '', ENT_QUOTES, 'UTF-8')
                ];
                ?>
                <div class="song-item-wrapper" onclick="if(typeof playPlaylist === 'function') playPlaylist(<?php echo $index; ?>)">
                    <?php renderSongItem($row); // Gọi hàm render giao diện chuẩn Zing MP3 ?>
                </div>
                <?php $index++; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color: rgba(255,255,255,0.5); padding: 20px 0;">Chưa có bài hát nào trong hệ thống.</p>
        <?php endif; ?>
    </div>

    <div id="current-playlist-data" style="display: none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

</div>

<script>
    // ================= JAVASCRIPT XỬ LÝ CHUYỂN ĐỘNG SLIDER =================
    (() => {
        const wrapper = document.getElementById('sliderWrapper');
        const dotsContainer = document.getElementById('sliderDots');
        const slides = wrapper.querySelectorAll('.slide');
        const totalSlides = slides.length;
        if (totalSlides === 0) return;
        let currentIndex = 0; let slideInterval;

        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('div'); dot.classList.add('dot');
            if (i === 0) dot.classList.add('active'); 
            dot.addEventListener('click', () => goToSlide(i)); 
            dotsContainer.appendChild(dot);
        }
        const dots = dotsContainer.querySelectorAll('.dot');

        function goToSlide(index) {
            currentIndex = index;
            wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
            dots.forEach((dot, i) => {
                if (i === currentIndex) dot.classList.add('active'); else dot.classList.remove('active');
            });
            startAutoSlide();
        }
        function nextSlide() {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= totalSlides) nextIndex = 0; 
            goToSlide(nextIndex);
        }
        function startAutoSlide() {
            clearInterval(slideInterval); slideInterval = setInterval(nextSlide, 5000); 
        }
        startAutoSlide();
    })();
</script>

<?php $conn->close(); ?>