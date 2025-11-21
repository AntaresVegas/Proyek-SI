<?php
session_start();

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

// Sesuaikan path jika perlu
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$ditmawa_id = $_SESSION['user_id'];
$nama_ditmawa = $_SESSION['nama'] ?? 'Staff Ditmawa';
$error_message = '';
$success_message = '';
$event_data = null;
// Meneruskan parameter halaman kembali untuk paginasi
$return_page_query = isset($_GET['page']) ? '?page=' . (int)$_GET['page'] : '';


// 2. PROSES FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['pengajuan_id'], $_POST['action'])) {
        $pengajuan_id = $_POST['pengajuan_id'];
        $komentar = $_POST['komentar'] ?? '';
        $action = $_POST['action'];
        $tanggal_approve = date('Y-m-d H:i:s');
        // Identifikasi apakah ini aksi pembatalan atau persetujuan proposal awal
        $is_pembatalan = (isset($_POST['is_pembatalan']) && $_POST['is_pembatalan'] == 'true');
        
        $conn->begin_transaction();
        
        try {
            $status_baru = "";

            // --- LOGIKA PEMBATALAN BARU ---
            if ($is_pembatalan) {
                if ($action === 'setujui_batal') {
                    $status_baru = 'Disetujui';
                    // Jika pembatalan disetujui, ubah status proposal utama menjadi Ditolak/Dibatalkan
                    $update_sql = "UPDATE pengajuan_event SET pengajuan_status_pembatalan = ?, pengajuan_status_proposal = 'Ditolak', komentar_ditmawa_pembatalan = ?, tanggal_pembatalan_disetujui = ?, pengajuan_tanggalEdit = NOW() WHERE pengajuan_id = ?";
                } elseif ($action === 'tolak_batal') {
                    $status_baru = 'Ditolak';
                    // Jika pembatalan ditolak, status proposal utama tetap (Disetujui/Diajukan)
                    $update_sql = "UPDATE pengajuan_event SET pengajuan_status_pembatalan = ?, komentar_ditmawa_pembatalan = ?, tanggal_pembatalan_disetujui = ?, pengajuan_tanggalEdit = NOW() WHERE pengajuan_id = ?";
                }
                $stmt = $conn->prepare($update_sql);
                if (!$stmt) throw new Exception("Prepare statement gagal (update batal): " . $conn->error);
                $stmt->bind_param("sssi", $status_baru, $komentar, $tanggal_approve, $pengajuan_id);
                $stmt->execute();
                $stmt->close();
                
            } 
            // --- LOGIKA PERSETUJUAN/PENOLAKAN LAMA ---
            else {
                $status_baru = ($action === 'setujui') ? 'Disetujui' : 'Ditolak';
                $update_sql = "UPDATE pengajuan_event SET pengajuan_status_ditmawa = ?, komentar_ditmawa = ?, tanggal_approve_ditmawa = ?, pengajuan_tanggalEdit = NOW() WHERE pengajuan_id = ?";
                $stmt = $conn->prepare($update_sql);
                if (!$stmt) throw new Exception("Prepare statement gagal (update): " . $conn->error);
                $stmt->bind_param("sssi", $status_baru, $komentar, $tanggal_approve, $pengajuan_id);
                $stmt->execute();
                $stmt->close();
            }

            // Mengambil data mahasiswa (termasuk email) dan nama event untuk notifikasi
            $info_stmt = $conn->prepare(
                "SELECT m.mahasiswa_email, m.mahasiswa_nama, pe.pengajuan_namaEvent, pe.pengaju_id
                 FROM pengajuan_event pe 
                 LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
                 WHERE pe.pengajuan_id = ? AND pe.pengaju_tipe = 'mahasiswa'"
            );
            if (!$info_stmt) throw new Exception("Prepare statement gagal (fetch info): " . $conn->error);
            $info_stmt->bind_param("i", $pengajuan_id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result()->fetch_assoc();
            $info_stmt->close();

            if ($info_result) {
                $target_user_id = $info_result['pengaju_id'];
                $target_email = $info_result['mahasiswa_email'];
                $nama_mahasiswa = $info_result['mahasiswa_nama'];
                $nama_event = $info_result['pengajuan_namaEvent'];
                $link = "mahasiswa/mahasiswa_history_pengajuan.php"; // Arahkan ke history umum
                
                // Siapkan pesan notifikasi dan email
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'audricaurelius.aa@gmail.com';
                $mail->Password   = 'leyp iuwc jxfs emlm';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->setFrom('no-reply@unpar.ac.id', 'Sistem Event Unpar');
                $mail->addAddress($target_email, $nama_mahasiswa);
                $mail->isHTML(true);
                $nama_mahasiswa_formatted = ucwords(strtolower($nama_mahasiswa));

                if ($is_pembatalan) {
                    if ($status_baru === 'Disetujui') {
                        $message = "Pembatalan event '{$nama_event}' Anda telah disetujui. Status event kini Dibatalkan.";
                        $mail->Subject = "Pemberitahuan: Pembatalan Event '{$nama_event}' Disetujui";
                        $mail->Body    = "
                            <html><body>
                            <h2>Halo {$nama_mahasiswa_formatted},</h2>
                            <p>Pembatalan event Anda yang bernama <strong>'{$nama_event}'</strong> telah kami setujui. Status pengajuan Anda kini menjadi Dibatalkan.</p>
                            <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                            </body></html>";
                    } else { // Ditolak Pembatalan
                        $message = "Mohon maaf, pengajuan pembatalan event '{$nama_event}' Anda ditolak. Silakan cek detail.";
                         $mail->Subject = "Pemberitahuan: Pembatalan Event '{$nama_event}' Ditolak";
                        $mail->Body    = "
                            <html><body>
                            <h2>Halo {$nama_mahasiswa_formatted},</h2>
                            <p>Mohon maaf, pengajuan pembatalan event Anda yang bernama <strong>'{$nama_event}'</strong> kami tolak.</p>
                            <p><strong>Alasan Penolakan Pembatalan:</strong></p>
                            <p><em>" . (!empty($komentar) ? htmlspecialchars($komentar) : "Tidak ada alasan spesifik yang diberikan.") . "</em></p>
                            <p>Event Anda tetap berjalan sesuai jadwal. Silakan login ke sistem untuk melihat detail.</p>
                            <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                            </body></html>";
                    }
                } else { // Persetujuan/Penolakan Proposal Awal
                    if ($status_baru === 'Disetujui') {
                        $message = "Selamat! Pengajuan event '{$nama_event}' Anda telah disetujui.";
                        $mail->Subject = "Selamat! Pengajuan Event '{$nama_event}' Anda Telah Disetujui";
                        $mail->Body    = "
                            <html><body>
                            <h2>Halo {$nama_mahasiswa_formatted},</h2>
                            <p>Kabar baik! Pengajuan event Anda yang bernama <strong>'{$nama_event}'</strong> telah kami setujui.</p>
                            <p>Anda dapat melanjutkan ke tahap persiapan selanjutnya. Silakan login ke sistem untuk melihat detail lebih lanjut.</p>
                            <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                            </body></html>";
                    } else {
                        $message = "Mohon maaf, pengajuan event '{$nama_event}' Anda ditolak. Silakan cek detail.";
                        $mail->Subject = "Pemberitahuan: Pengajuan Event '{$nama_event}' Anda Ditolak";
                        $mail->Body    = "
                            <html><body>
                            <h2>Halo {$nama_mahasiswa_formatted},</h2>
                            <p>Dengan berat hati kami memberitahukan bahwa pengajuan event Anda yang bernama <strong>'{$nama_event}'</strong> belum dapat kami setujui saat ini.</p>
                            <p><strong>Alasan Penolakan:</strong></p>
                            <p><em>" . (!empty($komentar) ? htmlspecialchars($komentar) : "Tidak ada alasan spesifik yang diberikan.") . "</em></p>
                            <p>Mohon periksa kembali proposal Anda dan lakukan perbaikan yang diperlukan. Silakan login ke sistem untuk melihat detail.</p>
                            <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                            </body></html>";
                    }
                }
                
                $notif_sql = "INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
                $notif_stmt = $conn->prepare($notif_sql);
                if (!$notif_stmt) throw new Exception("Prepare statement (insert notif) gagal: " . $conn->error);
                $notif_stmt->bind_param("iss", $target_user_id, $message, $link);
                $notif_stmt->execute();
                $notif_stmt->close();
                
                $mail->send();
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Status event berhasil diperbarui, notifikasi dan email telah dikirim!";
            header("Location: ditmawa_listKegiatan.php" . $return_page_query);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error_info = isset($mail) ? $mail->ErrorInfo : '';
            $error_message = "Terjadi kesalahan: " . $e->getMessage() . " | Mailer Error: " . $error_info;
        }
    }
}

