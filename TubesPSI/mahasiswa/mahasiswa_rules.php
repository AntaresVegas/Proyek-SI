<?php
session_start();

// Check if user is logged in and is a mahasiswa
// For this "Rules" page, we can decide if login is strictly required
// If login is required, uncomment the following lines:
/*
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}
*/

// Set user details for navbar, or default if not logged in
$nama = $_SESSION['nama'] ?? 'Pengunjung';
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ketentuan Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9; /* Light background for content page */
            min-height: 100vh;
            color: #333;
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
        .navbar-menu li a:hover,
        .navbar-menu li a.active { /* Added active class style */
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

        .container {
            max-width: 900px; /* Adjusted width for content page */
            margin: 80px auto 30px; /* Top margin for fixed navbar */
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 0; /* Remove padding to let header and content manage it */
            overflow: hidden;
        }

        .page-header {
            background: #2c3e50; /* Dark header style */
            color: white;
            padding: 20px 30px;
        }

        .page-header h1 {
            font-size: 26px;
            font-weight: 600;
        }

        .main-content {
            padding: 25px 30px;
            line-height: 1.7;
        }

        .main-content h2 {
            font-size: 22px;
            color: #2c3e50;
            margin-top: 20px;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .main-content h2:first-child {
            margin-top: 0;
        }

        .main-content h3 {
            font-size: 18px;
            color: #1a5276; /* Sub-heading color */
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .main-content p, .main-content ul {
            margin-bottom: 15px;
            color: #555;
        }

        .main-content ul {
            list-style-position: outside;
            padding-left: 20px;
        }

        .main-content li {
            margin-bottom: 8px;
        }

        .main-content strong {
            color: #333;
        }

        .main-content .note {
            background-color: #eef7ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 0.95em;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
            }
            .navbar-menu {
                display: none; /* Simple hide for mobile, can be toggled with JS if needed */
            }
             .page-header h1 {
                font-size: 22px;
            }
            .main-content {
                padding: 20px;
            }
             .main-content h2 {
                font-size: 20px;
            }
             .main-content h3 {
                font-size: 17px;
            }
        }

    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo"> <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li> 
        <li><a href="mahasiswa_rules.php" class="active">Rules</a></li>
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

<div class="container">
    <div class="page-header">
        <h1>Ketentuan Pengajuan Event</h1>
    </div>

    <div class="main-content">
        <h2>A. Peraturan Pengajuan Event</h2>

        <h3>1. Persyaratan Pengajuan</h3>
        <p><strong>Pemohon:</strong> Pengajuan event hanya dapat dilakukan oleh mahasiswa, dosen, atau staf UNPAR.</p>
        <p><strong>Dokumen yang Diperlukan:</strong></p>
        <ul>
            <li>Surat permohonan penyelenggaraan kegiatan di lingkungan UNPAR yang ditujukan kepada Sekretaris Universitas.</li>
            <li>Rundown kegiatan.</li>
            <li>Surat Rekomendasi berdasarkan jenis kegiatan:
                <ul>
                    <li>Kegiatan Kemahasiswaan: dikeluarkan oleh Direktorat Kemahasiswaan.</li>
                    <li>Kegiatan Akademik: dikeluarkan oleh Jurusan.</li>
                    <li>Kegiatan Kepanitiaan: dikeluarkan oleh unit penanggung jawab kegiatan.</li>
                </ul>
            </li>
        </ul>

        <h3>2. Batas Waktu Pengajuan</h3>
        <p><strong>Minimal:</strong> Pengajuan harus dilakukan minimal 7 hari sebelum tanggal pelaksanaan.</p>
        <p><strong>Keterlambatan:</strong> Keterlambatan pengajuan dapat menyebabkan penolakan atau keterlambatan persetujuan.</p>

        <h3>3. Izin dan Kepatuhan</h3>
        <p><strong>Peraturan:</strong> Semua event harus mematuhi peraturan akademik, etika, dan tata tertib UNPAR.</p>
        <p><strong>Pihak Eksternal:</strong> Event yang melibatkan pihak eksternal harus mendapatkan izin tambahan dari pihak terkait.</p>

        <h3>4. Penggunaan Fasilitas</h3>
        <p><strong>Konfirmasi Ruangan:</strong> Sebelum mengajukan form, panitia wajib mengonfirmasi ketersediaan ruangan dengan Administrasi Sarana dan Prasarana (ASP).</p>
        <p><strong>Kesesuaian:</strong> Penggunaan fasilitas kampus harus sesuai dengan jadwal dan kapasitas yang telah ditentukan.</p>
        <p><strong>Tanggung Jawab:</strong> Panitia bertanggung jawab atas kebersihan, ketertiban, dan keamanan area yang digunakan.</p>
        <p><strong>Persiapan:</strong> Jika acara memerlukan persiapan lebih, panitia harus menambah hari peminjaman ruangan sebelum acara dimulai.</p>
        <p><strong>Pembersihan:</strong> Jika acara selesai setelah jam 15:00, panitia harus meminjam ruangan tersebut untuk membersihkan area.</p>

        <h3>5. Keamanan dan Keselamatan</h3>
        <p><strong>Rencana Keamanan:</strong> Setiap event yang melibatkan kerumunan besar harus memiliki rencana keamanan yang jelas.</p>
        <p><strong>Larangan:</strong> Dilarang membawa atau menggunakan barang terlarang seperti minuman beralkohol, narkotika, dan senjata tajam dalam event.</p>

        <h3>6. Pembatalan atau Perubahan</h3>
        <p><strong>Pemberitahuan:</strong> Panitia wajib memberitahukan pihak kampus jika ada perubahan atau pembatalan event.</p>
        <p><strong>Kewenangan Universitas:</strong> Pihak universitas berhak membatalkan event jika ditemukan pelanggaran peraturan.</p>
    </div>
</div>

</body>
</html>