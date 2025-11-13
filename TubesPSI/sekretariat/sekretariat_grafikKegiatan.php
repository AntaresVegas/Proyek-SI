<?php
session_start();

// [DIUBAH] Autentikasi untuk Sekretariat
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'sekretariat') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');
// [DIUBAH] Variabel nama sekretariat
$nama = $_SESSION['nama'] ?? 'Staff Sekretariat';

$startYear = isset($_GET['start_year']) ? (int)$_GET['start_year'] : date('Y');
$endYear = isset($_GET['end_year']) ? (int)$_GET['end_year'] : date('Y');

if ($startYear > $endYear) {
    list($startYear, $endYear) = [$endYear, $startYear];
}

$event_data_per_month = array_fill(1, 12, 0);
$total_events = 0;

try {
    if (isset($conn)) {
        // Query (sama, mengambil event yang disetujui penuh)
        $sql = "
            SELECT 
                MONTH(pengajuan_event_tanggal_mulai) as bulan, 
                COUNT(*) as jumlah_event
            FROM pengajuan_event
            WHERE pengajuan_status_proposal = 'Disetujui'
              AND YEAR(pengajuan_event_tanggal_mulai) BETWEEN ? AND ?
            GROUP BY MONTH(pengajuan_event_tanggal_mulai)
            ORDER BY bulan ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $startYear, $endYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $event_data_per_month[(int)$row['bulan']] = (int)$row['jumlah_event'];
            $total_events += (int)$row['jumlah_event'];
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching Sekretariat chart data: " . $e->getMessage());
}
$conn->close();

$year_range = range(date('Y'), date('Y') - 10);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Event - Sekretariat - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* [DIUBAH] Tema Warna Sekretariat */
        :root {
            --primary-color: #1E88E5; /* Blue */
            --secondary-color: #464E51; /* Dark Grey */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-image: url('../img/backgroundSekretariat.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
            display: flex; flex-direction: column; min-height: 100vh; padding-top: 80px;
        }   
        .main-content { flex-grow: 1; width: 100%; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--secondary-color); width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a:hover, .navbar-menu li a.active { color: var(--primary-color); }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: #FFFFFF; }
        .navbar-right a {color: #FFFFFF;}
        .icon { font-size: 20px; cursor: pointer; }
        .chart-container { max-width: 1000px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 30px; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .title-group { text-align: left; }
        .title-group h1 { font-size: 32px; color: #2c3e50; margin: 0; }
        .title-group h2 { font-size: 24px; color: #555; font-weight: 400; margin: 0; }
        .back-button { background-color: #17a2b8; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s; }
        .back-button:hover { background-color: #138496; }
        .filter-container { display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-group { text-align: center; }
        .filter-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .filter-group select, .filter-group button { padding: 8px 15px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
        .filter-group button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        .total-events { text-align: center; margin-top: 20px; font-size: 20px; font-weight: bold; color: #333; }
        .page-footer { background-color: var(--secondary-color); color: #E0E0E0; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Sekretariat Universitas</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="sekretariat_dashboard.php">Home</a></li> 
        <li><a href="sekretariat_listKegiatan.php">List Event</a></li>
        <li><a href="sekretariat_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="sekretariat_kalender.php">Kalender Peminjaman</a></li>
        </ul>
    <div class="navbar-right">
        <a href="sekretariat_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="chart-container">
        <div class="chart-header">
            <div class="title-group">
                <h1>Grafik Event Universitas</h1>
                <h2>Tahun <?php echo ($startYear === $endYear) ? $startYear : "$startYear - $endYear"; ?></h2>
            </div>
            <a href="sekretariat_listKegiatan.php" class="back-button">
                <i class="fas fa-list-ul"></i> Kembali ke List
            </a>
        </div>

        <form method="GET" class="filter-container">
            <div class="filter-group">
                <label for="start_year">Tahun Mulai</label>
                <select name="start_year" id="start_year">
                    <?php foreach ($year_range as $year) { echo "<option value='$year'" . ($year == $startYear ? ' selected' : '') . ">$year</option>"; } ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="end_year">Tahun Selesai</label>
                <select name="end_year" id="end_year">
                    <?php foreach ($year_range as $year) { echo "<option value='$year'" . ($year == $endYear ? ' selected' : '') . ">$year</option>"; } ?>
                </select>
            </div>
            <div class="filter-group" style="align-self: flex-end;">
                <button type="submit">Tampilkan</button>
            </div>
        </form>

        <div class="chart-wrapper">
            <canvas id="kegiatanChart"></canvas>
        </div>

        <div class="total-events">
            Total Event Disetujui Penuh <?php echo ($startYear === $endYear) ? $startYear : "$startYear - $endYear"; ?> : <?php echo $total_events; ?> Event
        </div>
    </div>
</div>
<script>
    const ctx = document.getElementById('kegiatanChart').getContext('2d');
    const eventData = <?php echo json_encode(array_values($event_data_per_month)); ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [{
                label: 'Jumlah Event Disetujui Penuh',
                data: eventData,
                // [DIUBAH] Warna grafik disesuaikan dengan tema sekretariat
                backgroundColor: 'rgba(30, 136, 229, 0.8)', // --primary-color
                borderColor: 'rgba(30, 136, 229, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) { return ` ${context.dataset.label}: ${context.raw} event`; }
                    }
                }
            }
        }
    });
</script>
</body>
</html>