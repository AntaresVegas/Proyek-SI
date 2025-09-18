<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../auth/login.php");
    exit();
}

require_once(__DIR__ . '/../config/db_connection.php');
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$ditmawa_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $namaEvent = trim($_POST['nama_event']); // Ambil dan bersihkan spasi

    // ================================================
    // ## 1. VALIDASI BACKEND (PHP) ##
    // Cek panjang nama event di sisi server
    // ================================================
    if (strlen($namaEvent) < 5) {
        $message = "Nama event harus terdiri dari minimal 5 karakter.";
        $message_type = 'error';
    } else {
        // Jika validasi lolos, lanjutkan proses ke database
        $tipeKegiatan = $_POST['tipe_kegiatan_select'];
        if ($tipeKegiatan === 'Lainnya') {
            $tipeKegiatan = !empty($_POST['tipe_kegiatan_lainnya']) ? $_POST['tipe_kegiatan_lainnya'] : 'Lainnya';
        }
        
        $tanggalMulai = $_POST['tanggal_mulai'];
        $tanggalSelesai = $_POST['tanggal_selesai'];
        $jamMulai = $_POST['jam_mulai'];
        $jamSelesai = $_POST['jam_selesai'];
        $tanggalPersiapan = !empty($_POST['tanggal_persiapan']) ? $_POST['tanggal_persiapan'] : NULL;
        $tanggalBeres = !empty($_POST['tanggal_beres']) ? $_POST['tanggal_beres'] : NULL;
        $selected_ruangan_ids = isset($_POST['ruangan_ids']) ? $_POST['ruangan_ids'] : [];

        $status = 'Disetujui';
        $pengajuTipe = 'ditmawa';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                "INSERT INTO pengajuan_event (pengajuan_namaEvent, pengaju_tipe, pengaju_id, pengajuan_TypeKegiatan, pengajuan_event_tanggal_mulai, pengajuan_event_tanggal_selesai, pengajuan_event_jam_mulai, pengajuan_event_jam_selesai, tanggal_persiapan, tanggal_beres, pengajuan_status, pengajuan_tanggalEdit, pengajuan_tanggalApprove) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->bind_param("ssissssssss", $namaEvent, $pengajuTipe, $ditmawa_id, $tipeKegiatan, $tanggalMulai, $tanggalSelesai, $jamMulai, $jamSelesai, $tanggalPersiapan, $tanggalBeres, $status);
            $stmt->execute();
            $pengajuan_id = $stmt->insert_id;
            $stmt->close();
            
            if (!empty($selected_ruangan_ids)) {
                $stmt_ruangan = $conn->prepare("INSERT INTO peminjaman_ruangan (pengajuan_id, ruangan_id) VALUES (?, ?)");
                foreach ($selected_ruangan_ids as $ruangan_id) {
                    $stmt_ruangan->bind_param("ii", $pengajuan_id, $ruangan_id);
                    $stmt_ruangan->execute();
                }
                $stmt_ruangan->close();
            }

            $conn->commit();
            $message = "Event berhasil dibuat dan otomatis disetujui.";
            $message_type = 'success';

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Terjadi kesalahan: " . $e->getMessage();
            $message_type = 'error';
        }
    } // Akhir dari blok 'else' validasi
    $conn->close();
}

