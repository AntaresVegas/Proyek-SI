<?php
session_start();
// Sesuaikan path jika perlu
require_once(__DIR__ . '/../config/db_connection.php');

// 1. Cek otentikasi pengguna sebagai ASP
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff ASP';

// [BARU] Variabel Paginasi
$limit = 20; // 20 baris per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Pastikan halaman tidak kurang dari 1
$offset = ($page - 1) * $limit;


// [BARU] Logika untuk menghitung total data
$total_rows = 0;
$total_pages = 0;
try {
    // Query count ini sama dengan query utama di bawah, hanya untuk COUNT(*)
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
    error_log("Error counting reports for ASP: " . $e->getMessage());
}


// 2. Fungsionalitas aksi DIHAPUS, backend hanya mengambil data.
$laporan_data = [];
try {
    // [DIUBAH] SQL disesuaikan dengan paginasi (LIMIT ? OFFSET ?)
    $sql = "
        SELECT 
            pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_LPJ, pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ, m.mahasiswa_nama, m.mahasiswa_npm
        FROM pengajuan_event pe
        JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id
        WHERE pe.pengaju_tipe = 'mahasiswa' 
          AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY pe.pengajuan_event_tanggal_selesai DESC
        LIMIT ? OFFSET ?
    ";
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
    error_log("Error fetching report data for ASP: " . $e->getMessage());
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Arsip Laporan - Event Management Unpar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [DIUBAH] :root Menggabungkan style ASP dan Ditmawa */
        :root {
            --primary-color: #0A2342; /* ASP Theme */
            --hover-color: #FFD700;   /* ASP Theme */
            --text-dark: #2c3e50;
            --text-light: #8895a7;
            --border-color: #e5e7eb;
            --white: #ffffff;
            /* Variabel dari Ditmawa yg dibutuhkan tabel */
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
            --bg-light: #f9fafb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif; /* Diganti ke Poppins */
            background-image: url('../img/backgroundASP.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100%;
            /* Hapus padding-top, akan dikelola oleh .main-content */
        }
        
        /* [TETAP] CSS Navbar & Footer ASP (Tidak Diubah) */
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: sticky; /* Diubah dari fixed ke sticky */ top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: var(--white); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu a { text-decoration: none; color: #E0E0E0; font-weight: 500; transition: color 0.3s; }
        .navbar-menu a.active, .navbar-menu a:hover { color: var(--hover-color); }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: var(--white); }
        .navbar-right a { color: var(--white); }
        .icon { font-size: 20px; cursor: pointer; }
        a { text-decoration: none; }

        .page-footer { background-color: var(--primary-color); color: #E0E0E0; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: var(--white); }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        /* Akhir Navbar & Footer ASP */


        /* [DIUBAH] Main Content & Container disamakan dgn Ditmawa */
        .main-content { flex-grow: 1; padding: 30px 0; }
        .container { 
            max-width: 1400px; /* Diperlebar untuk tabel */
            margin: 0 auto; 
            padding: 0 20px;
        }
        .page-header { margin-bottom: 30px; padding: 1.5rem; text-align: center; background-color: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border-radius: 12px; }
        .page-header h1 { font-size: 2.25rem; font-weight: 700; color: var(--text-dark); }
        .page-header p { font-size: 1.1rem; color: #5a6a7a; margin-top: 5px;}

        /* [DIHAPUS] CSS Kartu Laporan (Diganti Tabel) */
        /* .laporan-card { ... } */
        /* .card-content { ... } */
        /* .info-item { ... } */

        /* [BARU] CSS Tabel (Dari Ditmawa) */
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
            color: var(--text-dark); /* Disesuaikan agar netral (bukan primary color) */
        }
        .keterangan-text { /* Menggantikan .keterangan-block */
            font-style: italic;
            color: var(--danger);
            font-size: 0.9rem;
            max-width: 250px; 
            white-space: normal;
        }

        /* [DIUBAH] Style Status Badge & Button (Dari Ditmawa) */
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 999px; font-weight: 600; font-size: 0.75rem; text-transform: capitalize; display: inline-block; }
        .status-badge.menunggu-persetujuan { background-color: #fef3c7; color: #92400e; }
        .status-badge.ditolak { background-color: #fee2e2; color: #991b1b; }
        .status-badge.disetujui { background-color: #d1fae5; color: #065f46; }

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
        .download-button { background-color: var(--info); color: var(--white); } 
        .download-button:hover { background-color: #2563eb; }

        /* [TETAP] CSS No Data Message */
        .no-data-message { text-align: center; padding: 3rem; background: rgba(255,255,255,0.9); border-radius: 12px; border: 1px dashed var(--border-color); }
        .no-data-message i { font-size: 3rem; color: var(--text-light); margin-bottom: 1rem; }
        .no-data-message p { font-size: 1.1rem; color: var(--text-light); }
        
        /* [BARU] CSS Untuk Paginasi (Dari Ditmawa) */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 1.5rem;
            background: var(--white);
            border-radius: 0 0 12px 12px; /* Menempel di bawah tabel */
            box-shadow: 0 -2px 5px rgba(0,0,0,0.03); 
            margin-top: -1px; 
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
            color: var(--primary-color); /* Disesuaikan dgn tema ASP */
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .page-link:hover {
            background-color: #f7f9fa;
            border-color: #d0d5db;
        }
        .page-link.active {
            background-color: var(--primary-color); /* Disesuaikan dgn tema ASP */
            color: var(--white);
            border-color: var(--primary-color);
        }
        .page-link.disabled {
            color: var(--text-light);
            pointer-events: none;
            background-color: var(--bg-light);
        }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #FFFFFF; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #FFD700; }
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
        <li><a href="asp_listKegiatan.php">Persetujuan Event</a></li>
        <li><a href="asp_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="asp_kalender_gabungan.php">Kalender Gabungan</a></li>
        <li><a href="asp_kalender.php">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php" class="active">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <header class="page-header">
            <h1>Arsip Laporan Bukti Kegiatan</h1>
            <p>Halaman ini menampilkan semua laporan bukti kegiatan yang telah diproses oleh Ditmawa.</p>
        </header>
        
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
                                <th>Keterangan (dari Ditmawa)</th>
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
                        $window = 2; 
                        for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php
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

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px; color: var(--white);">ADMINISTRASI SARANA & PRASARANA</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                <li><i class="fas fa-envelope"></i> asp@unpar.ac.id</li>
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