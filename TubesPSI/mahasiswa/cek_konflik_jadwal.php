<?php
header('Content-Type: application/json'); // Set header JSON
require_once('../config/db_connection.php'); // Sesuaikan path

$response = [
    'konflik' => [],
    'ruangan_tersedia' => []
];

// 1. Ambil data dari request GET/POST (lebih baik POST)
$ruangan_ids = $_POST['ruangan_ids'] ?? [];
$tanggal_mulai_str = $_POST['tanggal_mulai'] ?? null;
$tanggal_selesai_str = $_POST['tanggal_selesai'] ?? null;
$jam_mulai_str = $_POST['jam_mulai'] ?? null;
$jam_selesai_str = $_POST['jam_selesai'] ?? null;
$pengajuan_id_edit = isset($_POST['pengajuan_id']) ? (int)$_POST['pengajuan_id'] : 0; // Untuk mode edit, agar tidak konflik dgn diri sendiri

// Validasi input dasar
if (empty($ruangan_ids) || !$tanggal_mulai_str || !$tanggal_selesai_str || !$jam_mulai_str || !$jam_selesai_str) {
    echo json_encode($response); // Kembalikan response kosong jika input tidak lengkap
    exit;
}

// Konversi ke format yang benar
$tanggal_mulai = date('Y-m-d', strtotime($tanggal_mulai_str));
$tanggal_selesai = date('Y-m-d', strtotime($tanggal_selesai_str));
$jam_mulai = date('H:i:s', strtotime($jam_mulai_str));
$jam_selesai = date('H:i:s', strtotime($jam_selesai_str));

// Buat daftar hari dari rentang tanggal
$days = [];
$current_date = new DateTime($tanggal_mulai);
$end_date = new DateTime($tanggal_selesai);
$end_date->modify('+1 day'); // Include the end date
$interval = new DateInterval('P1D');
$period = new DatePeriod($current_date, $interval, $end_date);
$dayMap = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
foreach ($period as $date) {
    $dayShort = $date->format('D');
    if (isset($dayMap[$dayShort])) {
        $days[] = $dayMap[$dayShort];
    }
}
$days = array_unique($days);

if (empty($days)) { // Jika rentang tanggal tidak valid atau hanya hari Minggu
    echo json_encode($response);
    exit;
}

$ruangan_tersedia = array_flip($ruangan_ids); // Asumsikan semua tersedia awalnya

