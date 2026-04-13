<?php
session_start();
include_once 'render_helper.php';

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// ========================================================
// 1. TRUY VẤN: TOP 10 BÀI HÁT TRONG 7 NGÀY GẦN NHẤT
// ========================================================
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

// ========================================================
// 2. TRUY VẤN: BXH THEO THỂ LOẠI
// ========================================================
$tab = $_GET['tab'] ?? 'all';
$tabCondition = "";

if ($tab === 'vn') $tabCondition = "AND s.SongID IN (SELECT sa.SongID FROM song_artist sa JOIN artists a ON sa.ArtistID = a.ArtistID WHERE a.Country = 'Việt Nam')";
elseif ($tab === 'usuk') $tabCondition = "AND s.SongID IN (SELECT sa.SongID FROM song_artist sa JOIN artists a ON sa.ArtistID = a.ArtistID WHERE a.Country = 'US-UK')";
elseif ($tab === 'kr') $tabCondition = "AND s.SongID IN (SELECT sa.SongID FROM song_artist sa JOIN artists a ON sa.ArtistID = a.ArtistID WHERE a.Country = 'Hàn Quốc')";

$sqlGenre = "SELECT g.Name, SUM(s.PlayCount) AS TotalPlays
             FROM genres g
             JOIN songs s ON g.GenreID = s.GenreID
             WHERE s.status = 1 $tabCondition
             GROUP BY g.GenreID
             ORDER BY TotalPlays DESC LIMIT 10";

$resGenre = $conn->query($sqlGenre);
$genreStats = [];
$maxGenrePlays = 0;

if ($resGenre && $resGenre->num_rows > 0) {
    while ($r = $resGenre->fetch_assoc()) {
        $genreStats[] = $r;
        if ($r['TotalPlays'] > $maxGenrePlays) {
            $maxGenrePlays = $r['TotalPlays'];
        }
    }
}
?>

