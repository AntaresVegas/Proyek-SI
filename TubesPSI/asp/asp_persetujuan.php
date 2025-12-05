<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$nama_asp = $_SESSION['nama'] ?? 'Staff ASP';
$error_message = '';
$event_data = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pengajuan_id'], $_POST['action'])) {
    $pengajuan_id = $_POST['pengajuan_id'];
    $komentar_asp = $_POST['komentar'] ?? '';
    $status_asp_baru = ($_POST['action'] === 'setujui') ? 'Disetujui' : 'Ditolak';
    $tanggal_approve_asp = date('Y-m-d H:i:s');
    
    $conn->begin_transaction();
    try {
        // 1. Ambil status Ditmawa saat ini
        $stmt_check = $conn->prepare("SELECT pengajuan_status_ditmawa FROM pengajuan_event WHERE pengajuan_id = ?");
        $stmt_check->bind_param("i", $pengajuan_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_status = $result_check->fetch_assoc();
        $stmt_check->close();
        
        if (!$current_status) throw new Exception("Event tidak ditemukan.");

        // 2. Tentukan status proposal final berdasarkan logika
        $status_proposal_baru = 'Ditolak'; // Default-nya ditolak
        if ($current_status['pengajuan_status_ditmawa'] === 'Disetujui' && $status_asp_baru === 'Disetujui') {
            $status_proposal_baru = 'Disetujui';
        }

        // 3. Update status ASP dan status Proposal Final
        $update_sql = "UPDATE pengajuan_event SET pengajuan_status_asp = ?, komentar_asp = ?, pengajuan_tanggalApprove_asp = ?, pengajuan_status_proposal = ? WHERE pengajuan_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $status_asp_baru, $komentar_asp, $tanggal_approve_asp, $status_proposal_baru, $pengajuan_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        $_SESSION['success_message'] = "Keputusan untuk event telah berhasil disimpan!";
        header("Location: asp_listKegiatan.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}

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
    <title>Form Persetujuan ASP - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-image: url('../img/backgroundASP.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh; padding-top: 80px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #0A2342; width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #FFD700; }
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
        
        /* [MODIFIKASI] CSS hr dan h2 dibuat global */
        hr { border: 0; border-top: 1px solid #e0e0e0; margin: 30px 0; }
        h2 { font-size: 20px; color: #333; margin-bottom: 15px; }
        
        .action-form textarea { width: 100%; padding: 12px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px; min-height: 100px; margin-bottom: 20px; }
        .button-group { display: flex; gap: 15px; justify-content: flex-end; }
        .button-group button { padding: 10px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; color: white; cursor: pointer; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .error-message, .info-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .info-message { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-weight: bold; font-size: 14px; display: inline-block;}
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
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
        <li><a href="asp_listKegiatan.php" class="active">Persetujuan Event</a></li>
        <li><a href="asp_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="asp_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="asp_kalender.php">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama_asp); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>
<div class="form-container">
    <div class="form-header"><h1>Detail Persetujuan Sarana Prasarana</h1></div>
    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif ($event_data): ?>
        <dl class="detail-grid">
            <dt>Status Ditmawa</dt>
            <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_ditmawa'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_ditmawa']); ?></span></dd>
            <dt>Status ASP</dt>
            <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_asp'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_asp']); ?></span></dd>
            <dt>Status Proposal Final</dt>
            <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_proposal'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_proposal']); ?></span></dd>
            
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
            <dt>Tipe Kegiatan</dt>
            <dd><?php echo htmlspecialchars($event_data['pengajuan_TypeKegiatan']); ?></dd>
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
            <dt>Jam Acara</dt>
            <dd>
                <?php
                    echo htmlspecialchars(date('H:i', strtotime($event_data['pengajuan_event_jam_mulai']))) . " - " .
                         htmlspecialchars(date('H:i', strtotime($event_data['pengajuan_event_jam_selesai']))) . " WIB";
                ?>
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
        
        <hr><h2>Surat Izin Kegiatan (SIK)</h2>
        <dl class="detail-grid">
            <dt>Status SIK</dt>
            <?php if (!empty($event_data['surat_izin_kegiatan_file'])): ?>
                <dd>
                    <a href="../<?php echo htmlspecialchars($event_data['surat_izin_kegiatan_file']); ?>" class="download-link" target="_blank">
                        <i class="fas fa-check-circle" style="color: green; margin-right: 8px;"></i> 
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

        <?php if ($event_data['pengajuan_status_ditmawa'] === 'Disetujui' && $event_data['pengajuan_status_asp'] === 'Diajukan'): ?>
            <form method="POST" action="" class="action-form">
                <hr><h2>Tindakan Persetujuan ASP</h2>
                <input type="hidden" name="pengajuan_id" value="<?php echo htmlspecialchars($event_data['pengajuan_id']); ?>">
                <label for="komentar" style="font-weight: 600; display: block; margin-bottom: 8px;">Komentar/Catatan (Wajib jika menolak):</label>
                <textarea name="komentar" id="komentar" placeholder="Berikan catatan terkait sarana dan prasarana..."></textarea>
                <div class="button-group">
                    <button type="submit" name="action" value="setujui" class="btn-approve">SETUJUI</button>
                    <button type="submit" name="action" value="tolak" class="btn-reject">TOLAK</button>
                </div>
            </form>
        <?php else: ?>
            <hr><h2>Detail Keputusan ASP</h2>
            <?php if ($event_data['pengajuan_status_ditmawa'] !== 'Disetujui'): ?>
                <p class="info-message">Tidak dapat memberikan keputusan karena pengajuan ini belum disetujui oleh Ditmawa.</p>
            <?php else: ?>
                <dl class="detail-grid">
                    <dt>Komentar ASP</dt>
                    <dd><?php echo !empty($event_data['komentar_asp']) ? htmlspecialchars($event_data['komentar_asp']) : 'Tidak ada komentar.'; ?></dd>
                    <dt>Tanggal Keputusan ASP</dt>
                    <dd><?php echo htmlspecialchars(date('d F Y H:i', strtotime($event_data['pengajuan_tanggalApprove_asp']))); ?></dd>
                </dl>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>