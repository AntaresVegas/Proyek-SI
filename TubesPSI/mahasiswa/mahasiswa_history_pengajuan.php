<?php
session_start();
include '../config/db_connection.php';

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

$pengajuan_events = [];

if ($user_id !== 'No ID') {
    // Query sudah mengambil pengajuan_status, jadi tidak perlu diubah
    $stmt = $conn->prepare("
        SELECT pengajuan_id, pengajuan_event_tanggal_mulai, pengajuan_namaEvent, pengajuan_status
        FROM pengajuan_event
        WHERE mahasiswa_id = ?
        ORDER BY pengajuan_event_tanggal_mulai DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pengajuan_events[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* General Body and Navbar Styling (Keep from your existing code) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; object-fit: cover; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; font-size: 15px; }
        .navbar-menu li a:hover, .navbar-menu li a.active { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; font-size: 15px; color:rgb(255, 255, 255); }
        .user-name { font-weight: 500; }
        .icon { font-size: 20px; cursor: pointer; }
        
        /* Main Content and Table Styling */
        .container { max-width: 900px; margin: 100px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-radius: 10px; }
        .header h1 { font-size: 24px; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; }
        .data-table th { background-color: #f8f9fa; color: #333; font-weight: 600; text-transform: uppercase; }
        .data-table tr:hover { background-color: #f1f1f1; }

        .detail-button { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 14px; transition: background-color 0.3s; display: inline-block; }
        .detail-button:hover { background-color: #0056b3; }
        .no-data { text-align: center; padding: 20px; color: #777; }
        .download-button {
            background-color: #f44336; /* Red for download */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }

        .download-button:hover {
            background-color: #d32f2f;
        }
        /* === STYLE BARU UNTUK STATUS BADGE === */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: bold;
            color: white;
            text-align: center;
            font-size: 12px;
            text-transform: capitalize;
        }
        .status-badge.disetujui { background-color: #28a745; } /* Hijau */
        .status-badge.ditolak { background-color: #dc3545; } /* Merah */
        .status-badge.diajukan { background-color: #ffc107; color: #333; } /* Kuning */
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
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php" class="active">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="header">
        <h1>History Pengajuan Event</h1>
        <a href="mahasiswa_history.php" class="download-button" style="background-color: #6c757d;">Kembali</a>

    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>TANGGAL</th>
                <th>NAMA EVENT</th>
                <th>STATUS</th>
                <th>ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pengajuan_events)): ?>
                <?php foreach ($pengajuan_events as $event): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date('d F Y', strtotime($event['pengajuan_event_tanggal_mulai']))); ?></td>
                        <td><?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtolower(htmlspecialchars($event['pengajuan_status'])); ?>">
                                <?php echo htmlspecialchars($event['pengajuan_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="mahasiswa_editForm.php?id=<?php echo $event['pengajuan_id']; ?>" class="detail-button">DETAIL</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="no-data">Belum ada pengajuan event.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>