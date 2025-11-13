<?php
// File: fetch_fullcalendar_events.php
// Lokasi: di folder root (sejajar dengan config/)

header('Content-Type: application/json');
require_once('config/db_connection.php'); 

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

// [BARU] Ambil parameter filter dari GET
$gedung_id = $_GET['gedung_id'] ?? '';
$lantai_id = $_GET['lantai_id'] ?? '';

$events = [];

try {
    // [MODIFIKASI] SQL ini sekarang JAUH lebih kompleks untuk mendukung filter
    // Ini didasarkan pada file fetch_event_details.php Anda
    $sql = "
        SELECT 
            DISTINCT pe.pengajuan_id,
            pe.pengajuan_namaEvent,
            pe.pengajuan_event_tanggal_mulai,
            pe.pengajuan_event_jam_mulai,
            pe.pengajuan_event_tanggal_selesai,
            pe.pengajuan_event_jam_selesai
        FROM pengajuan_event pe
        LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
        LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
        LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
        WHERE 
            pe.pengajuan_status_ditmawa = 'Disetujui'
    ";

    $params = [];
    $types = "";

    // [BARU] Tambahkan filter ke query jika ada
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

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // [BARU] Bind parameter hanya jika ada
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start_datetime = $row['pengajuan_event_tanggal_mulai'] . 'T' . $row['pengajuan_event_jam_mulai'];
            $end_datetime = $row['pengajuan_event_tanggal_selesai'] . 'T' . $row['pengajuan_event_jam_selesai'];

            $events[] = [
                'id'    => $row['pengajuan_id'],
                'title' => htmlspecialchars($row['pengajuan_namaEvent']),
                'start' => $start_datetime,
                'end'   => $end_datetime
            ];
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Error in fetch_fullcalendar_events.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($events);
?>