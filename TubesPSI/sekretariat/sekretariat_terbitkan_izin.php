<?php
session_start();
require_once('../config/db_connection.php');

// Autentikasi Sekretariat
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'sekretariat') {
    header("Location: ../index.php");
    exit();
}

$nama_sekretariat = $_SESSION['nama'] ?? 'Staff Sekretariat';
$sekuniv_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';
$event_data = null;
$pengajuan_id = $_GET['id'] ?? null;

// --- PROSES UPLOAD FILE (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pengajuan_id'], $_FILES['surat_izin_file'])) {
    $pengajuan_id = $_POST['pengajuan_id'];
    $file = $_FILES['surat_izin_file'];

    // Validasi File
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Terjadi kesalahan saat mengunggah file. Kode Error: " . $file['error'];
    } else {
        $file_name = $file['name'];
        $file_tmp_name = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext !== 'pdf') {
            $error_message = "Gagal: File harus dalam format PDF.";
        } elseif ($file_size > 5242880) { // 5 MB
            $error_message = "Gagal: Ukuran file tidak boleh lebih dari 5 MB.";
        } else {
            // Buat nama file unik
            $new_file_name = 'SIK_' . $pengajuan_id . '_' . uniqid() . '.pdf';
            $upload_dir = '../uploads/surat_izin/'; // Pastikan folder ini ada!
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_destination = $upload_dir . $new_file_name;
            $db_file_path = 'uploads/surat_izin/' . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $file_destination)) {
                // Update database
                $stmt = $conn->prepare("UPDATE pengajuan_event SET surat_izin_kegiatan_file = ?, tanggal_terbit_surat_izin = NOW(), penerbit_surat_izin_id = ? WHERE pengajuan_id = ?");
                $stmt->bind_param("sii", $db_file_path, $sekuniv_id, $pengajuan_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Surat Izin Kegiatan berhasil diunggah!";
                    header("Location: sekretariat_listKegiatan.php"); // Kembali ke list
                    exit();
                } else {
                    $error_message = "Gagal menyimpan data file ke database.";
                }
                $stmt->close();
            } else {
                $error_message = "Gagal memindahkan file yang diunggah.";
            }
        }
    }
}

