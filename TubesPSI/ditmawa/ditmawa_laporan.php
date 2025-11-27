<?php
session_start();
// Sesuaikan path jika perlu
require_once(__DIR__ . '/../config/db_connection.php');
// Panggil autoloader Composer dan class PHPMailer
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// [LOGIC TETAP SAMA] - Tidak ada perubahan pada fungsionalitas backend
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$message = '';
$message_type = '';

// [BARU] Variabel Paginasi
$limit = 20; // 20 baris per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Pastikan halaman tidak kurang dari 1
$offset = ($page - 1) * $limit;

// [DIUBAH] Ambil ?page=N dari URL untuk form action
// Ini penting agar setelah setujui/tolak, user tetap di halaman yang sama
$page_query_string = isset($_GET['page']) ? '?page=' . htmlspecialchars($_GET['page']) : '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pengajuan_id'])) {
    // ... (Logika POST untuk setujui/tolak tetap sama persis) ...
    $pengajuan_id = $_POST['pengajuan_id'];
    $new_status = '';
    $komentar_lpj = NULL;

    if (isset($_POST['setujui_lpj'])) {
        $new_status = 'Disetujui';
    } elseif (isset($_POST['tolak_lpj'])) {
        $new_status = 'Ditolak';
        $komentar_lpj = !empty($_POST['alasan_penolakan']) ? trim($_POST['alasan_penolakan']) : 'Tidak ada alasan yang diberikan.';
    }

    if (!empty($new_status)) {
        try {
            // Mengambil informasi mahasiswa dan event sebelum update
            // [PERBAIKAN] Baris 49 diperbaiki dari 'mahasiswana_email' menjadi 'mahasiswa_email'
            $info_stmt = $conn->prepare(
                "SELECT m.mahasiswa_email, m.mahasiswa_nama, pe.pengajuan_namaEvent 
                 FROM pengajuan_event pe 
                 JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id 
                 WHERE pe.pengajuan_id = ?"
            );
            if (!$info_stmt) throw new Exception("Gagal menyiapkan query info mahasiswa.");
            $info_stmt->bind_param("i", $pengajuan_id);
            $info_stmt->execute();
            $info_result = $info_stmt->get_result()->fetch_assoc();
            $info_stmt->close();

            // Lakukan update ke database
            $stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_statusLPJ = ?, pengajuan_komentarLPJ = ? WHERE pengajuan_id = ?");
            if (!$stmt) throw new Exception("Gagal menyiapkan query update LPJ.");
            $stmt->bind_param("ssi", $new_status, $komentar_lpj, $pengajuan_id);
            $stmt->execute();
            $stmt->close();
            
            if ($info_result) {
                // Konfigurasi dan pengiriman email menggunakan PHPMailer
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'audricaurelius.aa@gmail.com'; // Ganti dengan email Anda
                $mail->Password   = 'leyp iuwc jxfs emlm';     // Ganti dengan App Password Anda
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('no-reply@unpar.ac.id', 'Sistem Event Unpar');
                // Baris ini sekarang akan berfungsi karena query di atas sudah benar
                $mail->addAddress($info_result['mahasiswa_email'], $info_result['mahasiswa_nama']);

                $mail->isHTML(true);
                $nama_event = $info_result['pengajuan_namaEvent'];
                $nama_mahasiswa = $info_result['mahasiswa_nama'];

                if ($new_status === 'Disetujui') {
                    $mail->Subject = "LPJ untuk Event '{$nama_event}' Telah Disetujui";
                    $mail->Body    = "
                        <html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2>Halo {$nama_mahasiswa},</h2>
                        <p>Kabar baik! Laporan Pertanggungjawaban (LPJ) untuk event <strong>'{$nama_event}'</strong> yang Anda unggah telah kami periksa dan setujui.</p>
                        <p>Terima kasih atas kerja keras dan laporannya. Proses pertanggungjawaban untuk event ini telah selesai.</p>
                        <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                        </body></html>";
                } else { // Ditolak
                    $mail->Subject = "Pemberitahuan: LPJ untuk Event '{$nama_event}' Ditolak";
                    $mail->Body    = "
                        <html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2>Halo {$nama_mahasiswa},</h2>
                        <p>Dengan menyesal kami informasikan bahwa Laporan Pertanggungjawaban (LPJ) untuk event <strong>'{$nama_event}'</strong> yang Anda unggah belum dapat kami setujui.</p>
                        <p><strong>Alasan Penolakan:</strong></p>
                        <blockquote style='border-left: 4px solid #ccc; padding-left: 15px; margin-left: 0;'><em>" . htmlspecialchars($komentar_lpj) . "</em></blockquote>
                        <p>Mohon segera perbaiki LPJ Anda sesuai dengan catatan di atas dan unggah kembali melalui sistem.</p>
                        <br><p>Hormat kami,</p><p><strong>Direktorat Kemahasiswaan (Ditmawa) UNPAR</strong></p>
                        </body></html>";
                }
                
                $mail->send();
            }

            $message = "Status LPJ berhasil diperbarui dan notifikasi email telah dikirim.";
            $message_type = 'success';

        } catch (Exception $e) {
            $error_info = isset($mail) ? $mail->ErrorInfo : '';
            $message = "Gagal memperbarui status LPJ. Error: " . $e->getMessage() . " | Mailer Error: " . $error_info;
            $message_type = 'error';
        }
    }
}


