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
    
    // Tìm dòng bài hát đang được phát
    const activeRow = document.getElementById('song-row-' + playlist[currentIndex].id);

    if (currentAudio.paused) {
        currentAudio.play();
        
        if (activeRow) {
            activeRow.classList.add('is-playing');
            activeRow.classList.remove('is-paused'); // Bỏ trạng thái pause đi
        }
        
        if (playBtn) playBtn.classList.replace('fa-circle-play', 'fa-circle-pause');
    } else {
        currentAudio.pause();
        
        if (activeRow) {
            activeRow.classList.add('is-paused'); // Gắn trạng thái pause vào để CSS dừng sóng
        }
        
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
    if (artistEl) {
        // Biến tên ca sĩ thành thẻ link có thể click và đổi màu khi hover
        artistEl.innerHTML = `<span style="cursor: pointer; transition: 0.2s;" 
                                    onmouseover="this.style.textDecoration='underline'; this.style.color='var(--purple-primary)'" 
                                    onmouseout="this.style.textDecoration='none'; this.style.color='var(--text-secondary)'" 
                                    onclick="loadContent('search_results.php?q=${encodeURIComponent(song.artist)}')">
                                ${song.artist}
                              </span>`;
    }
    
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

    // Bật sóng nhạc cho dòng bài hát tương ứng
    document.querySelectorAll('.song-item').forEach(el => el.classList.remove('is-playing', 'is-paused'));
    if (playlist.length > 0 && playlist[currentIndex]) {
        const activeRow = document.getElementById('song-row-' + playlist[currentIndex].id);
        if (activeRow) activeRow.classList.add('is-playing');
    }
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

// =========================================================================
// HỆ THỐNG LỜI BÀI HÁT ĐỒNG BỘ (LYRICS SYNC ENGINE)
// =========================================================================

let parsedLyrics = [];
let activeLyricIndex = -1;

function playAndShowLyric(btn) {
    // 1. Kích hoạt phát bài hát đó (Giả lập việc click vào nguyên dòng bài hát)
    btn.closest('.song-item-wrapper').click();
    // 2. Mở Panel Lyrics lên
    setTimeout(() => openLyricPanel(), 300);
}

function openLyricPanel() {
    if (!playlist[currentIndex]) return;
    const song = playlist[currentIndex];
    
    // Cập nhật thông tin Ảnh, Tên, Ca sĩ
    document.getElementById('lyricCover').src = song.cover || 'assets/default-cover.jpg';
    document.getElementById('lyricBgBlur').style.backgroundImage = `url('${song.cover}')`;
    document.getElementById('lyricTitle').textContent = song.title;
    document.getElementById('lyricArtist').textContent = song.artist;
    
    // Mở Panel
    document.getElementById('lyricPanel').classList.add('show');
    
    // Tải lời bài hát từ Database
    document.getElementById('lyricContainer').innerHTML = '<div class="lyric-line" style="color:white; font-size:20px;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải lời bài hát...</div>';
    
    // TÍNH NĂNG CHỐNG ĐẠN: Bắt mọi lỗi và hiển thị lên màn hình
    fetch(`get_lyrics.php?id=${song.id}`)
        .then(res => res.text()) // Đọc dạng chữ thô trước để "bắt bệnh"
        .then(text => {
            try {
                const data = JSON.parse(text);
                parseLyrics(data.lyrics);
            } catch(e) {
                console.error("Lỗi dữ liệu JSON:", text);
                parseLyrics("🎶 CẢNH BÁO: File get_lyrics.php đang bị lỗi PHP! 🎶\n\nNội dung lỗi là:\n" + text);
            }
            renderLyrics();
        })
        .catch(err => {
            parseLyrics("🎶 Lỗi kết nối mạng hoặc không tìm thấy file get_lyrics.php 🎶");
            renderLyrics();
        });
}
function closeLyricPanel() {
    document.getElementById('lyricPanel').classList.remove('show');
}

// Thuật toán bóc tách thời gian [mm:ss.xx]
function parseLyrics(rawText) {
    parsedLyrics = [];
    if (!rawText || rawText.trim() === '') {
        parsedLyrics.push({ time: -1, text: "🎶 Chưa có lời bài hát cho ca khúc này 🎶" });
        return;
    }
    
    const lines = rawText.split('\n');
    const regex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/; // Bắt mẫu [00:15.50] Lời...
    
    lines.forEach(line => {
        const match = line.match(regex);
        if (match) {
            const m = parseInt(match[1]);
            const s = parseInt(match[2]);
            const ms = parseInt(match[3]);
            const time = m * 60 + s + ms / (match[3].length === 3 ? 1000 : 100);
            const text = match[4].trim();
            if (text) parsedLyrics.push({ time, text });
        } else if (line.trim() !== '') {
            // Nếu không có thời gian thì hiển thị dạng tĩnh
            parsedLyrics.push({ time: -1, text: line.trim() });
        }
    });
}

// In chữ ra màn hình
// Thuật toán bóc tách thời gian (Chấp nhận mọi chuẩn LRC)
function parseLyrics(rawText) {
    parsedLyrics = [];
    if (!rawText || rawText.trim() === '') {
        parsedLyrics.push({ time: -1, text: "🎶 Chưa có lời bài hát cho ca khúc này 🎶" });
        return;
    }
    
    const lines = rawText.split('\n');
    // Regex này bắt được cả [00:15], [00:15.5], [00:15.50], [00:15.500]
    const regex = /\[(\d{2}):(\d{2})(?:\.(\d{1,3}))?\](.*)/; 
    
    lines.forEach(line => {
        const match = line.match(regex);
        if (match) {
            const m = parseInt(match[1]);
            const s = parseInt(match[2]);
            const msStr = match[3] || '0'; 
            
            // Chuẩn hóa mili-giây
            let ms = parseInt(msStr);
            if (msStr.length === 1) ms *= 100;
            else if (msStr.length === 2) ms *= 10;
            
            const time = m * 60 + s + (ms / 1000);
            const text = match[4].trim();
            if (text) parsedLyrics.push({ time, text });
        } else if (line.trim() !== '') {
            parsedLyrics.push({ time: -1, text: line.trim() });
        }
    });
}

// In chữ ra màn hình
function renderLyrics() {
    const container = document.getElementById('lyricContainer');
    container.innerHTML = '';
    
    parsedLyrics.forEach((lyric, index) => {
        const div = document.createElement('div');
        div.className = 'lyric-line';
        div.id = 'lyric-line-' + index;
        div.textContent = lyric.text;
        
        if(lyric.time !== -1) {
            div.onclick = () => { 
                currentAudio.currentTime = lyric.time; 
                if(currentAudio.paused) currentAudio.play();
            };
        }
        container.appendChild(div);
    });
    activeLyricIndex = -1;
}

// VÒNG LẶP ĐỒNG BỘ THỜI GIAN NHẠC VÀ CHỮ
setInterval(() => {
    const panel = document.getElementById('lyricPanel');
    // Bỏ check .paused để chữ vẫn tự focus đúng dòng kể cả khi đang tạm dừng nhạc
    if (!currentAudio || !panel.classList.contains('show') || parsedLyrics.length === 0) return;

    const currentTime = currentAudio.currentTime;
    
    // Tìm dòng lyric hiện tại
    let newActiveIndex = -1;
    for (let i = 0; i < parsedLyrics.length; i++) {
        if (parsedLyrics[i].time !== -1 && currentTime >= parsedLyrics[i].time) {
            newActiveIndex = i;
        }
    }

    // Nếu nhảy sang dòng mới -> Đổi màu chữ và tự động cuộn
    if (newActiveIndex !== -1 && newActiveIndex !== activeLyricIndex) {
        if (activeLyricIndex !== -1) {
            const oldEl = document.getElementById('lyric-line-' + activeLyricIndex);
            if(oldEl) oldEl.classList.remove('active');
        }
        
        activeLyricIndex = newActiveIndex;
        const newEl = document.getElementById('lyric-line-' + activeLyricIndex);
        
        if (newEl) {
            newEl.classList.add('active');
            newEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}, 50); // Tốc độ quét 50 mili-giây để chữ sáng lên ngay lập tức
// ====================================================================
// HÀM PHÁT NHẠC TỪ PLAYLIST (Được gọi từ nút bấm trên giao diện)
// ====================================================================
window.playPlaylist = function(index, source = null) {
    // 1. Tìm thẻ <div> tàng hình chứa dữ liệu bài hát mà mình đã gài vào HTML
    const dataBox = document.getElementById('current-playlist-data');
    if (!dataBox) {
        console.error("⚠️ Không tìm thấy kho dữ liệu bài hát (#current-playlist-data)!");
        return;
    }

    try {
        // 2. Lấy cục dữ liệu chữ (JSON) và biến nó thành mảng (Array)
        const jsonData = dataBox.getAttribute('data-playlist');
        if (jsonData) {
            // Cập nhật lại mảng playlist và vị trí bài hát hiện tại của hệ thống
            playlist = JSON.parse(jsonData);
            currentIndex = index;

            // 3. Gọi hàm tải bài hát lên trình phát
            // (Hầu hết các file player.js đều dùng hàm loadSong, nếu file của bạn dùng tên khác thì báo mình nhé)
            if (typeof loadSong === 'function') {
                loadSong(currentIndex);
                
                // Ép trình duyệt phát nhạc ngay lập tức
                if (currentAudio) {
                    currentAudio.play().then(() => {
                        // Đổi icon nút Play ở thanh Player thành Pause
                        if (playBtn) {
                            playBtn.classList.remove('fa-circle-play', 'fa-play');
                            playBtn.classList.add('fa-circle-pause');
                        }
                    }).catch(err => {
                        console.error("⚠️ Trình duyệt chặn tự động phát nhạc:", err);
                    });
                }
            } else {
                console.error("⚠️ Lỗi: Không tìm thấy hàm loadSong() trong player.js");
            }
        }
    } catch (error) {
        console.error("⚠️ Lỗi phân tích dữ liệu bài hát:", error);
    }
};// Bắt đầu phát danh sách (Hỗ trợ 100% cho mọi loại Tab)
window.playPlaylist = function(index, dataId = 'current-playlist-data') {
    // Tìm kho dữ liệu bài hát
    let dataEl = document.getElementById(dataId);
    
    // TRICK: Nếu tìm không ra, tự động fallback về kho dữ liệu mặc định
    if (!dataEl) {
        dataEl = document.getElementById('current-playlist-data');
    }

    if (dataEl) {
        window.currentPlaylistData = JSON.parse(dataEl.getAttribute('data-playlist'));
    }

    playlist = window.currentPlaylistData || []; 
    if (playlist.length === 0) {
        console.error("⚠️ Không có bài hát nào trong Playlist!");
        return;
    }
    
    currentIndex = index;
    loadAndPlaySong();
};