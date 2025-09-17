<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$selected_bulan = $_GET['bulan'] ?? '';
$selected_tahun = $_GET['tahun'] ?? '';
$kegiatan_data = [];

// =================================================================
// ## LOGIKA SORTING DINAMIS YANG SUDAH DIPERBAIKI ##
// =================================================================
$sort_by = $_GET['sort'] ?? 'pengajuan'; // Default: 'pengajuan', atau 'event' jika ada di URL

// Tentukan teks tombol dan klausa ORDER BY berdasarkan kondisi saat ini
if ($sort_by === 'event') {
    // Jika saat ini diurutkan berdasarkan TANGGAL EVENT
    $order_by_clause = "pe.pengajuan_event_tanggal_mulai DESC";
    $sort_button_text = "Urutkan Berdasarkan Tgl Pengajuan";
} else {
    // Jika saat ini diurutkan berdasarkan TANGGAL PENGAJUAN (default)
    $order_by_clause = "pe.pengajuan_tanggalEdit DESC";
    $sort_button_text = "Urutkan Berdasarkan Tgl Event";
}

// Logika untuk membuat URL tombol agar bisa kembali (toggle)
$query_params = $_GET; // Ambil semua parameter URL yang ada (misal: bulan, tahun)
if ($sort_by === 'event') {
    // Jika kondisi saat ini adalah 'event', tombol harus kembali ke default.
    // Caranya adalah dengan MENGHAPUS parameter 'sort' dari URL.
    unset($query_params['sort']);
} else {
    // Jika kondisi saat ini adalah default, tombol harus menuju ke kondisi 'event'.
    // Caranya adalah dengan MENAMBAHKAN parameter 'sort=event' ke URL.
    $query_params['sort'] = 'event';
}
$sort_button_url = 'ditmawa_listKegiatan.php?' . http_build_query($query_params);
// =================================================================
// ## AKHIR BLOK LOGIKA SORTING ##
// =================================================================

try {
    if (isset($conn)) {
        $sql = "
            SELECT 
                pe.pengajuan_id, 
                pe.pengajuan_namaEvent, 
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_tanggalEdit,
                pe.pengajuan_status,
                CASE
                    WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_nama
                    WHEN pe.pengaju_tipe = 'ditmawa' THEN d.ditmawa_nama
                    ELSE 'Tidak Diketahui'
                END AS nama_pengaju,
                CASE
                    WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_npm
                    ELSE 'STAFF DITMAWA'
                END AS identitas_pengaju
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
        ";
        
        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($selected_bulan)) { $conditions[] = "MONTH(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_bulan; $types .= "i"; }
        if (!empty($selected_tahun)) { $conditions[] = "YEAR(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_tahun; $types .= "i"; }

        if (count($conditions) > 0) { $sql .= " WHERE " . implode(' AND ', $conditions); }
        
        // Menggunakan klausa ORDER BY yang sudah dinamis
        $sql .= " ORDER BY " . $order_by_clause;

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) { $kegiatan_data[] = $row; }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    error_log("Error fetching event data: " . $e->getMessage());
}
$conn->close();

$current_year = date('Y');
$years = range($current_year, $current_year - 5);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>List Kegiatan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100%; padding-top: 80px; display: flex; flex-direction: column; }
        .main-content { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(249, 249, 249); }
        .icon { font-size: 20px; cursor: pointer; }
        .kegiatan-container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .kegiatan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;}
        .kegiatan-header h1 { font-size: 32px; color: #2c3e50; margin: 0; }
        .header-buttons { display: flex; gap: 10px; align-items: center; }
        .view-graph-button, .view-sort-button { color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s; }
        .view-graph-button { background-color: #28a745; }
        .view-graph-button:hover { background-color: #218838; }
        .view-sort-button { background-color: #17a2b8; }
        .view-sort-button:hover { background-color: #138496; }
        .filter-form { display: flex; gap: 15px; margin-bottom: 25px; justify-content: center; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-form select, .filter-form button { padding: 8px 12px; border-radius: 5px; border: 1px solid #ced4da; }
        .filter-form button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        .kegiatan-table-container { overflow-x: auto; }
        .kegiatan-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .kegiatan-table th, .kegiatan-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; white-space: nowrap; }
        .kegiatan-table th { background-color: #f2f2f2; }
        .status-badge { padding: 5px 10px; border-radius: 15px; color: white; font-weight: bold; font-size: 12px; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
        .view-form-button { background-color: #007bff; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .page-footer { background-color: #ff8c00; color: #fff; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #2c3e50; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_pengajuan.php">Form Pengajuan</a></li>
        <li><a href="ditmawa_listKegiatan.php" class="active">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="kegiatan-container">
        <div class="kegiatan-header">
            <h1>List Data Pengajuan Event</h1>
            <div class="header-buttons">
                <a href="<?php echo $sort_button_url; ?>" class="view-sort-button">
                    <i class="fas fa-sort-amount-down"></i> <?php echo $sort_button_text; ?>
                </a>
                <a href="ditmawa_grafikKegiatan.php" class="view-graph-button">
                    <i class="fas fa-chart-bar"></i> Lihat Grafik
                </a>
            </div>
        </div>

        <form method="GET" class="filter-form">
            <label for="bulan">Bulan:</label>
            <select name="bulan" id="bulan">
                <option value="">Semua Bulan</option>
                <?php $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                foreach ($months as $num => $name) { echo '<option value="' . $num . '" ' . ($selected_bulan == $num ? 'selected' : '') . '>' . $name . '</option>'; } ?>
            </select>
            <label for="tahun">Tahun:</label>
            <select name="tahun" id="tahun">
                <option value="">Semua Tahun</option>
                <?php foreach ($years as $year) { echo '<option value="' . $year . '" ' . ($selected_tahun == $year ? 'selected' : '') . '>' . $year . '</option>'; } ?>
            </select>
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <button type="submit">Filter</button>
        </form>
        <div class="kegiatan-table-container">
            <table class="kegiatan-table">
                <thead>
                    <tr>
                        <th>Tgl Diajukan</th>
                        <th>Tgl Event</th>
                        <th>Nama Pengaju</th>
                        <th>NPM / Identitas</th>
                        <th>Nama Acara</th>
                        <th>Status</th>
                        <th>Form Pengajuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($kegiatan_data)): ?>
                        <?php foreach ($kegiatan_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d F Y H:i', strtotime($row['pengajuan_tanggalEdit']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d F Y', strtotime($row['pengajuan_event_tanggal_mulai']))); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pengaju']); ?></td>
                                <td><?php echo htmlspecialchars($row['identitas_pengaju']); ?></td>
                                <td><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></td>
                                <td><span class="status-badge <?php echo strtolower(htmlspecialchars($row['pengajuan_status'])); ?>"><?php echo htmlspecialchars($row['pengajuan_status']); ?></span></td>
                                <td><a href="ditmawa_editForm.php?id=<?php echo $row['pengajuan_id']; ?>" class="view-form-button"><i class="fas fa-file-alt"></i> Lihat Form</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">Tidak ada data kegiatan event untuk filter ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;color :black">DIREKTORAT KEMAHASISWAAN</h3>
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