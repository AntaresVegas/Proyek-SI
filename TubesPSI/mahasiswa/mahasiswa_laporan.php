<?php
session_start();
include '../config/db_connection.php';

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
    // ======================================================
    // ## KODE DIPERBAIKI: Menggunakan skema database baru ##
    // ======================================================
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
            // ======================================================
            // ## KODE DIPERBAIKI: Menggunakan skema database baru ##
            // ======================================================
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary-color: rgb(2, 71, 25); --secondary-color: #0d6efd; --status-rejected: #dc3545; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding-top: 80px; background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; display: flex; flex-direction: column; min-height: 100%; }
        .content-wrapper { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:white; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; gap: 25px;}
        .navbar-menu li a { text-decoration: none; color:white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { color:white; }
        .icon { font-size: 20px; }
        .container { max-width: 700px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; color: #2c3e50; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 18px; }
        .form-group select, .form-control-file { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; }
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn { padding: 12px 28px; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
        .btn-clear { background-color: #6c757d; color: white; }
        .btn-submit { background-color: #198754; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .upload-message { position: fixed; top: 80px; right: 20px; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1001; opacity: 0; transition: opacity 0.5s; pointer-events: none; }
        .upload-message.success { background-color: #198754; color: white; }
        .upload-message.error { background-color: #dc3545; color: white; }
        .upload-message.show { opacity: 1; }
        .template-info-box { background-color: #e9f5ff; border-left: 5px solid #0d6efd; border-radius: 8px; padding: 20px; margin-bottom: 25px; }
        .template-info-box .template-title { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 15px; }
        .btn-download-template { display: block; width: 100%; padding: 12px; text-align: center; background-color: #0d6efd; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: background-color 0.3s; margin-bottom: 15px; }
        .btn-download-template:hover { background-color: #0b5ed7; }
        .btn-download-template i { margin-right: 8px; }
        .template-note { font-size: 14px; color: #555; line-height: 1.5; margin: 0; text-align: center; }
        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #e9ecef; font-size: 1.5em; }
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
        <div class="header"><h1>Laporan Pertanggungjawaban Event</h1></div>
        <form action="mahasiswa_laporan.php" method="POST" enctype="multipart/form-data" id="lpjForm">
            <div class="form-group">
                <label for="pengajuan_id">Pilih Event yang Telah Selesai</label>
                <select id="pengajuan_id" name="pengajuan_id" required>
                    <option value="">-- Pilih Event --</option>
                    <?php if (!empty($events_for_lpj)): ?>
                        <?php foreach ($events_for_lpj as $event): ?>
                            <?php 
                                $displayName = htmlspecialchars($event['pengajuan_namaEvent']);
                                $displayDate = htmlspecialchars(date('d M Y', strtotime($event['pengajuan_event_tanggal_mulai'])));
                                $statusInfo = ($event['pengajuan_statusLPJ'] == 'Ditolak') ? ' - (LPJ Ditolak, harap unggah ulang)' : '';
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
            <div class="template-info-box">
                <p class="template-title">Gunakan Template Resmi Untuk Laporan Anda</p>
                <a href="../templates/LPJ_Template.docx" class="btn-download-template" download><i class="fas fa-download"></i> Unduh Template LPJ</a>
                <p class="template-note"><strong>Penting:</strong> Pastikan laporan dibuat sesuai dengan template yang disediakan dan berikan penamaan file dengan nama <span style="color: red;"><b>LPJ_NamaEvent</b></span>.</p>
            </div>
            <div class="form-group">
                <label for="dokumen_lpj">Unggah Dokumen LPJ Anda</label>
                <input type="file" id="dokumen_lpj" name="dokumen_lpj" class="form-control-file" accept=".pdf,.doc,.docx" required>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-clear" onclick="document.getElementById('lpjForm').reset();">Clear</button>
                <button type="submit" name="submit_lpj" class="btn btn-submit">Submit LPJ</button>
            </div>
        </form>
    </div>
</div>
<div id="uploadMessage" class="upload-message <?php echo !empty($message) ? 'show ' . $message_type : ''; ?>"><?php echo htmlspecialchars($message); ?></div>
<script>
    window.onload = function() { const messageDiv = document.getElementById('uploadMessage'); if (messageDiv.textContent.trim() !== '') { setTimeout(() => { messageDiv.classList.add('show'); setTimeout(() => { messageDiv.classList.remove('show'); if (window.history.replaceState) { const url = new URL(window.location.href); url.searchParams.delete('status'); url.searchParams.delete('msg'); window.history.replaceState({}, document.title, url.href); } }, 5000); }, 100); } };
</script>
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
</body>
</html>