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
        
        // Data Kalender
        $stmt_calendar = $conn->prepare("SELECT pengajuan_namaEvent, pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai FROM pengajuan_event WHERE pengajuan_status = 'Disetujui' AND ((MONTH(pengajuan_event_tanggal_mulai) = ? AND YEAR(pengajuan_event_tanggal_mulai) = ?) OR (MONTH(pengajuan_event_tanggal_selesai) = ? AND YEAR(pengajuan_event_tanggal_selesai) = ?) OR (pengajuan_event_tanggal_mulai <= ? AND pengajuan_event_tanggal_selesai >= ?))");
        $startOfMonthForQuery = "$currentYear-$currentMonth-01";
        $endOfMonthForQuery = "$currentYear-$currentMonth-$daysInMonth";
        if ($stmt_calendar) {
            $stmt_calendar->bind_param("iissss", $currentMonth, $currentYear, $currentMonth, $currentYear, $endOfMonthForQuery, $startOfMonthForQuery);
            $stmt_calendar->execute();
            $result = $stmt_calendar->get_result();
            while ($row = $result->fetch_assoc()) {
                $period = new DatePeriod(new DateTime($row['pengajuan_event_tanggal_mulai']), new DateInterval('P1D'), (new DateTime($row['pengajuan_event_tanggal_selesai']))->modify('+1 day'));
                foreach ($period as $day) {
                    if ($day->format('n') == $currentMonth && $day->format('Y') == $currentYear) {
                        $calendar_events[$day->format('j')][] = htmlspecialchars($row['pengajuan_namaEvent']);
                    }
                }
            }
            $stmt_calendar->close();
        }

        // Data Event Mahasiswa Mendatang (Hanya 1 Terdekat)
        $stmt_events = $conn->prepare("SELECT pengajuan_namaEvent, pengajuan_event_tanggal_mulai, pengajuan_event_jam_mulai FROM pengajuan_event WHERE pengajuan_status = 'Disetujui' AND pengajuan_event_tanggal_selesai >= CURDATE() ORDER BY pengajuan_event_tanggal_mulai ASC, pengajuan_event_jam_mulai ASC LIMIT 1");
        if ($stmt_events) {
            $stmt_events->execute();
            $upcoming_events = $stmt_events->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_events->close();
        }

        // Data Aktivitas Pengajuan Terbaru (Hanya 1 terbaru)
        $stmt_submissions = $conn->prepare("SELECT pengajuan_namaEvent, pengajuan_status, pengajuan_tanggalEdit FROM pengajuan_event WHERE mahasiswa_id = ? ORDER BY pengajuan_tanggalEdit DESC LIMIT 1");
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
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background: var(--light-gray); padding-top: 80px; }
        
        .navbar { display: flex; justify-content: space-between; align-items: center; background: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: white; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #a7d8de; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: white; }
        .icon { font-size: 20px; cursor: pointer; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .welcome-section { background: linear-gradient(135deg, var(--primary-color) 0%, #307a4a 100%); color: white; border-radius: 12px; padding: 30px; margin-bottom: 30px; text-align: center; }
        .no-data-message { text-align: center; color: var(--text-light); padding: 40px 20px; font-style: italic; background: #fff; border-radius: 8px; border: 1px dashed var(--border-color); }
        .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 30px; margin-bottom: 30px; }
        @media (min-width: 1200px) { .dashboard-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
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
        .submission-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; position: relative; border-left: 5px solid; }
        .submission-card:not(:last-child) { margin-bottom: 15px; }
        .submission-card-title { font-size: 1.1em; color: white; margin-bottom: 8px; padding-right: 90px; }
        .submission-card-date { font-size: 0.9em; color: white; }
        .status-badge { position: absolute; top: 20px; right: 20px; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: 600; color: white; }
        .status-Disetujui { background-color: var(--status-green); border-color: var(--status-green); }
        .status-Ditolak { background-color: var(--status-red); border-color: var(--status-red); }
        .status-Diajukan { background-color: var(--status-yellow); color: #333 !important; border-color: var(--status-yellow); }
        .calendar-wrapper { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .calendar-title { text-align: center; color: var(--text-dark); font-size: 1.8em; margin-bottom: 20px; font-weight: 600; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { font-size: 1.5em; flex-grow: 1; text-align: center; }
        .calendar-header .nav-arrow { color: var(--primary-color); text-decoration: none; font-size: 1.5em; padding: 0 15px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background-color: var(--border-color); border: 1px solid var(--border-color); }
        .day-name { text-align: center; font-weight: 600; color: var(--text-light); padding: 10px 0; background-color: var(--light-gray); }
        .day-cell { background-color: #fff; padding: 8px; min-height: 100px; position: relative; }
        .day-number { font-weight: bold; }
        .event-indicator { font-size: 0.8em; background: var(--status-green); color: white; padding: 3px 5px; border-radius: 4px; margin-top: 5px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .empty-day { background-color: var(--light-gray); }
        
        /* === CSS Notifikasi yang Diperbarui === */
        .notification-container { position: relative; }
        .notification-badge { position: absolute; top: -5px; right: -8px; background-color: var(--status-red); color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; display: none; border: 1px solid white; }
        .notifications-dropdown { display: none; position: absolute; top: 50px; right: 0; background-color: #f8f9fa; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); width: 380px; z-index: 1001; }
        .notifications-dropdown.show { display: block; }
        .notifications-header { padding: 12px 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .notifications-header h4 { margin: 0; color: var(--text-dark); font-size: 1em; }
        #markAsRead { cursor:pointer; font-size:12px; color:var(--secondary-color); font-weight: 500; }
        #notificationList { list-style: none; padding: 8px; margin: 0; max-height: 320px; overflow-y: auto; }
        .notification-list-item { background: #fff; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); list-style: none; }
        .notification-list-item a { text-decoration: none; color: inherit; display: flex; align-items: center; padding: 12px 15px; }
        .notification-list-item.unread { background-color: #fff8f8; /* Latar belakang merah muda untuk belum dibaca */ }
        .status-dot { height: 10px; width: 10px; border-radius: 50%; flex-shrink: 0; margin-right: 12px; }
        .status-dot.unread { background-color: var(--status-red); } /* Titik merah */
        .status-dot.read { background-color: var(--secondary-color); } /* Titik biru */
        .notification-message { margin: 0 0 4px 0; font-size: 14px; color: var(--text-dark); line-height: 1.4; }
        .notification-time { font-size: 12px; color: var(--text-light); }
        .no-notifications { padding: 20px; text-align: center; color: #777; }
        .detail-link-container { text-align: center; margin-top: 15px; }
        .detail-link { color: #dc3545; text-decoration: none; font-weight: bold; }
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
        <div class="notification-container">
            <i class="fas fa-bell icon" id="notificationBell"></i>
            <span class="notification-badge" id="notificationBadge"></span>
            <div class="notifications-dropdown" id="notificationsDropdown">
                <div class="notifications-header">
                    <h4>Notifikasi</h4>
                    <span id="markAsRead">Tandai semua dibaca</span>
                </div>
                <ul id="notificationList">
                    </ul>
            </div>
        </div>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Selamat Datang, <?php echo htmlspecialchars(explode(' ', $nama)[0]); ?>!</h1>
        <p>Portal Event Mahasiswa UNPAR. Ajukan, kelola, dan temukan event dengan mudah.</p>
    </div>

    <div class="dashboard-grid">
        <div>
            <h3 class="dashboard-section-title">
                <span>🗓️ Event Mahasiswa Terdekat</span>
                <a href="mahasiswa_event.php" class="view-all-link">Lihat Semua &rarr;</a>
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

        <div>
            <h3 class="dashboard-section-title">
                <span>📄 Aktivitas Pengajuan Terakhir</span>
                <a href="mahasiswa_history.php" class="view-all-link">Lihat Riwayat &rarr;</a>
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
            <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-arrow">&larr;</a>
            <h2><?php setlocale(LC_TIME, 'id_ID.UTF-8'); echo mb_convert_case(strftime('%B %Y', $date->getTimestamp()), MB_CASE_TITLE, "UTF-8"); ?></h2>
            <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-arrow">&rarr;</a>
        </div>
        <div class="calendar-grid">
                <div class="day-name">Senin</div><div class="day-name">Selasa</div><div class="day-name">Rabu</div><div class="day-name">Kamis</div><div class="day-name">Jumat</div><div class="day-name">Sabtu</div><div class="day-name">Minggu</div>
            <?php 
            for ($i = 1; $i < $firstDayOfWeek; $i++) { echo '<div class="day-cell empty-day"></div>'; }
            for ($day = 1; $day <= $daysInMonth; $day++) {
                echo '<div class="day-cell">';
                echo '<div class="day-number">' . $day . '</div>';
                if (isset($calendar_events[$day])) {
                    foreach ($calendar_events[$day] as $eventName) {
                        echo '<div class="event-indicator" title="' . htmlspecialchars($eventName) . '">' . htmlspecialchars($eventName) . '</div>';
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
</div>

<script>
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

</body>
</html>