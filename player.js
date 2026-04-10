// =========================================================================
// TRÌNH PHÁT NHẠC TOÀN DIỆN (Lyrx Audio Player)
// Chứa tất cả: Phát/Dừng, Next/Prev, Shuffle, Repeat, Tua nhạc, Âm lượng
// =========================================================================

let currentAudio = null;
let playlist = [];
let currentIndex = 0;
let isShuffle = false;
let isRepeat = false;

// Khai báo các biến giao diện
let playBtn, progressBar, timeCurrent, timeTotal, progressContainer;
let btnNext, btnPrev, btnShuffle, btnRepeat;
let volumeContainer, currentVolume;

// Đợi giao diện tải xong mới gắn sự kiện
document.addEventListener('DOMContentLoaded', () => {
    // 1. Ánh xạ các nút bấm trên giao diện HTML
    playBtn = document.querySelector('.btn-play');
    progressBar = document.querySelector('.current-progress');
    timeCurrent = document.querySelector('.time-current');
    timeTotal = document.querySelector('.time-total');
    progressContainer = document.querySelector('.progress-bar');
    
    btnNext = document.querySelector('.btn-next');
    btnPrev = document.querySelector('.btn-prev');
    btnShuffle = document.querySelector('.btn-shuffle');
    btnRepeat = document.querySelector('.btn-repeat');

    volumeContainer = document.querySelector('.volume-bar');
    currentVolume = document.querySelector('.current-volume');

    // 2. Gắn sự kiện BẬT / DỪNG (Play / Pause)
    if (playBtn) {
        playBtn.addEventListener('click', togglePlayPause);
    }

    // 3. Gắn sự kiện TUA NHẠC (Click & Kéo thả mượt mà)
    if (progressContainer) {
        let isDraggingProgress = false;

        const updateProgress = (e) => {
            if (!currentAudio || isNaN(currentAudio.duration)) return;
            let rect = progressContainer.getBoundingClientRect();
            let percent = (e.clientX - rect.left) / rect.width;
            percent = Math.max(0, Math.min(1, percent)); // Giới hạn từ 0 đến 1
            currentAudio.currentTime = percent * currentAudio.duration;
            progressBar.style.width = (percent * 100) + '%';
        };

        progressContainer.addEventListener('mousedown', (e) => {
            isDraggingProgress = true;
            updateProgress(e);
        });
        document.addEventListener('mousemove', (e) => {
            if (isDraggingProgress) updateProgress(e);
        });
        document.addEventListener('mouseup', () => {
            isDraggingProgress = false;
        });
    }

    // 4. Gắn sự kiện CHỈNH ÂM LƯỢNG (Click & Kéo thả mượt mà)
    if (volumeContainer) {
        let isDraggingVolume = false;
        
        const updateVolume = (e) => {
            if (!currentAudio) return;
            let rect = volumeContainer.getBoundingClientRect();
            let percent = (e.clientX - rect.left) / rect.width;
            percent = Math.max(0, Math.min(1, percent)); // Giới hạn từ 0 đến 1
            currentAudio.volume = percent;
            if (currentVolume) currentVolume.style.width = (percent * 100) + '%';
        };

        volumeContainer.addEventListener('mousedown', (e) => {
            isDraggingVolume = true;
            updateVolume(e);
        });
        document.addEventListener('mousemove', (e) => {
            if (isDraggingVolume) updateVolume(e);
        });
        document.addEventListener('mouseup', () => {
            isDraggingVolume = false;
        });
    }

    // 5. Gắn sự kiện NEXT, PREV, SHUFFLE, REPEAT
    if (btnNext) btnNext.addEventListener('click', nextSong);
    if (btnPrev) btnPrev.addEventListener('click', prevSong);
    if (btnShuffle) btnShuffle.addEventListener('click', toggleShuffle);
    if (btnRepeat) btnRepeat.addEventListener('click', toggleRepeat);
});


// ==========================================
// CÁC HÀM XỬ LÝ CHÍNH
// ==========================================

