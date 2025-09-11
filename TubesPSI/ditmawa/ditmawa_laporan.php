<?php
session_start();
require_once('../config/db_connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pengajuan_id'])) {
    $pengajuan_id = $_POST['pengajuan_id'];
    $new_status = '';
    $komentar_lpj = NULL;

    if (isset($_POST['setujui_lpj'])) {
        $new_status = 'Disetujui';
        $message = "Status LPJ berhasil diubah menjadi 'Disetujui'!";
    } elseif (isset($_POST['tolak_lpj'])) {
        $new_status = 'Ditolak';
        $komentar_lpj = !empty($_POST['alasan_penolakan']) ? trim($_POST['alasan_penolakan']) : 'Tidak ada alasan yang diberikan.';
        $message = "Status LPJ berhasil diubah menjadi 'Ditolak'.";
    }

    if (!empty($new_status)) {
        $stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_statusLPJ = ?, pengajuan_komentarLPJ = ? WHERE pengajuan_id = ?");
        $stmt->bind_param("ssi", $new_status, $komentar_lpj, $pengajuan_id);
        if ($stmt->execute()) {
            $message_type = 'success';
        } else {
            $message = 'Gagal memperbarui status LPJ.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}

$laporan_data = [];
try {
    $sql = "
        SELECT 
            pe.pengajuan_id, pe.pengajuan_namaEvent, pe.pengajuan_LPJ, pe.pengajuan_statusLPJ,
            pe.pengajuan_komentarLPJ, m.mahasiswa_nama, m.mahasiswa_npm
        FROM pengajuan_event pe
        JOIN mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id
        WHERE pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY pe.pengajuan_event_tanggal_selesai DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $laporan_data[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching report data: " . $e->getMessage());
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Kelola Laporan - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: #ff8c00; width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:rgb(255, 255, 255); }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:rgb(254, 254, 254); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu a { text-decoration: none; color:rgb(255, 255, 255); font-weight: 500; }
        .navbar-menu a.active, .navbar-menu a:hover { color: #007bff; }
        .icon { font-size: 20px; }
        .container { max-width: 1300px; margin: 40px auto; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 30px; }
        .laporan-header { text-align: center; margin-bottom: 30px; }
        .laporan-header h1 { font-size: 32px; color: #2c3e50; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; text-align: left; }
        .data-table th, .data-table td { padding: 12px 15px; border: 1px solid #ddd; vertical-align: middle; }
        .data-table th { background-color: #f2f2f2; font-weight: 600; text-transform: uppercase; font-size: 14px; }
        .no-data-message { text-align: center; padding: 20px; font-size: 18px; color: #888; }
        .download-button { background-color: #0d6efd; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .action-buttons { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; color: white; font-size: 14px; cursor: pointer; }
        .btn-approve { background-color: #198754; }
        .btn-reject { background-color: #dc3545; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background-color: #d1e7dd; color: #0f5132; }
        .message.error { background-color: #f8d7da; color: #842029; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; color: white; text-align: center; font-size: 12px; text-transform: capitalize; white-space: nowrap; }
        .status-badge.menunggu-persetujuan, .status-badge.diterima { background-color: #ffc107; color: #333; }
        .status-badge.ditolak, .status-badge.revisi { background-color: #dc3545; }
        .status-badge.disetujui { background-color: #28a745; }
        .alasan-ditolak { font-size: 13px; color: #dc3545; font-style: italic; max-width: 250px; word-wrap: break-word; }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 10px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h2 { color: #2c3e50; }
        .close-button { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-body textarea { width: 100%; padding: 10px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; min-height: 100px; margin-bottom: 20px; }
        .modal-footer { text-align: right; }
        .page-footer { background-color: #ff8c00; color: #fff; padding: 40px 0; }
        .footer-container { max-width: 1300px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
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
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
        <li><a href="ditmawa_laporan.php" class="active">Laporan</a></li>
    </ul>
    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit;"><span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon" style="margin-left: 10px;"></i></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="main-content">
    <div class="container">
        <div class="laporan-header">
            <h1>Kelola Laporan Pertanggungjawaban</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>NAMA MAHASISWA</th>
                    <th>NAMA ACARA</th>
                    <th>STATUS LPJ</th>
                    <th>KETERANGAN</th>
                    <th>DOKUMEN LPJ</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($laporan_data)): ?>
                    <?php foreach ($laporan_data as $row):
                        $status_class = str_replace(' ', '-', strtolower(htmlspecialchars($row['pengajuan_statusLPJ'])));
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['mahasiswa_nama']); ?><br></td>
                            <td><?php echo htmlspecialchars($row['pengajuan_namaEvent']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($row['pengajuan_statusLPJ']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['pengajuan_statusLPJ'] == 'Ditolak' && !empty($row['pengajuan_komentarLPJ'])): ?>
                                    <div class="alasan-ditolak"><?php echo htmlspecialchars($row['pengajuan_komentarLPJ']); ?></div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['pengajuan_LPJ'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['pengajuan_LPJ']); ?>" class="download-button" download><i class="fas fa-download"></i> Download</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="ditmawa_laporan.php" style="display:inline;">
                                        <input type="hidden" name="pengajuan_id" value="<?php echo $row['pengajuan_id']; ?>">
                                        <button type="submit" name="setujui_lpj" class="btn btn-approve">Setujui</button>
                                    </form>
                                    <button type="button" class="btn btn-reject" onclick="openRejectModal('<?php echo $row['pengajuan_id']; ?>')">Tolak</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data-message">Belum ada LPJ yang pernah diunggah.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Alasan Penolakan LPJ</h2>
            <span class="close-button" onclick="closeRejectModal()">&times;</span>
        </div>
        <form method="POST" action="ditmawa_laporan.php">
            <div class="modal-body">
                <input type="hidden" name="pengajuan_id" id="modal_pengajuan_id">
                <label for="alasan_penolakan">Mohon berikan alasan penolakan/revisi:</label>
                <textarea name="alasan_penolakan" id="alasan_penolakan" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" name="tolak_lpj" class="btn btn-reject">Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>

<script>
    var modal = document.getElementById('rejectModal');
    function openRejectModal(pengajuan_id) {
        document.getElementById('modal_pengajuan_id').value = pengajuan_id;
        modal.style.display = "block";
    }
    function closeRejectModal() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            closeRejectModal();
        }
    }
</script>

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

</body>
</html>