<?php
header('Content-Type: application/json');
include '../config/db_connection.php';

// Validasi input dasar
if (!isset($_GET['lantai_ids']) || !is_array($_GET['lantai_ids']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    echo json_encode([]);
    exit();
}

$lantai_ids = $_GET['lantai_ids'];
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// Jika tidak ada lantai atau tanggal yang valid, kembalikan array kosong
if (empty($lantai_ids) || !$start_date || !$end_date) {
    echo json_encode([]);
    exit();
}

$placeholders = implode(',', array_fill(0, count($lantai_ids), '?'));
$types = str_repeat('i', count($lantai_ids));

// Query untuk mengambil SEMUA ruangan di lantai yang dipilih
$sql_all_ruangan = "
    SELECT r.ruangan_id, r.ruangan_nama, l.lantai_nomor, g.gedung_nama
    FROM ruangan r
    JOIN lantai l ON r.lantai_id = l.lantai_id
    JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE r.lantai_id IN ($placeholders)
";
$stmt_all = $conn->prepare($sql_all_ruangan);
$stmt_all->bind_param($types, ...$lantai_ids);
$stmt_all->execute();
$result_all = $stmt_all->get_result();
$all_ruangan = [];
while ($row = $result_all->fetch_assoc()) {
    $all_ruangan[$row['ruangan_id']] = $row;
}
$stmt_all->close();

// Query untuk mengambil ID ruangan yang TIDAK TERSEDIA (sudah dipesan dan disetujui) pada rentang tanggal yang dipilih
$sql_booked_ruangan = "
    SELECT DISTINCT pr.ruangan_id
    FROM peminjaman_ruangan pr
    JOIN pengajuan_event pe ON pr.pengajuan_id = pe.pengajuan_id
    WHERE pe.pengajuan_status = 'Disetujui' AND (
        (pe.pengajuan_event_tanggal_mulai <= ? AND pe.pengajuan_event_tanggal_selesai >= ?) OR
        (pe.pengajuan_event_tanggal_mulai BETWEEN ? AND ?) OR
        (pe.pengajuan_event_tanggal_selesai BETWEEN ? AND ?)
    )
";
$stmt_booked = $conn->prepare($sql_booked_ruangan);
$stmt_booked->bind_param("ssssss", $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
$stmt_booked->execute();
$result_booked = $stmt_booked->get_result();
$booked_ruangan_ids = [];
while ($row = $result_booked->fetch_assoc()) {
    $booked_ruangan_ids[] = $row['ruangan_id'];
}
$stmt_booked->close();
$conn->close();

// Filter ruangan yang tersedia
$available_ruangan = [];
foreach ($all_ruangan as $ruangan_id => $ruangan_data) {
    // Tambahkan field 'is_available'
    $ruangan_data['is_available'] = !in_array($ruangan_id, $booked_ruangan_ids);
    $available_ruangan[] = $ruangan_data;
}

echo json_encode($available_ruangan);
?>