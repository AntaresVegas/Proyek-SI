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
$full_name = 'Nama Lengkap Mahasiswa'; // Default jika tidak ditemukan
$email = 'email@student.unpar.ac.id'; // Default
$npm = 'XXXXXXXXXX'; // Default
$program_studi = 'Informatika'; // Hardcoded untuk saat ini
$fakultas_info = 'Sains'; // Hardcoded untuk saat ini

if ($user_id !== 'No ID') {
    // Ambil data profil mahasiswa dari database
    // Asumsi tabel 'mahasiswa' memiliki kolom: mahasiswa_id, mahasiswa_nama, mahasiswa_email, mahasiswa_npm
    // Sesuaikan nama kolom jika berbeda di database Anda.
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
        /* General Body and Navbar Styling (Keep from your existing code) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e5ec 100%);
            min-height: 100vh;
            background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background:rgb(2, 71, 25);
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
            color:rgb(255, 255, 255);
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
            color:rgb(253, 253, 253);
            font-weight: 500;
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
            color:rgb(255, 255, 255);
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

        /* Profile Card Styling */
        .profile-container {
            max-width: 600px; /* Lebar sesuai gambar */
            margin: 100px auto 30px; /* Jarak dari navbar */
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: visible; /* Penting: agar bayangan gambar profil tidak terpotong */
            padding-bottom: 30px; /* Padding bawah untuk konten */
            text-align: center;
        }

        .profile-header {
            background: linear-gradient(to right, #a1c4fd, #c2e9fb); /* Gradient sesuai gambar */
            color: #333; /* Warna teks pada header */
            padding-top: 20px; /* Padding atas untuk judul */
            padding-bottom: 80px; /* Padding bawah untuk memberikan ruang pada gambar profil */
            margin: 0; /* Hapus margin negatif */
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative; /* Untuk positioning profile-picture */
            z-index: 0; /* Agak di bawah profile-picture */
        }

        .profile-picture {
            width: 120px; /* Ukuran gambar profil */
            height: 120px;
            border-radius: 50%; /* Bentuk lingkaran */
            object-fit: cover;
            border: 4px solid white; /* Border putih */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); /* Shadow pada gambar */
            position: absolute; /* Posisikan absolut di dalam header */
            top: 70px; /* Sesuaikan posisi vertikal agar tepat di tengah header */
            left: 50%;
            transform: translateX(-50%); /* Pusatkan secara horizontal */
            z-index: 1; /* Di atas header */
        }

        .profile-details-top {
            margin-top: 80px; /* Memberikan ruang setelah gambar profil */
            text-align: center;
            padding: 0 20px;
        }

        .profile-details-top h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-details-top p {
            font-size: 16px;
            color: #666;
            margin-bottom: 3px;
        }

        .profile-info-grid {
            display: grid;
            grid-template-columns: auto 1fr; /* Kolom pertama auto, kolom kedua mengisi sisa ruang */
            gap: 15px 20px; /* Jarak antar baris dan kolom */
            margin-top: 30px;
            padding: 0 40px; /* Padding samping untuk grid agar tidak terlalu mepet */
            text-align: left; /* Teks dalam grid rata kiri */
        }

        .profile-info-grid strong {
            color: #333;
            font-weight: 600;
            white-space: nowrap; /* Mencegah label "Full Name" pecah baris */
        }

        .profile-info-grid span {
            color: #555;
            word-break: break-word; /* Memecah kata jika terlalu panjang */
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
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

<div class="profile-container">
    <div class="profile-header">
        Profil Mahasiswa
        <img src="../img/mahasiswa.png" alt="Profil Mahasiswa" class="profile-picture">
    </div>

    <div class="profile-details-top">
        <h2><?php echo $full_name; ?></h2>
        <p>S.I. | <?php echo $npm; ?></p>
        <p><?php echo $program_studi; ?></p>
        <p><?php echo $fakultas_info; ?></p>
    </div>

    <div class="profile-info-grid">
        <strong>Full Name</strong> <span><?php echo $full_name; ?></span>
        <strong>Email</strong> <span><?php echo $email; ?></span>
        <strong>NPM</strong> <span><?php echo $npm; ?></span>
        </div>
</div>

</body>
</html>