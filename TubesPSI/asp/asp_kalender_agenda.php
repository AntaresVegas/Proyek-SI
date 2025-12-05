<?php
session_start();
require_once('../config/db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff ASP';
$initialView = $_GET['view'] ?? 'week';
$jsInitialView = ($initialView === 'week') ? 'timeGridWeek' : 'timeGridDay';
$initialDate = $_GET['date'] ?? date('Y-m-d');

$buildings = [];
$floors = [];
try {
    $result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY LENGTH(gedung_nama), gedung_nama");
    while ($row = $result_gedung->fetch_assoc()) { $buildings[] = $row; }
    $result_lantai = $conn->query("SELECT lantai_id, gedung_id, lantai_nomor FROM lantai ORDER BY gedung_id, CAST(lantai_nomor AS UNSIGNED)");
    while ($row = $result_lantai->fetch_assoc()) { $floors[] = $row; }
} catch (Exception $e) { error_log("Error fetching location data: " . $e->getMessage()); }
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Agenda Peminjaman - Event UNPAR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <style>
        /* ... (CSS ASP Anda) ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 70px; background-image: url('../img/backgroundASP.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #0A2342; width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; margin: 0; padding: 0; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; transition: color 0.3s; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #FFD700; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: #FFFFFF; }
        .navbar-right a {color: #FFFFFF;}
        .icon { font-size: 20px; cursor: pointer; }
        .page-footer { background-color: #0A2342; color: #E0E0E0; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #FFFFFF; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .calendar-container-wrapper { max-width: 1200px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .page-header { background: linear-gradient(135deg, #0A2342 0%, #1a4a8a 100%); color: white; padding: 25px; margin: -30px -30px 30px -30px; border-radius: 15px 15px 0 0; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .page-header h1 { margin-bottom: 10px; font-size: 28px; }
        .page-header p { opacity: 0.9; font-size: 16px; }
        :root { --fc-border-color: #ddd; --fc-today-bg-color: rgba(10, 35, 66, 0.1); --fc-button-bg-color: #0A2342; --fc-button-active-bg-color: #1a4a8a; --fc-button-hover-bg-color: #1a4a8a; }
        .fc-event { cursor: pointer; }
        .fc-header-toolbar { display: none; }
        .custom-calendar-header { display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center; margin-bottom: 20px; }
        .calendar-nav-buttons { display: flex; gap: 5px; }
        .calendar-nav-buttons button, .calendar-nav-buttons a { background: none; border: 1px solid #ddd; border-radius: 8px; padding: 8px 12px; font-size: 14px; cursor: pointer; color: #0A2342; text-decoration: none; font-weight: 500; }
        .calendar-title { text-align: center; font-size: 26px; font-weight: 600; color: #333; }
        .view-toggle { display: flex; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; justify-self: end; }
        .view-toggle a, .view-toggle button { padding: 8px 16px; text-decoration: none; color: #333; font-size: 14px; font-weight: 500; border-left: 1px solid #ddd; background: none; border: none; cursor: pointer; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .view-toggle a:first-child, .view-toggle button:first-child { border-left: none; }
        .view-toggle a:hover, .view-toggle button:hover { background-color: #f0f0f0; }
        .view-toggle a.active, .view-toggle button.active { background-color: #0A2342; color: white; }
        .filter-section { display: flex; gap: 20px; margin-bottom: 20px; justify-content: center; padding-top: 15px; border-top: 1px solid #eee; }
        .filter-section select { padding: 8px 12px; border-radius: 8px; border: 1px solid #ddd; font-size: 16px; min-width: 200px; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan Sarana & Prasarana</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="asp_dashboard.php">Home</a></li>
        <li><a href="asp_listKegiatan.php">Persetujuan Event</a></li>
        <li><a href="asp_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="asp_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="asp_kalender.php" class="active">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="calendar-container-wrapper">
    <div class="page-header">
        <h1>Agenda Peminjaman</h1>
        <p>Menampilkan jadwal event yang proposalnya sudah disetujui penuh.</p>
    </div>
    <div class="custom-calendar-header">
        <div class="calendar-nav-buttons">
            <button id="prev-button">&larr;</button>
            <button id="next-button">&rarr;</button>
            <button id="today-button">Hari Ini</button>
        </div>
        <div id="calendar-title" class="calendar-title"></div>
        <div class="view-toggle">
            <a href="asp_kalender.php?month=<?php echo date('n', strtotime($initialDate)); ?>&year=<?php echo date('Y', strtotime($initialDate)); ?>">Bulan</a>
            <button id="week-button">Minggu</button>
            <button id="day-button">Hari</button>
        </div>
    </div>
    <div class="filter-section">
        <select id="gedungFilter">
            <option value="">Semua Gedung</option>
            <?php foreach ($buildings as $building): ?>
                <option value="<?php echo $building['gedung_id']; ?>"><?php echo htmlspecialchars($building['gedung_nama']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="lantaiFilter" disabled>
            <option value="">Semua Lantai</option>
        </select>
    </div>
    <div id='calendar'></div>
</div>

<script>
    const allFloors = <?php echo json_encode($floors); ?>;
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        const gedungFilter = document.getElementById('gedungFilter');
        const lantaiFilter = document.getElementById('lantaiFilter');
        const initialView = '<?php echo $jsInitialView; ?>';
        const initialDate = '<?php echo $initialDate; ?>';
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: false, 
            initialView: initialView,
            initialDate: initialDate,
            locale: 'id',
            slotMinTime: '06:00:00',
            slotMaxTime: '24:00:00',
            allDaySlot: true,
            events: function(fetchInfo, successCallback, failureCallback) {
                const gedungId = gedungFilter.value;
                const lantaiId = lantaiFilter.value;
                // Memanggil API ASP (yang query-nya sudah diperbaiki)
                const url = `asp_kalender_api.php?gedung_id=${gedungId}&lantai_id=${lantaiId}`;
                fetch(url)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => {
                        console.error('Error fetching FullCalendar events:', error);
                        failureCallback(error);
                    });
            },
            // Link sudah benar mengarah ke file persetujuan
            eventClick: function(info) {
                info.jsEvent.preventDefault(); 
                var eventId = info.event.id;
                window.open('asp_persetujuan.php?id=' + eventId, '_blank');
            },
            datesSet: function(dateInfo) {
                document.getElementById('calendar-title').innerText = dateInfo.view.title;
                document.getElementById('week-button').classList.toggle('active', dateInfo.view.type === 'timeGridWeek');
                document.getElementById('day-button').classList.toggle('active', dateInfo.view.type === 'timeGridDay');
            }
        });
        calendar.render();
        function updateLantaiFilter() {
            const selectedGedungId = gedungFilter.value;
            lantaiFilter.innerHTML = '<option value="">Semua Lantai</option>';
            lantaiFilter.disabled = true;
            if (selectedGedungId) {
                const filteredFloors = allFloors.filter(floor => floor.gedung_id == selectedGedungId);
                filteredFloors.forEach(floor => {
                    const option = document.createElement('option');
                    option.value = floor.lantai_id;
                    option.textContent = `Lantai ${floor.lantai_nomor}`;
                    lantaiFilter.appendChild(option);
                });
                lantaiFilter.disabled = false;
            }
            calendar.refetchEvents();
        }
        gedungFilter.addEventListener('change', updateLantaiFilter);
        lantaiFilter.addEventListener('change', function() {
            calendar.refetchEvents();
        });
        document.getElementById('prev-button').addEventListener('click', function() { calendar.prev(); });
        document.getElementById('next-button').addEventListener('click', function() { calendar.next(); });
        document.getElementById('today-button').addEventListener('click', function() { calendar.today(); });
        document.getElementById('week-button').addEventListener('click', function() { calendar.changeView('timeGridWeek'); });
        document.getElementById('day-button').addEventListener('click', function() { calendar.changeView('timeGridDay'); });
    });
</script>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;">ADMINISTRASI SARANA & PRASARANA</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                <li><i class="fas fa-envelope"></i> asp@unpar.ac.id</li>
            </ul>
        </div>
    </div>
</footer>
</body>
</html>