// Ambil data gedung untuk checkboxes
include '../config/db_connection.php';
$gedung_options = [];
$result_gedung = $conn->query("SELECT gedung_id, gedung_nama FROM gedung ORDER BY CAST(SUBSTRING(gedung_nama, 7) AS UNSIGNED) ASC");
while ($row = $result_gedung->fetch_assoc()) {
    $gedung_options[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Event - Ditmawa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS tetap sama seperti sebelumnya, tidak perlu diubah */
        :root { --ditmawa-primary: #ff8c00; --ditmawa-secondary: #e67e00; --text-dark: #2c3e50; }
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
        .checkbox-item label { display: flex; align-items: center; cursor: pointer; color: #495057; }
        .checkbox-item label::before { content: ''; width: 20px; height: 20px; border: 2px solid #adb5bd; border-radius: 4px; margin-right: 12px; transition: all 0.2s ease; flex-shrink: 0; }
        .checkbox-item input[type="checkbox"]:checked + label::before { background-color: var(--ditmawa-primary); border-color: var(--ditmawa-primary); background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e"); background-position: center; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid var(--ditmawa-primary); border-radius: 50%; width: 20px; height: 20px; animation: spin 2s linear infinite; display: none; margin-left: 10px; vertical-align: middle; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .navbar { display: flex; justify-content: space-between; align-items: center; background: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color: white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: var(--text-dark); }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .navbar-right .user-name { color: white; }
        .navbar-menu li a.active { color: #007bff; }    
        .icon { font-size: 20px; cursor: pointer; color: white; }
        a { text-decoration: none; }
        .page-footer { background-color: #ff8c00; color: #fff; padding: 40px 0; margin-top: 40px; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
            .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
            .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
            .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
            .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
            .footer-right .social-icons a { color: #2c3e50; font-size: 1.5em; transition: color 0.3s; }
            .footer-right .social-icons a:hover { color: #fff; }
        .navbar-right a[href="logout.php"] .icon {
            color: black;
            transition: color 0.3s; /* Opsional: agar perubahan warna saat hover lebih halus */
        }

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
                <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
                <li><a href="ditmawa_laporan.php">Laporan</a></li>
            </ul>
            <div class="navbar-right">
                <a href="ditmawa_profile.php" style="display: flex; align-items: center; gap: 10px;">
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
            <p class="subtitle">Event yang dibuat di sini akan otomatis disetujui dan masuk ke kalender institusional.</p>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form action="ditmawa_pengajuan.php" method="POST" id="event-form">
                 <div class="form-section">
                    <h2>Detail Event</h2>
                    <div class="form-group">
                        <label for="nama_event">Nama Event</label>
                        <input type="text" id="nama_event" name="nama_event" 
                               placeholder="Contoh: Rapat Koordinasi Awal Semester" 
                               required 
                               minlength="5" 
                               title="Nama event harus terdiri dari minimal 5 karakter.">
                    </div>
                    <div class="form-group">
                        <label for="tipe_kegiatan_select">Tipe Kegiatan</label>
                        <select id="tipe_kegiatan_select" name="tipe_kegiatan_select" required>
                            <option value="Institusional">Institusional</option>
                            <option value="Rapat">Rapat</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group" id="lainnya_container" style="display:none;">
                        <label for="tipe_kegiatan_lainnya">Sebutkan Tipe Kegiatan Lainnya</label>
                        <input type="text" id="tipe_kegiatan_lainnya" name="tipe_kegiatan_lainnya" placeholder="Contoh: Pelatihan Internal Staff">
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Jadwal dan Ruangan</h2>
                     <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal_mulai">Tanggal Mulai Event</label>
                            <input type="date" id="tanggal_mulai" name="tanggal_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="tanggal_selesai">Tanggal Selesai Event</label>
                            <input type="date" id="tanggal_selesai" name="tanggal_selesai" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="jam_mulai">Jam Mulai</label>
                            <input type="time" id="jam_mulai" name="jam_mulai" required>
                        </div>
                        <div class="form-group">
                            <label for="jam_selesai">Jam Selesai</label>
                            <input type="time" id="jam_selesai" name="jam_selesai" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tanggal_persiapan">Tgl Mulai Persiapan (Opsional)</label>
                            <input type="date" id="tanggal_persiapan" name="tanggal_persiapan">
                        </div>
                        <div class="form-group">
                            <label for="tanggal_beres">Tgl Selesai Pembongkaran (Opsional)</label>
                            <input type="date" id="tanggal_beres" name="tanggal_beres">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Gedung (bisa lebih dari satu)</label>
                        <div id="gedung_selection" class="checkbox-group-modern">
                            <?php foreach ($gedung_options as $gedung): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="gedung_ids[]" value="<?php echo htmlspecialchars($gedung['gedung_id']); ?>" id="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>">
                                    <label for="gedung_<?php echo htmlspecialchars($gedung['gedung_id']); ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Lantai (bisa lebih dari satu) <span class="loader" id="lantai_loader"></span></label>
                        <div id="lantai_selection_container">
                             <div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pilih Ruangan (bisa lebih dari satu) <span class="loader" id="ruangan_loader"></span></label>
                        <div id="ruangan_selection_container">
                            <div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Buat Event</button>
            </form>
        </div>
    </div>

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
                <div class="social-icons">
                    <a href="https://www.facebook.com/unparofficial" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/unparofficial/" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.youtube.com/channel/UCeIZdD9ul6JGpkSNM0oxcBw/featured" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="https://www.tiktok.com/@unparofficial" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </footer>
    
<script>
    // ================================================
    // ## PENAMBAHAN: VALIDASI FORM DI SISI CLIENT (JAVASCRIPT) ##
    // ================================================
    document.getElementById('event-form').addEventListener('submit', function(event) {
        // --- 1. Validasi Nama Event ---
        const namaEventInput = document.getElementById('nama_event');
        if (namaEventInput.value.trim().length < 5) {
            alert('Validasi Gagal: Nama event harus terdiri dari minimal 5 karakter.');
            namaEventInput.focus();
            event.preventDefault(); // Mencegah form submit
            return;
        }

        // --- 2. Validasi Tanggal dan Jam ---
        const tglMulai = document.getElementById('tanggal_mulai').value;
        const tglSelesai = document.getElementById('tanggal_selesai').value;
        const tglPersiapan = document.getElementById('tanggal_persiapan').value;
        const tglBeres = document.getElementById('tanggal_beres').value;
        const jamMulai = document.getElementById('jam_mulai').value;
        const jamSelesai = document.getElementById('jam_selesai').value;

        if (tglMulai && tglSelesai && tglSelesai < tglMulai) {
            alert('Validasi Gagal: Tanggal Selesai Event tidak boleh mendahului Tanggal Mulai Event.');
            event.preventDefault(); vs Jam Selesai ---
        if (tglMulai && tglSelesai && tglMulai === tglSelesai) {
            if (jamMulai && jamSelesai && jamSelesai < jamMulai) {
                alert('Validasi Gagal: Untuk event di hari yang sama, Jam Selesai tidak boleh lebih awal dari Jam Mulai.');
                event.preventDefault();
                document.getElementById('jam_selesai').focus();
                return;
            return;
        }

        // --- PENAMBAHAN BARU: Validasi Jam Mulai
            }
        }
        // --- AKHIR PENAMBAHAN BARU ---
        
        // Cek tanggal beres-beres
        if (tglBeres) {
            if (tglSelesai && tglBeres < tglSelesai) {
                alert('Validasi Gagal: Tanggal Selesai Pembongkaran tidak boleh mendahului Tanggal Selesai Event.');
                event.preventDefault();
                return;
            }
             if (tglMulai && tglBeres < tglMulai) {
                alert('Validasi Gagal: Tanggal Selesai Pembongkaran tidak boleh mendahului Tanggal Mulai Event.');
                event.preventDefault();
                return;
            }
        }

        // Cek tanggal persiapan
        if (tglPersiapan && tglMulai && tglPersiapan > tglMulai) {
            alert('Validasi Gagal: Tanggal Mulai Persiapan tidak boleh setelah Tanggal Mulai Event.');
            event.preventDefault();
            return;
        }

        // --- 3. Validasi Pemilihan Lokasi ---
        const gedungChecked = document.querySelectorAll('input[name="gedung_ids[]"]:checked').length > 0;
        const lantaiChecked = document.querySelectorAll('input[name="lantai_ids[]"]:checked').length > 0;
        const ruanganChecked = document.querySelectorAll('input[name="ruangan_ids[]"]:checked').length > 0;

        // Jika user mulai memilih lokasi (memilih gedung), maka lantai dan ruangan menjadi wajib.
        // Jika tidak ada gedung yang dipilih, kita asumsikan event tidak memerlukan ruangan.
        if (gedungChecked && (!lantaiChecked || !ruanganChecked)) {
            if (!lantaiChecked) {
                 alert('Validasi Gagal: Anda telah memilih Gedung, silakan pilih minimal satu Lantai.');
            } else { // Ini berarti lantai sudah dipilih, tapi ruangan belum
                 alert('Validasi Gagal: Anda telah memilih Lantai, silakan pilih minimal satu Ruangan.');
            }
            event.preventDefault();
            return;
        }
    });

    // Script lainnya (Tipe Kegiatan & Dynamic Checkbox) tetap sama
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

    const gedungSelection = document.getElementById('gedung_selection');
    const lantaiContainer = document.getElementById('lantai_selection_container');
    const ruanganContainer = document.getElementById('ruangan_selection_container');
    const lantaiLoader = document.getElementById('lantai_loader');
    const ruanganLoader = document.getElementById('ruangan_loader');
    gedungSelection.addEventListener('change', function() {
        const selectedGedungIds = Array.from(gedungSelection.querySelectorAll('input:checked')).map(cb => cb.value);
        lantaiContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Gedung terlebih dahulu.</div>';
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
        const oldLantaiSelection = document.getElementById('lantai_selection');
        if (oldLantaiSelection) {
            oldLantaiSelection.removeEventListener('change', handleLantaiChange);
        }
        if (selectedGedungIds.length > 0) {
            fetchData('lantai', selectedGedungIds);
        }
    });
    function handleLantaiChange() {
        const selectedLantaiIds = Array.from(document.querySelectorAll('#lantai_selection input:checked')).map(cb => cb.value);
        ruanganContainer.innerHTML = '<div class="checkbox-placeholder">Pilih Lantai terlebih dahulu.</div>';
        if (selectedLantaiIds.length > 0) {
            fetchData('ruangan', selectedLantaiIds);
        }
    }
    function fetchData(type, ids) {
        const loader = (type === 'lantai') ? lantaiLoader : ruanganLoader;
        const container = (type === 'lantai') ? lantaiContainer : ruanganContainer;
        const idKey = (type === 'lantai') ? 'gedung_ids' : 'lantai_ids';
        const endpoint = (type === 'lantai') ? 'get_lantai.php' : 'get_ruangan.php';
        loader.style.display = 'inline-block';
        const queryString = ids.map(id => `${idKey}[]=${encodeURIComponent(id)}`).join('&');
        fetch(`${endpoint}?${queryString}`)
            .then(response => {
                if (!response.ok) { throw new Error('Network response was not ok'); }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.length > 0) {
                    let html = `<div id="${type}_selection" class="checkbox-group-modern">`;
                    data.forEach(item => {
                        const id = item[`${type}_id`] || item['ruangan_id'] || item['lantai_id'];
                        const name = (type === 'lantai') ? `Lantai ${item.lantai_nomor} (${item.gedung_nama})` : `${item.ruangan_nama} (Lantai ${item.lantai_nomor}, ${item.gedung_nama})`;
                        const inputName = (type === 'lantai') ? 'lantai_ids[]' : 'ruangan_ids[]';
                        html += `
                            <div class="checkbox-item">
                                <input type="checkbox" name="${inputName}" value="${id}" id="${type}_${id}">
                                <label for="${type}_${id}">${name}</label>
                            </div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                    if (type === 'lantai') {
                        document.getElementById('lantai_selection').addEventListener('change', handleLantaiChange);
                    }
                } else {
                    container.innerHTML = `<div class="checkbox-placeholder">Tidak ada ${type} ditemukan.</div>`;
                }
            })
            .catch(error => {
                console.error(`Error fetching ${type}:`, error);
                container.innerHTML = `<div class="checkbox-placeholder" style="color:red;">Gagal memuat data ${type}. Periksa console untuk detail.</div>`;
            })
            .finally(() => {
                loader.style.display = 'none';
            });
    }
</script>

</body>
</html>