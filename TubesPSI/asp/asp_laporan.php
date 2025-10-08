<?php
session_start();
// Sesuaikan path jika perlu
require_once(__DIR__ . '/../config/db_connection.php');

// 1. Cek otentikasi pengguna sebagai ASP
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff ASP';

// 2. Fungsionalitas aksi DIHAPUS, backend hanya mengambil data.
$laporan_data = [];
try {
    $sql = "
        SELECT 
            pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_LPJ, pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ, m.mahasiswa_nama, m.mahasiswa_npm
        FROM pengajuan_event pe
        JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id
        WHERE pe.pengaju_tipe = 'mahasiswa' 
          AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY pe.pengajuan_event_tanggal_selesai DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $laporan_data[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching report data for ASP: " . $e->getMessage());
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Arsip Laporan - Event Management Unpar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [PERUBAHAN] Mengadopsi CSS modern dengan tema warna ASP */
        :root {
            --primary-color: #0A2342; /* Warna Biru ASP */
            --hover-color: #FFD700;   /* Warna Emas untuk Hover */
            --text-dark: #2c3e50;
            --text-light: #8895a7;
            --border-color: #e5e7eb;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background-image: url('../img/backgroundASP.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        
        /* Navbar & Footer dengan tema ASP */
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: sticky; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-right { gap: 15px; color: var(--white); }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: var(--white); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu a { text-decoration: none; color: #E0E0E0; font-weight: 500; transition: color 0.3s; }
        .navbar-menu a.active, .navbar-menu a:hover { color: var(--hover-color); }
        .icon { font-size: 20px; color: white; }
        a { text-decoration: none; }

        .page-footer { background-color: var(--primary-color); color: #E0E0E0; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: var(--white); }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }

        /* Main Content & Container */
        .main-content { flex-grow: 1; padding: 30px 0; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .page-header { margin-bottom: 30px; padding: 1.5rem; text-align: center; background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 12px; }
        .page-header h1 { font-size: 2.25rem; font-weight: 700; color: var(--text-dark); }
        .page-header p { font-size: 1.1rem; color: #5a6a7a; margin-top: 5px;}

        /* Kartu Laporan dengan Label */
        .laporan-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-content { padding: 1.5rem; display: grid; gap: 1.25rem; }
        .info-item { display: flex; flex-direction: column; gap: 0.25rem; }
        .info-label { font-size: 0.8rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; }
        .info-value { font-size: 1rem; font-weight: 500; }
        .info-value.event-title { font-size: 1.35rem; font-weight: 700; color: var(--primary-color); } /* Judul acara pakai warna biru ASP */
        
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-weight: 600; font-size: 0.75rem; text-transform: capitalize; display: inline-block; }
        .status-badge.menunggu-persetujuan { background-color: #fef3c7; color: #92400e; }
        .status-badge.ditolak { background-color: #fee2e2; color: #991b1b; }
        .status-badge.disetujui { background-color: #d1fae5; color: #065f46; }
        
        .keterangan-block { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 6px; font-style: italic; color: #b91c1c;}
        .download-button { background-color: #3b82f6; color: var(--white); padding: 0.6rem 1.2rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: background-color 0.2s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .download-button:hover { background-color: #2563eb; }
        
        /* Empty State */
        .no-data-message { text-align: center; padding: 3rem; background: rgba(255,255,255,0.9); border-radius: 12px; border: 1px dashed var(--border-color); }
        .no-data-message i { font-size: 3rem; color: var(--text-light); margin-bottom: 1rem; }
        .no-data-message p { font-size: 1.1rem; color: var(--text-light); }
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
        <li><a href="asp_kalender.php">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php" class="active">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <header class="page-header">
            <h1>Arsip Laporan Pertanggungjawaban</h1>
            <p>Halaman ini menampilkan semua laporan yang telah diproses oleh Ditmawa.</p>
        </header>
        
        <div class="laporan-list">
            <?php if (!empty($laporan_data)): ?>
                <?php foreach ($laporan_data as $row):
                    $status_class = str_replace(' ', '-', strtolower(htmlspecialchars($row['pengajuan_statusLPJ'])));
                ?>
                    <div class="laporan-card">
                        <div class="card-content">
                            <div class="info-item">
                                <span class="info-label">Nama Acara</span>
                                <p class="info-value event-title"><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></p>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Nama Mahasiswa</span>
                                <p class="info-value"><?php echo htmlspecialchars($row['mahasiswa_nama']); ?> (<?php echo htmlspecialchars($row['mahasiswa_npm']); ?>)</p>
                            </div>

                            <div class="info-item">
                                <span class="info-label">Status LPJ</span>
                                <div class="info-value">
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['pengajuan_statusLPJ']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($row['pengajuan_statusLPJ'] == 'Ditolak' && !empty($row['pengajuan_komentarLPJ'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Keterangan dari Ditmawa</span>
                                    <div class="info-value keterangan-block">
                                        <?php echo htmlspecialchars($row['pengajuan_komentarLPJ']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="info-item">
                                <span class="info-label">Dokumen LPJ</span>
                                <div class="info-value">
                                    <?php if (!empty($row['pengajuan_LPJ'])): ?>
                                         <a href="../<?php echo htmlspecialchars($row['pengajuan_LPJ']); ?>" class="download-button" download>
                                             <i class="fas fa-download"></i> Download
                                         </a>
                                    <?php else: ?>
                                        <span>- Tidak ada dokumen -</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data-message">
                    <i class="fas fa-folder-open"></i>
                    <p>Belum ada LPJ yang diunggah oleh mahasiswa.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px; color: var(--white);">ADMINISTRASI SARANA & PRASARANA</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                <li><i class="fas fa-envelope"></i> asp@unpar.ac.id</li>
            </ul>
        </div>
    </div>
</footer>
</body>
</html>