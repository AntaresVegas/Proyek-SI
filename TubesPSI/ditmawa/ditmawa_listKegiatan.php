<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

// Logika untuk menangani penghapusan event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $pengajuan_id_to_delete = $_POST['pengajuan_id'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("DELETE FROM peminjaman_ruangan WHERE pengajuan_id = ?");
        $stmt1->bind_param("i", $pengajuan_id_to_delete);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM pengajuan_event WHERE pengajuan_id = ?");
        $stmt2->bind_param("i", $pengajuan_id_to_delete);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        $_SESSION['success_message'] = "Event berhasil dihapus secara permanen.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Gagal menghapus event: " . $e->getMessage();
    }
    
    // [DIUBAH] Pastikan redirect mempertahankan filter & halaman saat ini
    unset($_GET['delete_event']); // Hapus aksi delete dari query string
    $redirect_url = 'ditmawa_listKegiatan.php?' . http_build_query($_GET);
    header("Location: " . $redirect_url);
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$selected_bulan = $_GET['bulan'] ?? '';
$selected_tahun = $_GET['tahun'] ?? '';
$search_event = $_GET['search_event'] ?? '';
$kegiatan_data = [];

// [BARU] Logika Paginasi
$limit = 10; // 10 baris per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// [DIUBAH] Logika Sorting (disesuaikan agar bekerja dgn paginasi)
$sort_by = $_GET['sort'] ?? 'pengajuan';
$order_by_clause = ($sort_by === 'event') ? "pe.pengajuan_event_tanggal_mulai DESC" : "pe.pengajuan_tanggalEdit DESC";
$sort_button_text = ($sort_by === 'event') ? "Urutkan Berdasarkan Tgl Pengajuan" : "Urutkan Berdasarkan Tgl Event";

// [DIUBAH] Membangun URL untuk tombol sort dan filter
$query_params = $_GET;
$query_params['sort'] = ($sort_by === 'event') ? 'pengajuan' : 'event';
unset($query_params['page']); // Hapus param page agar sort mulai dari halaman 1
$sort_button_url = 'ditmawa_listKegiatan.php?' . http_build_query($query_params);

// [DIUBAH] Membangun query string untuk paginasi (mempertahankan filter)
$pagination_query_params = $_GET;
unset($pagination_query_params['page']);
$pagination_query_string = http_build_query($pagination_query_params);
if (!empty($pagination_query_string)) {
    $pagination_query_string = '&' . $pagination_query_string;
}


// [BARU] Logika menghitung total data untuk paginasi
$total_rows = 0;
$total_pages = 0;
$conditions = [];
$params = [];
$types = "";

