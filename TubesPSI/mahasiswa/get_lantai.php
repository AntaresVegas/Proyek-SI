<?php
include '../config/db_connection.php'; // Sesuaikan jalur jika diperlukan

header('Content-Type: application/json'); // Penting untuk menandakan respons JSON

$gedung_id = isset($_GET['gedung_id']) ? intval($_GET['gedung_id']) : 0;

$lantai = [];
if ($gedung_id > 0) {
    $stmt = $conn->prepare("SELECT lantai_id, lantai_nomor FROM lantai WHERE gedung_id = ? ORDER BY lantai_nomor ASC");
    $stmt->bind_param("i", $gedung_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $lantai[] = $row;
    }
    $stmt->close();
}

echo json_encode($lantai);

$conn->close();
?>