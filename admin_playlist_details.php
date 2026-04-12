<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') exit();

$playlistId = intval($_GET['id'] ?? 0);
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

// 1. Lấy thông tin Playlist
$plQuery = $conn->query("SELECT Title FROM playlists WHERE PlaylistID = $playlistId");
$playlist = $plQuery->fetch_assoc();

// 2. Lấy danh sách bài hát ĐANG CÓ trong playlist này (Dùng bảng playlist_song)
$songsInPl = $conn->query("SELECT s.SongID, s.Title, ar.Name as ArtistName 
                           FROM playlist_song ps 
                           JOIN songs s ON ps.SongID = s.SongID 
                           LEFT JOIN song_artist sa ON s.SongID = sa.SongID
                           LEFT JOIN artists ar ON sa.ArtistID = ar.ArtistID
                           WHERE ps.PlaylistID = $playlistId");

// 3. Lấy tất cả bài hát ĐÃ DUYỆT (status = 1) mà CHƯA CÓ trong playlist này
$allSongs = $conn->query("SELECT s.SongID, s.Title, ar.Name as ArtistName 
                          FROM songs s 
                          LEFT JOIN song_artist sa ON s.SongID = sa.SongID
                          LEFT JOIN artists ar ON sa.ArtistID = ar.ArtistID
                          WHERE s.status = 1 
                          AND s.SongID NOT IN (
                              SELECT SongID FROM playlist_song WHERE PlaylistID = $playlistId
                          )
                          ORDER BY s.Title ASC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: white;">Chi tiết Playlist: <span style="color: var(--purple-primary);"><?php echo htmlspecialchars($playlist['Title']); ?></span></h2>
    <button class="btn-admin" onclick="loadContent('admin_playlists.php')" style="background: #6b7280; width: fit-content;">Quay lại</button>
</div>

<div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.1);">
    <h4 style="color: white; margin-bottom: 15px;">Thêm bài hát mới vào danh sách</h4>
    <div style="display: flex; gap: 10px;">
        <select id="adminSelectSong" class="search-select" style="flex: 1; padding: 10px; border-radius: 5px; background: #170f23; color: white; border: 1px solid rgba(255,255,255,0.2);">
            <option value="">-- Tìm và chọn bài hát --</option>
            <?php while($s = $allSongs->fetch_assoc()): ?>
                <option value="<?php echo $s['SongID']; ?>"><?php echo htmlspecialchars($s['Title'] . " - " . $s['ArtistName']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="button" onclick="submitAddSongToVipPlaylist()" class="btn-admin highlight-green" style="width: 150px;">THÊM VÀO</button>
    </div>
</div>

<h4 style="color: white; margin-bottom: 15px;">Danh sách bài hát trong Playlist</h4>
<table border="1" cellpadding="10" cellspacing="0" style="width:100%; color: white; border-color: rgba(255,255,255,0.1); text-align: left;">
    <thead>
        <tr style="background: rgba(255,255,255,0.05);">
            <th style="width: 50px; text-align: center;">STT</th>
            <th>Tên bài hát</th>
            <th>Nghệ sĩ</th>
            <th style="width: 120px; text-align: center;">Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if($songsInPl->num_rows > 0): $stt=1; while($s = $songsInPl->fetch_assoc()): ?>
            <tr>
                <td style="text-align: center;"><?php echo $stt++; ?></td>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($s['Title']); ?></td>
                <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($s['ArtistName']); ?></td>
                <td style="text-align: center;">
                    <button class="btn-admin" style="background: #ef4444; padding: 5px 12px;" onclick="removeSongFromVipPlaylist(<?php echo $s['SongID']; ?>)">Gỡ bỏ</button>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="4" style="text-align: center; padding: 30px; color: gray;">Playlist này chưa có bài hát nào. Hãy thêm bài hát ở trên!</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
    function submitAddSongToVipPlaylist() {
        const songId = document.getElementById('adminSelectSong').value;
        if(!songId) { showGlobalNotify("Vui lòng chọn một bài hát!", false); return; }

        fetch('user_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add_to_playlist&song_id=${songId}&playlist_id=<?php echo $playlistId; ?>`
        })
        .then(res => res.json())
        .then(data => {
            showGlobalNotify(data.message, data.success);
            if(data.success) loadContent('admin_playlist_details.php?id=<?php echo $playlistId; ?>');
        });
    }

    function removeSongFromVipPlaylist(songId) {
        if(!confirm('Bạn có chắc muốn gỡ bài hát này khỏi playlist mẫu?')) return;
        fetch('user_action.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=remove_from_playlist&playlist_id=<?php echo $playlistId; ?>&song_id=${songId}`
        })
        .then(res => res.json())
        .then(data => {
            showGlobalNotify(data.message, data.success);
            if(data.success) loadContent('admin_playlist_details.php?id=<?php echo $playlistId; ?>');
        });
    }
</script>
<?php $conn->close(); ?>