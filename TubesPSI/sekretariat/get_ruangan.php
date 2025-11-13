<?php
header('Content-Type: application/json');
// [FIX] Mengubah path require_once agar lebih konsisten
require_once(__DIR__ . '/../config/db_connection.php');

if (!isset($_GET['lantai_ids']) || !is_array($_GET['lantai_ids'])) {
    echo json_encode(['error' => 'Parameter lantai_ids tidak valid.']);
    exit;
}

$lantai_ids = $_GET['lantai_ids'];
if (empty($lantai_ids)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($lantai_ids), '?'));
$types = str_repeat('i', count($lantai_ids));

// [FIX] Memperbaiki urutan gedung dan lantai agar numerik
$sql = "
    SELECT r.ruangan_id as id, r.ruangan_nama, l.lantai_nomor, g.gedung_nama
    FROM ruangan r
    JOIN lantai l ON r.lantai_id = l.lantai_id
    JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE r.lantai_id IN ($placeholders)
    ORDER BY LENGTH(g.gedung_nama), g.gedung_nama, CAST(l.lantai_nomor AS UNSIGNED), r.ruangan_nama
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$lantai_ids);
$stmt->execute();
$result = $stmt->get_result();

$ruangan_data = [];
while ($row = $result->fetch_assoc()) {
    $ruangan_data[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars($row['ruangan_nama']) . " (Lantai " . htmlspecialchars($row['lantai_nomor']) . ", " . htmlspecialchars($row['gedung_nama']) . ")"
    ];
}

$stmt->close();
$conn->close();

echo json_encode($ruangan_data);
?>