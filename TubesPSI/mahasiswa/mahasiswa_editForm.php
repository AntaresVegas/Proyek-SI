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
    $check_status_stmt = $conn->prepare("SELECT pengajuan_status FROM pengajuan_event WHERE pengajuan_id = ? AND mahasiswa_id = ?");
    $check_status_stmt->bind_param("ii", $pengajuan_id, $user_id);
    $check_status_stmt->execute();
    $status_result = $check_status_stmt->get_result()->fetch_assoc();
    $check_status_stmt->close();

    if ($status_result && $status_result['pengajuan_status'] === 'Ditolak') {
        $pengajuan_namaEvent = $_POST['pengajuan_namaEvent'];
        $pengajuan_TypeKegiatan = $_POST['pengajuan_TypeKegiatan'];
        $pengajuan_event_jam_mulai = $_POST['pengajuan_event_jam_mulai'];
        $pengajuan_event_jam_selesai = $_POST['pengajuan_event_jam_selesai'];
        $pengajuan_event_tanggal_mulai = $_POST['pengajuan_event_tanggal_mulai'];
        $pengajuan_event_tanggal_selesai = $_POST['pengajuan_event_tanggal_selesai'];
        $new_selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : [];
        $rundown_file_path = $_POST['existing_rundown_file'];
        $proposal_file_path = $_POST['existing_proposal_file'];

        if (isset($_FILES['jadwal_event_rundown_file']) && $_FILES['jadwal_event_rundown_file']['error'] == UPLOAD_ERR_OK) {
            $rundown_file_path = 'uploads/rundown/' . uniqid() . "_" . basename($_FILES["jadwal_event_rundown_file"]["name"]);
            move_uploaded_file($_FILES["jadwal_event_rundown_file"]["tmp_name"], '../' . $rundown_file_path);
        }
        if (isset($_FILES['pengajuan_event_proposal_file']) && $_FILES['pengajuan_event_proposal_file']['error'] == UPLOAD_ERR_OK) {
            $proposal_file_path = 'uploads/proposal/' . uniqid() . "_" . basename($_FILES["pengajuan_event_proposal_file"]["name"]);
            move_uploaded_file($_FILES["pengajuan_event_proposal_file"]["tmp_name"], '../' . $proposal_file_path);
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_namaEvent = ?, pengajuan_TypeKegiatan = ?, pengajuan_event_jam_mulai = ?, pengajuan_event_jam_selesai = ?, pengajuan_event_tanggal_mulai = ?, pengajuan_event_tanggal_selesai = ?, jadwal_event_rundown_file = ?, pengajuan_event_proposal_file = ?, pengajuan_status = 'Diajukan', pengajuan_tanggalEdit = NOW(), pengajuan_komentarDitmawa = NULL WHERE pengajuan_id = ? AND mahasiswa_id = ?");
            $stmt->bind_param("ssssssssii", $pengajuan_namaEvent, $pengajuan_TypeKegiatan, $pengajuan_event_jam_mulai, $pengajuan_event_jam_selesai, $pengajuan_event_tanggal_mulai, $pengajuan_event_tanggal_selesai, $rundown_file_path, $proposal_file_path, $pengajuan_id, $user_id);
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
            $message = "Pengajuan event berhasil diperbarui dan diajukan kembali!";
            $message_type = 'success';
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
$stmt = $conn->prepare("SELECT pe.*, m.mahasiswa_npm FROM pengajuan_event pe JOIN mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id WHERE pe.pengajuan_id = ? AND pe.mahasiswa_id = ?");
$stmt->bind_param("ii", $pengajuan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$event_data = $result->num_rows > 0 ? $result->fetch_assoc() : die("Error: Anda tidak memiliki akses ke event ini.");
$stmt->close();

// === LOGIKA PENGUNCIAN FORM (EDITABLE/READ-ONLY) ===
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

if ($is_editable) {
    // Jika BISA DIEDIT, siapkan data untuk UI interaktif
    $result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY gedung_nama ASC");
    while ($row = $result_gedung->fetch_assoc()) $gedung_options[] = $row;
    
    $location_query = $conn->query("SELECT g.gedung_id, g.gedung_nama, l.lantai_id, l.lantai_nomor, r.ruangan_id, r.ruangan_nama FROM gedung g LEFT JOIN lantai l ON g.gedung_id = l.gedung_id LEFT JOIN ruangan r ON l.lantai_id = r.lantai_id ORDER BY g.gedung_nama, l.lantai_nomor, r.ruangan_nama");
    while ($row = $location_query->fetch_assoc()) $all_locations_data[] = $row;
} else {
    // Jika READ-ONLY, siapkan data untuk tampilan list statis
    if (!empty($selected_ruangan_ids)) {
        $placeholders = implode(',', array_fill(0, count($selected_ruangan_ids), '?'));
        $types = str_repeat('i', count($selected_ruangan_ids));
        $stmt_list = $conn->prepare("SELECT g.gedung_nama, l.lantai_nomor, r.ruangan_nama FROM ruangan r JOIN lantai l ON r.lantai_id = l.lantai_id JOIN gedung g ON l.gedung_id = g.gedung_id WHERE r.ruangan_id IN ($placeholders) ORDER BY g.gedung_nama, l.lantai_nomor, r.ruangan_nama");
        $stmt_list->bind_param($types, ...$selected_ruangan_ids);
        $stmt_list->execute();
        $result_list = $stmt_list->get_result();
        while($row = $result_list->fetch_assoc()) $selected_locations_list[] = $row;
        $stmt_list->close();
    }
}
$all_locations_data_str = json_encode($all_locations_data);
$conn->close();
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
        .button-group { text-align: right; margin-top: 25px; }
        .btn-submit { padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #28a745; color: white; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;}
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
        .current-file { margin-bottom: 8px; font-size: 14px; color: #555; }
        .current-file a { color: #007bff; }
        .form-control-file { display: block; width: 100%; }
        
        /* CSS untuk Status Notice Box */
        .status-notice { padding: 15px 20px; margin-bottom: 25px; border-left: 5px solid; border-radius: 5px; }
        .status-notice h4 { margin-bottom: 10px; }
        .status-notice-ditolak { background-color: #fff3cd; border-color: #ffc107; color: #856404; }
        .status-notice-diajukan { background-color: #cce5ff; border-color: #007bff; color: #004085; }
        .status-notice-disetujui { background-color: #d4edda; border-color: #28a745; color: #155724; }
        
        /* CSS untuk UI Interaktif (Mode Edit) */
        .multiselect-dropdown { position: relative; width: 100%; }
        .dropdown-select { padding: 10px 12px; border: 1px solid #ccc; border-radius: 5px; background-color: #f8f8f8; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .dropdown-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dropdown-list { display: none; position: absolute; top: 100%; left: 0; right: 0; border: 1px solid #ccc; border-top: none; border-radius: 0 0 5px 5px; background-color: white; z-index: 100; max-height: 200px; overflow-y: auto; }
        .dropdown-list label { display: block; padding: 10px 12px; cursor: pointer; font-weight: normal; }
        .dropdown-list label:hover { background-color: #f0f0f0; }
        .dropdown-list input[type="checkbox"] { margin-right: 10px; }
        .checkbox-group { border: 1px solid #ccc; border-radius: 5px; padding: 10px; max-height: 200px; overflow-y: auto; background-color: #f8f8f8; }
        
        /* CSS untuk Tampilan List Statis (Mode Read-Only) */
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
        <li><a href="mahasiswa_pengajuan.php" class="active">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
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
            <div class="status-notice status-notice-ditolak"><h4><i class="fas fa-exclamation-triangle"></i> Pengajuan Ditolak</h4><p>Alasan: <strong><?php echo htmlspecialchars($event_data['pengajuan_komentarDitmawa'] ?: 'Tidak ada komentar.'); ?></strong><br>Silakan perbaiki data dan ajukan kembali.</p></div>
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
            <div class="form-group"><label for="pengajuan_TypeKegiatan">Tipe Kegiatan</label><input type="text" id="pengajuan_TypeKegiatan" name="pengajuan_TypeKegiatan" value="<?php echo htmlspecialchars($event_data['pengajuan_TypeKegiatan']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            <hr style="margin: 25px 0;">

            <?php if ($is_editable): ?>
                <div class="form-group"><label>Pilih Gedung</label><div class="multiselect-dropdown" id="gedung_dropdown_container"><div class="dropdown-select"><span class="dropdown-text">Pilih Gedung...</span><i class="fas fa-chevron-down"></i></div><div class="dropdown-list"><?php foreach ($gedung_options as $gedung): ?><label><input type="checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" data-name="<?php echo htmlspecialchars($gedung['gedung_nama']); ?>"> <?php echo htmlspecialchars($gedung['gedung_nama']); ?></label><?php endforeach; ?></div></div></div>
                <div class="form-group"><label for="lantai_selection">Pilih Lantai</label><div id="lantai_selection" class="checkbox-group disabled"><p>Pilih Gedung terlebih dahulu.</p></div></div>
                <div class="form-group"><label for="ruangan_selection">Pilih Ruangan</label><div id="ruangan_selection" class="checkbox-group disabled"><p>Pilih Lantai terlebih dahulu.</p></div></div>
            <?php else: ?>
                <?php
                    $list_gedung = []; $list_lantai = []; $list_ruangan = [];
                    foreach ($selected_locations_list as $loc) {
                        $list_gedung[$loc['gedung_nama']] = true;
                        $list_lantai[$loc['gedung_nama'] . '_' . $loc['lantai_nomor']] = "Lantai {$loc['lantai_nomor']} ({$loc['gedung_nama']})";
                        $list_ruangan[] = "{$loc['ruangan_nama']} (Lantai {$loc['lantai_nomor']}, {$loc['gedung_nama']})";
                    }
                ?>
                <div class="form-group"><label>Gedung Terpilih</label><div class="static-list-box"><?php if(empty($list_gedung)): ?><p>Tidak ada gedung terpilih.</p><?php else: foreach (array_keys($list_gedung) as $nama_gedung): ?><p><?php echo htmlspecialchars($nama_gedung); ?></p><?php endforeach; endif; ?></div></div>
                <div class="form-group"><label>Lantai Terpilih</label><div class="static-list-box"><?php if(empty($list_lantai)): ?><p>Tidak ada lantai terpilih.</p><?php else: foreach ($list_lantai as $nama_lantai): ?><p><?php echo htmlspecialchars($nama_lantai); ?></p><?php endforeach; endif; ?></div></div>
                <div class="form-group"><label>Ruangan Terpilih</label><div class="static-list-box"><?php if(empty($list_ruangan)): ?><p>Tidak ada ruangan terpilih.</p><?php else: foreach ($list_ruangan as $nama_ruangan): ?><p><?php echo htmlspecialchars($nama_ruangan); ?></p><?php endforeach; endif; ?></div></div>
            <?php endif; ?>
            
            <div class="form-group"><label for="pengajuan_event_jam_mulai">Jam Mulai</label><input type="time" id="pengajuan_event_jam_mulai" name="pengajuan_event_jam_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_mulai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            <div class="form-group"><label for="pengajuan_event_jam_selesai">Jam Selesai</label><input type="time" id="pengajuan_event_jam_selesai" name="pengajuan_event_jam_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_selesai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            <div class="form-group"><label for="pengajuan_event_tanggal_mulai">Tanggal Mulai</label><input type="date" id="pengajuan_event_tanggal_mulai" name="pengajuan_event_tanggal_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_mulai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            <div class="form-group"><label for="pengajuan_event_tanggal_selesai">Tanggal Selesai</label><input type="date" id="pengajuan_event_tanggal_selesai" name="pengajuan_event_tanggal_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_selesai']); ?>" <?php if (!$is_editable) echo 'disabled'; ?> required></div>
            
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

            <?php if ($is_editable): ?>
                <div class="button-group"><button type="submit" class="btn-submit">Simpan & Ajukan Ulang</button></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isEditable = <?php echo json_encode($is_editable); ?>;

    // Jalankan skrip interaktif HANYA jika form bisa diedit
    if (isEditable) {
        const allLocations = <?php echo $all_locations_data_str; ?>;
        const preselectedRuanganIds = <?php echo $selected_ruangan_ids_str; ?>.map(String);
        
        const gedungDropdownContainer = document.getElementById('gedung_dropdown_container');
        const gedungSelectBox = gedungDropdownContainer.querySelector('.dropdown-select');
        const gedungDropdownList = gedungDropdownContainer.querySelector('.dropdown-list');
        const gedungDropdownText = gedungDropdownContainer.querySelector('.dropdown-text');
        const lantaiSelection = document.getElementById('lantai_selection');
        const ruanganSelection = document.getElementById('ruangan_selection');

        function initializeForm() {
            if (preselectedRuanganIds.length === 0) {
                gedungDropdownText.textContent = 'Pilih Gedung...';
                return;
            }
            const selectedGedungIds = new Set();
            const selectedLantaiIds = new Set();
            preselectedRuanganIds.forEach(ruanganId => {
                const roomData = allLocations.find(loc => loc.ruangan_id == ruanganId);
                if (roomData) {
                    selectedGedungIds.add(String(roomData.gedung_id));
                    selectedLantaiIds.add(String(roomData.lantai_id));
                }
            });
            const gedungCheckboxes = gedungDropdownList.querySelectorAll('input[type="checkbox"]');
            const selectedGedungNames = [];
            gedungCheckboxes.forEach(cb => {
                if (selectedGedungIds.has(cb.value)) {
                    cb.checked = true;
                    selectedGedungNames.push(cb.dataset.name);
                }
            });
            gedungDropdownText.textContent = selectedGedungNames.join(', ') || 'Pilih Gedung...';
            populateLantai(Array.from(selectedGedungIds), Array.from(selectedLantaiIds));
            populateRuangan(Array.from(selectedLantaiIds), preselectedRuanganIds);
        }

        function populateLantai(gedungIds, lantaiToSelect = []) {
            lantaiSelection.innerHTML = '';
            lantaiSelection.classList.remove('disabled');
            const relevantLantai = allLocations.filter(loc => loc.lantai_id && gedungIds.includes(String(loc.gedung_id)));
            const uniqueLantai = Array.from(new Map(relevantLantai.map(item => [item['lantai_id'], item])).values());
            if (uniqueLantai.length > 0) {
                uniqueLantai.forEach(lantai => {
                    const div = document.createElement('div');
                    div.innerHTML = `<input type="checkbox" class="lantai-checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}" ${lantaiToSelect.includes(String(lantai.lantai_id)) ? 'checked' : ''}><label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (<strong>${lantai.gedung_nama}</strong>)</label>`;
                    lantaiSelection.appendChild(div);
                });
            } else {
                lantaiSelection.innerHTML = '<p>Tidak ada lantai ditemukan.</p>';
            }
        }

        function populateRuangan(lantaiIds, ruanganToSelect = []) {
            ruanganSelection.innerHTML = '';
            ruanganSelection.classList.remove('disabled');
            const relevantRuangan = allLocations.filter(loc => loc.ruangan_id && lantaiIds.includes(String(loc.lantai_id)));
            if (relevantRuangan.length > 0) {
                relevantRuangan.forEach(ruangan => {
                    const div = document.createElement('div');
                    div.innerHTML = `<input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}" ${ruanganToSelect.includes(String(ruangan.ruangan_id)) ? 'checked' : ''}><label for="ruangan_${ruangan.ruangan_id}">${ruangan.ruangan_nama} (Lantai ${ruangan.lantai_nomor}, ${ruangan.gedung_nama})</label>`;
                    ruanganSelection.appendChild(div);
                });
            } else {
                ruanganSelection.innerHTML = '<p>Tidak ada ruangan tersedia.</p>';
            }
        }

        gedungSelectBox.addEventListener('click', () => { gedungDropdownList.style.display = gedungDropdownList.style.display === 'block' ? 'none' : 'block'; });
        window.addEventListener('click', (e) => { if (!gedungDropdownContainer.contains(e.target)) { gedungDropdownList.style.display = 'none'; } });

        gedungDropdownList.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                const selectedGedungCheckboxes = gedungDropdownList.querySelectorAll('input[type="checkbox"]:checked');
                const selectedNames = Array.from(selectedGedungCheckboxes).map(cb => cb.dataset.name);
                gedungDropdownText.textContent = selectedNames.join(', ') || 'Pilih Gedung...';
                const selectedGedungIds = Array.from(selectedGedungCheckboxes).map(cb => cb.value);
                ruanganSelection.innerHTML = '<p>Pilih Lantai terlebih dahulu.</p>';
                if (selectedGedungIds.length > 0) {
                    populateLantai(selectedGedungIds);
                } else {
                    lantaiSelection.innerHTML = '<p>Pilih Gedung terlebih dahulu.</p>';
                }
            }
        });

        lantaiSelection.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox') {
                const selectedLantaiCheckboxes = lantaiSelection.querySelectorAll('input[type="checkbox"]:checked');
                const selectedLantaiIds = Array.from(selectedLantaiCheckboxes).map(cb => cb.value);
                if (selectedLantaiIds.length > 0) {
                    const currentlySelectedRuangan = Array.from(ruanganSelection.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
                    populateRuangan(selectedLantaiIds, currentlySelectedRuangan);
                } else {
                    ruanganSelection.innerHTML = '<p>Pilih Lantai terlebih dahulu.</p>';
                }
            }
        });

        initializeForm();
    }
});
</script>
</body>
</html>