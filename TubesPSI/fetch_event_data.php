<?php
header('Content-Type: application/json');
require_once('config/db_connection.php'); // PERBAIKAN: Path langsung ke config

// Pastikan user login (opsional, karena ini hanya menampilkan data publik)
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['events' => []]);
    exit();
}

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');
$gedung_id = $_GET['gedung_id'] ?? '';
$lantai_id = $_GET['lantai_id'] ?? '';

$response = ['events' => []];
$startOfMonth = "$year-$month-01";
$endOfMonth = date('Y-m-t', strtotime($startOfMonth));

try {
    $sql = "
        SELECT DISTINCT
            pe.pengajuan_id,
            pe.pengajuan_namaEvent,
            pe.pengajuan_event_tanggal_mulai,
            pe.pengajuan_event_tanggal_selesai
        FROM pengajuan_event pe
        LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
        LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
        LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
        WHERE 
            pe.pengajuan_status = 'Disetujui'
            AND (
                (pe.pengajuan_event_tanggal_mulai BETWEEN ? AND ?) OR
                (pe.pengajuan_event_tanggal_selesai BETWEEN ? AND ?) OR
                (? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai)
            )
    ";
    
    $params = [$startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth, $startOfMonth];
    $types = "sssss";

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
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $eventStartDate = new DateTime($row['pengajuan_event_tanggal_mulai']);
        $eventEndDate = new DateTime($row['pengajuan_event_tanggal_selesai']);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($eventStartDate, $interval, $eventEndDate->modify('+1 day'));

        foreach ($period as $dt) {
            if ($dt->format('Y-m') === "$year-".str_pad($month, 2, '0', STR_PAD_LEFT)) {
                $day = (int)$dt->format('j');
                if (!isset($response['events'][$day])) {
                    $response['events'][$day] = [];
                }
                $response['events'][$day][] = [
                    'id'   => $row['pengajuan_id'],
                    'name' => htmlspecialchars($row['pengajuan_namaEvent'])
                ];
            }
        }
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Error in fetch_event_data.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($response);
?>