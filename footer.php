<?php
// 1. Logic xử lý dữ liệu đưa lên đầu trang
$connFoot = new mysqli("localhost", "root", "vertrigo", "song_management");
$connFoot->set_charset('utf8mb4');

// Lấy thông tin footer từ database
$resFoot = $connFoot->query("SELECT ConfigValue FROM settings WHERE ConfigKey = 'footer_info'");
$footerText = ($resFoot && $rowF = $resFoot->fetch_assoc()) ? $rowF['ConfigValue'] : '© 2026 Lyrx Music';

// Đóng kết nối ngay sau khi lấy xong dữ liệu cho sạch sẽ
$connFoot->close();
?>

<div style="margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; flex-wrap: wrap; gap: 30px; color: var(--text-secondary); font-size: 13px; line-height: 1.8; padding-bottom: 40px;">
    
    <div style="flex: 1; min-width: 200px;">
        <div class="brand-logo" style="margin-bottom: 10px; display: flex; align-items: baseline; gap: 2px; user-select: none;">
            <span style="font-size: 28px; font-weight: 900; color: white; letter-spacing: -1px;">lyrx</span>
            <span style="color: var(--purple-primary); font-size: 35px; line-height: 0;">.</span>
            <span style="font-size: 11px; font-weight: 700; color: var(--purple-primary); text-transform: uppercase; letter-spacing: 2px;">music</span>
        </div>
    </div>

    <div style="flex: 2; min-width: 250px;">
        <h4 style="color: white; font-size: 14px; margin-bottom: 10px; font-weight: 600;">Doanh nghiệp quản lý</h4>
        <p style="margin: 0;">Dự án Lyrx Music. GCN ĐKDN: 0000000000 do sở KH & ĐT cấp ngày 12/04/2026.<br>
        Địa chỉ: Phường Đông Xuyên, TP. Long Xuyên, Tỉnh An Giang, Việt Nam.</p>
        
        <h4 style="color: white; font-size: 14px; margin-bottom: 5px; margin-top: 20px; font-weight: 600;">Người chịu trách nhiệm nội dung</h4>
        <p style="margin: 0;">Vũ Thị Yến Vy - Phạm Thanh Tân</p>
    </div>

    <div style="flex: 2; min-width: 250px;">
        <h4 style="color: white; font-size: 14px; margin-bottom: 10px; font-weight: 600;">Thông tin dịch vụ</h4>
        <p style="margin: 0;">
            <?php echo $footerText; ?>
        </p>
    </div>
</div>