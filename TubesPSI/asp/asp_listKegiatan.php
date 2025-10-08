<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'asp') {
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
    
    header("Location: asp_listKegiatan.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff ASP';
$selected_bulan = $_GET['bulan'] ?? '';
$selected_tahun = $_GET['tahun'] ?? '';
$kegiatan_data = [];

$sort_by = $_GET['sort'] ?? 'pengajuan';
$order_by_clause = ($sort_by === 'event') ? "pe.pengajuan_event_tanggal_mulai DESC" : "pe.pengajuan_tanggalEdit DESC";
$sort_button_text = ($sort_by === 'event') ? "Urutkan Berdasarkan Tgl Pengajuan" : "Urutkan Berdasarkan Tgl Event";

$query_params = $_GET;
$query_params['sort'] = ($sort_by === 'event') ? 'pengajuan' : 'event';
$sort_button_url = 'asp_listKegiatan.php?' . http_build_query($query_params);

try {
    if (isset($conn)) {
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
            WHERE pe.pengajuan_status_ditmawa = 'Disetujui'
        ";
        
        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($selected_bulan)) { $conditions[] = "MONTH(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_bulan; $types .= "i"; }
        if (!empty($selected_tahun)) { $conditions[] = "YEAR(pe.pengajuan_event_tanggal_mulai) = ?"; $params[] = $selected_tahun; $types .= "i"; }

        if (count($conditions) > 0) { $sql .= " AND " . implode(' AND ', $conditions); }
        
        $sql .= " ORDER BY " . $order_by_clause;

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
        .filter-form { display: flex; gap: 15px; margin-bottom: 25px; justify-content: center; align-items: center; padding: 15px; background-color: #f8f9fa; border-radius: 10px; }
        .filter-form select, .filter-form button { padding: 8px 12px; border-radius: 5px; border: 1px solid #ced4da; }
        .filter-form button { background-color: #007bff; color: white; border: none; cursor: pointer; }
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

        /* [PENAMBAHAN] CSS untuk Modal Konfirmasi */
        .modal-overlay { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 400px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); text-align: center; }
        .modal-header h3 { font-size: 1.5em; color: #333; margin-bottom: 15px; }
        .modal-body p { font-size: 1.1em; color: #555; margin-bottom: 25px; }
        .modal-footer { display: flex; justify-content: center; gap: 15px; }
        .btn-secondary { background-color: #6c757d; }
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
            <?php if (isset($_GET['sort'])): ?>
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
            <?php endif; ?>
            <button type="submit">Filter</button>
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
                                        <a href="asp_persetujuan.php?id=<?php echo $row['pengajuan_id']; ?>" class="btn btn-view"><i class="fas fa-edit"></i> Lihat</a>
                                        <form method="POST" action="asp_listKegiatan.php" style="display:inline;">
                                            <input type="hidden" name="pengajuan_id" value="<?php echo $row['pengajuan_id']; ?>">
                                            <button type="submit" name="delete_event" class="btn btn-delete delete-btn"><i class="fas fa-trash"></i> Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center; padding: 20px;">Tidak ada pengajuan yang memerlukan persetujuan ASP saat ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
                <h3 style="font-weight: bold; margin-top: 5px;">ADMINISTRASI SARANA & PRASARANA</h3>
            </div>
        </div>
        <div class="footer-right">
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Jln. Ciumbuleuit No. 94 Bandung 40141 Jawa Barat</li>
                <li><i class="fas fa-phone-alt"></i> (022) 203 2655</li>
                <li><i class="fas fa-envelope"></i> asp@unpar.ac.id</li>
            </ul>
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
            e.preventDefault(); // Mencegah form submit langsung
            formToSubmit = this.closest('form'); // Simpan form yang diklik
            modal.style.display = 'flex'; // Tampilkan modal
        });
    });

    cancelBtn.addEventListener('click', function () {
        modal.style.display = 'none';
        formToSubmit = null;
    });

    confirmBtn.addEventListener('click', function () {
        if (formToSubmit) {
            formToSubmit.submit(); // Submit form yang sudah disimpan
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