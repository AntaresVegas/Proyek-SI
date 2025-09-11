<?php
header('Content-Type: application/json');
include '../config/db_connection.php';

if (!isset($_GET['lantai_ids']) || !is_array($_GET['lantai_ids'])) {
    echo json_encode([]);
    exit();
}

$lantai_ids = $_GET['lantai_ids'];
$placeholders = implode(',', array_fill(0, count($lantai_ids), '?'));
$types = str_repeat('i', count($lantai_ids));

// Join dengan tabel lantai dan gedung untuk mendapatkan konteks lengkap
$sql = "
    SELECT r.ruangan_id, r.ruangan_nama, l.lantai_nomor, g.gedung_nama
    FROM ruangan r
    JOIN lantai l ON r.lantai_id = l.lantai_id
    JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE r.lantai_id IN ($placeholders)
    ORDER BY g.gedung_nama, l.lantai_nomor, r.ruangan_nama ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$lantai_ids);
$stmt->execute();
$result = $stmt->get_result();

$ruangan_options = [];
while ($row = $result->fetch_assoc()) {
    $ruangan_options[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($ruangan_options);
?>