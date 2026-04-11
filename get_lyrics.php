<?php
$conn = new mysqli("localhost", "root", "vertrigo", "song_management");
$conn->set_charset('utf8mb4');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$res = $conn->query("SELECT Lyrics FROM songs WHERE SongID = $id");

if ($row = $res->fetch_assoc()) {
    echo json_encode(['success' => true, 'lyrics' => $row['Lyrics']]);
} else {
    echo json_encode(['success' => false, 'lyrics' => '']);
}
$conn->close();
?>