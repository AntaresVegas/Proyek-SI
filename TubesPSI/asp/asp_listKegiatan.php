<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
    header("Location: ../index.php");
    exit();
}

require_once('../config/db_connection.php');

$nama = $_SESSION['nama'] ?? 'Staff ASP';
$selected_bulan = $_GET['bulan'] ?? '';
$selected_tahun = $_GET['tahun'] ?? '';
$search_event = $_GET['search_event'] ?? '';
$kegiatan_data = [];

// [BARU] Logika Paginasi (diambil dari ditmawa)
$limit = 10; // 10 baris per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

// Logika Sorting
$sort_by = $_GET['sort'] ?? 'pengajuan';
$order_by_clause = ($sort_by === 'event') ? "pe.pengajuan_event_tanggal_mulai DESC" : "pe.pengajuan_tanggalEdit DESC";
$sort_button_text = ($sort_by === 'event') ? "Urutkan Berdasarkan Tgl Pengajuan" : "Urutkan Berdasarkan Tgl Event";

// Membangun URL untuk tombol sort
$query_params = $_GET;
$query_params['sort'] = ($sort_by === 'event') ? 'pengajuan' : 'event';
unset($query_params['page']); // Hapus param page agar sort mulai dari halaman 1
$sort_button_url = 'asp_listKegiatan.php?' . http_build_query($query_params);

// [BARU] Membangun query string untuk paginasi (mempertahankan filter)
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

// Menambahkan kondisi filter dan pencarian ke query SQL
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
        
        $where_clause = "";
        if (count($conditions) > 0) {
            $where_clause = " WHERE " . implode(' AND ', $conditions);
            $count_sql .= $where_clause;
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
                    ELSE 'STAFF KEMAHASISWAAN'
                END AS identitas_pengaju
            FROM pengajuan_event pe
            LEFT JOIN mahasiswa m ON pe.pengaju_id = m.mahasiswa_id AND pe.pengaju_tipe = 'mahasiswa'
            LEFT JOIN ditmawa d ON pe.pengaju_id = d.ditmawa_id AND pe.pengaju_tipe = 'ditmawa'
        ";
        
        $sql .= $where_clause; // Gunakan klausa WHERE yang sama dari COUNT
        $sql .= " ORDER BY " . $order_by_clause;
        
        // [BARU] Tambahkan LIMIT dan OFFSET
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;   // Tambahkan limit ke params
        $params[] = $offset;  // Tambahkan offset ke params
        $types .= "ii";       // Tambahkan tipe integer untuk limit dan offset

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
    error_log("Error fetching event data for ASP: " . $e->getMessage());
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
    <title>Daftar Persetujuan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [STYLES UTAMA ANDA] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body { font-family: 'Segoe UI', sans-serif; background-image: url('../img/backgroundASP.jpeg'); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100%; padding-top: 80px; display: flex; flex-direction: column; }
        .main-content { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #0A2342; width: 100%; padding: 10px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: #FFFFFF; font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu li a { text-decoration: none; color: #E0E0E0; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #FFD700; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color: #FFFFFF; }
        .navbar-right a {color: #FFFFFF;}
        .icon { font-size: 20px; cursor: pointer; }
        .kegiatan-container { max-width: 1200px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .kegiatan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;}
        .kegiatan-header h1 { font-size: 32px; color: #2c3e50; margin: 0; }
        .header-buttons { display: flex; gap: 10px; align-items: center; }
        .view-sort-button, .view-graph-button { color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: background-color 0.3s; }
        .view-sort-button { background-color: #17a2b8; }
        .view-sort-button:hover { background-color: #138496; }
        .view-graph-button { background-color: #28a745; }
        .view-graph-button:hover { background-color: #218838; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; justify-content: center; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-form select, .filter-form input, .filter-form button { padding: 8px 12px; border-radius: 5px; border: 1px solid #ced4da; }
        .filter-form button { background-color: #007bff; color: white; border: none; cursor: pointer; }
        .kegiatan-table-container { overflow-x: auto; } /* [BARU] Tambahkan overflow-x */
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
        .page-footer { background-color: #0A2342; color: #E0E0E0; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; color: #FFFFFF; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }

        /* [BARU] CSS Untuk Paginasi (disesuaikan tema ASP) */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 1.5rem 0;
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .pagination-info {
            color: #8895a7;
            font-size: 0.9rem;
        }
        .pagination-links {
            display: flex;
            gap: 5px;
        }
        .page-link {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #0A2342; /* Tema ASP */
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
        }
        .page-link:hover {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
        .page-link.active {
            background-color: #0A2342; /* Tema ASP */
            color: #ffffff;
            border-color: #0A2342;
        }
        .page-link.disabled {
            color: #8895a7;
            pointer-events: none;
            background-color: #f9fafb;
        }
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
        <li><a href="asp_listKegiatan.php" class="active">Persetujuan Event</a></li>
        <li><a href="asp_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="asp_kalender.php">Kalender Peminjaman</a></li>
        <li><a href="asp_laporan.php">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="asp_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="kegiatan-container">
        <div class="kegiatan-header">
            <h1>Daftar Persetujuan Sarana Prasarana</h1>
            <div class="header-buttons">
                 <a href="<?php echo $sort_button_url; ?>" class="view-sort-button"><i class="fas fa-sort-amount-down"></i> <?php echo $sort_button_text; ?></a>
                <a href="asp_grafikKegiatan.php" class="view-graph-button"><i class="fas fa-chart-bar"></i> Lihat Grafik</a>
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
                                        <a href="asp_persetujuan.php?id=<?php echo $row['pengajuan_id']; ?>&page=<?php echo $page; ?>" class="btn btn-view"><i class="fas fa-edit"></i> Lihat</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding: 20px;">Tidak ada pengajuan yang cocok dengan kriteria filter Anda.</td></tr>
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

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;">ADMINISTRASI SARANA & PRASARANA</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                <li><a href="mailto:asp@unpar.ac.id" style="color: inherit; text-decoration: none;"><i class="fas fa-envelope"></i> asp@unpar.ac.id</a></li>
            </ul>
        </div>
    </div>
</footer>

</body>
</html>