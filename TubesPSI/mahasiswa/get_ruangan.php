<?php
include '../config/db_connection.php'; // Sesuaikan jalur jika diperlukan

header('Content-Type: application/json'); // Penting untuk menandakan respons JSON

$lantai_id = isset($_GET['lantai_id']) ? intval($_GET['lantai_id']) : 0;

$ruangan = [];
if ($lantai_id > 0) {
    $stmt = $conn->prepare("SELECT ruangan_id, ruangan_nama FROM ruangan WHERE lantai_id = ? ORDER BY ruangan_nama ASC");
    $stmt->bind_param("i", $lantai_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ruangan[] = $row;
    }
    $stmt->close();
}

echo json_encode($ruangan);

$conn->close();
?>