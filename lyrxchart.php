<?php
session_start();
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// 1. TRUY VẤN TOP 10 BÀI HÁT TRONG 7 NGÀY GẦN NHẤT
// Sử dụng bảng user_history để đếm số lượt nghe thực tế trong 7 ngày qua
$sql = "SELECT s.SongID, s.Title, s.Duration, s.FilePath_URL, s.CoverImage_URL, 
               GROUP_CONCAT(DISTINCT a.Name SEPARATOR ', ') AS Artists,
               COUNT(uh.ID) AS RecentPlays
        FROM songs s
        JOIN user_history uh ON s.SongID = uh.SongID
        LEFT JOIN song_artist sa ON s.SongID = sa.SongID
        LEFT JOIN artists a ON sa.ArtistID = a.ArtistID
        WHERE uh.ListenedAt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND s.status = 1
        GROUP BY s.SongID
        ORDER BY RecentPlays DESC
        LIMIT 10";

$result = $conn->query($sql);
$topSongs = [];
$songsJSON = [];
$index = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $topSongs[] = $row;
        $songsJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists'] ?? 'Không rõ', 'cover' => $row['CoverImage_URL'] ?? ''];
    }
}
?>

<style>
    /* ================= CSS GIAO DIỆN LYRXCHART ================= */
    .lyrxchart-container {
        padding: 20px 0;
    }
    
    .chart-header-title {
        font-size: 32px;
        font-weight: 800;
        background: linear-gradient(90deg, #4a90e2, #50e3c2, #e35050);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 20px;
    }

    /* Vùng chứa Biểu đồ */
    .chart-wrapper {
        background: rgba(35, 27, 46, 0.8);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 40px;
        height: 350px;
        position: relative;
    }

    /* CSS cho Bảng xếp hạng */
    .rank-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .rank-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        transition: background 0.3s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .rank-item:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    /* Số thứ hạng siêu to khổng lồ */
    .rank-number {
        font-size: 32px;
        font-weight: 900;
        width: 60px;
        text-align: center;
        margin-right: 15px;
        color: transparent;
        -webkit-text-stroke: 1px rgba(255, 255, 255, 0.4);
    }

    /* Màu nổi bật cho Top 1, 2, 3 */
    .rank-1 .rank-number { -webkit-text-stroke: 1.5px #4a90e2; color: rgba(74, 144, 226, 0.1); }
    .rank-2 .rank-number { -webkit-text-stroke: 1.5px #50e3c2; color: rgba(80, 227, 194, 0.1); }
    .rank-3 .rank-number { -webkit-text-stroke: 1.5px #e35050; color: rgba(227, 80, 80, 0.1); }

    .rank-info { flex: 1; }
</style>

<div class="lyrxchart-container">
    <h1 class="chart-header-title">#lyrxchart</h1>

    <div class="chart-wrapper">
        <canvas id="lyrxChartCanvas"></canvas>
    </div>

    <div class="rank-list">
        <?php if (count($topSongs) > 0): ?>
            <?php foreach ($topSongs as $idx => $song): 
                $rank = $idx + 1;
                $rankClass = ($rank <= 3) ? "rank-$rank" : "";
            ?>
                <div class="rank-item <?php echo $rankClass; ?>" onclick="playPlaylist(<?php echo $idx; ?>, 'data-lyrxchart')">
                    <div class="rank-number"><?php echo $rank; ?></div>
                    
                    <div style="flex: 1; pointer-events: none;">
                        <?php renderSongItem($song); ?>
                    </div>
                    
                    <div style="color: gray; font-size: 14px; margin-left: 20px;">
                        <?php echo number_format($song['RecentPlays']); ?> lượt nghe
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; color: gray; padding: 40px;">
                Chưa có dữ liệu lượt nghe trong 7 ngày qua. Hãy nghe vài bài hát để hệ thống cập nhật nhé!
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="data-lyrxchart" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>

<script>
            (() => {
                const ctx = document.getElementById('lyrxChartCanvas').getContext('2d');
                
                // Plugin tạo viền sáng (Glow)
                const glowPlugin = {
                    id: 'glow',
                    beforeDatasetsDraw: (chart) => {
                        const ctx = chart.ctx;
                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            if (!meta.hidden && dataset.borderWidth >= 3) {
                                ctx.save();
                                ctx.shadowColor = dataset.borderColor;
                                ctx.shadowBlur = 15;
                                ctx.shadowOffsetX = 0;
                                ctx.shadowOffsetY = 0;
                                ctx.stroke();
                                ctx.restore();
                            }
                        });
                    }
                };

                // LẤY DỮ LIỆU THẬT CỦA TOP 3
                const topSongsData = <?php 
                    $top3 = array_slice($topSongs, 0, 3);
                    $chartExport = [];
                    foreach($top3 as $s) {
                        $chartExport[] = [
                            'title' => $s['Title'],
                            'plays' => (int)$s['RecentPlays']
                        ];
                    }
                    echo json_encode($chartExport);
                ?>;

                function generateLineData(totalPlays) {
                    if (totalPlays === 0) return [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                    const baseCurve = [10, 25, 15, 35, 50, 45, 60, 85, 70, 90, 100];
                    const baseTotal = baseCurve.reduce((a, b) => a + b, 0);
                    return baseCurve.map(val => {
                        let scaled = (val / baseTotal) * totalPlays;
                        let noise = scaled * (Math.random() * 0.3 - 0.15);
                        return Math.max(0.1, scaled + noise);
                    });
                }

                const colors = ['#4a90e2', '#50e3c2', '#e35050'];
                
                const dynamicDatasets = topSongsData.map((song, index) => {
                    return {
                        label: song.title,
                        data: generateLineData(song.plays),
                        borderColor: colors[index],
                        backgroundColor: colors[index],
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 8,
                        hitRadius: 30 // BÍ QUYẾT LÀ ĐÂY: Mở rộng vùng chạm chuột tàng hình lên 30px
                    };
                });

                let activeHoverIndex = null; // Khóa chống chớp giật biểu đồ

                const lyrxChart = new Chart(ctx, {
                    type: 'line',
                    plugins: [glowPlugin],
                    data: {
                        labels: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
                        datasets: dynamicDatasets 
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        // Sửa intersect thành false: Chỉ cần rê chuột GẦN đường là nó nhận, không cần chạm chính xác
                        interaction: { mode: 'nearest', intersect: false }, 
                        onHover: (event, elements, chart) => {
                            if (elements.length) {
                                const hoveredIndex = elements[0].datasetIndex;
                                // Chỉ vẽ lại khi rê chuột sang đường KHÁC (chống lỗi mất Tooltip)
                                if (activeHoverIndex !== hoveredIndex) {
                                    activeHoverIndex = hoveredIndex;
                                    chart.data.datasets.forEach((dataset, i) => {
                                        dataset.borderColor = (i === hoveredIndex) ? colors[i] : colors[i] + '1A';
                                        dataset.borderWidth = (i === hoveredIndex) ? 4 : 1;
                                    });
                                    chart.update();
                                }
                            } else {
                                // Khi rê chuột ra ngoài khoảng không
                                if (activeHoverIndex !== null) {
                                    activeHoverIndex = null;
                                    chart.data.datasets.forEach((dataset, i) => {
                                        dataset.borderColor = colors[i];
                                        dataset.borderWidth = 3;
                                    });
                                    chart.update();
                                }
                            }
                        },
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(0,0,0,0.8)', titleColor: '#fff', bodyColor: '#fff', padding: 10 } },
                        scales: { x: { grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, ticks: { color: 'rgba(255,255,255,0.5)' } }, y: { display: false } }
                    }
                });

                // LIÊN KẾT HOVER VỚI DANH SÁCH TOP 3 Ở DƯỚI
                const rankItems = document.querySelectorAll('.rank-item');
                rankItems.forEach((item, index) => {
                    if(index > 2) return; 
                    item.addEventListener('mouseenter', () => {
                        lyrxChart.data.datasets.forEach((dataset, i) => {
                            dataset.borderColor = (i === index) ? colors[i] : colors[i] + '1A';
                            dataset.borderWidth = (i === index) ? 4 : 1;
                        });
                        lyrxChart.update();
                    });
                    item.addEventListener('mouseleave', () => {
                        lyrxChart.data.datasets.forEach((dataset, i) => {
                            dataset.borderColor = colors[i];
                            dataset.borderWidth = 3;
                        });
                        lyrxChart.update();
                    });
                });

            })();
</script>
<?php $conn->close(); ?>
</div>
    </div> <?php include 'footer.php'; ?>