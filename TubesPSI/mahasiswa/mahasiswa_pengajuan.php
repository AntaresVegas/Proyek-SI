<?php
session_start();
include '../config/db_connection.php'; // Asumsikan Anda punya file koneksi database

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$email = $_SESSION['username'] ?? 'No email'; // Assuming username is email
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Fetch Mahasiswa details from DB for pre-filling form
$mahasiswa_npm = '';
$mahasiswa_jurusan = '';
$mahasiswa_unit_nama = '';
$mahasiswa_organisasi_nama = '';

if ($user_id !== 'No ID') {
    $stmt = $conn->prepare("
        SELECT m.mahasiswa_npm, m.mahasiswa_jurusan, u.unit_nama, o.organisasi_nama
        FROM mahasiswa m
        LEFT JOIN unit u ON m.unit_id = u.unit_id
        LEFT JOIN organisasi o ON m.organisasi_id = o.organisasi_id
        WHERE m.mahasiswa_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $mahasiswa_npm = $row['mahasiswa_npm'];
        $mahasiswa_jurusan = $row['mahasiswa_jurusan'];
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
    
    // --- PERUBAHAN PHP: Handle Tipe Kegiatan 'Lainnya' ---
    $pengajuan_TypeKegiatan_raw = $_POST['pengajuan_TypeKegiatan'];
    if ($pengajuan_TypeKegiatan_raw === 'Lainnya') {
        $pengajuan_TypeKegiatan = $_POST['pengajuan_TypeKegiatan_Lainnya'];
    } else {
        $pengajuan_TypeKegiatan = $pengajuan_TypeKegiatan_raw;
    }
    // --- AKHIR PERUBAHAN PHP ---

    $pengajuan_event_jam_mulai = $_POST['pengajuan_event_jam_mulai'];
    $pengajuan_event_jam_selesai = $_POST['pengajuan_event_jam_selesai'];
    $pengajuan_event_tanggal_mulai = $_POST['pengajuan_event_tanggal_mulai'];
    $pengajuan_event_tanggal_selesai = $_POST['pengajuan_event_tanggal_selesai'];
    $tanggal_persiapan = !empty($_POST['tanggal_persiapan']) ? $_POST['tanggal_persiapan'] : null;
    $tanggal_beres = !empty($_POST['tanggal_beres']) ? $_POST['tanggal_beres'] : null;
    $selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : []; // Array of selected room IDs

    // File uploads
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

    $pengaju_tipe = 'mahasiswa'; // Tentukan tipe pengaju karena ini form mahasiswa

    if ($message_type !== 'error') {
        $stmt = $conn->prepare("
        INSERT INTO pengajuan_event (
            pengajuan_namaEvent, pengaju_tipe, pengaju_id, pengajuan_TypeKegiatan,
            pengajuan_event_jam_mulai, pengajuan_event_jam_selesai,
            pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai,
            tanggal_persiapan, tanggal_beres,
            jadwal_event_rundown_file, pengajuan_event_proposal_file,
            pengajuan_status, pengajuan_tanggalEdit
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan', NOW())
    ");

        $stmt->bind_param("ssisssssssss",
        $pengajuan_namaEvent, $pengaju_tipe, $user_id, $pengajuan_TypeKegiatan,
        $pengajuan_event_jam_mulai, $pengajuan_event_jam_selesai,
        $pengajuan_event_tanggal_mulai, $pengajuan_event_tanggal_selesai,
        $tanggal_persiapan, $tanggal_beres,
        $rundown_file_path, $proposal_file_path
    );

        if ($stmt->execute()) {
            $pengajuan_id = $stmt->insert_id;
            if (!empty($selected_ruangan_ids)) {
                $insert_peminjaman_stmt = $conn->prepare("
                    INSERT INTO peminjaman_ruangan (pengajuan_id, ruangan_id) VALUES (?, ?)
                ");
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

// Fetch buildings for the checkboxes
$gedung_options = [];
// BARU (Mengurutkan berdasarkan angka setelah kata 'Gedung ')
$result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY CAST(SUBSTRING(gedung_nama, 7) AS UNSIGNED) ASC");
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
        /* CSS Umum dan Layout (Tidak ada perubahan di sini) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('../img/backgroundUnpar.jpeg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgb(2, 71, 25);
            width: 100%;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            color: rgb(255, 255, 255);
            font-size: 14px;
            line-height: 1.2;
        }

        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color: rgb(255, 255, 255);
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
            max-width: 900px;
            margin: 80px auto 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .main-content {
            padding: 30px;
        }

        .header {
            background: rgb(44, 62, 80);
            color: white;
            padding: 20px 30px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .header h1 {
            font-size: 24px;
        }

        /* Form Styling */
        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group > label {
            display: block;
            margin-bottom: 8px;
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
            background-color: #f8f8f8;
        }

        .form-group input[readonly] { 
            background-color: #e9ecef; 
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
            transition: background-color 0.3s;
        }

        .button-group button.clear {
            background-color: #f44336;
            color: white;
        }
        .button-group button.clear:hover { background-color: #d32f2f; }
        .button-group button.next, .button-group button.submit {
            background-color: #28a745;
            color: white;
        }
        .button-group button.next:hover, .button-group button.submit:hover { background-color: #218838; }
        .button-group button.back {
            background-color: #6c757d;
            color: white;
        }
        .button-group button.back:hover { background-color: #5a6268; }

        /* Pesan Notifikasi */
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

        /* Multi-step Form */
        .form-step { display: none; }
        .form-step.active { display: block; }

        /* Loader */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 2s linear infinite;
            display: none;
            margin-left: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== START: DESAIN CHECKBOX KUSTOM MODERN ===== */
        .checkbox-placeholder {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            color: #6c757d;
            border: 1px dashed #dee2e6;
        }

        .checkbox-group-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            position: relative;
        }

        .checkbox-item input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 0;
            height: 0;
        }

        .checkbox-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: normal;
            color: #495057;
            margin-bottom: 0;
        }

        .checkbox-item label::before {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #adb5bd;
            border-radius: 4px;
            margin-right: 12px;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .checkbox-item input[type="checkbox"]:checked + label::before {
            background-color: #007bff;
            border-color: #007bff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 60%;
        }

        .checkbox-item label:hover::before {
            border-color: #007bff;
        }
        
        .checkbox-item input[type="checkbox"]:focus + label::before {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        /* ===== END: DESAIN CHECKBOX KUSTOM MODERN ===== */

        /* Custom File Upload Styling */
        .custom-file-upload {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f8f8f8;
            padding: 5px;
        }

        .hidden-file-input { display: none; }
        .upload-button {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            flex-shrink: 0;
        }
        .upload-button:hover { background-color: #0056b3; }
        .file-name {
            flex-grow: 1;
            color: #555;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .date-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Footer */
        .page-footer {
            background-color: rgb(2, 71, 25);
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
        .footer-right .social-icons a:hover { color: #fff; }
        
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>
    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php" class="active">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="main-content">
        <div class="header">
            <h1>Form Pengajuan Event</h1>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form id="eventForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_step" id="form_step" value="step1">
            
            <div class="form-step active" id="step1">
                 <div class="form-section">
                    <h2>Langkah 1: Informasi Dasar</h2>
                    <div class="form-group">
                        <label for="nama_penanggung_jawab">Nama Penanggung Jawab</label>
                        <input type="text" id="nama_penanggung_jawab" name="nama_penanggung_jawab" value="<?php echo htmlspecialchars($nama); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="npm">NPM</label>
                        <input type="text" id="npm" name="npm" value="<?php echo htmlspecialchars($mahasiswa_npm); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nama_unit">Nama Unit</label>
                        <input type="text" id="nama_unit" name="nama_unit" value="<?php echo htmlspecialchars($mahasiswa_unit_nama); ?>" placeholder="Contoh: Fakultas Ilmu Komputer">
                    </div>
                    <div class="form-group">
                        <label for="organisasi_penyelenggara">Organisasi Penyelenggara</label>
                        <input type="text" id="organisasi_penyelenggara" name="organisasi_penyelenggara" value="<?php echo htmlspecialchars($mahasiswa_organisasi_nama); ?>" placeholder="Contoh: Himpunan Mahasiswa Informatika">
                    </div>
                    <div class="form-group">
                        <label for="pengajuan_namaEvent">Nama Event</label>
                        <input type="text" id="pengajuan_namaEvent" name="pengajuan_namaEvent" required>
                    </div>
                    <div class="form-group">
                        <label for="pengajuan_TypeKegiatan">Tipe Kegiatan</label>
                        <select id="pengajuan_TypeKegiatan" name="pengajuan_TypeKegiatan" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Lomba">Lomba</option>
                            <option value="Pameran">Pameran</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group" id="type_kegiatan_lainnya_container" style="display:none;">
                        <label for="pengajuan_TypeKegiatan_Lainnya">Sebutkan Tipe Kegiatan Lainnya</label>
                        <input type="text" id="pengajuan_TypeKegiatan_Lainnya" name="pengajuan_TypeKegiatan_Lainnya" placeholder="Tuliskan tipe kegiatan Anda di sini">
                    </div>
                    <div class="button-group">
                        <button type="button" class="clear" onclick="clearForm('step1')">Clear</button>
                        <button type="button" class="next" onclick="nextStep()">Next</button>
                    </div>
                </div>
            </div>

            <div class="form-step" id="step2">
                <div class="form-section">
                    <h2>Langkah 2: Detail Jadwal dan Ruangan</h2>
                    <div class="form-group">
                        <label>Pilih Gedung (bisa lebih dari satu)</label>
                        <div id="gedung_selection" class="checkbox-group-modern">
                            <?php foreach ($gedung_options as $gedung): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" class="gedung-checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>">
                                    <label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Lantai (bisa lebih dari satu)</label>
                        <div id="lantai_selection_container">
                             <div class="checkbox-placeholder"><p>Pilih Gedung terlebih dahulu.</p></div>
                        </div>
                        <span class="loader" id="lantai_loader"></span>
                    </div>
                    <div class="form-group">
                        <label>Pilih Ruangan (bisa lebih dari satu)</label>
                        <div id="ruangan_selection_container">
                            <div class="checkbox-placeholder"><p>Pilih Lantai terlebih dahulu.</p></div>
                        </div>
                        <span class="loader" id="ruangan_loader"></span>
                    </div>
                    
                    <div class="date-grid">
                        <div class="form-group">
                            <label for="pengajuan_event_jam_mulai">Jam Mulai</label>
                            <input type="time" id="pengajuan_event_jam_mulai" name="pengajuan_event_jam_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="pengajuan_event_jam_selesai">Jam Selesai</label>
                            <input type="time" id="pengajuan_event_jam_selesai" name="pengajuan_event_jam_selesai" required>
                        </div>
                    </div>

                    <div class="date-grid">
                        <div class="form-group">
                            <label for="pengajuan_event_tanggal_mulai">Tanggal Mulai Acara</label>
                            <input type="date" id="pengajuan_event_tanggal_mulai" name="pengajuan_event_tanggal_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="pengajuan_event_tanggal_selesai">Tanggal Selesai Acara</label>
                            <input type="date" id="pengajuan_event_tanggal_selesai" name="pengajuan_event_tanggal_selesai" required>
                        </div>
                    </div>

                    <div class="date-grid">
                        <div class="form-group">
                            <label for="tanggal_persiapan">Tanggal Persiapan (Opsional)</label>
                            <input type="date" id="tanggal_persiapan" name="tanggal_persiapan">
                        </div>
                        <div class="form-group">
                            <label for="tanggal_beres">Tanggal Beres-Beres (Opsional)</label>
                            <input type="date" id="tanggal_beres" name="tanggal_beres">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="jadwal_event_rundown_file">Rundown Acara (PDF, DOCX)</label>
                        <div class="custom-file-upload">
                            <input type="file" id="jadwal_event_rundown_file" name="jadwal_event_rundown_file" accept=".pdf,.doc,.docx" required class="hidden-file-input">
                            <button type="button" class="upload-button" onclick="document.getElementById('jadwal_event_rundown_file').click()">Pilih File</button>
                            <span class="file-name" id="rundown_file_name">Belum ada file dipilih</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pengajuan_event_proposal_file">Proposal Kegiatan (PDF, DOCX)</label>
                        <div class="custom-file-upload">
                            <input type="file" id="pengajuan_event_proposal_file" name="pengajuan_event_proposal_file" accept=".pdf,.doc,.docx" required class="hidden-file-input">
                            <button type="button" class="upload-button" onclick="document.getElementById('pengajuan_event_proposal_file').click()">Pilih File</button>
                            <span class="file-name" id="proposal_file_name">Belum ada file dipilih</span>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="back" onclick="prevStep()">Back</button>
                        <button type="button" class="clear" onclick="clearForm('step2')">Clear</button>
                        <button type="submit" class="submit">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;">DIREKTORAT KEMAHASISWAAN</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655 ext. 100140</li>
                <li><i class="fas fa-envelope"></i> kemahasiswaan@unpar.ac.id</li>
            </ul>
            <div class="social-icons">
                <a href="https://www.facebook.com/unparofficial" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/unparofficial/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/channel/UCeIZdD9ul6JGpkSNM0oxcBw/featured" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="https://www.tiktok.com/@unparofficial" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
    let currentStep = 1;
    const formSteps = document.querySelectorAll('.form-step');
    const formStepInput = document.getElementById('form_step');

    function showStep(step) {
        formSteps.forEach((s, index) => {
            s.classList.remove('active');
            if (index + 1 === step) {
                s.classList.add('active');
            }
        });
        formStepInput.value = `step${step}`;
    }

// --- PERUBAHAN JAVASCRIPT: Fungsi nextStep dengan validasi berurutan dan spesifik ---
    function nextStep() {
        // Ambil semua elemen input dan nilainya
        const namaUnitInput = document.getElementById('nama_unit');
        const organisasiInput = document.getElementById('organisasi_penyelenggara');
        const namaEventInput = document.getElementById('pengajuan_namaEvent');
        const typeKegiatanSelect = document.getElementById('pengajuan_TypeKegiatan');
        const typeLainnyaInput = document.getElementById('pengajuan_TypeKegiatan_Lainnya');

        const namaUnitValue = namaUnitInput.value.trim();
        const organisasiValue = organisasiInput.value.trim();
        const namaEventValue = namaEventInput.value.trim();
        const typeKegiatanValue = typeKegiatanSelect.value;
        const typeLainnyaValue = typeLainnyaInput.value.trim();

        // Pengecekan berurutan untuk setiap input
        if (namaUnitValue === '' && organisasiValue === '' && namaEventValue === '' && typeKegiatanValue === '' && (typeKegiatanValue === 'Lainnya' && typeLainnyaValue === '')) {
            alert('Harap lengkapi semua data yang wajib diisi pada Langkah 1.');
            return; // Hentikan fungsi jika ada yang kosong
        }
        else if (namaUnitValue === '') {
            alert('Nama Unit wajib diisi.');
            namaUnitInput.focus();
            return;
        } 
        
        else if (organisasiValue === '') {
            alert('Organisasi Penyelenggara wajib diisi.');
            organisasiInput.focus();
            return;
        } 
        
        else if (namaEventValue === '') {
            alert('Nama Event wajib diisi.');
            namaEventInput.focus();
            return;
        } 
        
        else if (typeKegiatanValue === '') {
            alert('Harap pilih Tipe Kegiatan.');
            typeKegiatanSelect.focus();
            return;
        } 
        
        else if (typeKegiatanValue === 'Lainnya' && typeLainnyaValue === '') {
            alert('Harap sebutkan tipe kegiatan lainnya.');
            typeLainnyaInput.focus();
            return;
        } 
        
        // TERAKHIR: Cek panjang Nama Event (hanya jika semua kolom sudah terisi)
        else if (namaEventValue.length < 5) {
            alert('Nama Event harus memiliki minimal 5 karakter.');
            namaEventInput.focus();
            return;
        }

        // Jika semua pengecekan di atas lolos, lanjutkan ke langkah berikutnya
        currentStep++;
        showStep(currentStep);
    }
    // --- AKHIR PERUBAHAN JAVASCRIPT ---

    function prevStep() {
        currentStep--;
        showStep(currentStep);
    }
    
    function clearForm(stepId) {
        const stepElement = document.getElementById(stepId);
        const inputs = stepElement.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else if (input.type === 'file') {
                input.value = '';
                const fileNameSpanId = input.id.replace(/_file$/, '_file_name');
                const fileNameSpan = document.getElementById(fileNameSpanId);
                if (fileNameSpan) {
                    fileNameSpan.textContent = 'Belum ada file dipilih';
                }
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else if (!input.hasAttribute('readonly')) {
                input.value = '';
            }
        });
        
        if (stepId === 'step1') {
            // Sembunyikan lagi field 'lainnya' jika form di-clear
            document.getElementById('type_kegiatan_lainnya_container').style.display = 'none';
            document.getElementById('pengajuan_TypeKegiatan_Lainnya').removeAttribute('required');
        }

        if (stepId === 'step2') {
            document.getElementById('lantai_selection_container').innerHTML = '<div class="checkbox-placeholder"><p>Pilih Gedung terlebih dahulu.</p></div>';
            document.getElementById('ruangan_selection_container').innerHTML = '<div class="checkbox-placeholder"><p>Pilih Lantai terlebih dahulu.</p></div>';
        }
    }
    
    // File upload display name handlers
    document.getElementById('jadwal_event_rundown_file').addEventListener('change', function() {
        const fileNameSpan = document.getElementById('rundown_file_name');
        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Belum ada file dipilih';
    });

    document.getElementById('pengajuan_event_proposal_file').addEventListener('change', function() {
        const fileNameSpan = document.getElementById('proposal_file_name');
        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Belum ada file dipilih';
    });
    
    // --- PENAMBAHAN JAVASCRIPT: Logika untuk Dropdown 'Lainnya' ---
    document.getElementById('pengajuan_TypeKegiatan').addEventListener('change', function() {
        const lainnyaContainer = document.getElementById('type_kegiatan_lainnya_container');
        const lainnyaInput = document.getElementById('pengajuan_TypeKegiatan_Lainnya');
        if (this.value === 'Lainnya') {
            lainnyaContainer.style.display = 'block';
            lainnyaInput.setAttribute('required', 'required');
        } else {
            lainnyaContainer.style.display = 'none';
            lainnyaInput.removeAttribute('required');
            lainnyaInput.value = ''; // Kosongkan nilainya jika pilihan diubah
        }
    });
    // --- AKHIR PENAMBAHAN JAVASCRIPT ---

    // --- PENAMBAHAN JAVASCRIPT: Validasi Tanggal dan Lokasi Sebelum Submit ---
    document.getElementById('eventForm').addEventListener('submit', function(event) {
        // 1. Validasi Tanggal
        const tglMulai = document.getElementById('pengajuan_event_tanggal_mulai').value;
        const tglSelesai = document.getElementById('pengajuan_event_tanggal_selesai').value;
        const tglPersiapan = document.getElementById('tanggal_persiapan').value;
        const tglBeres = document.getElementById('tanggal_beres').value;

        if (tglMulai && tglSelesai && tglSelesai < tglMulai) {
            alert('Error: Tanggal Selesai Acara tidak boleh mendahului Tanggal Mulai Acara.');
            event.preventDefault(); // Mencegah form untuk submit
            return;
        }

        if (tglBeres) {
            if (tglSelesai && tglBeres < tglSelesai) {
                alert('Error: Tanggal Beres-Beres tidak boleh mendahului Tanggal Selesai Acara.');
                event.preventDefault();
                return;
            }
            if (tglMulai && tglBeres < tglMulai) {
                 alert('Error: Tanggal Beres-Beres tidak boleh mendahului Tanggal Mulai Acara.');
                event.preventDefault();
                return;
            }
        }
        
        // Permintaan: "kalau tanggal persiapan mendahului tanggal mulai acara maka akan error"
        // Interpretasi logis: Tanggal persiapan tidak boleh SETELAH tanggal mulai acara. 
        // Persiapan harus sebelum atau pada hari H.
        if (tglPersiapan && tglMulai && tglPersiapan > tglMulai) {
            alert('Error: Tanggal Persiapan tidak boleh setelah Tanggal Mulai Acara.');
            event.preventDefault();
            return;
        }

        // 2. Validasi Pemilihan Lokasi
        const gedungChecked = document.querySelectorAll('input[name="gedung_ids[]"]:checked').length;
        if (gedungChecked === 0) {
            alert('Error: Anda harus memilih minimal satu Gedung.');
            event.preventDefault();
            return;
        }

        const lantaiChecked = document.querySelectorAll('input[name="lantai_ids[]"]:checked').length;
        // Hanya validasi jika container lantai sudah ada isinya (bukan placeholder)
        if (document.getElementById('lantai_selection') && lantaiChecked === 0) {
            alert('Error: Anda harus memilih minimal satu Lantai.');
            event.preventDefault();
            return;
        }
        
        const ruanganChecked = document.querySelectorAll('input[name="ruangan_ids[]"]:checked').length;
        // Hanya validasi jika container ruangan sudah ada isinya
        if (document.getElementById('ruangan_selection') && ruanganChecked === 0) {
            alert('Error: Anda harus memilih minimal satu Ruangan.');
            event.preventDefault();
            return;
        }
    });
    // --- AKHIR PENAMBAHAN JAVASCRIPT ---

    // --- DYNAMIC HIERARCHICAL CHECKBOX LOGIC (Tidak ada perubahan) ---
    const gedungSelection = document.getElementById('gedung_selection');
    const lantaiContainer = document.getElementById('lantai_selection_container');
    const ruanganContainer = document.getElementById('ruangan_selection_container');
    const lantaiLoader = document.getElementById('lantai_loader');
    const ruanganLoader = document.getElementById('ruangan_loader');

    gedungSelection.addEventListener('change', function() {
        const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input[type=checkbox]:checked')).map(cb => cb.value);

        lantaiContainer.innerHTML = '<div class="checkbox-placeholder"><p>Pilih Gedung terlebih dahulu.</p></div>';
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p>Pilih Lantai terlebih dahulu.</p></div>';
        
        const oldLantaiSelection = document.getElementById('lantai_selection');
        if (oldLantaiSelection) {
            oldLantaiSelection.removeEventListener('change', lantaiChangeListener);
        }

        if (selectedGedungIds.length > 0) {
            lantaiLoader.style.display = 'inline-block';
            const queryString = selectedGedungIds.map(id => `gedung_ids[]=${id}`).join('&');
            
            fetch(`get_lantai.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '<div id="lantai_selection" class="checkbox-group-modern">';
                        data.forEach(lantai => {
                            html += `
                                <div class="checkbox-item">
                                    <input type="checkbox" class="lantai-checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}">
                                    <label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (${lantai.gedung_nama})</label>
                                </div>
                            `;
                        });
                        html += '</div>';
                        lantaiContainer.innerHTML = html;
                        document.getElementById('lantai_selection').addEventListener('change', lantaiChangeListener);
                    } else {
                        lantaiContainer.innerHTML = '<div class="checkbox-placeholder"><p>Tidak ada lantai ditemukan untuk gedung yang dipilih.</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching lantai:', error);
                    lantaiContainer.innerHTML = '<div class="checkbox-placeholder"><p style="color: red;">Gagal memuat data lantai.</p></div>';
                })
                .finally(() => {
                    lantaiLoader.style.display = 'none';
                });
        }
    });

    function lantaiChangeListener(e) {
        const lantaiSelection = e.currentTarget;
        const selectedLantaiIds = Array.from(lantaiSelection.querySelectorAll('input[type=checkbox]:checked')).map(cb => cb.value);
            
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p>Pilih Lantai terlebih dahulu.</p></div>';

        if (selectedLantaiIds.length > 0) {
            ruanganLoader.style.display = 'inline-block';
            const queryString = selectedLantaiIds.map(id => `lantai_ids[]=${id}`).join('&');
            
            fetch(`get_ruangan.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '<div id="ruangan_selection" class="checkbox-group-modern">';
                        data.forEach(ruangan => {
                            html += `
                                <div class="checkbox-item">
                                    <input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}">
                                    <label for="ruangan_${ruangan.ruangan_id}">${ruangan.ruangan_nama} (Lantai ${ruangan.lantai_nomor}, ${ruangan.gedung_nama})</label>
                                </div>
                            `;
                        });
                        html += '</div>';
                        ruanganContainer.innerHTML = html;
                    } else {
                        ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p>Tidak ada ruangan tersedia untuk lantai yang dipilih.</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching ruangan:', error);
                    ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p style="color: red;">Gagal memuat data ruangan.</p></div>';
                })
                .finally(() => {
                    ruanganLoader.style.display = 'none';
                });
        }
    }

    // Initial step display
    showStep(currentStep);
</script>
</body>
</html>