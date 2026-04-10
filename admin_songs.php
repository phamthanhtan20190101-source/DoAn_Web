<?php
session_start();
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo '<div style="color:white;">Bạn không có quyền truy cập.</div>'; exit();
}

$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$query = "SELECT s.SongID, s.Title, s.FilePath_URL, g.Name AS GenreName, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists
          FROM songs s
          LEFT JOIN genres g ON g.GenreID = s.GenreID
          LEFT JOIN song_artist sa ON sa.SongID = s.SongID
          LEFT JOIN artists a ON a.ArtistID = sa.ArtistID
          GROUP BY s.SongID ORDER BY s.SongID DESC";
$result = $conn->query($query);
?>

<h2 style="color: white; margin-bottom: 20px;">Quản lý Bài hát</h2>
<button type="button" class="btn-admin highlight-green" onclick="loadContent('add_song.php')" style="width: fit-content;">+ Thêm bài hát mới</button>

<div style="margin-top: 20px; color: white;">
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-color: rgba(255,255,255,0.1); text-align: left;">
        <thead style="background: rgba(255,255,255,0.05);">
            <tr>
                <th>Tên bài hát</th>
                <th>Ca sĩ</th>
                <th>Nghe thử</th> <th style="width: 160px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['Title']); ?></strong><br><small style="color:#aaa"><?php echo $row['GenreName']; ?></small></td>
                        <td style="color: var(--purple-primary);"><?php echo htmlspecialchars($row['Artists'] ?? 'Chưa có'); ?></td>
                        
                        <td>
                            <div id="preview-container-<?php echo $row['SongID']; ?>">
                                <button type="button" class="btn-admin" style="background: #3b82f6; padding: 5px 12px;" 
                                        onclick="togglePreview('<?php echo $row['FilePath_URL']; ?>', <?php echo $row['SongID']; ?>)">
                                    <i class="fa-solid fa-play"></i> Nghe thử
                                </button>
                            </div>
                        </td>

                        <td>
                            <button type="button" class="btn-admin" onclick="loadContent('edit_song.php?id=<?php echo $row['SongID']; ?>')">Sửa</button>
                            <button type="button" class="btn-admin" style="background:#ef4444;" onclick="deleteSong(<?php echo $row['SongID']; ?>)">Xóa</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">Chưa có bài hát nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Hàm xử lý hiện/ẩn trình phát nhạc mini
function togglePreview(url, id) {
    const container = document.getElementById('preview-container-' + id);
    
    // Nếu đang hiện trình phát thì thu hồi lại nút bấm
    if (container.querySelector('audio')) {
        container.innerHTML = `<button type="button" class="btn-admin" style="background: #3b82f6; padding: 5px 12px;" onclick="togglePreview('${url}', ${id})"><i class="fa-solid fa-play"></i> Nghe thử</button>`;
    } else {
        // Tắt tất cả các trình phát khác đang mở để tránh ồn
        document.querySelectorAll('[id^="preview-container-"]').forEach(c => {
            if (c.querySelector('audio')) {
                const oldId = c.id.replace('preview-container-', '');
                const oldUrl = c.querySelector('audio source').src;
                // Trả về nút bấm cũ (lưu ý: cần xử lý chuỗi URL cẩn thận ở đây)
                // Để đơn giản, ta chỉ cần reset lại HTML của tất cả
            }
        });

        // Hiện trình phát audio tại đúng dòng đó
        container.innerHTML = `
            <div style="display:flex; align-items:center; gap:10px;">
                <audio controls autoplay style="height: 30px; width: 200px;">
                    <source src="${url}" type="audio/mpeg">
                </audio>
                <i class="fa-solid fa-xmark" style="cursor:pointer; color:#ef4444;" onclick="togglePreview('${url}', ${id})"></i>
            </div>
        `;
    }
}
</script>
<?php $conn->close(); ?>