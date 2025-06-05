<?php
session_start();
require_once('../config/db_connection.php'); // Adjust path if necessary

header('Content-Type: application/json');

$events = [];
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$gedungId = isset($_GET['gedung_id']) ? $_GET['gedung_id'] : '';
$lantaiId = isset($_GET['lantai_id']) ? $_GET['lantai_id'] : '';

try {
    if (isset($conn) && $conn->ping()) {
        $query = "
            SELECT 
                pe.pengajuan_id,
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_event_tanggal_selesai,
                g.gedung_id,
                l.lantai_id
            FROM 
                pengajuan_event pe
            LEFT JOIN 
                peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
            LEFT JOIN 
                ruangan r ON pr.ruangan_id = r.ruangan_id
            LEFT JOIN 
                lantai l ON r.lantai_id = l.lantai_id
            LEFT JOIN 
                gedung g ON l.gedung_id = g.gedung_id
            WHERE 
                pe.pengajuan_status = 'Disetujui'
                AND (
                    (pe.pengajuan_event_tanggal_mulai BETWEEN ? AND ?)
                    OR (pe.pengajuan_event_tanggal_selesai BETWEEN ? AND ?)
                    OR (? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai)
                    OR (? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai)
                )
        ";

        $startOfMonth = date('Y-m-01', strtotime("$currentYear-$currentMonth-01"));
        $endOfMonth = date('Y-m-t', strtotime("$currentYear-$currentMonth-01"));

        $params = [$startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth];
        $types = "ssssss";

        if (!empty($gedungId)) {
            $query .= " AND g.gedung_id = ?";
            $params[] = $gedungId;
            $types .= "i";
        }
        if (!empty($lantaiId)) {
            $query .= " AND l.lantai_id = ?";
            $params[] = $lantaiId;
            $types .= "i";
        }

        $query .= " ORDER BY pe.pengajuan_event_tanggal_mulai ASC, pe.pengajuan_event_jam_mulai ASC";

        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $eventStartDate = new DateTime($row['pengajuan_event_tanggal_mulai']);
                $eventEndDate = new DateTime($row['pengajuan_event_tanggal_selesai']);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($eventStartDate, $interval, $eventEndDate->modify('+1 day'));

                foreach ($period as $dt) {
                    $day = (int)$dt->format('j');
                    $month = (int)$dt->format('n');
                    $year = (int)$dt->format('Y');

                    if ($month === $currentMonth && $year === $currentYear) {
                        if (!isset($events[$day])) {
                            $events[$day] = [];
                        }
                        $events[$day][] = [
                            'id' => $row['pengajuan_id'],
                            'name' => htmlspecialchars($row['pengajuan_namaEvent'])
                        ];
                    }
                }
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for fetch_event_data: " . $conn->error);
        }
    } else {
        error_log("Database connection not established or lost in fetch_event_data.php");
    }
} catch (Exception $e) {
    error_log("Error fetching filtered event data: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode(['events' => $events]);
?>