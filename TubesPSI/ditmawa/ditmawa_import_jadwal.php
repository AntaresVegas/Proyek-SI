<?php
session_start();

// Autentikasi Ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$nama_ditmawa = $_SESSION['nama'] ?? 'Staff Ditmawa';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Import Jadwal Kelas - Ditmawa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --ditmawa-primary: #ff8c00; --ditmawa-secondary: #e67e00; --text-dark: #2c3e50; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center center; background-repeat: no-repeat; background-attachment: fixed; display: flex; flex-direction: column; min-height: 100vh; padding-top: 80px; }
        .main-container { flex: 1; padding-bottom: 40px; }
        .form-container { max-width: 700px; margin: 40px auto; padding: 30px; background: rgba(255, 255, 255, 0.95); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: var(--text-dark); margin-bottom: 25px;}
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input[type="text"], .form-group input[type="file"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        .btn-submit { display: block; width: 100%; padding: 15px; background-color: var(--ditmawa-primary); color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; transition: background-color 0.3s; font-weight: bold; margin-top: 20px;}
        .btn-submit:hover { background-color: var(--ditmawa-secondary); }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #fff; text-align: center; font-weight: bold; }
        .alert.success { background-color: #28a745; }
        .alert.error { background-color: #dc3545; }
        .notes { background-color: #e9ecef; border-left: 5px solid var(--ditmawa-primary); padding: 15px; margin-top: 25px; border-radius: 5px; font-size: 14px; color: #495057; }
        .notes ul { margin-left: 20px; padding-left: 10px; }
        .notes li { margin-bottom: 8px; }

         .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; object-fit: cover; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; font-size: 15px; }
        .navbar-menu li a:hover, .navbar-menu li a.active { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; font-size: 15px; color:rgb(255, 255, 255); }
        .user-name { font-weight: 500; }
        .icon { font-size: 20px; cursor: pointer; color: white; }
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

        /* [PERUBAHAN BARU] CSS untuk Custom Checkbox */
        .checkbox-modern {
            display: flex;
            align-items: center;
            position: relative;
        }
        /* Target label di dalam .checkbox-modern */
        .checkbox-modern label {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
            font-weight: 500; /* Samakan dengan label lain */
            color: #555; 
            margin: 0; /* Hapus margin bawaan label */
        }
        /* Sembunyikan checkbox asli */
        .checkbox-modern input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 1px;
            height: 1px;
        }
        /* Buat kotak checkbox kustom */
        .checkbox-modern label::before {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #adb5bd;
            border-radius: 4px;
            margin-right: 12px;
            transition: all 0.2s ease;
            background-color: #fff;
            flex-shrink: 0;
        }
        /* Ganti warna saat di-hover */
        .checkbox-modern:hover label::before {
            border-color: var(--ditmawa-primary);
        }
        /* Tampilan saat dicentang */
        .checkbox-modern input[type="checkbox"]:checked + label::before {
            background-color: var(--ditmawa-primary);
            border-color: var(--ditmawa-primary);
            /* Ikon centang SVG (putih) */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e");
            background-position: center;
        }
        /* Efek focus untuk aksesibilitas */
        .checkbox-modern input[type="checkbox"]:focus + label::before {
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.3);
        }
        /* [AKHIR PERUBAHAN BARU] */

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
                <li><a href="ditmawa_pengajuan.php">Form Pengajuan</a></li>
                <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
                <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
                <li><a href="ditmawa_kalender_gabungan.php">Kalender Gabungan</a></li>
                <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
                <li><a href="ditmawa_import_jadwal.php" class="active">Import Jadwal</a></li> 
                <li><a href="ditmawa_laporan.php">Laporan</a></li>
            </ul>
            <div class="navbar-right">
                    <a href="ditmawa_profile.php" style="display: flex; align-items: center; gap: 10px; color: white; text-decoration:none;">
                    <span class="user-name"><?php echo htmlspecialchars($nama_ditmawa); ?></span>
                    <i class="fas fa-user-circle icon"></i>
                </a>
                <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <div class="form-container">
            <h1>Import Jadwal Kelas dari Excel</h1>

            <?php if (isset($_SESSION['import_message'])): ?>
                <div class="alert <?php echo $_SESSION['import_message_type']; ?>">
                    <?php echo $_SESSION['import_message']; ?>
                </div>
                <?php unset($_SESSION['import_message'], $_SESSION['import_message_type']); ?>
            <?php endif; ?>

            <form action="proses_import_jadwal.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="semester_tahun">Semester/Tahun Ajaran:</label>
                    <input type="text" id="semester_tahun" name="semester_tahun" placeholder="Contoh: Ganjil 2025/2026" required>
                </div>
                <div class="form-group">
                    <label for="fileJadwal">Pilih File Excel Jadwal Kelas (.xlsx, .xls):</label>
                    <input type="file" id="fileJadwal" name="fileJadwal" accept=".xlsx, .xls" required>
                </div>
                 
                 <div class="form-group checkbox-modern">
                    <input type="checkbox" id="hapus_jadwal_lama" name="hapus_jadwal_lama" value="1">
                    <label for="hapus_jadwal_lama">Hapus jadwal lama untuk semester ini sebelum import?</label>
                </div>

                <button type="submit" class="btn-submit">Import Jadwal</button>
            </form>

            <div class="notes">
                <strong>Catatan Penting:</strong>
                <ul>
                    <li>Pastikan file Excel Anda memiliki kolom: **Nama Ruangan**, **Hari** (Senin-Sabtu), **Jam Mulai** (HH:MM), **Jam Selesai** (HH:MM), **Nama Matakuliah** (Opsional).</li>
                    <li>Baris pertama diasumsikan sebagai header dan akan dilewati.</li>
                    <li>Nama Ruangan di Excel harus **sama persis** dengan nama ruangan yang ada di sistem database. Jika tidak cocok, baris tersebut akan dilewati.</li>
                     <li>Format jam harus **HH:MM** (contoh: 08:00, 14:30).</li>
                    <li>Jika mencentang "Hapus jadwal lama", semua data jadwal kelas untuk semester/tahun yang dimasukkan akan dihapus terlebih dahulu.</li>
                </ul>
            </div>
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
</body>
</html>