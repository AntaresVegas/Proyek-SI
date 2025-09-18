<?php
session_start();

// 1. Validasi Sesi Pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil data pengguna dari session
$nama = $_SESSION['nama'] ?? 'Mahasiswa';
$user_id = $_SESSION['user_id'] ?? null;

// 2. Koneksi ke Database
require_once(__DIR__ . '/../config/db_connection.php');

// 3. Inisialisasi Variabel
$upcoming_events = [];
$recent_submissions = [];
$calendar_events = [];

// 4. Logika Kalender (Waktu & Navigasi)
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; }
if ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }
$date = new DateTimeImmutable("$currentYear-$currentMonth-01");
$daysInMonth = $date->format('t');
$firstDayOfWeek = $date->format('N'); 
$prevMonth = $currentMonth - 1; $prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $currentMonth + 1; $nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// 5. Pengambilan Data dari Database
try {
    if (isset($conn) && $conn->ping()) {
        
        // ======================= PERUBAHAN LOGIKA KALENDER DIMULAI DI SINI =======================
        $stmt_calendar = $conn->prepare("
            SELECT pengajuan_namaEvent, pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai, tanggal_persiapan, tanggal_beres, pengaju_tipe 
            FROM pengajuan_event 
            WHERE pengajuan_status = 'Disetujui' 
            AND (
                (MONTH(pengajuan_event_tanggal_mulai) = ? AND YEAR(pengajuan_event_tanggal_mulai) = ?) OR
                (MONTH(pengajuan_event_tanggal_selesai) = ? AND YEAR(pengajuan_event_tanggal_selesai) = ?) OR
                (MONTH(tanggal_persiapan) = ? AND YEAR(tanggal_persiapan) = ?) OR
                (MONTH(tanggal_beres) = ? AND YEAR(tanggal_beres) = ?)
            )
        ");
        if ($stmt_calendar) {
            $stmt_calendar->bind_param("iiiiiiii", $currentMonth, $currentYear, $currentMonth, $currentYear, $currentMonth, $currentYear, $currentMonth, $currentYear);
            $stmt_calendar->execute();
            $result = $stmt_calendar->get_result();
            
            $temp_events_by_day = [];

            // Langkah 1: Kumpulkan semua event dan kelompokkan berdasarkan hari dan tipe
            while ($row = $result->fetch_assoc()) {
                $event_pengaju_tipe = $row['pengaju_tipe'];
                
                $period = new DatePeriod(new DateTime($row['pengajuan_event_tanggal_mulai']), new DateInterval('P1D'), (new DateTime($row['pengajuan_event_tanggal_selesai']))->modify('+1 day'));
                foreach ($period as $day) {
                    if ($day->format('n') == $currentMonth && $day->format('Y') == $currentYear) {
                         $day_num = $day->format('j');
                        $temp_events_by_day[$day_num][$event_pengaju_tipe][] = ['name' => htmlspecialchars($row['pengajuan_namaEvent']), 'type' => 'main'];
                    }
                }
                
                if (!empty($row['tanggal_persiapan'])) {
                    $prep_start_dt = new DateTime($row['tanggal_persiapan']);
                    $main_event_start_dt = new DateTime($row['pengajuan_event_tanggal_mulai']);
                     if ($prep_start_dt <= $main_event_start_dt) {
                        $prep_period_end = (clone $main_event_start_dt)->modify('+1 day');
                        if($prep_start_dt->format('Y-m-d') == $main_event_start_dt->format('Y-m-d')) {
                            $prep_period_end = (clone $prep_start_dt)->modify('+1 day');
                        }
                        $prep_period = new DatePeriod($prep_start_dt, new DateInterval('P1D'), $prep_period_end);
                        foreach ($prep_period as $dt) {
                            if ($dt->format('n') == $currentMonth && $dt->format('Y') == $currentYear) {
                                $day_num = (int)$dt->format('j');
                                $temp_events_by_day[$day_num][$event_pengaju_tipe][] = ['name' => htmlspecialchars($row['pengajuan_namaEvent']) . ' (Persiapan)', 'type' => 'prep'];
                            }
                        }
                    }
                }

                if (!empty($row['tanggal_beres'])) {
                    $main_event_end_dt = new DateTime($row['pengajuan_event_tanggal_selesai']);
                    $clear_end_dt = new DateTime($row['tanggal_beres']);
                    if ($clear_end_dt >= $main_event_end_dt) {
                        $clear_start_dt = (clone $main_event_end_dt)->modify('+1 day');
                         if($clear_end_dt->format('Y-m-d') == $main_event_end_dt->format('Y-m-d')) {
                            $clear_start_dt = $clear_end_dt;
                        }
                        $clear_period = new DatePeriod($clear_start_dt, new DateInterval('P1D'), (clone $clear_end_dt)->modify('+1 day'));
                        foreach ($clear_period as $dt) {
                            if ($dt->format('n') == $currentMonth && $dt->format('Y') == $currentYear) {
                                $day_num = (int)$dt->format('j');
                                $temp_events_by_day[$day_num][$event_pengaju_tipe][] = ['name' => htmlspecialchars($row['pengajuan_namaEvent']) . ' (Pembongkaran)', 'type' => 'clear'];
                            }
                        }
                    }
                }
            }
            $stmt_calendar->close();

            // Langkah 2: Bangun array $calendar_events final dengan aturan prioritas
            foreach ($temp_events_by_day as $day => $types) {
                if (!empty($types['ditmawa'])) {
                    $calendar_events[$day] = $types['ditmawa'];
                } else if (!empty($types['mahasiswa'])) {
                    $calendar_events[$day] = $types['mahasiswa'];
                }
            }
        }
        // ======================= PERUBAHAN LOGIKA KALENDER SELESAI DI SINI =======================

        // Data Event Mahasiswa Mendatang (3 Terdekat)
        $stmt_events = $conn->prepare(
            "SELECT pengajuan_namaEvent, pengajuan_event_tanggal_mulai, pengajuan_event_jam_mulai 
            FROM pengajuan_event 
            WHERE pengajuan_status = 'Disetujui' 
            AND pengaju_tipe = 'mahasiswa'
            AND pengajuan_event_tanggal_selesai >= CURDATE() 
            ORDER BY pengajuan_event_tanggal_mulai ASC, pengajuan_event_jam_mulai ASC 
            LIMIT 3"  
        );
        if ($stmt_events) {
            $stmt_events->execute();
            $upcoming_events = $stmt_events->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_events->close();
        }

        // Data Aktivitas Pengajuan Terbaru (Hanya 1 terbaru)
        $stmt_submissions = $conn->prepare(
            "SELECT pengajuan_namaEvent, pengajuan_status, pengajuan_tanggalEdit 
            FROM pengajuan_event 
            WHERE pengaju_id = ? AND pengaju_tipe = 'mahasiswa' 
            ORDER BY pengajuan_tanggalEdit DESC LIMIT 1"
        );
        if ($stmt_submissions && $user_id !== null) {
            $stmt_submissions->bind_param("i", $user_id);
            $stmt_submissions->execute();
            $recent_submissions = $stmt_submissions->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_submissions->close();
        }
    }
} catch (Exception $e) {
    die("Terjadi kesalahan saat mengambil data dari database: " . $e->getMessage());
} finally {
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: rgb(2, 71, 25); --secondary-color: #0d6efd; --light-gray: #f8f9fa;
            --text-dark: #212529; --text-light: #6c757d; --border-color: #dee2e6;
            --status-green: #198754; --status-red: #dc3545; --status-yellow: #ffc107;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: var(--light-gray); padding-top: 80px;  background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; display: flex; flex-direction: column; min-height: 100%; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: white; line-height: 1.2;font-size: 14px; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: white; }
        .icon { font-size: 20px; cursor: pointer; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; flex-grow: 1; }
        .welcome-section { background: linear-gradient(135deg, var(--primary-color) 0%, #307a4a 100%); color: white; border-radius: 12px; padding: 30px; margin-bottom: 30px; text-align: center; }
        .no-data-message { text-align: center; color: var(--text-light); padding: 40px 20px; font-style: italic; background: #fff; border-radius: 8px; border: 1px dashed var(--border-color); }
        .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 30px; }
        @media (min-width: 1200px) { .dashboard-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
        
        .dashboard-card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .dashboard-section-title { font-size: 1.6em; color: var(--text-dark); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .view-all-link { font-size: 0.6em; font-weight: 600; text-decoration: none; color: var(--secondary-color); }
        .event-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; align-items: stretch; transition: transform 0.2s; }
        .event-card:not(:last-child) { margin-bottom: 15px; }
        .event-card:hover { transform: translateY(-4px); }
        .event-card-date { background: var(--secondary-color); color: white; padding: 15px; text-align: center; flex: 0 0 80px; display: flex; flex-direction: column; justify-content: center; border-radius: 12px 0 0 12px; }
        .event-card-day { font-size: 2em; font-weight: 700; }
        .event-card-month { font-size: 1em; text-transform: uppercase; }
        .event-card-info { padding: 15px 20px; }
        .event-card-info h4 { font-size: 1.1em; color: var(--text-dark); margin-bottom: 8px; }
        .event-card-info p { font-size: 0.9em; color: var(--text-light); }
        .event-card:not(:last-child) { margin-bottom: 15px; }
        .submission-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; position: relative; border-left: 5px solid; }
        .submission-card:not(:last-child) { margin-bottom: 15px; }
        .submission-card-title { font-size: 1.1em; color: var(--text-dark); margin-bottom: 8px; padding-right: 90px; }
        .submission-card-date { font-size: 0.9em; color: var(--text-light); }
        .status-badge { position: absolute; top: 20px; right: 20px; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: 600; color: white; }
        .status-Disetujui { border-color: var(--status-green); } .status-Disetujui .status-badge { background-color: var(--status-green); }
        .status-Ditolak { border-color: var(--status-red); } .status-Ditolak .status-badge { background-color: var(--status-red); }
        .status-Diajukan { border-color: var(--status-yellow); } .status-Diajukan .status-badge { background-color: var(--status-yellow); color: #333; }
        .calendar-wrapper { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .calendar-title { text-align: center; color: var(--text-dark); font-size: 1.8em; margin-bottom: 20px; font-weight: 600; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { font-size: 1.5em; flex-grow: 1; text-align: center; }
        .calendar-header .nav-arrow { color: var(--primary-color); text-decoration: none; font-size: 1.5em; padding: 0 15px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background-color: var(--border-color); border: 1px solid var(--border-color); }
        .day-name { text-align: center; font-weight: 600; color: var(--text-light); padding: 10px 0; background-color: var(--light-gray); }
        .day-cell { background-color: #fff; padding: 8px; min-height: 100px; position: relative; }
        .day-number { font-weight: bold; }
        
        .day-cell.today .day-number {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .event-indicator { font-size: 0.8em; background: var(--status-green); color: white; padding: 3px 5px; border-radius: 4px; margin-top: 5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-indicator.prep-clear { background: var(--status-yellow); color: var(--text-dark); }

        .empty-day { background-color: var(--light-gray); }
        .detail-link-container { text-align: center; margin-top: 15px; }
        .detail-link { color: #dc3545; text-decoration: none; font-weight: bold; }
        .service-flow-container { background: #fff; border-radius: 12px; padding: 25px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .service-flow-container h2 { text-align: center; font-size: 1.8em; color: var(--text-dark); margin-bottom: 30px; }
        .timeline { position: relative; max-width: 800px; margin: 0 auto; list-style: none; }
        .timeline::before { content: ''; position: absolute; top: 0; left: 20px; height: 100%; width: 3px; background: #e9ecef; }
        .timeline-item { position: relative; padding-left: 60px; margin-bottom: 30px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-step { position: absolute; left: 0; top: 0; width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2em; border: 3px solid #fff; box-shadow: 0 0 0 3px var(--primary-color); }
        .timeline-content { background: #f8f9fa; border-radius: 8px; padding: 20px; border: 1px solid #e9ecef; }
        .timeline-content h4 { font-size: 1.2em; margin-bottom: 8px; color: var(--text-dark); }
        .timeline-content p { font-size: 0.95em; line-height: 1.6; color: var(--text-light); }
        .timeline-content a { color: var(--secondary-color); font-weight: bold; text-decoration: none; }
        .timeline-content a:hover { text-decoration: underline; }

        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .footer-right .social-icons a {
            color: #e9ecef;
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
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
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
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Selamat Datang, <?php echo htmlspecialchars(explode(' ', $nama)[0]); ?>!</h1>
        <p>Portal Event Mahasiswa UNPAR. Ajukan, kelola, dan temukan event dengan mudah.</p>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3 class="dashboard-section-title">
                <span>🗓️ Event Mahasiswa Terdekat</span>
                <a href="mahasiswa_event.php" class="view-all-link">Lihat Semua →</a>
            </h3>
            <?php if (!empty($upcoming_events)): ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-card">
                        <div class="event-card-date">
                            <div class="event-card-day"><?php echo date('d', strtotime($event['pengajuan_event_tanggal_mulai'])); ?></div>
                            <div class="event-card-month"><?php echo date('M', strtotime($event['pengajuan_event_tanggal_mulai'])); ?></div>
                        </div>
                        <div class="event-card-info">
                            <h4><?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?></h4>
                            <p><i class="far fa-clock"></i> Pukul <?php echo date('H:i', strtotime($event['pengajuan_event_jam_mulai'])); ?> WIB</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data-message">Tidak ada event mahasiswa yang akan datang.</p>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3 class="dashboard-section-title">
                <span>📄 Aktivitas Pengajuan Terakhir</span>
                <a href="mahasiswa_history.php" class="view-all-link">Lihat Riwayat →</a>
            </h3>
            <?php if (!empty($recent_submissions)): ?>
                <?php foreach ($recent_submissions as $submission): ?>
                    <div class="submission-card status-<?php echo htmlspecialchars($submission['pengajuan_status']); ?>">
                        <div class="status-badge status-<?php echo htmlspecialchars($submission['pengajuan_status']); ?>"><?php echo htmlspecialchars($submission['pengajuan_status']); ?></div>
                        <h4 class="submission-card-title"><?php echo htmlspecialchars($submission['pengajuan_namaEvent']); ?></h4>
                        <p class="submission-card-date">Terakhir diubah: <?php echo date('d F Y, H:i', strtotime($submission['pengajuan_tanggalEdit'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data-message">Anda belum memiliki aktivitas pengajuan event.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="calendar-wrapper">
        <h2 class="calendar-title">KALENDER INSTITUSIONAL UNPAR</h2>
        <div class="calendar-header">
            <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-arrow">←</a>
            <h2><?php setlocale(LC_TIME, 'id_ID.UTF-8'); echo mb_convert_case(strftime('%B %Y', $date->getTimestamp()), MB_CASE_TITLE, "UTF-8"); ?></h2>
            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-arrow">→</a>
        </div>
        <div class="calendar-grid">
                <div class="day-name">Senin</div><div class="day-name">Selasa</div><div class="day-name">Rabu</div><div class="day-name">Kamis</div><div class="day-name">Jumat</div><div class="day-name">Sabtu</div><div class="day-name">Minggu</div>
            <?php 
            $today_day = date('j');
            $today_month = date('n');
            $today_year = date('Y');

            for ($i = 1; $i < $firstDayOfWeek; $i++) { echo '<div class="day-cell empty-day"></div>'; }
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($day == $today_day && $currentMonth == $today_month && $currentYear == $today_year);
                $cellClass = 'day-cell' . ($isToday ? ' today' : '');

                echo '<div class="' . $cellClass . '">';
                echo '<div class="day-number">' . $day . '</div>';
                
                if (isset($calendar_events[$day])) {
                    $unique_events = [];
                    foreach ($calendar_events[$day] as $event) {
                        $unique_events[$event['name']] = $event;
                    }

                    foreach ($unique_events as $event) {
                        $eventTypeClass = ($event['type'] === 'main') ? '' : ' prep-clear';
                        echo '<div class="event-indicator' . $eventTypeClass . '" title="' . htmlspecialchars($event['name']) . '">' . htmlspecialchars($event['name']) . '</div>';
                    }
                }
                echo '</div>';
            }

            $totalCells = $firstDayOfWeek - 1 + $daysInMonth;
            $remainingCells = (7 - ($totalCells % 7)) % 7;
            if ($remainingCells > 0) {
                for ($i = 0; $i < $remainingCells; $i++) {
                    echo '<div class="day-cell empty-day"></div>';
                }
            }
            ?>
        </div>
            <div class="detail-link-container">
                <a href="mahasiswa_event.php" class="detail-link">Klik Untuk Kalender Lebih Detail</a>
            </div>
    </div>
    <br>
    <br>
        <div class="service-flow-container">
        <h2>Alur Pengajuan Event</h2>
        <ul class="timeline">
            <li class="timeline-item">
                <div class="timeline-step">1</div>
                <div class="timeline-content">
                    <div>
                        <h4>Baca Peraturan & Ketentuan</h4>
                        <p>Pahami semua persyaratan yang tercantum di halaman <a href="mahasiswa_rules.php">Rules</a> sebelum memulai proses pengajuan.</p>
                    </div>
                </div>
            </li>
            <li class="timeline-item">
                <div class="timeline-step">2</div>
                <div class="timeline-content">
                    <div>
                        <h4>Cek Ketersediaan Ruangan</h4>
                        <p>Hubungi Administrasi Sarana & Prasarana (ASP) untuk memastikan ruangan yang Anda inginkan tersedia pada tanggal acara.</p>
                    </div>
                </div>
            </li>
            <li class="timeline-item">
                <div class="timeline-step">3</div>
                <div class="timeline-content">
                    <div>
                        <h4>Isi Formulir Pengajuan</h4>
                        <p>Setelah ruangan terkonfirmasi, isi formulir pengajuan secara lengkap dan akurat melalui halaman <a href="mahasiswa_pengajuan.php">Form</a>.</p>
                    </div>
                </div>
            </li>
            <li class="timeline-item">
                <div class="timeline-step">4</div>
                <div class="timeline-content">
                    <div>
                        <h4>Tunggu Persetujuan Ditmawa</h4>
                        <p>Pengajuan Anda akan direview oleh Ditmawa. Anda bisa memantau status pengajuan di halaman <a href="mahasiswa_history.php">History</a>.</p>
                    </div>
                </div>
            </li>
            <li class="timeline-item">
                <div class="timeline-step">5</div>
                <div class="timeline-content">
                    <div>
                        <h4>Unggah Laporan (LPJ)</h4>
                        <p>Setelah acara Anda disetujui dan selesai dilaksanakan, segera unggah Laporan Pertanggungjawaban (LPJ) melalui halaman <a href="mahasiswa_laporan.php">Laporan</a>.</p>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</div>

<script>
// Script notifikasi tidak diubah, tetap sama
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');
    const markAsReadButton = document.getElementById('markAsRead');

    function fetchNotifications() {
        fetch('../includes/fetch_notifications.php')
            .then(response => {
                if (!response.ok) { throw new Error('Network response error'); }
                return response.json();
            })
            .then(data => {
                notificationList.innerHTML = '';
                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const li = document.createElement('li');
                        li.classList.add('notification-list-item');
                        if (notif.is_read == 0) {
                            li.classList.add('unread');
                        }
                        
                        const statusDot = `<span class="status-dot ${notif.is_read == 0 ? 'unread' : 'read'}"></span>`;

                        li.innerHTML = `
                            <a href="${notif.link || '#'}">
                                ${statusDot}
                                <div class="notification-content">
                                    <p class="notification-message">${notif.message}</p>
                                    <small class="notification-time">${notif.time_ago}</small>
                                </div>
                            </a>`;
                        notificationList.appendChild(li);
                    });

                    if (data.unread_count > 0) {
                        notificationBadge.textContent = data.unread_count;
                        notificationBadge.style.display = 'block';
                    } else {
                        notificationBadge.style.display = 'none';
                    }
                } else {
                    notificationList.innerHTML = '<li class="no-notifications">Tidak ada notifikasi.</li>';
                    notificationBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                notificationList.innerHTML = '<li class="no-notifications">Gagal memuat notifikasi.</li>';
            });
    }

    notificationBell.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('show');
        if (notificationsDropdown.classList.contains('show')) {
            fetchNotifications();
        }
    });

    markAsReadButton.addEventListener('click', () => {
        fetch('../includes/mark_notifications_as_read.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    fetchNotifications();
                }
            });
    });

    document.addEventListener('click', (e) => {
        if (notificationsDropdown.classList.contains('show') && !notificationBell.contains(e.target) && !notificationsDropdown.contains(e.target)) {
            notificationsDropdown.classList.remove('show');
        }
    });
    
    fetchNotifications();
});
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
