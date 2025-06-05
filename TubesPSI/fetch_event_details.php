<?php
session_start();
require_once('../config/db_connection.php'); // Adjust path if necessary

header('Content-Type: application/json');

$response = [];

if (!isset($_GET['date'])) {
    echo json_encode([]);
    exit();
}

$selectedDate = $_GET['date'];
$gedungId = isset($_GET['gedung_id']) ? $_GET['gedung_id'] : '';
$lantaiId = isset($_GET['lantai_id']) ? $_GET['lantai_id'] : '';

try {
    if (isset($conn) && $conn->ping()) {
        $query = "
            SELECT 
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_jam_mulai,
                pe.pengajuan_event_jam_selesai,
                COALESCE(g.gedung_nama, 'N/A') AS gedung_nama,
                COALESCE(l.lantai_nomor, 'N/A') AS lantai_nomor,
                COALESCE(r.ruangan_nama, 'N/A') AS ruangan_nama
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
                AND ? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai
        ";

        $params = [$selectedDate];
        $types = "s";

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

        $query .= " ORDER BY pe.pengajuan_event_jam_mulai ASC";

        $stmt = $conn->prepare($query);

        if ($stmt) {
            // Use call_user_func_array for bind_param with dynamic parameters
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $response[] = [
                    'name' => htmlspecialchars($row['pengajuan_namaEvent']),
                    'start_time' => htmlspecialchars(substr($row['pengajuan_event_jam_mulai'], 0, 5)), // Format to HH:MM
                    'end_time' => htmlspecialchars(substr($row['pengajuan_event_jam_selesai'], 0, 5)),   // Format to HH:MM
                    'gedung' => htmlspecialchars($row['gedung_nama']),
                    'lantai' => htmlspecialchars($row['lantai_nomor']),
                    'ruangan' => htmlspecialchars($row['ruangan_nama'])
                ];
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for fetch_event_details: " . $conn->error);
            // Optionally, return an error message in JSON
            // $response = ['error' => 'Failed to prepare statement.'];
        }
    } else {
        error_log("Database connection not established or lost in fetch_event_details.php");
        // $response = ['error' => 'Database connection error.'];
    }
} catch (Exception $e) {
    error_log("Error fetching event details: " . $e->getMessage());
    // $response = ['error' => 'An unexpected error occurred.'];
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>