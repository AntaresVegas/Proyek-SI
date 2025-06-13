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
    // === PERBAIKAN QUERY: Mengambil kolom komentar dan mengubah status 'Revisi' menjadi 'Ditolak' ===
    $stmt = $conn->prepare("
        SELECT
            pe.pengajuan_id,
            pe.pengajuan_namaEvent AS nama_acara,
            pe.pengajuan_event_tanggal_selesai AS tanggal_upload,
            pe.pengajuan_LPJ,
            pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ -- KOLOM BARU YANG DIAMBIL
        FROM
            pengajuan_event pe
        WHERE
            pe.mahasiswa_id = ? AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 80px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #87CEEB; }
        .icon { font-size: 20px; color: white; }
        .container { max-width: 1200px; margin: 20px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-radius: 10px; }
        .header h1 { font-size: 24px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #f8f9fa; font-weight: 600; text-transform: uppercase; }
        .download-button { background-color: #0d6efd; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .kembali-button { background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        .no-data { text-align: center; padding: 20px; color: #777; }
        .alasan-ditolak { font-size: 13px; color: #dc3545; margin-top: 5px; font-style: italic; }

        /* === STYLE BARU UNTUK STATUS BADGE LPJ === */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            color: white;
            text-align: center;
            font-size: 12px;
            text-transform: capitalize;
            white-space: nowrap;
        }
        .status-badge.menunggu-persetujuan { background-color: #ffc107; color: #333; } /* Kuning */
        .status-badge.ditolak { background-color: #dc3545; } /* Merah */
        .status-badge.disetujui { background-color: #28a745; } /* Hijau */
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
        <li><a href="mahasiswa_laporan.php"class="active">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

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
                    // Mengganti spasi dengan tanda hubung untuk nama kelas CSS
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
                                <a href="../<?php echo urlencode($laporan['pengajuan_LPJ']); ?>" class="download-button" download>
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

</body>
</html>