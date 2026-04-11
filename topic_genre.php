<?php
session_start();
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy danh sách Thể loại
$genres = $conn->query("SELECT * FROM genres ORDER BY Name ASC");

// ĐÃ SỬA: Thêm cột a.Image_URL vào câu lệnh truy vấn
$artists = $conn->query("SELECT a.ArtistID, a.Name, a.Image_URL, SUM(s.PlayCount) as TotalPlays 
                         FROM artists a 
                         JOIN song_artist sa ON a.ArtistID = sa.ArtistID 
                         JOIN songs s ON sa.SongID = s.SongID 
                         GROUP BY a.ArtistID 
                         ORDER BY TotalPlays DESC LIMIT 8");
?>

<style>
    .topic-section { margin-bottom: 40px; }
    .topic-section h2 { color: white; font-size: 20px; font-weight: 700; margin-bottom: 20px; text-transform: capitalize; }
    
    .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .card-grid-small { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    
    /* Box màu bự chuẩn Zing MP3 */
    .topic-card {
        position: relative; height: 140px; border-radius: 12px; overflow: hidden;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: transform 0.3s;
    }
    .topic-card:hover { transform: scale(1.03); }
    
    /* Chữ in chìm (Watermark) */
    .topic-card .watermark {
        position: absolute; font-size: 65px; font-weight: 900; 
        color: rgba(255,255,255,0.1); white-space: nowrap; z-index: 1;
        user-select: none; pointer-events: none;
    }
    
    /* Chữ hiển thị chính */
    .topic-card .title {
        position: relative; z-index: 2; color: white; font-size: 22px; font-weight: 800; text-transform: uppercase;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    /* Các dải màu Gradient (Theo ảnh của bạn) */
    .bg-vpop { background: linear-gradient(to right, #f27121, #e94057); } /* Cam/Đỏ */
    .bg-usuk { background: linear-gradient(to right, #00c6ff, #0072ff); } /* Xanh ngọc */
    .bg-kpop { background: linear-gradient(to right, #11998e, #38ef7d); } /* Xanh lá */
    
    /* Màu ngẫu nhiên cho các thể loại */
    .bg-random-1 { background: linear-gradient(to right, #8e2de2, #4a00e0); }
    .bg-random-2 { background: linear-gradient(to right, #f12711, #f5af19); }
    .bg-random-3 { background: linear-gradient(to right, #fc4a1a, #f7b733); }
    .bg-random-4 { background: linear-gradient(to right, #1fa2ff, #12d8fa); }

    /* Box nghệ sĩ hình tròn */
    .artist-circle { text-align: center; cursor: pointer; transition: 0.3s; }
    .artist-circle:hover { transform: translateY(-5px); }
    .artist-circle .circle { 
        width: 140px; height: 140px; border-radius: 50%; background: linear-gradient(135deg, #4e346b, #231b2e);
        margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        overflow: hidden; /* Quan trọng: Đảm bảo ảnh bên trong không bị tràn ra ngoài hình tròn */
    }
    .artist-circle h4 { color: white; margin: 0; font-size: 15px; font-weight: 600; }
</style>

<div class="topic-section">
    <h2>Quốc Gia</h2>
    <div class="card-grid">
        <div class="topic-card bg-vpop" onclick="loadContent('category_view.php?type=country&val=Việt Nam')">
            <div class="watermark">V-POP</div>
            <div class="title">Nhạc Việt</div>
        </div>
        <div class="topic-card bg-usuk" onclick="loadContent('category_view.php?type=country&val=US-UK')">
            <div class="watermark">US-UK</div>
            <div class="title">Nhạc Âu Mỹ</div>
        </div>
        <div class="topic-card bg-kpop" onclick="loadContent('category_view.php?type=country&val=Hàn Quốc')">
            <div class="watermark">K-POP</div>
            <div class="title">Nhạc Hàn</div>
        </div>
    </div>
</div>

<div class="topic-section">
    <h2>Thể Loại Nhạc</h2>
    <div class="card-grid-small">
        <?php 
        $colors = ['bg-random-1', 'bg-random-2', 'bg-random-3', 'bg-random-4'];
        $i = 0;
        if ($genres && $genres->num_rows > 0): while($g = $genres->fetch_assoc()): 
            $bgClass = $colors[$i % 4]; $i++;
        ?>
            <div class="topic-card <?php echo $bgClass; ?>" style="height: 100px;" onclick="loadContent('category_view.php?type=genre&val=<?php echo $g['GenreID']; ?>&name=<?php echo urlencode($g['Name']); ?>')">
                <div class="title" style="font-size: 16px;"><?php echo htmlspecialchars($g['Name']); ?></div>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<div class="topic-section">
    <h2>Nghệ Sĩ Nổi Bật</h2>
    <div style="display: flex; gap: 30px; overflow-x: auto; padding-bottom: 20px; flex-wrap: wrap;">
        <?php if ($artists && $artists->num_rows > 0): while($a = $artists->fetch_assoc()): ?>
            <div class="artist-circle" onclick="loadContent('category_view.php?type=artist&val=<?php echo $a['ArtistID']; ?>&name=<?php echo urlencode($a['Name']); ?>')">
                <div class="circle">
                    <?php if (!empty($a['Image_URL'])): ?>
                        <img src="<?php echo htmlspecialchars($a['Image_URL']); ?>" alt="<?php echo htmlspecialchars($a['Name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-microphone-lines" style="font-size: 50px; color: var(--purple-primary); opacity: 0.5;"></i>
                    <?php endif; ?>
                </div>
                <h4><?php echo htmlspecialchars($a['Name']); ?></h4>
            </div>
        <?php endwhile; endif; ?>
    </div>
</div>

<?php $conn->close(); ?>