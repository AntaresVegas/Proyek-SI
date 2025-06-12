<?php
session_start();
include '../config/db_connection.php';

// 1. Otentikasi & Otorisasi
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
$selected_ruangan_ids = [];

$pengajuan_id = $_GET['id'] ?? null;
if (!$pengajuan_id) {
    die("Error: ID Pengajuan tidak valid.");
}

// 3. Logika UPDATE data saat form disubmit (Tidak ada perubahan di sini)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $message = "Pengajuan event berhasil diperbarui dan diajukan kembali untuk peninjauan!";
        $message_type = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
    $stmt->close();
}

// 4. Ambil data event yang akan diedit untuk mengisi form (Tidak ada perubahan di sini)
$stmt = $conn->prepare("SELECT pe.*, m.mahasiswa_npm FROM pengajuan_event pe JOIN mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id WHERE pe.pengajuan_id = ? AND pe.mahasiswa_id = ?");
$stmt->bind_param("ii", $pengajuan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $event_data = $result->fetch_assoc();
} else {
    die("Error: Anda tidak memiliki akses untuk mengedit event ini atau event tidak ditemukan.");
}
$stmt->close();

$stmt_ruangan = $conn->prepare("SELECT ruangan_id FROM peminjaman_ruangan WHERE pengajuan_id = ?");
$stmt_ruangan->bind_param("i", $pengajuan_id);
$stmt_ruangan->execute();
$result_ruangan = $stmt_ruangan->get_result();
while($row = $result_ruangan->fetch_assoc()){
    $selected_ruangan_ids[] = (string)$row['ruangan_id'];
}
$stmt_ruangan->close();

