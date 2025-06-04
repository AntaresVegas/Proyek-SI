<?php
session_start();

// Cek login dan tipe user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

// Ambil nama dan email dari session
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Query gabungan Pengajuan_Event dan Mahasiswa
$query = "
    SELECT 
        m.mahasiswa_nama AS nama,
        m.mahasiswa_email AS email,
        m.mahasiswa_npm AS npm,
        pe.status_persetujuan AS status,
        pe.pengajuan_event_proposal_file AS form_file
    FROM pengjajuan_event pe
    JOIN mahasiswa m ON pe.mahasiwa_id = m.mahasiswa_id
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Data Event - Ditmawa UNPAR</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        /* Reset dan dasar */
        * {
            margin: 0; padding: 0; box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding-top: 70px; /* ruang navbar fixed */
            color: #2c3e50;
        }
        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ff8c00;
            width: 100%;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-family: 'Segoe UI', sans-serif;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            color: #2c3e50;
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
            font-weight: bold;
            font-size: 18px;
            user-select: none;
        }
        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 25px;
        }
        .navbar-menu li a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s;
        }
        .navbar-menu li a:hover {
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

        /* Container utama */
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            padding: 30px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 700;
        }

        /* Tabel */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        thead {
            background: #ff8c00;
            color: #2c3e50;
            font-weight: 600;
            user-select: none;
        }
        th, td {
            padding: 15px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f0f4ff;
        }
        /* Status badge */
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: white;
        }
        .status-setuju {
            background-color: #27ae60; /* hijau */
        }
        .status-tolak {
            background-color: #e74c3c; /* merah */
        }
        .status-pending, .status-menunggu {
            background-color: #f39c12; /* oranye */
            color: #2c3e50;
        }

        /* Link file form */
        .form-link a {
            color: #007bff;
            font-weight: 600;
            text-decoration: none;
        }
        .form-link a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }
            .navbar-menu {
                gap: 15px;
            }
            th, td {
                font-size: 12px;
                padding: 10px 5px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo" />
        <div class="navbar-title">Pengelolaan Event UNPAR</div>
    </div>

    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_dataEvent.php" style="font-weight: bold; border-bottom: 2px solid #007bff;">Data Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>

    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
            <span class="user-name"><?= htmlspecialchars($nama) ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="../logout.php" title="Logout"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <h2>List Data Pengajuan Event Mahasiswa</h2>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>NPM</th>
                <th>Status</th>
                <th>Form</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['npm']) ?></td>
                        <td>
                            <?php
                                $status = strtolower($row['status'] ?? 'pending');
                                $status_class = 'status-pending';
                                $status_label = strtoupper($status);

                                if ($status === 'setuju' || $status === 'approved') {
                                    $status_class = 'status-setuju';
                                    $status_label = 'SETUJU';
                                } elseif ($status === 'tolak' || $status === 'rejected') {
                                    $status_class = 'status-tolak';
                                    $status_label = 'TOLAK';
                                } elseif ($status === 'menunggu' || $status === 'pending') {
                                    $status_class = 'status-pending';
                                    $status_label = 'MENUNGGU';
                                }
                            ?>
                            <span class="status <?= $status_class ?>"><?= $status_label ?></span>
                        </td>
                        <td class="form-link">
                            <?php if (!empty($row['form_file'])): ?>
                                <a href="uploads/<?= urlencode($row['form_file']) ?>" target="_blank" rel="noopener noreferrer">FORM EVENT</a>
                            <?php else: ?>
                                <em>Tidak tersedia</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">Belum ada data pengajuan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
