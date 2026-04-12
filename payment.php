<?php
session_start();
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Khách';
$defaultAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=9b4de0&color=fff';
$avatar = (!empty($_SESSION['avatar_path'])) ? $_SESSION['avatar_path'] : $defaultAvatar;
?>
<style>
    .payment-wrapper { padding: 20px; color: white; border-radius: 12px; }
    
    .payment-header { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; }
    .payment-header h1 { font-size: 32px; font-weight: 900; color: #eab308; display: flex; align-items: center; gap: 8px; margin: 0; }
    .payment-header .vip-badge { background-color: #eab308; color: #170f23; font-size: 16px; padding: 4px 12px; border-radius: 8px; font-weight: 800; }

    .payment-layout { display: flex; gap: 30px; align-items: flex-start; max-width: 1100px; margin: 0 auto; }
    
    .packages-col { flex: 1; background: #231b2e; padding: 25px; border-radius: 12px; }
    .packages-col h2 { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: white; }
    
    .plan-option { 
        display: flex; justify-content: space-between; align-items: center; 
        background: #170f23; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; 
        padding: 20px; margin-bottom: 15px; cursor: pointer; transition: 0.2s;
    }
    .plan-option:hover { background: #1c132a; }
    .plan-option.active { border-color: #eab308; background: rgba(234, 179, 8, 0.05); }
    
    .plan-info h3 { font-size: 15px; color: #eab308; margin-bottom: 5px; font-weight: 700; }
    .plan-price-main { font-size: 24px; font-weight: 800; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
    .plan-badge { font-size: 11px; background: #b45309; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; }
    .plan-desc { font-size: 13px; color: var(--text-secondary); }
    
    .custom-radio { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #666; display: flex; align-items: center; justify-content: center; }
    .plan-option.active .custom-radio { border-color: #eab308; }
    .plan-option.active .custom-radio::after { content: ''; width: 10px; height: 10px; background: #eab308; border-radius: 50%; }

    .summary-col { width: 380px; display: flex; flex-direction: column; gap: 20px; }
    .summary-box, .benefits-box { background: #231b2e; padding: 25px; border-radius: 12px; }
    
    .user-profile { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
    .user-profile img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
    .user-profile span { font-size: 16px; font-weight: 700; color: white; }

    .summary-row { display: flex; justify-content: space-between; font-size: 14px; color: var(--text-secondary); margin-bottom: 15px; }
    .summary-row span.val { color: white; font-weight: 600; }
    
    .total-price-box { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: 10px; margin-bottom: 20px; }
    .total-price-box div { font-size: 13px; color: var(--text-secondary); margin-bottom: 5px; }
    .total-price-val { font-size: 32px; font-weight: 900; color: #eab308; }

    .terms-check { display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: var(--text-secondary); margin-bottom: 20px; line-height: 1.5; cursor: pointer; }
    .terms-check input { margin-top: 3px; accent-color: #eab308; }
    .terms-check b { color: white; }

    .btn-checkout { width: 100%; padding: 15px; background: #eab308; color: #170f23; border: none; border-radius: 30px; font-size: 15px; font-weight: 800; cursor: pointer; transition: 0.3s; }
    .btn-checkout:hover { background: #facc15; }

    .benefits-box h3 { font-size: 16px; font-weight: 700; color: white; margin-bottom: 20px; }
    .benefits-list { list-style: none; padding: 0; }
    .benefits-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 15px; color: #e5e7eb; font-size: 14px; }
    .benefits-list i { color: #eab308; font-size: 14px; }
</style>

<div class="payment-wrapper">
    <div class="payment-header">
        <i class="fa-solid fa-arrow-left" style="font-size: 24px; cursor: pointer; margin-right: 15px;" onclick="loadContent('upgrade.php')"></i>
        <h1>Lyrx <span class="vip-badge">VIP</span></h1>
    </div>

    <div class="payment-layout">
        <div class="packages-col">
            <h2>Chọn gói nâng cấp</h2>
            
            <div class="plan-option active" data-price="499.000" data-months="12" onclick="window.selectPlan(this)">
                <div class="plan-info">
                    <h3>12 tháng</h3>
                    <div class="plan-price-main">499.000đ <span class="plan-badge">Tiết kiệm 15%</span></div>
                    <div class="plan-desc">Chỉ 41.000đ/tháng</div>
                </div>
                <div class="custom-radio"></div>
            </div>

            <div class="plan-option" data-price="279.000" data-months="6" onclick="window.selectPlan(this)">
                <div class="plan-info">
                    <h3>6 tháng</h3>
                    <div class="plan-price-main">279.000đ <span class="plan-badge">Tiết kiệm 5%</span></div>
                    <div class="plan-desc">Chỉ 46.000đ/tháng</div>
                </div>
                <div class="custom-radio"></div>
            </div>

            <div class="plan-option" data-price="49.000" data-months="1" onclick="window.selectPlan(this)">
                <div class="plan-info">
                    <h3>1 tháng</h3>
                    <div class="plan-price-main">49.000đ</div>
                    <div class="plan-desc">Gói tiêu chuẩn</div>
                </div>
                <div class="custom-radio"></div>
            </div>
        </div>

        <div class="summary-col">
            <div class="summary-box">
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Thời điểm nâng cấp</span>
                    <span class="val" id="val-today">--/--/----</span>
                </div>
                <div class="summary-row">
                    <span>Hiệu lực đến</span>
                    <span class="val" id="val-end">Khi bạn hủy</span>
                </div>
                <div class="summary-row">
                    <span>Kỳ thanh toán tiếp theo</span>
                    <span class="val" id="val-next">--/--/----</span>
                </div>

                <div class="total-price-box">
                    <div>Tổng thanh toán:</div>
                    <div class="total-price-val" id="val-total">499.000đ</div>
                </div>

                <label class="terms-check">
                    <input type="checkbox" id="chk-terms" checked>
                    <span>Khi nhấn Thanh toán, bạn đã đồng ý với <b>Chính sách thanh toán</b> của chúng tôi.</span>
                </label>

                <button class="btn-checkout" onclick="window.processPayment()">ĐĂNG KÝ</button>
            </div>

            <div class="benefits-box">
                <h3>Đặc quyền gói VIP</h3>
                <ul class="benefits-list">
                    <li><i class="fa-solid fa-check"></i> Kho nhạc VIP độc quyền</li>
                    <li><i class="fa-solid fa-check"></i> Nghe nhạc không quảng cáo</li>
                    <li><i class="fa-solid fa-check"></i> Nghe và tải nhạc Lossless</li>
                    <li><i class="fa-solid fa-check"></i> Lưu trữ nhạc không giới hạn</li>
                    <li><i class="fa-solid fa-check"></i> Tính năng nghe nhạc nâng cao</li>
                    <li><i class="fa-solid fa-check"></i> Mở rộng khả năng Upload</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div style="margin-top: 60px;">
        <?php @include 'footer.php'; ?>
    </div>
</div>

<script>
    // Gắn hàm vào window để đảm bảo gọi được khi tải qua AJAX
    window.selectPlan = function(element) {
        document.querySelectorAll('.plan-option').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        
        const price = element.getAttribute('data-price');
        const months = parseInt(element.getAttribute('data-months'));
        
        document.getElementById('val-total').textContent = price + 'đ';
        
        const today = new Date();
        const nextDate = new Date(today.setMonth(today.getMonth() + months));
        
        const dd = String(nextDate.getDate()).padStart(2, '0');
        const mm = String(nextDate.getMonth() + 1).padStart(2, '0');
        const yyyy = nextDate.getFullYear();
        document.getElementById('val-next').textContent = `${dd}/${mm}/${yyyy}`;
    };

    window.processPayment = function() {
        if(!document.getElementById('chk-terms').checked) {
            if(typeof showGlobalNotify === 'function') showGlobalNotify('Vui lòng đồng ý với Chính sách thanh toán!', false);
            else alert('Vui lòng đồng ý với Chính sách thanh toán!');
            return;
        }
        if(typeof showGlobalNotify === 'function') showGlobalNotify('Đang chuyển hướng đến cổng thanh toán...', true);
        setTimeout(() => { alert('Tính năng thanh toán đang được phát triển!'); }, 1000);
    };

    // Khởi tạo ngày tháng ngay khi load file này
    setTimeout(() => {
        const initDate = new Date();
        const dStr = String(initDate.getDate()).padStart(2, '0') + '/' + String(initDate.getMonth() + 1).padStart(2, '0') + '/' + initDate.getFullYear();
        const todayEl = document.getElementById('val-today');
        if(todayEl) todayEl.textContent = dStr;
        
        const activePlan = document.querySelector('.plan-option.active');
        if(activePlan) window.selectPlan(activePlan);
    }, 100);
</script>