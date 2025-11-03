<?php
session_start();
require_once('../config/db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$ditmawa_nama = $_SESSION['nama'] ?? 'Ditmawa';
$message = '';
$message_type = '';
$active_tab = 'gedung'; // Default tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === KELOLA GEDUNG ===
    if (isset($_POST['add_gedung'])) {
        $active_tab = 'gedung';
        
        // [FIX] Validasi diubah agar menerima '0'
        $gedung_nama_raw = $_POST['gedung_nama'] ?? null;
        $gedung_nama = is_null($gedung_nama_raw) ? null : trim($gedung_nama_raw);
        
        if ($gedung_nama !== null && $gedung_nama !== '') {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM gedung WHERE UPPER(gedung_nama) = UPPER(?)");
            $check_stmt->bind_param("s", $gedung_nama);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $message = "Gagal: Gedung dengan nama '{$gedung_nama}' sudah ada.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO gedung (gedung_nama) VALUES (?)");
                $stmt->bind_param("s", $gedung_nama);
                if ($stmt->execute()) { $message = "Gedung '{$gedung_nama}' berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
                $stmt->close();
            }
        } else {
            $message = "Gagal: Nama Gedung tidak boleh kosong.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_gedung'])) {
        $active_tab = 'gedung';
        $gedung_id = $_POST['gedung_id'];
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM lantai WHERE gedung_id = ?");
        $check_stmt->bind_param("i", $gedung_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();
        if ($count > 0) {
            $message = "Gagal menghapus! Gedung ini masih memiliki data lantai."; $message_type = "error";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM gedung WHERE gedung_id = ?");
            $delete_stmt->bind_param("i", $gedung_id);
            if ($delete_stmt->execute()) { $message = "Gedung berhasil dihapus."; $message_type = "success"; } else { $message = "Gagal: " . $delete_stmt->error; $message_type = "error"; }
            $delete_stmt->close();
        }
    }

    // === KELOLA LANTAI ===
    if (isset($_POST['add_lantai'])) {
        $active_tab = 'lantai';
        
        // [FIX] Validasi diubah agar menerima '0'
        $lantai_nomor_raw = $_POST['lantai_nomor'] ?? null;
        $lantai_nomor = is_null($lantai_nomor_raw) ? null : trim($lantai_nomor_raw);
        $gedung_id = $_POST['gedung_id_for_lantai'] ?? '';
        
        if ($lantai_nomor !== null && $lantai_nomor !== '' && !empty($gedung_id)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM lantai WHERE gedung_id = ? AND UPPER(lantai_nomor) = UPPER(?)");
            $check_stmt->bind_param("is", $gedung_id, $lantai_nomor);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $message = "Gagal: Lantai '{$lantai_nomor}' sudah ada di gedung ini.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO lantai (lantai_nomor, gedung_id) VALUES (?, ?)");
                $stmt->bind_param("si", $lantai_nomor, $gedung_id);
                if ($stmt->execute()) { $message = "Lantai baru berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
                $stmt->close();
            }
        } else {
            $message = "Gagal: Anda harus memilih Gedung dan mengisi Nomor Lantai.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_lantai'])) {
        $active_tab = 'lantai';
        $lantai_id = $_POST['lantai_id'];
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM ruangan WHERE lantai_id = ?");
        $check_stmt->bind_param("i", $lantai_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();
        if ($count > 0) {
            $message = "Gagal menghapus! Lantai ini masih memiliki data ruangan."; $message_type = "error";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM lantai WHERE lantai_id = ?");
            $delete_stmt->bind_param("i", $lantai_id);
            if ($delete_stmt->execute()) { $message = "Lantai berhasil dihapus."; $message_type = "success"; } else { $message = "Gagal: " . $delete_stmt->error; $message_type = "error"; }
            $delete_stmt->close();
        }
    }

    // === KELOLA RUANGAN ===
    if (isset($_POST['add_ruangan'])) {
        $active_tab = 'ruangan';
        
        // [FIX] Validasi diubah agar menerima '0'
        $ruangan_nama_raw = $_POST['ruangan_nama'] ?? null;
        $ruangan_nama = is_null($ruangan_nama_raw) ? null : trim($ruangan_nama_raw);
        $lantai_id = $_POST['lantai_id_for_ruangan'] ?? '';
        
        if ($ruangan_nama !== null && $ruangan_nama !== '' && !empty($lantai_id)) {
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM ruangan WHERE lantai_id = ? AND UPPER(ruangan_nama) = UPPER(?)");
            $check_stmt->bind_param("is", $lantai_id, $ruangan_nama);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            if ($count > 0) {
                $message = "Gagal: Ruangan '{$ruangan_nama}' sudah ada di lantai ini.";
                $message_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO ruangan (ruangan_nama, lantai_id) VALUES (?, ?)");
                $stmt->bind_param("si", $ruangan_nama, $lantai_id);
                if ($stmt->execute()) { $message = "Ruangan baru berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
                $stmt->close();
            }
        } else {
            $message = "Gagal: Anda harus memilih Gedung, Lantai, dan mengisi Nama Ruangan.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['delete_ruangan'])) {
        $active_tab = 'ruangan';
        $ruangan_id = $_POST['ruangan_id'];
        
        $conn->begin_transaction();
        try {
            $check_stmt1 = $conn->prepare("SELECT COUNT(*) FROM peminjaman_ruangan WHERE ruangan_id = ?");
            $check_stmt1->bind_param("i", $ruangan_id); $check_stmt1->execute();
            $check_stmt1->bind_result($count1); $check_stmt1->fetch(); $check_stmt1->close();
            
            $check_stmt2 = $conn->prepare("SELECT COUNT(*) FROM jadwal_kelas WHERE ruangan_id = ?");
            $check_stmt2->bind_param("i", $ruangan_id); $check_stmt2->execute();
            $check_stmt2->bind_result($count2); $check_stmt2->fetch(); $check_stmt2->close();

            if ($count1 > 0 || $count2 > 0) {
                $message = "Gagal menghapus! Ruangan ini terdaftar pada sebuah event atau jadwal kelas."; $message_type = "error";
            } else {
                $delete_stmt = $conn->prepare("DELETE FROM ruangan WHERE ruangan_id = ?");
                $delete_stmt->bind_param("i", $ruangan_id);
                if ($delete_stmt->execute()) { $message = "Ruangan berhasil dihapus."; $message_type = "success"; } else { $message = "Gagal: " . $delete_stmt->error; $message_type = "error"; }
                $delete_stmt->close();
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Gagal: Terjadi error database. " . $e->getMessage(); $message_type = "error";
        }
    }

    header("Location: ditmawa_kelolaRuangan.php?msg=" . urlencode($message) . "&type=" . $message_type . "&tab=" . $active_tab);
    exit();
}

if(isset($_GET['msg'])) { $message = $_GET['msg']; $message_type = $_GET['type']; }
if(isset($_GET['tab'])) { $active_tab = $_GET['tab']; }

$gedung_list = $conn->query("SELECT * FROM gedung ORDER BY CAST(SUBSTRING_INDEX(gedung_nama, ' ', -1) AS UNSIGNED), gedung_nama")->fetch_all(MYSQLI_ASSOC);
$lantai_list = $conn->query("SELECT l.lantai_id, l.lantai_nomor, g.gedung_nama, g.gedung_id FROM lantai l JOIN gedung g ON l.gedung_id = g.gedung_id ORDER BY CAST(SUBSTRING_INDEX(g.gedung_nama, ' ', -1) AS UNSIGNED), g.gedung_nama, l.lantai_nomor ASC")->fetch_all(MYSQLI_ASSOC);
$ruangan_list = $conn->query("SELECT r.ruangan_id, r.ruangan_nama, l.lantai_nomor, l.lantai_id, g.gedung_nama, g.gedung_id FROM ruangan r JOIN lantai l ON r.lantai_id = l.lantai_id JOIN gedung g ON l.gedung_id = g.gedung_id ORDER BY CAST(SUBSTRING_INDEX(g.gedung_nama, ' ', -1) AS UNSIGNED), g.gedung_nama, l.lantai_nomor ASC, r.ruangan_nama ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lokasi - Ditmawa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #ff8c00;
            --primary-dark: #e67e00;
            --secondary-color: #007bff;
            --danger-color: #dc3545;
            --success-color: #198754;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --border-color: #dee2e6;
            --text-dark: #2c3e50;
            --text-light: #495057;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-image: url('../img/backgroundDitmawa.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding-top: 80px;
            display: flex;
            flex-direction: column;
            min-height: 100%;
        }
        .main-content { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; padding: 0; margin: 0; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .icon { font-size: 20px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 15px; }
        .page-header { font-size: 2.5em; color: white; margin-bottom: 25px; text-align: center; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .message { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; font-size: 1em; text-align: center; }
        .message.success { background-color: #d1e7dd; color: #0f5132; }
        .message.error { background-color: #f8d7da; color: #842029; }
        .tabs-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .tab-buttons {
            display: flex;
            background-color: var(--medium-gray);
        }
        .tab-button {
            flex-grow: 1;
            padding: 18px 20px;
            border: none;
            background-color: transparent;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            color: var(--text-light);
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .tab-button:hover {
            background-color: #dcdcdc;
        }
        .tab-button.active {
            color: var(--primary-dark);
            border-color: var(--primary-color);
        }
        .tab-content {
            display: none;
            padding: 30px;
        }
        .tab-content.active {
            display: block;
        }
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
        }
        @media (min-width: 992px) { .content-grid { grid-template-columns: 350px 1fr; } }
        .form-section h3, .table-section h3 {
            font-size: 1.5em;
            color: var(--text-dark);
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-light);
        }
        .form-group select, .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group select:disabled {
            background-color: var(--medium-gray);
            cursor: not-allowed;
        }
        .form-group select:focus, .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.2);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background-color: var(--primary-color); }
        .btn-primary:hover { background-color: var(--primary-dark); }
        .btn-delete {
            background-color: var(--danger-color);
            padding: 6px 12px;
            font-size: 0.9em;
        }
        .btn-delete:hover { background-color: #c82333; }
        .filter-bar {
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
        }
        .filter-bar .form-group {
            flex-grow: 1;
            margin-bottom: 0;
        }
        .table-wrapper { overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
            vertical-align: middle;
        }
        .data-table thead th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--text-dark);
        }
        .data-table tbody tr:hover {
            background-color: #f1f1f1;
        }
        .page-footer { background-color: var(--primary-color); color: #fff; padding: 40px 0; margin-top: 50px; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #2c3e50; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
    </style>
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_pengajuan.php">Form Pengajuan</a></li>
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php" class="active">Kelola Ruangan</a></li>
        <li><a href="ditmawa_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
         <li><a href="ditmawa_import_jadwal.php">Import Jadwal</a></li> 
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($ditmawa_nama); ?></span><i class="fas fa-user-circle icon" style="margin-left:10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <h1 class="page-header">Manajemen Lokasi Kampus</h1>
        <?php if ($message): ?><div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="tabs-container">
            <div class="tab-buttons">
                <button class="tab-button <?php if($active_tab == 'gedung') echo 'active'; ?>" data-tab="gedung"><i class="fas fa-building"></i> Kelola Gedung</button>
                <button class="tab-button <?php if($active_tab == 'lantai') echo 'active'; ?>" data-tab="lantai"><i class="fas fa-layer-group"></i> Kelola Lantai</button>
                <button class="tab-button <?php if($active_tab == 'ruangan') echo 'active'; ?>" data-tab="ruangan"><i class="fas fa-door-open"></i> Kelola Ruangan</button>
            </div>

            <div id="gedung" class="tab-content <?php if($active_tab == 'gedung') echo 'active'; ?>">
                <div class="content-grid">
                    <div class="form-section">
                        <h3>Tambah Gedung Baru</h3>
                        <form action="ditmawa_kelolaRuangan.php" method="POST">
                            <div class="form-group">
                                <label for="gedung_nama">Nama Gedung</label>
                                <input type="text" id="gedung_nama" name="gedung_nama" placeholder="Contoh: Gedung 10" required>
                            </div>
                            <button type="submit" name="add_gedung" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Gedung</button>
                        </form>
                    </div>
                    <div class="table-section">
                        <h3>Daftar Gedung</h3>
                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead><tr><th>Nama Gedung</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php foreach($gedung_list as $gedung): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($gedung['gedung_nama']); ?></td>
                                        <td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus gedung ini?');"><input type="hidden" name="gedung_id" value="<?php echo $gedung['gedung_id']; ?>"><button type="submit" name="delete_gedung" class="btn btn-delete"><i class="fas fa-trash"></i></button></form></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="lantai" class="tab-content <?php if($active_tab == 'lantai') echo 'active'; ?>">
                 <div class="content-grid">
                    <div class="form-section">
                        <h3>Tambah Lantai Baru</h3>
                        <form action="ditmawa_kelolaRuangan.php" method="POST">
                            <div class="form-group">
                                <label for="gedung_id_for_lantai">Pilih Gedung</label>
                                <select id="gedung_id_for_lantai" name="gedung_id_for_lantai" required>
                                    <option value="" disabled selected>-- Pilih Gedung --</option>
                                    <?php foreach ($gedung_list as $gedung): ?><option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lantai_nomor">Nomor/Nama Lantai</label>
                                <input type="text" id="lantai_nomor" name="lantai_nomor" placeholder="Contoh: 1 atau G" required>
                            </div>
                            <button type="submit" name="add_lantai" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Lantai</button>
                        </form>
                    </div>
                    <div class="table-section">
                         <h3>Daftar Lantai</h3>
                         <div class="filter-bar">
                             <div class="form-group">
                                 <label>Filter berdasarkan Gedung:</label>
                                 <select id="filterGedungForLantai">
                                     <option value="">Tampilkan Semua</option>
                                     <?php foreach ($gedung_list as $gedung): ?><option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option><?php endforeach; ?>
                                 </select>
                             </div>
                         </div>
                         <div class="table-wrapper">
                             <table class="data-table">
                                 <thead><tr><th>Nomor Lantai</th><th>Gedung</th><th>Aksi</th></tr></thead>
                                 <tbody id="tableBodyLantai">
                                     <?php foreach($lantai_list as $lantai): ?>
                                     <tr data-gedung-id="<?php echo $lantai['gedung_id']; ?>">
                                         <td><?php echo htmlspecialchars($lantai['lantai_nomor']); ?></td>
                                         <td><?php echo htmlspecialchars($lantai['gedung_nama']); ?></td>
                                         <td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus lantai ini?');"><input type="hidden" name="lantai_id" value="<?php echo $lantai['lantai_id']; ?>"><button type="submit" name="delete_lantai" class="btn btn-delete"><i class="fas fa-trash"></i></button></form></td>
                                     </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                    </div>
                </div>
            </div>

            <div id="ruangan" class="tab-content <?php if($active_tab == 'ruangan') echo 'active'; ?>">
                <div class="content-grid">
                    <div class="form-section">
                        <h3>Tambah Ruangan Baru</h3>
                        <form action="ditmawa_kelolaRuangan.php" method="POST">
                            <div class="form-group">
                                <label for="gedung_id_for_ruangan_add">Langkah 1: Pilih Gedung</label>
                                <select id="gedung_id_for_ruangan_add" required>
                                    <option value="" disabled selected>-- Pilih Gedung --</option>
                                    <?php foreach ($gedung_list as $gedung): ?>
                                        <option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="lantai_id_for_ruangan">Langkah 2: Pilih Lantai</label>
                                <select id="lantai_id_for_ruangan" name="lantai_id_for_ruangan" required disabled>
                                    <option value="" disabled selected>-- Pilih Gedung Dahulu --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ruangan_nama">Langkah 3: Nama/Nomor Ruangan</label>
                                <input type="text" id="ruangan_nama" name="ruangan_nama" placeholder="Contoh: R10317" required>
                            </div>
                            <button type="submit" name="add_ruangan" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Ruangan</button>
                        </form>
                    </div>
                    <div class="table-section">
                        <h3>Daftar Ruangan</h3>
                        <div class="filter-bar">
                            <div class="form-group"><label>Filter Gedung:</label><select id="filterGedungForRuangan"><option value="">Semua</option><?php foreach ($gedung_list as $gedung): ?><option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option><?php endforeach; ?></select></div>
                            <div class="form-group"><label>Filter Lantai:</label><select id="filterLantaiForRuangan" disabled><option value="">Pilih Gedung</option></select></div>
                        </div>
                        <div class="table-wrapper">
                             <table class="data-table">
                                 <thead><tr><th>Nama Ruangan</th><th>Lantai</th><th>Gedung</th><th>Aksi</th></tr></thead>
                                 <tbody id="tableBodyRuangan">
                                     <?php foreach($ruangan_list as $ruangan): ?>
                                     <tr data-gedung-id="<?php echo $ruangan['gedung_id']; ?>" data-lantai-id="<?php echo $ruangan['lantai_id']; ?>">
                                         <td><?php echo htmlspecialchars($ruangan['ruangan_nama']); ?></td>
                                         <td><?php echo htmlspecialchars($ruangan['lantai_nomor']); ?></td>
                                         <td><?php echo htmlspecialchars($ruangan['gedung_nama']); ?></td>
                                         <td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus ruangan ini?');"><input type="hidden" name="ruangan_id" value="<?php echo $ruangan['ruangan_id']; ?>"><button type="submit" name="delete_ruangan" class="btn btn-delete"><i class="fas fa-trash"></i></button></form></td>
                                     </tr>
                                     <?php endforeach; ?>
                                 </tbody>
                             </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // Data ini diambil dari PHP untuk dipakai oleh JavaScript
    const allFloorsData = <?php echo json_encode($lantai_list); ?>;
    
    // --- Logika Sistem Tab ---
    const tabs = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Mengubah URL tanpa reload halaman
            const newUrl = window.location.pathname + '?tab=' + tab.dataset.tab;
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === tab.dataset.tab) {
                    content.classList.add('active');
                }
            });
        });
    });

    // --- Filter untuk Tabel Lantai ---
    const filterGedungLantai = document.getElementById('filterGedungForLantai');
    const tableBodyLantai = document.getElementById('tableBodyLantai').getElementsByTagName('tr');
    filterGedungLantai.addEventListener('change', function() {
        const selectedGedung = this.value;
        for (let row of tableBodyLantai) {
            row.style.display = (selectedGedung === "" || row.dataset.gedungId === selectedGedung) ? "" : "none";
        }
    });

    // --- Filter untuk Tabel Ruangan ---
    const filterGedungRuangan = document.getElementById('filterGedungForRuangan');
    const filterLantaiRuangan = document.getElementById('filterLantaiForRuangan');
    const tableBodyRuangan = document.getElementById('tableBodyRuangan').getElementsByTagName('tr');
    
    filterGedungRuangan.addEventListener('change', function() {
        const selectedGedung = this.value;
        filterLantaiRuangan.innerHTML = '<option value="">Semua Lantai</Tampilkan>';
        filterLantaiRuangan.value = ""; 
        
        if (selectedGedung) {
             const uniqueFloors = {};
             // Filter data lantai berdasarkan gedung_id
             allFloorsData.filter(floor => floor.gedung_id == selectedGedung)
                         .forEach(floor => { uniqueFloors[floor.lantai_id] = floor.lantai_nomor; });
             
             // Buat opsi untuk dropdown lantai
             Object.entries(uniqueFloors).forEach(([lantai_id, lantai_nomor]) => {
                const option = document.createElement('option');
                option.value = lantai_id;
                option.textContent = `Lantai ${lantai_nomor}`;
                filterLantaiRuangan.appendChild(option);
             });
            filterLantaiRuangan.disabled = false;
        } else {
            filterLantaiRuangan.innerHTML = '<option value="">Pilih Gedung Dulu</option>';
            filterLantaiRuangan.disabled = true;
        }
        applyRuanganFilter(); // Terapkan filter setelah mengubah gedung
    });

    filterLantaiRuangan.addEventListener('change', applyRuanganFilter);

    function applyRuanganFilter() {
        const selectedGedung = filterGedungRuangan.value;
        const selectedLantai = filterLantaiRuangan.value;
        for (let row of tableBodyRuangan) {
            const rowGedung = row.dataset.gedungId;
            const rowLantai = row.dataset.lantaiId;
            const showByGedung = selectedGedung === "" || rowGedung === selectedGedung;
            const showByLantai = selectedLantai === "" || rowLantai === selectedLantai;
            row.style.display = (showByGedung && showByLantai) ? "" : "none";
        }
    }

    // --- Logika untuk form TAMBAH RUANGAN ---
    const addGedungSelect = document.getElementById('gedung_id_for_ruangan_add');
    const addLantaiSelect = document.getElementById('lantai_id_for_ruangan');

    addGedungSelect.addEventListener('change', function() {
        const selectedGedungId = this.value;
        addLantaiSelect.innerHTML = '<option value="" disabled selected>-- Memuat Lantai... --</option>';
        addLantaiSelect.disabled = true;

        if (!selectedGedungId) {
            addLantaiSelect.innerHTML = '<option value="" disabled selected>-- Pilih Gedung Dahulu --</option>';
            return;
        }

        // Cari lantai yang relevan dari data yang sudah ada
        const relevantFloors = allFloorsData.filter(floor => floor.gedung_id == selectedGedungId);
        
        addLantaiSelect.innerHTML = '<option value="" disabled selected>-- Pilih Lantai --</option>';
        relevantFloors.forEach(floor => {
            const option = document.createElement('option');
            option.value = floor.lantai_id;
            option.textContent = `Lantai ${floor.lantai_nomor}`;
            addLantaiSelect.appendChild(option);
        });
        addLantaiSelect.disabled = false;
    });
});
</script>

</body>
</html>