// --- AMBIL DATA UNTUK DITAMPILKAN (GET) ---
if ($pengajuan_id) {
    // [MODIFIKASI] Query diperlengkap untuk menampilkan semua detail event
    $sql = "SELECT pe.*,
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
                   GROUP_CONCAT(DISTINCT g.gedung_nama SEPARATOR ', ') AS nama_gedung,
                   su.sekuniv_nama as nama_penerbit
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
            LEFT JOIN sekretariat_universitas su ON pe.penerbit_surat_izin_id = su.sekuniv_id
            LEFT JOIN peminjaman_ruangan pr ON pe.pengajuan_id = pr.pengajuan_id
            LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
            LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
            LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
            WHERE pe.pengajuan_id = ? AND pe.pengajuan_status_proposal = 'Disetujui'
            GROUP BY pe.pengajuan_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pengajuan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event_data = $result->fetch_assoc();
    $stmt->close();

    if (!$event_data) {
        $error_message = "Event tidak ditemukan atau belum disetujui sepenuhnya.";
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
    <title>Terbitkan Izin - Sekretariat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary-color: #1E88E5; --secondary-color: #464E51; --success-color: #28a745; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-image: url('../img/backgroundSekretariat.jpg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh; padding-top: 80px; }
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
        .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .status-badge { padding: 5px 12px; border-radius: 15px; color: white; font-weight: bold; font-size: 14px; display: inline-block;}
        .status-badge.disetujui { background-color: var(--success-color); }
        hr { border: 0; border-top: 1px solid #e0e0e0; margin: 30px 0; }
        h2 { font-size: 20px; color: #333; margin-bottom: 20px; }
        
        .download-link { text-decoration: none; color: #007bff; font-weight: 500; }
        .download-link i { margin-right: 5px; }

        .current-file-box { background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; }
        
        /* [MODIFIKASI] CSS untuk Tombol Template dan Form Upload */
        .upload-section { margin-top: 20px; }
        .template-download {
            display: inline-block;
            background-color: #17a2b8;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: background-color 0.3s;
        }
        .template-download:hover { background-color: #138496; }
        .template-download i { margin-right: 8px; }

        .upload-form label { font-weight: 600; display: block; margin-bottom: 10px; }
        .upload-form .file-upload-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            background-color: #f8f9fa;
            width: 100%;
            margin-bottom: 20px;
        }
        .upload-form .file-upload-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .upload-form .file-upload-wrapper .file-upload-text {
            color: #555;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .upload-form .file-upload-wrapper .file-upload-text i { color: var(--primary-color); }
        
        .btn-submit { 
            background-color: var(--success-color); 
            color: white; 
            padding: 12px 25px; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover { background-color: #218838; }
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
        <li><a href="sekretariat_listKegiatan.php" class="active">Semua Event</a></li>
        <li><a href="sekretariat_kalender.php">Kalender Event</a></li>
        <li><a href="sekretariat_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="sekretariat_kalender_gabungan.php">Kalender Gabungan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="sekretariat_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama_sekretariat); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="form-container">
    <div class="form-header"><h1>Terbitkan Surat Izin Kegiatan</h1></div>
    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php elseif ($event_data): ?>
        
        <h2>Detail Event</h2>
        <dl class="detail-grid">
            <dt>Status Proposal Final</dt>
            <dd><span class="status-badge disetujui">Disetujui</span></dd>
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
        
        <hr>
        
        <?php if (!empty($event_data['surat_izin_kegiatan_file'])): ?>
            <div class="current-file-box">
                <h2>Surat Izin Sudah Diterbitkan</h2>
                <dl class="detail-grid">
                    <dt>File SIK</dt>
                    <dd><a href="../<?php echo htmlspecialchars($event_data['surat_izin_kegiatan_file']); ?>" class="download-link" target="_blank"><i class="fas fa-file-pdf"></i> Lihat SIK Saat Ini</a></dd>
                    <dt>Diterbitkan Oleh</dt>
                    <dd><?php echo htmlspecialchars($event_data['nama_penerbit'] ?? 'N/A'); ?></dd>
                    <dt>Tanggal Terbit</dt>
                    <dd><?php echo htmlspecialchars(date('d F Y H:i', strtotime($event_data['tanggal_terbit_surat_izin']))); ?></dd>
                </dl>
            </div>
            <hr>
        <?php endif; ?>

        <div class="upload-section">
            <h2><?php echo !empty($event_data['surat_izin_kegiatan_file']) ? 'Unggah Versi Baru (Menggantikan)' : 'Unggah Surat Izin Kegiatan'; ?></h2>

            <a href="../templates/Template_SIK.pdf" class="template-download" download>
                <i class="fas fa-file-word"></i> Unduh Template SIK
            </a>

            <form action="sekretariat_terbitkan_izin.php?id=<?php echo $pengajuan_id; ?>" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="pengajuan_id" value="<?php echo htmlspecialchars($event_data['pengajuan_id']); ?>">
                
                <label for="surat_izin_file">Pilih File PDF (Maks 5 MB)</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="surat_izin_file" id="surat_izin_file" accept=".pdf" required onchange="document.getElementById('file-upload-text').textContent = this.files[0].name">
                    <span classs="file-upload-text" id="file-upload-text">
                        <i class="fas fa-paperclip"></i> Pilih file...
                    </span>
                </div>
                
                <button type="submit" class="btn-submit"><i class="fas fa-upload"></i> Unggah dan Terbitkan</button>
            </form>
        </div>
        
    <?php endif; ?>
</div>

<script>
    // Script kecil untuk menampilkan nama file di tombol upload
    const fileInput = document.getElementById('surat_izin_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileName = this.files.length > 0 ? this.files[0].name : 'Pilih file...';
            document.getElementById('file-upload-text').innerHTML = `<i class="fas fa-paperclip"></i> ${fileName}`;
        });
    }
</script>

</body>
</html>