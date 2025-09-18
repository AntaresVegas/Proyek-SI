<?php
session_start();
include '../config/db_connection.php';

// --- (Blok PHP Anda tidak berubah, karena fungsinya sudah benar) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}
$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = "File LPJ berhasil terkirim!";
        $message_type = "success";
    } elseif ($_GET['status'] === 'error') {
        $message = urldecode($_GET['msg'] ?? "Terjadi kesalahan.");
        $message_type = "error";
    }
}
$events_for_lpj = [];
if (isset($user_id)) {
    $stmt = $conn->prepare("
        SELECT pengajuan_id, pengajuan_namaEvent, pengajuan_event_tanggal_mulai, pengajuan_statusLPJ 
        FROM pengajuan_event 
        WHERE pengaju_id = ? AND pengaju_tipe = 'mahasiswa'
          AND pengajuan_status = 'Disetujui' 
          AND ((pengajuan_LPJ IS NULL OR pengajuan_LPJ = '') OR pengajuan_statusLPJ = 'Ditolak')
        ORDER BY pengajuan_event_tanggal_mulai DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events_for_lpj[] = $row;
    }
    $stmt->close();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_lpj'])) {
    $selected_pengajuan_id = $_POST['pengajuan_id'] ?? '';
    if (empty($selected_pengajuan_id)) { header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Silakan pilih event terlebih dahulu.")); exit(); }
    if (isset($_FILES['dokumen_lpj']) && $_FILES['dokumen_lpj']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['dokumen_lpj']['tmp_name'];
        $file_name = basename($_FILES['dokumen_lpj']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_extensions)) { header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Ekstensi file tidak valid.")); exit(); }
        
        $db_path = 'uploads/lpj/' . uniqid('lpj_', true) . '.' . $file_ext;
        $upload_path = '../' . $db_path;
        if (!is_dir(dirname($upload_path))) { mkdir(dirname($upload_path), 0777, true); }

        if (move_uploaded_file($file_tmp_name, $upload_path)) {
            $update_stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_LPJ = ?, pengajuan_statusLPJ = 'Menunggu Persetujuan' WHERE pengajuan_id = ? AND pengaju_id = ? AND pengaju_tipe = 'mahasiswa'");
            $update_stmt->bind_param("sii", $db_path, $selected_pengajuan_id, $user_id);
            
            if ($update_stmt->execute()) { header("Location: mahasiswa_laporan.php?status=success"); exit(); } 
            else { unlink($upload_path); header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Gagal menyimpan data LPJ.")); exit(); }
        } else { header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Gagal mengunggah file.")); exit(); }
    } else { header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Silakan pilih file LPJ untuk diunggah.")); exit(); }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload LPJ - Event Management Unpar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ====================================================== */
        /* ## CSS BARU: Desain yang lebih modern ## */
        /* ====================================================== */
        :root { 
            --primary-color: rgb(2, 71, 25); 
            --secondary-color: #0d6efd; 
            --accent-color: #198754;
            --text-dark: #2c3e50;
            --text-light: #5a6a7a;
            --bg-light: #f4f7f6;
            --border-color: #dee2e6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { 
            font-family: 'Poppins', 'Segoe UI', sans-serif; 
            background: var(--bg-light); 
            padding-top: 80px; 
            background-image: url('../img/backgroundUnpar.jpeg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            color: var(--text-dark);
        }

        /* Navbar & Footer (Sama seperti sebelumnya, tidak diubah) */
        .navbar { display: flex; justify-content: space-between; align-items: center; background:var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:white; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; gap: 25px;}
        .navbar-menu li a { text-decoration: none; color:white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { color:white; }
        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        /* ... Sisa CSS Navbar & Footer */

        .container { 
            max-width: 800px; 
            margin: 40px auto; 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1); 
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header { 
            text-align: center; 
            margin-bottom: 40px; 
        }
        .header h1 { 
            font-size: 32px; 
            font-weight: 700;
            color: var(--text-dark); 
        }
        .header p {
            font-size: 16px;
            color: var(--text-light);
            margin-top: 10px;
        }

        /* Desain Step-by-Step (Wizard) */
        .step {
            display: flex;
            gap: 25px;
            margin-bottom: 35px;
        }
        .step-number {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: 600;
        }
        .step-content {
            width: 100%;
        }
        .step-content label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 18px;
        }
        select, .form-control-file {
            width: 100%;
            padding: 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
        }

        /* Desain Box Template yang Diperbarui */
        .template-info-box {
            background-color: #e9f0ff;
            border-left: 5px solid var(--secondary-color);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .template-info-box i {
            font-size: 24px;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        .template-info-box .template-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        .btn-download-template {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        .btn-download-template:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }
        .template-note {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Desain Area Upload File Baru */
        .file-upload-area {
            position: relative;
            width: 100%;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .file-upload-area:hover {
            border-color: var(--accent-color);
            background-color: #f9fbf9;
        }
        .file-upload-area i {
            font-size: 40px;
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        .file-upload-text {
            font-size: 16px;
            color: var(--text-light);
        }
        .file-upload-text strong {
            color: var(--accent-color);
        }
        #dokumen_lpj {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        #file-name-display {
            margin-top: 15px;
            font-size: 14px;
            color: var(--text-dark);
            font-weight: 500;
        }

        /* Tombol Aksi */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 30px;
        }
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-clear {
            background-color: #6c757d;
            color: white;
        }
        .btn-submit {
            background-color: var(--accent-color);
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        /* Notifikasi (Sama seperti sebelumnya) */
        .upload-message { position: fixed; top: 80px; right: 20px; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1001; opacity: 0; transition: opacity 0.5s; pointer-events: none; }
        .upload-message.success { background-color: var(--accent-color); color: white; }
        .upload-message.error { background-color: #dc3545; color: white; }
        .upload-message.show { opacity: 1; }
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
        <li><a href="mahasiswa_laporan.php" class="active">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right"><a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 15px;"></i></a><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a></div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <div class="header">
            <h1>Laporan Pertanggungjawaban</h1>
            <p>Unggah LPJ untuk event yang telah disetujui dan diselenggarakan.</p>
        </div>
        
        
        <form action="mahasiswa_laporan.php" method="POST" enctype="multipart/form-data" id="lpjForm">

            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <label for="pengajuan_id">Pilih Event yang Telah Selesai</label>
                    <select id="pengajuan_id" name="pengajuan_id" required>
                        <option value="">-- Klik untuk memilih event --</option>
                        <?php if (!empty($events_for_lpj)): ?>
                            <?php foreach ($events_for_lpj as $event): ?>
                                <?php 
                                    $displayName = htmlspecialchars($event['pengajuan_namaEvent']);
                                    $displayDate = htmlspecialchars(date('d M Y', strtotime($event['pengajuan_event_tanggal_mulai'])));
                                    $statusInfo = ($event['pengajuan_statusLPJ'] == 'Ditolak') ? ' - (LPJ Ditolak, unggah ulang)' : '';
                                ?>
                                <option value="<?php echo htmlspecialchars($event['pengajuan_id']); ?>">
                                    <?php echo $displayName . ' (' . $displayDate . ')' . $statusInfo; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Tidak ada event yang perlu di-LPJ-kan</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <label>Siapkan Dokumen Sesuai Template</label>
                    <div class="template-info-box">
                        <i class="fas fa-file-alt"></i>
                        <p class="template-title">Gunakan Template Resmi Untuk Laporan Anda</p>
                        <a href="../templates/LPJ_Template.docx" class="btn-download-template" download><i class="fas fa-download"></i> Unduh Template LPJ</a>
                        <p class="template-note"><strong>Penting:</strong> Pastikan file laporan diberi nama <strong>LPJ_NamaEvent</strong> (contoh: LPJ_ScienceFest.pdf)</p>
                    </div>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <label for="dokumen_lpj">Unggah Dokumen LPJ Anda</label>
                    <div class="file-upload-area">
                        <input type="file" id="dokumen_lpj" name="dokumen_lpj" accept=".pdf,.doc,.docx" required>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="file-upload-text" id="file-upload-text">Tarik & Lepas file di sini, atau <strong>klik untuk memilih file</strong>.</p>
                        <p id="file-name-display"></p>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-clear" onclick="clearForm()">Clear</button>
                <button type="submit" name="submit_lpj" class="btn btn-submit">Submit LPJ</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadMessage" class="upload-message <?php echo !empty($message) ? 'show ' . $message_type : ''; ?>"><?php echo htmlspecialchars($message); ?></div>

<footer class="page-footer">
    </footer>

<script>
    // Script notifikasi (sama seperti sebelumnya)
    window.onload = function() { const messageDiv = document.getElementById('uploadMessage'); if (messageDiv.textContent.trim() !== '') { setTimeout(() => { messageDiv.classList.add('show'); setTimeout(() => { messageDiv.classList.remove('show'); if (window.history.replaceState) { const url = new URL(window.location.href); url.searchParams.delete('status'); url.searchParams.delete('msg'); window.history.replaceState({}, document.title, url.href); } }, 5000); }, 100); } };

    // Script untuk area upload file kustom
    const fileInput = document.getElementById('dokumen_lpj');
    const uploadText = document.getElementById('file-upload-text');
    const fileNameDisplay = document.getElementById('file-name-display');

    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const fileName = this.files[0].name;
            uploadText.style.display = 'none'; // Sembunyikan teks asli
            fileNameDisplay.textContent = 'File terpilih: ' + fileName; // Tampilkan nama file
        } else {
            resetUploadArea();
        }
    });

    // Fungsi untuk mereset form dan area upload
    function clearForm() {
        document.getElementById('lpjForm').reset();
        resetUploadArea();
    }

    function resetUploadArea() {
        uploadText.style.display = 'block'; // Tampilkan kembali teks asli
        fileNameDisplay.textContent = ''; // Kosongkan nama file
    }
</script>

</body>
</html>