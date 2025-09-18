<?php
session_start();
include '../config/db_connection.php';

// 1. Otentikasi & Otorisasi Pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'] ?? 'User';
$email = $_SESSION['username'] ?? 'No email';
$message = '';
$message_type = '';
$event_data = null;
$selected_ruangan_ids_str = '';

$pengajuan_id = $_GET['id'] ?? null;
if (!$pengajuan_id) {
    die("Error: ID Pengajuan tidak valid.");
}

// 2. Logika UPDATE saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_status_stmt = $conn->prepare("SELECT pengajuan_status FROM pengajuan_event WHERE pengajuan_id = ? AND pengaju_id = ? AND pengaju_tipe = 'mahasiswa'");
    $check_status_stmt->bind_param("ii", $pengajuan_id, $user_id);
    $check_status_stmt->execute();
    $status_result = $check_status_stmt->get_result()->fetch_assoc();
    $check_status_stmt->close();

    if ($status_result && $status_result['pengajuan_status'] === 'Ditolak') {
        $pengajuan_namaEvent = $_POST['pengajuan_namaEvent'];
        $pengajuan_TypeKegiatan_raw = $_POST['pengajuan_TypeKegiatan_select'];
        if ($pengajuan_TypeKegiatan_raw === 'Lainnya') {
            $pengajuan_TypeKegiatan = $_POST['pengajuan_TypeKegiatan_Lainnya'];
        } else {
            $pengajuan_TypeKegiatan = $pengajuan_TypeKegiatan_raw;
        }

        $pengajuan_event_jam_mulai = $_POST['pengajuan_event_jam_mulai'];
        $pengajuan_event_jam_selesai = $_POST['pengajuan_event_jam_selesai'];
        $pengajuan_event_tanggal_mulai = $_POST['pengajuan_event_tanggal_mulai'];
        $pengajuan_event_tanggal_selesai = $_POST['pengajuan_event_tanggal_selesai'];
        $tanggal_persiapan = !empty($_POST['tanggal_persiapan']) ? $_POST['tanggal_persiapan'] : null;
        $tanggal_beres = !empty($_POST['tanggal_beres']) ? $_POST['tanggal_beres'] : null;

        $new_selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : [];
        $rundown_file_path = $_POST['existing_rundown_file'];
        $proposal_file_path = $_POST['existing_proposal_file'];

        if (isset($_FILES['jadwal_event_rundown_file']) && $_FILES['jadwal_event_rundown_file']['error'] == UPLOAD_ERR_OK) {
            $target_dir_rundown = '../uploads/rundown/';
            if (!is_dir($target_dir_rundown)) { mkdir($target_dir_rundown, 0777, true); }
            $rundown_file_path = 'uploads/rundown/' . uniqid() . "_" . basename($_FILES["jadwal_event_rundown_file"]["name"]);
            move_uploaded_file($_FILES["jadwal_event_rundown_file"]["tmp_name"], $rundown_file_path);
        }
        if (isset($_FILES['pengajuan_event_proposal_file']) && $_FILES['pengajuan_event_proposal_file']['error'] == UPLOAD_ERR_OK) {
            $target_dir_proposal = '../uploads/proposal/';
            if (!is_dir($target_dir_proposal)) { mkdir($target_dir_proposal, 0777, true); }
            $proposal_file_path = 'uploads/proposal/' . uniqid() . "_" . basename($_FILES["pengajuan_event_proposal_file"]["name"]);
            move_uploaded_file($_FILES["pengajuan_event_proposal_file"]["tmp_name"], $proposal_file_path);
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_namaEvent = ?, pengajuan_TypeKegiatan = ?, pengajuan_event_jam_mulai = ?, pengajuan_event_jam_selesai = ?, pengajuan_event_tanggal_mulai = ?, pengajuan_event_tanggal_selesai = ?, tanggal_persiapan = ?, tanggal_beres = ?, jadwal_event_rundown_file = ?, pengajuan_event_proposal_file = ?, pengajuan_status = 'Diajukan', pengajuan_tanggalEdit = NOW(), pengajuan_komentarDitmawa = NULL WHERE pengajuan_id = ? AND pengaju_id = ? AND pengaju_tipe = 'mahasiswa'");
            $stmt->bind_param("ssssssssssii", $pengajuan_namaEvent, $pengajuan_TypeKegiatan, $pengajuan_event_jam_mulai, $pengajuan_event_jam_selesai, $pengajuan_event_tanggal_mulai, $pengajuan_event_tanggal_selesai, $tanggal_persiapan, $tanggal_beres, $rundown_file_path, $proposal_file_path, $pengajuan_id, $user_id);
            $stmt->execute();
            
            $conn->query("DELETE FROM peminjaman_ruangan WHERE pengajuan_id = $pengajuan_id");
            if (!empty($new_selected_ruangan_ids)) {
                $insert_peminjaman_stmt = $conn->prepare("INSERT INTO peminjaman_ruangan (pengajuan_id, ruangan_id) VALUES (?, ?)");
                foreach ($new_selected_ruangan_ids as $ruangan_id) {
                    $insert_peminjaman_stmt->bind_param("ii", $pengajuan_id, $ruangan_id);
                    $insert_peminjaman_stmt->execute();
                }
                $insert_peminjaman_stmt->close();
            }
            $conn->commit();
            header("Location: mahasiswa_history_pengajuan.php?status=re-submitted");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "Error: Pengajuan ini tidak dapat diubah.";
        $message_type = 'error';
    }
}

