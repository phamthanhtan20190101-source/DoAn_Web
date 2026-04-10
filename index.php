<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lyrx - Đồ án Lập trình Web</title>
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

        /* ================= 1. SIDEBAR ================= */
        .sidebar {
            width: 240px; 
            background-color: var(--bg-sidebar); 
            display: flex; 
            flex-direction: column;
            height: 100%; 
        }

        .logo-container { 
            padding: 0 25px;
            cursor: pointer; 
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        
        .lyrx-logo-img {
            max-width: 100%;
            height: 45px;
            object-fit: contain;
        }

        .sidebar-scroll { flex: 1; overflow-y: auto; }
        .sidebar-scroll::-webkit-scrollbar { display: none; } 

        .menu-list { list-style: none; margin-bottom: 15px; }
        .menu-item { 
            padding: 12px 25px; display: flex; align-items: center; gap: 15px; 
            color: #dadada; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s;
        }
        .menu-item:hover { color: white; }
        .menu-item.active { 
            background-color: #393243; color: white; 
            border-left: 3px solid var(--purple-primary); padding-left: 22px; 
        }
        .menu-item i { font-size: 20px; width: 24px; text-align: center; }

        .live-badge { background: red; color: white; font-size: 8px; padding: 2px 4px; border-radius: 4px; font-weight: 800; margin-left: auto; }
        .divider { height: 1px; background-color: rgba(255,255,255,0.1); margin: 15px 25px; }

        .login-card {
            background-color: var(--purple-primary); border-radius: 8px; padding: 15px;
            margin: 10px 20px 20px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .login-card p { color: white; font-size: 12px; font-weight: 600; line-height: 1.6; }
        .btn-login {
            background: transparent; border: 1px solid white; color: white; border-radius: 20px; 
            padding: 6px 20px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-login:hover { background: white; color: var(--purple-primary); }

        /* ================= 2. MAIN VÙNG CHÍNH ================= */
        .main-container { flex: 1; display: flex; flex-direction: column; background-color: var(--bg-body); }
        .admin-sidebar { width: 220px; background-color: var(--bg-sidebar); border-left: 1px solid rgba(255,255,255,0.08); padding: 20px; display: flex; flex-direction: column; gap: 12px; position: sticky; top: 0; height: calc(100vh - 90px); }
        .admin-sidebar h3 { color: white; margin-bottom: 10px; font-size: 14px; }
        .admin-sidebar button { width: 100%; text-align: left; }
        .admin-sidebar .btn-admin { width: 100%; margin-bottom: 5px; }

        /* HEADER */
        .header { 
            height: 70px; padding: 0 40px; display: flex; align-items: center; 
            justify-content: space-between; background-color: var(--bg-header); z-index: 10; 
        }
        
        .header-left { display: flex; align-items: center; gap: 20px; }
        .header-left .nav-btn { color: var(--text-secondary); font-size: 20px; cursor: pointer; }
        
        .search-bar { position: relative; width: 440px; }
        .search-bar i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-size: 18px; }
        .search-bar input { 
            width: 100%; height: 40px; border-radius: 20px; border: none; 
            background: rgba(255, 255, 255, 0.1); padding: 0 15px 0 45px; 
            color: white; outline: none; font-size: 14px;
        }

        .header-right { display: flex; align-items: center; gap: 15px; }
        .btn-vip {
            background-color: var(--purple-primary); color: white; border: none;
            padding: 10px 20px; border-radius: 20px; font-size: 13px; font-weight: 700; cursor: pointer;
        }
        
        .btn-setting, .btn-avatar { 
            width: 40px; height: 40px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); 
            border: none; color: white; display: flex; justify-content: center; align-items: center; cursor: pointer; 
        }
        .btn-avatar { background-image: linear-gradient(to right, #ffbaba, #aee2ff); }
        .btn-avatar i { font-size: 20px; color: rgba(0,0,0,0.5); }
        
        .page-content { flex: 1; overflow-y: auto; padding: 20px 40px 100px 40px; }

        /* ================= 3. PLAYER ================= */
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
        .progress-bar .current-progress { position: absolute; left: 0; top: 0; height: 100%; width: 0%; background: var(--text-primary); border-radius: 5px; }

        /* ================= CUSTOM SELECT2 DARK MODE ================= */
        .select2-container--default .select2-selection--single {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            height: 40px !important; border-radius: 5px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #fff !important; line-height: 40px;
        }
        .select2-dropdown {
            background-color: #231b2e !important; border: 1px solid var(--purple-primary) !important; color: #fff !important;
        }
        .select2-container--default .select2-results__option { color: #fff !important; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--purple-primary) !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #170f23 !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; color: #fff !important;
        }

        /* ================= MODALS & UTILS ================= */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content {
            background: var(--bg-sidebar); border-radius: 10px; padding: 30px; width: 400px; text-align: center;
        }
        .modal-content input {
            width: 100%; padding: 10px; margin: 10px 0; border: 1px solid var(--border-color); border-radius: 5px;
            background: rgba(255,255,255,0.1); color: white;
        }
        .modal-content button {
            width: 100%; padding: 10px; background: var(--purple-primary); color: white; border: none; border-radius: 5px; cursor: pointer;
        }
        .welcome-box {
            position: fixed; top: 20px; right: 20px; background: rgba(32, 28, 45, 0.95);
            border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 14px 18px;
            color: white; box-shadow: 0 14px 40px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-10px);
            transition: 0.4s ease; z-index: 1100; pointer-events: none;
        }
        .welcome-box.show { opacity: 1; transform: translateY(0); }

        .avatar-dropdown {
            position: absolute; top: 80px; right: 40px; width: 240px;
            background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3); padding: 18px; display: none; z-index: 1100;
        }
        .avatar-dropdown.show { display: block; }
        .avatar-dropdown .logout-btn {
            width: 100%; padding: 10px 0; background: #6f55ff; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; margin-top: 10px;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>

    <div id="welcomeBox" class="welcome-box"></div>
    
    <div id="avatarDropdown" class="avatar-dropdown">
        <div class="item"><strong>Tên:</strong> <span id="avatarName"></span></div>
        <div class="item"><strong>Email:</strong> <span id="avatarEmail"></span></div>
        <div class="item"><strong>Quyền:</strong> <span id="avatarRole"></span></div>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Đăng xuất</button>
        </form>
    </div>

    <div class="app">
        <aside class="sidebar">
            <div class="logo-container" onclick="location.reload()">
                <img src="images/logo1.png" alt="Lyrx Logo" class="lyrx-logo-img">
            </div>
            <div class="sidebar-scroll">
                <ul class="menu-list">
                    <li class="menu-item" onclick="loadContent('library.php')"><i class="fa-solid fa-layer-group"></i> Thư Viện</li>
                    <li class="menu-item active"><i class="fa-regular fa-circle-dot"></i> Khám Phá</li>
                    <li class="menu-item"><i class="fa-solid fa-chart-line"></i> #Lyrxchart</li>
                    <li class="menu-item"><i class="fa-solid fa-podcast"></i> Phòng Nhạc <span class="live-badge">LIVE</span></li>
                </ul>
                <div class="divider"></div>
                <ul class="menu-list">
                    <li class="menu-item"><i class="fa-solid fa-music"></i> BXH Nhạc Mới</li>
                    <li class="menu-item"><i class="fa-solid fa-icons"></i> Chủ Đề & Thể Loại</li>
                    <li class="menu-item"><i class="fa-regular fa-star"></i> Top 100</li>
                </ul>
                <?php if (!isset($_SESSION['username'])): ?>
                <div class="login-card">
                    <p>Đăng nhập để khám phá playlist dành riêng cho bạn</p>
                    <button type="button" class="btn-login">ĐĂNG NHẬP</button>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <main class="main-container">
            <header class="header">
                <div class="header-left">
                    <i class="fa-solid fa-arrow-left nav-btn"></i>
                    <i class="fa-solid fa-arrow-right nav-btn"></i>
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Tìm kiếm bài hát, nghệ sĩ, lời bài hát...">
                    </div>
                </div>
                <div class="header-right">
                    <button class="btn-vip">Nâng cấp tài khoản</button>
                    <button class="btn-setting"><i class="fa-solid fa-gear"></i></button>
                    <button class="btn-avatar"><i class="fa-solid fa-user"></i></button>
                </div>
            </header>

            <div class="page-content" id="main-content-area">
                </div>
        </main>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <aside class="admin-sidebar">
            <h3>Admin Menu</h3>
            <button class="btn-admin" style="background-color: #10b981;" onclick="loadContent('admin_dashboard.php')">Bảng điều khiển</button>
            <button class="btn-admin" style="background-color: #ea580c;" onclick="loadContent('approve_songs.php')">Duyệt bài hát chờ</button>
            <button class="btn-admin" onclick="loadContent('admin_songs.php')">Bài hát</button>
            <button class="btn-admin" onclick="loadContent('admin_genres.php')">Thể loại</button>
            <button class="btn-admin" onclick="loadContent('admin_artists.php')">Nghệ sĩ</button>
            <button class="btn-admin" onclick="loadContent('admin_albums.php')">Albums</button>
            <button class="btn-admin" onclick="loadContent('admin_users.php')">Người dùng</button>
            <button class="btn-admin" onclick="loadContent('admin_comments.php')">Bình luận & Báo cáo</button>
        </aside>
        <?php endif; ?>
    </div>

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
                <i class="fa-solid fa-shuffle"></i>
                <i class="fa-solid fa-backward-step"></i>
                <i class="fa-regular fa-circle-play btn-play"></i>
                <i class="fa-solid fa-forward-step"></i>
                <i class="fa-solid fa-repeat"></i>
            </div>
            <div class="progress-container">
                <span class="time-current">00:00</span>
                <div class="progress-bar"><div class="current-progress"></div></div>
                <span class="time-total">00:00</span>
            </div>
        </div>
        <div class="player-right">
            <i class="fa-solid fa-volume-high"></i>
            <div class="volume-bar"><div class="current-volume"></div></div>
        </div>
    </footer>

    <div id="loginModal" class="modal-overlay">
        <div class="modal-content">
            <h2>Đăng nhập Lyrx</h2>
            <form action="login_action.php" method="POST">
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <button type="submit">Đăng nhập</button>
            </form>
            <div class="modal-switch" onclick="openRegisterModal()">Bạn chưa có tài khoản? Đăng ký ngay</div>
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
            <div class="modal-switch" onclick="openLoginModal()">Đã có tài khoản? Đăng nhập</div>
        </div>
    </div>

    <script>
        const avatarDropdown = document.getElementById('avatarDropdown');
        const mainContent = document.getElementById('main-content-area');
        const welcomeBox = document.getElementById('welcomeBox');

        const isLoggedIn = <?php echo isset($_SESSION['username']) ? 'true' : 'false'; ?>;
        const userInfo = {
            username: '<?php echo isset($_SESSION['username']) ? addslashes($_SESSION['username']) : ''; ?>',
            email: '<?php echo isset($_SESSION['email']) ? addslashes($_SESSION['email']) : ''; ?>',
            role: '<?php echo isset($_SESSION['role']) ? addslashes($_SESSION['role']) : ''; ?>'
        };

        // --- CÁC HÀM GIAO DIỆN ---
        function openLoginModal() { 
            document.getElementById('registerModal').style.display = 'none';
            document.getElementById('loginModal').style.display = 'flex'; 
        }
        function openRegisterModal() { 
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'flex'; 
        }

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

        // --- AJAX LOAD NỘI DUNG ---
        function loadContent(url) {
            fetch(url, { credentials: 'same-origin' })
                .then(res => res.text())
                .then(html => {
                    mainContent.innerHTML = html;
                    attachAjaxFormHandler();
                    if (typeof $ !== 'undefined') {
                        $('.search-select').select2({ width: '100%', tags: true });
                    }
                });
        }

        function attachAjaxFormHandler() {
            const ajaxForms = mainContent.querySelectorAll('form[data-ajax]');
            ajaxForms.forEach(form => {
                if (form.dataset.ajaxAttached) return;
                form.dataset.ajaxAttached = 'true';
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const res = await fetch(form.getAttribute('action'), {
                        method: form.getAttribute('method') || 'POST',
                        body: formData
                    });
                    const result = await res.text();
                    if (form.dataset.reloadUrl) loadContent(form.dataset.reloadUrl);
                    else { mainContent.innerHTML = result; attachAjaxFormHandler(); }
                });
            });
        }

        // --- HÀM XÓA HỆ THỐNG ---
        window.deleteSong = function(songId) {
            if (!confirm('Bạn có chắc muốn xóa bài hát này?')) return;
            fetch('song_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'delete', songId: songId})
            }).then(res => res.json()).then(data => {
                alert(data.message);
                if (data.success) loadContent('admin_songs.php');
            });
        };

        window.deleteCategory = function(type, id) {
            if (!confirm('Bạn có chắc muốn xóa dữ liệu này?')) return;
            fetch('category_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'delete', type: type, id: id})
            }).then(res => res.json()).then(data => {
                alert(data.message);
                if (data.success) {
                    const reloadMap = { 'genre': 'admin_genres.php', 'artist': 'admin_artists.php', 'album': 'admin_albums.php', 'comment': 'admin_comments.php' };
                    loadContent(reloadMap[type]);
                }
            });
        };

        // --- KHỞI TẠO ---
        document.addEventListener('DOMContentLoaded', () => {
            showWelcome();
            document.querySelector('.btn-avatar').addEventListener('click', toggleAvatarDropdown);
            const loginBtn = document.querySelector('.btn-login');
            if (loginBtn) loginBtn.addEventListener('click', openLoginModal);

            if (userInfo.role === 'admin') loadContent('admin_dashboard.php');
        });

        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
            if (!e.target.closest('.btn-avatar') && !e.target.closest('#avatarDropdown')) {
                avatarDropdown.classList.remove('show');
            }
        };
    </script>
    <script src="player.js"></script>
</body>
</html>