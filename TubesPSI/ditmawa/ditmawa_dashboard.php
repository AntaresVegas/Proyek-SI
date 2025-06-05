<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php"); // Redirect to login page if not authorized
    exit();
}

// Set default values for session variables if they are not set
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID'; // Added user_id to session from the login process

// Include database connection
require_once('../config/db_connection.php');

// Initialize statistics variables
$total_events_stat = 0; // Renamed to avoid conflict
$total_mahasiswa = 0;
$pending_approvals = 0;

// Current month and year for calendar
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Adjust month and year if out of range
if ($currentMonth < 1) {
    $currentMonth = 12;
    $currentYear--;
} elseif ($currentMonth > 12) {
    $currentMonth = 1;
    $currentYear++;
}

$date = new DateTime("$currentYear-$currentMonth-01");
$daysInMonth = $date->format('t');
$firstDayOfWeek = $date->format('N'); // 1 for Monday, 7 for Sunday

$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Fetch event data for the current month for the calendar
$calendar_events = [];

try {
    if (isset($conn) && $conn->ping()) {
        // Count total events for statistics
        $result_stat_events = $conn->query("SELECT COUNT(*) as total FROM pengajuan_event"); // Corrected table name
        if ($result_stat_events) {
            $row_stat_events = $result_stat_events->fetch_assoc();
            $total_events_stat = $row_stat_events['total'];
        }

        // Count total mahasiswa
        $result_mahasiswa = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
        if ($result_mahasiswa) {
            $row_mahasiswa = $result_mahasiswa->fetch_assoc();
            $total_mahasiswa = $row_mahasiswa['total'];
        }

        // Count pending event approvals
        $result_pending = $conn->query("SELECT COUNT(*) as total FROM pengajuan_event WHERE pengajuan_status = 'Menunggu Persetujuan'"); // Corrected table name and status
        if ($result_pending) {
            $row_pending = $result_pending->fetch_assoc();
            $pending_approvals = $row_pending['total'];
        }

        // Fetch event data for the calendar for the current month
        $stmt_calendar_events = $conn->prepare("
            SELECT 
                pe.pengajuan_id,
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_event_tanggal_selesai
            FROM 
                pengajuan_event pe
            WHERE 
                pe.pengajuan_status = 'Disetujui'
                AND (
                    (MONTH(pe.pengajuan_event_tanggal_mulai) = ? AND YEAR(pe.pengajuan_event_tanggal_mulai) = ?)
                    OR (MONTH(pe.pengajuan_event_tanggal_selesai) = ? AND YEAR(pe.pengajuan_event_tanggal_selesai) = ?)
                    OR (pe.pengajuan_event_tanggal_mulai <= ? AND pe.pengajuan_event_tanggal_selesai >= ?)
                )
            ORDER BY 
                pe.pengajuan_event_tanggal_mulai ASC
        ");

        $startOfMonthForQuery = "$currentYear-$currentMonth-01";
        $endOfMonthForQuery = "$currentYear-$currentMonth-$daysInMonth";

        if ($stmt_calendar_events) {
            $stmt_calendar_events->bind_param("iissss", $currentMonth, $currentYear, $currentMonth, $currentYear, $endOfMonthForQuery, $startOfMonthForQuery);
            $stmt_calendar_events->execute();
            $result_calendar_events = $stmt_calendar_events->get_result();

            while ($row = $result_calendar_events->fetch_assoc()) {
                $eventStartDate = new DateTime($row['pengajuan_event_tanggal_mulai']);
                $eventEndDate = new DateTime($row['pengajuan_event_tanggal_selesai']);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($eventStartDate, $interval, $eventEndDate->modify('+1 day')); 

                foreach ($period as $dt) {
                    $day = (int)$dt->format('j');
                    $month = (int)$dt->format('n');
                    $year = (int)$dt->format('Y');

                    if ($month === $currentMonth && $year === $currentYear) {
                        if (!isset($calendar_events[$day])) {
                            $calendar_events[$day] = [];
                        }
                        $calendar_events[$day][] = htmlspecialchars($row['pengajuan_namaEvent']);
                    }
                }
            }
            $stmt_calendar_events->close();
        } else {
            error_log("Failed to prepare statement for fetching calendar events: " . $conn->error);
        }

    } else {
        error_log("Database connection not established or lost in ditmawa_dashboard.php");
    }
} catch (Exception $e) {
    error_log("Error in Ditmawa Dashboard: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Ditmawa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* General styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar styles from mahasiswa_dashboard.php */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ff8c00;
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
            color: #2c3e50;
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
            color: #2c3e50;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
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
            color: #2c3e50;
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

        /* Container & Main Content - adjusted for top padding */
        .container {
            max-width: 1400px;
            margin: 20px auto; /* Adjusted margin-top to account for fixed navbar */
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .main-content {
            padding: 30px;
        }

        .welcome-section {
            background: linear-gradient(135deg,rgb(2, 73, 43)100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon { /* Adjusted icon class within stat-card */
            font-size: 40px;
            margin-bottom: 15px;
            color: #3498db; /* Example color, can be specific per card */
        }
         .stat-card.events .icon { color: #3498db; }
         .stat-card.students .icon { color: #27ae60; }
         .stat-card.pending .icon { color: #f39c12; }


        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card.events {
            border-left: 4px solid #3498db;
        }

        .stat-card.students {
            border-left: 4px solid #27ae60;
        }

        .stat-card.pending {
            border-left: 4px solid #f39c12;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card p {
            color: #495057;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-card .value {
            font-weight: bold;
            color: #2c3e50;
        }

        .features-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .features-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features-section h3:before {
            content: "⚙️";
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .feature-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: background 0.3s;
        }

        .feature-item:hover {
            background: #e9ecef;
        }

        .feature-item h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .feature-item p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending { /* Style for pending status in session info if needed */
            background: #fff3cd;
            color: #856404;
        }
        
        /* Calendar styles adapted from ditmawa_dataEvent.php and merged */
        .calendar-wrapper { /* Existing wrapper, ensure it fits the new calendar */
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .calendar-header { /* From ditmawa_dataEvent.php */
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .calendar-header h2 { /* Combined styling */
            color: #2c3e50;
            font-size: 22px; /* Adjusted size */
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: center; /* Center if it's the main title */
            flex-grow: 1; /* Allow h2 to take space */
            justify-content: center; /* Center text within h2 */
        }

        .calendar-header .nav-arrow { /* From ditmawa_dataEvent.php */
            font-size: 20px; /* Adjusted size */
            color: #ff8c00;
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none;
        }

        .calendar-header .nav-arrow:hover {
            color: #e67e00;
        }

        .calendar-grid { /* From ditmawa_dataEvent.php */
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px; /* Adjusted gap */
        }

        .day-name { /* Combined styling */
            text-align: center;
            font-weight: 600;
            color: #555;
            padding: 10px 0;
            background-color: #f0f0f0; /* Keep background for day names */
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
        }
        
        .day-cell { /* From ditmawa_dataEvent.php, adapted */
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 8px; /* Adjusted padding */
            min-height: 100px; /* Adjusted height */
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
        }

        .day-cell:hover {
            background-color: #f0f0f0;
        }

        .day-number { /* From ditmawa_dataEvent.php */
            font-size: 16px; /* Adjusted size */
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .event-indicator { /* From ditmawa_dataEvent.php */
            font-size: 11px; /* Smaller font for dashboard */
            color: #007bff; /* Changed color for better contrast if needed */
            font-weight: 500;
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            padding: 2px 4px;
            background-color: #e0efff; /* Light background for indicator */
            border-radius: 4px;
            margin-bottom: 2px; /* Space between multiple events */
        }

        .empty-day { /* From ditmawa_dataEvent.php */
            background-color: #ffffff;
            border: 1px dashed #eee;
        }
        .empty-day:hover {
            background-color: #ffffff;
        }


        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 100px; /* Adjust padding for smaller navbar on mobile */
            }
            .navbar {
                flex-direction: column;
                padding: 10px 15px;
                gap: 10px;
            }
            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            .navbar-right {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .calendar-grid {
                gap: 3px;
            }
            .day-cell {
                min-height: 80px; /* Adjust height for mobile */
                padding: 5px;
            }
            .day-number {
                font-size: 14px;
            }
            .event-indicator {
                font-size: 9px;
            }
            .calendar-header h2 {
                font-size: 18px;
            }
            .calendar-header .nav-arrow {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php" class="active">Home</a></li>
        <li><a href="ditmawa_dataEvent.php">Data Event</a></li>
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

<div class="container">
    <div class="main-content">
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
            <div class="calendar-header">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-arrow" aria-label="Previous Month">&larr;</a>
                <h2><?php echo $date->format('F Y'); ?></h2>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-arrow" aria-label="Next Month">&rarr;</a>
            </div>
            <div class="calendar-grid">
                <div class="day-name">Senin</div>
                <div class="day-name">Selasa</div>
                <div class="day-name">Rabu</div>
                <div class="day-name">Kamis</div>
                <div class="day-name">Jumat</div>
                <div class="day-name">Sabtu</div>
                <div class="day-name">Minggu</div>

                <?php
                // Fill in leading empty days (Monday as first day)
                for ($i = 1; $i < $firstDayOfWeek; $i++) {
                    echo '<div class="day-cell empty-day"></div>';
                }

                // Fill in days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    echo '<div class="day-cell">';
                    echo '<div class="day-number">' . $day . '</div>';
                    if (isset($calendar_events[$day]) && !empty($calendar_events[$day])) {
                        foreach ($calendar_events[$day] as $eventName) {
                            echo '<span class="event-indicator">' . $eventName . '</span>';
                        }
                    }
                    echo '</div>';
                }

                // Fill in trailing empty days for the grid
                $totalCells = $firstDayOfWeek -1 + $daysInMonth;
                $remainingCells = (7 - ($totalCells % 7)) % 7;
                for ($i = 0; $i < $remainingCells; $i++) {
                     echo '<div class="day-cell empty-day"></div>';
                }
                ?>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fas fa-user-check"></i> Informasi Login</h3>
                <p><span>Nama:</span> <span class="value"><?php echo htmlspecialchars($nama); ?></span></p>
                <p><span>Email:</span> <span class="value"><?php echo htmlspecialchars($email); ?></span></p>
                <p><span>User ID:</span> <span class="value"><?php echo htmlspecialchars($user_id); ?></span></p>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-clock"></i> Informasi Session</h3>
                <p><span>Session ID:</span> <span class="value"><?php echo substr(session_id(), 0, 10) . '...'; ?></span></p>
                <p><span>Login Time:</span> <span class="value"><?php echo date('d/m/Y H:i:s'); // This will be current time, not actual login time unless stored ?></span></p>
                <p><span>Server Time:</span> <span class="value"><?php echo date('d/m/Y H:i:s'); ?></span></p>
                <p><span>Status:</span> <span class="status-badge status-approved">Active</span></p>
            </div>
        </div>

        <div class="features-section">
            <h3><i class="fas fa-cogs"></i> Fitur Dashboard Ditmawa</h3>
            <div class="feature-grid">
                <div class="feature-item">
                    <h4><i class="fas fa-tasks"></i> Persetujuan Event</h4>
                    <p>Meninjau dan menyetujui proposal event yang diajukan oleh mahasiswa dan organisasi kemahasiswaan</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-chart-line"></i> Monitoring Event</h4>
                    <p>Memantau pelaksanaan event yang sedang berlangsung dan evaluasi hasil kegiatan</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-users-cog"></i> Manajemen Partisipan</h4>
                    <p>Melihat daftar partisipan event dan mengelola pendaftaran peserta kegiatan</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-file-alt"></i> Laporan Kegiatan</h4>
                    <p>Menghasilkan laporan statistik dan analisis kegiatan kemahasiswaan per periode</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-building"></i> Manajemen Ruangan</h4>
                    <p>Mengelola pemesanan dan penjadwalan penggunaan ruangan untuk kegiatan event</p>
                </div>
                <div class="feature-item">
                    <h4><i class="fas fa-bullhorn"></i> Notifikasi & Pengumuman</h4>
                    <p>Mengirim notifikasi dan pengumuman terkait event kepada mahasiswa dan organisasi</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>