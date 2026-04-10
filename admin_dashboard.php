<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);

// Thống kê số liệu
$totalSongs = $conn->query("SELECT COUNT(*) as c FROM songs")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM account WHERE Role='user'")->fetch_assoc()['c'];
$totalArtists = $conn->query("SELECT COUNT(*) as c FROM artists")->fetch_assoc()['c'];
$totalPlays = $conn->query("SELECT SUM(PlayCount) as c FROM songs")->fetch_assoc()['c'];

// Lấy Top 5 bài hát
$topSongsResult = $conn->query("SELECT Title, PlayCount FROM songs ORDER BY PlayCount DESC LIMIT 5");
$chartLabels = []; $chartData = [];
while($row = $topSongsResult->fetch_assoc()) {
    $chartLabels[] = $row['Title'];
    $chartData[] = $row['PlayCount'];
}
$conn->close();
?>

<style>
    .dashboard-cards { display: flex; gap: 20px; margin-top: 20px; }
    .stat-card { 
        flex: 1; padding: 25px; border-radius: 15px; color: white; 
        box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: 0.3s;
        position: relative; overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card i { position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.15; }
    .stat-card h3 { margin: 0; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
    .stat-card p { font-size: 32px; font-weight: 800; margin: 10px 0 0 0; }

    .chart-box {
        margin-top: 35px; background: #231b2e; padding: 25px; 
        border-radius: 15px; border: 1px solid rgba(255,255,255,0.05);
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    }
</style>

<h2 style="color: white; font-weight: 700;">Hệ thống Lyrx <span style="color: var(--purple-primary); font-size: 14px; font-weight: 400; margin-left: 10px;">Dashboard</span></h2>

<div class="dashboard-cards">
    <div class="stat-card" style="background: linear-gradient(135deg, #9b4de0, #6f55ff);">
        <h3>Bài hát</h3>
        <p><?php echo number_format($totalSongs); ?></p>
        <i class="fa-solid fa-music"></i>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #ea580c);">
        <h3>Thành viên</h3>
        <p><?php echo number_format($totalUsers); ?></p>
        <i class="fa-solid fa-users"></i>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
        <h3>Lượt nghe</h3>
        <p><?php echo number_format($totalPlays ?? 0); ?></p>
        <i class="fa-solid fa-headphones"></i>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
        <h3>Nghệ sĩ</h3>
        <p><?php echo number_format($totalArtists); ?></p>
        <i class="fa-solid fa-microphone-lines"></i>
    </div>
</div>

<div class="chart-box">
    <h3 style="color: white; margin-bottom: 25px; font-size: 18px;">Top 5 Bài Hát Được Nghe Nhiều Nhất</h3>
    <div style="height: 350px;">
        <canvas id="topSongsChart"></canvas>
    </div>
</div>

<script>
    (() => {
        var ctx = document.getElementById('topSongsChart').getContext('2d');
        
        // Tạo màu Gradient cho cột biểu đồ
        var purpleGradient = ctx.createLinearGradient(0, 0, 0, 400);
        purpleGradient.addColorStop(0, 'rgba(155, 77, 224, 1)');
        purpleGradient.addColorStop(1, 'rgba(111, 85, 255, 0.2)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: purpleGradient,
                    borderColor: 'rgba(155, 77, 224, 1)',
                    borderWidth: 1,
                    borderRadius: 10, // Bo góc cột biểu đồ
                    borderSkipped: false,
                    maxBarThickness: 60 // QUAN TRỌNG: Giới hạn độ rộng cột để ko bị "to quá khổ"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Ẩn chú thích cho gọn
                    tooltip: {
                        backgroundColor: '#170f23',
                        titleColor: '#fff',
                        bodyColor: '#ccc',
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }, // Lưới mờ tinh tế
                        ticks: { color: '#888', font: { size: 12 } }
                    },
                    x: {
                        grid: { display: false }, // Ẩn lưới trục X
                        ticks: { color: '#ccc', font: { size: 13, weight: '500' } }
                    }
                }
            }
        });
    })();
</script>