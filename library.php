<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo '<div style="color: white; text-align: center; margin-top: 50px;"><h2>Vui lòng <b style="color: var(--purple-primary); cursor:pointer;" onclick="openLoginModal()">Đăng nhập</b> để xem Thư viện cá nhân.</h2></div>';
    exit;
}

include_once 'render_helper.php';
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');
$username = $conn->real_escape_string($_SESSION['username']);
$accountId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

// 1. Dữ liệu TAB YÊU THÍCH
$sqlFav = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, 1 AS IsFavorite FROM user_favorites uf JOIN songs s ON uf.SongID = s.SongID LEFT JOIN song_artist sa ON s.SongID = sa.SongID LEFT JOIN artists a ON sa.ArtistID = a.ArtistID WHERE uf.Username = '$username' GROUP BY s.SongID ORDER BY s.SongID DESC";
$resFav = $conn->query($sqlFav);
$favJSON = []; $idxFav = 0;

// 2. Dữ liệu TAB LỊCH SỬ
$sqlHis = "SELECT s.*, GROUP_CONCAT(a.Name SEPARATOR ', ') AS Artists, MAX(uh.ListenedAt) as LastListened, IF(uf.SongID IS NOT NULL, 1, 0) AS IsFavorite FROM user_history uh JOIN songs s ON uh.SongID = s.SongID LEFT JOIN song_artist sa ON s.SongID = sa.SongID LEFT JOIN artists a ON sa.ArtistID = a.ArtistID LEFT JOIN user_favorites uf ON s.SongID = uf.SongID AND uf.Username = '$username' WHERE uh.Username = '$username' GROUP BY s.SongID ORDER BY LastListened DESC LIMIT 50";
$resHis = $conn->query($sqlHis);
$hisJSON = []; $idxHis = 0;

// 3. Dữ liệu TAB PLAYLIST (Lấy các playlist của user này)
$sqlPl = "SELECT * FROM playlists WHERE AccountID = $accountId ORDER BY PlaylistID DESC";
$resPl = $conn->query($sqlPl);
?>

<style>
    .lib-header { margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; display: flex; gap: 30px; }
    .tab-btn { background: none; border: none; color: rgba(255,255,255,0.5); font-size: 18px; font-weight: 700; cursor: pointer; transition: 0.3s; padding-bottom: 8px; position: relative; }
    .tab-btn:hover { color: white; }
    .tab-btn.active { color: white; }
    .tab-btn.active::after { content: ''; position: absolute; bottom: -11px; left: 0; width: 100%; height: 3px; background: var(--purple-primary); border-radius: 5px; }
    .song-list-container { display: flex; flex-direction: column; gap: 5px; }
    .song-item-wrapper { cursor: pointer; }
    .playlist-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: 0.3s; width: 200px; height: 220px; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 10px; color: white;}
    .playlist-card:hover { background: rgba(255,255,255,0.1); border-color: var(--purple-primary); }
    .btn-create-pl { border: 1px dashed rgba(255,255,255,0.3); }
    .btn-create-pl:hover { color: var(--purple-primary); border-color: var(--purple-primary); }
</style>

<div class="lib-header">
    <button class="tab-btn active" onclick="switchLibTab('fav', this)">Bài hát Yêu thích</button>
    <button class="tab-btn" onclick="switchLibTab('history', this)">Lịch sử nghe</button>
    <button class="tab-btn" onclick="switchLibTab('playlist', this)">Playlist của tôi</button>
</div>

<div id="tab-fav" class="tab-content">
    <div class="song-list-container">
    <?php if ($resFav->num_rows > 0): while ($row = $resFav->fetch_assoc()): 
        $favJSON[] = ['id' => $row['SongID'], 'url' => $row['FilePath_URL'], 'title' => $row['Title'], 'artist' => $row['Artists']??'Không rõ', 'cover' => $row['CoverImage_URL']??''];
    ?>
        <div class="song-item-wrapper" onclick="playPlaylist(<?php echo $idxFav++; ?>, 'data-fav')">
            <?php renderSongItem($row); ?>
        </div>
    <?php endwhile; else: echo "<p style='color:gray; padding: 20px 0;'>Bạn chưa yêu thích bài hát nào.</p>"; endif; ?>
    </div>
    <div id="data-fav" style="display:none;" data-playlist='<?php echo htmlspecialchars(json_encode($favJSON), ENT_QUOTES, "UTF-8"); ?>'></div>
