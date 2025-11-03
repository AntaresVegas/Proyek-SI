<?php
session_start();
require_once('../config/db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

$asp_id = $_SESSION['user_id'];

// Ambil data terbaru untuk ditampilkan
$stmt = $conn->prepare("SELECT asp_nama, asp_email, asp_NIK FROM asp WHERE asp_id = ?");
$stmt->bind_param("i", $asp_id);
$stmt->execute();
$result = $stmt->get_result();
$asp_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$asp_data) {
    // Jika data tidak ditemukan, redirect atau tampilkan error
    die("Data profil tidak ditemukan.");
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - ASP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-image: url('../img/backgroundASP.jpeg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed; 
            padding-top: 80px;
        }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #0A2342; width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a:hover { color: #FFD700; }
        .navbar-right { color: #FFFFFF; }
        .icon { font-size: 20px; }
        .container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .profile-card { background: rgba(255, 255, 255, 0.98); border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header { font-size: 1.8em; margin-bottom: 25px; color: #333; display: flex; align-items: center; gap: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        .profile-details dt { font-weight: 600; color: #555; font-size: 0.9em; margin-bottom: 5px; }
        .profile-details dd { color: #333; font-size: 1.1em; margin-left: 0; margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 8px; }
    </style>
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan Sarana & Prasarana</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="asp_dashboard.php">Home</a></li>
        <li><a href="asp_listKegiatan.php">Persetujuan Event</a></li>
        <li><a href="asp_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="asp_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="asp_kalender.php">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="text-decoration: none; color: #FFD700; font-weight: bold;"><span class="user-name"><?php echo htmlspecialchars($asp_data['asp_nama']); ?></span><i class="fas fa-user-circle icon" style="margin-left:10px;"></i></a>
        <a href="logout.php" style="color: white;"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="profile-card">
        <h2 class="card-header"><i class="fas fa-user-circle"></i> Profil Saya</h2>
        <dl class="profile-details">
            <dt>Nama Lengkap</dt>
            <dd><?php echo htmlspecialchars($asp_data['asp_nama']); ?></dd>

            <dt>Alamat Email</dt>
            <dd><?php echo htmlspecialchars($asp_data['asp_email']); ?></dd>

            <dt>Nomor Induk Kependudukan (NIK)</dt>
            <dd><?php echo htmlspecialchars($asp_data['asp_NIK']); ?></dd>
        </dl>
    </div>
</div>

</body>
</html>