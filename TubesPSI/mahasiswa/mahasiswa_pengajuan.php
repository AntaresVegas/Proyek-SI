<?php
session_start();
include '../config/db_connection.php';

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Fetch Mahasiswa details
$mahasiswa_npm = '';
$mahasiswa_unit_nama = '';
$mahasiswa_organisasi_nama = '';
if ($user_id !== 'No ID') {
    $stmt = $conn->prepare("SELECT m.mahasiswa_npm, u.unit_nama, o.organisasi_nama FROM mahasiswa m LEFT JOIN unit u ON m.unit_id = u.unit_id LEFT JOIN organisasi o ON m.organisasi_id = o.organisasi_id WHERE m.mahasiswa_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $mahasiswa_npm = $row['mahasiswa_npm'];
        $mahasiswa_unit_nama = $row['unit_nama'];
        $mahasiswa_organisasi_nama = $row['organisasi_nama'];
    }
    $stmt->close();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_step']) && $_POST['form_step'] == 'step2') {
    // Process Step 2 data
    $pengajuan_namaEvent = $_POST['pengajuan_namaEvent'];
    $pengajuan_TypeKegiatan = $_POST['pengajuan_TypeKegiatan'];
    $pengajuan_event_jam_mulai = $_POST['pengajuan_event_jam_mulai'];
    $pengajuan_event_jam_selesai = $_POST['pengajuan_event_jam_selesai'];
    $pengajuan_event_tanggal_mulai = $_POST['pengajuan_event_tanggal_mulai'];
    $pengajuan_event_tanggal_selesai = $_POST['pengajuan_event_tanggal_selesai'];
    $selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : [];
    $rundown_file_path = null;
    $proposal_file_path = null;
    $target_dir_rundown = "uploads/rundown/";
    $target_dir_proposal = "uploads/proposal/";

    if (!is_dir($target_dir_rundown)) { mkdir($target_dir_rundown, 0777, true); }
    if (!is_dir($target_dir_proposal)) { mkdir($target_dir_proposal, 0777, true); }

    if (isset($_FILES['jadwal_event_rundown_file']) && $_FILES['jadwal_event_rundown_file']['error'] == UPLOAD_ERR_OK) {
        $file_name_rundown = basename($_FILES["jadwal_event_rundown_file"]["name"]);
        $rundown_file_path = $target_dir_rundown . uniqid() . "_" . $file_name_rundown;
        if (!move_uploaded_file($_FILES["jadwal_event_rundown_file"]["tmp_name"], $rundown_file_path)) {
            $message = "Error uploading rundown file."; $message_type = 'error'; $rundown_file_path = null;
        }
    }
    if (isset($_FILES['pengajuan_event_proposal_file']) && $_FILES['pengajuan_event_proposal_file']['error'] == UPLOAD_ERR_OK) {
        $file_name_proposal = basename($_FILES["pengajuan_event_proposal_file"]["name"]);
        $proposal_file_path = $target_dir_proposal . uniqid() . "_" . $file_name_proposal;
        if (!move_uploaded_file($_FILES["pengajuan_event_proposal_file"]["tmp_name"], $proposal_file_path)) {
            $message = "Error uploading proposal file."; $message_type = 'error'; $proposal_file_path = null;
        }
    }
    if ($message_type !== 'error') {
        $stmt = $conn->prepare("INSERT INTO pengajuan_event (pengajuan_namaEvent, mahasiswa_id, pengajuan_TypeKegiatan, pengajuan_event_jam_mulai, pengajuan_event_jam_selesai, pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai, jadwal_event_rundown_file, pengajuan_event_proposal_file, pengajuan_status, pengajuan_tanggalEdit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan', NOW())");
        $stmt->bind_param("sisssssss", $pengajuan_namaEvent, $user_id, $pengajuan_TypeKegiatan, $pengajuan_event_jam_mulai, $pengajuan_event_jam_selesai, $pengajuan_event_tanggal_mulai, $pengajuan_event_tanggal_selesai, $rundown_file_path, $proposal_file_path);
        if ($stmt->execute()) {
            $pengajuan_id = $stmt->insert_id;
            if (!empty($selected_ruangan_ids)) {
                $insert_peminjaman_stmt = $conn->prepare("INSERT INTO peminjaman_ruangan (pengajuan_id, ruangan_id) VALUES (?, ?)");
                foreach ($selected_ruangan_ids as $ruangan_id) {
                    $insert_peminjaman_stmt->bind_param("ii", $pengajuan_id, $ruangan_id);
                    $insert_peminjaman_stmt->execute();
                }
                $insert_peminjaman_stmt->close();
            }
            $message = "Pengajuan event berhasil diajukan!";
            $message_type = 'success';
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

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
    <title>Form Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
                /* All your existing CSS goes here. I'm omitting it for brevity but you should keep it. */
        /* Existing CSS from the template */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e5ec 100%); /* Lighter gradient for better contrast with the form */
            min-height: 100vh;
            background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background:rgb(2, 71, 25);
            width: 100%;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', sans-serif;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;  
        }

        .navbar-title {
            color:rgb(255, 255, 255);
            font-size: 14px;
            line-height: 1.2;
        }

        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        .navbar-menu li a {
            text-decoration: none;
            color:rgb(253, 253, 253);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .navbar-menu li a:hover {
            color: #007bff;
        }
        .navbar-menu li a:hover,
        .navbar-menu li a.active { /* Added active class style */
            color: #007bff;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color:rgb(255, 255, 255);
        }

        .user-name {
            font-weight: 500;
        }

        .icon {
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .icon:hover {
            color: #007bff;
        }

        .container {
            max-width: 900px; /* Adjusted for wider form */
            margin: 80px auto 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex; /* For sidebar layout */
        }


        .main-content {
            flex-grow: 1; /* Take remaining space */
            padding: 30px;
        }

        .header {
            background:rgb(44, 62, 80);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px; /* Added margin for separation */
            border-radius: 10px;
        }

        .header h1 {
            font-size: 24px;
        }

        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="time"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #f8f8f8; /* Light grey background */
        }

        .form-group input[type="file"] {
            padding: 8px 0;
        }
        .form-group input[readonly] { background-color: #e9ecef; }
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="time"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-group select[multiple] {
            min-height: 100px; /* For multi-select rooms */
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .button-group button {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s;
        }

        .button-group button.clear {
            background-color: #f44336;
            color: white;
        }

        .button-group button.clear:hover {
            background-color: #d32f2f;
        }

        .button-group button.next,
        .button-group button.submit {
            background-color: #28a745;
            color: white;
        }

        .button-group button.next:hover,
        .button-group button.submit:hover {
            background-color: #218838;
        }

        .button-group button.back {
            background-color: #6c757d;
            color: white;
        }

        .button-group button.back:hover {
            background-color: #5a6268;
        }

        /* Styling for messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Hide steps initially */
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }

        /* Loader */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 2s linear infinite;
            display: none; /* Hidden by default */
            margin-left: 10px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .checkbox-group {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
            max-height: 200px; /* Optional: Scroll if too many rooms */
            overflow-y: auto; /* Optional: Scroll if too many rooms */
            background-color: #f8f8f8;
        }

        .checkbox-group label {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            color: #34495e;
            font-weight: normal; /* Override the general label font-weight */
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
            width: auto; /* Ensure checkbox itself doesn't take full width */
        }
        
        .checkbox-group.disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        
        .checkbox-group.disabled p {
            color: #6c757d;
        }
        
        /* Custom File Upload Styling */
        .custom-file-upload {
            display: flex;
            align-items: center;
            gap: 10px; /* Space between button and file name */
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f8f8f8;
            padding: 5px; /* Adjust padding as needed */
        }

        .hidden-file-input {
            display: none; /* Hide the default file input */
        }

        .upload-button {
            background-color: #007bff; /* Blue button */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            flex-shrink: 0; /* Prevent button from shrinking */
        }

        .upload-button:hover {
            background-color: #0056b3;
        }

        .file-name {
            flex-grow: 1; /* Allow file name to take remaining space */
            color: #555;
            font-size: 14px;
            overflow: hidden; /* Hide overflow text */
            text-overflow: ellipsis; /* Add ellipsis for long file names */
            white-space: nowrap; /* Prevent wrapping */
        }

        /* Make sure the label also looks good */
        .file-upload-label {
            margin-bottom: 5px; /* Space below the label */
            display: block; /* Ensure it takes its own line */
        }
        :root {
            --primary-color: rgb(2, 71, 25); --secondary-color: #0d6efd; --light-gray: #f8f9fa;
            --text-dark: #212529; --text-light: #6c757d; --border-color: #dee2e6;
            --status-green: #198754; --status-red: #dc3545; --status-yellow: #ffc107;
        }
        /* ===== FOOTER STYLES ===== */
        .page-footer {
            background-color: var(--primary-color);
            color: #e9ecef;
            padding: 40px 0;
            margin-top: 40px;
        }
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;
        }
        .footer-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .footer-logo {
            width: 60px;
            height: 60px;
        }
        .footer-left h4 {
            font-size: 1.2em;
            font-weight: 500;
            line-height: 1.4;
        }
        .footer-right ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-right li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .footer-right .social-icons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .footer-right .social-icons a {
            color: #e9ecef;
            font-size: 1.5em;
            transition: color 0.3s;
        }
        .footer-right .social-icons a:hover {
            color: #fff;
        }

        /* === CSS BARU UNTUK CATATAN/NOTE === */
        .form-note {
            background-color: #fffbe6; /* Light Yellow */
            border-left: 5px solid #ffc107; /* Yellow Accent */
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-note p {
            margin: 5px 0 0 0;
            line-height: 1.5;
        }
        .form-note .fa-info-circle {
            margin-right: 8px;
            color: #ffc107;
        }
        /* Salin semua CSS dari file asli Anda ke sini */
        body { font-family: 'Segoe UI', sans-serif; background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-attachment: fixed; }
        .form-note { background-color: #fffbe6; border-left: 5px solid #ffc107; padding: 15px; margin-top: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 14px; }
        .form-note p { margin: 5px 0 0 0; line-height: 1.5; }
        .form-note .fa-info-circle { margin-right: 8px; color: #ffc107; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 20px; height: 20px; animation: spin 2s linear infinite; display: none; margin-left: 10px; vertical-align: middle; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        /* ... sisa CSS Anda ... */
    </style>
</head>
<body>
<nav class="navbar"></nav>
<div class="container">
    <div class="main-content">
        <div class="header"><h1>Form Pengajuan Event</h1></div>
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        <form id="eventForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_step" id="form_step" value="step1">
            <div class="form-step active" id="step1">
                 <div class="form-section">
                    <h2>Langkah 1: Informasi Dasar</h2>
                    <div class="form-group"><label>Nama Penanggung Jawab</label><input type="text" value="<?php echo htmlspecialchars($nama); ?>" readonly></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($email); ?>" readonly></div>
                    <div class="form-group"><label>NPM</label><input type="text" value="<?php echo htmlspecialchars($mahasiswa_npm); ?>" readonly></div>
                    <div class="form-group"><label for="nama_unit">Nama Unit</label><input type="text" id="nama_unit" name="nama_unit" value="<?php echo htmlspecialchars($mahasiswa_unit_nama); ?>"></div>
                    <div class="form-group"><label for="organisasi_penyelenggara">Organisasi Penyelenggara</label><input type="text" id="organisasi_penyelenggara" name="organisasi_penyelenggara" value="<?php echo htmlspecialchars($mahasiswa_organisasi_nama); ?>"></div>
                    <div class="form-group"><label for="pengajuan_namaEvent">Nama Event</label><input type="text" id="pengajuan_namaEvent" name="pengajuan_namaEvent" required></div>
                    <div class="form-group"><label for="pengajuan_TypeKegiatan">Tipe Kegiatan</label><input type="text" id="pengajuan_TypeKegiatan" name="pengajuan_TypeKegiatan" placeholder="Contoh: Seminar, Workshop" required></div>
                    <div class="button-group"><button type="button" class="clear" onclick="clearForm('step1')">Clear</button><button type="button" class="next" onclick="nextStep()">Next</button></div>
                </div>
            </div>
            <div class="form-step" id="step2">
                <div class="form-section">
                    <h2>Langkah 2: Detail Jadwal dan Ruangan</h2>
                    <div class="form-group"><label for="gedung_selection">Pilih Gedung</label><div id="gedung_selection" class="checkbox-group"><?php foreach ($gedung_options as $gedung): ?><div><input type="checkbox" class="gedung-checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></label></div><?php endforeach; ?></div></div>
                    <div class="form-group"><label for="lantai_selection">Pilih Lantai</label><div id="lantai_selection" class="checkbox-group disabled"><p>Pilih Gedung terlebih dahulu.</p></div><span class="loader" id="lantai_loader"></span></div>
                    <div class="form-note"><i class="fas fa-info-circle"></i><strong>Catatan Penting:</strong><p>Pilih rentang tanggal peminjaman termasuk waktu untuk persiapan dan pemberesan. Ketersediaan ruangan akan ditampilkan setelah Anda mengisi tanggal.</p></div>
                    <div class="form-group"><label for="pengajuan_event_tanggal_mulai">Tanggal Mulai Peminjaman</label><input type="date" id="pengajuan_event_tanggal_mulai" name="pengajuan_event_tanggal_mulai" required></div>
                    <div class="form-group"><label for="pengajuan_event_tanggal_selesai">Tanggal Selesai Peminjaman</label><input type="date" id="pengajuan_event_tanggal_selesai" name="pengajuan_event_tanggal_selesai" required></div>
                    <div class="form-group"><label for="ruangan_selection">Pilih Ruangan</label><div id="ruangan_selection" class="checkbox-group disabled"><p>Pilih Lantai dan Tanggal terlebih dahulu.</p></div><span class="loader" id="ruangan_loader"></span></div>
                    <div class="form-group"><label for="pengajuan_event_jam_mulai">Jam Mulai</label><input type="time" id="pengajuan_event_jam_mulai" name="pengajuan_event_jam_mulai" required></div>
                    <div class="form-group"><label for="pengajuan_event_jam_selesai">Jam Selesai</label><input type="time" id="pengajuan_event_jam_selesai" name="pengajuan_event_jam_selesai" required></div>
                    <div class="form-group"><label class="file-upload-label">Rundown Acara</label><div class="custom-file-upload"><input type="file" id="jadwal_event_rundown_file" name="jadwal_event_rundown_file" required class="hidden-file-input"><button type="button" class="upload-button" onclick="document.getElementById('jadwal_event_rundown_file').click()">Pilih File</button><span class="file-name" id="rundown_file_name">Belum ada file</span></div></div>
                    <div class="form-group"><label class="file-upload-label">Proposal Kegiatan</label><div class="custom-file-upload"><input type="file" id="pengajuan_event_proposal_file" name="pengajuan_event_proposal_file" required class="hidden-file-input"><button type="button" class="upload-button" onclick="document.getElementById('pengajuan_event_proposal_file').click()">Pilih File</button><span class="file-name" id="proposal_file_name">Belum ada file</span></div></div>
                    <div class="button-group"><button type="button" class="back" onclick="prevStep()">Back</button><button type="button" class="clear" onclick="clearForm('step2')">Clear</button><button type="submit" class="submit">Submit</button></div>
                </div>
            </div>
        </form>
    </div>
</div>
<footer class="page-footer"></footer>

<script>
    // ... (Fungsi showStep, nextStep, prevStep, clearForm, dan file upload handler tetap sama) ...
    
    const gedungSelection = document.getElementById('gedung_selection');
    const lantaiSelection = document.getElementById('lantai_selection');
    const ruanganSelection = document.getElementById('ruangan_selection');
    const tanggalMulaiInput = document.getElementById('pengajuan_event_tanggal_mulai');
    const tanggalSelesaiInput = document.getElementById('pengajuan_event_tanggal_selesai');
    const lantaiLoader = document.getElementById('lantai_loader');
    const ruanganLoader = document.getElementById('ruangan_loader');

    gedungSelection.addEventListener('change', function() {
        const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input[type=checkbox]:checked')).map(cb => cb.value);
        lantaiSelection.innerHTML = '<p>Pilih Gedung terlebih dahulu.</p>';
        lantaiSelection.classList.add('disabled');
        ruanganSelection.innerHTML = '<p>Pilih Lantai dan Tanggal terlebih dahulu.</p>';
        ruanganSelection.classList.add('disabled');
        if (selectedGedungIds.length > 0) {
            lantaiLoader.style.display = 'inline-block';
            const queryString = selectedGedungIds.map(id => `gedung_ids[]=${id}`).join('&');
            fetch(`get_lantai.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    lantaiSelection.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(lantai => {
                            const div = document.createElement('div');
                            div.innerHTML = `<input type="checkbox" class="lantai-checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}"><label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (${lantai.gedung_nama})</label>`;
                            lantaiSelection.appendChild(div);
                        });
                        lantaiSelection.classList.remove('disabled');
                    } else {
                        lantaiSelection.innerHTML = '<p>Tidak ada lantai ditemukan.</p>';
                    }
                })
                .finally(() => { lantaiLoader.style.display = 'none'; });
        }
    });

    function checkRuanganAvailability() {
        const startDate = tanggalMulaiInput.value;
        const endDate = tanggalSelesaiInput.value;
        const selectedLantaiIds = Array.from(lantaiSelection.querySelectorAll('input[type=checkbox]:checked')).map(cb => cb.value);

        ruanganSelection.innerHTML = '<p>Pilih Lantai dan Tanggal terlebih dahulu.</p>';
        ruanganSelection.classList.add('disabled');

        if (selectedLantaiIds.length > 0) {
            if (!startDate || !endDate) {
                ruanganSelection.innerHTML = '<p style="color: red;">Harap isi Tanggal Mulai dan Selesai untuk melihat ketersediaan ruangan.</p>';
                ruanganSelection.classList.remove('disabled');
                return;
            }
            ruanganLoader.style.display = 'inline-block';
            const lantaiQuery = selectedLantaiIds.map(id => `lantai_ids[]=${id}`).join('&');
            const queryString = `${lantaiQuery}&start_date=${startDate}&end_date=${endDate}`;
            
            fetch(`get_ruangan.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    ruanganSelection.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(ruangan => {
                            const div = document.createElement('div');
                            const isAvailable = ruangan.is_available;
                            div.innerHTML = `
                                <input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}" ${!isAvailable ? 'disabled' : ''}>
                                <label for="ruangan_${ruangan.ruangan_id}" style="${!isAvailable ? 'color: #999; text-decoration: line-through;' : ''}">
                                    ${ruangan.ruangan_nama} (${ruangan.gedung_nama}, Lt. ${ruangan.lantai_nomor})
                                    ${!isAvailable ? ' <strong>(Tidak Tersedia)</strong>' : ''}
                                </label>`;
                            ruanganSelection.appendChild(div);
                        });
                        ruanganSelection.classList.remove('disabled');
                    } else {
                        ruanganSelection.innerHTML = '<p>Tidak ada ruangan pada lantai yang dipilih.</p>';
                    }
                })
                .catch(error => { console.error('Error fetching ruangan:', error); ruanganSelection.innerHTML = '<p style="color: red;">Gagal memuat data ruangan.</p>'; })
                .finally(() => { ruanganLoader.style.display = 'none'; });
        }
    }

    lantaiSelection.addEventListener('change', checkRuanganAvailability);
    tanggalMulaiInput.addEventListener('change', checkRuanganAvailability);
    tanggalSelesaiInput.addEventListener('change', checkRuanganAvailability);
</script>
</body>
</html>