</div>

<div id="tab-history" class="tab-content" style="display:none;">
    <div id="local-history-container" class="song-list-container"></div>
</div>

<div id="tab-playlist" class="tab-content" style="display:none; padding-top: 20px;">
    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div class="playlist-card btn-create-pl" onclick="openPlaylistModal()">
            <i class="fa-solid fa-plus" style="font-size: 40px; opacity: 0.5;"></i>
            <div style="font-weight: 600; font-size: 16px;">Tạo Playlist mới</div>
        </div>

        <?php if ($resPl && $resPl->num_rows > 0): while($pl = $resPl->fetch_assoc()): ?>
           <div class="playlist-card" onclick="loadContent('playlist_view.php?id=<?php echo $pl['PlaylistID']; ?>')">
                <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #4e346b, #231b2e); border-radius: 10px; display: flex; justify-content: center; align-items: center;">
                    <i class="fa-solid fa-compact-disc" style="font-size: 50px; color: var(--purple-primary); opacity: 0.8;"></i>
                </div>
                <div style="font-weight: 600; font-size: 15px; margin-top:5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%;"><?php echo htmlspecialchars($pl['Title']); ?></div>
                <div style="font-size: 12px; color: gray;">Playlist • <?php echo $_SESSION['username']; ?></div>
            </div>
        <?php endwhile; endif; ?>

    </div>
</div>

<div id="createPlModal" class="modal-overlay" style="z-index: 2000;">
    <div class="modal-content" style="width: 350px; background: var(--bg-sidebar); border: 1px solid rgba(255,255,255,0.1);">
        <h3 style="color: white; margin-bottom: 20px;">Tạo Playlist Mới</h3>
        <input type="text" id="plTitleInput" placeholder="Nhập tên playlist..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; margin-bottom: 20px; outline:none;">
        <div style="display: flex; gap: 10px;">
            <button onclick="submitCreatePlaylist()" style="flex: 1; background: var(--purple-primary); padding: 10px; border-radius: 20px; color: white; border: none; cursor: pointer; font-weight: bold;">Tạo mới</button>
            <button onclick="closePlaylistModal()" style="flex: 1; background: transparent; padding: 10px; border-radius: 20px; color: white; border: 1px solid rgba(255,255,255,0.3); cursor: pointer;">Hủy</button>
        </div>
    </div>
</div>

<script>
function switchLibTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    btn.classList.add('active');

    if (tabId === 'history') {
        renderHistory('local-history-container');
    }
}

// Các hàm xử lý Modal Tạo Playlist
function openPlaylistModal() {
    document.getElementById('createPlModal').style.display = 'flex';
    document.getElementById('plTitleInput').focus();
}

function closePlaylistModal() {
    document.getElementById('createPlModal').style.display = 'none';
    document.getElementById('plTitleInput').value = '';
}

function submitCreatePlaylist() {
    const titleInput = document.getElementById('plTitleInput');
    const title = titleInput.value.trim();
    if (!title) { alert('Vui lòng nhập tên Playlist!'); return; }

    fetch('user_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=create_playlist&title=' + encodeURIComponent(title)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closePlaylistModal();
            // Tải lại trang Thư viện để hiện playlist mới
            loadContent('library.php');
            // Gợi ý: Có thể dùng setTimeout để auto click sang tab Playlist
            setTimeout(() => {
                const tabs = document.querySelectorAll('.tab-btn');
                if(tabs.length >= 3) switchLibTab('playlist', tabs[2]);
            }, 300);
        } else {
            alert(data.message);
        }
    });
}
</script>
<?php $conn->close(); ?>
</div>
    </div> <?php include 'footer.php'; ?>