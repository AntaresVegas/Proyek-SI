<?php
session_start();
include '../config/db_connection.php';

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

$pengajuan_events = [];

if ($user_id !== 'No ID') {
    $stmt = $conn->prepare("
        SELECT pengajuan_id, pengajuan_event_tanggal_mulai, pengajuan_namaEvent, pengajuan_status, pengajuan_tanggalEdit, pengajuan_komentarDitmawa
        FROM pengajuan_event
        WHERE mahasiswa_id = ?
        ORDER BY pengajuan_id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pengajuan_events[] = $row;
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
    <title>History Pengajuan Event - Event Management Unpar</title>
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
        .navbar { display: flex; justify-content: space-between; align-items: center; background:var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:white; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:white; }
        .icon { font-size: 20px; cursor: pointer; }
        .container { max-width: 1100px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; margin: -30px -30px 30px -30px; border-radius: 15px 15px 0 0; }
        .header h1 { font-size: 24px; }
        .kembali-button { background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #f8f9fa; font-weight: 600; text-transform: uppercase; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .no-data { text-align: center; padding: 20px; color: #777; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; color: white; text-align: center; font-size: 12px; text-transform: capitalize; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
        .action-button { background-color: #007bff; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; border: none; font-family: 'Segoe UI'; }
        .action-button:hover { background-color: #0056b3; }
        .action-disabled { display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; border-radius: 5px; background-color: #6c757d; color: white; font-size: 14px; font-weight: 500; cursor: not-allowed; }
        .alasan-ditolak { font-size: 13px; color: #dc3545; margin-top: 4px; font-style: italic; }
        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; }
        .footer-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
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
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <div class="header">
            <h1>History Pengajuan Event</h1>
            <a href="mahasiswa_history.php" class="kembali-button">Kembali</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>TANGGAL EVENT</th>
                    <th>NAMA EVENT</th>
                    <th>STATUS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pengajuan_events)): ?>
                    <?php foreach ($pengajuan_events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($event['pengajuan_event_tanggal_mulai']))); ?></td>
                            <td>
                                <?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?>
                                <?php if ($event['pengajuan_status'] == 'Ditolak' && !empty($event['pengajuan_komentarDitmawa'])): ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo strtolower(htmlspecialchars($event['pengajuan_status'])); ?>">
                                    <?php echo htmlspecialchars($event['pengajuan_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($event['pengajuan_status'] == 'Disetujui'): ?>
                                    <span class="action-disabled" title="Pengajuan yang sudah disetujui tidak dapat diedit.">
                                        <i class="fas fa-lock"></i> Terkunci
                                    </span>
                                <?php else: ?>
                                    <a href="mahasiswa_editForm.php?id=<?php echo $event['pengajuan_id']; ?>" class="action-button">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">Belum ada pengajuan event.</td>
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