// =========================================================================
// TRÌNH PHÁT NHẠC TOÀN DIỆN (Lyrx Audio Player) - VERSION PRO
// =========================================================================

let currentAudio = null;
let playlist = [];
let currentIndex = 0;
let isShuffle = false;
let isRepeat = false;
let hasCountedPlay = false;

let playBtn, progressBar, timeCurrent, timeTotal, progressContainer;
let btnNext, btnPrev, btnShuffle, btnRepeat;
let volumeContainer, currentVolume;

document.addEventListener('DOMContentLoaded', () => {
    // Tự động nhận diện nút bấm thông minh dù có thiếu class
    playBtn = document.querySelector('.btn-play');
    progressBar = document.querySelector('.current-progress');
    timeCurrent = document.querySelector('.time-current') || document.querySelector('.progress-container span:first-child');
    timeTotal = document.querySelector('.time-total') || document.querySelector('.progress-container span:last-child');
    progressContainer = document.querySelector('.progress-bar');
    
    btnNext = document.querySelector('.btn-next') || document.querySelector('.fa-forward-step');
    btnPrev = document.querySelector('.btn-prev') || document.querySelector('.fa-backward-step');
    btnShuffle = document.querySelector('.btn-shuffle') || document.querySelector('.fa-shuffle');
    btnRepeat = document.querySelector('.btn-repeat') || document.querySelector('.fa-repeat');

    volumeContainer = document.querySelector('.volume-bar');
    currentVolume = document.querySelector('.current-volume') || (volumeContainer ? volumeContainer.querySelector('div') : null);

    if (playBtn) playBtn.addEventListener('click', togglePlayPause);

    if (progressContainer) {
        let isDraggingProgress = false;
        const updateProgress = (e) => {
            if (!currentAudio || isNaN(currentAudio.duration)) return;
            let rect = progressContainer.getBoundingClientRect();
            let percent = (Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)));
            currentAudio.currentTime = percent * currentAudio.duration;
            if (progressBar) progressBar.style.width = (percent * 100) + '%';
        };
        progressContainer.addEventListener('mousedown', (e) => { isDraggingProgress = true; updateProgress(e); });
        document.addEventListener('mousemove', (e) => { if (isDraggingProgress) updateProgress(e); });
        document.addEventListener('mouseup', () => { isDraggingProgress = false; });
    }

    if (volumeContainer) {
        let isDraggingVolume = false;
        const updateVolume = (e) => {
            if (!currentAudio) return;
            let rect = volumeContainer.getBoundingClientRect();
            let percent = (Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width)));
            currentAudio.volume = percent;
            if (currentVolume) currentVolume.style.width = (percent * 100) + '%';
        };
        volumeContainer.addEventListener('mousedown', (e) => { isDraggingVolume = true; updateVolume(e); });
        document.addEventListener('mousemove', (e) => { if (isDraggingVolume) updateVolume(e); });
        document.addEventListener('mouseup', () => { isDraggingVolume = false; });
    }

    if (btnNext) btnNext.addEventListener('click', nextSong);
    if (btnPrev) btnPrev.addEventListener('click', prevSong);
    if (btnShuffle) btnShuffle.addEventListener('click', toggleShuffle);
    if (btnRepeat) btnRepeat.addEventListener('click', toggleRepeat);
});

