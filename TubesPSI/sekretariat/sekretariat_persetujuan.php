<?php
session_start();

// Cek session untuk sekretariat
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'sekretariat') {
    header("Location: ../index.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');

$nama_sekretariat = $_SESSION['nama'] ?? 'Staff Sekretariat';
$error_message = '';
$event_data = null;

$pengajuan_id = $_GET['id'] ?? null;
if ($pengajuan_id) {
    // [MODIFIKASI] Query ditambah 'pe.surat_izin_kegiatan_file'
    $sql = "SELECT pe.*,
                   pe.surat_izin_kegiatan_file, -- <--- DITAMBAHKAN
                   CASE 
                       WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_nama
                       WHEN pe.pengaju_tipe = 'ditmawa' THEN d.ditmawa_nama
                       ELSE 'N/A'
                   END AS nama_pengaju,
                   CASE 
                       WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_email
                       WHEN pe.pengaju_tipe = 'ditmawa' THEN d.ditmawa_email
                       ELSE 'N/A'
                   END AS email_pengaju,
                   CASE 
                       WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_npm
                       WHEN pe.pengaju_tipe = 'ditmawa' THEN d.ditmawa_NIK
                       ELSE 'N/A'
                   END AS identitas_pengaju,
                   CASE 
                       WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_jurusan
                       WHEN pe.pengaju_tipe = 'ditmawa' THEN 'Direktorat Kemahasiswaan'
                       ELSE 'N/A'
                   END AS unit_pengaju,
                   GROUP_CONCAT(DISTINCT r.ruangan_nama SEPARATOR ', ') AS nama_ruangan,
                   GROUP_CONCAT(DISTINCT g.gedung_nama SEPARATOR ', ') AS nama_gedung
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
            LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
            LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
            LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
            LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
            WHERE pe.pengajuan_id = ? GROUP BY pe.pengajuan_id";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $pengajuan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event_data = ($result->num_rows > 0) ? $result->fetch_assoc() : null;
        if (!$event_data) $error_message = "Data pengajuan event tidak ditemukan.";
        $stmt->close();
    }
} else {
    $error_message = "ID Pengajuan tidak valid.";
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detail Event - Sekretariat - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1E88E5; /* Blue */
            --secondary-color: #464E51; /* Dark Grey */
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-image: url('../img/backgroundSekretariat.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed; 
            min-height: 100%; /* [MODIFIKASI] Diubah dari 100vh ke 100% */
            padding-top: 80px; 
            display: flex; /* [MODIFIKASI] Ditambahkan flex */
            flex-direction: column; /* [MODIFIKASI] Ditambahkan flex */
        }
        .main-content { /* [BARU] Wrapper untuk konten utama */
            flex-grow: 1;
        }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--secondary-color); width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: var(--primary-color); } 
        .navbar-right { display: flex; align-items: center; gap: 15px; color: #FFFFFF; }
        .navbar-right a {color: #FFFFFF;}
        .icon { font-size: 20px; }
        .form-container { max-width: 800px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); padding: 30px 40px; }
        .form-header h1 { font-size: 28px; color: #2c3e50; text-align: center; margin-bottom: 30px;}
        .detail-grid { display: grid; grid-template-columns: 220px 1fr; gap: 15px 20px; margin-bottom: 30px; }
        .detail-grid dt { font-weight: 600; color: #555; }
        .detail-grid dd { color: #333; display: flex; align-items: center; }
        .download-link { text-decoration: none; color: #007bff; font-weight: 500; }
        .download-link i { margin-right: 5px; }
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-weight: bold; font-size: 14px; display: inline-block;}
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
        hr { border: 0; border-top: 1px solid #e0e0e0; margin: 30px 0; }
        h2 { font-size: 20px; color: #333; margin-bottom: 15px; }
        
        /* [BARU] CSS Untuk Footer */
        .page-footer { background-color: var(--secondary-color); color: #E0E0E0; padding: 40px 0; margin-top: auto; /* Mendorong footer ke bawah */ }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #FFFFFF; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
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
        <li><a href="sekretariat_listKegiatan.php" class="active">List Event</a></li>
        <li><a href="sekretariat_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="sekretariat_kalender.php">Kalender Peminjaman</a></li>
    </ul>
    <div class="navbar-right">
        <a href="sekretariat_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama_sekretariat); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="form-container">
        <div class="form-header"><h1>Detail Pengajuan Event</h1></div>
        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif ($event_data): ?>
            
            <dl class="detail-grid">
                <dt>Nama Pengaju</dt>
                <dd><?php echo htmlspecialchars($event_data['nama_pengaju'] ?? 'N/A'); ?></dd>
                <dt>Email</dt>
                <dd><?php echo htmlspecialchars($event_data['email_pengaju'] ?? 'N/A'); ?></dd>
                <dt>NPM / NIK</dt>
                <dd><?php echo htmlspecialchars($event_data['identitas_pengaju'] ?? 'N/A'); ?></dd>
                <dt>Jurusan / Unit</dt>
                <dd><?php echo htmlspecialchars($event_data['unit_pengaju'] ?? 'N/A'); ?></dd>
                
                <dt>Nama Event</dt>
                <dd><?php echo htmlspecialchars($event_data['pengajuan_namaEvent']); ?></dd>
                <dt>Lokasi Diajukan</dt>
                <dd>
                    <?php
                        $lokasi = 'Belum ada ruangan yang dipilih.';
                        if (!empty($event_data['nama_gedung']) || !empty($event_data['nama_ruangan'])) {
                            $nama_gedung = $event_data['nama_gedung'] ?? 'Gedung tidak spesifik';
                            $nama_ruangan = $event_data['nama_ruangan'] ?? 'Ruangan tidak spesifik';
                            $lokasi = htmlspecialchars($nama_gedung . ' (' . $nama_ruangan . ')');
                        }
                        echo $lokasi;
                    ?>
                </dd>
                <dt>Waktu Acara</dt>
                <dd>
                    <?php echo htmlspecialchars(date('d M Y', strtotime($event_data['pengajuan_event_tanggal_mulai']))) . " - " . htmlspecialchars(date('d M Y', strtotime($event_data['pengajuan_event_tanggal_selesai']))); ?>
                </dd>

                <dt>Rundown Acara</dt>
                <dd>
                    <a href="../<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>" class="download-link" download>
                        <i class="fas fa-download"></i> Unduh Rundown
                    </a>
                </dd>
                <dt>Proposal Kegiatan</dt>
                <dd>
                    <a href="../<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>" class="download-link" download>
                        <i class="fas fa-download"></i> Unduh Proposal
                    </a>
                </dd>
            </dl>
            
            <hr><h2>Detail Status Persetujuan</h2>
            <dl class="detail-grid">
                <dt>Status Ditmawa</dt>
                <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_ditmawa'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_ditmawa']); ?></span></dd>
                <dt>Komentar Ditmawa</dt>
                <dd><?php echo !empty($event_data['komentar_ditmawa']) ? htmlspecialchars($event_data['komentar_ditmawa']) : '<em>Tidak ada komentar.</em>'; ?></dd>
                
                <dt>Status ASP</dt>
                <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_asp'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_asp']); ?></span></dd>
                <dt>Komentar ASP</dt>
                <dd><?php echo !empty($event_data['komentar_asp']) ? htmlspecialchars($event_data['komentar_asp']) : '<em>Tidak ada komentar.</em>'; ?></dd>
                
                <dt>Status Proposal Final</dt>
                <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_proposal'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_proposal']); ?></span></dd>
            </dl>

            <hr><h2>Surat Izin Kegiatan (SIK)</h2>
            <dl class="detail-grid">
                <dt>Status SIK</dt>
                <?php if (!empty($event_data['surat_izin_kegiatan_file'])): ?>
                    <dd>
                        <a href="../<?php echo htmlspecialchars($event_data['surat_izin_kegiatan_file']); ?>" class="download-link" target="_blank">
                            <i class="fas fa-check-circle" style="color: green;"></i> 
                            <strong>Telah Diterbitkan. Klik untuk melihat.</strong>
                        </a>
                    </dd>
                <?php else: ?>
                    <dd>
                        <i class="fas fa-clock" style="color: #ffc107; margin-right: 8px;"></i>
                        <em>SIK belum diterbitkan oleh Sekretariat.</em>
                    </dd>
                <?php endif; ?>
            </dl>
            
        <?php endif; ?>
    </div>
</div> <footer class="page-footer">
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
                <li><a href="mailto:rektorat@unpar.ac.id" style="color: inherit; text-decoration: none;"><i class="fas fa-envelope"></i> rektorat@unpar.ac.id</a></li>
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