// 3. Ambil data event dari database
$stmt = $conn->prepare("
    SELECT pe.*, m.mahasiswa_npm 
    FROM pengajuan_event pe 
    JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id 
    WHERE pe.pengajuan_id = ? AND pe.pengaju_id = ? AND pe.pengaju_tipe = 'mahasiswa'
");
$stmt->bind_param("ii", $pengajuan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$event_data = $result->num_rows > 0 ? $result->fetch_assoc() : die("Error: Anda tidak memiliki akses ke event ini atau event tidak ditemukan.");
$stmt->close();

// ================================================
// ## LOGIKA DIPERBAIKI: Kapan form bisa diedit ##
// Form hanya bisa diedit jika statusnya 'Ditolak'.
// ================================================
$is_editable = ($event_data['pengajuan_status'] === 'Ditolak');

// 4. Ambil semua data lokasi yang diperlukan
$selected_ruangan_ids = [];
$stmt_ruangan = $conn->prepare("SELECT ruangan_id FROM peminjaman_ruangan WHERE pengajuan_id = ?");
$stmt_ruangan->bind_param("i", $pengajuan_id);
$stmt_ruangan->execute();
$result_ruangan = $stmt_ruangan->get_result();
while($row = $result_ruangan->fetch_assoc()){
    $selected_ruangan_ids[] = $row['ruangan_id'];
}
$stmt_ruangan->close();
$selected_ruangan_ids_str = json_encode($selected_ruangan_ids);

$gedung_options = [];
$all_locations_data = []; 
$selected_locations_list = [];

$result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY CAST(SUBSTRING(gedung_nama, 7) AS UNSIGNED) ASC");
while ($row = $result_gedung->fetch_assoc()) $gedung_options[] = $row;
    
$location_query = $conn->query("SELECT g.gedung_id, g.gedung_nama, l.lantai_id, l.lantai_nomor, r.ruangan_id, r.ruangan_nama FROM gedung g LEFT JOIN lantai l ON g.gedung_id = l.gedung_id LEFT JOIN ruangan r ON l.lantai_id = r.lantai_id ORDER BY CAST(SUBSTRING(g.gedung_nama, 7) AS UNSIGNED), l.lantai_nomor, r.ruangan_nama");
while ($row = $location_query->fetch_assoc()) $all_locations_data[] = $row;

if (!empty($selected_ruangan_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ruangan_ids), '?'));
    $types = str_repeat('i', count($selected_ruangan_ids));
    $stmt_list = $conn->prepare("SELECT g.gedung_nama, l.lantai_nomor, r.ruangan_nama FROM ruangan r JOIN lantai l ON r.lantai_id = l.lantai_id JOIN gedung g ON l.gedung_id = g.gedung_id WHERE r.ruangan_id IN ($placeholders) ORDER BY CAST(SUBSTRING(g.gedung_nama, 7) AS UNSIGNED), l.lantai_nomor, r.ruangan_nama");
    $stmt_list->bind_param($types, ...$selected_ruangan_ids);
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    while($row = $result_list->fetch_assoc()) $selected_locations_list[] = $row;
    $stmt_list->close();
}
$all_locations_data_str = json_encode($all_locations_data);
$conn->close();

