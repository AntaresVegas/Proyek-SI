<?php
session_start();

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../auth/login.php"); // Redirect ke halaman login jika belum login/tipe salah
    exit();
}

// Ambil data user dari session
$nama = $_SESSION['nama'] ?? 'Mahasiswa'; // Nama pengguna
$email = $_SESSION['username'] ?? 'email@example.com'; // Asumsi username adalah email
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Path relatif ke file koneksi database
require_once(__DIR__ . '/../config/db_connection.php');

// --- Data Fetching Logic Starts Here ---

// Calendar Logic
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

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

$calendar_events = [];
$upcoming_events = [];
$recent_submissions = [];

try {
    if (isset($conn) && $conn->ping()) {
        // Fetch approved event data for the calendar for the current month
        $stmt_calendar_events = $conn->prepare("
            SELECT
                pe.pengajuan_id,
                pe.pengajuan_event_nama,
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
                        $calendar_events[$day][] = htmlspecialchars($row['pengajuan_event_nama']);
                    }
                }
            }
            $stmt_calendar_events->close();
        } else {
            error_log("Failed to prepare statement for fetching calendar events (mahasiswa homepage): " . $conn->error);
        }

        // Fetch Upcoming Events
        $stmt_events = $conn->prepare("
            SELECT pengajuan_id, pengajuan_event_nama, pengajuan_event_tanggal_mulai,
                   pengajuan_event_tanggal_selesai, pengajuan_event_waktu_mulai,
                   pengajuan_event_waktu_selesai, pengajuan_event_lokasi
            FROM pengajuan_event
            WHERE pengajuan_status = 'Disetujui'
              AND pengajuan_event_tanggal_selesai >= CURDATE()
            ORDER BY pengajuan_event_tanggal_mulai ASC, pengajuan_event_waktu_mulai ASC
            LIMIT 5
        ");
        if ($stmt_events) {
            $stmt_events->execute();
            $result_events = $stmt_events->get_result();
            while ($row = $result_events->fetch_assoc()) {
                $upcoming_events[] = $row;
            }
            $stmt_events->close();
        } else {
            error_log("Failed to prepare statement for upcoming events: " . $conn->error);
        }

        // Fetch Recent Submissions
        $stmt_submissions = $conn->prepare("
            SELECT pengajuan_id, pengajuan_event_nama, pengajuan_status, pengajuan_created_at
            FROM pengajuan_event
            WHERE pengajuan_user_id = ?
            ORDER BY pengajuan_created_at DESC
            LIMIT 5
        ");
        if ($stmt_submissions) {
            $stmt_submissions->bind_param("i", $user_id);
            $stmt_submissions->execute();
            $result_submissions = $stmt_submissions->get_result();
            while ($row = $result_submissions->fetch_assoc()) {
                $recent_submissions[] = $row;
            }
            $stmt_submissions->close();
        } else {
            error_log("Failed to prepare statement for recent submissions: " . $conn->error);
        }

    } else {
        error_log("Database connection not established or lost in mahasiswa/homepage.php");
    }
} catch (Exception $e) {
    error_log("Error in Mahasiswa Homepage/Dashboard: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
// --- Data Fetching Logic Ends Here ---
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Anda bisa memindahkan sebagian besar CSS ini ke style.css jika mau */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgb(2, 71, 25); /* Your existing green color */
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
            color: rgb(255, 255, 255);
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
            color: rgb(253, 253, 253);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .navbar-menu li a:hover,
        .navbar-menu li a.active {
            color: #007bff; /* Blue hover for green navbar */
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color: rgb(255, 255, 255);
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

        /* Container & Main Content */
        .container {
            max-width: 1400px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px; /* Ensure padding for overall content */
        }

        .welcome-section {
            background: linear-gradient(135deg, rgb(2, 71, 25) 0%, #307a4a 100%); /* Green gradient for welcome */
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h1 { /* Changed from h2 to h1 for dashboard consistency */
            margin-bottom: 10px;
            font-size: 28px; /* Larger font size from dashboard */
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 16px;
        }

        /* Calendar styles */
        .calendar-wrapper {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 10px;
        }

        .calendar-header h2 {
            color: #2c3e50;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: center;
            flex-grow: 1;
            justify-content: center;
        }

        .calendar-header .nav-arrow {
            font-size: 20px;
            color: rgb(2, 71, 25); /* Green accent for nav arrows */
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none;
        }

        .calendar-header .nav-arrow:hover {
            color: #007bff;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }

        .day-name {
            text-align: center;
            font-weight: 600;
            color: #555;
            padding: 10px 0;
            background-color: #f0f0f0;
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
        }

        .day-cell {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 8px;
            min-height: 100px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
        }

        .day-cell:hover {
            background-color: #f0f0f0;
        }

        .day-number {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .event-indicator {
            font-size: 11px;
            color: #007bff;
            font-weight: 500;
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            padding: 2px 4px;
            background-color: #e0efff;
            border-radius: 4px;
            margin-bottom: 2px;
        }

        .empty-day {
            background-color: #ffffff;
            border: 1px dashed #eee;
        }
        .empty-day:hover {
            background-color: #ffffff;
        }

        /* Main Content Grid (from dashboard.php) */
        .main-content-grid {
            display: grid;
            grid-template-columns: 1fr; /* Default to one column */
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (min-width: 768px) {
            .main-content-grid {
                grid-template-columns: 1fr 1fr; /* Two columns on larger screens */
            }
        }

        .section-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 25px;
        }

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .section-title h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.5em;
        }

        .view-all {
            color: #007bff;
            text-decoration: none;
            font-size: 0.95em;
            display: flex;
            align-items: center;
        }

        .view-all i {
            margin-left: 5px;
            transition: transform 0.2s;
        }

        .view-all:hover i {
            transform: translateX(3px);
        }

        .event-list, .submission-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .event-item, .submission-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .event-item:last-child, .submission-item:last-child {
            border-bottom: none;
        }

        .event-item h4, .submission-item h4 {
            margin: 0 0 8px 0;
            color: #34495e;
            font-size: 1.2em;
        }

        .event-item p, .submission-item p {
            margin: 5px 0;
            color: #666;
            font-size: 0.95em;
            display: flex;
            align-items: center;
        }

        .event-item p i, .submission-item p i {
            margin-right: 8px;
            color: #555;
            width: 20px; /* Fixed icon width */
            text-align: center;
        }

        .status-approved {
            color: #28a745; /* Green */
            font-weight: bold;
        }
        .status-rejected {
            color: #dc3545; /* Red */
            font-weight: bold;
        }
        .status-pending {
            color: #ffc107; /* Yellow/Orange */
            font-weight: bold;
        }
        .status-revision {
            color: #17a2b8; /* Cyan */
            font-weight: bold;
        }

        .no-data-message {
            text-align: center;
            color: #888;
            padding: 30px;
            font-style: italic;
        }

        /* Quick Links Section (from homepage.php, slightly adjusted for consistency) */
        .quick-links-section {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .quick-links-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            text-align: center;
        }

        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .quick-link-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background-color: #f0f8ff; /* Lightest blue */
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .quick-link-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .quick-link-item i {
            font-size: 2.5em;
            color: #007bff;
            margin-bottom: 10px;
        }

        .quick-link-item span {
            font-size: 0.95em;
            font-weight: 500;
        }


        /* Notifikasi - CSS (Keep existing styles) */
        .notification-container {
            position: relative;
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0 10px;
        }

        .notification-badge {
            position: absolute;
            top: 5px;
            right: 0px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            z-index: 1001;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }

        .notifications-dropdown {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            transform-origin: top right;
            animation: fadeInScale 0.2s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(-10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .notifications-dropdown.show {
            display: block;
        }

        .notifications-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f7f7f7;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .notifications-header h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }

        .mark-as-read {
            font-size: 12px;
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }
        .mark-as-read:hover {
            color: #0056b3;
        }

        #notificationList {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #notificationList li {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        #notificationList li.unread {
             background-color: #e6f7ff; /* Latar belakang untuk notif belum dibaca */
             font-weight: bold;
        }

        #notificationList li:last-child {
            border-bottom: none;
        }

        #notificationList li:hover {
            background-color: #f9f9f9;
        }

        #notificationList li a {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .notification-item p {
            margin: 0;
            font-size: 14px;
            color: #555;
            line-height: 1.4;
        }

        .notification-item small {
            font-size: 11px;
            color: #888;
            display: block;
            margin-top: 5px;
        }

        .notification-item .status-approved {
            color: #28a745;
            font-weight: bold;
        }
        .notification-item .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        .no-notifications {
            padding: 20px;
            text-align: center;
            color: #777;
            font-style: italic;
        }

        .notifications-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            text-align: center;
            background-color: #f7f7f7;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .notifications-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 13px;
        }
        .notifications-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 100px;
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
            .calendar-grid {
                gap: 3px;
            }
            .day-cell {
                min-height: 80px;
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
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php" class="active">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>

    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>

        <div class="notification-container">
            <i class="fas fa-bell icon" id="notificationBell"></i>
            <span class="notification-badge" id="notificationBadge" style="display:none;"></span>
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h4>Notifikasi Anda</h4>
                    <span class="mark-as-read" id="markAsRead">Tandai sudah dibaca</span>
                </div>
                <ul id="notificationList">
                    <li class="no-notifications">Memuat notifikasi...</li>
                </ul>
                <div class="notifications-footer">
                    <a href="mahasiswa_notifications.php">Lihat Semua Notifikasi</a>
                </div>
            </div>
        </div>
        <a href="../logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Selamat Datang di Portal Event Mahasiswa UNPAR!</h1>
        <p>Temukan berbagai event menarik, ajukan kegiatan Anda, dan kelola partisipasi Anda dengan mudah.</p>
    </div>

    <div class="main-content-grid">
        <div class="section-card">
            <div class="section-title">
                <h3>🗓️ Event Mendatang</h3>
                <a href="mahasiswa_event.php" class="view-all">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>
            <ul class="event-list">
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event): ?>
                        <li class="event-item">
                            <h4><?php echo htmlspecialchars($event['pengajuan_event_nama']); ?></h4>
                            <p><i class="far fa-calendar-alt"></i>
                                <?php echo date('d M Y', strtotime($event['pengajuan_event_tanggal_mulai'])); ?>
                                <?php if ($event['pengajuan_event_tanggal_mulai'] != $event['pengajuan_event_tanggal_selesai']): ?>
                                    - <?php echo date('d M Y', strtotime($event['pengajuan_event_tanggal_selesai'])); ?>
                                <?php endif; ?>
                            </p>
                            <p><i class="far fa-clock"></i> <?php echo htmlspecialchars($event['pengajuan_event_waktu_mulai']); ?> - <?php echo htmlspecialchars($event['pengajuan_event_waktu_selesai']); ?> WIB</p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['pengajuan_event_lokasi']); ?></p>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data-message">Tidak ada event mendatang yang terdaftar saat ini.</p>
                <?php endif; ?>
            </ul>
        </div>

        <div class="section-card">
            <div class="section-title">
                <h3>📄 Aktivitas Pengajuan Terbaru</h3>
                <a href="mahasiswa_history_pengajuan.php" class="view-all">Lihat Riwayat <i class="fas fa-arrow-right"></i></a>
            </div>
            <ul class="submission-list">
                <?php if (!empty($recent_submissions)): ?>
                    <?php foreach ($recent_submissions as $submission): ?>
                        <li class="submission-item">
                            <h4><?php echo htmlspecialchars($submission['pengajuan_event_nama']); ?></h4>
                            <p>Status:
                                <?php
                                    $status_text = htmlspecialchars($submission['pengajuan_status']);
                                    $status_class = '';
                                    switch ($submission['pengajuan_status']) {
                                        case 'Disetujui':
                                            $status_class = 'status-approved';
                                            break;
                                        case 'Ditolak':
                                            $status_class = 'status-rejected';
                                            break;
                                        case 'Menunggu':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'Perlu Revisi':
                                            $status_class = 'status-revision';
                                            break;
                                        default:
                                            $status_class = '';
                                    }
                                ?>
                                <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </p>
                            <p><i class="far fa-clock"></i> Diajukan pada: <?php echo date('d M Y H:i', strtotime($submission['pengajuan_created_at'])); ?></p>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data-message">Anda belum melakukan pengajuan event apapun.</p>
                <?php endif; ?>
            </ul>
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
            $totalCells = $firstDayOfWeek - 1 + $daysInMonth;
            $remainingCells = (7 - ($totalCells % 7)) % 7;
            for ($i = 0; $i < $remainingCells; $i++) {
                echo '<div class="day-cell empty-day"></div>';
            }
            ?>
        </div>
    </div>
    <div class="quick-links-section">
        <div class="section-title" style="border-bottom: none;">
            <h3>🔗 Tautan Cepat & Sumber Daya</h3>
        </div>
        <div class="quick-links-grid">
            <a href="mahasiswa_pengajuan.php" class="quick-link-item">
                <i class="fas fa-plus-circle"></i>
                <span>Ajukan Event Baru</span>
            </a>
            <a href="mahasiswa_rules.php" class="quick-link-item">
                <i class="fas fa-book"></i>
                <span>Peraturan Event</span>
            </a>
            <a href="mahasiswa_faq.php" class="quick-link-item"> <i class="fas fa-question-circle"></i>
                <span>FAQ</span>
            </a>
            <a href="mahasiswa_contact.php" class="quick-link-item"> <i class="fas fa-headset"></i>
                <span>Kontak Ditmawa</span>
            </a>
            <a href="mahasiswa_event.php" class="quick-link-item"> <i class="fas fa-calendar-alt"></i>
                <span>Lihat Semua Event</span>
            </a>
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const notificationBell = document.getElementById('notificationBell');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        const markAsReadButton = document.getElementById('markAsRead');

        function fetchNotifications() {
            // Menggunakan path relatif dari `mahasiswa/homepage.php` ke `includes/fetch_notifications.php`
            fetch('../includes/fetch_notifications.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    notificationList.innerHTML = ''; // Hapus notifikasi yang ada
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const li = document.createElement('li');
                            if (!notif.is_read) {
                                li.classList.add('unread');
                            }
                            li.innerHTML = `
                                <a href="${notif.link ? notif.link : '#'}">
                                    <div class="notification-item">
                                        <p>${notif.message}</p>
                                        <small>${notif.time_ago}</small>
                                    </div>
                                </a>
                            `;
                            notificationList.appendChild(li);
                        });
                        if (data.unread_count > 0) {
                            notificationBadge.textContent = data.unread_count;
                            notificationBadge.style.display = 'block';
                        } else {
                            notificationBadge.style.display = 'none';
                        }
                    } else {
                        notificationList.innerHTML = '<li class="no-notifications">Tidak ada notifikasi baru.</li>';
                        notificationBadge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                    notificationList.innerHTML = '<li class="no-notifications">Gagal memuat notifikasi.</li>';
                    notificationBadge.style.display = 'none';
                });
        }

        notificationBell.addEventListener('click', function(event) {
            event.stopPropagation(); // Mencegah event click menyebar ke document
            notificationsDropdown.classList.toggle('show');
            if (notificationsDropdown.classList.contains('show')) {
                fetchNotifications(); // Ambil notifikasi terbaru saat dropdown dibuka
            }
        });

        // Menutup dropdown ketika mengklik di luar area dropdown atau lonceng
        document.addEventListener('click', function(event) {
            if (!notificationsDropdown.contains(event.target) && !notificationBell.contains(event.target)) {
                notificationsDropdown.classList.remove('show');
            }
        });

        markAsReadButton.addEventListener('click', function() {
            // Menggunakan path relatif dari `mahasiswa/homepage.php` ke `includes/mark_notifications_as_read.php`
            fetch('../includes/mark_notifications_as_read.php', { method: 'POST' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        notificationList.querySelectorAll('.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        notificationBadge.style.display = 'none';
                        console.log('Notifications marked as read.');
                    }
                })
                .catch(error => console.error('Error marking notifications as read:', error));
        });

        // Panggil fetchNotifications saat halaman dimuat untuk menampilkan badge jika ada notifikasi baru
        fetchNotifications();
    });
</script>

</body>
</html>