try {
    // 2. Query Cek Jadwal Kelas
    if (!empty($ruangan_ids)) {
        $placeholders_ruangan = implode(',', array_fill(0, count($ruangan_ids), '?'));
        $placeholders_hari = implode(',', array_fill(0, count($days), '?'));
        
        $sql_kelas = "
            SELECT jk.ruangan_id, jk.hari, jk.jam_mulai, jk.jam_selesai, jk.nama_matakuliah, r.ruangan_nama
            FROM jadwal_kelas jk
            JOIN ruangan r ON jk.ruangan_id = r.ruangan_id
            WHERE jk.ruangan_id IN ($placeholders_ruangan)
              AND jk.hari IN ($placeholders_hari)
              AND (
                  (jk.jam_mulai < ? AND jk.jam_selesai > ?) OR -- Overlap
                  (jk.jam_mulai >= ? AND jk.jam_mulai < ?) OR  -- Event mulai saat kelas berjalan
                  (jk.jam_selesai > ? AND jk.jam_selesai <= ?) -- Event selesai saat kelas berjalan
              )
            -- Optional: AND jk.semester_tahun = 'Ganjil 2025/2026' -- Tambahkan jika perlu filter semester
        ";

        $params_kelas = array_merge($ruangan_ids, $days, [$jam_selesai, $jam_mulai, $jam_mulai, $jam_selesai, $jam_mulai, $jam_selesai]);
        $types_kelas = str_repeat('i', count($ruangan_ids)) . str_repeat('s', count($days)) . 'ssssss';

        $stmt_kelas = $conn->prepare($sql_kelas);
        if ($stmt_kelas) {
            $stmt_kelas->bind_param($types_kelas, ...$params_kelas);
            $stmt_kelas->execute();
            $result_kelas = $stmt_kelas->get_result();
            while ($row = $result_kelas->fetch_assoc()) {
                $response['konflik'][] = [
                    'ruangan_id' => $row['ruangan_id'],
                    'ruangan_nama' => $row['ruangan_nama'],
                    'tipe' => 'kelas',
                    'detail' => $row['hari'] . ', ' . date('H:i', strtotime($row['jam_mulai'])) . '-' . date('H:i', strtotime($row['jam_selesai'])) . ($row['nama_matakuliah'] ? ' - ' . $row['nama_matakuliah'] : '')
                ];
                 if (isset($ruangan_tersedia[$row['ruangan_id']])) unset($ruangan_tersedia[$row['ruangan_id']]); // Hapus dari yg tersedia
            }
            $stmt_kelas->close();
        } else {
             // Handle error prepare statement
             error_log("Error preparing statement kelas: " . $conn->error);
        }
    }

    // 3. Query Cek Event Lain (yang status proposalnya Diajukan atau Disetujui)
    if (!empty($ruangan_ids)) {
        $placeholders_ruangan = implode(',', array_fill(0, count($ruangan_ids), '?'));
        
        $sql_event = "
            SELECT pr.ruangan_id, pe.pengajuan_namaEvent, pe.pengajuan_event_tanggal_mulai, pe.pengajuan_event_tanggal_selesai, pe.pengajuan_event_jam_mulai, pe.pengajuan_event_jam_selesai, r.ruangan_nama
            FROM peminjaman_ruangan pr
            JOIN pengajuan_event pe ON pr.pengajuan_id = pe.pengajuan_id
            JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
            WHERE pr.ruangan_id IN ($placeholders_ruangan)
              AND pe.pengajuan_id != ? -- Jangan cek konflik dengan diri sendiri (jika edit)
              AND (pe.pengajuan_status_proposal = 'Disetujui' OR pe.pengajuan_status_proposal = 'Diajukan') 
              AND (
                  -- Cek overlap tanggal
                  (pe.pengajuan_event_tanggal_mulai <= ? AND pe.pengajuan_event_tanggal_selesai >= ?) OR -- Event lain mencakup tanggal event ini
                  (pe.pengajuan_event_tanggal_mulai >= ? AND pe.pengajuan_event_tanggal_mulai <= ?) OR -- Event lain mulai di dalam rentang event ini
                  (pe.pengajuan_event_tanggal_selesai >= ? AND pe.pengajuan_event_tanggal_selesai <= ?) -- Event lain selesai di dalam rentang event ini
              )
              AND (
                  -- Cek overlap waktu
                  (pe.pengajuan_event_jam_mulai < ? AND pe.pengajuan_event_jam_selesai > ?) OR -- Overlap waktu
                  (pe.pengajuan_event_jam_mulai >= ? AND pe.pengajuan_event_jam_mulai < ?) OR  -- Event lain mulai saat event ini berjalan
                  (pe.pengajuan_event_jam_selesai > ? AND pe.pengajuan_event_jam_selesai <= ?) -- Event lain selesai saat event ini berjalan
              )
        ";
        
        $params_event = array_merge($ruangan_ids, [$pengajuan_id_edit, $tanggal_selesai, $tanggal_mulai, $tanggal_mulai, $tanggal_selesai, $tanggal_mulai, $tanggal_selesai, $jam_selesai, $jam_mulai, $jam_mulai, $jam_selesai, $jam_mulai, $jam_selesai]);
        $types_event = str_repeat('i', count($ruangan_ids)) . 'issssssssssss';

        $stmt_event = $conn->prepare($sql_event);
         if ($stmt_event) {
            $stmt_event->bind_param($types_event, ...$params_event);
            $stmt_event->execute();
            $result_event = $stmt_event->get_result();
            while ($row = $result_event->fetch_assoc()) {
                 $response['konflik'][] = [
                    'ruangan_id' => $row['ruangan_id'],
                    'ruangan_nama' => $row['ruangan_nama'],
                    'tipe' => 'event',
                    'detail' => date('d M', strtotime($row['pengajuan_event_tanggal_mulai'])) . ', ' . date('H:i', strtotime($row['pengajuan_event_jam_mulai'])) . '-' . date('H:i', strtotime($row['pengajuan_event_jam_selesai'])) . ' - ' . $row['pengajuan_namaEvent']
                 ];
                 if (isset($ruangan_tersedia[$row['ruangan_id']])) unset($ruangan_tersedia[$row['ruangan_id']]);
            }
            $stmt_event->close();
         } else {
             // Handle error prepare statement
             error_log("Error preparing statement event: " . $conn->error);
         }
    }

} catch (Exception $e) {
    // Log error
    error_log("Error checking konflik: " . $e->getMessage());
    // Anda bisa menambahkan 'error' key di response jika perlu
    // $response['error'] = "Terjadi kesalahan pada server.";
}

$conn->close();

$response['ruangan_tersedia'] = array_keys($ruangan_tersedia); // Ambil ID ruangan yg tidak ada konflik
echo json_encode($response);
?>