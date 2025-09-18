<?php
session_start();
include '../config/db_connection.php'; // Pastikan path ini benar

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Initialize profile data
$full_name = 'Nama Lengkap Mahasiswa'; // Default
$email = 'email@student.unpar.ac.id'; // Default
$npm = 'XXXXXXXXXX'; // Default
// TODO: Ambil data Program Studi dan Fakultas dari database jika tersedia
$program_studi = 'Teknik Informatika'; // Ganti dengan data dinamis
$fakultas_info = 'Fakultas Teknologi Industri'; // Ganti dengan data dinamis

if ($user_id !== 'No ID') {
    $stmt = $conn->prepare("SELECT mahasiswa_nama, mahasiswa_email, mahasiswa_npm FROM mahasiswa WHERE mahasiswa_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $profile_data = $result->fetch_assoc();
        $full_name = htmlspecialchars($profile_data['mahasiswa_nama']);
        $email = htmlspecialchars($profile_data['mahasiswa_email']);
        $npm = htmlspecialchars($profile_data['mahasiswa_npm']);
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
    <title>Profil Mahasiswa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Styling Navbar (Sama seperti sebelumnya) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            min-height: 100vh;
            background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;
        }
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            background:rgb(2, 71, 25); width: 100%; padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed;
            top: 0; left: 0; right: 0; z-index: 1000;
        }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; object-fit: cover; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; font-size: 15px; transition: color 0.3s; }
        .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; font-size: 15px; color:rgb(255, 255, 255); }
        .user-name { font-weight: 500; }
        .icon { font-size: 20px; cursor: pointer; transition: color 0.3s; }
        .icon:hover { color: #007bff; }

        /* ==================== DESAIN BARU PROFIL ==================== */
        .profile-card {
            max-width: 550px;
            margin: 120px auto 40px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            padding: 30px;
            text-align: center;
        }

        /* --- Bagian Identitas Utama --- */
        .profile-identity {
            margin-bottom: 25px;
        }
        .profile-pic {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e9ecef;
            margin-bottom: 15px;
        }
        .profile-name {
            font-size: 26px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .profile-npm {
            font-size: 16px;
            color: #7f8c8d;
            margin-top: 5px;
        }

        /* --- Garis Pemisah --- */
        hr {
            border: none;
            height: 1px;
            background-color: #e9ecef;
            margin-bottom: 25px;
        }

        /* --- Detail Informasi dengan Ikon --- */
        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 20px; /* Jarak antar item info */
            text-align: left;
        }
        .info-item {
            display: flex;
            align-items: center;
        }
        .info-icon {
            font-size: 18px;
            color: #3498db;
            width: 40px; /* Lebar tetap untuk ikon agar rapi */
            text-align: center;
        }
        .info-text strong {
            display: block;
            font-size: 13px;
            color: #95a5a6;
            font-weight: 500;
            text-transform: uppercase;
        }
        .info-text span {
            font-size: 16px;
            color: #34495e;
        }

        /* --- Bagian Tombol Aksi --- */
        .profile-actions {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .btn-action {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        .btn-action:hover {
            background-color: #2980b9;
        }
        .btn-action.secondary {
            background-color: #ecf0f1;
            color: #34495e;
        }
        .btn-action.secondary:hover {
            background-color: #bdc3c7;
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
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="profile-card">
    <div class="profile-identity">
        <img src="../img/mahasiswa.png" alt="Foto Profil" class="profile-pic">
        <h2 class="profile-name"><?php echo $full_name; ?></h2>
        <p class="profile-npm">Mahasiswa Aktif</p>
    </div>

    <hr>

    <div class="profile-info">
        <div class="info-item">
            <i class="fas fa-id-card info-icon"></i>
            <div class="info-text">
                <strong>NPM</strong>
                <span><?php echo $npm; ?></span>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-envelope info-icon"></i>
            <div class="info-text">
                <strong>Email</strong>
                <span><?php echo $email; ?></span>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-graduation-cap info-icon"></i>
            <div class="info-text">
                <strong>Program Studi</strong>
                <span><?php echo $program_studi; ?></span>
            </div>
        </div>
        <div class="info-item">
            <i class="fas fa-university info-icon"></i>
            <div class="info-text">
                <strong>Fakultas</strong>
                <span><?php echo $fakultas_info; ?></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>