<?php
session_start();

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Mahasiswa';
$user_id_mahasiswa = $_SESSION['user_id'] ?? null;

// Logika Kalender
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; }
if ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }

$date = new DateTime("$currentYear-$currentMonth-01");
$daysInMonth = $date->format('t');
$firstDayOfWeek = $date->format('N'); 

$prevMonth = $currentMonth - 1; $prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $currentMonth + 1; $nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Ambil data gedung dan lantai untuk filter
$buildings = [];
$floors = [];
try {
    $result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY gedung_nama");
    while ($row = $result_gedung->fetch_assoc()) {
        $buildings[] = $row;
    }
    $result_lantai = $conn->query("SELECT lantai_id, gedung_id, lantai_nomor FROM lantai ORDER BY gedung_id, lantai_nomor");
    while ($row = $result_lantai->fetch_assoc()) {
        $floors[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching location data: " . $e->getMessage());
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Data Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Salin semua CSS dari kode sebelumnya, tidak ada perubahan di sini */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 70px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #87CEEB; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; cursor: pointer; }
        .calendar-container { max-width: 1100px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { font-size: 28px; }
        .calendar-header a { text-decoration: none; font-size: 24px; color: rgb(2, 71, 25); }
        .filter-section { display: flex; gap: 20px; margin-bottom: 20px; justify-content: center; }
        .filter-section select { padding: 8px 12px; border-radius: 8px; border: 1px solid #ddd; font-size: 16px; min-width: 200px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .day-name, .day-cell { border: 1px solid #eee; border-radius: 8px; padding: 10px; }
        .day-name { text-align: center; font-weight: 600; background-color: #f8f9fa; }
        .day-cell { min-height: 120px; cursor: pointer; transition: background-color 0.2s; }
        .day-cell:hover { background-color: #f0f0f0; }
        .day-number { font-size: 18px; font-weight: bold; }
        .event-indicator { font-size: 12px; color: rgb(2, 71, 25); font-weight: 600; margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; }
        .empty-day { background-color: #fafafa; cursor: default; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 10px; width: 90%; max-width: 400px; position: relative; }
        .close-button { position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-header h3 { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-body .event-item { border-left: 4px solid rgb(2, 71, 25); padding: 10px; margin-bottom: 10px; background-color: #f8f9fa; }
        .modal-body .event-item h4 { margin-bottom: 5px; }
        .modal-body .event-item span { display: block; font-size: 14px; color: #555; }
               /* --- STYLE BARU UNTUK HEADER --- */
        .page-header {
            background: linear-gradient(135deg, rgb(2, 73, 43) 0%, rgb(2, 71, 25) 100%);
            color: white;
            padding: 25px;
            margin: 20px auto;
            max-width: 1100px; /* Samakan dengan lebar container kalender */
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        .page-header p {
            opacity: 0.9;
            font-size: 16px;
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
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php" class="active">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>
<div class="page-header">
    <h1>Kalender Institusional UNPAR</h1>
    <p>Gunakan filter di bawah untuk melihat jadwal event berdasarkan gedung dan lantai tertentu.</p>
</div>
<div class="calendar-container">
    <div class="calendar-header">
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">&larr;</a>
        <h2><?php echo $date->format('F Y'); ?></h2>
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">&rarr;</a>
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

    <div class="calendar-grid" id="calendarGrid">
        </div>
</div>

<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-header"><h3 id="modalDate"></h3></div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarGrid = document.getElementById('calendarGrid');
    const gedungFilter = document.getElementById('gedungFilter');
    const lantaiFilter = document.getElementById('lantaiFilter');
    const eventModal = document.getElementById('eventModal');
    const closeButton = document.querySelector('.close-button');

    const allFloors = <?php echo json_encode($floors); ?>;
    const currentMonth = <?php echo $currentMonth; ?>;
    const currentYear = <?php echo $currentYear; ?>;
    
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
        fetchEventsAndRenderCalendar();
    }

    function renderCalendar(eventsData) {
        const date = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        let firstDayOfWeek = date.getDay(); 
        if (firstDayOfWeek === 0) firstDayOfWeek = 7; // Konversi Minggu (0) menjadi 7

        let html = `<div class="day-name">Sen</div><div class="day-name">Sel</div><div class="day-name">Rab</div><div class="day-name">Kam</div><div class="day-name">Jum</div><div class="day-name">Sab</div><div class="day-name">Min</div>`;
        for (let i = 1; i < firstDayOfWeek; i++) {
            html += '<div class="day-cell empty-day"></div>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const fullDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const hasEvents = eventsData[day] && eventsData[day].length > 0;
            
            html += `<div class="day-cell ${hasEvents ? 'has-events' : ''}" data-date="${fullDate}">`;
            html += `<div class="day-number">${day}</div>`;
            if (hasEvents) {
                eventsData[day].forEach(event => {
                    html += `<span class="event-indicator">${event.name}</span>`;
                });
            }
            html += `</div>`;
        }
        calendarGrid.innerHTML = html;
        addDayCellClickListeners();
    }

    function fetchEventsAndRenderCalendar() {
        const selectedGedung = gedungFilter.value;
        const selectedLantai = lantaiFilter.value;
        // PERBAIKAN: Menambahkan ../ pada path fetch karena file ini ada di dalam folder /mahasiswa
        fetch(`../fetch_event_data.php?month=${currentMonth}&year=${currentYear}&gedung_id=${selectedGedung}&lantai_id=${selectedLantai}`)
            .then(response => response.json())
            .then(data => {
                renderCalendar(data.events || {});
            })
            .catch(error => console.error('Error fetching events:', error));
    }

    function addDayCellClickListeners() {
        document.querySelectorAll('.day-cell:not(.empty-day)').forEach(cell => {
            cell.addEventListener('click', function() {
                const date = this.dataset.date;
                const selectedGedung = gedungFilter.value;
                const selectedLantai = lantaiFilter.value;

                document.getElementById('modalDate').textContent = new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('modalBody').innerHTML = '<p>Memuat...</p>';
                eventModal.style.display = 'flex';

                // PERBAIKAN: Menambahkan ../ pada path fetch dan memperbaiki cara menampilkan lokasi
                fetch(`../fetch_event_details.php?date=${date}&gedung_id=${selectedGedung}&lantai_id=${selectedLantai}`)
                    .then(response => response.json())
                    .then(data => {
                        const modalBody = document.getElementById('modalBody');
                        modalBody.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(event => {
                                const eventItem = document.createElement('div');
                                eventItem.className = 'event-item';
                                // PERBAIKAN: Menggunakan `event.lokasi` yang sudah diformat
                                eventItem.innerHTML = `
                                    <h4>${event.name}</h4>
                                    <span><strong>Waktu:</strong> ${event.start_time} - ${event.end_time}</span>
                                    <span><strong>Lokasi:</strong> ${event.lokasi}</span>
                                `;
                                modalBody.appendChild(eventItem);
                            });
                        } else {
                            modalBody.innerHTML = '<p>Tidak ada kegiatan pada tanggal ini.</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching details:', error);
                        document.getElementById('modalBody').innerHTML = '<p>Gagal memuat detail kegiatan.</p>';
                    });
            });
        });
    }

    gedungFilter.addEventListener('change', updateLantaiFilter);
    lantaiFilter.addEventListener('change', fetchEventsAndRenderCalendar);
    closeButton.addEventListener('click', () => { eventModal.style.display = 'none'; });
    window.addEventListener('click', (event) => { if (event.target == eventModal) { eventModal.style.display = 'none'; } });

    fetchEventsAndRenderCalendar();
});
</script>

</body>
</html>