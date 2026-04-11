<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lyrx - Đồ án Lập trình Web</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ================= BIẾN MÀU SẮC CHUẨN ================= */
        :root {
            --bg-body: #170f23;
            --bg-sidebar: #231b2e;
            --bg-header: #170f23; 
            --bg-player: #120c1c;
            --purple-primary: #9b4de0;
            --text-primary: #ffffff;
            --text-secondary: #ffffff80;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        /* ================= RESET CSS ================= */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', Arial, sans-serif; }
        body { color: var(--text-primary); background-color: var(--bg-body); overflow: hidden; }

        /* ================= BỐ CỤC TỔNG THỂ ================= */
        .app { display: flex; height: calc(100vh - 90px); } 

        /* ================= 1. SIDEBAR TRÁI ================= */
        .sidebar { width: 240px; background-color: var(--bg-sidebar); display: flex; flex-direction: column; height: 100%; border-right: 1px solid var(--border-color); }
        .logo-container { padding: 0 25px; cursor: pointer; height: 90px; display: flex; align-items: center; justify-content: flex-start; }
        .brand-logo { display: flex; align-items: baseline; gap: 2px; text-decoration: none; user-select: none; }
        .logo-main-text { font-size: 32px; font-weight: 900; color: var(--text-primary); letter-spacing: -1.5px; text-transform: lowercase; }
        .logo-highlight { color: var(--purple-primary); font-size: 40px; line-height: 0; }
        .logo-sub-text { font-size: 12px; font-weight: 700; color: var(--purple-primary); text-transform: uppercase; letter-spacing: 2px; margin-left: 5px; opacity: 0.8; }
        .logo-container:hover .logo-main-text { color: var(--purple-primary); transition: 0.3s; }

        .sidebar-scroll { flex: 1; overflow-y: auto; padding-bottom: 20px; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        .sidebar h3 { color: var(--text-secondary); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin: 25px 0 10px 25px; }
        .menu-list { list-style: none; }
        .menu-item { padding: 12px 25px; display: flex; align-items: center; gap: 15px; color: #dadada; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; }
        .menu-item:hover { color: white; background: rgba(255,255,255,0.05); }
        .menu-item.active { background-color: #393243; color: white; border-left: 3px solid var(--purple-primary); padding-left: 22px; }
        .menu-item i { font-size: 18px; width: 24px; text-align: center; opacity: 0.8; }
        .divider { height: 1px; background-color: rgba(255,255,255,0.1); margin: 15px 25px; }

        /* ================= CSS BADGE LIVE ================= */
        .live-badge { 
            background-color: #ff0a0a; 
            color: white; 
            font-size: 10px; 
            padding: 3px 6px; 
            border-radius: 4px; 
            font-weight: 800; 
            margin-left: auto;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .login-card { background-color: var(--purple-primary); border-radius: 8px; padding: 15px; margin: 10px 20px 20px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .login-card p { color: white; font-size: 12px; font-weight: 600; line-height: 1.6; }
        .btn-login { background: transparent; border: 1px solid white; color: white; border-radius: 20px; padding: 6px 20px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background: white; color: var(--purple-primary); }

        /* ================= 2. CSS BÀI HÁT (SỬA LỖI LỆCH ICON TRIỆT ĐỂ) ================= */
        .song-item { display: flex; align-items: center; padding: 8px 15px; border-radius: 8px; transition: 0.2s; cursor: pointer; }
        .song-item:hover { background-color: rgba(255, 255, 255, 0.05); }
        
        .prefix-music-icon { width: 20px; font-size: 14px; color: var(--text-secondary); margin-right: 15px; opacity: 0.6; text-align: center; }
        
        .song-cover-container { position: relative; width: 50px; height: 50px; margin-right: 15px; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
        .song-cover { width: 100%; height: 100%; object-fit: cover; text-indent: -10000px; }
        .song-cover-placeholder { width: 100%; height: 100%; background: linear-gradient(135deg, #4e346b, #231b2e); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); font-size: 18px; }

        .cover-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.2s; }
        /* Khi hover vào nguyên DÒNG BÀI HÁT, icon play trên ảnh sẽ hiện ra */
        .song-item:hover .cover-overlay { opacity: 1; }
        .overlay-icon-play-small { color: white; font-size: 18px; }

        .song-details { flex: 1; display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .song-title { font-size: 14px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .song-artist { font-size: 12px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* VÙNG CHỨA ICON BÊN PHẢI (Ép cứng width 150px để không bị đẩy) */
        .song-action-icons { width: 150px; display: flex; align-items: center; justify-content: flex-end; margin-left: 20px; }
        
        /* Trạng thái mặc định: Hiện thời gian */
        .action-default { display: flex; align-items: center; justify-content: flex-end; width: 100%; }
        .duration-text { font-size: 13px; color: var(--text-secondary); }

        /* Trạng thái Hover: Ẩn mặc định, hiện icon */
        .action-hover { display: none; align-items: center; justify-content: flex-end; gap: 15px; width: 100%; }
        .action-sub-icon { color: white; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .action-sub-icon:hover { color: var(--purple-primary); }
        .icon-mv { border: 1px solid rgba(255,255,255,0.5); color: white; font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 3px; cursor: pointer; transition: 0.2s; }
        .icon-mv:hover { border-color: var(--purple-primary); color: var(--purple-primary); }

        /* Lệnh hoán đổi khi Hover chuột */
        .song-item:hover .action-default { display: none; }
        .song-item:hover .action-hover { display: flex; }

        /* ================= 3. VÙNG CHÍNH & HEADER ================= */
        .main-container { flex: 1; display: flex; flex-direction: column; background-color: var(--bg-body); position: relative; }
        .header { height: 70px; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; background-color: var(--bg-header); z-index: 10; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-left .nav-btn { color: var(--text-secondary); font-size: 20px; cursor: pointer; }
        .search-bar { position: relative; width: 440px; }
        .search-bar input { width: 100%; height: 40px; border-radius: 20px; border: none; background: rgba(255, 255, 255, 0.1); padding: 0 15px 0 45px; color: white; outline: none; font-size: 14px; }
        .search-bar i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 18px; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        .btn-vip { background-color: var(--purple-primary); color: white; border: none; padding: 10px 20px; border-radius: 20px; font-size: 13px; font-weight: 700; cursor: pointer; }
        .btn-setting, .btn-avatar { width: 40px; height: 40px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); border: none; color: white; display: flex; justify-content: center; align-items: center; cursor: pointer; }
        .btn-avatar { background-image: linear-gradient(to right, #ffbaba, #aee2ff); }
        .page-content { flex: 1; overflow-y: auto; padding: 20px 40px 100px 40px; }

        /* ================= 4. PLAYER & MODALS ================= */
        .player { position: fixed; bottom: 0; left: 0; width: 100%; height: 90px; background-color: var(--bg-player); border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 100; }
        .player-left { width: 30%; display: flex; align-items: center; gap: 15px; }
        .song-thumb { width: 45px; height: 45px; border-radius: 5px; background-color: #333; } 
        .song-info { display: flex; flex-direction: column; gap: 3px; }
        .song-title { font-size: 14px; font-weight: 500; }
        .song-artist { font-size: 12px; color: var(--text-secondary); }
        .player-center { width: 40%; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .control-buttons { display: flex; align-items: center; gap: 25px; }
        .control-buttons i { font-size: 18px; cursor: pointer; color: var(--text-primary); transition: 0.2s; }
        .btn-play { font-size: 35px !important; }
        .progress-container { width: 100%; display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-secondary); }
        .progress-bar { flex: 1; height: 3px; background: rgba(255,255,255,0.2); border-radius: 5px; position: relative; cursor: pointer; }
        .current-progress { position: absolute; left: 0; top: 0; height: 100%; width: 0%; background: var(--text-primary); border-radius: 5px; }
        .player-right { width: 30%; display: flex; justify-content: flex-end; align-items: center; gap: 15px; }
        .volume-bar { width: 100px; height: 3px; background: rgba(255,255,255,0.2); border-radius: 5px; position: relative; cursor: pointer; }
        .volume-bar .current-volume { position: absolute; left: 0; top: 0; height: 100%; width: 50%; background: var(--text-primary); border-radius: 5px; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: var(--bg-sidebar); border-radius: 10px; padding: 30px; width: 400px; text-align: center; }
        .modal-content input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid var(--border-color); border-radius: 5px; background: rgba(255,255,255,0.1); color: white; }
        .modal-content button { width: 100%; padding: 10px; background: var(--purple-primary); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 700; }
        .welcome-box { position: fixed; top: 20px; right: 20px; background: rgba(32, 28, 45, 0.95); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 14px 18px; color: white; box-shadow: 0 14px 40px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-10px); transition: 0.4s ease; z-index: 1100; pointer-events: none; }
        .welcome-box.show { opacity: 1; transform: translateY(0); }
        .avatar-dropdown { position: absolute; top: 80px; right: 40px; width: 240px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); padding: 18px; display: none; z-index: 1100; }
        .avatar-dropdown.show { display: block; }
        .avatar-dropdown .logout-btn { width: 100%; padding: 10px 0; background: #6f55ff; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; margin-top: 10px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <div id="globalNotify" style="position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; font-size: 14px; font-weight: bold; color: white; z-index: 9999; opacity: 0; transform: translateY(-20px); transition: all 0.3s ease; pointer-events: none;"></div>
    <div id="welcomeBox" class="welcome-box"></div>
    
    <div id="avatarDropdown" class="avatar-dropdown">
        <div id="userInfoArea">
            <div style="margin-bottom: 12px; font-size: 14px; color: white;"><strong>Tên:</strong> <span id="avatarName"></span></div>
            <div style="margin-bottom: 12px; font-size: 14px; color: white;"><strong>Email:</strong> <span id="avatarEmail"></span></div>
            <div style="margin-bottom: 12px; font-size: 14px; color: white;"><strong>Quyền:</strong> <span id="avatarRole"></span></div>
        </div>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Đăng xuất</button>
        </form>
    </div>

    <div class="app">
        <aside class="sidebar">
            <div class="logo-container" onclick="location.reload()">
                <div class="brand-logo">
                    <span class="logo-main-text">lyrx</span><span class="logo-highlight">.</span><span class="logo-sub-text">music</span>
                </div>
            </div>
            <div class="sidebar-scroll">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <h3>Hệ thống</h3>
                    <ul class="menu-list">
                        <li class="menu-item active" onclick="loadContent('admin_dashboard.php')"><i class="fa-solid fa-chart-pie"></i> Dashboard</li>
                        <li class="menu-item" onclick="loadContent('admin_banners.php')"><i class="fa-solid fa-images"></i> Quản lý Banner</li>
                        <li class="menu-item" onclick="loadContent('approve_songs.php')"><i class="fa-solid fa-circle-check"></i> Duyệt bài hát</li>
                    </ul>
                    <h3>Nội dung</h3>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('admin_songs.php')"><i class="fa-solid fa-music"></i> Bài hát</li>
                        <li class="menu-item" onclick="loadContent('admin_playlists.php')"><i class="fa-solid fa-list-check"></i> Playlist mẫu</li>
                        <li class="menu-item" onclick="loadContent('admin_genres.php')"><i class="fa-solid fa-tags"></i> Thể loại</li>
                        <li class="menu-item" onclick="loadContent('admin_artists.php')"><i class="fa-solid fa-microphone-lines"></i> Nghệ sĩ</li>
                        <li class="menu-item" onclick="loadContent('admin_albums.php')"><i class="fa-solid fa-compact-disc"></i> Albums</li>
                    </ul>
                    <h3>Người dùng</h3>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('admin_users.php')"><i class="fa-solid fa-users-gear"></i> Thành viên</li>
                        <li class="menu-item" onclick="loadContent('admin_comments.php')"><i class="fa-solid fa-comments"></i> Bình luận</li>
                    </ul>
                    <h3>Cấu hình</h3>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('admin_settings.php')"><i class="fa-solid fa-gears"></i> Cài đặt chung</li>
                    </ul>
                <?php else: ?>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('library.php')"><i class="fa-solid fa-layer-group"></i> Thư Viện</li>
                        <li class="menu-item active" onclick="loadContent('discover.php')"><i class="fa-regular fa-circle-dot"></i> Khám Phá</li>
                        <li class="menu-item" onclick="loadContent('lyrxchart.php')"><i class="fa-solid fa-chart-line"></i> #Lyrxchart</li>
                        <li class="menu-item"><i class="fa-solid fa-podcast"></i> Phòng Nhạc <span class="live-badge">LIVE</span></li>
                    </ul>
                    <div class="divider"></div>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('chart_new_releases.php')"><i class="fa-solid fa-music"></i> BXH Nhạc Mới</li>
                        <li class="menu-item" onclick="loadContent('topic_genre.php')"><i class="fa-solid fa-icons"></i> Chủ Đề & Thể Loại</li>
                        <li class="menu-item"><i class="fa-regular fa-star"></i> Top 100</li>
                    </ul>
                    <?php if (!isset($_SESSION['username'])): ?>
                    <div class="login-card">
                        <p>Đăng nhập để khám phá playlist dành riêng cho bạn</p>
                        <button type="button" class="btn-login" onclick="openLoginModal()">ĐĂNG NHẬP</button>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-container">
            <header class="header">
                <div class="header-left">
                    <i id="btn-nav-back" class="fa-solid fa-arrow-left nav-btn" onclick="goBack()" style="opacity: 0.3; cursor: default;"></i>
                    <i id="btn-nav-forward" class="fa-solid fa-arrow-right nav-btn" onclick="goForward()" style="opacity: 0.3; cursor: default;"></i>
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Tìm kiếm bài hát, nghệ sĩ...">
                    </div>
                </div>
                <div class="header-right">
                    <button class="btn-vip">Nâng cấp tài khoản</button>
                    <button class="btn-setting"><i class="fa-solid fa-gear"></i></button>
                    <button class="btn-avatar"><i class="fa-solid fa-user"></i></button>
                </div>
            </header>
            <div class="page-content" id="main-content-area"></div>
        </main>
    </div>

    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
    <footer class="player">
        <div class="player-left">
            <div class="song-thumb"></div>
            <div class="song-info">
                <div class="song-title">...</div>
                <div class="song-artist">...</div>
            </div>
        </div>
        <div class="player-center">
            <div class="control-buttons">
                <i class="fa-solid fa-shuffle btn-shuffle"></i>
                <i class="fa-solid fa-backward-step btn-prev"></i>
                <i class="fa-regular fa-circle-play btn-play"></i>
                <i class="fa-solid fa-forward-step btn-next"></i>
                <i class="fa-solid fa-repeat btn-repeat"></i>
            </div>
            <div class="progress-container">
                <span class="time-current">00:00</span>
                <div class="progress-bar">
                    <div class="current-progress"></div>
                </div>
                <span class="time-total">00:00</span>
            </div>
        </div>
        <div class="player-right">
            <i class="fa-solid fa-volume-high"></i>
            <div class="volume-bar">
                <div class="current-volume" style="width: 50%;"></div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <div id="loginModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Đăng nhập Lyrx</h2>
            <form action="login_action.php" method="POST">
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <button type="submit">Đăng nhập</button>
            </form>
            <div style="font-size: 13px; margin-top: 15px; color: var(--text-secondary); cursor: pointer;" onclick="openRegisterModal()">Chưa có tài khoản? Đăng ký ngay</div>
        </div>
    </div>

    <div id="registerModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Đăng ký tài khoản</h2>
            <form action="register_action.php" method="POST">
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
                <input type="email" name="email" placeholder="Email" required>
                <button type="submit">Đăng ký</button>
            </form>
            <div style="font-size: 13px; margin-top: 15px; color: var(--text-secondary); cursor: pointer;" onclick="openLoginModal()">Đã có tài khoản? Đăng nhập</div>
        </div>
    </div>

    <div id="addToPlaylistModal" class="modal-overlay" style="z-index: 2500;">
        <div class="modal-content" style="width: 350px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px;">
            <h3 style="color: white; margin-bottom: 20px; font-size: 18px;">Thêm vào Playlist</h3>
            
            <div id="playlistNotify" style="display: none; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; font-weight: bold; text-align: center; transition: 0.3s;"></div>

            <div id="userPlaylistsContainer" style="max-height: 250px; overflow-y: auto; text-align: left; margin-bottom: 20px; border-radius: 8px; background: rgba(0,0,0,0.2);">
                </div>
            
            <button onclick="closeAddToPlaylistModal()" style="width: 100%; background: transparent; padding: 10px; border-radius: 20px; color: white; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; font-weight: bold; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">Đóng</button>
        </div>
    </div>

    <script>
        // --- LOGIC XỬ LÝ THÊM VÀO PLAYLIST ---
        let currentSongIdToAdd = 0;

        window.openAddToPlaylistModal = function(songId) {
            if (!isLoggedIn) { alert('Vui lòng đăng nhập để sử dụng tính năng này!'); return; }
            currentSongIdToAdd = songId;
            
            const notify = document.getElementById('playlistNotify');
            if (notify) notify.style.display = 'none';

            document.getElementById('addToPlaylistModal').style.display = 'flex';
            
            fetch('user_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_user_playlists'
            })
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('userPlaylistsContainer');
                if (data.success && data.playlists.length > 0) {
                    container.innerHTML = data.playlists.map(pl => `
                        <div onclick="submitAddSongToPlaylist(${pl.PlaylistID})" style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: white; cursor: pointer; transition: 0.2s; display: flex; align-items: center;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'">
                            <i class="fa-solid fa-list-music" style="margin-right: 12px; color: var(--purple-primary); font-size: 16px;"></i> 
                            <span style="font-weight: 500; font-size: 14px;">${pl.Title}</span>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="color: gray; text-align: center; padding: 20px;">Bạn chưa có Playlist nào.<br><br><small>Hãy vào Thư viện để tạo Playlist nhé!</small></p>';
                }
            });
        };

        window.closeAddToPlaylistModal = function() {
            document.getElementById('addToPlaylistModal').style.display = 'none';
        };

        window.submitAddSongToPlaylist = function(playlistId) {
            fetch('user_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=add_to_playlist&song_id=${currentSongIdToAdd}&playlist_id=${playlistId}`
            })
            .then(res => res.json())
            .then(data => {
                const notify = document.getElementById('playlistNotify');
                notify.style.display = 'block';
                notify.textContent = data.message;

                if (data.success) {
                    notify.style.background = 'rgba(16, 185, 129, 0.15)';
                    notify.style.color = '#10b981';
                    notify.style.border = '1px solid rgba(16, 185, 129, 0.3)';
                    setTimeout(() => { closeAddToPlaylistModal(); }, 1500);
                } else {
                    notify.style.background = 'rgba(239, 68, 68, 0.15)';
                    notify.style.color = '#ef4444';
                    notify.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                    setTimeout(() => { notify.style.display = 'none'; }, 3000);
                }
            });
        };
    </script>

    <script>
        const mainContent = document.getElementById('main-content-area');
        const welcomeBox = document.getElementById('welcomeBox');
        const avatarDropdown = document.getElementById('avatarDropdown');

        const isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
        const userInfo = {
            username: '<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : ''; ?>',
            email: '<?php echo isset($_SESSION['email']) ? addslashes($_SESSION['email']) : ''; ?>',
            role: '<?php echo isset($_SESSION['role']) ? addslashes($_SESSION['role']) : ''; ?>'
        };

        function openLoginModal() { document.getElementById('registerModal').style.display = 'none'; document.getElementById('loginModal').style.display = 'flex'; }
        function openRegisterModal() { document.getElementById('loginModal').style.display = 'none'; document.getElementById('registerModal').style.display = 'flex'; }

        function showWelcome() {
            if (!isLoggedIn) return;
            welcomeBox.textContent = `Chào mừng trở lại, ${userInfo.username}`;
            welcomeBox.classList.add('show');
            setTimeout(() => welcomeBox.classList.remove('show'), 3000);
        }

        function toggleAvatarDropdown() {
            if (!isLoggedIn) { openLoginModal(); return; }
            document.getElementById('avatarName').textContent = userInfo.username;
            document.getElementById('avatarEmail').textContent = userInfo.email || '---';
            document.getElementById('avatarRole').textContent = userInfo.role || 'user';
            avatarDropdown.classList.toggle('show');
        }

        let pageHistory = [];
        let currentHistoryIndex = -1;

        function loadContent(url, isHistoryNav = false) {
            fetch(url, { credentials: 'same-origin' })
                .then(res => res.text())
                .then(html => {
                    mainContent.innerHTML = html;
                    const scripts = mainContent.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                    attachAjaxFormHandler();
                    if (typeof $ !== 'undefined') { $('.search-select').select2({ width: '100%', tags: true }); }

                    // LOGIC LƯU LỊCH SỬ KHI CHUYỂN TRANG
                    if (!isHistoryNav) {
                        // Cắt bỏ nhánh tương lai nếu người dùng lùi lại rồi lại rẽ sang trang mới
                        pageHistory = pageHistory.slice(0, currentHistoryIndex + 1);
                        pageHistory.push(url);
                        currentHistoryIndex++;
                    }
                    updateNavButtons(); // Cập nhật màu sắc 2 nút mũi tên
                })
                .catch(err => console.error('Lỗi load:', err));
        }

        // --- CÁC HÀM ĐIỀU KHIỂN NÚT BACK / FORWARD ---
        function goBack() {
            if (currentHistoryIndex > 0) {
                currentHistoryIndex--;
                loadContent(pageHistory[currentHistoryIndex], true);
            }
        }

        function goForward() {
            if (currentHistoryIndex < pageHistory.length - 1) {
                currentHistoryIndex++;
                loadContent(pageHistory[currentHistoryIndex], true);
            }
        }

        function updateNavButtons() {
            const backBtn = document.getElementById('btn-nav-back');
            const fwdBtn = document.getElementById('btn-nav-forward');
            
            if (backBtn) {
                backBtn.style.opacity = currentHistoryIndex > 0 ? '1' : '0.3';
                backBtn.style.cursor = currentHistoryIndex > 0 ? 'pointer' : 'default';
            }
            if (fwdBtn) {
                fwdBtn.style.opacity = currentHistoryIndex < pageHistory.length - 1 ? '1' : '0.3';
                fwdBtn.style.cursor = currentHistoryIndex < pageHistory.length - 1 ? 'pointer' : 'default';
            }
        }

        // --- HÀM ĐIỀU KHIỂN HỘP THÔNG BÁO MÀU MÈ ---
        function showGlobalNotify(message, isSuccess) {
            const notify = document.getElementById('globalNotify');
            if (!notify) return;
            
            notify.textContent = message;
            if (isSuccess) {
                notify.style.background = 'rgba(16, 185, 129, 0.95)'; // Xanh lá
                notify.style.border = '1px solid #10b981';
                notify.style.boxShadow = '0 10px 30px rgba(16, 185, 129, 0.2)';
            } else {
                notify.style.background = 'rgba(239, 68, 68, 0.95)'; // Đỏ
                notify.style.border = '1px solid #ef4444';
                notify.style.boxShadow = '0 10px 30px rgba(239, 68, 68, 0.2)';
            }
            
            // Hiệu ứng trượt xuống và hiện rõ
            notify.style.opacity = '1';
            notify.style.transform = 'translateY(0)';
            
            // Tự động mờ đi sau 3 giây
            setTimeout(() => {
                notify.style.opacity = '0';
                notify.style.transform = 'translateY(-20px)';
            }, 3000);
        }

        // --- HÀM XỬ LÝ FORM ĐÃ LOẠI BỎ ALERT ---
        function attachAjaxFormHandler() {
            const ajaxForms = mainContent.querySelectorAll('form[data-ajax]');
            ajaxForms.forEach(form => {
                if (form.dataset.ajaxAttached) return;
                form.dataset.ajaxAttached = 'true';
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const res = await fetch(form.getAttribute('action'), { method: form.getAttribute('method') || 'POST', body: formData });
                    
                    try {
                        const data = await res.clone().json(); 
                        
                        // GỌI THÔNG BÁO XỊN THAY VÌ DÙNG ALERT
                        showGlobalNotify(data.message, data.success); 
                        
                        if (data.success) {
                            // Đợi 0.8 giây để user kịp nhìn thông báo rồi mới chuyển trang
                            if (form.dataset.delayReloadUrl) {
                                setTimeout(() => loadContent(form.dataset.delayReloadUrl), 800);
                            } else if (form.dataset.reloadUrl) {
                                loadContent(form.dataset.reloadUrl);
                            }
                        } else {
                            const btn = form.querySelector('button[type=submit]');
                            if (btn) { btn.disabled = false; btn.innerText = 'Thử lại'; }
                        }
                    } catch (err) {
                        const result = await res.text();
                        if (form.dataset.reloadUrl) { 
                            loadContent(form.dataset.reloadUrl); 
                        } else { 
                            mainContent.innerHTML = result; 
                            attachAjaxFormHandler(); 
                            if (form.dataset.delayReloadUrl) setTimeout(() => { loadContent(form.dataset.delayReloadUrl); }, 2000); 
                        }
                    }
                });
            });
        }

        window.deleteSong = function(songId) {
            if (!confirm('Bạn có chắc muốn xóa bài hát này?')) return;
            fetch('song_action.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'delete', songId: songId}) }).then(res => res.json()).then(data => { alert(data.message); if (data.success) loadContent('admin_songs.php'); });
        };

        window.deleteCategory = function(type, id) {
            if (!confirm('Bạn có chắc muốn xóa?')) return;
            fetch('category_action.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'delete', type: type, id: id}) }).then(res => res.json()).then(data => { alert(data.message); if (data.success) { const reloadMap = { 'genre': 'admin_genres.php', 'artist': 'admin_artists.php', 'album': 'admin_albums.php', 'comment': 'admin_comments.php', 'banner': 'admin_banners.php' }; loadContent(reloadMap[type]); } });
        };

        window.toggleFavorite = function(songId, btn) {
            if (!isLoggedIn) { alert('Vui lòng đăng nhập để thả tim!'); return; }
            fetch('user_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=toggle_favorite&song_id=' + songId
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    if (data.status === 'added') {
                        btn.className = 'fa-solid fa-heart action-sub-icon btn-heart';
                        btn.style.color = '#ff4081';
                    } else {
                        btn.className = 'fa-regular fa-heart action-sub-icon btn-heart';
                        btn.style.color = 'white';
                    }
                } else alert(data.message);
            });
        };

        const searchInput = document.querySelector('.search-bar input');
        let searchTimer;
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const query = this.value.trim();
                searchTimer = setTimeout(() => { if (query.length > 0) { loadContent('search_results.php?q=' + encodeURIComponent(query)); document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active')); } else { loadContent('discover.php'); } }, 500);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if(userInfo.username) { const welcome = document.getElementById('welcomeBox'); welcome.textContent = "Chào mừng trở lại, " + userInfo.username; welcome.classList.add('show'); setTimeout(() => welcome.classList.remove('show'), 3000); }
            document.querySelector('.btn-avatar').addEventListener('click', () => { if(!userInfo.username) { openLoginModal(); return; } document.getElementById('avatarName').textContent = userInfo.username; document.getElementById('avatarRole').textContent = userInfo.role; document.getElementById('avatarDropdown').classList.toggle('show'); });
            window.onclick = (e) => { if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none'; if (!e.target.closest('.btn-avatar') && !e.target.closest('#avatarDropdown')) document.getElementById('avatarDropdown').classList.remove('show'); };
            if (userInfo.role === 'admin') loadContent('admin_dashboard.php'); else loadContent('discover.php');
            const mainApp = document.querySelector('.app');
            mainApp.addEventListener('click', function(e) { const menuItem = e.target.closest('.menu-item'); if (menuItem) { document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active')); menuItem.classList.add('active'); } });
        });
    </script>
    <script src="player.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .lyric-panel { position: fixed; top: 100vh; left: 0; width: 100vw; height: calc(100vh - 90px); background: #170f23; z-index: 999; transition: top 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); display: flex; overflow: hidden; }
        .lyric-panel.show { top: 0; }
        .lyric-bg-blur { position: absolute; top: -10%; left: -10%; width: 120%; height: 120%; filter: blur(60px) brightness(0.4); background-size: cover; background-position: center; z-index: -1; transition: 1s; }
        .lyric-close-btn { position: absolute; top: 30px; right: 40px; font-size: 30px; color: white; cursor: pointer; z-index: 1000; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 50%; width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; transition: 0.3s; }
        .lyric-close-btn:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
        .lyric-left { width: 45%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 50px; }
        .lyric-left img { width: 350px; height: 350px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); margin-bottom: 30px; transition: 0.5s; }
        .lyric-left h2 { color: white; font-size: 30px; font-weight: 800; text-align: center; margin-bottom: 10px; }
        .lyric-left p { color: rgba(255,255,255,0.6); font-size: 18px; text-align: center; }
        .lyric-right { width: 55%; height: 100%; overflow-y: auto; scroll-behavior: smooth; padding: 100px 50px 200px 0; scrollbar-width: none; }
        .lyric-right::-webkit-scrollbar { display: none; }
        .lyric-line { font-size: 36px; font-weight: 700; color: rgba(255,255,255,0.3); margin-bottom: 25px; transition: all 0.3s; cursor: pointer; transform-origin: left center; }
        .lyric-line.active { color: #fff; font-size: 42px; text-shadow: 0 0 20px rgba(255,255,255,0.4); transform: scale(1.05); }
    </style>

    <div id="lyricPanel" class="lyric-panel">
        <div id="lyricBgBlur" class="lyric-bg-blur"></div>
        <div class="lyric-close-btn" onclick="closeLyricPanel()"><i class="fa-solid fa-chevron-down"></i></div>
        
        <div class="lyric-left">
            <img id="lyricCover" src="" alt="Cover">
            <h2 id="lyricTitle">Tên bài hát</h2>
            <p id="lyricArtist">Ca sĩ</p>
        </div>
        
        <div class="lyric-right" id="lyricContainer">
            </div>
    </div>
</body>
</html>