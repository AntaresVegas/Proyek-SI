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
    $pengajuan_TypeKegiatan = $_POST['pengajuan_TypeKegiatan'];
    $pengajuan_event_jam_mulai = $_POST['pengajuan_event_jam_mulai'];
    $pengajuan_event_jam_selesai = $_POST['pengajuan_event_jam_selesai'];
    $pengajuan_event_tanggal_mulai = $_POST['pengajuan_event_tanggal_mulai'];
    $pengajuan_event_tanggal_selesai = $_POST['pengajuan_event_tanggal_selesai'];
    // **BARU**: Ambil tanggal persiapan dan beres, set ke NULL jika kosong
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

    if ($message_type !== 'error') {
        // **DIUBAH**: Query INSERT diperbarui dengan kolom tanggal_persiapan dan tanggal_beres
        $stmt = $conn->prepare("
            INSERT INTO pengajuan_event (
                pengajuan_namaEvent, mahasiswa_id, pengajuan_TypeKegiatan,
                pengajuan_event_jam_mulai, pengajuan_event_jam_selesai,
                pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai,
                tanggal_persiapan, tanggal_beres,
                jadwal_event_rundown_file, pengajuan_event_proposal_file,
                pengajuan_status, pengajuan_tanggalEdit
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diajukan', NOW())
        ");

        // **DIUBAH**: bind_param diperbarui untuk mencocokkan kolom baru (menjadi 11 parameter)
        $stmt->bind_param("sisssssssss",
            $pengajuan_namaEvent, $user_id, $pengajuan_TypeKegiatan,
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
        .date-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

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
        <li><a href="mahasiswa_pengajuan.php"class="active">Form</a></li>
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
                <?php echo $message; ?>
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
                        <input type="text" id="pengajuan_TypeKegiatan" name="pengajuan_TypeKegiatan" placeholder="Contoh: Seminar, Workshop, Lomba" required>
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
                        <label for="gedung_selection">Pilih Gedung (bisa lebih dari satu)</label>
                        <div id="gedung_selection" class="checkbox-group">
                            <?php foreach ($gedung_options as $gedung): ?>
                                <div>
                                    <input type="checkbox" class="gedung-checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>">
                                    <label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lantai_selection">Pilih Lantai (bisa lebih dari satu)</label>
                        <div id="lantai_selection" class="checkbox-group disabled">
                             <p>Pilih Gedung terlebih dahulu.</p>
                        </div>
                        <span class="loader" id="lantai_loader"></span>
                    </div>
                    <div class="form-group">
                        <label for="ruangan_selection">Pilih Ruangan (bisa lebih dari satu)</label>
                        <div id="ruangan_selection" class="checkbox-group disabled">
                            <p>Pilih Lantai terlebih dahulu.</p>
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

                    <!-- <div class="form-note">
                        <i class="fas fa-info-circle"></i>
                        <strong>Catatan Penting Peminjaman Tanggal</strong>
                        <p>
                            Harap perhitungkan waktu yang dibutuhkan untuk <b>persiapan (loading)</b> sebelum acara dan <b>pemberesan (clearing)</b> setelah acara.
                            <br><b>Contoh:</b> Jika acara utama Anda tanggal 25, namun butuh persiapan tanggal 24 dan pemberesan tanggal 26, maka Anda harus memilih "Tanggal Mulai Acara" di <b>24</b> dan "Tanggal Selesai Acara" di <b>26</b>.
                        </p>
                    </div> -->

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
                        <label for="jadwal_event_rundown_file" class="file-upload-label">Rundown Acara (PDF, DOCX)</label>
                        <div class="custom-file-upload">
                            <input type="file" id="jadwal_event_rundown_file" name="jadwal_event_rundown_file" accept=".pdf,.doc,.docx" required class="hidden-file-input">
                            <button type="button" class="upload-button" onclick="document.getElementById('jadwal_event_rundown_file').click()">Pilih File Rundown</button>
                            <span class="file-name" id="rundown_file_name">Belum ada file dipilih</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pengajuan_event_proposal_file" class="file-upload-label">Proposal Penyelenggaraan Kegiatan (PDF, DOCX)</label>
                        <div class="custom-file-upload">
                            <input type="file" id="pengajuan_event_proposal_file" name="pengajuan_event_proposal_file" accept=".pdf,.doc,.docx" required class="hidden-file-input">
                            <button type="button" class="upload-button" onclick="document.getElementById('pengajuan_event_proposal_file').click()">Pilih File Proposal</button>
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

    function nextStep() {
        const namaEvent = document.getElementById('pengajuan_namaEvent').value;
        const typeKegiatan = document.getElementById('pengajuan_TypeKegiatan').value;
        if (namaEvent.trim() === '' || typeKegiatan.trim() === '') {
            alert('Harap isi semua kolom Nama Event dan Tipe Kegiatan pada langkah ini.');
            return;
        }
        currentStep++;
        showStep(currentStep);
    }

    function prevStep() {
        currentStep--;
        showStep(currentStep);
    }
    
    // Updated clearForm function
    function clearForm(stepId) {
        const stepElement = document.getElementById(stepId);
        const inputs = stepElement.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Uncheck all checkboxes
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } 
            // Clear file inputs and their display names
            else if (input.type === 'file') {
                input.value = ''; // Clear file input
                 if (input.id === 'jadwal_event_rundown_file') {
                    document.getElementById('rundown_file_name').textContent = 'Belum ada file dipilih';
                } else if (input.id === 'pengajuan_event_proposal_file') {
                    document.getElementById('proposal_file_name').textContent = 'Belum ada file dipilih';
                }
            } 
            // Reset select dropdowns
            else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } 
            // Clear all other inputs
            else {
                input.value = '';
            }
        });
        
        // Specifically reset Step 2 dynamic fields
        if (stepId === 'step2') {
            const lantaiSelection = document.getElementById('lantai_selection');
            lantaiSelection.innerHTML = '<p>Pilih Gedung terlebih dahulu.</p>';
            lantaiSelection.classList.add('disabled');

            const ruanganSelection = document.getElementById('ruangan_selection');
            ruanganSelection.innerHTML = '<p>Pilih Lantai terlebih dahulu.</p>';
            ruanganSelection.classList.add('disabled');
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


    // --- NEW DYNAMIC HIERARCHICAL CHECKBOX LOGIC ---

    const gedungSelection = document.getElementById('gedung_selection');
    const lantaiSelection = document.getElementById('lantai_selection');
    const ruanganSelection = document.getElementById('ruangan_selection');
    const lantaiLoader = document.getElementById('lantai_loader');
    const ruanganLoader = document.getElementById('ruangan_loader');

    // Listener for Gedung checkboxes
    gedungSelection.addEventListener('change', function() {
        const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input[type=checkbox]:checked'))
            .map(cb => cb.value);

        // Reset lower levels
        lantaiSelection.innerHTML = '<p>Pilih Gedung terlebih dahulu.</p>';
        lantaiSelection.classList.add('disabled');
        ruanganSelection.innerHTML = '<p>Pilih Lantai terlebih dahulu.</p>';
        ruanganSelection.classList.add('disabled');

        if (selectedGedungIds.length > 0) {
            lantaiLoader.style.display = 'inline-block';
            
            // Build query string for multiple IDs
            const queryString = selectedGedungIds.map(id => `gedung_ids[]=${id}`).join('&');
            
            fetch(`get_lantai.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    lantaiSelection.innerHTML = ''; // Clear
                    if (data.length > 0) {
                        data.forEach(lantai => {
                            const div = document.createElement('div');
                            div.innerHTML = `
                                <input type="checkbox" class="lantai-checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}">
                                <label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (${lantai.gedung_nama})</label>
                            `;
                            lantaiSelection.appendChild(div);
                        });
                        lantaiSelection.classList.remove('disabled');
                    } else {
                        lantaiSelection.innerHTML = '<p>Tidak ada lantai ditemukan untuk gedung yang dipilih.</p>';
                        lantaiSelection.classList.add('disabled');
                    }
                })
                .catch(error => {
                    console.error('Error fetching lantai:', error);
                    lantaiSelection.innerHTML = '<p style="color: red;">Gagal memuat data lantai.</p>';
                })
                .finally(() => {
                    lantaiLoader.style.display = 'none';
                });
        }
    });

    // Listener for Lantai checkboxes
    lantaiSelection.addEventListener('change', function(e) {
        // Only proceed if the change was on a checkbox, not the container
        if (e.target.type !== 'checkbox') return;

        const selectedLantaiIds = Array.from(lantaiSelection.querySelectorAll('input[type=checkbox]:checked'))
            .map(cb => cb.value);
            
        // Reset ruangan
        ruanganSelection.innerHTML = '<p>Pilih Lantai terlebih dahulu.</p>';
        ruanganSelection.classList.add('disabled');

        if (selectedLantaiIds.length > 0) {
            ruanganLoader.style.display = 'inline-block';
            
            const queryString = selectedLantaiIds.map(id => `lantai_ids[]=${id}`).join('&');
            
            fetch(`get_ruangan.php?${queryString}`)
                .then(response => response.json())
                .then(data => {
                    ruanganSelection.innerHTML = ''; // Clear
                    if (data.length > 0) {
                        data.forEach(ruangan => {
                            const div = document.createElement('div');
                            div.innerHTML = `
                                <input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}">
                                <label for="ruangan_${ruangan.ruangan_id}">${ruangan.ruangan_nama} (Lantai ${ruangan.lantai_nomor}, ${ruangan.gedung_nama})</label>
                            `;
                            ruanganSelection.appendChild(div);
                        });
                        ruanganSelection.classList.remove('disabled');
                    } else {
                        ruanganSelection.innerHTML = '<p>Tidak ada ruangan tersedia untuk lantai yang dipilih.</p>';
                        ruanganSelection.classList.add('disabled');
                    }
                })
                .catch(error => {
                    console.error('Error fetching ruangan:', error);
                    ruanganSelection.innerHTML = '<p style="color: red;">Gagal memuat data ruangan.</p>';
                })
                .finally(() => {
                    ruanganLoader.style.display = 'none';
                });
        }
    });

    // Initial step display
    showStep(currentStep);
</script>
</body>
</html>