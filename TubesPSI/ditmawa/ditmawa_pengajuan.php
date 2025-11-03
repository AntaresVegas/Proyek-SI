<?php
session_start();

// Autentikasi Ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../auth/login.php"); // Arahkan ke login jika tidak sesuai
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$ditmawa_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Proses Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namaEvent = trim($_POST['nama_event']);

    // Validasi Nama Event
    if (strlen($namaEvent) < 5) {
        $message = "Nama event harus terdiri dari minimal 5 karakter.";
        $message_type = 'error';
    } else {
        // Ambil data Tipe Kegiatan
        $tipeKegiatan = $_POST['tipe_kegiatan_select'];
        if ($tipeKegiatan === 'Lainnya') {
            $tipeKegiatan = !empty($_POST['tipe_kegiatan_lainnya']) ? trim($_POST['tipe_kegiatan_lainnya']) : 'Lainnya'; // Ambil dari input teks jika 'Lainnya' dipilih
        }
        
        // Ambil data tanggal dan jam
        $tanggalMulai = $_POST['tanggal_mulai'];
        $tanggalSelesai = $_POST['tanggal_selesai'];
        $jamMulai = $_POST['jam_mulai'];
        $jamSelesai = $_POST['jam_selesai'];
        $tanggalPersiapan = !empty($_POST['tanggal_persiapan']) ? $_POST['tanggal_persiapan'] : NULL;
        $tanggalBeres = !empty($_POST['tanggal_beres']) ? $_POST['tanggal_beres'] : NULL;
        
        // Ambil ID ruangan yang dipilih
        $selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : [];

        // Tentukan Status Awal (karena ini form Ditmawa)
        $pengajuTipe = 'ditmawa';
        $status_ditmawa = 'Disetujui'; // Otomatis disetujui oleh Ditmawa
        $status_asp = 'Diajukan';       // Perlu persetujuan ASP
        $status_proposal = 'Diajukan';  // Status proposal awal

        // Mulai transaksi database
        $conn->begin_transaction();
        try {
            // Insert data event utama
            $stmt = $conn->prepare(
                "INSERT INTO pengajuan_event (
                    pengajuan_namaEvent, pengaju_tipe, pengaju_id, pengajuan_TypeKegiatan, 
                    pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai, 
                    pengajuan_event_jam_mulai, pengajuan_event_jam_selesai, 
                    tanggal_persiapan, tanggal_beres, 
                    pengajuan_status_ditmawa, pengajuan_status_asp, pengajuan_status_proposal, 
                    pengajuan_tanggalEdit, tanggal_approve_ditmawa -- Tanggal approve ditmawa diisi NOW()
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())" // Tambah 1 placeholder '?' lagi
            );
            
            // Bind parameter (13 parameter: ssissssssssss)
            $stmt->bind_param("ssissssssssss", 
                $namaEvent, $pengajuTipe, $ditmawa_id, $tipeKegiatan, 
                $tanggalMulai, $tanggalSelesai, $jamMulai, $jamSelesai, 
                $tanggalPersiapan, $tanggalBeres, 
                $status_ditmawa, $status_asp, $status_proposal
            );
            
            $stmt->execute();
            $pengajuan_id = $stmt->insert_id; // Dapatkan ID event yang baru dibuat
            $stmt->close();
            
            // Insert data peminjaman ruangan jika ada ruangan yang dipilih
            if (!empty($selected_ruangan_ids) && is_array($selected_ruangan_ids)) {
                $stmt_ruangan = $conn->prepare("INSERT INTO peminjaman_ruangan (pengajuan_id, ruangan_id) VALUES (?, ?)");
                foreach ($selected_ruangan_ids as $ruangan_id) {
                    $stmt_ruangan->bind_param("ii", $pengajuan_id, $ruangan_id);
                    $stmt_ruangan->execute();
                }
                $stmt_ruangan->close();
            }

            $conn->commit(); // Simpan perubahan jika semua berhasil
            $message = "Event institusional berhasil dibuat. Status Ditmawa otomatis disetujui.";
            $message_type = 'success';

             $_POST = array(); 

        } catch (Exception $e) {
            $conn->rollback(); // Batalkan perubahan jika ada error
            $message = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
            $message_type = 'error';
            error_log("Error insert event Ditmawa: " . $e->getMessage()); // Log error untuk debug
        }
    }
}

