<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php"); // Redirect to login page if not authorized
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$user_id = $_SESSION['user_id'] ?? 'No ID';

require_once('../config/db_connection.php');

// Initialize statistics variables
$total_events_stat = 0;
$total_mahasiswa = 0;
$pending_approvals = 0;

// Current month and year for calendar
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; } 
elseif ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }

$date = new DateTime("$currentYear-$currentMonth-01");
$daysInMonth = $date->format('t');
$firstDayOfWeek = $date->format('N'); // 1 for Monday, 7 for Sunday

$prevMonth = $currentMonth - 1; $prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $currentMonth + 1; $nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$calendar_events = [];

try {
    if (isset($conn) && $conn->ping()) {
        // Count total events for statistics
        $result_stat_events = $conn->query("SELECT COUNT(*) as total FROM pengajuan_event");
        if ($result_stat_events) {
            $total_events_stat = $result_stat_events->fetch_assoc()['total'];
        }

        // Count total mahasiswa
        $result_mahasiswa = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
        if ($result_mahasiswa) {
            $total_mahasiswa = $result_mahasiswa->fetch_assoc()['total'];
        }

        // Count pending event approvals
        $result_pending = $conn->query("SELECT COUNT(*) as total FROM pengajuan_event WHERE pengajuan_status = 'Diajukan'");
        if ($result_pending) {
            $pending_approvals = $result_pending->fetch_assoc()['total'];
        }

        // Fetch event data for the calendar for the current month
        $stmt_calendar_events = $conn->prepare("
            SELECT 
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_event_tanggal_selesai
            FROM pengajuan_event pe
            WHERE pe.pengajuan_status = 'Disetujui'
              AND (
                  (MONTH(pe.pengajuan_event_tanggal_mulai) = ? AND YEAR(pe.pengajuan_event_tanggal_mulai) = ?) OR
                  (MONTH(pe.pengajuan_event_tanggal_selesai) = ? AND YEAR(pe.pengajuan_event_tanggal_selesai) = ?)
              )
        ");

        if ($stmt_calendar_events) {
            $stmt_calendar_events->bind_param("iiii", $currentMonth, $currentYear, $currentMonth, $currentYear);
            $stmt_calendar_events->execute();
            $result_calendar_events = $stmt_calendar_events->get_result();

            while ($row = $result_calendar_events->fetch_assoc()) {
                $eventStartDate = new DateTime($row['pengajuan_event_tanggal_mulai']);
                $eventEndDate = new DateTime($row['pengajuan_event_tanggal_selesai']);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($eventStartDate, $interval, $eventEndDate->modify('+1 day')); 

                foreach ($period as $dt) {
                    $day = (int)$dt->format('j');
                    if ($dt->format('n') == $currentMonth && $dt->format('Y') == $currentYear) {
                        if (!isset($calendar_events[$day])) {
                            $calendar_events[$day] = [];
                        }
                        $calendar_events[$day][] = htmlspecialchars($row['pengajuan_namaEvent']);
                    }
                }
            }
            $stmt_calendar_events->close();
        }

    }
} catch (Exception $e) {
    error_log("Error in Ditmawa Dashboard: " . $e->getMessage());
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Ditmawa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1e3c72;
            background-image: url('../img/backgroundDitmawa.jpeg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
        }
        .navbar { display: flex; justify-content: space-between; align-items: center; background: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #2c3e50; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: #2c3e50; font-weight: 500; }
        .navbar-menu li a.active {
            color: #007bff;
        }     
        .navbar-right { display: flex; align-items: center; gap: 15px; color: #2c3e50; }
        .icon { font-size: 20px; cursor: pointer; }
        .main-content { flex-grow: 1; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .content-card { background: rgba(255, 255, 255, 0.9); border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 30px; }
        .welcome-section { background: linear-gradient(135deg,rgb(2, 73, 43) 0%, rgb(2, 71, 25) 100%); color: white; border-radius: 10px; padding: 25px; margin-bottom: 30px; text-align: center; }
        .welcome-section h2 { font-size: 28px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; border-left: 5px solid; border-radius: 10px; padding: 25px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-card.events { border-color: #3498db; }
        .stat-card.students { border-color: #27ae60; }
        .stat-card.pending { border-color: #f39c12; }
        .stat-card .icon { font-size: 40px; margin-bottom: 15px; }
        .stat-card.events .icon { color: #3498db; }
        .stat-card.students .icon { color: #27ae60; }
        .stat-card.pending .icon { color: #f39c12; }
        .stat-card .number { font-size: 32px; font-weight: bold; }
        .stat-card .label { color: #7f8c8d; font-size: 14px; text-transform: uppercase; }
        .calendar-wrapper { margin-top: 30px; }
        .calendar-title { text-align: center; font-size: 1.8em; margin-bottom: 20px; font-weight: 600; color: #2c3e50;}
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { font-size: 1.5em; }
        .calendar-header a { text-decoration: none; color: #ff8c00; font-size: 1.5em; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .day-name { text-align: center; font-weight: 600; padding: 10px 0; font-size: 0.9em; }
        .day-cell { border: 1px solid #f0f0f0; min-height: 100px; padding: 5px; font-size: 0.8em; background: #fff; border-radius: 4px; }
        .day-number { font-weight: bold; }
        .event-indicator { font-size: 0.9em; background-color: #ffe8cc; color: #d97706; padding: 2px 4px; border-radius: 4px; margin-top: 3px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .empty-day { background-color: #f9f9f9; }
        .detail-link-container { text-align: center; margin-top: 15px; }
        .detail-link { color: #dc3545; text-decoration: none; font-weight: bold; }

        /* ===== FOOTER STYLES ===== */
        .page-footer {
            background-color: #ff8c00; /* Warna disamakan dengan navbar Ditmawa */
            color: #fff; /* Warna teks putih agar kontras */
            padding: 40px 0;
            margin-top: 40px;
        }
        .footer-container {
            max-width: 1200px;
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
            color: #2c3e50; /* Menyamakan warna teks title di navbar */
        }
        .footer-right ul {
            list-style: none;
            padding: 0;
            margin: 0;
            color: #2c3e50;
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
            color: #2c3e50;
            font-size: 1.5em;
            transition: color 0.3s;
        }
        .footer-right .social-icons a:hover {
            color: #fff;
        }

    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php" class="active">Home</a></li>
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <div class="content-card">
            <div class="welcome-section">
                <h2>Selamat Datang di Dashboard Ditmawa</h2>
                <p>Pengelolaan event dan kegiatan kemahasiswaan Universitas Katolik Parahyangan</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card events">
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="number"><?php echo $total_events_stat; ?></div>
                    <div class="label">Total Event</div>
                </div>
                <div class="stat-card students">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo $total_mahasiswa; ?></div>
                    <div class="label">Total Mahasiswa</div>
                </div>
                <div class="stat-card pending">
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    <div class="number"><?php echo $pending_approvals; ?></div>
                    <div class="label">Menunggu Persetujuan</div>
                </div>
            </div>

            <div class="calendar-wrapper">
                <h2 class="calendar-title">KALENDER INSTITUSIONAL UNPAR</h2>
                <div class="calendar-header">
                    <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" aria-label="Bulan Sebelumnya">&larr;</a>
                    <h2><?php echo $date->format('F Y'); ?></h2>
                    <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" aria-label="Bulan Berikutnya">&rarr;</a>
                </div>
                <div class="calendar-grid">
                    <div class="day-name">Senin</div><div class="day-name">Selasa</div><div class="day-name">Rabu</div><div class="day-name">Kamis</div><div class="day-name">Jumat</div><div class="day-name">Sabtu</div><div class="day-name">Minggu</div>
                    <?php for ($i = 1; $i < $firstDayOfWeek; $i++) echo '<div class="day-cell empty-day"></div>'; ?>
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <div class="day-cell">
                            <div class="day-number"><?php echo $day; ?></div>
                            <?php if (isset($calendar_events[$day])): ?>
                                <?php foreach ($calendar_events[$day] as $eventName): ?>
                                    <span class="event-indicator"><?php echo $eventName; ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    <?php 
                        $totalCells = $firstDayOfWeek - 1 + $daysInMonth;
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                        for ($i = 0; $i < $remainingCells; $i++) echo '<div class="day-cell empty-day"></div>';
                    ?>
                </div>
                <div class="detail-link-container">
                    <a href="ditmawa_dataEvent.php" class="detail-link">Klik Untuk Kalender Lebih Detail</a>
                </div>
            </div>
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