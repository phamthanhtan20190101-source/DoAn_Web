<?php session_start(); ?>
<style>
    .upgrade-wrapper { padding: 40px; color: white; background: radial-gradient(circle at 50% 0%, #2e1a47 0%, var(--bg-body) 60%); min-height: 100vh; border-radius: 12px; }
    
    .upgrade-hero { text-align: center; margin-bottom: 60px; }
    .upgrade-hero h1 { font-size: 46px; font-weight: 800; margin-bottom: 15px; letter-spacing: -1px; }
    .upgrade-hero p { font-size: 16px; color: var(--text-secondary); margin-bottom: 40px; }
    
    .vip-main-card {
        background: linear-gradient(145deg, #33210b, #1f1704);
        border: 1px solid #eab308; border-radius: 16px; max-width: 500px; margin: 0 auto; padding: 40px; text-align: left;
        box-shadow: 0 20px 50px rgba(234, 179, 8, 0.15); transition: transform 0.3s;
    }
    .vip-main-card:hover { transform: translateY(-5px); }
    .vip-card-header { border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
    .vip-title { font-size: 28px; font-weight: 900; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 10px; }
    .vip-badge { background-color: #eab308; color: #170f23; font-size: 14px; padding: 4px 10px; border-radius: 6px; letter-spacing: 1px; font-weight: 800; }
    .vip-price-text { font-size: 18px; font-weight: 700; color: white; }
    
    .vip-btn {
        display: block; width: 100%; background-color: #eab308; color: #170f23; text-align: center;
        padding: 15px; border-radius: 30px; font-size: 16px; font-weight: 800; cursor: pointer; border: none;
        margin-bottom: 30px; text-transform: uppercase; transition: 0.3s;
    }
    .vip-btn:hover { background-color: #facc15; transform: scale(1.02); }
    
    .vip-features-list { list-style: none; padding: 0; }
    .vip-features-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; color: #e5e7eb; font-size: 15px; }
    .vip-features-list i { color: #eab308; font-size: 14px; }

    .features-grid { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; max-width: 1200px; margin: 0 auto 60px; }
    .feature-card { width: calc(25% - 15px); min-width: 250px; background: linear-gradient(180deg, #2a2a2a 0%, #1a1a1a 100%); border: 1px solid rgba(234, 179, 8, 0.4); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; text-align: left; transition: transform 0.3s; position: relative; }
    .feature-card:hover { transform: translateY(-5px); border-color: rgba(234, 179, 8, 0.8); }
    .feature-img-wrapper { height: 180px; width: 100%; position: relative; background: linear-gradient(135deg, #4c2f72, #2a1b3d); display: flex; justify-content: center; align-items: center; }
    .feature-gradient-overlay { position: absolute; bottom: 0; left: 0; width: 100%; height: 80px; background: linear-gradient(to bottom, rgba(26,26,26,0) 0%, rgba(26,26,26,1) 100%); }
    .feature-content { padding: 20px; background-color: #1a1a1a; flex-grow: 1; z-index: 2; position: relative; }
    .feature-title { font-size: 18px; font-weight: 700; color: white; margin-bottom: 12px; line-height: 1.4; }
    .feature-desc { font-size: 14px; color: var(--text-secondary); line-height: 1.6; }
</style>

<div class="upgrade-wrapper">
    <div class="upgrade-hero">
        <h1>Âm nhạc không giới hạn</h1>
        <p>Nâng cấp tài khoản để trải nghiệm các tính năng và nội dung cao cấp</p>
        
        <div class="vip-main-card">
            <div class="vip-card-header">
                <div class="vip-title">Lyrx <span class="vip-badge">VIP</span></div>
                <div class="vip-price-text">Chỉ từ 41.000đ/tháng</div>
            </div>
            
            <button class="vip-btn" onclick="loadContent('payment.php')">Đăng ký gói</button>
            
            <div style="font-weight: 700; margin-bottom: 15px; color: white;">Đặc quyền đặc biệt:</div>
            <ul class="vip-features-list">
                <li><i class="fa-solid fa-check"></i> Kho nhạc VIP độc quyền</li>
                <li><i class="fa-solid fa-check"></i> Nghe nhạc không quảng cáo</li>
                <li><i class="fa-solid fa-check"></i> Nghe và tải nhạc Lossless</li>
                <li><i class="fa-solid fa-check"></i> Lưu trữ nhạc không giới hạn</li>
                <li><i class="fa-solid fa-check"></i> Tính năng nghe nhạc nâng cao</li>
                <li><i class="fa-solid fa-check"></i> Mở rộng khả năng Upload</li>
            </ul>
        </div>
    </div>

    <h2 style="text-align: center; font-size: 32px; font-weight: 800; margin-bottom: 40px; color: white;">Đặc quyền khi nâng cấp</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-img-wrapper"><i class="fa-solid fa-compact-disc" style="font-size: 60px; color: rgba(255,255,255,0.2);"></i><div class="feature-gradient-overlay"></div></div>
            <div class="feature-content">
                <h3 class="feature-title">Kho nhạc VIP</h3>
                <p class="feature-desc">Nghe và tải các bài hát dành riêng cho thành viên VIP</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-img-wrapper"><i class="fa-solid fa-ban" style="font-size: 60px; color: rgba(255,255,255,0.2);"></i><div class="feature-gradient-overlay"></div></div>
            <div class="feature-content">
                <h3 class="feature-title">Không quảng cáo</h3>
                <p class="feature-desc">Trải nghiệm nghe nhạc xuyên suốt, không bị làm phiền bởi quảng cáo</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-img-wrapper"><i class="fa-solid fa-headphones-simple" style="font-size: 60px; color: rgba(255,255,255,0.2);"></i><div class="feature-gradient-overlay"></div></div>
            <div class="feature-content">
                <h3 class="feature-title">Nhạc Lossless</h3>
                <p class="feature-desc">Chất lượng âm thanh Lossless giúp phát huy hết khả năng của Loa và Tai Nghe</p>
            </div>
        </div>
        <div class="feature-card">
            <div class="feature-img-wrapper"><i class="fa-solid fa-cloud-arrow-down" style="font-size: 60px; color: rgba(255,255,255,0.2);"></i><div class="feature-gradient-overlay"></div></div>
            <div class="feature-content">
                <h3 class="feature-title">Lưu trữ thả ga</h3>
                <p class="feature-desc">Không giới hạn số lượng bài hát tải về thiết bị</p>
            </div>
        </div>
    </div>
    
    <?php @include 'footer.php'; ?>
</div>