// Ambil data gedung untuk ditampilkan di form
if (!isset($conn) || $conn->connect_errno) {
     include '../config/db_connection.php'; 
}
$gedung_options = [];
// [PERBAIKAN] Mengurutkan secara "natural" (Gedung 0-10 dulu, baru sisanya)
$result_gedung = $conn->query("
    SELECT gedung_id, gedung_nama 
    FROM gedung 
    ORDER BY
        -- 1. Pisahkan antara yang nama 'Gedung' dan yang bukan
        CASE 
            WHEN gedung_nama LIKE 'Gedung %' THEN 1
            ELSE 2
        END ASC,
        -- 2. Urutkan yang 'Gedung' berdasarkan angkanya
        CAST(SUBSTRING(gedung_nama FROM 8) AS UNSIGNED) ASC,
        -- 3. Urutkan sisanya (misal: 'Merdeka', 'Parkiran') secara alfabetis
        gedung_nama ASC
");if ($result_gedung) {
    while ($row = $result_gedung->fetch_assoc()) {
        $gedung_options[] = $row;
    }
    $result_gedung->free();
} else {
     error_log("Error fetching gedung: " . $conn->error); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Event - Ditmawa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        /* CSS Lengkap dari versi sebelumnya */
        :root { 
            --ditmawa-primary: #ff8c00; 
            --ditmawa-secondary: #e67e00; 
            --text-dark: #2c3e50; 
            --text-light: #555;
            --light-gray: #f8f9fa;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center center; background-repeat: no-repeat; background-attachment: fixed; display: flex; flex-direction: column; min-height: 100vh; }
        .main-container { flex: 1; padding-top: 120px; padding-bottom: 40px; }
        .form-container { max-width: 900px; margin: 0 auto; padding: 30px; background: rgba(255, 255, 255, 0.95); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: var(--text-dark); margin-bottom: 10px;}
        .form-container .subtitle { text-align:center; color:#777; margin-bottom: 30px; }
        .form-section h2 { border-bottom: 2px solid var(--ditmawa-primary); padding-bottom: 10px; margin-bottom: 20px; color: var(--text-dark); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn-submit { display: block; width: 100%; padding: 15px; background-color: var(--ditmawa-primary); color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; transition: background-color 0.3s; font-weight: bold; margin-top: 20px;}
        .btn-submit:hover { background-color: var(--ditmawa-secondary); }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #fff; text-align: center; font-weight: bold; }
        .alert.success { background-color: #28a745; }
        .alert.error { background-color: #dc3545; }
        .checkbox-placeholder { background-color: #f8f9fa; border-radius: 5px; padding: 15px; color: #6c757d; border: 1px dashed #dee2e6; text-align: center; }
        .checkbox-group-modern { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .checkbox-item { display: flex; align-items: center; position: relative; }
        .checkbox-item input[type="checkbox"] { opacity: 0; position: absolute; }
        .checkbox-item label { display: flex; align-items: center; cursor: pointer; color: #495057; transition: color 0.2s; } /* Tambah transisi */
        .checkbox-item label::before { content: ''; width: 20px; height: 20px; border: 2px solid #adb5bd; border-radius: 4px; margin-right: 12px; transition: all 0.2s ease; flex-shrink: 0; }
        .checkbox-item input[type="checkbox"]:checked + label::before { background-color: var(--ditmawa-primary); border-color: var(--ditmawa-primary); background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e"); background-position: center; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid var(--ditmawa-primary); border-radius: 50%; width: 20px; height: 20px; animation: spin 2s linear infinite; display: none; margin-left: 10px; vertical-align: middle; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(249, 249, 249); }  
        .icon { font-size: 20px; cursor: pointer; color: white; }
        a { text-decoration: none; }
        .page-footer { background-color: #ff8c00; color: #fff; padding: 40px 0; margin-top: auto; } 
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #2c3e50; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
        .navbar-right a[href="logout.php"] .icon { color: black; transition: color 0.3s; }
        .konflik-message { color: #dc3545; font-size: 0.8em; font-style: italic; margin-left: 5px; display: block; }
        .checkbox-item input[type="checkbox"]:disabled + label { cursor: not-allowed; color: #adb5bd !important; }
        .checkbox-item input[type="checkbox"]:disabled + label::before { background-color: #e9ecef !important; border-color: #adb5bd !important; background-image: none !important; }
        
        .form-group input.flatpickr-input {
            background-color: #ffffff;
            cursor: pointer;
        }
        input[type="date"], input[type="time"] {
            position: relative;
        }

        /* [PERUBAHAN BARU] Kustomisasi Tema Flatpickr agar Sesuai Tema Ditmawa */
        .flatpickr-calendar {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.15);
            border: 1px solid #ddd;
        }
        .flatpickr-months .flatpickr-month {
            color: var(--text-dark);
            fill: var(--text-dark);
        }
        .flatpickr-months .flatpickr-prev-month:hover svg,
        .flatpickr-months .flatpickr-next-month:hover svg {
            fill: var(--ditmawa-primary);
        }
        .flatpickr-weekdays {
            background: var(--light-gray, #f8f9fa);
        }
        span.flatpickr-weekday {
            color: var(--text-light, #555);
            font-weight: 600;
        }
        .flatpickr-day.selected, 
        .flatpickr-day.startRange, 
        .flatpickr-day.endRange {
            background: var(--ditmawa-primary);
            border-color: var(--ditmawa-primary);
            color: #fff;
        }
        .flatpickr-day:hover {
            background: #fdf0e1; /* Light orange hover */
            border-color: #fdf0e1;
            color: var(--text-dark);
        }
        .flatpickr-day.today {
            border-color: var(--ditmawa-secondary);
        }
        .flatpickr-day.today:hover {
            background: var(--ditmawa-secondary);
            border-color: var(--ditmawa-secondary);
            color: #fff;
        }
        .flatpickr-day.disabled, 
        .flatpickr-day.disabled:hover {
            color: #ccc;
            background: #f8f8f8;
        }
        /* Time Picker */
        .flatpickr-time {
            border-top: 1px solid #ddd;
        }
        .flatpickr-time .numInputWrapper span.arrowUp:after,
        .flatpickr-time .numInputWrapper span.arrowDown:after {
            border-color: var(--text-dark);
        }
        .flatpickr-time .numInputWrapper span.arrowUp:hover:after,
        .flatpickr-time .numInputWrapper span.arrowDown:hover:after {
            border-color: var(--ditmawa-primary);
        }
        .flatpickr-time input.numInput {
            color: var(--text-dark);
            font-weight: 600;
        }
        .flatpickr-time input.numInput:focus {
            border-color: var(--ditmawa-primary);
        }
        /* [AKHIR PERUBAHAN] */

    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="navbar-left">
                <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
                <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
            </div>
            <ul class="navbar-menu">
                 <li><a href="ditmawa_dashboard.php">Home</a></li>
                <li><a href="ditmawa_pengajuan.php" class="active">Form Pengajuan</a></li>
                <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
                <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
                <li><a href="ditmawa_kalender_gabungan.php">Kalender Gabungan</a></li>
                <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
                 <li><a href="ditmawa_import_jadwal.php">Import Jadwal</a></li> 
                 <li><a href="ditmawa_laporan.php">Laporan</a></li>
            </ul>
            <div class="navbar-right">
                    <a href="ditmawa_profile.php" style="display: flex; align-items: center; gap: 10px; color: white; text-decoration: none;">
                    <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
                    <i class="fas fa-user-circle icon"></i>
                </a>
                <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <div class="form-container">
            <h1>Formulir Pembuatan Event Institusional</h1>
            <p class="subtitle">Event yang dibuat di sini akan otomatis disetujui oleh Ditmawa dan masuk ke antrian persetujuan ASP.</p>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form action="ditmawa_pengajuan.php" method="POST" id="event-form">
                 <div class="form-section">
                    <h2>Detail Event</h2>
                    <div class="form-group">
                        <label for="nama_event">Nama Event</label>
                        <input type="text" id="nama_event" name="nama_event" placeholder="Contoh: Rapat Koordinasi Awal Semester" required value="<?= htmlspecialchars($_POST['nama_event'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipe_kegiatan_select">Tipe Kegiatan</label>
                        <select id="tipe_kegiatan_select" name="tipe_kegiatan_select" required>
                            <option value="">-- Pilih Tipe --</option>
                            <option value="Institusional" <?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Institusional' ? 'selected' : '' ?>>Institusional</option>
                            <option value="Rapat" <?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Rapat' ? 'selected' : '' ?>>Rapat</option>
                            <option value="Seminar" <?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                            <option value="Workshop" <?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                            <option value="Lainnya" <?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group" id="lainnya_container" style="display:<?= ($_POST['tipe_kegiatan_select'] ?? '') == 'Lainnya' ? 'block' : 'none' ?>;">
                        <label for="tipe_kegiatan_lainnya">Sebutkan Tipe Kegiatan Lainnya</label>
                        <input type="text" id="tipe_kegiatan_lainnya" name="tipe_kegiatan_lainnya" placeholder="Contoh: Pelatihan Internal Staff" value="<?= htmlspecialchars($_POST['tipe_kegiatan_lainnya'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Jadwal dan Ruangan</h2>
                     <div class="form-row">
                        <div class="form-group"><label for="tanggal_mulai">Tanggal Mulai Event</label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" required placeholder="Pilih Tanggal Mulai" value="<?= htmlspecialchars($_POST['tanggal_mulai'] ?? '') ?>">
                        </div>
                        <div class="form-group"><label for="tanggal_selesai">Tanggal Selesai Event</label>
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai" required placeholder="Pilih Tanggal Selesai" value="<?= htmlspecialchars($_POST['tanggal_selesai'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="jam_mulai">Jam Mulai</label>
                            <input type="time" id="jam_mulai" name="jam_mulai" required placeholder="Pilih Jam Mulai" value="<?= htmlspecialchars($_POST['jam_mulai'] ?? '') ?>">
                        </div>
                        <div class="form-group"><label for="jam_selesai">Jam Selesai</label>
                            <input type="time" id="jam_selesai" name="jam_selesai" required placeholder="Pilih Jam Selesai" value="<?= htmlspecialchars($_POST['jam_selesai'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label for="tanggal_persiapan">Tgl Persiapan (Opsional)</label>
                            <input type="date" id="tanggal_persiapan" name="tanggal_persiapan" placeholder="Pilih Tanggal Persiapan" value="<?= htmlspecialchars($_POST['tanggal_persiapan'] ?? '') ?>">
                        </div>
                        <div class="form-group"><label for="tanggal_beres">Tgl Pembongkaran (Opsional)</label>
                            <input type="date" id="tanggal_beres" name="tanggal_beres" placeholder="Pilih Tanggal Pembongkaran" value="<?= htmlspecialchars($_POST['tanggal_beres'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Gedung</label>
                        <div id="gedung_selection" class="checkbox-group-modern">
                            <?php foreach ($gedung_options as $gedung): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>" <?= (isset($_POST['gedung_ids']) && in_array($gedung['gedung_id'], $_POST['gedung_ids'])) ? 'checked' : '' ?>>
                                    <label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Lantai <span class="loader" id="lantai_loader"></span></label>
                        <div id="lantai_selection_container"><div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div></div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Ruangan <span class="loader" id="ruangan_loader"></span></label>
                        <div id="ruangan_selection_container"><div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div></div>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Buat Event</button>
            </form>
        </div>
    </div>

     <footer class="page-footer">
        <div class="footer-container">
            <div class="footer-left"><img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
                <div><h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4><h3 style="font-weight: bold; margin-top: 5px;color :black">DIREKTORAT KEMAHASISWAAN</h3></div>
            </div>
            <div class="footer-right">
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                    <li><i class="fas fa-phone-alt"></i> (022) 203 2655 ext. 100140</li>
                    <li><i class="fas fa-envelope"></i> kemahasiswaan@unpar.ac.id</li>
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
    
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
    // --- Atur Tanggal Minimum ---
     document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const minDate = `${year}-${month}-${day}`;
        
        // --- Inisialisasi Flatpickr untuk Tanggal ---
        const tglMulaiPicker = flatpickr("#tanggal_mulai", {
            dateFormat: "Y-m-d",
            minDate: minDate,
            onChange: function(selectedDates, dateStr, instance) {
                if(tglSelesaiPicker) {
                    tglSelesaiPicker.set('minDate', dateStr);
                }
                checkKonflik(); 
            }
        });

        const tglSelesaiPicker = flatpickr("#tanggal_selesai", {
            dateFormat: "Y-m-d",
            minDate: document.getElementById('tanggal_mulai').value || minDate, 
            onChange: function() { checkKonflik(); }
        });
        
         flatpickr("#tanggal_persiapan", { dateFormat: "Y-m-d", minDate: minDate });
         flatpickr("#tanggal_beres", { dateFormat: "Y-m-d", minDate: minDate });

        // --- Inisialisasi Flatpickr untuk Jam ---
        flatpickr("#jam_mulai", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            onChange: function() { checkKonflik(); }
        });

        flatpickr("#jam_selesai", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            onChange: function() { checkKonflik(); }
        });

        // Trigger fetch lantai/ruangan jika form sudah terisi
        const initialGedungChecked = document.querySelectorAll('#gedung_selection input:checked').length > 0;
        if(initialGedungChecked) {
             handleGedungChange(); 
        }
    });

    // --- Validasi Submit Form ---
    document.getElementById('event-form').addEventListener('submit', function(event) {
        const stopSubmission = (message, element) => {
            alert('Validasi Gagal: ' + message);
            if (element) { element.focus(); }
            event.preventDefault();
        };

        const namaEventInput = document.getElementById('nama_event');
        if (namaEventInput.value.trim().length < 5) { return stopSubmission('Nama event harus terdiri dari minimal 5 karakter.', namaEventInput); }

        const tipeSelect = document.getElementById('tipe_kegiatan_select');
        const tipeLainnyaInput = document.getElementById('tipe_kegiatan_lainnya');
        if (tipeSelect.value === 'Lainnya' && tipeLainnyaInput.value.trim() === '') { return stopSubmission('Anda memilih "Lainnya", maka wajib menyebutkan tipe kegiatannya.', tipeLainnyaInput); }
        
        const tglMulai = document.getElementById('tanggal_mulai').value;
        const tglSelesai = document.getElementById('tanggal_selesai').value;
        const jamMulai = document.getElementById('jam_mulai').value;
        const jamSelesai = document.getElementById('jam_selesai').value;
        const tglPersiapan = document.getElementById('tanggal_persiapan').value;
        const tglBeres = document.getElementById('tanggal_beres').value;

         if (!tglMulai || !tglSelesai || !jamMulai || !jamSelesai) {
             return stopSubmission('Tanggal Mulai/Selesai dan Jam Mulai/Selesai wajib diisi.');
         }

        if (tglSelesai < tglMulai) { return stopSubmission('Tanggal Selesai Event tidak boleh mendahului Tanggal Mulai Event.', document.getElementById('tanggal_selesai')); }
        if (tglMulai === tglSelesai && jamSelesai <= jamMulai) { return stopSubmission('Untuk event di hari yang sama, Jam Selesai harus setelah Jam Mulai.', document.getElementById('jam_selesai')); }
        if (tglBeres && tglSelesai && tglBeres < tglSelesai) { return stopSubmission('Tanggal Selesai Pembongkaran tidak boleh mendahului Tanggal Selesai Event.', document.getElementById('tanggal_beres')); }
        if (tglPersiapan && tglMulai && tglPersiapan > tglMulai) { return stopSubmission('Tanggal Mulai Persiapan tidak boleh setelah Tanggal Mulai Event.', document.getElementById('tanggal_persiapan')); }

        const gedungCheckedCount = document.querySelectorAll('input[name="gedung_ids[]"]:checked').length;
        if (gedungCheckedCount > 0) { 
            const lantaiIsChecked = document.querySelectorAll('#lantai_selection input:checked').length > 0;
            if (!lantaiIsChecked && document.getElementById('lantai_selection')) { return stopSubmission('Anda telah memilih Gedung, maka wajib memilih minimal satu Lantai.'); }
            
            const ruanganIsChecked = document.querySelectorAll('#ruangan_selection input:checked').length > 0;
            const ruanganDisplayed = document.getElementById('ruangan_selection');
            if(ruanganDisplayed && ruanganIsChecked === 0) {
                 return stopSubmission('Anda telah memilih Lantai, maka wajib memilih minimal satu Ruangan.');
            }
             const konflikRuangan = document.querySelectorAll('#ruangan_selection input:checked:disabled');
             if (konflikRuangan.length > 0) {
                return stopSubmission('Ada ruangan yang Anda pilih sedang tidak tersedia (konflik jadwal). Harap batalkan pilihan pada ruangan tersebut atau ubah jadwal Anda.');
             }
        }
    });

    // --- Dropdown 'Lainnya' ---
    const tipeKegiatanSelect = document.getElementById('tipe_kegiatan_select');
    const lainnyaContainer = document.getElementById('lainnya_container');
    const lainnyaInput = document.getElementById('tipe_kegiatan_lainnya');
    tipeKegiatanSelect.addEventListener('change', function() {
        if (this.value === 'Lainnya') {
            lainnyaContainer.style.display = 'block';
            lainnyaInput.required = true;
        } else {
            lainnyaContainer.style.display = 'none';
            lainnyaInput.required = false;
            lainnyaInput.value = '';
        }
    });

    // --- Dynamic Checkbox & Cek Konflik Logic ---
    const gedungSelection = document.getElementById('gedung_selection');
    const lantaiContainer = document.getElementById('lantai_selection_container');
    const ruanganContainer = document.getElementById('ruangan_selection_container');
    const lantaiLoader = document.getElementById('lantai_loader');
    const ruanganLoader = document.getElementById('ruangan_loader');
    
    gedungSelection.addEventListener('change', handleGedungChange); 

     // Variabel global untuk menyimpan state checkbox
    let initialLantaiState = <?= json_encode($_POST['lantai_ids'] ?? []) ?>;
    let initialRuanganState = <?= json_encode($_POST['ruangan_ids'] ?? []) ?>;

     function handleGedungChange() {
        const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input:checked')).map(cb => cb.value);
        lantaiContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div>';
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
        const oldLantaiSelection = document.getElementById('lantai_selection');
        if (oldLantaiSelection) oldLantaiSelection.removeEventListener('change', handleLantaiChange);
        
        resetKonflikUI(); 
        
        if (selectedGedungIds.length > 0) fetchLantai(selectedGedungIds);
        else checkKonflik(); 
    }

    function fetchLantai(gedungIds) {
        lantaiLoader.style.display = 'inline-block';
        const queryString = gedungIds.map(id => `gedung_ids[]=${id}`).join('&');
        fetch(`get_lantai.php?${queryString}`) 
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '<div id="lantai_selection" class="checkbox-group-modern">';
                    data.forEach(lantai => {
                        const isChecked = initialLantaiState.includes(String(lantai.lantai_id));
                        html += `
                            <div class="checkbox-item">
                                <input type="checkbox" class="lantai-checkbox" name="lantai_ids[]" value="${lantai.lantai_id}" id="lantai_${lantai.lantai_id}" ${isChecked ? 'checked' : ''}>
                                <label for="lantai_${lantai.lantai_id}">Lantai ${lantai.lantai_nomor} (${lantai.gedung_nama})</label>
                            </div>`;
                    });
                    html += '</div>';
                    lantaiContainer.innerHTML = html;
                    document.getElementById('lantai_selection').addEventListener('change', handleLantaiChange);
                    handleLantaiChange(); 
                } else {
                    lantaiContainer.innerHTML = '<div class="checkbox-placeholder"><p>Tidak ada lantai ditemukan.</p></div>';
                }
            })
            .catch(error => { console.error('Error fetching lantai:', error); lantaiContainer.innerHTML = '<div class="checkbox-placeholder"><p style="color: red;">Gagal memuat data lantai.</p></div>'; })
            .finally(() => { lantaiLoader.style.display = 'none'; }); 
    }

    function handleLantaiChange() { 
        const selectedLantaiIds = Array.from(document.querySelectorAll('#lantai_selection input:checked')).map(cb => cb.value);
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
         resetKonflikUI(); 
        if (selectedLantaiIds.length > 0) fetchRuangan(selectedLantaiIds);
        else checkKonflik(); 
    }

    function fetchRuangan(lantaiIds) { 
        ruanganLoader.style.display = 'inline-block';
        const queryString = lantaiIds.map(id => `lantai_ids[]=${id}`).join('&');
        fetch(`get_ruangan.php?${queryString}`) 
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '<div id="ruangan_selection" class="checkbox-group-modern">';
                    data.forEach(ruangan => {
                        const isChecked = initialRuanganState.includes(String(ruangan.ruangan_id));
                        html += `
                            <div class="checkbox-item">
                                <input type="checkbox" name="ruangan_ids[]" value="${ruangan.ruangan_id}" id="ruangan_${ruangan.ruangan_id}" class="ruangan-checkbox" ${isChecked ? 'checked' : ''}>
                                <label for="ruangan_${ruangan.ruangan_id}">${ruangan.ruangan_nama} (Lantai ${ruangan.lantai_nomor}, ${ruangan.gedung_nama})</label>
                                <span class="konflik-message" id="konflik_ruangan_${ruangan.ruangan_id}"></span>
                            </div>`;
                    });
                    html += '</div>';
                    ruanganContainer.innerHTML = html;
                     document.getElementById('ruangan_selection').addEventListener('change', checkKonflik); 
                     initialLantaiState = []; 
                     initialRuanganState = [];
                } else {
                    ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p>Tidak ada ruangan tersedia.</p></div>';
                }
            })
            .catch(error => { console.error('Error fetching ruangan:', error); ruanganContainer.innerHTML = '<div class="checkbox-placeholder"><p style="color: red;">Gagal memuat data ruangan.</p></div>'; })
            .finally(() => { ruanganLoader.style.display = 'none'; checkKonflik(); }); 
    }

    let conflictCheckTimeout; 

    function checkKonflik() { 
        clearTimeout(conflictCheckTimeout);
        conflictCheckTimeout = setTimeout(() => {
            // Ambil nilai dari input, meskipun inputnya dari Flatpickr, .value tetap berfungsi
            const tanggalMulai = document.getElementById('tanggal_mulai').value;
            const tanggalSelesai = document.getElementById('tanggal_selesai').value;
            const jamMulai = document.getElementById('jam_mulai').value;
            const jamSelesai = document.getElementById('jam_selesai').value;
            const selectedRuanganCheckboxes = document.querySelectorAll('#ruangan_selection input.ruangan-checkbox');
            
            if (!tanggalMulai || !tanggalSelesai || !jamMulai || !jamSelesai || selectedRuanganCheckboxes.length === 0) {
                 resetKonflikUI(); return;
            }
            
             const allRuanganIds = Array.from(selectedRuanganCheckboxes).map(cb => cb.value);

            const formData = new FormData();
            formData.append('tanggal_mulai', tanggalMulai);
            formData.append('tanggal_selesai', tanggalSelesai);
            formData.append('jam_mulai', jamMulai);
            formData.append('jam_selesai', jamSelesai);
            allRuanganIds.forEach(id => formData.append('ruangan_ids[]', id));

             resetKonflikUI(); 

            fetch('cek_konflik_jadwal.php', { method: 'POST', body: formData }) 
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => updateKonflikUI(data.konflik))
            .catch(error => { console.error('Error checking konflik:', error); alert('Gagal memeriksa ketersediaan ruangan. Periksa koneksi atau hubungi admin.'); })
        }, 500); 
    }

    function resetKonflikUI() { 
         const ruanganCheckboxes = document.querySelectorAll('#ruangan_selection input.ruangan-checkbox');
         ruanganCheckboxes.forEach(checkbox => {
             checkbox.disabled = false; 
             const konflikSpan = document.getElementById(`konflik_${checkbox.id}`); 
              if (konflikSpan) konflikSpan.textContent = ''; 
              else { 
                  const spanByCheckboxId = document.getElementById(`konflik_ruangan_${checkbox.value}`);
                  if(spanByCheckboxId) spanByCheckboxId.textContent = '';
              }
         });
    }

    function updateKonflikUI(konflikList) { 
        konflikList.forEach(konflik => {
            const checkbox = document.getElementById(`ruangan_${konflik.ruangan_id}`);
            if (checkbox) {
                checkbox.disabled = true; 
                checkbox.checked = false; // Otomatis uncheck jika konflik
                const konflikSpan = document.getElementById(`konflik_ruangan_${konflik.ruangan_id}`);
                if (konflikSpan) {
                     let detailSingkat = konflik.detail;
                     if (konflik.tipe === 'kelas') detailSingkat = konflik.detail.replace(/ - .+$/, ''); 
                     else if (konflik.tipe === 'event') detailSingkat = konflik.detail.replace(/ - .+$/, '');
                    konflikSpan.textContent = `(Dipakai: ${detailSingkat})`; 
                }
            }
        });
    }
    // --- AKHIR LOGIKA CEK KONFLIK ---

</script>

</body>
</html>