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

// Array to store report data
$laporan_data = [];

try {
    if (isset($conn) && $conn->ping()) {
        // Query to fetch data from pengajuan_event and join with mahasiswa table
        // We assume 'mahasiswa' table exists with 'mahasiswa_id', 'mahasiswa_nama', 'mahasiswa_npm'
        // And we only show events that have an LPJ submitted and approved (or status is 'Disetujui')
        $stmt = $conn->prepare("
            SELECT 
                pe.pengajuan_namaEvent,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_LPJ,
                m.mahasiswa_nama,
                m.mahasiswa_npm
            FROM 
                pengajuan_event pe
            JOIN 
                mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id
            WHERE 
                pe.pengajuan_statusLPJ = 'Disetujui' AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
            ORDER BY 
                pe.pengajuan_event_tanggal_mulai DESC
        ");

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $laporan_data[] = $row;
                }
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for ditmawa_laporan: " . $conn->error);
            // Optionally, set a user-friendly message
            // $_SESSION['error_message'] = "Gagal memuat data laporan.";
        }
    } else {
        error_log("Database connection not established or lost in ditmawa_laporan.php");
    }
} catch (Exception $e) {
    error_log("Error fetching report data: " . $e->getMessage());
    // Optionally, set a user-friendly message
    // $_SESSION['error_message'] = "Terjadi kesalahan saat mengambil data laporan.";
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
    <title>Laporan Pertanggungjawaban - Event Management Unpar</title>
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
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e4eb 100%); /* Light background from image */
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
            color: #333;
        }

        /* Navbar styles (from your existing dashboard) */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ff8c00; /* Orange background from image */
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
            color: #2c3e50; /* Changed to dark color */
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
            color: #2c3e50; /* Changed to dark color */
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .navbar-menu li a:hover {
            color: #007bff; /* Biru terang untuk hover */
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
            color: #2c3e50; /* Changed to dark color */
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
            color: #007bff; /* Biru terang untuk hover */
        }

        /* Laporan page specific styles */
        .laporan-container {
            max-width: 950px;
            margin: 40px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
        }

        .laporan-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .laporan-header h1 {
            font-size: 32px;
            color: #2c3e50;
        }

        .laporan-table-container {
            overflow-x: auto;
        }

        .laporan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            text-align: left;
        }

        .laporan-table th,
        .laporan-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        .laporan-table th {
            background-color: #f2f2f2;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            font-size: 14px;
        }

        .laporan-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .laporan-table tr:hover {
            background-color: #f1f1f1;
        }

        .download-button {
            background-color: #28a745; /* Green color from image */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .download-button:hover {
            background-color: #218838;
        }

        .no-data-message {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #888;
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
            .laporan-container {
                margin: 20px auto;
                padding: 20px;
            }
            .laporan-table th,
            .laporan-table td {
                padding: 10px;
                font-size: 12px;
            }
            .download-button {
                padding: 6px 12px;
                font-size: 12px;
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
        <li><a href="ditmawa_ListKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_dataEvent.php"     >Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php"class="active">Laporan</a></li>
    </ul>

    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="laporan-container">
    <div class="laporan-header">
        <h1>List Laporan Pertanggungjawaban</h1>
    </div>

    <div class="laporan-table-container">
        <table class="laporan-table">
            <thead>
                <tr>
                    <th>NAMA</th>
                    <th>NPM</th>
                    <th>TANGGAL ACARA</th>
                    <th>NAMA ACARA</th>
                    <th>DOKUMEN LPJ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($laporan_data)): ?>
                    <?php foreach ($laporan_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['mahasiswa_nama']); ?></td>
                            <td><?php echo htmlspecialchars($row['mahasiswa_npm']); ?></td>
                            <td><?php echo htmlspecialchars(date('d F Y', strtotime($row['pengajuan_event_tanggal_mulai']))); ?></td>
                            <td><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></td>
                            <td>
                                <?php if (!empty($row['pengajuan_LPJ'])): ?>
                                    <a href="../uploads/lpj/<?php echo htmlspecialchars($row['pengajuan_LPJ']); ?>" class="download-button" download>
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                <?php else: ?>
                                    Tidak Ada LPJ
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-data-message">Tidak ada data laporan pertanggungjawaban yang tersedia.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>