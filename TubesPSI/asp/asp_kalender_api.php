<?php
// File: asp_kalender_api.php
header('Content-Type: application/json');
require_once('../config/db_connection.php'); 

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    echo json_encode([]);
    exit();
}

$gedung_id = $_GET['gedung_id'] ?? '';
$lantai_id = $_GET['lantai_id'] ?? '';

$events = [];

try {
    // [FIX] Query diubah agar SAMA seperti Sekretariat
    $sql = "
        SELECT 
            pe.pengajuan_id,
            pe.pengajuan_namaEvent,
            pe.pengajuan_event_tanggal_mulai,
            pe.pengajuan_event_jam_mulai,
            pe.pengajuan_event_tanggal_selesai,
            pe.pengajuan_event_jam_selesai,
            pe.tanggal_persiapan,
            pe.tanggal_beres,
            GROUP_CONCAT(DISTINCT CONCAT('• ', r.ruangan_nama, ' (', g.gedung_nama, ' - Lt. ', l.lantai_nomor, ')') SEPARATOR '\n') AS lokasi_event
        FROM pengajuan_event pe
        LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
        LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
        LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
        WHERE 
            pe.pengajuan_status_proposal = 'Disetujui' 
    "; // DIUBAH DARI status_ditmawa menjadi status_proposal

    $params = [];
    $types = "";

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

    $sql .= " GROUP BY pe.pengajuan_id, pe.pengajuan_namaEvent, 
                     pe.pengajuan_event_tanggal_mulai, pe.pengajuan_event_jam_mulai, 
                     pe.pengajuan_event_tanggal_selesai, pe.pengajuan_event_jam_selesai,
                     pe.tanggal_persiapan, pe.tanggal_beres";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        // Logika untuk memproses 3 JENIS event (Main, Prep, Clear)
        while ($row = $result->fetch_assoc()) {
            $event_id = $row['pengajuan_id'];
            $event_name = htmlspecialchars($row['pengajuan_namaEvent']);
            $location_string = htmlspecialchars($row['lokasi_event'] ?? '');
            
            $full_title_main = $event_name;
            if (!empty($location_string)) {
                $full_title_main .= "\n" . $location_string;
            }

            // --- 1. Event Utama (Dengan Waktu) ---
            $events[] = [
                'id'              => $event_id,
                'title'           => $full_title_main,
                'start'           => $row['pengajuan_event_tanggal_mulai'] . 'T' . $row['pengajuan_event_jam_mulai'],
                'end'             => $row['pengajuan_event_tanggal_selesai'] . 'T' . $row['pengajuan_event_jam_selesai'],
                'backgroundColor' => '#17a2b8', // Warna Biru (Info) dari legenda ASP
                'borderColor'     => '#17a2b8'
            ];

            $main_start_dt = new DateTime($row['pengajuan_event_tanggal_mulai']);
            $main_end_dt = new DateTime($row['pengajuan_event_tanggal_selesai']);

            // --- 2. Event Persiapan (All-day) ---
            if (!empty($row['tanggal_persiapan'])) {
                $prep_start_dt = new DateTime($row['tanggal_persiapan']);
                if ($prep_start_dt < $main_start_dt) {
                    $prep_end_date = $main_start_dt->format('Y-m-d'); 
                    $events[] = [
                        'id'              => $event_id,
                        'title'           => $event_name . " (Persiapan)\n" . $location_string,
                        'start'           => $prep_start_dt->format('Y-m-d'),
                        'end'             => $prep_end_date,
                        'allDay'          => true,
                        'backgroundColor' => '#6c757d', // Warna Abu-abu (Secondary) dari legenda ASP
                        'borderColor'     => '#6c757d'
                    ];
                }
            }
            
            // --- 3. Event Pembongkaran (All-day) ---
            if (!empty($row['tanggal_beres'])) {
                $clear_end_dt = new DateTime($row['tanggal_beres']);
                if ($clear_end_dt > $main_end_dt) {
                    $clear_start_dt = (clone $main_end_dt)->modify('+1 day');
                    $clear_end_date = (clone $clear_end_dt)->modify('+1 day')->format('Y-m-d');
                    $events[] = [
                        'id'              => $event_id,
                        'title'           => $event_name . " (Pembongkaran)\n" . $location_string,
                        'start'           => $clear_start_dt->format('Y-m-d'),
                        'end'             => $clear_end_date,
                        'allDay'          => true,
                        'backgroundColor' => '#6c757d', // Warna Abu-abu (Secondary) dari legenda ASP
                        'borderColor'     => '#6c757d'
                    ];
                }
            }
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Error in asp_kalender_api.php: " . $e->getMessage());
}

$conn->close();
echo json_encode($events);
?>