<style>
    /* ================= CSS GIAO DIỆN LYRXCHART (TOP 10) ================= */
    .lyrxchart-container { padding: 20px 0; animation: fadeIn 0.5s ease; }
    
    .chart-header-title {
        font-size: 38px; font-weight: 900;
        background: linear-gradient(90deg, #4a90e2, #50e3c2, #e35050);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        margin-bottom: 25px; letter-spacing: -1px; text-transform: lowercase;
    }

    .chart-wrapper {
        background: rgba(35, 27, 46, 0.8);
        border-radius: 15px; padding: 20px; margin-bottom: 40px; height: 350px; position: relative;
    }

    .rank-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 50px; }
    .rank-item {
        display: flex; align-items: center; padding: 10px 15px; border-radius: 10px;
        background: rgba(255, 255, 255, 0.02); transition: background 0.3s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05); cursor: pointer;
    }
    .rank-item:hover { background: rgba(255, 255, 255, 0.1); }

    .rank-number {
        font-size: 35px; font-weight: 900; width: 60px; text-align: center;
        margin-right: 15px; color: transparent; font-family: 'Inter', sans-serif;
        -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.4);
    }
    .rank-1 .rank-number { -webkit-text-stroke: 1.5px #4a90e2; color: rgba(74, 144, 226, 0.1); }
    .rank-2 .rank-number { -webkit-text-stroke: 1.5px #50e3c2; color: rgba(80, 227, 194, 0.1); }
    .rank-3 .rank-number { -webkit-text-stroke: 1.5px #e35050; color: rgba(227, 80, 80, 0.1); }

    /* Override renderSongItem để ẩn nốt nhạc mặc định đi */
    .rank-item .song-item { border: none !important; padding: 0 !important; margin: 0 !important; background: transparent !important; }
    .rank-item .song-item:hover { background: transparent !important; }
    .rank-item .prefix-music-icon { display: none !important; }

    /* ================= CSS CHO BXH THỂ LOẠI ================= */
    .genre-chart-box { background: #231b2e; padding: 25px; border-radius: 15px; margin-bottom: 40px; }
    .genre-tabs { display: flex; gap: 10px; margin-bottom: 25px; }
    .genre-tab-btn { 
        padding: 8px 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); 
        background: transparent; color: white; cursor: pointer; font-size: 13px; transition: 0.2s; 
    }
    .genre-tab-btn.active { background: var(--purple-primary); border-color: var(--purple-primary); font-weight: bold; }
    .genre-tab-btn:hover:not(.active) { background: rgba(255,255,255,0.1); }
    
    .genre-row { display: flex; align-items: center; margin-bottom: 18px; }
    .genre-rank { width: 30px; font-weight: 900; font-size: 16px; color: rgba(255,255,255,0.4); text-align: center; }
    .genre-rank.top-1, .genre-rank.top-2, .genre-rank.top-3 { color: var(--purple-primary); }
    .genre-name { width: 120px; font-weight: 600; font-size: 14px; color: white; padding-left: 10px; }
    .genre-bar-wrap { flex: 1; height: 6px; background: rgba(255,255,255,0.05); border-radius: 4px; margin: 0 15px; overflow: hidden; }
    .genre-bar-fill { height: 100%; background: linear-gradient(90deg, #4e346b, #9b4de0); border-radius: 4px; transition: width 1s ease-out; }
    .genre-plays { width: 70px; text-align: right; font-size: 13px; color: rgba(255,255,255,0.6); }
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
                    
                    <div style="flex: 1;">
                        <?php renderSongItem($song); ?>
                    </div>
                    
                    <div style="color: gray; font-size: 14px; margin-left: 20px; font-weight: bold;">
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

    <div id="data-lyrxchart" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($songsJSON), ENT_QUOTES, "UTF-8"); ?>'></div>


    <div class="genre-chart-box">
        <h3 style="font-size: 22px; font-weight: 800; color: white; margin-bottom: 20px;">BXH Theo Thể Loại</h3>
        
        <div class="genre-tabs">
            <button class="genre-tab-btn <?php echo $tab == 'all' ? 'active' : ''; ?>" onclick="loadContent('lyrxchart.php?tab=all')">Tất cả</button>
            <button class="genre-tab-btn <?php echo $tab == 'vn' ? 'active' : ''; ?>" onclick="loadContent('lyrxchart.php?tab=vn')">Việt Nam</button>
            <button class="genre-tab-btn <?php echo $tab == 'usuk' ? 'active' : ''; ?>" onclick="loadContent('lyrxchart.php?tab=usuk')">Âu Mỹ</button>
            <button class="genre-tab-btn <?php echo $tab == 'kr' ? 'active' : ''; ?>" onclick="loadContent('lyrxchart.php?tab=kr')">Hàn Quốc</button>
        </div>

        <div>
            <?php if (!empty($genreStats)): ?>
                <?php foreach ($genreStats as $idx => $g): 
                    $gRank = $idx + 1;
                    $rankColorClass = ($gRank <= 3) ? 'top-'.$gRank : '';
                    $percent = ($maxGenrePlays > 0) ? ($g['TotalPlays'] / $maxGenrePlays) * 100 : 0;
                ?>
                    <div class="genre-row">
                        <div class="genre-rank <?php echo $rankColorClass; ?>"><?php echo $gRank; ?></div>
                        <div class="genre-name"><?php echo htmlspecialchars($g['Name']); ?></div>
                        <div class="genre-bar-wrap">
                            <div class="genre-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                        <div class="genre-plays"><?php echo number_format($g['TotalPlays']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: gray; text-align: center; padding: 20px;">Chưa có dữ liệu cho khu vực này.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
            (() => {
                if (window.lyrxChart) window.lyrxChart.destroy();
                
                setTimeout(() => {
                    const canvas = document.getElementById('lyrxChartCanvas');
                    if (!canvas) return;
                    const ctx = canvas.getContext('2d');
                    
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
                            hitRadius: 30 // Mở rộng vùng chạm chuột
                        };
                    });

                    let activeHoverIndex = null;

                    window.lyrxChart = new Chart(ctx, {
                        type: 'line',
                        plugins: [glowPlugin],
                        data: {
                            labels: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00'],
                            datasets: dynamicDatasets 
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            interaction: { mode: 'nearest', intersect: false }, 
                            onHover: (event, elements, chart) => {
                                if (elements.length) {
                                    const hoveredIndex = elements[0].datasetIndex;
                                    if (activeHoverIndex !== hoveredIndex) {
                                        activeHoverIndex = hoveredIndex;
                                        chart.data.datasets.forEach((dataset, i) => {
                                            dataset.borderColor = (i === hoveredIndex) ? colors[i] : colors[i] + '1A';
                                            dataset.borderWidth = (i === hoveredIndex) ? 4 : 1;
                                        });
                                        chart.update();
                                    }
                                } else {
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
                            window.lyrxChart.data.datasets.forEach((dataset, i) => {
                                dataset.borderColor = (i === index) ? colors[i] : colors[i] + '1A';
                                dataset.borderWidth = (i === index) ? 4 : 1;
                            });
                            window.lyrxChart.update();
                        });
                        item.addEventListener('mouseleave', () => {
                            window.lyrxChart.data.datasets.forEach((dataset, i) => {
                                dataset.borderColor = colors[i];
                                dataset.borderWidth = 3;
                            });
                            window.lyrxChart.update();
                        });
                    });
                }, 100);
            })();
</script>

<?php 
$conn->close();
include 'footer.php'; 
?>