<?php
session_start();

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

// Sesuaikan path jika perlu
require_once(__DIR__ . '/../config/db_connection.php'); 
// Panggil autoloader Composer dan class PHPMailer
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$ditmawa_id = $_SESSION['user_id'];
$nama_ditmawa = $_SESSION['nama'] ?? 'Staff Ditmawa';
$error_message = '';
$success_message = '';
$event_data = null;

// 2. PROSES FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['pengajuan_id'], $_POST['action'])) {
        $pengajuan_id = $_POST['pengajuan_id'];
        $komentar = $_POST['komentar'] ?? '';
        $status_baru = ($_POST['action'] === 'setujui') ? 'Disetujui' : 'Ditolak';
        $tanggal_approve = date('Y-m-d H:i:s');

        $conn->begin_transaction();
        
        try {
            // Update status pengajuan di database
            $update_sql = "UPDATE pengajuan_event SET pengajuan_status = ?, pengajuan_komentarDitmawa = ?, pengajuan_tanggalApprove = ? WHERE pengajuan_id = ?";
            $stmt = $conn->prepare($update_sql);
            if (!$stmt) throw new Exception("Prepare statement gagal (update): " . $conn->error);
            $stmt->bind_param("sssi", $status_baru, $komentar, $tanggal_approve, $pengajuan_id);
            $stmt->execute();
            $stmt->close();

            // Mengambil data mahasiswa (termasuk email) dan nama event untuk notifikasi
            $info_stmt = $conn->prepare(
                "SELECT m.mahasiswa_email, m.mahasiswa_nama, pe.pengajuan_namaEvent, pe.pengaju_id
                 FROM pengajuan_event pe 
                 JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id 
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
                $link = "mahasiswa/mahasiswa_detail_pengajuan.php?id=" . $pengajuan_id;

                // Kirim notifikasi internal (jika masih diperlukan)
                if ($status_baru === 'Disetujui') {
                    $message = "Selamat! Pengajuan event '{$nama_event}' Anda telah disetujui.";
                } else {
                    $message = "Mohon maaf, pengajuan event '{$nama_event}' Anda ditolak. Silakan cek detail.";
                }
                $notif_sql = "INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
                $notif_stmt = $conn->prepare($notif_sql);
                if (!$notif_stmt) throw new Exception("Prepare statement gagal (insert notif): " . $conn->error);
                $notif_stmt->bind_param("iss", $target_user_id, $message, $link);
                $notif_stmt->execute();
                $notif_stmt->close();

                // =================================================================
                // ## KODE PENGIRIMAN EMAIL (MENGGUNAKAN PHPMailer) ##
                // =================================================================
                $mail = new PHPMailer(true);

                // Konfigurasi server SMTP
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'audricaurelius.aa@gmail.com'; // Email Pengirim
                $mail->Password   = 'leyp iuwc jxfs emlm';     // App Password Anda
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Penerima
                $mail->setFrom('no-reply@unpar.ac.id', 'Sistem Event Unpar');
                $mail->addAddress($target_email, $nama_mahasiswa);

                // Konten Email
                $mail->isHTML(true);
                
                if ($status_baru === 'Disetujui') {
                    $mail->Subject = "Selamat! Pengajuan Event '{$nama_event}' Anda Telah Disetujui";
                    $mail->Body    = "
                        <html><body>
                        <h2>Halo {$nama_mahasiswa},</h2>
                        <p>Kabar baik! Pengajuan event Anda yang bernama <strong>'{$nama_event}'</strong> telah kami setujui.</p>
                        <p>Anda dapat melanjutkan ke tahap persiapan selanjutnya. Silakan login ke sistem untuk melihat detail lebih lanjut.</p>
                        <br>
                        <p>Hormat kami,</p>
                        <p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                        </body></html>";
                } else { // Ditolak
                    $mail->Subject = "Pemberitahuan: Pengajuan Event '{$nama_event}' Anda Ditolak";
                    $mail->Body    = "
                        <html><body>
                        <h2>Halo {$nama_mahasiswa},</h2>
                        <p>Dengan berat hati kami memberitahukan bahwa pengajuan event Anda yang bernama <strong>'{$nama_event}'</strong> belum dapat kami setujui saat ini.</p>
                        <p><strong>Alasan Penolakan:</strong></p>
                        <p><em>" . (!empty($komentar) ? htmlspecialchars($komentar) : "Tidak ada alasan spesifik yang diberikan.") . "</em></p>
                        <p>Mohon periksa kembali proposal Anda dan lakukan perbaikan yang diperlukan. Silakan login ke sistem untuk melihat detail.</p>
                        <br>
                        <p>Hormat kami,</p>
                        <p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                        </body></html>";
                }

                $mail->send(); // Kirim email
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Status event berhasil diperbarui, notifikasi dan email telah dikirim!";
            header("Location: ditmawa_listKegiatan.php");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            // Menambahkan error dari PHPMailer ke pesan error utama jika ada
            $error_info = isset($mail) ? $mail->ErrorInfo : '';
            $error_message = "Terjadi kesalahan: " . $e->getMessage() . " | Mailer Error: " . $error_info;
        }
    }
}