// Hàm định dạng phút:giây (00:00)
function formatTime(seconds) {
    if (isNaN(seconds)) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

// Hàm Phát / Dừng nhạc khi bấm nút Play ở thanh dưới đáy
function togglePlayPause() {
    if (!currentAudio) return; 
    if (currentAudio.paused) {
        currentAudio.play();
        playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
    } else {
        currentAudio.pause();
        playBtn.classList.replace('fa-circle-pause', 'fa-circle-play');
    }
}

// Hàm BẮT ĐẦU CHẠY DANH SÁCH (Được gọi khi bấm nút "Phát" trong Thư viện)
function playPlaylist(index) {
    const dataEl = document.getElementById('current-playlist-data');
    if (dataEl) {
        window.currentPlaylistData = JSON.parse(dataEl.getAttribute('data-playlist'));
    }

    playlist = window.currentPlaylistData || []; 
    if (playlist.length === 0) return;
    
    currentIndex = index;
    loadAndPlaySong();
}

// Hàm LÕI: Tải và phát bài hát theo currentIndex
function loadAndPlaySong() {
    let song = playlist[currentIndex];
    if (!song) return;

    // A. Cập nhật thông tin UI (Tên bài, Ca sĩ)
    document.querySelector('.player .song-title').textContent = song.title;
    document.querySelector('.player .song-artist').textContent = song.artist;
    
    // B. Cập nhật ảnh bìa góc trái dưới cùng (Kèm hiệu ứng nốt nhạc nếu không có ảnh)
    const thumb = document.querySelector('.player .song-thumb');
    if (song.cover && song.cover !== '') {
        thumb.style.backgroundImage = `url(${song.cover})`;
        thumb.style.backgroundSize = 'cover';
        thumb.style.backgroundPosition = 'center';
        thumb.innerHTML = ''; // Xóa nốt nhạc
    } else {
        thumb.style.backgroundImage = 'none';
        thumb.style.background = 'linear-gradient(135deg, #9b4de0, #ffbaba)'; // Màu Gradient bắt mắt
        thumb.style.display = 'flex';
        thumb.style.justifyContent = 'center';
        thumb.style.alignItems = 'center';
        thumb.innerHTML = '<i class="fa-solid fa-music" style="color: white; font-size: 20px;"></i>';
    }

    // C. Tắt bài cũ nếu có
    if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
    }

    // D. Tạo luồng âm thanh mới
    currentAudio = new Audio(song.url);
    
    // Khôi phục mức âm lượng hiện tại trên thanh UI
    if (currentVolume) {
        let volWidth = currentVolume.style.width || '50%';
        currentAudio.volume = parseFloat(volWidth) / 100;
    }
    
    // E. Các sự kiện cập nhật thanh thời gian
    currentAudio.addEventListener('loadedmetadata', function() {
        timeTotal.textContent = formatTime(currentAudio.duration);
    });

    currentAudio.addEventListener('timeupdate', function() {
        const percent = (currentAudio.currentTime / currentAudio.duration) * 100;
        progressBar.style.width = percent + '%';
        timeCurrent.textContent = formatTime(currentAudio.currentTime);
    });

    // F. Xử lý khi HÁT XONG BÀI
    currentAudio.addEventListener('ended', function() {
        if (isRepeat) {
            currentAudio.currentTime = 0;
            currentAudio.play();
        } else {
            nextSong();
        }
    });

    // Bắt đầu phát
    currentAudio.play();
    if (playBtn) playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
}

// Hàm: BÀI TIẾP THEO (Next)
function nextSong() {
    if (playlist.length === 0) return;
    if (isShuffle) {
        currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
        currentIndex = (currentIndex + 1) % playlist.length; 
    }
    loadAndPlaySong();
}

// Hàm: BÀI TRƯỚC ĐÓ (Prev)
function prevSong() {
    if (playlist.length === 0) return;
    if (isShuffle) {
        currentIndex = Math.floor(Math.random() * playlist.length);
    } else {
        currentIndex = (currentIndex - 1 + playlist.length) % playlist.length; 
    }
    loadAndPlaySong();
}

// Hàm: BẬT/TẮT TRỘN BÀI (Shuffle)
function toggleShuffle() {
    isShuffle = !isShuffle;
    if (btnShuffle) btnShuffle.style.color = isShuffle ? 'var(--purple-primary)' : 'var(--text-primary)';
}

// Hàm: BẬT/TẮT LẶP LẠI 1 BÀI (Repeat)
function toggleRepeat() {
    isRepeat = !isRepeat;
    if (btnRepeat) btnRepeat.style.color = isRepeat ? 'var(--purple-primary)' : 'var(--text-primary)';
}