function formatTime(seconds) {
    if (isNaN(seconds)) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

function togglePlayPause() {
    if (!currentAudio) return; 
    if (currentAudio.paused) {
        currentAudio.play();
        if (playBtn) playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
    } else {
        currentAudio.pause();
        if (playBtn) playBtn.classList.replace('fa-circle-pause', 'fa-circle-play');
    }
}

// Bắt đầu phát danh sách (Hỗ trợ 100% cho mọi loại Tab)
window.playPlaylist = function(index, dataId = 'current-playlist-data') {
    const dataEl = document.getElementById(dataId);
    if (dataEl) {
        window.currentPlaylistData = JSON.parse(dataEl.getAttribute('data-playlist'));
    }

    playlist = window.currentPlaylistData || []; 
    if (playlist.length === 0) return;
    
    currentIndex = index;
    loadAndPlaySong();
};

function loadAndPlaySong() {
    let song = playlist[currentIndex];
    if (!song) return;

    hasCountedPlay = false;

    // Lưu Lịch sử ngầm trên máy người dùng
    if (typeof saveToHistory === 'function') {
        saveToHistory({ url: song.url, title: song.title, artist: song.artist, cover: song.cover, id: song.id });
    }

    let titleEl = document.querySelector('.player .song-title');
    let artistEl = document.querySelector('.player .song-artist');
    if (titleEl) titleEl.textContent = song.title;
    if (artistEl) artistEl.textContent = song.artist;
    
    const thumb = document.querySelector('.player .song-thumb');
    if (thumb) {
        if (song.cover && song.cover !== '') {
            thumb.style.backgroundImage = `url(${song.cover})`;
            thumb.style.backgroundSize = 'cover';
            thumb.style.backgroundPosition = 'center';
            thumb.innerHTML = ''; 
        } else {
            thumb.style.backgroundImage = 'none';
            thumb.style.background = 'linear-gradient(135deg, #9b4de0, #ffbaba)'; 
            thumb.style.display = 'flex';
            thumb.style.justifyContent = 'center';
            thumb.style.alignItems = 'center';
            thumb.innerHTML = '<i class="fa-solid fa-music" style="color: white; font-size: 20px;"></i>';
        }
    }

    if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
    }

    currentAudio = new Audio(song.url);
    
    // Ghi lịch sử lên Server
    if (song.id) {
        fetch('user_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=log_history&song_id=' + song.id
        }).catch(e => console.log("Lỗi ghi lịch sử: ", e));
    }
    
    if (currentVolume) {
        let volWidth = currentVolume.style.width || '50%';
        currentAudio.volume = parseFloat(volWidth) / 100;
    }
    
    currentAudio.addEventListener('loadedmetadata', function() {
        if (timeTotal) timeTotal.textContent = formatTime(currentAudio.duration);
    });

    currentAudio.addEventListener('timeupdate', function() {
    const percent = (currentAudio.currentTime / currentAudio.duration) * 100;
    if (progressBar) progressBar.style.width = percent + '%';
    if (timeCurrent) timeCurrent.textContent = formatTime(currentAudio.currentTime);

    // --- LOGIC: CỘNG VIEW KHI NGHE TRÊN 50% ---
    if (!hasCountedPlay && currentAudio.duration > 0) {
        // Kiểm tra nếu thời gian hiện tại >= một nửa tổng thời gian
        if (currentAudio.currentTime >= (currentAudio.duration / 2)) {
            hasCountedPlay = true; // Bật công tắc để không bị cộng nhiều lần

            // Gọi xuống Server để báo cáo cộng 1 view
            if (song.id) {
                fetch('user_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=increment_playcount&song_id=' + song.id
                }).catch(e => console.log("Lỗi cộng view: ", e));
            }
        }
    }
});

    currentAudio.addEventListener('ended', function() {
        if (isRepeat) { currentAudio.currentTime = 0; currentAudio.play(); } 
        else { nextSong(); }
    });

    currentAudio.play().catch(e => console.error("Lỗi phát audio:", e));
    if (playBtn) playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
}

function nextSong() {
    if (playlist.length === 0) return;
    if (isShuffle) currentIndex = Math.floor(Math.random() * playlist.length);
    else currentIndex = (currentIndex + 1) % playlist.length; 
    loadAndPlaySong();
}

function prevSong() {
    if (playlist.length === 0) return;
    if (isShuffle) currentIndex = Math.floor(Math.random() * playlist.length);
    else currentIndex = (currentIndex - 1 + playlist.length) % playlist.length; 
    loadAndPlaySong();
}

function toggleShuffle() {
    isShuffle = !isShuffle;
    if (btnShuffle) btnShuffle.style.color = isShuffle ? 'var(--purple-primary)' : 'var(--text-primary)';
}

function toggleRepeat() {
    isRepeat = !isRepeat;
    if (btnRepeat) btnRepeat.style.color = isRepeat ? 'var(--purple-primary)' : 'var(--text-primary)';
}

// =========================================================================
// TÍNH NĂNG LỊCH SỬ NGHE NHẠC BẰNG LOCALSTORAGE
// =========================================================================
const HISTORY_KEY = 'lyrx_history';
const MAX_HISTORY_ITEMS = 20;

window.saveToHistory = function(songObject) {
    let history = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
    history = history.filter(song => song.url !== songObject.url);
    history.unshift(songObject);
    if (history.length > MAX_HISTORY_ITEMS) history = history.slice(0, MAX_HISTORY_ITEMS);
    localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
};

window.renderHistory = function(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let history = JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];

    if (history.length === 0) {
        container.innerHTML = '<p style="color: gray; padding: 20px 0; text-align: center;">Bạn chưa nghe bài hát nào gần đây.</p>';
        return;
    }

    let historyDataDiv = `<div id="data-local-history" style="display:none;" data-playlist='${JSON.stringify(history).replace(/'/g, "&apos;")}'></div>`;

    let htmlString = history.map((song, index) => `
        <div class="song-item-wrapper" onclick="playPlaylist(${index}, 'data-local-history')">
            <div class="song-item">
                <i class="fa-solid fa-music prefix-music-icon"></i>
                <div class="song-cover-container">
                    ${song.cover 
                        ? `<img src="${song.cover}" class="song-cover">` 
                        : `<div class="song-cover-placeholder"><i class="fa-solid fa-music"></i></div>`
                    }
                    <div class="cover-overlay"><i class="fa-solid fa-play overlay-icon-play-small"></i></div>
                </div>
                <div class="song-details">
                    <div class="song-title">${song.title}</div>
                    <div class="song-artist">${song.artist}</div>
                </div>
                <div class="song-action-icons">
                    <div class="action-default"><span class="duration-text">--:--</span></div>
                    <div class="action-hover">
                        <i class="fa-solid fa-play action-sub-icon" style="color: var(--purple-primary);"></i>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = htmlString + historyDataDiv;
};