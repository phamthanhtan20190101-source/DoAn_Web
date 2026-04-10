// Các biến toàn cục cho trình phát nhạc
let currentAudio = null;
let playBtn, progressBar, timeCurrent, timeTotal, progressContainer;

// Đợi giao diện tải xong mới gắn sự kiện để không bị lỗi
document.addEventListener('DOMContentLoaded', () => {
    playBtn = document.querySelector('.btn-play');
    progressBar = document.querySelector('.current-progress');
    timeCurrent = document.querySelector('.time-current');
    timeTotal = document.querySelector('.time-total');
    progressContainer = document.querySelector('.progress-bar'); // Thanh chứa tiến trình

    // 1. XỬ LÝ SỰ KIỆN BẤM NÚT PLAY / PAUSE DƯỚI ĐÁY
    if (playBtn) {
        playBtn.addEventListener('click', function() {
            if (!currentAudio) return; // Chưa có bài nào thì bỏ qua

            if (currentAudio.paused) {
                currentAudio.play();
                playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
            } else {
                currentAudio.pause();
                playBtn.classList.replace('fa-circle-pause', 'fa-circle-play');
            }
        });
    }

    // 2. XỬ LÝ SỰ KIỆN TUA NHẠC (CLICK VÀO THANH PROGRESS)
    if (progressContainer) {
        progressContainer.addEventListener('click', function(e) {
            // Nếu chưa có nhạc hoặc nhạc chưa load xong thì không cho tua
            if (!currentAudio || isNaN(currentAudio.duration)) return;
            
            // Lấy tọa độ X nơi chuột click vào
            const clickX = e.offsetX;
            // Lấy tổng chiều rộng của thanh progress
            const width = this.clientWidth;
            // Tính ra phần trăm tua
            const percent = clickX / width;
            
            // Cập nhật lại thời gian hiện tại của bài hát
            currentAudio.currentTime = percent * currentAudio.duration;
        });
    }
});

// Hàm đổi giây sang định dạng Phút:Giây (00:00)
function formatTime(seconds) {
    if (isNaN(seconds)) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

// Hàm BẬT NHẠC (Được gọi khi bấm nút Phát ở Thư viện)
function playMusic(fileUrl, title, artist) {
    document.querySelector('.player .song-title').textContent = title;
    document.querySelector('.player .song-artist').textContent = artist;

    if (currentAudio) {
        currentAudio.pause();
    }

    currentAudio = new Audio(fileUrl);
    
    // Load xong lấy tổng thời gian
    currentAudio.addEventListener('loadedmetadata', function() {
        timeTotal.textContent = formatTime(currentAudio.duration);
    });

    // Cập nhật thanh chạy liên tục
    currentAudio.addEventListener('timeupdate', function() {
        const percent = (currentAudio.currentTime / currentAudio.duration) * 100;
        progressBar.style.width = percent + '%';
        timeCurrent.textContent = formatTime(currentAudio.currentTime);
    });

    // Hát xong thì reset nút
    currentAudio.addEventListener('ended', function() {
        playBtn.classList.replace('fa-circle-pause', 'fa-circle-play');
        progressBar.style.width = '0%';
        timeCurrent.textContent = '00:00';
    });

    currentAudio.play();
    playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
}