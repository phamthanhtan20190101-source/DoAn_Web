<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>';
    exit();
}

$servername = "localhost"; $username = "root"; $password = "vertrigo"; $dbname = "song_management";
$conn = new mysqli($servername, $username, $password, $dbname);

// Đếm tổng số liệu từ các bảng
$totalSongs = $conn->query("SELECT COUNT(*) as c FROM songs")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM account WHERE Role='user'")->fetch_assoc()['c'];
$totalArtists = $conn->query("SELECT COUNT(*) as c FROM artists")->fetch_assoc()['c'];
$totalPlays = $conn->query("SELECT SUM(PlayCount) as c FROM songs")->fetch_assoc()['c'];

// Lấy Top 5 bài hát hot nhất để vẽ biểu đồ
$topSongsResult = $conn->query("SELECT Title, PlayCount FROM songs ORDER BY PlayCount DESC LIMIT 5");
$chartLabels = []; $chartData = [];
while($row = $topSongsResult->fetch_assoc()) {
    $chartLabels[] = $row['Title'];
    $chartData[] = $row['PlayCount'];
}
$conn->close();
?>

<h2>Tổng Quan Hệ Thống (Dashboard)</h2>

<div style="display: flex; gap: 20px; margin-top: 20px;">
    <div style="flex: 1; background: linear-gradient(135deg, #9b4de0, #6f55ff); padding: 20px; border-radius: 10px; color: white;">
        <h3 style="margin: 0; font-size: 16px; opacity: 0.9;"><i class="fa-solid fa-music"></i> Tổng bài hát</h3>
        <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo number_format($totalSongs); ?></p>
    </div>
    <div style="flex: 1; background: linear-gradient(135deg, #f59e0b, #ea580c); padding: 20px; border-radius: 10px; color: white;">
        <h3 style="margin: 0; font-size: 16px; opacity: 0.9;"><i class="fa-solid fa-users"></i> Người dùng</h3>
        <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo number_format($totalUsers); ?></p>
    </div>
    <div style="flex: 1; background: linear-gradient(135deg, #10b981, #059669); padding: 20px; border-radius: 10px; color: white;">
        <h3 style="margin: 0; font-size: 16px; opacity: 0.9;"><i class="fa-solid fa-headphones"></i> Tổng lượt nghe</h3>
        <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo number_format($totalPlays ?? 0); ?></p>
    </div>
    <div style="flex: 1; background: linear-gradient(135deg, #3b82f6, #2563eb); padding: 20px; border-radius: 10px; color: white;">
        <h3 style="margin: 0; font-size: 16px; opacity: 0.9;"><i class="fa-solid fa-microphone-lines"></i> Nghệ sĩ</h3>
        <p style="font-size: 32px; font-weight: bold; margin: 10px 0 0 0;"><?php echo number_format($totalArtists); ?></p>
    </div>
</div>

<div style="margin-top: 30px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1);">
    <h3 style="color: white; margin-bottom: 20px;">Top 5 Bài Hát Được Nghe Nhiều Nhất</h3>
    <canvas id="topSongsChart" height="80"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('topSongsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [{
                label: 'Lượt nghe',
                data: <?php echo json_encode($chartData); ?>,
                backgroundColor: 'rgba(155, 77, 224, 0.7)',
                borderColor: 'rgba(155, 77, 224, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { ticks: { color: '#ccc' }, grid: { display: false } }
            },
            plugins: { legend: { labels: { color: 'white' } } }
        }
    });
</script>