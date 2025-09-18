<?php
header('Content-Type: application/json');
require_once('config/db_connection.php');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$date = $_GET['date'] ?? '';
$gedung_id = $_GET['gedung_id'] ?? '';
$lantai_id = $_GET['lantai_id'] ?? '';

if (empty($date)) {
    echo json_encode([]);
    exit();
}

$response = [];

try {
    $sql = "
        SELECT 
            pe.pengajuan_id AS id, -- ================== TAMBAHKAN BARIS INI ==================
            pe.pengajuan_namaEvent,
            TIME_FORMAT(pe.pengajuan_event_jam_mulai, '%H:%i') AS start_time,
            TIME_FORMAT(pe.pengajuan_event_jam_selesai, '%H:%i') AS end_time,
            GROUP_CONCAT(DISTINCT g.gedung_nama ORDER BY g.gedung_nama SEPARATOR ', ') as gedung,
            GROUP_CONCAT(DISTINCT l.lantai_nomor ORDER BY l.lantai_nomor SEPARATOR ', ') as lantai,
            GROUP_CONCAT(DISTINCT r.ruangan_nama ORDER BY r.ruangan_nama SEPARATOR ', ') as ruangan
        FROM pengajuan_event pe
        LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
        LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
        LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
        WHERE 
            pe.pengajuan_status = 'Disetujui'
            AND ? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai
    ";

    $params = [$date];
    $types = "s";

    if (!empty($gedung_id)) {
        $sql .= " AND g.gedung_id = ?";
        $params[] = $gedung_id;
        $types .= "i";
    }
    if (!empty($lantai_id)) {
        $sql .= " AND l.lantai_id = ?";
        $params[] = $lantai_id;
        $types .= "i";
    }
    
    $sql .= " GROUP BY pe.pengajuan_id ORDER BY pe.pengajuan_event_jam_mulai ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lokasi_parts = [];
            if (!empty($row['gedung'])) $lokasi_parts[] = 'Gedung ' . htmlspecialchars($row['gedung']);
            if (!empty($row['lantai'])) $lokasi_parts[] = 'Lantai ' . htmlspecialchars($row['lantai']);
            if (!empty($row['ruangan'])) $lokasi_parts[] = 'Ruangan ' . htmlspecialchars($row['ruangan']);
            
            $lokasi = !empty($lokasi_parts) ? implode(', ', $lokasi_parts) : 'Lokasi Belum Ditentukan';

            $response[] = [
                'id'         => $row['id'], // ================== TAMBAHKAN BARIS INI ==================
                'name'       => htmlspecialchars($row['pengajuan_namaEvent']),
                'start_time' => htmlspecialchars($row['start_time']),
                'end_time'   => htmlspecialchars($row['end_time']),
                'lokasi'     => $lokasi
            ];
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Error in fetch_event_details.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>