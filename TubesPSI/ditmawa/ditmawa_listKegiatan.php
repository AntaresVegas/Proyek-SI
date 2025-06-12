<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php"); // Redirect to login page if not authorized
    exit();
}

// Include database connection
require_once('../config/db_connection.php');

// Set default values for session variables for navbar
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';

// Get selected month and year from GET request
$selected_bulan = $_GET['bulan'] ?? '';
$selected_tahun = $_GET['tahun'] ?? '';

// Array to store event data
$kegiatan_data = [];

try {
    if (isset($conn) && $conn->ping()) {
        // PERUBAHAN 1: Query mengambil pengajuan_id dan tidak lagi memfilter berdasarkan status 'Diajukan'
        $sql = "
            SELECT 
                pe.pengajuan_id,
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_status,
                m.mahasiswa_nama,
                m.mahasiswa_npm
            FROM 
                pengajuan_event pe
            JOIN 
                mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id
        ";

        $conditions = [];
        $params = [];
        $types = "";

        // Add month filter if selected
        if (!empty($selected_bulan) && is_numeric($selected_bulan)) {
            $conditions[] = "MONTH(pe.pengajuan_event_tanggal_mulai) = ?";
            $params[] = $selected_bulan;
            $types .= "i";
        }

        // Add year filter if selected
        if (!empty($selected_tahun) && is_numeric($selected_tahun)) {
            $conditions[] = "YEAR(pe.pengajuan_event_tanggal_mulai) = ?";
            $params[] = $selected_tahun;
            $types .= "i";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Order by date
        $sql .= " ORDER BY pe.pengajuan_event_tanggal_mulai DESC";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $kegiatan_data[] = $row;
                }
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for ditmawa_listKegiatan: " . $conn->error);
        }
    } else {
        error_log("Database connection not established or lost in ditmawa_listKegiatan.php");
    }
} catch (Exception $e) {
    error_log("Error fetching event data: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

// Generate years for the dropdown
$current_year = date('Y');
$years = range($current_year, $current_year - 5);
krsort($years);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>List Kegiatan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* General styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 70px; color: #333; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; object-fit: cover; }
        .navbar-title { color: #2c3e50; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: #2c3e50; font-weight: 500; font-size: 15px; transition: color 0.3s; }
        .navbar-menu li a:hover, .navbar-menu li a.active { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; font-size: 15px; color: #2c3e50; }
        .user-name { font-weight: 500; }
        .icon { font-size: 20px; cursor: pointer; transition: color 0.3s; }
        .icon:hover { color: #007bff; }
        .kegiatan-container { max-width: 1100px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); overflow: hidden; padding: 30px; }
        .kegiatan-header { text-align: center; margin-bottom: 30px; }
        .kegiatan-header h1 { font-size: 32px; color: #2c3e50; }
        .filter-form { display: flex; gap: 15px; margin-bottom: 25px; justify-content: center; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-form label { font-weight: 600; color: #555; }
        .filter-form select { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 5px; font-size: 14px; cursor: pointer; }
        .filter-form button { background-color: #007bff; color: white; padding: 8px 20px; border: none; border-radius: 5px; font-size: 15px; cursor: pointer; transition: background-color 0.3s; }
        .filter-form button:hover { background-color: #0056b3; }
        .kegiatan-table-container { overflow-x: auto; }
        .kegiatan-table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        .kegiatan-table th, .kegiatan-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; vertical-align: middle; }
        .kegiatan-table th { background-color: #f2f2f2; font-weight: 600; color: #555; text-transform: uppercase; font-size: 14px; }
        .kegiatan-table tr:nth-child(even) { background-color: #f9f9f9; }
        .kegiatan-table tr:hover { background-color: #f1f1f1; }
        .no-data-message { text-align: center; padding: 20px; font-size: 18px; color: #888; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-weight: bold; color: white; text-align: center; font-size: 12px; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }

        /* PERUBAHAN 2: Menambahkan style untuk tombol baru */
        .view-form-button {
            background-color: #007bff; /* Blue color */
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .view-form-button:hover { background-color: #0056b3; }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body { padding-top: 120px; }
            .navbar { flex-direction: column; }
            .filter-form { flex-direction: column; width: 100%; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_ListKegiatan.php" class="active">Data Event</a></li>
        <li><a href="ditmawa_dataEvent.php" >Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="kegiatan-container">
    <div class="kegiatan-header">
        <h1>List Data Pengajuan Event</h1>
    </div>

    <form method="GET" class="filter-form">
        <label for="bulan">Bulan:</label>
        <select name="bulan" id="bulan">
            <option value="">Semua Bulan</option>
            <?php
            $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
            foreach ($months as $num => $name) {
                echo '<option value="' . $num . '" ' . ((int)$selected_bulan === $num ? 'selected' : '') . '>' . $name . '</option>';
            }
            ?>
        </select>
        <label for="tahun">Tahun:</label>
        <select name="tahun" id="tahun">
            <option value="">Semua Tahun</option>
            <?php foreach ($years as $year) { echo '<option value="' . $year . '" ' . ((int)$selected_tahun === $year ? 'selected' : '') . '>' . $year . '</option>'; } ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <div class="kegiatan-table-container">
        <table class="kegiatan-table">
            <thead>
                <tr>
                    <th>TANGGAL EVENT</th>
                    <th>NAMA PENGAJU</th>
                    <th>NPM</th>
                    <th>NAMA ACARA</th>
                    <th>STATUS</th>
                    <th>FORM PENGAJUAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($kegiatan_data)): ?>
                    <?php foreach ($kegiatan_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('d F Y', strtotime($row['pengajuan_event_tanggal_mulai']))); ?></td>
                            <td><?php echo htmlspecialchars($row['mahasiswa_nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['mahasiswa_npm']); ?></td>
                            <td><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower(htmlspecialchars($row['pengajuan_status'])); ?>">
                                    <?php echo htmlspecialchars($row['pengajuan_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="ditmawa_editForm.php?id=<?php echo $row['pengajuan_id']; ?>" class="view-form-button">
                                    <i class="fas fa-file-alt"></i> Lihat Form
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data-message">Tidak ada data kegiatan event yang tersedia untuk filter ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>