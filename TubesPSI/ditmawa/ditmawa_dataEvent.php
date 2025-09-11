<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';

$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($currentMonth < 1) { $currentMonth = 12; $currentYear--; } 
elseif ($currentMonth > 12) { $currentMonth = 1; $currentYear++; }

$date = new DateTime("$currentYear-$currentMonth-01");

$prevMonth = $currentMonth - 1; $prevYear = $currentYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $currentMonth + 1; $nextYear = $currentYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$buildings = [];
$floors = [];
try {
    if ($conn) {
        $result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY gedung_nama");
        while ($row = $result_gedung->fetch_assoc()) { $buildings[] = $row; }
        
        $result_lantai = $conn->query("SELECT lantai_id, gedung_id, lantai_nomor FROM lantai ORDER BY gedung_id, lantai_nomor");
        while ($row = $result_lantai->fetch_assoc()) { $floors[] = $row; }
    }
} catch (Exception $e) {
    error_log("Error fetching location data for Ditmawa: " . $e->getMessage());
}

$calendar_events = [];
$events_by_id = []; 

try {
    $stmt = $conn->prepare("
        SELECT 
            pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_event_tanggal_mulai,
            pe.pengajuan_event_tanggal_selesai, pe.tanggal_persiapan, pe.tanggal_beres,
            r.lantai_id, l.gedung_id
        FROM pengajuan_event pe
        LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
        LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
        LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
        WHERE pe.pengajuan_status = 'Disetujui' AND (
            (MONTH(pe.pengajuan_event_tanggal_mulai) = ? AND YEAR(pe.pengajuan_event_tanggal_mulai) = ?) OR
            (MONTH(pe.pengajuan_event_tanggal_selesai) = ? AND YEAR(pe.pengajuan_event_tanggal_selesai) = ?) OR
            (MONTH(pe.tanggal_persiapan) = ? AND YEAR(pe.tanggal_persiapan) = ?) OR
            (MONTH(pe.tanggal_beres) = ? AND YEAR(pe.tanggal_beres) = ?)
        )
    ");
    $stmt->bind_param("iiiiiiii", $currentMonth, $currentYear, $currentMonth, $currentYear, $currentMonth, $currentYear, $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $id = $row['pengajuan_id'];
        if (!isset($events_by_id[$id])) {
            $events_by_id[$id] = [
                'name'      => htmlspecialchars($row['pengajuan_namaEvent']),
                'start'     => $row['pengajuan_event_tanggal_mulai'],
                'end'       => $row['pengajuan_event_tanggal_selesai'],
                'prep'      => $row['tanggal_persiapan'],
                'clear'     => $row['tanggal_beres'],
                'locations' => []
            ];
        }
        if ($row['gedung_id'] && $row['lantai_id']) {
            $locations_key = $row['gedung_id'] . '-' . $row['lantai_id'];
            if (!isset($events_by_id[$id]['locations'][$locations_key])) {
                $events_by_id[$id]['locations'][$locations_key] = ['gedung' => $row['gedung_id'], 'lantai' => $row['lantai_id']];
            }
        }
    }
    $stmt->close();
    
    foreach ($events_by_id as &$event_data) {
        $event_data['locations'] = array_values($event_data['locations']);
    }
    unset($event_data);

    foreach ($events_by_id as $event) {
        // Proses tanggal utama event
        $start = new DateTime($event['start']);
        $end = (new DateTime($event['end']))->modify('+1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($period as $dt) {
            if ($dt->format('n') == $currentMonth) {
                $day = (int)$dt->format('j');
                $calendar_events[$day][] = ['name' => $event['name'], 'type' => 'main', 'locations' => $event['locations']];
            }
        }
    
        // Logika untuk rentang waktu persiapan
        if (!empty($event['prep'])) {
            $prep_start_dt = new DateTime($event['prep']);
            $main_event_start_dt = new DateTime($event['start']);
            if ($prep_start_dt < $main_event_start_dt) {
                $prep_period = new DatePeriod($prep_start_dt, new DateInterval('P1D'), $main_event_start_dt);
                foreach ($prep_period as $dt) {
                    if ($dt->format('n') == $currentMonth) {
                        $day = (int)$dt->format('j');
                        $calendar_events[$day][] = ['name' => $event['name'] . ' (Persiapan)', 'type' => 'prep', 'locations' => $event['locations'], 'main_start_date' => $event['start']];
                    }
                }
            }
        }
    
        // Logika untuk rentang waktu beres-beres
        if (!empty($event['clear'])) {
            $main_event_end_dt = new DateTime($event['end']);
            $clear_end_dt = new DateTime($event['clear']);
            if ($clear_end_dt > $main_event_end_dt) {
                $clear_start_dt = (clone $main_event_end_dt)->modify('+1 day');
                $clear_period_end_dt = (clone $clear_end_dt)->modify('+1 day');
                $clear_period = new DatePeriod($clear_start_dt, new DateInterval('P1D'), $clear_period_end_dt);
                foreach ($clear_period as $dt) {
                    if ($dt->format('n') == $currentMonth) {
                        $day = (int)$dt->format('j');
                        $calendar_events[$day][] = ['name' => $event['name'] . ' (Beres-beres)', 'type' => 'clear', 'locations' => $event['locations'], 'main_start_date' => $event['start']];
                    }
                }
            }
        }
    }

} catch (Exception $e) {
    error_log("Error fetching calendar data in ditmawa_dataEvent.php: " . $e->getMessage());
}
$conn->close();
$calendar_events_json = json_encode($calendar_events);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kalender Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100%; padding-top: 80px; display: flex; flex-direction: column; }
        .main-content { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; padding: 0; margin: 0; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; cursor: pointer; }
        .page-header { background: linear-gradient(135deg, rgb(2, 73, 43) 0%, rgb(2, 71, 25) 100%); color: white; padding: 25px; margin: 20px auto; max-width: 1100px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .page-header h1 { margin-bottom: 10px; font-size: 28px; }
        .page-header p { opacity: 0.9; font-size: 16px; }
        .calendar-container { max-width: 1100px; margin: 20px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .calendar-header h2 { font-size: 28px; }
        .calendar-header a { text-decoration: none; font-size: 24px; color: #ff8c00; }
        .filter-section { display: flex; gap: 20px; margin-bottom: 20px; justify-content: center; }
        .filter-section select { padding: 8px 12px; border-radius: 8px; border: 1px solid #ddd; font-size: 16px; min-width: 200px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }
        .day-name, .day-cell { border: 1px solid #eee; border-radius: 8px; padding: 10px; }
        .day-name { text-align: center; font-weight: 600; background-color: #f8f9fa; }
        .day-cell { min-height: 120px; cursor: pointer; transition: background-color 0.2s; }
        .day-cell:not(.empty-day):hover { background-color: #f0f0f0; }
        .day-number { font-size: 18px; font-weight: bold; }
        .event-indicator { 
            font-size: 12px; font-weight: 600; margin-top: 5px; white-space: nowrap; overflow: hidden; 
            text-overflow: ellipsis; width: 100%; padding: 3px 6px; border-radius: 4px; display: block;
            background-color: #27ae60; color: white; 
        }
        .event-indicator.prep-day { background-color: #dc3545; color: white; }
        .event-indicator.clear-day { background-color: #dc3545; color: #ffffffff; }
        .empty-day { background-color: #fafafa; cursor: default; }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 10px; width: 90%; max-width: 400px; position: relative; }
        .close-button { position: absolute; top: 10px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-header h3 { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-body .event-item { border-left: 4px solid #ff8c00; padding: 10px; margin-bottom: 10px; background-color: #fff9f2; }
        .modal-body .event-item h4 { margin-bottom: 5px; color: #d97706; }
        .modal-body .event-item span { display: block; font-size: 14px; color: #555; }
        .page-footer { background-color: #ff8c00; color: #fff; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
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
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php" class="active">Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="page-header">
        <h1>Kalender Event UNPAR</h1>
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
        <div class="calendar-grid" id="calendarGrid"></div>
    </div>
</div>

<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-header"><h3 id="modalDate"></h3></div>
        <div class="modal-body" id="modalBody"></div>
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
        </div>
    </div>
</footer>

<script>
    const calendarEventsData = <?php echo $calendar_events_json; ?>;
</script>

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
        renderFilteredCalendar();
    }

    function renderFilteredCalendar() {
        const selectedGedung = gedungFilter.value;
        const selectedLantai = lantaiFilter.value;
        const filteredEvents = {};
        for (const day in calendarEventsData) {
            if (Object.hasOwnProperty.call(calendarEventsData, day)) {
                const dayEvents = calendarEventsData[day].filter(event => {
                    if (!selectedGedung && !selectedLantai) return true;
                    if (!event.locations || event.locations.length === 0) {
                         return !selectedGedung && !selectedLantai;
                    }
                    return event.locations.some(loc => {
                        const gedungMatch = !selectedGedung || loc.gedung == selectedGedung;
                        const lantaiMatch = !selectedLantai || loc.lantai == selectedLantai;
                        return gedungMatch && lantaiMatch;
                    });
                });
                if (dayEvents.length > 0) {
                    filteredEvents[day] = dayEvents;
                }
            }
        }
        renderCalendar(filteredEvents);
    }
    
    function renderCalendar(eventsData) {
        const date = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        let firstDayOfWeek = date.getDay(); 
        if (firstDayOfWeek === 0) firstDayOfWeek = 7; 
        let html = `<div class="day-name">Senin</div><div class="day-name">Selasa</div><div class="day-name">Rabu</div><div class="day-name">Kamis</div><div class="day-name">Jumat</div><div class="day-name">Sabtu</div><div class="day-name">Minggu</div>`;
        for (let i = 1; i < firstDayOfWeek; i++) {
            html += '<div class="day-cell empty-day"></div>';
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const fullDate = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayEvents = eventsData[day] || [];
            const hasEvents = dayEvents.length > 0;
            html += `<div class="day-cell ${hasEvents ? 'has-events' : ''}" data-date="${fullDate}">`;
            html += `<div class="day-number">${day}</div>`;
            if (hasEvents) {
                const uniqueEvents = {};
                dayEvents.forEach(event => {
                    const key = event.name + event.type;
                    if (!uniqueEvents[key]) {
                        uniqueEvents[key] = event;
                    }
                });
                Object.values(uniqueEvents).forEach(event => {
                    let eventTypeClass = '';
                    if (event.type === 'prep') { eventTypeClass = 'prep-day'; } 
                    else if (event.type === 'clear') { eventTypeClass = 'clear-day'; }
                    html += `<span class="event-indicator ${eventTypeClass}">${event.name}</span>`;
                });
            }
            html += `</div>`;
        }
        calendarGrid.innerHTML = html;
        addDayCellClickListeners();
    }

    function addDayCellClickListeners() {
        document.querySelectorAll('.day-cell:not(.empty-day)').forEach(cell => {
            cell.addEventListener('click', function() {
                const cellDate = this.dataset.date;
                const dayNumber = new Date(cellDate + 'T00:00:00Z').getUTCDate();
                const selectedGedung = gedungFilter.value;
                const selectedLantai = lantaiFilter.value;

                let dateForFetch = cellDate;

                const dayEvents = calendarEventsData[dayNumber] || [];
                const filteredDayEvents = dayEvents.filter(event => {
                    if (!selectedGedung && !selectedLantai) return true;
                    if (!event.locations || event.locations.length === 0) {
                        return !selectedGedung && !selectedLantai;
                    }
                    return event.locations.some(loc => {
                        const gedungMatch = !selectedGedung || loc.gedung == selectedGedung;
                        const lantaiMatch = !selectedLantai || loc.lantai == selectedLantai;
                        return gedungMatch && lantaiMatch;
                    });
                });

                if (filteredDayEvents.length > 0) {
                    const firstEvent = filteredDayEvents[0];
                    if ((firstEvent.type === 'prep' || firstEvent.type === 'clear') && firstEvent.main_start_date) {
                        dateForFetch = firstEvent.main_start_date.split(' ')[0];
                    }
                }
                
                document.getElementById('modalDate').textContent = new Date(cellDate + 'T00:00:00Z').toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('modalBody').innerHTML = '<p>Memuat...</p>';
                eventModal.style.display = 'flex';

                fetch(`../fetch_event_details.php?date=${dateForFetch}&gedung_id=${selectedGedung}&lantai_id=${selectedLantai}`)
                    .then(response => response.json())
                    .then(data => {
                        const modalBody = document.getElementById('modalBody');
                        modalBody.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(event => {
                                const eventItem = document.createElement('div');
                                eventItem.className = 'event-item';
                                eventItem.innerHTML = `<h4>${event.name}</h4><span><strong>Waktu:</strong> ${event.start_time} - ${event.end_time}</span><span><strong>Lokasi:</strong> ${event.lokasi}</span>`;
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
    lantaiFilter.addEventListener('change', renderFilteredCalendar);
    closeButton.addEventListener('click', () => { eventModal.style.display = 'none'; });
    window.addEventListener('click', (event) => { if (event.target == eventModal) { eventModal.style.display = 'none'; } });

    renderFilteredCalendar(); 
});
</script>

</body>
</html>