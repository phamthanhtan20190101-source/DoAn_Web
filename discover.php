<!--hien banner o muc khám phá-->
<?php
// Tải trang này vào vùng main-content-area của user qua AJAX
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// Lấy tất cả các banner đang hoạt động (IsActive = 1)
$banners = $conn->query("SELECT * FROM banners WHERE IsActive = 1 ORDER BY OrderIndex ASC");
$bannersData = [];
if ($banners) {
    while($row = $banners->fetch_assoc()) {
        $bannersData[] = $row;
    }
}
$conn->close();
?>

<style>
    /* ================= CSS CHO SLIDER (CHUẨN NHƯ HÌNH) ================= */
    .discover-container { width: 100%; }
    
    .slider-container {
        width: 100%;
        position: relative;
        overflow: hidden;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        margin-bottom: 40px;
    }

    .slider-wrapper {
        display: flex;
        transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1); /* Hiệu ứng lướt mượt */
        width: 100%;
    }

    .slide {
        min-width: 100%; /* Mỗi ảnh chiếm trọn 1 chiều rộng */
        position: relative;
        cursor: pointer;
    }

    .slide img {
        width: 100%;
        height: 380px; /* Chiều cao cố định cho slider */
        object-fit: cover; /* Ảnh không bị méo */
        border-radius: 15px;
    }

    /* Tiêu đề mờ phủ lên ảnh */
    .slide-title {
        position: absolute;
        bottom: 30px;
        left: 30px;
        background: rgba(0,0,0,0.6);
        color: white;
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 18px;
        font-weight: 600;
        backdrop-filter: blur(5px);
    }

    /* Các chấm tròn hoa tiêu (Dots) */
    .slider-dots {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 5;
    }

    .dot {
        width: 10px;
        height: 10px;
        background: rgba(255,255,255,0.4);
        border-radius: 50%;
        cursor: pointer;
        transition: 0.3s;
    }

    .dot:hover { background: rgba(255,255,255,0.8); }
    
    /* Chấm đang được chọn (màu tím primary) */
    .dot.active {
        background: var(--purple-primary);
        width: 25px; /* Kéo dài chấm active ra một chút cho đẹp */
        border-radius: 10px;
    }
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

        <div class="slider-dots" id="sliderDots">
            </div>
    </div>

    <h3 style="color:white; margin-bottom:20px;">Gợi ý dành cho bạn</h3>
    <p style="color: var(--text-secondary);">Các bài hát nổi bật sẽ hiển thị tại đây...</p>
</div>

<script>
    // ================= JAVASCRIPT XỬ LÝ CHUYỂN ĐỘNG SLIDER =================
    (() => {
        const wrapper = document.getElementById('sliderWrapper');
        const dotsContainer = document.getElementById('sliderDots');
        const slides = wrapper.querySelectorAll('.slide');
        const totalSlides = slides.length;
        
        if (totalSlides === 0) return;

        let currentIndex = 0;
        let slideInterval;

        // 1. Tự động sinh ra các chấm tròn dựa trên số lượng ảnh
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (i === 0) dot.classList.add('active'); // Chấm đầu tiên active
            dot.addEventListener('click', () => goToSlide(i)); // Click chấm để chuyển ảnh
            dotsContainer.appendChild(dot);
        }

        const dots = dotsContainer.querySelectorAll('.dot');

        // 2. Hàm chuyển đến một slide cụ thể
        function goToSlide(index) {
            currentIndex = index;
            // Di chuyển wrapper sang trái bằng transform translate
            wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // Cập nhật trạng thái active của chấm tròn
            dots.forEach((dot, i) => {
                if (i === currentIndex) dot.classList.add('active');
                else dot.classList.remove('active');
            });
            
            // Reset lại thời gian tự động chuyển (để ko bị chuyển ngay sau khi user vừa click)
            startAutoSlide();
        }

        // 3. Hàm tự động chuyển sang slide tiếp theo
        function nextSlide() {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= totalSlides) nextIndex = 0; // Quay về ảnh đầu nếu hết
            goToSlide(nextIndex);
        }

        // 4. Bắt đầu thời gian tự động chuyển (5 giây một lần)
        function startAutoSlide() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000); 
        }

        // Khởi chạy
        startAutoSlide();
    })();
</script>