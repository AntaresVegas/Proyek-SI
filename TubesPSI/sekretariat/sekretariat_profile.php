<?php
session_start();
require_once('../config/db_connection.php');

// [DIUBAH] Cek session untuk sekretariat
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'sekretariat') {
    header("Location: ../index.php");
    exit();
}

// [DIUBAH] Variabel ID untuk sekretariat
$sekuniv_id = $_SESSION['user_id'];

// [DIUBAH] Ambil data dari tabel sekretariat_universitas
$stmt = $conn->prepare("SELECT sekuniv_nama, sekuniv_email, sekuniv_NIK FROM sekretariat_universitas WHERE sekuniv_id = ?");
$stmt->bind_param("i", $sekuniv_id);
$stmt->execute();
$result = $stmt->get_result();
$sekretariat_data = $result->fetch_assoc(); // [DIUBAH] Nama variabel
$stmt->close();
$conn->close();

if (!$sekretariat_data) {
    die("Data profil tidak ditemukan.");
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sekretariat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [DIUBAH] Style dasar disamakan, mengganti warna dan background */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-image: url('../img/backgroundSekretariat.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed; 
            padding-top: 80px;
        }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #464E51; width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a:hover { color: #1E88E5; }
        .navbar-right { color: #FFFFFF; }
        .icon { font-size: 20px; }
        .container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .profile-card { background: rgba(255, 255, 255, 0.98); border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .card-header { font-size: 1.8em; margin-bottom: 25px; color: #333; display: flex; align-items: center; gap: 15px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        .profile-details dt { font-weight: 600; color: #555; font-size: 0.9em; margin-bottom: 5px; }
        .profile-details dd { color: #333; font-size: 1.1em; margin-left: 0; margin-bottom: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 8px; }
        .page-footer { background-color: #464E51; color: #E0E0E0; padding: 40px 0; margin-top: auto; }        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #FFFFFF; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .pagination-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 1.5rem 0; margin-top: 20px; border-top: 1px solid #e5e7eb; }
        .pagination-info { color: #8895a7; font-size: 0.9rem; }
        .pagination-links { display: flex; gap: 5px; }
        .page-link { text-decoration: none; padding: 0.5rem 1rem; border: 1px solid #e5e7eb; background: #ffffff; color: var(--secondary-color); border-radius: 8px; font-weight: 500; transition: background 0.2s, color 0.2s; }
        .page-link:hover { background-color: #f8f9fa; border-color: #dee2e6; }
        .page-link.active { background-color: var(--secondary-color); color: #ffffff; border-color: var(--secondary-color); }
        .page-link.disabled { color: #8895a7; pointer-events: none; background-color: #f9fafb; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #FFFFFF; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: var(--primary-color); }
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
        <a href="sekretariat_profile.php" style="text-decoration: none; color: #1E88E5; font-weight: bold;"><span class="user-name"><?php echo htmlspecialchars($sekretariat_data['sekuniv_nama']); ?></span><i class="fas fa-user-circle icon" style="margin-left:10px;"></i></a>
        <a href="logout.php" style="color: white;"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="profile-card">
        <h2 class="card-header"><i class="fas fa-user-circle"></i> Profil Saya</h2>
        <dl class="profile-details">
            <dt>Nama Lengkap</dt>
            <dd><?php echo htmlspecialchars($sekretariat_data['sekuniv_nama']); ?></dd>

            <dt>Alamat Email</dt>
            <dd><?php echo htmlspecialchars($sekretariat_data['sekuniv_email']); ?></dd>

            <dt>Nomor Induk Kependudukan (NIK)</dt>
            <dd><?php echo htmlspecialchars($sekretariat_data['sekuniv_NIK']); ?></dd>
        </dl>
    </div>
</div>
    <footer class="page-footer">
        <div class="footer-container">
            <div class="footer-left">
                <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
                <div>
                    <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                    <h3 style="font-weight: bold; margin-top: 5px;">SEKRETARIAT UNIVERSITAS</h3>
                </div>
            </div>
            <div class="footer-right">
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                    <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                    <li><i class="fas fa-envelope"></i> rektorat@unpar.ac.id</li>
                </ul>
                <div class="social-icons">
                    <a href="https://www.facebook.com/unparofficial" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/unparofficial/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/channel/UCeIZdD9ul6JGpkSNM0oxcBw/featured" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="https://www.tiktok.com/@unparofficial" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>