// [BARU] Logika untuk menghitung total data
$total_rows = 0;
$total_pages = 0;
try {
    $count_sql = "
        SELECT COUNT(pe.pengajuan_id) as total
        FROM pengajuan_event pe
        JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id
        WHERE pe.pengaju_tipe = 'mahasiswa' 
          AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
    ";
    $count_result = $conn->query($count_sql);
    if ($count_result) {
        $total_rows = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_rows / $limit);
    }
} catch (Exception $e) {
    error_log("Error counting reports: " . $e->getMessage());
}


$laporan_data = [];
try {
    // [DIUBAH] Query SQL utama ditambahkan LIMIT dan OFFSET
    $sql = "
        SELECT 
            pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_LPJ, pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ, m.mahasiswa_nama, m.mahasiswa_npm
        FROM pengajuan_event pe
        JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id
        WHERE pe.pengaju_tipe = 'mahasiswa' 
          AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY 
            CASE pe.pengajuan_statusLPJ
                WHEN 'Menunggu Persetujuan' THEN 1
                WHEN 'Ditolak' THEN 2
                WHEN 'Disetujui' THEN 3
                ELSE 4
            END,
            pe.pengajuan_event_tanggal_selesai DESC
        LIMIT ? OFFSET ?
    "; // Tambahkan LIMIT ? OFFSET ?
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // [BARU] Bind parameter untuk LIMIT dan OFFSET
        $stmt->bind_param("ii", $limit, $offset);
        
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $laporan_data[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching report data: " . $e->getMessage());
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Laporan - Event Management Unpar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ff8c00;
            --text-dark: #2c3e50;
            --text-light: #8895a7;
            --border-color: #e5e7eb;
            --white: #ffffff;
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
            --bg-light: #f9fafb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background-image: url('../img/backgroundDitmawa.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        
        /* Navbar & Footer Konsisten (TIDAK BERUBAH) */
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-right { gap: 15px; color: var(--white); }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: var(--white); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu a { text-decoration: none; color: var(--white); font-weight: 500; }
        .navbar-menu a.active, .navbar-menu a:hover { color: #007bff; }
        .icon { font-size: 20px; color: white; }
        .navbar-right .icon { margin-left: 5px; }
        a { text-decoration: none; }
        .page-footer { background-color: var(--primary-color); color: #fff; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        /* AKHIR DARI Navbar & Footer */


        /* Main Content & Container */
        .main-content { flex-grow: 1; padding: 30px 0; }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 0 20px; 
        }
        .page-header { margin-bottom: 30px; padding: 1.5rem; text-align: center; background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 12px; }
        .page-header h1 { font-size: 2.25rem; font-weight: 700; color: var(--text-dark); }
        .page-header p { font-size: 1.1rem; color: #5a6a7a; margin-top: 5px;}

        /* Alert/Message Box */
        .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .message.success { background-color: #d1fae5; color: #065f46; }
        .message.error { background-color: #fee2e2; color: #991b1b; }
        
        
        /* Table Styling */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            overflow: hidden; 
        }

        .table-responsive-wrapper {
            overflow-x: auto; 
        }

        .laporan-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px; 
        }

        .laporan-table th,
        .laporan-table td {
            padding: 1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .laporan-table th {
            background-color: var(--bg-light);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .laporan-table tbody tr:last-child td {
            border-bottom: none;
        }

        .laporan-table tbody tr:hover {
            background-color: #fcfcfc;
        }

        .event-title {
            font-weight: 600;
            color: var(--text-dark);
        }

        .keterangan-text {
            font-style: italic;
            color: var(--danger);
            font-size: 0.9rem;
            max-width: 250px; 
            white-space: normal;
        }
        
        /* Status Badge */
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-weight: 600; font-size: 0.75rem; text-transform: capitalize; white-space: nowrap; display: inline-block; }
        .status-badge.menunggu-persetujuan { background-color: #fef3c7; color: #92400e; }
        .status-badge.ditolak { background-color: #fee2e2; color: #991b1b; }
        .status-badge.disetujui { background-color: #d1fae5; color: #065f46; }

        /* Buttons */
        .btn { 
            padding: 0.6rem 1.2rem; 
            border: none; 
            border-radius: 8px; 
            font-size: 0.9rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem;
        }
        .btn i { font-size: 0.8rem; }
        .btn-approve { background-color: var(--success); color: var(--white); }
        .btn-approve:hover { background-color: #059669; }
        .btn-reject { background-color: var(--danger); color: var(--white); }
        .btn-reject:hover { background-color: #dc2626; }
        .download-button { background-color: var(--info); color: var(--white); } 
        .download-button:hover { background-color: #2563eb; }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }
        
        /* Empty State */
        .no-data-message { text-align: center; padding: 3rem; background: rgba(255,255,255,0.9); border-radius: 12px; border: 1px dashed var(--border-color); }
        .no-data-message i { font-size: 3rem; color: var(--text-light); margin-bottom: 1rem; }
        .no-data-message p { font-size: 1.1rem; color: var(--text-light); }

        /* Modal (TIDAK BERUBAH) */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 550px; border-radius: 12px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { color: var(--text-dark); }
        .close-button { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-body textarea { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 8px; min-height: 120px; margin-bottom: 20px; resize: vertical; }
        .modal-footer { text-align: right; }
        
        /* [BARU] CSS Untuk Paginasi */
        .pagination-container {
            display: flex;
            justify-content: space-between; /* Mengatur jarak */
            align-items: center;
            flex-wrap: wrap;
            padding: 1.5rem;
            background: var(--white);
            border-radius: 0 0 12px 12px; /* Menempel di bawah tabel */
            box-shadow: 0 -2px 5px rgba(0,0,0,0.03); /* Bayangan halus di atas */
            margin-top: -1px; /* Menempel sempurna dengan table-container */
        }
        .pagination-info {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        .pagination-links {
            display: flex;
            gap: 5px;
        }
        .page-link {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background: var(--white);
            color: var(--primary-color);
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .page-link:hover {
            background-color: #fdf8f2;
            border-color: #fcd9b3;
        }
        .page-link.active {
            background-color: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        .page-link.disabled {
            color: var(--text-light);
            pointer-events: none;
            background-color: var(--bg-light);
        }
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
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_import_jadwal.php">Import Jadwal</a></li>
        <li><a href="ditmawa_laporan.php" class="active">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"style="color:black;"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <header class="page-header">
            <h1>Laporan Bukti Kegiatan</h1>
            <p>Tinjau, setujui, atau tolak laporan bukti kegiatan yang diajukan oleh mahasiswa.</p>
        </header>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($laporan_data)): ?>
            <div class="table-container">
                <div class="table-responsive-wrapper">
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th>Nama Acara</th>
                                <th>Nama Mahasiswa</th>
                                <th>NPM</th>            
                                <th>Status LPJ</th>
                                <th>Dokumen</th>
                                <th>Keterangan (Jika Ditolak)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporan_data as $row):
                                $status_class = str_replace(' ', '-', strtolower(htmlspecialchars($row['pengajuan_statusLPJ'])));
                            ?>
                                <tr>
                                    <td class="event-title">
                                        <?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?>
                                    </td>
                                    <td> 
                                        <?php echo htmlspecialchars($row['mahasiswa_nama']); ?>
                                    </td>
                                    <td> 
                                        <?php echo htmlspecialchars($row['mahasiswa_npm']); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($row['pengajuan_statusLPJ']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['pengajuan_LPJ'])): ?>
                                             <a href="../<?php echo htmlspecialchars($row['pengajuan_LPJ']); ?>" class="btn download-button" download>
                                                 <i class="fas fa-download"></i> Download
                                             </a>
                                        <?php else: ?>
                                            <span>-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="keterangan-text">
                                        <?php 
                                            if ($row['pengajuan_statusLPJ'] == 'Ditolak' && !empty($row['pengajuan_komentarLPJ'])) {
                                                echo htmlspecialchars($row['pengajuan_komentarLPJ']);
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="ditmawa_laporan.php<?php echo $page_query_string; ?>" style="display:inline;">
                                                <input type="hidden" name="pengajuan_id" value="<?php echo $row['pengajuan_id']; ?>">
                                                <button type="submit" name="setujui_lpj" class="btn btn-approve">
                                                    <i class="fas fa-check"></i> Setuju
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-reject" onclick="openRejectModal('<?php echo $row['pengajuan_id']; ?>')">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div> <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Menampilkan <strong><?php echo count($laporan_data); ?></strong> dari <strong><?php echo $total_rows; ?></strong> data
                </div>
                <div class="pagination-links">
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        &laquo;
                    </a>
                    
                    <?php
                        $window = 2; // Jumlah halaman di kiri dan kanan halaman aktif
                        for ($i = 1; $i <= $total_pages; $i++):
                            // Tampilkan halaman pertama, halaman terakhir, dan halaman di sekitar halaman aktif
                            if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php
                            // Tambahkan '...' jika ada jeda
                            elseif ($i == 2 || $i == $total_pages - 1):
                                echo '<span class="page-link" style="border:none; background:none;">...</span>';
                            endif;
                        endfor;
                    ?>
                    
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        &raquo;
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="no-data-message">
                <i class="fas fa-folder-open"></i>
                <p>Belum ada LPJ yang diunggah oleh mahasiswa.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Alasan Penolakan LPJ</h2>
            <span class="close-button" onclick="closeRejectModal()">&times;</span>
        </div>
        <form id="rejectForm" method="POST" action="ditmawa_laporan.php<?php echo $page_query_string; ?>">
            <div class="modal-body">
                <input type="hidden" id="reject_pengajuan_id" name="pengajuan_id">
                <label for="alasan_penolakan">Mohon berikan alasan penolakan yang jelas agar mahasiswa dapat melakukan perbaikan:</label>
                <textarea id="alasan_penolakan" name="alasan_penolakan" required placeholder="Contoh: ada kesalahan dalam foto tidak sesuai"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" name="tolak_lpj" class="btn btn-reject"><i class="fas fa-paper-plane"></i> Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>

<script>
    var modal = document.getElementById('rejectModal');
    var rejectPengajuanIdInput = document.getElementById('reject_pengajuan_id');
    function openRejectModal(pengajuan_id) {
        rejectPengajuanIdInput.value = pengajuan_id;
        modal.style.display = "block";
    }
    function closeRejectModal() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            closeRejectModal();
        }
    }
</script>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;color :black">DIREKTORAT KEMAHASISWAAN</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655 ext. 100140</li>
                <li><i class="fas fa-envelope"></i> kemahasiswaan@unpar.ac.id</li>
            </ul>
        </div>
    </div>
</footer>
</body>
</html>