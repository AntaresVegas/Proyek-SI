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
$email = $_SESSION['username'] ?? 'No email'; // Assuming username is email

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

// Fetch event data for the current month to initially populate the calendar grid
// This data will be refined by AJAX calls when filters are applied.
$initial_events_for_calendar = [];
$buildings = [];
$floors = [];

try {
    if (isset($conn) && $conn->ping()) {
        // Fetch event data for the current month
        $stmt_initial_events = $conn->prepare("
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
                    (pe.pengajuan_event_tanggal_mulai BETWEEN ? AND ?)
                    OR (pe.pengajuan_event_tanggal_selesai BETWEEN ? AND ?)
                    OR (? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai)
                    OR (? BETWEEN pe.pengajuan_event_tanggal_mulai AND pe.pengajuan_event_tanggal_selesai)
                )
            ORDER BY 
                pe.pengajuan_event_tanggal_mulai ASC, pe.pengajuan_event_jam_mulai ASC
        ");

        $startOfMonth = date('Y-m-01', strtotime("$currentYear-$currentMonth-01"));
        $endOfMonth = date('Y-m-t', strtotime("$currentYear-$currentMonth-01"));

        if ($stmt_initial_events) {
            $stmt_initial_events->bind_param("ssssss", $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth);
            $stmt_initial_events->execute();
            $result_initial_events = $stmt_initial_events->get_result();

            while ($row = $result_initial_events->fetch_assoc()) {
                $eventStartDate = new DateTime($row['pengajuan_event_tanggal_mulai']);
                $eventEndDate = new DateTime($row['pengajuan_event_tanggal_selesai']);
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($eventStartDate, $interval, $eventEndDate->modify('+1 day')); // Include end date

                foreach ($period as $dt) {
                    $day = (int)$dt->format('j');
                    $month = (int)$dt->format('n');
                    $year = (int)$dt->format('Y');

                    // Only add events for the current displayed month
                    if ($month === $currentMonth && $year === $currentYear) {
                        if (!isset($initial_events_for_calendar[$day])) {
                            $initial_events_for_calendar[$day] = [];
                        }
                        $initial_events_for_calendar[$day][] = [
                            'id' => $row['pengajuan_id'],
                            'name' => htmlspecialchars($row['pengajuan_namaEvent'])
                        ];
                    }
                }
            }
            $stmt_initial_events->close();
        } else {
            error_log("Failed to prepare statement for fetching initial events: " . $conn->error);
        }

        // Fetch buildings
        $stmt_gedung = $conn->prepare("SELECT gedung_id, gedung_nama FROM gedung ORDER BY gedung_nama");
        if ($stmt_gedung) {
            $stmt_gedung->execute();
            $result_gedung = $stmt_gedung->get_result();
            while ($row = $result_gedung->fetch_assoc()) {
                $buildings[] = $row;
            }
            $stmt_gedung->close();
        } else {
            error_log("Failed to prepare statement for fetching buildings: " . $conn->error);
        }

        // Fetch floors
        // Fetch all floors with their associated building_id for JavaScript filtering
        $stmt_lantai = $conn->prepare("SELECT lantai_id, gedung_id, lantai_nomor FROM lantai ORDER BY gedung_id, lantai_nomor");
        if ($stmt_lantai) {
            $stmt_lantai->execute();
            $result_lantai = $stmt_lantai->get_result();
            while ($row = $result_lantai->fetch_assoc()) {
                $floors[] = $row;
            }
            $stmt_lantai->close();
        } else {
            error_log("Failed to prepare statement for fetching floors: " . $conn->error);
        }

    } else {
        error_log("Database connection not established or lost in ditmawa_dataEvent.php");
    }
} catch (Exception $e) {
    error_log("Error fetching calendar data: " . $e->getMessage());
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
    <title>Data Event - Event Management Unpar</title>
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
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%);
            min-height: 100vh;
            padding-top: 70px;
            color: #333;
        }

        /* Navbar styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ff8c00;
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
        .navbar-menu li a.active {
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

        /* Calendar styles */
        .calendar-container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
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
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .calendar-header .nav-arrow {
            font-size: 24px;
            color: #ff8c00;
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none; /* Remove underline from anchor */
        }

        .calendar-header .nav-arrow:hover {
            color: #e67e00;
        }

        .filter-section {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .filter-section select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 16px;
            color: #555;
            min-width: 180px;
            background-color: #fff;
            cursor: pointer;
            flex-grow: 1; /* Allow selects to grow */
            max-width: 45%; /* Limit width on larger screens */
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .day-name {
            text-align: center;
            font-weight: 600;
            color: #555;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            background-color: #f0f0f0; /* Keep background for day names */
            margin-bottom: 5px;
        }

        .day-cell {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            min-height: 120px;
            cursor: pointer;
            transition: background-color 0.2s, box-shadow 0.2s;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            overflow: hidden;
        }

        .day-cell:hover {
            background-color: #f0f0f0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .day-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .event-indicator {
            font-size: 12px;
            color: #ff8c00;
            font-weight: 600;
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .empty-day {
            background-color: #ffffff;
            border: 1px dashed #eee;
            cursor: default;
        }

        .empty-day:hover {
            background-color: #ffffff;
            box-shadow: none;
        }

        /* Modal/Popup Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 2000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-header h3 {
            font-size: 24px;
            color: #2c3e50;
        }

        .modal-body p {
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.5;
            color: #555;
        }
        
        .modal-body strong {
            color: #333;
        }

        .modal-body .event-item {
            background-color: #f0f8ff; /* Light blue background */
            border: 1px solid #e0efff;
            border-left: 5px solid #007bff; /* Blue left border */
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .modal-body .event-item:last-child {
            margin-bottom: 0;
        }
        
        .modal-body .event-item h4 {
            font-size: 18px;
            color: #007bff;
            margin-bottom: 5px;
        }
        

        .modal-body .event-item span {
            font-size: 14px;
            color: #666;
        }

        .welcome-section {
            background: linear-gradient(135deg,rgb(2, 73, 43)100%);
            color: white;
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
            .calendar-container {
                margin: 20px auto;
                padding: 20px;
            }
            .calendar-grid {
                gap: 5px;
            }
            .day-cell {
                min-height: 90px;
                padding: 8px;
            }
            .day-number {
                font-size: 16px;
            }
            .event-indicator {
                font-size: 10px;
            }
            .modal-content {
                padding: 20px;
            }
            .modal-header h3 {
                font-size: 20px;
            }
            .modal-body p, .modal-body span {
                font-size: 14px;
            }
            .modal-body .event-item h4 {
                font-size: 16px;
            }
            .filter-section {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .filter-section select {
                width: 90%; /* Adjust width for better fit on small screens */
                max-width: unset; /* Remove max-width restriction */
            }
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
        <li><a href="ditmawa_dashboard.php" >Home</a></li>
        <li><a href="ditmawa_ListKegiatan.php" class="active">Data Event</a></li>
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
        <div class="welcome-section">
            <h2>Kalender Institusional Unpar</h2>
            <p>Pengelolaan event dan kegiatan kemahasiswaan Universitas Katolik Parahyangan</p>
        </div>
<div class="calendar-container">
    
    <div class="calendar-header">
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="nav-arrow" aria-label="Previous Month">&larr;</a>
        <h2><?php echo $date->format('F Y'); ?></h2>
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="nav-arrow" aria-label="Next Month">&rarr;</a>
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
                <div class="day-name">Senin</div>
                <div class="day-name">Selasa</div>
                <div class="day-name">Rabu</div>
                <div class="day-name">Kamis</div>
                <div class="day-name">Jumat</div>
                <div class="day-name">Sabtu</div>
                <div class="day-name">Minggu</div>

        <?php
        // Fill in leading empty days
        for ($i = 1; $i < $firstDayOfWeek; $i++) {
            echo '<div class="day-cell empty-day"></div>';
        }

        // Fill in days of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $fullDate = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
            $hasEvents = isset($initial_events_for_calendar[$day]) && !empty($initial_events_for_calendar[$day]);
            echo '<div class="day-cell ' . ($hasEvents ? 'has-events' : '') . '" data-date="' . $fullDate . '">';
            echo '<div class="day-number">' . $day . '</div>';
            if ($hasEvents) {
                foreach ($initial_events_for_calendar[$day] as $event) {
                    echo '<span class="event-indicator">' . $event['name'] . '</span>';
                }
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="modal-header">
            <h3 id="modalDate"></h3>
        </div>
        <div class="modal-body" id="modalBody">
            </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const eventModal = document.getElementById('eventModal');
        const closeButton = document.querySelector('.close-button');
        const modalDate = document.getElementById('modalDate');
        const modalBody = document.getElementById('modalBody');
        const calendarGrid = document.getElementById('calendarGrid'); // Get the calendar grid container
        const gedungFilter = document.getElementById('gedungFilter');
        const lantaiFilter = document.getElementById('lantaiFilter');

        const allFloors = <?php echo json_encode($floors); ?>;
        const currentMonth = <?php echo $currentMonth; ?>;
        const currentYear = <?php echo $currentYear; ?>;
        const daysInMonth = <?php echo $daysInMonth; ?>;
        const firstDayOfWeek = <?php echo $firstDayOfWeek; ?>; // 1 for Monday, 7 for Sunday

        // Function to update floor filter options based on selected building
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
            // Re-fetch and re-render events after filter change
            fetchEventsAndRenderCalendar();
        }

        // Function to render the calendar grid with events
        function renderCalendar(eventsData) {
            let html = `
                <div class="day-name">Sen</div>
                <div class="day-name">Sel</div>
                <div class="day-name">Rab</div>
                <div class="day-name">Kam</div>
                <div class="day-name">Jum</div>
                <div class="day-name">Sab</div>
                <div class="day-name">Min</div>
            `;

            // Fill in leading empty days
            for (let i = 1; i < firstDayOfWeek; i++) {
                html += '<div class="day-cell empty-day"></div>';
            }

            // Fill in days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const fullDate = `${String(currentYear).padStart(4, '0')}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
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
            addDayCellClickListeners(); // Re-add listeners after rendering
        }

        // Function to fetch events based on current filters and re-render the calendar
        function fetchEventsAndRenderCalendar() {
            const selectedGedung = gedungFilter.value;
            const selectedLantai = lantaiFilter.value;

            // Make an AJAX request to fetch events
            fetch(`fetch_event_data.php?month=${currentMonth}&year=${currentYear}&gedung_id=${selectedGedung}&lantai_id=${selectedLantai}`)
                .then(response => response.json())
                .then(data => {
                    renderCalendar(data.events);
                })
                .catch(error => console.error('Error fetching filtered events:', error));
        }

        // Function to add click listeners to day cells (for opening modal)
        function addDayCellClickListeners() {
            const dayCells = document.querySelectorAll('.day-cell:not(.empty-day)');
            dayCells.forEach(cell => {
                cell.addEventListener('click', function() {
                    const date = this.dataset.date;
                    const selectedGedung = gedungFilter.value;
                    const selectedLantai = lantaiFilter.value;

                    modalDate.textContent = new Date(date).toLocaleDateString('id-ID', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    modalBody.innerHTML = '<p>Loading event details...</p>'; // Loading message
                    eventModal.style.display = 'flex'; // Show modal by setting display to flex

                    // Fetch event details for the clicked date
                    fetch(`fetch_event_details.php?date=${date}&gedung_id=${selectedGedung}&lantai_id=${selectedLantai}`)
                        .then(response => response.json())
                        .then(data => {
                            modalBody.innerHTML = ''; // Clear loading message

                            if (data.length > 0) {
                                data.forEach(event => {
                                    const eventItem = document.createElement('div');
                                    eventItem.classList.add('event-item');
                                    eventItem.innerHTML = `
                                        <h4>${event.name}</h4>
                                        <span><strong>Waktu:</strong> ${event.start_time} - ${event.end_time}</span>
                                        <span><strong>Lokasi:</strong> ${event.gedung}, ${event.lantai}, ${event.ruangan}</span>
                                    `;
                                    modalBody.appendChild(eventItem);
                                });
                            } else {
                                modalBody.innerHTML = '<p>Belum ada kegiatan apapun pada tanggal ini.</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching event details:', error);
                            modalBody.innerHTML = '<p>Tidak ada kegiatan apapun</p>';
                        });
                });
            });
        }

        // Add event listeners for filters
        gedungFilter.addEventListener('change', updateLantaiFilter);
        lantaiFilter.addEventListener('change', fetchEventsAndRenderCalendar); // Re-fetch events when floor changes

        closeButton.addEventListener('click', function() {
            eventModal.style.display = 'none';
        });

        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target == eventModal) {
                eventModal.style.display = 'none';
            }
        });

        // Initial setup on page load
        updateLantaiFilter(); // This will also trigger fetchEventsAndRenderCalendar
        addDayCellClickListeners(); // Attach listeners for initial calendar load
    });
</script>

</body>
</html>