$predefined_types = ['Seminar', 'Workshop', 'Lomba', 'Pameran'];
$is_type_lainnya = !in_array($event_data['pengajuan_TypeKegiatan'], $predefined_types);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Edit Pengajuan Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 80px;background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;}
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-left { gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { color:rgb(255, 255, 255); }
        .container { max-width: 900px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .main-content { padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; border-radius: 15px 15px 0 0; text-align: center;}
        .header h1 { font-size: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; background-color: #f8f8f8; }
        .form-group input:disabled, .form-group select:disabled, .form-group textarea:disabled { background-color: #e9ecef; cursor: not-allowed; }
        .button-group { display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; }
        .btn-submit { padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #28a745; color: white; }
        .btn-kembali { padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #6c757d; color: white; text-decoration: none;}
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;}
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
        .current-file { margin-bottom: 8px; font-size: 14px; color: #555; }
        .current-file a { color: #007bff; }
        .form-control-file { display: block; width: 100%; }
        .status-notice { padding: 15px 20px; margin-bottom: 25px; border-left: 5px solid; border-radius: 5px; }
        .status-notice h4 { margin-bottom: 10px; }
        .status-notice-ditolak { background-color: #fff3cd; border-color: #ffc107; color: #856404; }
        .status-notice-diajukan { background-color: #cce5ff; border-color: #007bff; color: #004085; }
        .status-notice-disetujui { background-color: #d4edda; border-color: #28a745; color: #155724; }
        .date-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px;}
        
        /* ================================================ */
        /* ## CSS BARU: Mengadopsi dari mahasiswa_pengajuan.php ## */
        /* ================================================ */
        .checkbox-placeholder { background-color: #f8f9fa; border-radius: 5px; padding: 15px; color: #6c757d; border: 1px dashed #dee2e6; }
        .checkbox-group-modern { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; border: 1px solid #ccc; border-radius: 5px; padding: 10px; max-height: 150px; overflow-y: auto; background-color: #f8f8f8; }
        .checkbox-group-modern.disabled { background-color: #e9ecef; }
        .checkbox-item { display: flex; align-items: center; position: relative; }
        .checkbox-item input[type="checkbox"] { opacity: 0; position: absolute; width: 0; height: 0; }
        .checkbox-item label { display: flex; align-items: center; cursor: pointer; font-weight: normal; color: #495057; margin-bottom: 0; width: 100%; }
        .checkbox-item label::before { content: ''; width: 20px; height: 20px; border: 2px solid #adb5bd; border-radius: 4px; margin-right: 12px; transition: all 0.2s ease; flex-shrink: 0; }
        .checkbox-item input[type="checkbox"]:checked + label::before { background-color: #007bff; border-color: #007bff; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: center; background-size: 60%; }
        .checkbox-item label:hover::before { border-color: #007bff; }
        .checkbox-item input[type="checkbox"]:focus + label::before { box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); }
        .static-list-box { background-color: #e9ecef; border: 1px solid #ced4da; border-radius: 5px; padding: 15px; min-height: 50px; }
        .static-list-box p { margin: 0 0 8px 0; padding-left: 10px; border-left: 3px solid #adb5bd; font-size: 16px; }
        .static-list-box p:last-child { margin-bottom: 0; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left"><img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo"><div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div></div>
    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php" class="active">History</a></li>
    </ul>
    <div class="navbar-right"><a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left:15px;"></i></a><a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i></a></div>
</nav>

<div class="container">
    <div class="header"><h1>Detail & Edit Pengajuan Event</h1></div>
    <div class="main-content">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($event_data['pengajuan_status'] == 'Ditolak'): ?>
            <div class="status-notice status-notice-ditolak"><h4><i class="fas fa-exclamation-triangle"></i> Pengajuan Ditolak</h4><p>Alasan: <strong><?php echo htmlspecialchars($event_data['pengajuan_komentarDitmawa'] ?: 'Tidak ada komentar.'); ?></strong><br>Silakan perbaiki data di bawah ini dan ajukan kembali.</p></div>
        <?php elseif ($event_data['pengajuan_status'] == 'Diajukan'): ?>
            <div class="status-notice status-notice-diajukan"><h4><i class="fas fa-info-circle"></i> Status: Diajukan</h4><p>Pengajuan sedang ditinjau dan tidak dapat diubah.</p></div>
        <?php elseif ($event_data['pengajuan_status'] == 'Disetujui'): ?>
            <div class="status-notice status-notice-disetujui"><h4><i class="fas fa-check-circle"></i> Status: Disetujui</h4><p>Pengajuan telah disetujui dan tidak dapat diubah.</p></div>
        <?php endif; ?>
        
        <form id="eventForm" method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Nama Penanggung Jawab</label><input type="text" value="<?php echo htmlspecialchars($nama); ?>" readonly></div>
            <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly></div>
            <div class="form-group"><label>NPM</label><input type="text" value="<?php echo htmlspecialchars($event_data['mahasiswa_npm']); ?>" readonly></div>
            <div class="form-group"><label for="pengajuan_namaEvent">Nama Event</label><input type="text" id="pengajuan_namaEvent" name="pengajuan_namaEvent" value="<?php echo htmlspecialchars($event_data['pengajuan_namaEvent']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            
            <div class="form-group">
                <label for="pengajuan_TypeKegiatan_select">Tipe Kegiatan</label>
                <select id="pengajuan_TypeKegiatan_select" name="pengajuan_TypeKegiatan_select" <?php if (!$is_editable) echo 'disabled'; ?> required>
                    <option value="Seminar" <?php echo ($event_data['pengajuan_TypeKegiatan'] == 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                    <option value="Workshop" <?php echo ($event_data['pengajuan_TypeKegiatan'] == 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                    <option value="Lomba" <?php echo ($event_data['pengajuan_TypeKegiatan'] == 'Lomba') ? 'selected' : ''; ?>>Lomba</option>
                    <option value="Pameran" <?php echo ($event_data['pengajuan_TypeKegiatan'] == 'Pameran') ? 'selected' : ''; ?>>Pameran</option>
                    <option value="Lainnya" <?php echo $is_type_lainnya ? 'selected' : ''; ?>>Lainnya</option>
                </select>
            </div>
            <div class="form-group" id="type_kegiatan_lainnya_container" style="<?php echo $is_type_lainnya ? '' : 'display:none;'; ?>">
                <label for="pengajuan_TypeKegiatan_Lainnya">Sebutkan Tipe Kegiatan Lainnya</label>
                <input type="text" id="pengajuan_TypeKegiatan_Lainnya" name="pengajuan_TypeKegiatan_Lainnya" value="<?php echo $is_type_lainnya ? htmlspecialchars($event_data['pengajuan_TypeKegiatan']) : ''; ?>" <?php if (!$is_editable) echo 'disabled'; ?>>
            </div>

            <hr style="margin: 25px 0;">
            
            <div class="form-group">
                <label>Gedung & Ruangan Terpilih</label>
                <div id="location_selection_area">
                    </div>
            </div>

            <div class="date-grid">
                <div class="form-group"><label for="pengajuan_event_jam_mulai">Jam Mulai</label><input type="time" id="pengajuan_event_jam_mulai" name="pengajuan_event_jam_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_mulai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
                <div class="form-group"><label for="pengajuan_event_jam_selesai">Jam Selesai</label><input type="time" id="pengajuan_event_jam_selesai" name="pengajuan_event_jam_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_selesai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
                <div class="form-group"><label for="pengajuan_event_tanggal_mulai">Tanggal Mulai Acara</label><input type="date" id="pengajuan_event_tanggal_mulai" name="pengajuan_event_tanggal_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_mulai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
                <div class="form-group"><label for="pengajuan_event_tanggal_selesai">Tanggal Selesai Acara</label><input type="date" id="pengajuan_event_tanggal_selesai" name="pengajuan_event_tanggal_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_selesai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
                <div class="form-group"><label for="tanggal_persiapan">Tanggal Persiapan (Opsional)</label><input type="date" id="tanggal_persiapan" name="tanggal_persiapan" value="<?php echo htmlspecialchars($event_data['tanggal_persiapan']); ?>" <?php if (!$is_editable) echo 'disabled'; ?>></div>
                <div class="form-group"><label for="tanggal_beres">Tanggal Beres-Beres (Opsional)</label><input type="date" id="tanggal_beres" name="tanggal_beres" value="<?php echo htmlspecialchars($event_data['tanggal_beres']); ?>" <?php if (!$is_editable) echo 'disabled'; ?>></div>
            </div>
            
            <input type="hidden" name="existing_rundown_file" value="<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>">
            <input type="hidden" name="existing_proposal_file" value="<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>">

            <div class="form-group">
                <label for="jadwal_event_rundown_file">Rundown Acara</label>
                <div class="current-file">File saat ini: <a href="../<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>" target="_blank"><?php echo basename($event_data['jadwal_event_rundown_file']); ?></a></div>
                <?php if ($is_editable): ?>
                    <p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 5px;">Pilih file baru di bawah ini hanya jika Anda ingin menggantinya.</p>
                    <input type="file" id="jadwal_event_rundown_file" name="jadwal_event_rundown_file" class="form-control-file">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="pengajuan_event_proposal_file">Proposal Kegiatan</label>
                <div class="current-file">File saat ini: <a href="../<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>" target="_blank"><?php echo basename($event_data['pengajuan_event_proposal_file']); ?></a></div>
                <?php if ($is_editable): ?>
                    <p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 5px;">Pilih file baru di bawah ini hanya jika Anda ingin menggantinya.</p>
                    <input type="file" id="pengajuan_event_proposal_file" name="pengajuan_event_proposal_file" class="form-control-file">
                <?php endif; ?>
            </div>

            <div class="button-group">
                <a href="mahasiswa_history_pengajuan.php" class="btn-kembali">Kembali</a>
                <?php if ($is_editable): ?>
                    <button type="submit" class="btn-submit">Simpan & Ajukan Ulang</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isEditable = <?php echo json_encode($is_editable); ?>;
    const allLocations = <?php echo $all_locations_data_str; ?>;
    const preselectedRuanganIds = <?php echo $selected_ruangan_ids_str; ?>.map(String);
    const locationArea = document.getElementById('location_selection_area');

    if (isEditable) {
        // Mode EDIT: Tampilkan UI interaktif dengan gaya modern
        let editableHtml = `
            <div class="form-group">
                <label>Pilih Gedung</label>
                <div id="gedung_selection" class="checkbox-group-modern">
                    <?php foreach ($gedung_options as $gedung): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>">
                            <label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"> <?php echo htmlspecialchars($gedung['gedung_nama']); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="lantai_selection_container">Pilih Lantai</label>
                <div id="lantai_selection_container"><div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div></div>
            </div>
            <div class="form-group">
                <label for="ruangan_selection_container">Pilih Ruangan</label>
                <div id="ruangan_selection_container"><div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div></div>
            </div>
        `;
        locationArea.innerHTML = editableHtml;

        const gedungSelection = document.getElementById('gedung_selection');
        const lantaiContainer = document.getElementById('lantai_selection_container');
        const ruanganContainer = document.getElementById('ruangan_selection_container');

        function populateLantai(gedungIds, lantaiToSelect = []) {
            const relevantLantai = allLocations.filter(loc => loc.lantai_id && gedungIds.includes(String(loc.gedung_id)));
            const uniqueLantai = Array.from(new Map(relevantLantai.map(item => [item['lantai_id'], item])).values());
            
            if (uniqueLantai.length > 0) {
                let html = '<div id="lantai_selection" class="checkbox-group-modern">';
                uniqueLantai.forEach(lantai => {
                    const checked = lantaiToSelect.includes(String(lantai.lantai_id)) ? 'checked' : '';
                    html += `<div class="checkbox-item"><input type="checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}" ${checked}><label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (${lantai.gedung_nama})</label></div>`;
                });
                html += '</div>';
                lantaiContainer.innerHTML = html;
            } else {
                lantaiContainer.innerHTML = '<div class="checkbox-placeholder">Tidak ada lantai ditemukan.</div>';
            }
        }

        function populateRuangan(lantaiIds, ruanganToSelect = []) {
            const relevantRuangan = allLocations.filter(loc => loc.ruangan_id && lantaiIds.includes(String(loc.lantai_id)));
            if (relevantRuangan.length > 0) {
                 let html = '<div id="ruangan_selection" class="checkbox-group-modern">';
                relevantRuangan.forEach(ruangan => {
                    const checked = ruanganToSelect.includes(String(ruangan.ruangan_id)) ? 'checked' : '';
                    html += `<div class="checkbox-item"><input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}" ${checked}><label for="ruangan_${ruangan.ruangan_id}">${ruangan.ruangan_nama} (Lantai ${ruangan.lantai_nomor}, ${ruangan.gedung_nama})</label></div>`;
                });
                html += '</div>';
                ruanganContainer.innerHTML = html;
            } else {
                ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Tidak ada ruangan tersedia.</div>';
            }
        }

        gedungSelection.addEventListener('change', function() {
            const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input:checked')).map(cb => cb.value);
            ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
            if (selectedGedungIds.length > 0) {
                populateLantai(selectedGedungIds);
            } else {
                lantaiContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div>';
            }
        });

        lantaiContainer.addEventListener('change', function(e) {
            if (e.target && e.target.matches('input[type="checkbox"]')) {
                const selectedLantaiIds = Array.from(lantaiContainer.querySelectorAll('input:checked')).map(cb => cb.value);
                if (selectedLantaiIds.length > 0) {
                    populateRuangan(selectedLantaiIds);
                } else {
                    ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
                }
            }
        });

        // Initialize state for editable form
        const selectedGedungIds = new Set();
        const selectedLantaiIds = new Set();
        preselectedRuanganIds.forEach(ruanganId => {
            const roomData = allLocations.find(loc => loc.ruangan_id == ruanganId);
            if (roomData) {
                selectedGedungIds.add(String(roomData.gedung_id));
                selectedLantaiIds.add(String(roomData.lantai_id));
            }
        });
        document.querySelectorAll('#gedung_selection input[type="checkbox"]').forEach(cb => {
            if (selectedGedungIds.has(cb.value)) cb.checked = true;
        });
        if (selectedGedungIds.size > 0) {
            populateLantai(Array.from(selectedGedungIds), Array.from(selectedLantaiIds));
            populateRuangan(Array.from(selectedLantaiIds), preselectedRuanganIds);
        }

    } else {
        // Mode READ-ONLY: Tampilkan daftar statis
        const listGedung = {}; const listLantai = {}; const listRuangan = [];
        <?php foreach ($selected_locations_list as $loc): ?>
            listGedung['<?php echo addslashes($loc["gedung_nama"]); ?>'] = true;
            listLantai['<?php echo addslashes($loc["gedung_nama"] . "_" . $loc["lantai_nomor"]); ?>'] = "Lantai <?php echo addslashes($loc["lantai_nomor"]); ?> (<?php echo addslashes($loc["gedung_nama"]); ?>)";
            listRuangan.push("<?php echo addslashes($loc["ruangan_nama"]); ?> (Lantai <?php echo addslashes($loc["lantai_nomor"]); ?>, <?php echo addslashes($loc["gedung_nama"]); ?>)");
        <?php endforeach; ?>

        let readonlyHtml = `
            <div class="form-group"><label>Gedung Terpilih</label><div class="static-list-box">${Object.keys(listGedung).map(g => `<p>${g}</p>`).join('') || '<p>Tidak ada data.</p>'}</div></div>
            <div class="form-group"><label>Lantai Terpilih</label><div class="static-list-box">${Object.values(listLantai).map(l => `<p>${l}</p>`).join('') || '<p>Tidak ada data.</p>'}</div></div>
            <div class="form-group"><label>Ruangan Terpilih</label><div class="static-list-box">${listRuangan.map(r => `<p>${r}</p>`).join('') || '<p>Tidak ada data.</p>'}</div></div>
        `;
        locationArea.innerHTML = readonlyHtml;
    }

    // Handler untuk dropdown tipe kegiatan 'Lainnya'
    const tipeSelect = document.getElementById('pengajuan_TypeKegiatan_select');
    const lainnyaContainer = document.getElementById('type_kegiatan_lainnya_container');
    const lainnyaInput = document.getElementById('pengajuan_TypeKegiatan_Lainnya');
    tipeSelect.addEventListener('change', function() {
        if (this.value === 'Lainnya') {
            lainnyaContainer.style.display = 'block';
            lainnyaInput.required = true;
        } else {
            lainnyaContainer.style.display = 'none';
            lainnyaInput.required = false;
        }
    });

});
</script>
</body>
</html>