// ... Sisa kode HTML Anda tetap sama persis seperti sebelumnya ...
// 3. PENGAMBILAN DATA EVENT DARI DATABASE
$pengajuan_id = $_GET['id'] ?? null;
if ($pengajuan_id) {
    // Query Utama untuk Menampilkan Detail
    $sql = "SELECT 
                pe.*, 
                m.mahasiswa_nama, 
                m.mahasiswa_email, 
                m.mahasiswa_npm, 
                m.mahasiswa_jurusan,
                GROUP_CONCAT(DISTINCT r.ruangan_nama SEPARATOR ', ') AS nama_ruangan,
                GROUP_CONCAT(DISTINCT g.gedung_nama SEPARATOR ', ') AS nama_gedung
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
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
    <title>Form Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS Anda tetap sama */
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
        .download-link { text-decoration: none; color: #007bff; font-weight: 500; margin-left: 10px; }
        .download-link:hover { color: #0056b3; }
        .download-link i { margin-right: 5px; }
        .action-form hr { border: 0; border-top: 1px solid #e0e0e0; margin: 30px 0; }
        .action-form h2 { font-size: 20px; color: #333; margin-bottom: 15px; font-weight: 600;}
        .action-form textarea { width: 100%; padding: 12px; font-family: 'Segoe UI', sans-serif; font-size: 14px; border: 1px solid #ccc; border-radius: 8px; resize: vertical; min-height: 100px; margin-bottom: 20px; }
        .button-group { display: flex; gap: 15px; justify-content: flex-end; }
        .button-group button { padding: 10px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; color: white; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
        .button-group button:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .error-message { color: #dc3545; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center; font-size: 16px; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-weight: bold; font-size: 14px; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
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
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
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
            <dt>Status Saat Ini</dt>
            <dd><span class="status-badge <?php echo strtolower(htmlspecialchars($event_data['pengajuan_status'])); ?>"><?php echo htmlspecialchars($event_data['pengajuan_status']); ?></span></dd>
            <dt>Nama Pengaju</dt>
            <dd><?php echo htmlspecialchars($event_data['mahasiswa_nama'] ?? 'N/A'); ?></dd>
            <dt>Email</dt>
            <dd><?php echo htmlspecialchars($event_data['mahasiswa_email'] ?? 'N/A'); ?></dd>
            <dt>NPM</dt>
            <dd><?php echo htmlspecialchars($event_data['mahasiswa_npm'] ?? 'N/A'); ?></dd>
            <dt>Jurusan</dt>
            <dd><?php echo htmlspecialchars($event_data['mahasiswa_jurusan'] ?? 'N/A'); ?></dd>
            <dt>Nama Event</dt>
            <dd><?php echo htmlspecialchars($event_data['pengajuan_namaEvent']); ?></dd>
            <dt>Tipe Kegiatan</dt>
            <dd><?php echo htmlspecialchars($event_data['pengajuan_TypeKegiatan']); ?></dd>
            <dt>Lokasi</dt>
            <dd><?php echo htmlspecialchars($event_data['nama_gedung'] . ' (' . $event_data['nama_ruangan'] . ')'); ?></dd>
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
                <a href="../mahasiswa/<?php echo htmlspecialchars($event_data['jadwal_event_rundown_file']); ?>" class="download-link" download>
                    <i class="fas fa-download"></i> Unduh File Rundown
                </a>
            </dd>
            <dt>Proposal Kegiatan</dt>
            <dd>
                 <a href="../mahasiswa/<?php echo htmlspecialchars($event_data['pengajuan_event_proposal_file']); ?>" class="download-link" download>
                    <i class="fas fa-download"></i> Unduh File Proposal
                </a>
            </dd>
        </dl>
        
        <?php if ($event_data['pengajuan_status'] == 'Diajukan'): ?>
            <form method="POST" action="" class="action-form">
                <hr>
                <h2>Tindakan Persetujuan</h2>
                <input type="hidden" name="pengajuan_id" value="<?php echo htmlspecialchars($event_data['pengajuan_id']); ?>">
                <label for="komentar" style="font-weight: 600; color: #555; display: block; margin-bottom: 8px;">Komentar/Alasan (Wajib diisi jika menolak):</label>
                <textarea name="komentar" id="komentar" placeholder="Berikan komentar atau alasan persetujuan/penolakan..."></textarea>
                <div class="button-group">
                    <button type="submit" name="action" value="setujui" class="btn-approve">SETUJUI</button>
                    <button type="submit" name="action" value="tolak" class="btn-reject">TOLAK</button>
                </div>
            </form>
        <?php else: ?>
            <hr>
            <h2>Detail Persetujuan</h2>
            <dl class="detail-grid">
                <dt>Komentar</dt>
                <dd><?php echo !empty($event_data['pengajuan_komentarDitmawa']) ? htmlspecialchars($event_data['pengajuan_komentarDitmawa']) : 'Tidak ada komentar.'; ?></dd>
                <dt>Tanggal Keputusan</dt>
                <dd><?php echo htmlspecialchars(date('d F Y H:i', strtotime($event_data['pengajuan_tanggalApprove']))); ?></dd>
            </dl>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>