<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Mahasiswa';

$buildings = [];
try {
    // [DIUBAH] Kueri ini menggabungkan tabel `gedung` dengan `fasilitas_gedung`
    $sql = "
        SELECT 
            g.gedung_id, 
            g.gedung_nama, 
            fg.deskripsi, 
            fg.foto_utama
        FROM gedung g
        LEFT JOIN fasilitas_gedung fg ON g.gedung_id = fg.gedung_id
        ORDER BY LENGTH(g.gedung_nama), g.gedung_nama
    ";
    $result_gedung = $conn->query($sql);
    while ($row = $result_gedung->fetch_assoc()) {
        $buildings[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching building data: " . $e->getMessage());
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Fasilitas Kampus - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [SALIN SEMUA CSS DARI HALAMAN MAHASISWA_EVENT.PHP ANDA] */
        /* ... (navbar, footer, body, dll) ... */
        
        /* [TAMBAHKAN CSS BARU INI] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; min-height: 100vh; padding-top: 70px; background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center; background-attachment: fixed;}
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(253, 253, 253); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; cursor: pointer; }
        
        .page-header { background: linear-gradient(135deg, rgb(2, 73, 43) 0%, rgb(2, 71, 25) 100%); color: white; padding: 25px; margin: 20px auto; max-width: 1100px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .page-header h1 { margin-bottom: 10px; font-size: 28px; }
        .page-header p { opacity: 0.9; font-size: 16px; }

        .facilities-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); /* Grid responsif */
            gap: 25px;
        }

        .building-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .building-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .building-card-image {
            width: 100%;
            height: 220px;
            background-color: #eee;
            background-size: cover;
            background-position: center;
        }
        .building-card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .building-card-content h3 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }
        .building-card-content p {
            font-size: 15px;
            color: #666;
            line-height: 1.6;
            flex-grow: 1;
            margin-bottom: 20px;
            /* [BARU] Batasi deskripsi hanya 3 baris */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .building-card-link {
            display: inline-block;
            text-decoration: none;
            background-color: rgb(2, 71, 25);
            color: white;
            padding: 10px 18px;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
            text-align: center;
        }
        .building-card-link:hover {
            background-color: rgb(1, 46, 16);
        }
        
        .page-footer { background-color: rgb(2, 71, 25); color: #e9ecef; padding: 40px 0; margin-top: 40px; }
        /* ... (Salin CSS Footer dari file lain) ... */
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
        <li><a href="mahasiswa_fasilitas.php" class="active">Fasilitas</a></li> 
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_kalender_gabungan.php">Kalender Gabungan</a></li> 
        <li><a href="mahasiswa_event.php">Kalender Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="page-header">
    <h1>Fasilitas Kampus</h1>
    <p>Telusuri berbagai gedung dan ruangan yang tersedia di lingkungan UNPAR.</p>
</div>

<div class="facilities-container">
    <?php if (empty($buildings)): ?>
        <p style="text-align: center; grid-column: 1 / -1; font-size: 18px;">Data fasilitas gedung belum tersedia.</p>
    <?php else: ?>
        <?php foreach ($buildings as $building): ?>
            <div class="building-card">
                <?php 
                    // Tampilkan foto utama, atau foto placeholder jika kosong
                    $foto_utama = !empty($building['foto_utama']) ? htmlspecialchars($building['foto_utama']) : '../img/placeholder_gedung.png'; 
                ?>
                <div class="building-card-image" style="background-image: url('<?php echo $foto_utama; ?>');"></div>
                <div class="building-card-content">
                    <h3><?php echo htmlspecialchars($building['gedung_nama']); ?></h3>
                    <p>
                        <?php 
                            // Tampilkan deskripsi, atau deskripsi default jika kosong
                            $deskripsi = !empty($building['deskripsi']) ? $building['deskripsi'] : 'Deskripsi untuk gedung ini belum tersedia.';
                            echo htmlspecialchars(strip_tags($deskripsi)); // strip_tags untuk keamanan
                        ?>
                    </p>
                    <a href="mahasiswa_detail_gedung.php?gedung_id=<?php echo $building['gedung_id']; ?>" class="building-card-link">
                        Lihat Detail Ruangan <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer class="page-footer">
    </footer>

</body>
</html>