// 3. PENGAMBILAN DATA EVENT DARI DATABASE
$pengajuan_id = $_GET['id'] ?? null;
if ($pengajuan_id) {
    // [MODIFIKASI] Query ditambah kolom Pembatalan
    $sql = "SELECT 
                pe.*,
                pe.surat_izin_kegiatan_file, 
                pe.pengajuan_status_pembatalan, 
                pe.surat_pembatalan_file, 
                pe.komentar_ditmawa_pembatalan, 
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
                GROUP_CONCAT(DISTINCT g.gedung_nama SEPARATOR ', ') AS nama_gedung,
                GROUP_CONCAT(DISTINCT r.ruangan_nama SEPARATOR ', ') AS nama_ruangan
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
            LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
            LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
            LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
            LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
            WHERE pe.pengajuan_id = ?
            GROUP BY pe.pengajuan_id";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $pengajuan_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $event_data = $result->fetch_assoc();
        } else {
            $error_message = "Data pengajuan event tidak ditemukan.";
        }
        $stmt->close();
    } else {
        $error_message = "Gagal mengambil data: " . $conn->error;
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
    <title>Detail Pengajuan Event - Ditmawa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1e3c72; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center center; background-repeat: no-repeat; background-attachment: fixed; min-height: 100vh; padding-top: 80px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; object-fit: cover; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; font-size: 15px; }
        .navbar-menu li a:hover, .navbar-menu li a.active { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; font-size: 15px; color:rgb(255, 255, 255); }
        .user-name { font-weight: 500; }
        .icon { font-size: 20px; cursor: pointer; }
        .form-container { max-width: 800px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); padding: 30px 40px; }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h1 { font-size: 28px; color: #2c3e50; font-weight: 600; }
        .detail-grid { display: grid; grid-template-columns: 250px 1fr; gap: 15px 20px; margin-bottom: 30px; }
        .detail-grid dt { font-weight: 600; color: #555; }
        .detail-grid dd { color: #333; display: flex; align-items: center; }
        .download-link { text-decoration: none; color: #007bff; font-weight: 500; }
        .download-link i { margin-right: 5px; }
        
        /* [MODIFIKASI] CSS hr dan h2 dibuat global */
        hr { border: 0; border-top: 1px solid #e0e0e0; margin: 30px 0; }
        h2 { font-size: 20px; color: #333; margin-bottom: 15px; font-weight: 600;}

        .action-form textarea { width: 100%; padding: 12px; font-family: 'Segoe UI', sans-serif; font-size: 14px; border: 1px solid #ccc; border-radius: 8px; resize: vertical; min-height: 100px; margin-bottom: 20px; }
        .button-group { display: flex; gap: 15px; justify-content: flex-end; }
        .button-group button { padding: 10px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; color: white; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .button-group button:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .btn-approve-batal { background-color: #ff5722; }
        .btn-reject-batal { background-color: #dc3545; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center; font-size: 16px; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-weight: bold; font-size: 14px; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
        .status-badge.dibatalkan { background-color: #000; } /* Status baru */
        .status-badge.diajukan_batal { background-color: #ff5722; } /* Status baru */
        .info-message { padding: 15px; background-color: #f8f9fa; border-radius: 8px; color: #555; text-align: center; border: 1px solid #e0e0e0; }
        .warning-pembatalan { padding: 15px; background-color: #fff3cd; border-left: 5px solid #ffc107; color: #856404; border-radius: 8px; margin-top: 20px;}
        .warning-pembatalan strong { color: #856404; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_pengajuan.php">Form Pengajuan</a></li>
        <li><a href="ditmawa_listKegiatan.php"class="active">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_import_jadwal.php">Import Jadwal</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama_ditmawa); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="form-container">
    <div class="form-header">
        <h1>Detail Pengajuan Event</h1>
    </div>

    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif ($event_data): ?>
        
        <dl class="detail-grid">
            <dt>Status Ditmawa</dt>
            <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_ditmawa'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_ditmawa']); ?></span></dd>
            
            <dt>Status Pembatalan</dt>
            <dd>
                <?php 
                    $batal_status = $event_data['pengajuan_status_pembatalan'];
                    $batal_class = strtolower(str_replace(' ', '_', $batal_status));
                    if ($batal_status == 'Disetujui') $batal_class = 'dibatalkan';
                    if ($batal_status == 'Diajukan') $batal_class = 'diajukan_batal';
                    if ($batal_status == 'Tidak Ada') $batal_class = 'ditolak'; // Use ditolak style for 'Tidak Ada'
                    
                    echo '<span class="status-badge ' . $batal_class . '">';
                    echo htmlspecialchars($batal_status == 'Tidak Ada' ? 'Belum Ada' : $batal_status);
                    echo '</span>';
                    
                    if ($batal_status == 'Ditolak' && !empty($event_data['komentar_ditmawa_pembatalan'])): 
                ?>
                    <i class="fas fa-comment-dots" style="color: #dc3545; margin-left: 10px;"></i>
                    <span style="font-size: 14px; color: #dc3545; margin-left: 5px;"><?php echo htmlspecialchars($event_data['komentar_ditmawa_pembatalan']); ?></span>
                <?php endif; ?>
            </dd>


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
            
            <dt>Lokasi</dt>
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
                <?php 
                    echo htmlspecialchars(date('d F Y', strtotime($event_data['pengajuan_event_tanggal_mulai']))) . " - " .
                         htmlspecialchars(date('d F Y', strtotime($event_data['pengajuan_event_tanggal_selesai'])));
                ?>
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
                    <i class="fas fa-download"></i> Unduh File Rundown
                </a>
            </dd>
            <dt>Proposal Kegiatan</dt>
            <dd>
                 <a href="../<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>" class="download-link" download>
                    <i class="fas fa-download"></i> Unduh File Proposal
                </a>
            </dd>
        </dl>
        
        <hr><h2>Status Persetujuan Lain</h2>
        <dl class="detail-grid">
             <dt>Status ASP</dt>
             <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status_asp'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status_asp']); ?></span></dd>
             <dt>Komentar ASP</dt>
             <dd><?php echo htmlspecialchars($event_data['komentar_asp'] ?? 'Tidak ada komentar'); ?></dd>
            
            <dt>Surat Izin Kegiatan (SIK)</dt>
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

        <?php // --- BAGIAN PEMBATALAN BARU --- ?>
        <?php if ($event_data['pengajuan_status_pembatalan'] === 'Diajukan'): ?>
            <hr><h2 style="color: #ff5722;"><i class="fas fa-exclamation-triangle"></i> Pengajuan Pembatalan Event</h2>
            
            <div class="warning-pembatalan">
                <strong>Peringatan:</strong> Mahasiswa telah mengajukan pembatalan untuk event ini. Harap segera periksa dan konfirmasi.
                <br>
                <a href="../<?php echo htmlspecialchars($event_data['surat_pembatalan_file']); ?>" class="download-link" download style="color: #ff5722; font-weight: bold; margin-top: 5px; display: inline-block;">
                    <i class="fas fa-file-download"></i> Unduh Surat Pembatalan dari Mahasiswa
                </a>
            </div>

            <form method="POST" action="" class="action-form">
                <input type="hidden" name="pengajuan_id" value="<?php echo htmlspecialchars($event_data['pengajuan_id']); ?>">
                <input type="hidden" name="is_pembatalan" value="true">
                
                <label for="komentar_batal" style="font-weight: 600; color: #555; display: block; margin-bottom: 8px;">Komentar/Alasan (Wajib diisi jika menolak pembatalan):</label>
                
                <textarea name="komentar" id="komentar_batal" placeholder="Berikan komentar atau alasan persetujuan/penolakan pembatalan..."><?php echo htmlspecialchars($event_data['komentar_ditmawa_pembatalan'] ?? ''); ?></textarea>
                
                <div class="button-group">
                    <button type="submit" name="action" value="setujui_batal" class="btn-approve-batal"><i class="fas fa-check"></i> SETUJUI PEMBATALAN</button>
                    <button type="submit" name="action" value="tolak_batal" class="btn-reject-batal"><i class="fas fa-times"></i> TOLAK PEMBATALAN</button>
                </div>
            </form>
            
        <?php elseif ($event_data['pengajuan_status_pembatalan'] === 'Disetujui'): ?>
             <hr><h2 style="color: black;"><i class="fas fa-ban"></i> Event Dibatalkan</h2>
            <p class="info-message" style="background-color: #f1f1f1;">Event ini telah **Dibatalkan** pada tanggal <?php echo htmlspecialchars(date('d F Y', strtotime($event_data['tanggal_pembatalan_disetujui']))); ?>.</p>
        <?php endif; ?>


        <?php // --- BAGIAN PERSETUJUAN PROPOSAL LAMA --- ?>
        <?php if ($event_data['pengaju_tipe'] === 'mahasiswa' && $event_data['pengajuan_status_pembatalan'] !== 'Disetujui'): ?>
            <form method="POST" action="" class="action-form">
                <hr>
                <h2>Tindakan Persetujuan Proposal Ditmawa</h2>
                <input type="hidden" name="pengajuan_id" value="<?php echo htmlspecialchars($event_data['pengajuan_id']); ?>">
                <label for="komentar" style="font-weight: 600; color: #555; display: block; margin-bottom: 8px;">Komentar/Alasan (Wajib diisi jika menolak):</label>
                
                <textarea name="komentar" id="komentar" placeholder="Berikan komentar atau alasan persetujuan/penolakan..."><?php echo htmlspecialchars($event_data['komentar_ditmawa'] ?? ''); ?></textarea>
                
                <div class="button-group">
                    <button type="submit" name="action" value="setujui" class="btn-approve" 
                        <?php if($event_data['pengajuan_status_pembatalan'] === 'Diajukan') echo 'disabled title="Tidak bisa menyetujui proposal karena ada pengajuan pembatalan yang belum diproses."'; ?>>
                        SETUJUI
                    </button>
                    <button type="submit" name="action" value="tolak" class="btn-reject"
                        <?php if($event_data['pengajuan_status_pembatalan'] === 'Diajukan') echo 'disabled title="Tidak bisa menolak proposal karena ada pengajuan pembatalan yang belum diproses."'; ?>>
                        TOLAK
                    </button>
                </div>
                <?php if($event_data['pengajuan_status_pembatalan'] === 'Diajukan'): ?>
                    <p class="error-message" style="margin-top: 15px;">** Harap proses pengajuan pembatalan terlebih dahulu sebelum mengubah status persetujuan proposal. **</p>
                <?php endif; ?>
            </form>
        <?php elseif ($event_data['pengaju_tipe'] !== 'mahasiswa' && $event_data['pengajuan_status_pembatalan'] !== 'Disetujui'): ?>
            <?php // Tampilkan pesan jika event diajukan oleh Ditmawa ?>
            <hr>
            <h2>Tindakan Persetujuan</h2>
            <p class="info-message">
                Ini adalah event yang Anda ajukan atas nama Ditmawa. Tindakan persetujuan tidak diperlukan.
            </p>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

</body>
</html>