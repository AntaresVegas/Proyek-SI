<?php
header('Content-Type: application/json');
// [FIX] Mengubah path require_once agar lebih konsisten
require_once(__DIR__ . '/../config/db_connection.php');

if (!isset($_GET['gedung_ids']) || !is_array($_GET['gedung_ids'])) {
    echo json_encode(['error' => 'Parameter gedung_ids tidak valid.']);
    exit;
}

$gedung_ids = $_GET['gedung_ids'];
if (empty($gedung_ids)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($gedung_ids), '?'));
$types = str_repeat('i', count($gedung_ids));

// [FIX] Memperbaiki urutan gedung agar numerik
$sql = "
    SELECT l.lantai_id as id, l.lantai_nomor, g.gedung_nama 
    FROM lantai l
    JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE l.gedung_id IN ($placeholders)
    ORDER BY LENGTH(g.gedung_nama), g.gedung_nama, CAST(l.lantai_nomor AS UNSIGNED)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$gedung_ids);
$stmt->execute();
$result = $stmt->get_result();

$lantai_data = [];
while ($row = $result->fetch_assoc()) {
    $lantai_data[] = [
        'id' => $row['id'],
        'name' => "Lantai " . htmlspecialchars($row['lantai_nomor']) . " (" . htmlspecialchars($row['gedung_nama']) . ")"
    ];
}

$stmt->close();
$conn->close();

echo json_encode($lantai_data);
?>