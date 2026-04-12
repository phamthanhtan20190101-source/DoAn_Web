<?php 
session_start(); 
// Đếm số bài hát đang chờ duyệt (status = 0)
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $connCount = new mysqli("localhost", "root", "vertrigo", "song_management");
    $resCount = $connCount->query("SELECT COUNT(*) as c FROM songs WHERE status = 0");
    if ($resCount && $rowC = $resCount->fetch_assoc()) {
        $pendingCount = $rowC['c'];
    }
    $connCount->close();
}
?>
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
        /* Tự động điều chỉnh chiều cao: Nếu có thanh nhạc thì trừ 90px, nếu admin thì lấy hết 100vh */
        .app { 
            display: flex; 
            height: 100vh; 
        } 
        
        /* Nếu không phải Admin (có footer player) thì mới trừ đi 90px */
        body:not(.admin-mode) .app {
            height: calc(100vh - 90px);
        }
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

        /* ================= CSS NÚT TẠO PLAYLIST SIDEBAR ================= */
        .btn-create-playlist-sidebar {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            font-size: 14px;
            font-weight: 700;
            padding: 15px 25px;
            cursor: pointer;
            border-top: 1px solid rgba(255,255,255,0.1);
            transition: color 0.2s;
            margin-top: 10px;
        }
        .btn-create-playlist-sidebar:hover {
            color: var(--purple-primary);
        }
        .btn-create-playlist-sidebar i {
            font-size: 18px;
        }

        .login-card { background-color: var(--purple-primary); border-radius: 8px; padding: 15px; margin: 10px 20px 20px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .login-card p { color: white; font-size: 12px; font-weight: 600; line-height: 1.6; }
        .btn-login { background: transparent; border: 1px solid white; color: white; border-radius: 20px; padding: 6px 20px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-login:hover { background: white; color: var(--purple-primary); }

        /* ================= 2. CSS BÀI HÁT ================= */
        .song-item { display: flex; align-items: center; padding: 8px 15px; border-radius: 8px; transition: 0.2s; cursor: pointer; }
        .song-item:hover { background-color: rgba(255, 255, 255, 0.05); }
        
        .prefix-music-icon { width: 20px; font-size: 14px; color: var(--text-secondary); margin-right: 15px; opacity: 0.6; text-align: center; }
        
        .song-cover-container { position: relative; width: 50px; height: 50px; margin-right: 15px; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
        .song-cover { width: 100%; height: 100%; object-fit: cover; text-indent: -10000px; }
        .song-cover-placeholder { width: 100%; height: 100%; background: linear-gradient(135deg, #4e346b, #231b2e); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); font-size: 18px; }

        .cover-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.2s; }
        .song-item:hover .cover-overlay { opacity: 1; }
        .overlay-icon-play-small { color: white; font-size: 18px; }

        .song-details { flex: 1; display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .song-title { font-size: 14px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .song-artist { font-size: 12px; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .song-action-icons { width: 150px; display: flex; align-items: center; justify-content: flex-end; margin-left: 20px; }
        
        .action-default { display: flex; align-items: center; justify-content: flex-end; width: 100%; }
        .duration-text { font-size: 13px; color: var(--text-secondary); }

        .action-hover { display: none; align-items: center; justify-content: flex-end; gap: 15px; width: 100%; }
        .action-sub-icon { color: white; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .action-sub-icon:hover { color: var(--purple-primary); }
        .icon-mv { border: 1px solid rgba(255,255,255,0.5); color: white; font-size: 9px; font-weight: 800; padding: 1px 4px; border-radius: 3px; cursor: pointer; transition: 0.2s; }
        .icon-mv:hover { border-color: var(--purple-primary); color: var(--purple-primary); }

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
        .modal-content input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid var(--border-color); border-radius: 5px; background: rgba(255,255,255,0.1); color: white; outline: none; }
        .modal-content button { width: 100%; padding: 10px; background: var(--purple-primary); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 700; }
        .welcome-box { position: fixed; top: 20px; right: 20px; background: rgba(32, 28, 45, 0.95); border: 1px solid rgba(255,255,255,0.12); border-radius: 14px; padding: 14px 18px; color: white; box-shadow: 0 14px 40px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-10px); transition: 0.4s ease; z-index: 1100; pointer-events: none; }
        .welcome-box.show { opacity: 1; transform: translateY(0); }

        /* HIỆU ỨNG SÓNG NHẠC */
        @keyframes bounce {
            0% { height: 3px; }
            100% { height: 14px; }
        }
        .song-item.is-playing { background-color: rgba(255, 255, 255, 0.05); }
        .song-item.is-playing .song-title { color: var(--purple-primary) !important; }
        .song-item.is-playing .cover-overlay { opacity: 1 !important; }
        .song-item.is-playing .overlay-icon-play-small { display: none; }
        .song-item.is-playing .playing-icon { display: flex !important; }
        .song-item.is-paused .playing-icon span { animation-play-state: paused !important; }
        .song-item.is-paused:hover .playing-icon { display: none !important; }
        .song-item.is-paused:hover .overlay-icon-play-small { display: block; }
        
        /* ================= MENU CÀI ĐẶT ================= */
        .settings-wrapper { position: relative; display: inline-block; }
        .settings-menu {
            position: absolute; top: 130%; right: 0; width: 280px;
            background-color: #28104e;
            border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            z-index: 1000; display: none; padding: 10px 0;
        }
        .settings-menu.show { display: block; }

        .settings-menu ul { list-style: none; padding: 0; margin: 0; }
        .settings-menu li {
            padding: 12px 20px; color: var(--text-secondary); font-size: 14px;
            display: flex; align-items: center; cursor: pointer; position: relative; transition: 0.2s;
        }
        .settings-menu li:hover { background-color: rgba(255,255,255,0.1); color: white; }
        .settings-menu li > i:first-child { width: 25px; font-size: 16px; }
        .settings-menu li .menu-text { flex: 1; }
        .settings-menu li .menu-arrow { font-size: 12px; opacity: 0.6; }

        .settings-divider { height: 1px; background: rgba(255,255,255,0.05); margin: 8px 0; }

        .settings-submenu {
            position: absolute; right: 100%; top: 0; width: 320px;
            background-color: #28104e; border-radius: 8px; box-shadow: -5px 4px 20px rgba(0,0,0,0.6);
            display: none; padding: 15px; margin-right: 5px; cursor: default;
        }
        .settings-menu li.has-submenu:hover .settings-submenu { display: block; }

        .submenu-banner {
            background: linear-gradient(90deg, #9b4de0, #c86dd7); color: white;
            padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; line-height: 1.5;
        }
        .submenu-banner span { font-weight: bold; background: white; color: #9b4de0; padding: 2px 6px; border-radius: 4px; margin-right: 5px; font-size: 10px; text-transform: uppercase;}

        .setting-group { margin-bottom: 20px; }
        .setting-group-title { color: white; font-weight: 700; font-size: 15px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;}
        .badge-plus { background: #9b4de0; color: white; font-size: 9px; padding: 2px 5px; border-radius: 3px; font-weight: bold; }

        .setting-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; color: white; font-size: 14px;}
        .setting-row-desc { font-size: 12px; color: var(--text-secondary); display: block; margin-top: 4px; }

        .switch { position: relative; display: inline-block; width: 34px; height: 18px; flex-shrink: 0;}
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.3); transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        .switch input:checked + .slider { background-color: var(--purple-primary); }
        .switch input:checked + .slider:before { transform: translateX(16px); }

        .radio-row { display: flex; justify-content: space-between; align-items: center; color: var(--text-secondary); margin-bottom: 12px; font-size: 14px; cursor: pointer; transition: 0.2s;}
        .radio-row:hover { color: white; }
        .radio-row input[type="radio"] { accent-color: var(--purple-primary); transform: scale(1.2); cursor: pointer;}

        /* ================= TRUNG TÂM HỖ TRỢ (GIỚI THIỆU / BẢN QUYỀN / QUẢNG CÁO / LIÊN HỆ / THỎA THUẬN / CHÍNH SÁCH) ================= */
        .help-modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #ffffff; display: none; z-index: 3500; overflow: hidden; }
        .help-container { display: flex; width: 100%; height: 100%; max-width: 1200px; margin: 0 auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        
        /* Menu bên trái */
        .help-sidebar { width: 250px; border-right: 1px solid #eaeaea; padding: 40px 0; background: #fbfbfb; flex-shrink: 0; overflow-y: auto; }
        .help-sidebar .help-tab-btn { padding: 15px 30px; font-size: 14px; font-weight: 600; color: #555; cursor: pointer; text-transform: uppercase; transition: 0.2s; }
        .help-sidebar .help-tab-btn:hover { color: var(--purple-primary); }
        .help-sidebar .help-tab-btn.active { color: var(--purple-primary); border-left: 3px solid var(--purple-primary); background: #f0e6f9; }
        
        /* Nội dung bên phải */
        .help-content-area { flex: 1; padding: 50px 60px; overflow-y: auto; position: relative; color: #333; }
        .help-close-btn { position: absolute; top: 20px; right: 30px; font-size: 40px; color: #aaa; cursor: pointer; line-height: 1; transition: 0.2s; }
        .help-close-btn:hover { color: #333; }
        .help-panel { display: none; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Style cho Form nhập liệu */
        .help-form-group { margin-bottom: 20px; display: flex; align-items: flex-start; }
        .help-form-group label { width: 220px; font-weight: 600; font-size: 14px; color: #444; padding-top: 10px; flex-shrink: 0; }
        .help-form-control { flex: 1; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.2s; width: 100%; background: #fff;}
        .help-form-control:focus { border-color: var(--purple-primary); box-shadow: 0 0 0 3px rgba(155, 77, 224, 0.1); }
        textarea.help-form-control { resize: vertical; min-height: 100px; }
        .help-submit-btn { padding: 12px 30px; background: var(--purple-primary); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; transition: 0.2s; text-transform: uppercase;}
        .help-submit-btn:hover { opacity: 0.9; }
        
        /* File upload box */
        .upload-box { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 6px; background: #fafafa; cursor: pointer; color: #777; font-size: 14px; }
        .upload-box:hover { border-color: var(--purple-primary); color: var(--purple-primary); }

        /* ================= AVATAR DROPDOWN MỚI ================= */
        .avatar-dropdown {
            position: absolute; top: 80px; right: 40px; width: 300px;
            background: #28104e; border: 1px solid rgba(255,255,255,0.12);
            border-radius: 8px; box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            display: none; flex-direction: column; z-index: 1100; padding: 0;
        }
        .avatar-dropdown.show { display: flex; }

        .dropdown-header {
            display: flex; align-items: center; gap: 15px; padding: 20px;
            border-bottom: 1px solid var(--border-color); background-color: var(--bg-sidebar);
            border-top-left-radius: 8px; border-top-right-radius: 8px;
        }
        .user-avatar-container {
            position: relative; width: 60px; height: 60px; border-radius: 50%; overflow: hidden;
        }
        .user-avatar { width: 100%; height: 100%; object-fit: cover; }
        .change-avatar-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6); color: white;
            display: flex; justify-content: center; align-items: center;
            font-size: 12px; font-weight: 600; cursor: pointer;
            opacity: 0; transition: opacity 0.3s;
        }
        .user-avatar-container:hover .change-avatar-overlay { opacity: 1; }

        .user-name-info { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        .user-name { color: white; font-size: 16px; font-weight: 700; }
        /* Bỏ background gradient cũ của nút Avatar để nhường chỗ cho ảnh */
        .btn-setting, .btn-avatar { width: 40px; height: 40px; border-radius: 50%; background: rgba(255, 255, 255, 0.1); border: none; color: white; display: flex; justify-content: center; align-items: center; cursor: pointer; overflow: hidden; padding: 0; }
        
        .user-package {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 4px 12px; border-radius: 5px; /* Bo góc nhẹ hơn, kéo dài bề ngang */
            font-size: 11px; font-weight: 800; text-transform: uppercase;
            width: fit-content; letter-spacing: 0.5px; margin-top: 2px;
        }
        /* Chỉnh màu BASIC giống hệt ảnh bạn gửi (Nền xám, chữ trắng) */
        .basic-package { background-color: #a0a0a0; color: #ffffff; }
        .vip-package { background-color: #fcd34d; color: #1f2937; }
        .basic-package { background-color: #f3f4f6; color: #1f2937; }
        .vip-package { background-color: #fcd34d; color: #1f2937; }

        .dropdown-actions { padding: 15px 20px; display: flex; flex-direction: column; gap: 10px; background-color: #28104e; }
        .btn-upgrade {
            display: block; width: 100%; padding: 10px;
            background-color: #059669; color: white;
            border: none; border-radius: 6px; font-weight: 700;
            cursor: pointer; font-size: 14px; text-align: center;
            text-decoration: none; transition: background-color 0.3s;
        }
        .btn-upgrade:hover { background-color: #047857; }
        .dropdown-actions .logout-btn {
            display: block; width: 100%; padding: 10px;
            background-color: #ef4444; color: white;
            border: none; border-radius: 6px; font-weight: 700;
            cursor: pointer; font-size: 14px; transition: background-color 0.3s;
            margin-top: 0;
        }
        .dropdown-actions .logout-btn:hover { background-color: #dc2626; }

        .upgrade-package-section { padding: 15px 20px 20px; background-color: #28104e; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
        .upgrade-package-title { color: white; font-size: 16px; font-weight: 700; margin-bottom: 12px; }
        .upgrade-banner { border-radius: 8px; padding: 15px; display: flex; flex-direction: column; gap: 8px; }
        .vip-banner { background: linear-gradient(135deg, #fcd34d, #f59e0b); }
        .banner-title { display: flex; align-items: baseline; gap: 2px; font-weight: 900; font-size: 18px; color: black; }
        .banner-highlight { color: #b45309; }
        .banner-subtext { color: #b45309; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .banner-package {
            font-size: 24px; font-weight: 900;
            background: linear-gradient(135deg, #1f2937, #111827);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }
        .banner-desc { color: #1f2937; font-size: 11px; line-height: 1.4; }

        /* ================= GIAO DIỆN LỜI BÀI HÁT ================= */
        .lyric-panel { position: fixed; top: 100vh; left: 0; width: 100vw; height: calc(100vh - 90px); background: #170f23; z-index: 999; transition: top 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94); display: flex; overflow: hidden; }
        .lyric-panel.show { top: 0; }
        .lyric-bg-blur { position: absolute; top: -10%; left: -10%; width: 120%; height: 120%; filter: blur(60px) brightness(0.4); background-size: cover; background-position: center; z-index: -1; transition: 1s; }
        .lyric-close-btn { position: absolute; top: 30px; right: 40px; font-size: 30px; color: white; cursor: pointer; z-index: 1000; padding: 10px; background: rgba(255,255,255,0.1); border-radius: 50%; width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; transition: 0.3s; }
        .lyric-close-btn:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
        
        .lyric-left { width: 40%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; }
        .lyric-left img { width: 100%; max-width: 280px; aspect-ratio: 1/1; object-fit: cover; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); margin-bottom: 25px; transition: 0.5s; }
        .lyric-left h2 { color: white; font-size: 26px; font-weight: 800; text-align: center; margin-bottom: 10px; }
        .lyric-left p { color: rgba(255,255,255,0.6); font-size: 16px; text-align: center; }
        
        .lyric-right { width: 60%; height: 100%; overflow-y: auto; scroll-behavior: smooth; padding: 100px 50px 200px 20px; scrollbar-width: none; }
        .lyric-right::-webkit-scrollbar { display: none; }
        .lyric-line { font-size: 26px; font-weight: 700; color: rgba(255,255,255,0.3); margin-bottom: 25px; transition: all 0.3s; cursor: pointer; transform-origin: left center; }
        .lyric-line.active { color: #fff; font-size: 32px; text-shadow: 0 0 15px rgba(255,255,255,0.4); transform: scale(1.02); }
    
        /* ================= CUSTOM SELECT2 DARK THEME ================= */
/* 1. Khung chọn chính */
.select2-container--default .select2-selection--single {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    height: 45px !important;
    border-radius: 8px !important;
    display: flex;
    align-items: center;
}

/* Chữ trong khung chọn */
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: white !important;
    padding-left: 15px !important;
    font-size: 14px;
}

/* Mũi tên xuống */
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 45px !important;
}

/* 2. Khung danh sách xổ xuống */
.select2-dropdown {
    background-color: #231b2e !important; /* Màu nền sidebar */
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    border-radius: 8px !important;
    overflow: hidden;
    z-index: 9999;
}

/* 3. Ô tìm kiếm bên trong danh sách */
.select2-search--dropdown {
    padding: 10px !important;
    background-color: #170f23 !important;
}

.select2-search--dropdown .select2-search__field {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border-radius: 20px !important;
    padding: 8px 15px !important;
    outline: none !important;
}

/* 4. Các mục bài hát trong danh sách */
.select2-results__option {
    padding: 10px 15px !important;
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 14px;
}

/* Khi di chuột qua (Hover) hoặc đang chọn */
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--purple-primary) !important; /* Màu tím chủ đạo */
    color: white !important;
}

/* Khi một mục đã được chọn */
.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: rgba(155, 77, 224, 0.2) !important;
    color: var(--purple-primary) !important;
}
    
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'admin-mode' : ''; ?>">
    <div id="globalNotify" style="position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; font-size: 14px; font-weight: bold; color: white; z-index: 9999; opacity: 0; transform: translateY(-20px); transition: all 0.3s ease; pointer-events: none;"></div>
    <div id="welcomeBox" class="welcome-box"></div>
    
    <div id="avatarDropdown" class="avatar-dropdown">
        <?php if (isset($_SESSION['username'])): 
            // Kiểm tra xem có phải ADMIN không
            $isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

            // Giả lập gói (Thực tế lấy từ DB)
            $_SESSION['subscription_package'] = 'BASIC'; 
            $isUpgraded = ($_SESSION['subscription_package'] === 'VIP');

            // XỬ LÝ ẢNH MẶC ĐỊNH & BẮT LỖI
            $defaultAvatarPath = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username']) . '&background=9b4de0&color=fff&size=150';
            $avatarPath = (!empty($_SESSION['avatar_path'])) ? $_SESSION['avatar_path'] : $defaultAvatarPath;
        ?>
            <div class="dropdown-header">
                <div class="user-avatar-container">
                    <img src="<?php echo $avatarPath; ?>" alt="Avatar" class="user-avatar" id="dropdownAvatarImg" onerror="this.src='<?php echo $defaultAvatarPath; ?>'">
                    <label for="changeAvatarInput" class="change-avatar-overlay">Đổi ảnh</label>
                    <input type="file" id="changeAvatarInput" style="display: none;" accept="image/*">
                </div>
                <div class="user-name-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    
                    <?php if ($isAdmin): ?>
                        <div class="user-package" style="background-color: #ef4444; color: white;">
                            ADMIN
                        </div>
                    <?php else: ?>
                        <div class="user-package <?php echo $isUpgraded ? 'vip-package' : 'basic-package'; ?>">
                            <?php echo $isUpgraded ? 'VIP' : 'BASIC'; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="dropdown-actions">
                <?php if (!$isAdmin && !$isUpgraded): ?>
                    <button onclick="loadContent('upgrade.php'); toggleAvatarDropdown();" class="btn-upgrade">Nâng cấp tài khoản VIP</button>
                <?php endif; ?>
                
                <form action="logout.php" method="POST" style="margin: 0;">
                    <button type="submit" class="logout-btn">Đăng xuất</button>
                </form>
            </div>

            <?php if (!$isAdmin): ?>
            <div class="upgrade-package-section">
                <h4 class="upgrade-package-title">Đặc quyền VIP</h4>
                <div class="upgrade-banner vip-banner">
                    <div class="banner-title">
                        <span>Lyrx</span><span class="banner-highlight">.</span><span class="banner-subtext">music</span>
                    </div>
                    <div class="banner-package">VIP</div>
                    <p class="banner-desc">Thưởng thức âm nhạc không giới hạn với chất lượng cao.</p>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
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
                        <li class="menu-item" onclick="loadContent('approve_songs.php')">
                            <i class="fa-solid fa-circle-check"></i> Duyệt bài hát
                            <?php if ($pendingCount > 0): ?>
                                <span style="background: #ef4444; color: white; border-radius: 50%; min-width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; margin-left: auto;"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </li>
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
                        <li class="menu-item" onclick="loadContent('add_song.php')"><i class="fa-solid fa-cloud-arrow-up"></i> Thêm bài hát</li>
                    </ul>
                    <div class="divider"></div>
                    <ul class="menu-list">
                        <li class="menu-item" onclick="loadContent('chart_new_releases.php')"><i class="fa-solid fa-music"></i> BXH Nhạc Mới</li>
                        <li class="menu-item" onclick="loadContent('topic_genre.php')"><i class="fa-solid fa-icons"></i> Chủ Đề & Thể Loại</li>
                        <li class="menu-item" onclick="loadContent('top100.php')"><i class="fa-regular fa-star"></i> Top 100</li>
                    </ul>
                    
                    <div class="btn-create-playlist-sidebar" onclick="checkAndCreatePlaylist()">
                        <i class="fa-solid fa-plus"></i> Tạo playlist mới
                    </div>

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
                    
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                        <button class="btn-vip" onclick="loadContent('upgrade.php')">Nâng cấp tài khoản</button>
                    <?php endif; ?>
                    
                    <div class="settings-wrapper">
                        <button class="btn-setting" id="btn-open-settings"><i class="fa-solid fa-gear"></i></button>
                        
                        <div class="settings-menu" id="settings-dropdown">
                            <ul>
                                <li class="has-submenu">
                                    <i class="fa-regular fa-circle-play"></i> <span class="menu-text">Trình phát nhạc</span> <i class="fa-solid fa-chevron-right menu-arrow"></i>
                                    <div class="settings-submenu">
                                        <div class="submenu-banner"><span>PLUS</span> Nâng cấp Plus để trải nghiệm Gapless và các tính năng nâng cao khác</div>
                                        <div class="setting-group">
                                            <div class="setting-group-title">Chuyển bài <span class="badge-plus">PLUS</span></div>
                                            <div class="setting-row"><span>Chuyển bài mượt mà (Crossfade)</span><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                                            <div class="setting-row"><div>Bỏ qua khoảng lặng (Gapless) <span class="setting-row-desc">Loại bỏ đoạn im lặng khi chuyển bài hát</span></div><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                                        </div>
                                        <div class="setting-group">
                                            <div class="setting-group-title">Chất lượng nhạc</div>
                                            <label class="radio-row">Thường (128 kbps) <input type="radio" name="music_quality" checked></label>
                                            <label class="radio-row">Cao (320 kbps) <input type="radio" name="music_quality"></label>
                                            <label class="radio-row">Lossless <span class="badge-plus">PLUS</span> <input type="radio" name="music_quality"></label>
                                        </div>
                                        <div class="setting-group" style="margin-bottom: 0;">
                                            <div class="setting-group-title">Phát nhạc</div>
                                            <div class="setting-row" style="margin-bottom: 0;"><span>Luôn phát nhạc toàn màn hình</span><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
                                        </div>
                                    </div>
                                </li>

                                <li class="has-submenu">
                                    <i class="fa-solid fa-paint-roller"></i> <span class="menu-text">Giao diện</span> <i class="fa-solid fa-chevron-right menu-arrow"></i>
                                    <div class="settings-submenu" style="width: 250px;">
                                        <div class="setting-group">
                                            <div class="setting-group-title">Chủ đề <i class="fa-solid fa-chevron-right" style="margin-left: auto; font-size: 12px; color: gray;"></i></div>
                                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                                                <div style="width: 60px; height: 40px; border-radius: 5px; background: linear-gradient(135deg, #28104e, #9b4de0); border: 1px solid var(--purple-primary);"></div>
                                                <span style="color: white; font-weight: bold; font-size: 14px;">Tím</span>
                                            </div>
                                            <div class="settings-divider"></div>
                                            <div class="setting-row" style="margin-top: 15px; margin-bottom: 0;">
                                                <span>Hiệu ứng chuyển động</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                <div class="settings-divider"></div>
                                <li onclick="openHelpCenter('help-about')"><i class="fa-solid fa-circle-info"></i> <span class="menu-text">Giới thiệu</span></li>
                                <li onclick="openHelpCenter('help-terms')"><i class="fa-regular fa-file-lines"></i> <span class="menu-text">Thỏa thuận sử dụng dịch vụ</span> <i class="fa-solid fa-arrow-up-right-from-square menu-arrow"></i></li>
                                <li onclick="openHelpCenter('help-privacy')"><i class="fa-solid fa-shield-halved"></i> <span class="menu-text">Chính sách bảo mật</span> <i class="fa-solid fa-arrow-up-right-from-square menu-arrow"></i></li>
                                <li onclick="openHelpCenter('help-copyright')"><i class="fa-regular fa-flag"></i> <span class="menu-text">Báo cáo vi phạm bản quyền</span> <i class="fa-solid fa-arrow-up-right-from-square menu-arrow"></i></li>
                                <li onclick="openHelpCenter('help-ads')"><i class="fa-solid fa-ad"></i> <span class="menu-text">Quảng cáo</span> <i class="fa-solid fa-arrow-up-right-from-square menu-arrow"></i></li>
                                <li onclick="openHelpCenter('help-contact')"><i class="fa-solid fa-phone"></i> <span class="menu-text">Liên hệ</span> <i class="fa-solid fa-arrow-up-right-from-square menu-arrow"></i></li>
                            </ul>
                        </div>
                    </div>
                    
                    <button class="btn-avatar" onclick="toggleAvatarDropdown()">
                        <img src="<?php echo isset($avatarPath) ? $avatarPath : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>" id="headerAvatarImg" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='<?php echo isset($defaultAvatarPath) ? $defaultAvatarPath : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>'">
                    </button>
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
            <div id="userPlaylistsContainer" style="max-height: 250px; overflow-y: auto; text-align: left; margin-bottom: 20px; border-radius: 8px; background: rgba(0,0,0,0.2);"></div>
            <button onclick="closeAddToPlaylistModal()" style="width: 100%; background: transparent; padding: 10px; border-radius: 20px; color: white; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; font-weight: bold; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">Đóng</button>
        </div>
    </div>

    <div id="globalCreatePlModal" class="modal-overlay" style="z-index: 2500;">
        <div class="modal-content" style="width: 350px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px;">
            <h3 style="color: white; margin-bottom: 20px;">Tạo playlist mới</h3>
            <input type="text" id="globalPlTitle" placeholder="Nhập tên playlist..." style="width: 100%; padding: 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; margin-bottom: 20px; outline: none; text-align: center; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; gap: 10px;">
                <button onclick="document.getElementById('globalCreatePlModal').style.display='none'" style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: rgba(255,255,255,0.1); color: white; cursor: pointer; font-weight: 600;">Hủy</button>
                <button onclick="submitGlobalCreatePlaylist()" style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: var(--purple-primary); color: white; cursor: pointer; font-weight: 600;">TẠO MỚI</button>
            </div>
        </div>
    </div>
    
    <div id="adminCreatePlModal" class="modal-overlay" style="z-index: 2500;">
    <div class="modal-content" style="width: 350px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px;">
        <h3 style="color: white; margin-bottom: 20px; font-size: 18px;">Tạo playlist mẫu (Admin)</h3>
        
        <input type="text" id="adminPlTitleInput" placeholder="Nhập tên playlist mẫu..." 
               style="width: 100%; padding: 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; margin-bottom: 25px; outline: none; text-align: center; font-size: 14px;">
        
        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <button onclick="document.getElementById('adminCreatePlModal').style.display='none'" 
                    style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: rgba(255,255,255,0.1); color: white; cursor: pointer; font-weight: 600;">Hủy</button>
            
            <button onclick="submitAdminCreatePlaylist()" 
                    style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: var(--purple-primary); color: white; cursor: pointer; font-weight: 600;">TẠO MỚI</button>
        </div>
    </div>
</div>

    <div id="adminEditPlModal" class="modal-overlay" style="z-index: 2500;">
    <div class="modal-content" style="width: 350px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 25px;">
        <h3 style="color: white; margin-bottom: 20px; font-size: 18px;">Chỉnh sửa Playlist mẫu</h3>
        
        <input type="hidden" id="editPlId"> 
        
        <input type="text" id="editPlTitleInput" placeholder="Nhập tên mới..." 
               style="width: 100%; padding: 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: white; margin-bottom: 25px; outline: none; text-align: center; font-size: 14px;">
        
        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <button onclick="document.getElementById('adminEditPlModal').style.display='none'" 
                    style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: rgba(255,255,255,0.1); color: white; cursor: pointer; font-weight: 600;">Hủy</button>
            
            <button onclick="submitAdminEditPlaylist()" 
                    style="flex: 1; padding: 10px; border-radius: 20px; border: none; background: #3b82f6; color: white; cursor: pointer; font-weight: 600;">LƯU THAY ĐỔI</button>
        </div>
    </div>
</div>


    <script>
        function checkAndCreatePlaylist() {
            if (!isLoggedIn) {
                openLoginModal();
            } else {
                document.getElementById('globalCreatePlModal').style.display = 'flex';
                document.getElementById('globalPlTitle').focus();
            }
        }

        function submitGlobalCreatePlaylist() {
            const title = document.getElementById('globalPlTitle').value.trim();
            if(!title) { showGlobalNotify('Vui lòng nhập tên playlist!', false); return; }

            fetch('user_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=create_playlist&title=' + encodeURIComponent(title)
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('globalCreatePlModal').style.display = 'none';
                    document.getElementById('globalPlTitle').value = '';
                    showGlobalNotify('Đã tạo playlist thành công!', true);
                    loadContent('library.php');
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    const libItem = Array.from(document.querySelectorAll('.menu-item')).find(el => el.textContent.includes('Thư Viện'));
                    if(libItem) libItem.classList.add('active');
                } else {
                    showGlobalNotify(data.message || 'Có lỗi xảy ra!', false);
                }
            })
            .catch(err => { console.error(err); showGlobalNotify('Lỗi kết nối máy chủ!', false); });
        }

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
            role: '<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : ''; ?>' // Thêm dòng này để JS biết bạn là Admin
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
            if (!isLoggedIn) {
                openLoginModal();
                return;
            }
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

                    if (!isHistoryNav) {
                        pageHistory = pageHistory.slice(0, currentHistoryIndex + 1);
                        pageHistory.push(url);
                        currentHistoryIndex++;
                    }
                    updateNavButtons();
                })
                .catch(err => console.error('Lỗi load:', err));
        }

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

        function showGlobalNotify(message, isSuccess) {
            const notify = document.getElementById('globalNotify');
            if (!notify) return;
            
            notify.textContent = message;
            if (isSuccess) {
                notify.style.background = 'rgba(16, 185, 129, 0.95)';
                notify.style.border = '1px solid #10b981';
                notify.style.boxShadow = '0 10px 30px rgba(16, 185, 129, 0.2)';
            } else {
                notify.style.background = 'rgba(239, 68, 68, 0.95)';
                notify.style.border = '1px solid #ef4444';
                notify.style.boxShadow = '0 10px 30px rgba(239, 68, 68, 0.2)';
            }
            
            notify.style.opacity = '1';
            notify.style.transform = 'translateY(0)';
            
            setTimeout(() => {
                notify.style.opacity = '0';
                notify.style.transform = 'translateY(-20px)';
            }, 3000);
        }

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
                        showGlobalNotify(data.message, data.success); 
                        
                        if (data.success) {
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
            fetch('song_action.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'delete', songId: songId}) }).then(res => res.json()).then(data => { showGlobalNotify(data.message, data.success); if (data.success) loadContent('admin_songs.php'); });
        };

        window.deleteCategory = function(type, id) {
            if (!confirm('Bạn có chắc muốn xóa?')) return;
            fetch('category_action.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action: 'delete', type: type, id: id}) }).then(res => res.json()).then(data => { showGlobalNotify(data.message, data.success); if (data.success) { const reloadMap = { 'genre': 'admin_genres.php', 'artist': 'admin_artists.php', 'album': 'admin_albums.php', 'comment': 'admin_comments.php', 'banner': 'admin_banners.php' }; loadContent(reloadMap[type]); } });
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
            
            const btnSettings = document.getElementById('btn-open-settings');
            const menuSettings = document.getElementById('settings-dropdown');

            if (btnSettings && menuSettings) {
                btnSettings.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                    menuSettings.classList.toggle('show');
                    // Đóng menu avatar nếu đang mở
                    const avatarDropdown = document.getElementById('avatarDropdown');
                    if(avatarDropdown) avatarDropdown.classList.remove('show');
                });
            }

            // Script mở Trung tâm Hỗ trợ & Chuyển Tab
            window.openHelpCenter = function(tabId) {
                document.getElementById('settings-dropdown').classList.remove('show'); // Ẩn menu đi
                document.getElementById('helpCenterModal').style.display = 'flex'; // Mở full màn hình
                switchHelpTab(tabId); // Bật đúng tab được chọn
            };

            window.switchHelpTab = function(tabId) {
                // Xóa active tất cả các tab bên trái
                document.querySelectorAll('.help-tab-btn').forEach(btn => btn.classList.remove('active'));
                // Ẩn tất cả nội dung bên phải
                document.querySelectorAll('.help-panel').forEach(panel => panel.style.display = 'none');
                
                // Kích hoạt tab và nội dung tương ứng
                document.querySelector(`.help-tab-btn[data-target="${tabId}"]`).classList.add('active');
                document.getElementById(tabId).style.display = 'block';
            };

            window.closeHelpCenter = function() {
                document.getElementById('helpCenterModal').style.display = 'none';
            };

            // Cập nhật sự kiện click ra ngoài để đóng
            window.onclick = (e) => { 
                // Đóng các Modal văn bản (Modal Đăng nhập)
                if (e.target.classList.contains('modal-overlay')) {
                    e.target.style.display = 'none'; 
                }
                
                // Đóng menu avatar nếu click ra ngoài
                const avatarDropdown = document.getElementById('avatarDropdown');
                if (avatarDropdown && !e.target.closest('.btn-avatar') && !e.target.closest('#avatarDropdown')) {
                    avatarDropdown.classList.remove('show'); 
                }

                // Đóng menu Cài đặt nếu click ra ngoài
                if (menuSettings && menuSettings.classList.contains('show')) {
                    if (!menuSettings.contains(e.target) && !btnSettings.contains(e.target)) {
                        menuSettings.classList.remove('show');
                    }
                }
            };

            // XỬ LÝ VIỆC ĐỔI ẢNH ĐẠI DIỆN VÀ LƯU VÀO CSDL
            const changeAvatarInput = document.getElementById('changeAvatarInput');
            if (changeAvatarInput) {
                changeAvatarInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    if (file) {
                        const formData = new FormData();
                        formData.append('action', 'update_avatar');
                        formData.append('avatar', file);

                        fetch('user_action.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if(data.success) {
                                // Cập nhật ảnh ở cả 2 vị trí ngay lập tức mà không cần F5
                                document.getElementById('headerAvatarImg').src = data.avatar_url;
                                document.getElementById('dropdownAvatarImg').src = data.avatar_url;
                                showGlobalNotify('Cập nhật ảnh đại diện thành công!', true);
                            } else {
                                showGlobalNotify(data.message || 'Lỗi cập nhật ảnh!', false);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            showGlobalNotify('Lỗi kết nối máy chủ!', false);
                        });
                    }
                });
            }
            if (userInfo.role === 'admin') loadContent('admin_dashboard.php'); else loadContent('discover.php');
            const mainApp = document.querySelector('.app');
            mainApp.addEventListener('click', function(e) { const menuItem = e.target.closest('.menu-item'); if (menuItem) { document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active')); menuItem.classList.add('active'); } });
        });
        // 1. Hàm này chỉ để mở cái khung Modal lên
// --- THAY THẾ ĐOẠN TẠO PLAYLIST BẰNG ĐOẠN NÀY ---
window.openAdminCreatePlaylist = function() {
    console.log("Đang mở Modal tạo playlist mẫu...");
    const modal = document.getElementById('adminCreatePlModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('adminPlTitleInput').value = ''; 
        document.getElementById('adminPlTitleInput').focus();   
    } else {
        alert("Lỗi: Không tìm thấy khung adminCreatePlModal trong index.php! Vy kiểm tra lại dòng 666 nhé.");
    }
};

window.submitAdminCreatePlaylist = function() {
    const title = document.getElementById('adminPlTitleInput').value.trim();
    console.log("Tên playlist Admin nhập: " + title);
    
    if (title === "") {
        showGlobalNotify("Vui lòng nhập tên playlist!", false);
        return;
    }

    fetch('user_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=create_playlist&title=' + encodeURIComponent(title)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('adminCreatePlModal').style.display = 'none';
            showGlobalNotify(data.message, true);
            loadContent('admin_playlists.php'); 
        } else {
            showGlobalNotify(data.message, false);
        }
    })
    .catch(err => {
        console.error("Lỗi hệ thống:", err);
        showGlobalNotify("Lỗi kết nối máy chủ!", false);
    });
};
let currentEditId = 0;

function openAdminEditPlaylist(id, title) {
    currentEditId = id;
    document.getElementById('adminEditPlModal').style.display = 'flex';
    document.getElementById('editPlTitleInput').value = title;
    document.getElementById('editPlTitleInput').focus();
}

function submitAdminEditPlaylist() {
    const newTitle = document.getElementById('editPlTitleInput').value.trim();
    if (newTitle === "") {
        showGlobalNotify("Tên không được để trống!", false);
        return;
    }

    fetch('user_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=edit_playlist&playlist_id=${currentEditId}&title=${encodeURIComponent(newTitle)}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('adminEditPlModal').style.display = 'none';
            showGlobalNotify(data.message, true);
            loadContent('admin_playlists.php'); // Tải lại bảng
        } else {
            showGlobalNotify(data.message, false);
        }
    });
}
    </script>
    <script src="player.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

    <div id="helpCenterModal" class="help-modal-overlay">
        <div class="help-container">
            <div class="help-sidebar">
                <div class="help-tab-btn" data-target="help-about" onclick="switchHelpTab('help-about')">Giới thiệu</div>
                <div class="help-tab-btn" data-target="help-terms" onclick="switchHelpTab('help-terms')">Thỏa thuận sử dụng</div>
                <div class="help-tab-btn" data-target="help-privacy" onclick="switchHelpTab('help-privacy')">Chính sách bảo mật</div>
                <div class="help-tab-btn" data-target="help-copyright" onclick="switchHelpTab('help-copyright')">Bản quyền</div>
                <div class="help-tab-btn" data-target="help-ads" onclick="switchHelpTab('help-ads')">Quảng cáo</div>
                <div class="help-tab-btn" data-target="help-contact" onclick="switchHelpTab('help-contact')">Liên hệ</div>
            </div>

            <div class="help-content-area">
                <span class="help-close-btn" onclick="closeHelpCenter()">&times;</span>

                <div id="help-about" class="help-panel">
                    <h2 style="font-size: 32px; margin-bottom: 20px; font-weight: 800;">Giới thiệu</h2>
                    <p style="line-height: 1.8; font-size: 15px; color: #444; text-align: justify; margin-bottom: 15px;">
                        Được khai sinh vào ngày 1/8/2007, Lyrx Music là dịch vụ nghe nhạc trực tuyến được yêu thích nhất Việt Nam hiện nay. Với nhiều tính năng hữu ích giúp người nghe luôn có trải nghiệm âm nhạc tuyệt vời và xuyên suốt trên các thiết bị của mình (từ PC, điện thoại, máy tính bảng đến Smart TV). Lyrx Music mang đến cho người yêu nhạc thư viện nhạc khổng lồ với hàng chục triệu bài hát chất lượng cao có bản quyền đầy đủ tất cả các thể loại và được cập nhật liên tục nội dung mới nhất mỗi ngày.
                    </p>
                    <p style="line-height: 1.8; font-size: 15px; color: #444; text-align: justify; margin-bottom: 15px;">
                        Thành viên của Lyrx Music có thể tự tổ chức thư viện nhạc cá nhân cho riêng mình, upload và lưu trữ kho nhạc của mình ngay trên Lyrx Music và tạo playlist để nghe và chia sẻ cho bạn bè rất dễ dàng. Lyrx Music hiện đã có đầy đủ các phiên bản và ứng dụng dành cho các nền tảng mobile (iOS, Android, Windows...) và Smart TV.
                    </p>
                    <p style="line-height: 1.8; font-size: 15px; color: #444; text-align: justify; font-weight: bold;">
                        Lyrx Music là một sản phẩm của Tập đoàn VNG.
                    </p>
                </div>

                <div id="help-terms" class="help-panel">
                    <h2 style="font-size: 28px; margin-bottom: 20px; font-weight: 700; color: #333;">Thỏa Thuận Cung Cấp Và Sử Dụng Dịch Vụ</h2>
                    <div style="font-size: 15px; line-height: 1.8; color: #444; text-align: justify; max-width: 900px;">
                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 1: Giải thích từ ngữ</h3>
                        <p style="margin-bottom: 12px;"><b>Lyrx Music:</b> là dịch vụ mạng xã hội do Công ty Cổ phần Tập đoàn VNG là chủ quản có thể truy cập qua website, ứng dụng hoặc bất kỳ cách truy cập khả dụng nào khác.</p>
                        <p style="margin-bottom: 12px;"><b>Thỏa Thuận:</b> là thỏa thuận cung cấp và sử dụng dịch vụ mạng xã hội, cùng với tất cả các bản sửa đổi, bổ sung, cập nhật.</p>
                        <p style="margin-bottom: 12px;"><b>Thông Tin Cá Nhân:</b> là thông tin gắn liền với việc xác định danh tính, nhân thân của cá nhân bao gồm tên, tuổi, địa chỉ, số chứng minh nhân dân, số điện thoại, địa chỉ thư điện tử, tài khoản ngân hàng của Người Sử Dụng và một số thông tin khác theo quy định của pháp luật.</p>
                        <p style="margin-bottom: 12px;"><b>Lyrx ID:</b> là tài khoản để Người Sử Dụng đăng nhập, upload nội dung lên và sử dụng các tính năng nâng cao khác.</p>
                        
                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 2: Nội dung dịch vụ</h3>
                        <p style="margin-bottom: 12px;">Lyrx Music là mạng xã hội chia sẻ thông tin về âm nhạc, cho phép nghe nhạc trực tuyến, xem video clip, music video (MV) bao gồm nhiều thể loại khác nhau và/hoặc những nội dung khác được Người Sử Dụng đăng tải.</p>
                        <p style="margin-bottom: 12px;">Thông qua mạng xã hội, chủ thể bản quyền có thể để đăng tải bài hát / video clip, MV chất lượng để truyền đạt tới Người Sử Dụng.</p>
                        <p style="margin-bottom: 12px;">Mạng xã hội cho phép Người Sử Dụng trao đổi, thảo luận và phản hồi thông qua công cụ bình luận bằng kí tự chữ về những nội dung được cung cấp.</p>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 3: Chấp nhận điều khoản sử dụng và sửa đổi</h3>
                        <p style="margin-bottom: 12px;">Khi sử dụng Dịch vụ, Người Sử Dụng mặc định phải đồng ý và tuân theo các điều khoản được quy định tại Thỏa Thuận này và quy định, quy chế mà hệ thống liên kết, tích hợp.</p>
                        <p style="margin-bottom: 12px;">Để đáp ứng nhu cầu sử dụng của Người Sử Dụng, hệ thống không ngừng hoàn thiện và phát triển, vì vậy các điều khoản quy định tại Thỏa thuận này có thể được cập nhật, chỉnh sửa bất cứ lúc nào mà không cần phải thông báo trước tới Người Sử Dụng. Hệ thống sẽ công bố rõ trên Website về những thay đổi, bổ sung đó.</p>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 4: Các nội dung cấm trao đổi và chia sẻ</h3>
                        <p style="margin-bottom: 12px;">Khi sử dụng sản phẩm, nghiêm cấm khách hàng một số hành vi bao gồm nhưng không giới hạn sau:</p>
                        <ul style="margin-left: 25px; margin-bottom: 12px;">
                            <li style="margin-bottom: 8px;">Lợi dụng việc cung cấp, trao đổi, sử dụng thông tin nhằm mục đích: Chống lại Nhà nước; gây phương hại đến an ninh quốc gia; Tuyên truyền, kích động bạo lực, dâm ô, đồi trụy. Tuyệt đối không bàn luận, đăng tải các nội dung về các vấn đề chính trị.</li>
                            <li style="margin-bottom: 8px;">Tiết lộ bí mật nhà nước, bí mật quân sự, an ninh, kinh tế.</li>
                            <li style="margin-bottom: 8px;">Khi giao tiếp với người dùng khác, quấy rối, chửi bới, làm phiền hay có bất kỳ hành vi thiếu văn hoá.</li>
                            <li style="margin-bottom: 8px;">Đưa thông tin xuyên tạc, vu khống, nhạo báng, xúc phạm uy tín tới tổ chức, cá nhân dưới bất kỳ hình thức nào.</li>
                            <li style="margin-bottom: 8px;">Xâm phạm bản quyền, sao chép, tải về, phân phối nội dung khi chưa được sự đồng ý của chủ sở hữu.</li>
                        </ul>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 5: Sử dụng dịch vụ tính phí</h3>
                        <p style="margin-bottom: 12px;">Hệ thống cung cấp các gói dịch vụ tính phí (“Gói VIP”) cho phép người sử dụng tiếp cận các tính năng ưu đãi vượt trội so với dịch vụ miễn phí thông thường. Phí công bố cho từng Gói VIP đã bao gồm toàn bộ các loại thuế, lệ phí. Ngay khi khoản thanh toán được chấp thuận thì Gói VIP tương ứng được kích hoạt.</p>
                        <p style="margin-bottom: 12px;">Đối với một số Gói VIP được áp dụng tính năng tự động gia hạn, trong vòng 24 giờ trước khi hết thời hạn sử dụng, tính năng này sẽ tự kích hoạt thêm 1 chu kỳ sử dụng nữa.</p>
                    </div>
                </div>

                <div id="help-privacy" class="help-panel">
                    <h2 style="font-size: 28px; margin-bottom: 20px; font-weight: 700; color: #333;">Chính Sách Bảo Mật Thông Tin</h2>
                    <div style="font-size: 15px; line-height: 1.8; color: #444; text-align: justify; max-width: 900px;">
                        <p style="margin-bottom: 12px;">Chúng tôi luôn cam kết bảo mật những thông tin, dữ liệu cá nhân của Khách hàng một cách tốt nhất theo quy định của pháp luật. Vì vậy, Chính sách Bảo vệ Dữ liệu cá nhân ("Chính sách") này được xây dựng để Khách hàng hiểu rõ hơn về mục đích, phạm vi thông tin mà chúng tôi xử lý dữ liệu cá nhân.</p>
                        <p style="margin-bottom: 12px;">Chính sách này là một phần không thể tách rời của bản Hợp đồng, các Điều khoản và Điều kiện sử dụng Dịch vụ cung cấp tới Khách hàng.</p>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 1: Định nghĩa</h3>
                        <ul style="margin-left: 25px; margin-bottom: 12px;">
                            <li style="margin-bottom: 8px;"><b>Khách hàng:</b> là Khách hàng cá nhân đăng ký, sử dụng dịch vụ.</li>
                            <li style="margin-bottom: 8px;"><b>Dữ liệu cá nhân:</b> là thông tin dưới dạng ký hiệu, chữ viết, chữ số, hình ảnh, âm thanh... gắn liền với một con người cụ thể.</li>
                            <li style="margin-bottom: 8px;"><b>Xử lý dữ liệu cá nhân:</b> là một hoặc nhiều hoạt động tác động tới dữ liệu như: thu thập, ghi, phân tích, xác nhận, lưu trữ, chỉnh sửa, xóa, hủy dữ liệu.</li>
                        </ul>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 2: Loại Dữ liệu được xử lý</h3>
                        <p style="margin-bottom: 12px;">Các Dữ liệu cá nhân của Khách hàng có thể được thu thập và xử lý bao gồm:</p>
                        <ul style="margin-left: 25px; margin-bottom: 12px;">
                            <li style="margin-bottom: 8px;">Thông tin cá nhân: họ tên, số điện thoại, ngày tháng năm sinh... để liên lạc và khôi phục tài khoản.</li>
                            <li style="margin-bottom: 8px;">Tên tài khoản, ảnh đại diện.</li>
                            <li style="margin-bottom: 8px;">Thông tin về ứng dụng, trình duyệt và thiết bị sử dụng (IP, địa chỉ Wifi MAC, hệ điều hành).</li>
                            <li style="margin-bottom: 8px;">Thông tin được bạn chia sẻ để tối ưu hóa nội dung hiển thị nhằm phục vụ tốt hơn.</li>
                        </ul>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 3: Mục đích Xử lý dữ liệu cá nhân</h3>
                        <p style="margin-bottom: 12px;">Khách hàng đồng ý cho phép Xử lý dữ liệu cá nhân với các mục đích như sau:</p>
                        <ul style="margin-left: 25px; margin-bottom: 12px;">
                            <li style="margin-bottom: 8px;">Để quản lý, điều hành, cung cấp Dịch vụ và tài khoản người dùng của Bạn.</li>
                            <li style="margin-bottom: 8px;">Để giải quyết hoặc tạo điều kiện dịch vụ khách hàng, trả lời thắc mắc.</li>
                            <li style="margin-bottom: 8px;">Để tiến hành các hoạt động nghiên cứu, phân tích và phát triển, cải thiện trải nghiệm khách hàng.</li>
                            <li style="margin-bottom: 8px;">Vì mục đích tiếp thị, gửi thông tin và tài liệu quảng bá liên quan đến các sản phẩm/dịch vụ.</li>
                            <li style="margin-bottom: 8px;">Để ngăn chặn hoặc điều tra hành vi vi phạm Điều Khoản Dịch Vụ, hoạt động gian lận, phi pháp.</li>
                        </ul>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 4: Quyền của Khách hàng</h3>
                        <p style="margin-bottom: 12px;">Khách hàng là chủ thể dữ liệu cá nhân của mình và có các quyền:</p>
                        <ul style="margin-left: 25px; margin-bottom: 12px;">
                            <li style="margin-bottom: 8px;">Quyền được biết về hoạt động xử lý dữ liệu.</li>
                            <li style="margin-bottom: 8px;">Quyền truy cập để xem, chỉnh sửa hoặc yêu cầu xóa Dữ liệu cá nhân của mình.</li>
                            <li style="margin-bottom: 8px;">Quyền rút lại sự đồng ý tại bất kỳ thời điểm nào.</li>
                        </ul>

                        <h3 style="font-size: 18px; margin-top: 25px; margin-bottom: 10px; font-weight: 700; color: var(--purple-primary);">Điều 5: Liên hệ</h3>
                        <p style="margin-bottom: 12px;">Nếu Bạn có bất kỳ thắc mắc hoặc câu hỏi nào về Chính Sách này, vui lòng liên hệ với Chúng tôi tại địa chỉ Email hỗ trợ khách hàng của hệ thống: <b>hotro@lyrxmusic.com.vn</b>.</p>
                    </div>
                </div>

                <div id="help-copyright" class="help-panel">
                    <h2 style="font-size: 28px; margin-bottom: 20px; font-weight: 700;">Bản quyền</h2>
                    <h3 style="font-size: 18px; margin-bottom: 10px; font-weight: 700;">Quy trình báo cáo vi phạm bản quyền</h3>
                    <p style="line-height: 1.6; color: #555; margin-bottom: 15px;">Nếu bạn tin rằng bất kỳ nội dung nào đang được phát hành thông qua Dịch vụ Lyrx Music, vi phạm quyền sở hữu trí tuệ của bạn và/hoặc của bất kỳ bên thứ ba nào, vui lòng báo cáo cho chúng tôi về việc vi phạm bản quyền theo đúng yêu cầu dưới đây.</p>
                    <p style="line-height: 1.6; color: #555; margin-bottom: 40px;">Chúng tôi sẽ xử lý từng thông báo vi phạm bản quyền mà chúng tôi nhận được theo quy định của Điều khoản sử dụng của Lyrx Music và quy định của pháp luật sở hữu trí tuệ và thông báo đến bạn kết quả giải quyết.</p>

                    <h4 style="font-size: 16px; margin-bottom: 20px; font-weight: bold;">BÁO CÁO VI PHẠM BẢN QUYỀN</h4>
                    <div class="help-form-group"><label>Họ tên *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Số điện thoại *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Email *</label><input type="email" class="help-form-control"></div>
                    <div class="help-form-group"><label>Địa chỉ liên lạc *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Số CCCD / CMND / Hộ chiếu *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Ngày cấp *</label><input type="text" class="help-form-control" placeholder="dd/mm/yyyy"></div>
                    <div class="help-form-group"><label>Nơi cấp *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Link nội dung vi phạm *</label><input type="text" class="help-form-control"></div>
                    <div class="help-form-group"><label>Mô tả *</label><textarea class="help-form-control"></textarea></div>
                    
                    <div class="help-form-group">
                        <label></label>
                        <div style="flex: 1;">
                            <div class="upload-box"><i class="fa-solid fa-cloud-arrow-up" style="font-size: 24px; margin-bottom: 10px;"></i><br>Đính kèm tài liệu...<br><small style="color: gray;">* Vui lòng cung cấp giấy tờ chứng minh chủ thể quyền</small></div>
                            
                            <div style="margin-top: 20px;">
                                <label style="display: flex; gap: 10px; align-items: flex-start; font-size: 14px; color: #555; width: auto; font-weight: normal; margin-bottom: 10px; cursor: pointer;">
                                    <input type="checkbox" style="margin-top: 4px;"> Bạn tuyên bố và đảm bảo rằng nội dung báo cáo vi phạm bản quyền đang được sử dụng không được phép của chủ thể quyền.
                                </label>
                                <label style="display: flex; gap: 10px; align-items: flex-start; font-size: 14px; color: #555; width: auto; font-weight: normal; cursor: pointer;">
                                    <input type="checkbox" style="margin-top: 4px;"> Bạn tuyên bố và đảm bảo rằng tất cả các thông tin được khai báo... hợp pháp và bạn sẵn sàng chịu mọi trách nhiệm pháp lý.
                                </label>
                            </div>
                            <button class="help-submit-btn" style="margin-top: 30px;">Gửi Báo Cáo</button>
                        </div>
                    </div>
                </div>

                <div id="help-ads" class="help-panel">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <h2 style="font-size: 32px; font-weight: 800; color: #0052cc;">Lyrx<span style="color:#00a3ff;">Ads</span></h2>
                        <span style="background: #e6f0ff; color: #0052cc; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Leader in mobile</span>
                    </div>
                    <h3 style="font-size: 24px; margin-bottom: 15px; font-weight: 700; color: #333;">Liên hệ quảng cáo</h3>
                    <p style="line-height: 1.6; color: #555; margin-bottom: 40px;">Khai thác các nền tảng quảng cáo, truyền thông số và giải trí hàng đầu Việt Nam. Chúng tôi giúp bạn tăng khả năng nhận diện thương hiệu và rút ngắn hành trình chinh phục khách hàng. Điền thông tin của bạn ngay để nhận tư vấn miễn phí!</p>

                    <div style="max-width: 700px;">
                        <div class="help-form-group"><label style="width: 180px;">Họ và tên *</label><input type="text" class="help-form-control"></div>
                        <div class="help-form-group"><label style="width: 180px;">Số điện thoại *</label><input type="text" class="help-form-control"></div>
                        <div class="help-form-group"><label style="width: 180px;">Email *</label><input type="email" class="help-form-control"></div>
                        <div class="help-form-group"><label style="width: 180px;">Tên công ty *</label><input type="text" class="help-form-control"></div>
                        <div class="help-form-group"><label style="width: 180px;">Chức vụ *</label><input type="text" class="help-form-control"></div>
                        <div class="help-form-group"><label style="width: 180px;">Mục tiêu kinh doanh *</label>
                            <select class="help-form-control">
                                <option>Vui lòng chọn mục tiêu kinh doanh bạn quan tâm</option>
                                <option>Tăng nhận diện thương hiệu</option>
                                <option>Tìm kiếm khách hàng mới</option>
                            </select>
                        </div>
                        <div class="help-form-group">
                            <label style="width: 180px;"></label>
                            <div style="flex: 1;">
                                <label style="display: flex; gap: 10px; align-items: center; font-size: 14px; color: #555; width: auto; font-weight: normal; margin-bottom: 20px; cursor: pointer;">
                                    <input type="checkbox"> Tôi đã đọc và đồng ý với <a href="#" style="color: #0052cc; text-decoration: none;">Điều khoản sử dụng</a> và <a href="#" style="color: #0052cc; text-decoration: none;">Chính sách bảo mật</a>
                                </label>
                                <button class="help-submit-btn" style="background: #0052cc;">Nhận tư vấn ngay</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="help-contact" class="help-panel">
                    <div style="text-align: center; margin-bottom: 40px;">
                        <div style="width: 150px; height: 150px; background: #f0e6f9; border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 20px auto;">
                            <i class="fa-solid fa-at" style="font-size: 80px; color: var(--purple-primary);"></i>
                        </div>
                        <h3 style="font-size: 20px; font-weight: bold; text-transform: uppercase;">Liên hệ với chúng tôi</h3>
                        <p style="color: #555; max-width: 600px; margin: 15px auto; line-height: 1.6;">Chúng tôi luôn ghi nhận các đóng góp ý kiến của bạn để cải tiến và nâng cấp sản phẩm Lyrx Music ngày một hoàn thiện và hữu ích hơn. Đừng ngại chia sẻ ý tưởng cho chúng tôi.</p>
                    </div>

                    <div style="max-width: 600px; margin: 0 auto;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px;">Chọn vấn đề bạn đang cần hỗ trợ: *</label>
                            <select class="help-form-control">
                                <option>Chọn vấn đề cần liên hệ</option>
                                <option>Lỗi tài khoản VIP</option>
                                <option>Lỗi phát nhạc / Giao diện</option>
                                <option>Góp ý tính năng mới</option>
                            </select>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px;">Nội dung: *</label>
                            <textarea class="help-form-control" placeholder="Nhập nội dung cần giúp đỡ"></textarea>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px;">Họ tên: *</label>
                            <input type="text" class="help-form-control">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px;">Email: *</label>
                            <input type="email" class="help-form-control">
                        </div>
                        <div style="margin-bottom: 30px;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; font-size: 14px;">Số điện thoại: *</label>
                            <input type="text" class="help-form-control">
                        </div>
                        <button class="help-submit-btn" style="width: 100%; padding: 15px; font-size: 16px;">GỬI</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>