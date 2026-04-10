<h2 style="color: white;">Quản lý Banner/Slide</h2>
<div style="margin-bottom: 20px;">
    <button class="btn-admin highlight-green" onclick="showAddBannerForm()" style="width: fit-content;">+ Thêm Banner mới</button>
</div>

<table class="admin-table" style="width: 100%; color: white; border-collapse: collapse;">
    <thead>
        <tr style="background: rgba(255,255,255,0.05);">
            <th style="padding: 12px;">Ảnh</th>
            <th>Tiêu đề</th>
            <th>Liên kết</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody id="banner-list">
        </tbody>
</table>

<script>
function showAddBannerForm() {
    // Bạn có thể dùng hàm loadContent('add_banner.php') tương tự như add_song.php
}
</script>