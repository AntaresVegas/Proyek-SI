<?php
session_start();
require_once('../config/db_connection.php'); // Koneksi DB untuk ambil history

// Autentikasi Ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$nama_ditmawa = $_SESSION['nama'] ?? 'Staff Ditmawa';

// --- [DIUBAH] LOGIKA PAGINATION DIMULAI ---
$limit = 10; // 10 item per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_items = 0;
$total_pages = 1;
$history_list = [];

try {
    // 1. Hitung total data
    $total_result = $conn->query("SELECT COUNT(*) as total FROM log_import_jadwal");
    if ($total_result) {
        $total_items = (int)$total_result->fetch_assoc()['total'];
        $total_pages = ceil($total_items / $limit);
    }
    
    // 2. Ambil data untuk halaman ini
    $sql_history = "SELECT l.semester_tahun, l.jurusan_fakultas, l.nama_file, l.diupload_pada, d.ditmawa_nama 
                    FROM log_import_jadwal l
                    LEFT JOIN ditmawa d ON l.diupload_oleh_id = d.ditmawa_id
                    ORDER BY l.diupload_pada DESC
                    LIMIT ? OFFSET ?"; // Gunakan LIMIT dan OFFSET
                    
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bind_param("ii", $limit, $offset);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    
    if ($result_history) {
        while ($row = $result_history->fetch_assoc()) {
            $history_list[] = $row;
        }
    }
    $stmt_history->close();

} catch (Exception $e) {
    error_log("Error fetching import history: " . $e->getMessage());
}
$conn->close(); 
// --- LOGIKA PAGINATION SELESAI ---
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
        
        .tab-wrapper { max-width: 900px; margin: 40px auto; background: rgba(255, 255, 255, 0.95); border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        h1 { text-align: center; color: var(--text-dark); margin-bottom: 25px; padding-top: 30px; }
        .tab-container { display: flex; background-color: #f1f1f1; border-bottom: 1px solid #ddd; }
        .tab-link { background-color: inherit; border: none; outline: none; cursor: pointer; padding: 14px 20px; transition: 0.3s; font-size: 16px; font-weight: 600; color: #555; border-bottom: 3px solid transparent; }
        .tab-link:hover { background-color: #ddd; }
        .tab-link.active { background-color: #fff; color: var(--ditmawa-primary); border-bottom: 3px solid var(--ditmawa-primary); }
        .tab-content { display: none; padding: 30px; animation: fadeIn 0.5s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .form-container { max-width: 700px; margin: 0 auto; padding: 0; background: none; box-shadow: none; }
        .history-container { max-width: 100%; margin: 0 auto; padding: 0; background: none; box-shadow: none; }
        .history-container h2 { text-align: left; color: var(--text-dark); margin-bottom: 20px; }
        .alert-container { max-width: 900px; margin: 20px auto 0 auto; }
        .alert { padding: 15px; border-radius: 5px; color: #fff; text-align: left; font-weight: bold; }
        .alert.success { background-color: #28a745; }
        .alert.error { background-color: #dc3545; }

        /* CSS Lainnya (Form, Navbar, Footer, Checkbox) tetap sama */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input[type="text"], .form-group input[type="file"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 16px; }
        .btn-submit { display: block; width: 100%; padding: 15px; background-color: var(--ditmawa-primary); color: white; border: none; border-radius: 5px; font-size: 18px; cursor: pointer; transition: background-color 0.3s; font-weight: bold; margin-top: 20px;}
        .btn-submit:hover { background-color: var(--ditmawa-secondary); }
        .notes { background-color: #fffbe6; border: 1px solid #ffeeba; border-left: 5px solid #ffc107; padding: 20px; margin-top: 30px; border-radius: 8px; font-size: 15px; color: #664d03; box-shadow: 0 3px 8px rgba(0,0,0,0.08); }
        .notes strong { display: block; font-size: 18px; font-weight: 700; color: #856404; margin-bottom: 15px; }
        .notes strong .fas { margin-right: 10px; color: #ffc107; }
        .notes ul { margin-left: 20px; padding-left: 10px; }
        .notes li { margin-bottom: 10px; line-height: 1.5; }
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
        .checkbox-modern { display: flex; align-items: center; position: relative; }
        .checkbox-modern label { display: flex; align-items: center; position: relative; cursor: pointer; font-weight: 500; color: #555; margin: 0; }
        .checkbox-modern input[type="checkbox"] { opacity: 0; position: absolute; width: 1px; height: 1px; }
        .checkbox-modern label::before { content: ''; width: 20px; height: 20px; border: 2px solid #adb5bd; border-radius: 4px; margin-right: 12px; transition: all 0.2s ease; background-color: #fff; flex-shrink: 0; }
        .checkbox-modern:hover label::before { border-color: var(--ditmawa-primary); }
        .checkbox-modern input[type="checkbox"]:checked + label::before { background-color: var(--ditmawa-primary); border-color: var(--ditmawa-primary); background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%<path fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26 2.974 7.25 8 2.193z'/%3e%3c/svg%3e"); background-position: center; }
        .checkbox-modern input[type="checkbox"]:focus + label::before { box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.3); }
        .history-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .history-table th, .history-table td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; }
        .history-table th { background-color: #f8f9fa; font-weight: 600; color: #333; }
        .history-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .history-table td { color: #555; word-break: break-word; }
        .no-history { text-align: center; color: #777; font-style: italic; padding: 20px; }

        /* [BARU] CSS UNTUK TOMBOL PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 25px;
            flex-wrap: wrap;
            gap: 4px;
        }
        .pagination a {
            color: var(--ditmawa-primary);
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 2px;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 14px;
            font-weight: 600;
        }
        .pagination a.active {
            background-color: var(--ditmawa-primary);
            color: white;
            border-color: var(--ditmawa-primary);
            cursor: default;
        }
        .pagination a:hover:not(.active) {
            background-color: #f0f0f0;
        }
        .pagination-info {
            font-size: 14px;
            color: #555;
            margin: 0 10px;
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
                <a href="logout.php"><i class="fas fa-sign-out-alt icon"style="color:black;"></i></a>
            </div>
        </nav>
    </header>

    <div class="main-container">
        <div class="alert-container">
            <?php if (isset($_SESSION['import_message'])): ?>
                <div class="alert <?php echo $_SESSION['import_message_type']; ?>">
                    <?php echo $_SESSION['import_message']; ?>
                </div>
                <?php unset($_SESSION['import_message'], $_SESSION['import_message_type']); ?>
            <?php endif; ?>
        </div>

        <div class="tab-wrapper">
            <h1>Import Jadwal Kelas</h1>

            <div class="tab-container">
                <button class="tab-link" onclick="openTab(event, 'Import')"><i class="fas fa-upload"></i> Import Jadwal</button>
                <button class="tab-link" onclick="openTab(event, 'History')"><i class="fas fa-history"></i> History Import</button>
            </div>

            <div id="Import" class="tab-content">
                <div class="form-container">
                    <form action="proses_import_jadwal.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="semester_tahun">Semester/Tahun Ajaran:</label>
                            <input type="text" id="semester_tahun" name="semester_tahun" placeholder="Contoh: Ganjil 2025/2026" required>
                        </div>
                        <div class="form-group">
                            <label for="jurusan_fakultas">Jurusan / Fakultas:</label>
                            <input type="text" id="jurusan_fakultas" name="jurusan_fakultas" placeholder="Contoh: Informatika / FTIS" required>
                        </div>
                        <div class="form-group">
                            <label for="fileJadwal">Pilih File Excel Jadwal Kelas (.xlsx, .xls):</label>
                            <input type="file" id="fileJadwal" name="fileJadwal" accept=".xlsx, .xls" required>
                        </div>
                        <div class="form-group checkbox-modern">
                            <input type="checkbox" id="hapus_jadwal_lama" name="hapus_jadwal_lama" value="1">
                            <label for="hapus_jadwal_lama">Hapus jadwal lama untuk semester ini sebelum import?</label>
                        </div>
                        <div class="notes">
                            <strong><i class="fas fa-exclamation-triangle"></i> <b> Catatan Penting:</b></strong>
                            <ul>
                                <li>Pastikan file Excel Anda memiliki kolom: <b>Nama Ruangan</b>,  <b> Hari (Senin-Sabtu)</b>, <b>Jam Mulai (HH:MM)</b>, <b>Jam Selesai (HH:MM)</b>,<b> Nama Matakuliah (Opsional).</b></li>
                                <li>Baris pertama diasumsikan sebagai header dan akan dilewati.</li>
                                <li>Nama Ruangan di Excel harus <b>SAMA</b> dengan nama ruangan yang ada di sistem database. Jika tidak cocok, baris tersebut akan dilewati.</li>
                                <li>Format jam harus <b>HH:MM</b> (contoh: 08:00, 14:30).</li>
                                <li>Jika mencentang "Hapus jadwal lama", semua data jadwal kelas untuk semester/tahun yang dimasukkan akan dihapus terlebih dahulu.</li>
                            </ul>
                        </div>
                        <button type="submit" class="btn-submit">Import Jadwal</button>
                    </form>
                </div>
            </div>

            <div id="History" class="tab-content">
                <div class="history-container">
                    <h2><i class="fas fa-history"></i> History Import Jadwal</h2>
                    <div style="overflow-x: auto;">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Waktu Upload</th>
                                    <th>Semester/Tahun</th>
                                    <th>Jurusan/Fakultas</th>
                                    <th>Nama File</th>
                                    <th>Diupload Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history_list)): ?>
                                    <tr>
                                        <td colspan="5" class="no-history">Belum ada history import.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($history_list as $history): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($history['diupload_pada']))); ?></td>
                                            <td><?php echo htmlspecialchars($history['semester_tahun']); ?></td>
                                            <td><?php echo htmlspecialchars($history['jurusan_fakultas']); ?></td>
                                            <td><?php echo htmlspecialchars($history['nama_file']); ?></td>
                                            <td><?php echo htmlspecialchars($history['ditmawa_nama']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <?php if ($total_pages > 1): ?>
                            <?php if ($page > 1): ?>
                                <a href="?tab=history&page=<?php echo $page - 1; ?>">&laquo;</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?tab=history&page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?tab=history&page=<?php echo $page + 1; ?>">&raquo;</a>
                            <?php endif; ?>
                        <?php elseif ($total_items > 0): ?>
                             <span class="pagination-info">Halaman 1 dari 1</span>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>

        </div> </div>

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

    <script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        // [DIUBAH] Jika ada parameter 'tab=history' ATAU 'page', buka tab History
        if (tab === 'history' || urlParams.has('page')) {
            document.querySelector('.tab-link[onclick*="History"]').click();
        } else {
            // Jika tidak, buka tab Import sebagai default
            document.querySelector('.tab-link[onclick*="Import"]').click();
        }
    });
    </script>
</body>
</html>