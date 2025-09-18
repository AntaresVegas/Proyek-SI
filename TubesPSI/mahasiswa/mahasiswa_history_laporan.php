<?php
session_start();
include '../config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'];

$laporan_pertanggungjawaban = [];

if ($user_id !== 'No ID') {
    // ================================================
    // ## KODE DIPERBAIKI: Menggunakan skema database baru ##
    // Query disesuaikan untuk menggunakan pengaju_id dan pengaju_tipe.
    // ================================================
    $stmt = $conn->prepare("
        SELECT
            pe.pengajuan_id,
            pe.pengajuan_namaEvent AS nama_acara,
            pe.pengajuan_event_tanggal_selesai AS tanggal_upload,
            pe.pengajuan_LPJ,
            pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ
        FROM
            pengajuan_event pe
        WHERE
            pe.pengaju_id = ? AND pe.pengaju_tipe = 'mahasiswa' 
            AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY
            pe.pengajuan_event_tanggal_selesai DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $laporan_pertanggungjawaban[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: rgb(2, 71, 25);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            min-height: 100%; 
            padding-top: 80px;
            background-image: url('../img/backgroundUnpar.jpeg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; color: white; }
        .container { max-width: 1200px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; margin: -30px -30px 30px -30px; border-radius: 15px 15px 0 0; }
        .header h1 { font-size: 24px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #f8f9fa; font-weight: 600; text-transform: uppercase; }
        .download-button { background-color: #0d6efd; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .kembali-button { background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .no-data { text-align: center; padding: 20px; color: #777; }
        .alasan-ditolak { font-size: 13px; color: #dc3545; margin-top: 5px; font-style: italic; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; color: white; text-align: center; font-size: 12px; text-transform: capitalize; white-space: nowrap; }
        .status-badge.menunggu-persetujuan { background-color: #ffc107; color: #333; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.disetujui { background-color: #28a745; }
        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #e9ecef; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php" class="active">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt iconLog"></i></a>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <div class="header">
            <h1>Riwayat Laporan Pertanggungjawaban</h1>
            <a href="mahasiswa_history.php" class="kembali-button">Kembali</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>TANGGAL UPLOAD</th>
                    <th>NAMA ACARA</th>
                    <th>STATUS LPJ</th>
                    <th>KETERANGAN</th>
                    <th>DOKUMEN LPJ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($laporan_pertanggungjawaban)): ?>
                    <?php foreach ($laporan_pertanggungjawaban as $laporan):
                        $status_class = str_replace(' ', '-', strtolower(htmlspecialchars($laporan['pengajuan_statusLPJ'])));
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d F Y', strtotime($laporan['tanggal_upload']))); ?></td>
                            <td><?php echo htmlspecialchars($laporan['nama_acara']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($laporan['pengajuan_statusLPJ']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($laporan['pengajuan_statusLPJ'] == 'Ditolak' && !empty($laporan['pengajuan_komentarLPJ'])): ?>
                                    <div class="alasan-ditolak">
                                        <strong>Alasan:</strong> <?php echo htmlspecialchars($laporan['pengajuan_komentarLPJ']); ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($laporan['pengajuan_LPJ'])): ?>
                                    <a href="../<?php echo htmlspecialchars($laporan['pengajuan_LPJ']); ?>" class="download-button" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data">Belum ada LPJ yang pernah diunggah.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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

</body>
</html>