// [MODIFIKASI] Menambahkan kondisi filter dan pencarian ke query SQL
if (!empty($selected_bulan)) { $conditions[] = "MONTH(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_bulan; $types .= "i"; }
if (!empty($selected_tahun)) { $conditions[] = "YEAR(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_tahun; $types .= "i"; }
if (!empty($search_event)) {
    $conditions[] = "LOWER(pe.pengajuan_namaEvent) LIKE LOWER(?)";
    $search_param = "%" . $search_event . "%";
    $params[] = $search_param;
    $types .= "s";
}

try {
    if (isset($conn)) {
        // [BARU] Query untuk COUNT
        $count_sql = "SELECT COUNT(pe.pengajuan_id) as total
                      FROM pengajuan_event pe
                      LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
                      LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'";
        if (count($conditions) > 0) {
            $count_sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            if (!empty($params)) { $count_stmt->bind_param($types, ...$params); }
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            if ($count_result) {
                $total_rows = $count_result->fetch_assoc()['total'];
                $total_pages = ceil($total_rows / $limit);
            }
            $count_stmt->close();
        }

        // [DIUBAH] Query utama untuk mengambil data + LIMIT
        $sql = "
            SELECT 
                pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_tanggalEdit, pe.pengajuan_status_ditmawa, pe.pengajuan_status_asp,
                pe.pengajuan_status_proposal,
                CASE
                    WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_nama
                    WHEN pe.pengaju_tipe = 'ditmawa' THEN d.ditmawa_nama
                    ELSE 'Tidak Diketahui'
                END AS nama_pengaju,
                CASE
                    WHEN pe.pengaju_tipe = 'mahasiswa' THEN m.mahasiswa_npm
                    ELSE 'STAFF DITMAWA'
                END AS identitas_pengaju
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
        ";
        
        if (count($conditions) > 0) { $sql .= " WHERE " . implode(' AND ', $conditions); }
        
        $sql .= " ORDER BY " . $order_by_clause;
        
        // [BARU] Tambahkan LIMIT dan OFFSET
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) { $kegiatan_data[] = $row; }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    error_log("Error fetching event data: " . $e->getMessage());
}
$conn->close();

$current_year = date('Y');
$years = range($current_year, $current_year - 5);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>List Kegiatan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [TETAP] CSS Utama (Navbar, Footer, Tabel, dll) */
        :root {
            --primary-color: #ff8c00;
            --text-dark: #2c3e50;
            --text-light: #8895a7;
            --border-color: #e5e7eb;
            --white: #ffffff;
            --bg-light: #f9fafb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-image: url('../img/backgroundDitmawa.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100%; padding-top: 80px; display: flex; flex-direction: column; }
        .main-content { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(255, 255, 255); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #007bff; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(249, 249, 249); }
        .icon { font-size: 20px; cursor: pointer; color: white; }
        .kegiatan-container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .kegiatan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;}
        .kegiatan-header h1 { font-size: 32px; color: #2c3e50; margin: 0; }
        .header-buttons { display: flex; gap: 10px; align-items: center; }
        .view-graph-button, .view-sort-button { color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s; }
        .view-graph-button { background-color: #28a745; }
        .view-graph-button:hover { background-color: #218838; }
        .view-sort-button { background-color: #17a2b8; }
        .view-sort-button:hover { background-color: #138496; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; justify-content: center; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-form select, .filter-form input, .filter-form button { padding: 8px 12px; border-radius: 5px; border: 1px solid #ced4da; }
        .filter-form button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        .kegiatan-table-container { overflow-x: auto; }
        .kegiatan-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .kegiatan-table th, .kegiatan-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; }
        .kegiatan-table th { background-color: #f2f2f2; }
        .status-badge { padding: 5px 10px; border-radius: 15px; color: white; font-weight: bold; font-size: 12px; white-space: nowrap; }
        .status-badge.disetujui { background-color: #28a745; }
        .status-badge.ditolak { background-color: #dc3545; }
        .status-badge.diajukan { background-color: #ffc107; color: #333; }
        .action-buttons { display: flex; gap: 10px; align-items: center; }
        .btn { padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; border: none; cursor: pointer; font-family: 'Segoe UI', sans-serif; font-size: 14px;}
        .btn-view { background-color: #007bff; color: white; }
        .btn-delete { background-color: #dc3545; color: white; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }
        .page-footer { background-color: var(--primary-color); color: #fff; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #2c3e50; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; color: #2c3e50; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #2c3e50; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
        .modal-overlay { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center; }
        .modal-header h3 { font-size: 1.5em; color: #333; margin-bottom: 15px; }
        .modal-body p { font-size: 1.1em; color: #555; margin-bottom: 25px; }
        .modal-footer { display: flex; justify-content: center; gap: 15px; }
        .btn-secondary { background-color: #6c757d; }

        /* [BARU] CSS Untuk Paginasi */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 1.5rem 0;
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
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
        <li><a href="ditmawa_listKegiatan.php" class="active">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="kegiatan-container">
        <div class="kegiatan-header">
            <h1>List Data Pengajuan Event</h1>
            <div class="header-buttons">
                <a href="<?php echo $sort_button_url; ?>" class="view-sort-button"><i class="fas fa-sort-amount-down"></i> <?php echo $sort_button_text; ?></a>
                <a href="ditmawa_grafikKegiatan.php" class="view-graph-button"><i class="fas fa-chart-bar"></i> Lihat Grafik</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <form method="GET" class="filter-form">
            <label for="bulan">Bulan:</label>
            <select name="bulan" id="bulan">
                <option value="">Semua Bulan</option>
                <?php $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                foreach ($months as $num => $name) { echo '<option value="' . $num . '" ' . ($selected_bulan == $num ? 'selected' : '') . '>' . $name . '</option>'; } ?>
            </select>
            <label for="tahun">Tahun:</label>
            <select name="tahun" id="tahun">
                <option value="">Semua Tahun</option>
                <?php foreach ($years as $year) { echo '<option value="' . $year . '" ' . ($selected_tahun == $year ? 'selected' : '') . '>' . $year . '</option>'; } ?>
            </select>
             <label for="search_event" style="margin-left: 10px;">Cari Event:</label>
            <input type="text" id="search_event" name="search_event" placeholder="Masukkan nama event..." value="<?php echo htmlspecialchars($search_event); ?>">
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <button type="submit">Filter & Cari</button>
        </form>

        <div class="kegiatan-table-container">
            <table class="kegiatan-table">
                <thead>
                    <tr>
                        <th>Tgl Diajukan</th>
                        <th>Tgl Event</th>
                        <th>Nama Pengaju</th>
                        <th>Identitas</th>
                        <th>Nama Acara</th>
                        <th>Status Ditmawa</th>
                        <th>Status ASP</th>
                        <th>Status Proposal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($kegiatan_data)): ?>
                        <?php foreach ($kegiatan_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d F Y', strtotime($row['pengajuan_tanggalEdit']))); ?></td>
                                <td><?php echo htmlspecialchars(date('d F Y', strtotime($row['pengajuan_event_tanggal_mulai']))); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_pengaju']); ?></td>
                                <td><?php echo htmlspecialchars($row['identitas_pengaju']); ?></td>
                                <td><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></td>
                                <td><span class="status-badge <?php echo strtolower(htmlspecialchars($row['pengajuan_status_ditmawa'])); ?>"><?php echo htmlspecialchars($row['pengajuan_status_ditmawa']); ?></span></td>
                                <td><span class="status-badge <?php echo strtolower(htmlspecialchars($row['pengajuan_status_asp'])); ?>"><?php echo htmlspecialchars($row['pengajuan_status_asp']); ?></span></td>
                                <td><span class="status-badge <?php echo strtolower(htmlspecialchars($row['pengajuan_status_proposal'])); ?>"><?php echo htmlspecialchars($row['pengajuan_status_proposal']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="ditmawa_editForm.php?id=<?php echo $row['pengajuan_id']; ?>&page=<?php echo $page; ?>" class="btn btn-view"><i class="fas fa-file-alt"></i> Lihat</a>
                                        <form method="POST" action="ditmawa_listKegiatan.php?page=<?php echo $page; ?><?php echo $pagination_query_string; ?>" style="display:inline;">
                                            <input type="hidden" name="pengajuan_id" value="<?php echo $row['pengajuan_id']; ?>">
                                            <button type="submit" name="delete_event" class="btn btn-delete delete-btn"><i class="fas fa-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding: 20px;">Tidak ada data kegiatan event untuk filter ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1 && !empty($kegiatan_data)): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Menampilkan <strong><?php echo count($kegiatan_data); ?></strong> dari <strong><?php echo $total_rows; ?></strong> data
            </div>
            <div class="pagination-links">
                <a href="?page=<?php echo $page - 1; ?><?php echo $pagination_query_string; ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    &laquo;
                </a>
                
                <?php
                    $window = 2; // Jumlah halaman di kiri dan kanan halaman aktif
                    for ($i = 1; $i <= $total_pages; $i++):
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)):
                ?>
                    <a href="?page=<?php echo $i; ?><?php echo $pagination_query_string; ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php
                        elseif ($i == 2 || $i == $total_pages - 1):
                            echo '<span class="page-link" style="border:none; background:none;">...</span>';
                        endif;
                    endfor;
                ?>
                
                <a href="?page=<?php echo $page + 1; ?><?php echo $pagination_query_string; ?>" class="page-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    &raquo;
                </a>
            </div>
        </div>
        <?php endif; ?>
        </div>
</div>

<div id="deleteConfirmationModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Penghapusan</h3>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus event ini secara permanen? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button id="cancelDelete" class="btn btn-secondary">Batal</button>
            <button id="confirmDelete" class="btn btn-delete">Ya, Hapus</button>
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
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('deleteConfirmationModal');
    const cancelBtn = document.getElementById('cancelDelete');
    const confirmBtn = document.getElementById('confirmDelete');
    let formToSubmit = null;

    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            formToSubmit = this.closest('form');
            modal.style.display = 'flex';
        });
    });

    cancelBtn.addEventListener('click', function () {
        modal.style.display = 'none';
        formToSubmit = null;
    });

    confirmBtn.addEventListener('click', function () {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    window.addEventListener('click', function (e) {
        if (e.target == modal) {
            modal.style.display = 'none';
            formToSubmit = null;
        }
    });
});
</script>

</body>
</html>