$gedung_options = [];
$result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY gedung_nama ASC");
while ($row = $result_gedung->fetch_assoc()) {
    $gedung_options[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Sebagian besar CSS sama, hanya menambahkan sedikit style untuk file input */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 80px;}
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #87CEEB; }
        .navbar-right { color:rgb(255, 255, 255); }
        .container { max-width: 900px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .main-content { padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; border-radius: 15px 15px 0 0; text-align: center;}
        .header h1 { font-size: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; background-color: #f8f8f8; }
        .form-group input[readonly] { background-color: #e9ecef; }
        .button-group { text-align: right; margin-top: 25px; }
        .btn-submit { padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; background-color: #28a745; color: white; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;}
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
        .rejection-reason-box { background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 15px 20px; margin-bottom: 25px; }
        .rejection-reason-box h4 { color: #856404; margin-bottom: 10px; }
        .current-file { margin-bottom: 8px; font-size: 14px; color: #555; }
        .current-file a { color: #007bff; }
        .form-control-file { display: block; width: 100%; } /* Style untuk input file */
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
    <div class="navbar-right"><a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left:15px;"></i></a><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a></div>
</nav>

<div class="container">
    <div class="header"><h1>Edit Pengajuan Event</h1></div>
    <div class="main-content">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($event_data['pengajuan_status'] == 'Ditolak' && !empty($event_data['pengajuan_komentarDitmawa'])): ?>
            <div class="rejection-reason-box"><h4><i class="fas fa-comment-dots"></i> Alasan Penolakan dari Ditmawa:</h4><p><?php echo nl2br(htmlspecialchars($event_data['pengajuan_komentarDitmawa'])); ?></p></div>
        <?php endif; ?>
        
        <form id="eventForm" method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>Nama Penanggung Jawab</label><input type="text" value="<?php echo htmlspecialchars($nama); ?>" readonly></div>
            <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly></div>
            <div class="form-group"><label>NPM</label><input type="text" value="<?php echo htmlspecialchars($event_data['mahasiswa_npm']); ?>" readonly></div>
            <div class="form-group"><label for="pengajuan_namaEvent">Nama Event</label><input type="text" id="pengajuan_namaEvent" name="pengajuan_namaEvent" value="<?php echo htmlspecialchars($event_data['pengajuan_namaEvent']); ?>" required></div>
            <div class="form-group"><label for="pengajuan_TypeKegiatan">Tipe Kegiatan</label><input type="text" id="pengajuan_TypeKegiatan" name="pengajuan_TypeKegiatan" value="<?php echo htmlspecialchars($event_data['pengajuan_TypeKegiatan']); ?>" required></div>
            <hr style="margin: 25px 0;">
            <div class="form-group"><label for="gedung_id">Nama Gedung</label><select id="gedung_id" name="gedung_id" required><option value="">Pilih Gedung</option><?php foreach ($gedung_options as $gedung) { echo "<option value='{$gedung['gedung_id']}'>" . htmlspecialchars($gedung['gedung_nama']) . "</option>"; } ?></select></div>
            <div class="form-group"><label for="lantai_id">Nomor Lantai</label><select id="lantai_id" name="lantai_id" required disabled><option value="">Pilih Gedung Terlebih Dahulu</option></select></div>
            <div class="form-group"><label for="ruangan_selection">Pilih Ruangan</label><div id="ruangan_selection" class="checkbox-group"><p style="color: #666;">Pilih Lantai Terlebih Dahulu</p></div></div>
            <div class="form-group"><label for="pengajuan_event_jam_mulai">Jam Mulai</label><input type="time" id="pengajuan_event_jam_mulai" name="pengajuan_event_jam_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_mulai']); ?>" required></div>
            <div class="form-group"><label for="pengajuan_event_jam_selesai">Jam Selesai</label><input type="time" id="pengajuan_event_jam_selesai" name="pengajuan_event_jam_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_jam_selesai']); ?>" required></div>
            <div class="form-group"><label for="pengajuan_event_tanggal_mulai">Tanggal Mulai</label><input type="date" id="pengajuan_event_tanggal_mulai" name="pengajuan_event_tanggal_mulai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_mulai']); ?>" required></div>
            <div class="form-group"><label for="pengajuan_event_tanggal_selesai">Tanggal Selesai</label><input type="date" id="pengajuan_event_tanggal_selesai" name="pengajuan_event_tanggal_selesai" value="<?php echo htmlspecialchars($event_data['pengajuan_event_tanggal_selesai']); ?>" required></div>
            
            <input type="hidden" name="existing_rundown_file" value="<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>">
            <input type="hidden" name="existing_proposal_file" value="<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>">

            <div class="form-group">
                <label for="jadwal_event_rundown_file">Rundown Acara</label>
                <div class="current-file">
                    File saat ini: 
                    <a href="../<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>" target="_blank">
                        <?php echo basename($event_data['jadwal_event_rundown_file']); ?>
                    </a>
                </div>
                <p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 5px;">Pilih file baru di bawah ini hanya jika Anda ingin menggantinya.</p>
                <input type="file" id="jadwal_event_rundown_file" name="jadwal_event_rundown_file" class="form-control-file">
            </div>

            <div class="form-group">
                <label for="pengajuan_event_proposal_file">Proposal Kegiatan</label>
                <div class="current-file">
                    File saat ini: 
                    <a href="../<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>" target="_blank">
                        <?php echo basename($event_data['pengajuan_event_proposal_file']); ?>
                    </a>
                </div>
                <p style="font-size: 12px; color: #666; margin-top: 5px; margin-bottom: 5px;">Pilih file baru di bawah ini hanya jika Anda ingin menggantinya.</p>
                <input type="file" id="pengajuan_event_proposal_file" name="pengajuan_event_proposal_file" class="form-control-file">
            </div>
            <div class="button-group"><button type="submit" class="btn-submit">Simpan & Ajukan Ulang</button></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari PHP untuk inisialisasi form
    const preselectedRuanganIds = <?php echo json_encode($selected_ruangan_ids); ?>;
    
    // Fungsi untuk mendapatkan ID gedung dan lantai dari ruangan pertama yang dipilih
    function getInitialLocation(ruanganId) {
        if (!ruanganId) return Promise.resolve({ gedung_id: null, lantai_id: null });
        return fetch(`get_lokasi_from_ruangan.php?ruangan_id=${ruanganId}`).then(res => res.json());
    }

    // Inisialisasi form dengan data yang ada
    getInitialLocation(preselectedRuanganIds[0]).then(location => {
        const gedungSelect = document.getElementById('gedung_id');
        if (location.gedung_id) {
            gedungSelect.value = location.gedung_id;
            // Panggil fetchLantai setelah gedung di-set
            fetchLantai(location.gedung_id, location.lantai_id, preselectedRuanganIds);
        }
    });

    // Event listener untuk file input
    document.getElementById('jadwal_event_rundown_file').addEventListener('change', function(){
        document.getElementById('rundown_file_name').textContent = this.files[0] ? this.files[0].name : 'Pilih file baru untuk mengganti...';
    });
    document.getElementById('pengajuan_event_proposal_file').addEventListener('change', function(){
        document.getElementById('proposal_file_name').textContent = this.files[0] ? this.files[0].name : 'Pilih file baru untuk mengganti...';
    });
});

// Event listener untuk dropdown gedung
document.getElementById('gedung_id').addEventListener('change', function() {
    fetchLantai(this.value);
});

function fetchLantai(gedungId, preselectedLantaiId = null, preselectedRuanganIds = []) {
    const lantaiSelect = document.getElementById('lantai_id');
    const lantaiLoader = document.getElementById('lantai_loader');
    lantaiSelect.innerHTML = '<option value="">Memuat...</option>';
    lantaiLoader.style.display = 'inline-block';
    lantaiSelect.disabled = true;

    fetch(`get_lantai.php?gedung_id=${gedungId}`)
        .then(response => response.json())
        .then(data => {
            lantaiSelect.innerHTML = '<option value="">Pilih Lantai</option>';
            if (data.length > 0) {
                data.forEach(lantai => {
                    const option = document.createElement('option');
                    option.value = lantai.lantai_id;
                    option.textContent = `Lantai ${lantai.lantai_nomor}`;
                    if(lantai.lantai_id == preselectedLantaiId) {
                        option.selected = true;
                    }
                    lantaiSelect.appendChild(option);
                });
                lantaiSelect.disabled = false;
                // Jika lantai sudah terpilih, langsung panggil fetchRuangan
                if (preselectedLantaiId) {
                    fetchRuangan(preselectedLantaiId, preselectedRuanganIds);
                }
            }
            lantaiLoader.style.display = 'none';
        });
}

// Event listener untuk dropdown lantai
document.getElementById('lantai_id').addEventListener('change', function() {
    fetchRuangan(this.value);
});

function fetchRuangan(lantaiId, preselectedRuanganIds = []) {
    const ruanganDiv = document.getElementById('ruangan_selection');
    const ruanganLoader = document.getElementById('ruangan_loader');
    ruanganDiv.innerHTML = '<p>Memuat...</p>';
    ruanganLoader.style.display = 'inline-block';

    fetch(`get_ruangan.php?lantai_id=${lantaiId}`)
        .then(response => response.json())
        .then(data => {
            ruanganDiv.innerHTML = '';
            if (data.length > 0) {
                data.forEach(ruangan => {
                    const cbContainer = document.createElement('div');
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = `ruangan_${ruangan.ruangan_id}`;
                    checkbox.name = 'ruangan_ids[]';
                    checkbox.value = ruangan.ruangan_id;
                    if (preselectedRuanganIds.includes(String(ruangan.ruangan_id))) {
                        checkbox.checked = true;
                    }
                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = ruangan.ruangan_nama;
                    label.style.fontWeight = 'normal';
                    label.style.marginLeft = '8px';
                    cbContainer.appendChild(checkbox);
                    cbContainer.appendChild(label);
                    ruanganDiv.appendChild(cbContainer);
                });
            } else {
                ruanganDiv.innerHTML = '<p>Tidak ada ruangan tersedia.</p>';
            }
            ruanganLoader.style.display = 'none';
        });
}
</script>

</body>
</html>