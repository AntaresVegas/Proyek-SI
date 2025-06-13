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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_gedung'])) {
        $gedung_nama = trim($_POST['gedung_nama']);
        if (!empty($gedung_nama)) {
            $stmt = $conn->prepare("INSERT INTO gedung (gedung_nama) VALUES (?)");
            $stmt->bind_param("s", $gedung_nama);
            if ($stmt->execute()) { $message = "Gedung '{$gedung_nama}' berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
            $stmt->close();
        }
    }
    if (isset($_POST['delete_gedung'])) {
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
    if (isset($_POST['add_lantai'])) {
        $lantai_nomor = trim($_POST['lantai_nomor']);
        $gedung_id = $_POST['gedung_id_for_lantai'];
        if (!empty($lantai_nomor) && !empty($gedung_id)) {
            $stmt = $conn->prepare("INSERT INTO lantai (lantai_nomor, gedung_id) VALUES (?, ?)");
            $stmt->bind_param("si", $lantai_nomor, $gedung_id);
            if ($stmt->execute()) { $message = "Lantai baru berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
            $stmt->close();
        }
    }
    if (isset($_POST['delete_lantai'])) {
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
    if (isset($_POST['add_ruangan'])) {
        $ruangan_nama = trim($_POST['ruangan_nama']);
        $lantai_id = $_POST['lantai_id_for_ruangan'];
        if (!empty($ruangan_nama) && !empty($lantai_id)) {
            $stmt = $conn->prepare("INSERT INTO ruangan (ruangan_nama, lantai_id) VALUES (?, ?)");
            $stmt->bind_param("si", $ruangan_nama, $lantai_id);
            if ($stmt->execute()) { $message = "Ruangan baru berhasil ditambahkan!"; $message_type = "success"; } else { $message = "Gagal: " . $stmt->error; $message_type = "error"; }
            $stmt->close();
        }
    }
    if (isset($_POST['delete_ruangan'])) {
        $ruangan_id = $_POST['ruangan_id'];
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM peminjaman_ruangan WHERE ruangan_id = ?");
        $check_stmt->bind_param("i", $ruangan_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();
        if ($count > 0) {
            $message = "Gagal menghapus! Ruangan ini terdaftar pada sebuah event."; $message_type = "error";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM ruangan WHERE ruangan_id = ?");
            $delete_stmt->bind_param("i", $ruangan_id);
            if ($delete_stmt->execute()) { $message = "Ruangan berhasil dihapus."; $message_type = "success"; } else { $message = "Gagal: " . $delete_stmt->error; $message_type = "error"; }
            $delete_stmt->close();
        }
    }

    header("Location: ditmawa_kelolaRuangan.php?msg=" . urlencode($message) . "&type=" . $message_type);
    exit();
}

if(isset($_GET['msg'])) { $message = $_GET['msg']; $message_type = $_GET['type']; }

$gedung_list = $conn->query("SELECT * FROM gedung ORDER BY gedung_nama")->fetch_all(MYSQLI_ASSOC);
$lantai_list = $conn->query("SELECT l.lantai_id, l.lantai_nomor, g.gedung_nama, g.gedung_id FROM lantai l JOIN gedung g ON l.gedung_id = g.gedung_id ORDER BY g.gedung_nama, l.lantai_nomor")->fetch_all(MYSQLI_ASSOC);
$ruangan_list = $conn->query("SELECT r.ruangan_id, r.ruangan_nama, l.lantai_nomor, l.lantai_id, g.gedung_nama, g.gedung_id FROM ruangan r JOIN lantai l ON r.lantai_id = l.lantai_id JOIN gedung g ON l.gedung_id = g.gedung_id ORDER BY g.gedung_nama, l.lantai_nomor, r.ruangan_nama")->fetch_all(MYSQLI_ASSOC);
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
        :root { --primary-color: #ff8c00; --danger-color: #dc3545; --success-color: #198754; --light-gray: #f8f9fa; --border-color: #dee2e6; --text-dark: #2c3e50; }
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
        .navbar { display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .navbar-left, .navbar-right, .navbar-menu { display: flex; align-items: center; gap: 25px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color: var(--text-dark); font-size: 14px; line-height: 1.2; }
        .navbar-menu { list-style: none; }
        .navbar-menu a { text-decoration: none; color: var(--text-dark); font-weight: 500; }
        .navbar-menu a.active, .navbar-menu a:hover { color: #007bff; }
        .icon { font-size: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .page-header { font-size: 2.2em; color: white; margin-bottom: 20px; text-shadow: 1px 1px 3px rgba(0,0,0,0.4); }
        .management-card { background: rgba(255, 255, 255, 0.97); border-radius: 15px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card-header { font-size: 1.5em; margin-bottom: 10px; color: #333; }
        .filter-bar { padding: 15px; background-color: #f0f3f5; border-radius: 8px; margin-bottom: 20px; }
        .grid-container { display: grid; grid-template-columns: 1fr; gap: 30px; }
        @media (min-width: 992px) { .grid-container { grid-template-columns: 400px 1fr; } }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 1em; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; font-size: 1em; color: white; cursor: pointer; }
        .btn-primary { background-color: #0d6efd; }
        .btn-delete { background-color: var(--danger-color); padding: 5px 10px; font-size: 0.9em; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid var(--border-color); text-align: left; }
        .data-table th { background-color: var(--light-gray); }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .message.success { background-color: #d1e7dd; color: #0f5132; }
        .message.error { background-color: #f8d7da; color: #842029; }
        .page-footer { background-color: var(--primary-color); color: #fff; padding: 40px 0; }
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
        <li><a href="ditmawa_listKegiatan.php">Data Event</a></li>
        <li><a href="ditmawa_kelolaRuangan.php" class="active">Kelola Ruangan</a></li>
        <li><a href="ditmawa_dataEvent.php">Kalender Event</a></li>
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

        <div class="management-card">
            <h2 class="card-header">Kelola Gedung</h2>
            <div class="grid-container">
                <form action="ditmawa_kelolaRuangan.php" method="POST">
                    <div class="form-group"><label for="gedung_nama">Nama Gedung Baru</label><input type="text" id="gedung_nama" name="gedung_nama" placeholder="Contoh: Gedung 10" required></div>
                    <button type="submit" name="add_gedung" class="btn btn-primary">Tambah Gedung</button>
                </form>
                <div><table class="data-table"><thead><tr><th>Nama Gedung</th><th>Aksi</th></tr></thead><tbody>
                    <?php foreach($gedung_list as $gedung): ?><tr><td><?php echo htmlspecialchars($gedung['gedung_nama']); ?></td><td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus gedung ini?');"><input type="hidden" name="gedung_id" value="<?php echo $gedung['gedung_id']; ?>"><button type="submit" name="delete_gedung" class="btn btn-delete">Hapus</button></form></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </div>
        </div>

        <div class="management-card">
            <h2 class="card-header">Kelola Lantai</h2>
            <div class="filter-bar">
                <div class="form-group">
                    <label>Tampilkan Lantai untuk Gedung:</label>
                    <select id="filterGedungForLantai">
                        <option value="">Tampilkan Semua Gedung</option>
                        <?php foreach ($gedung_list as $gedung): ?><option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid-container">
                <form action="ditmawa_kelolaRuangan.php" method="POST">
                    <input type="hidden" name="gedung_id_for_lantai" id="gedung_id_for_lantai_hidden">
                    <div class="form-group"><label for="lantai_nomor">Nomor Lantai Baru (di Gedung terpilih)</label><input type="text" id="lantai_nomor" name="lantai_nomor" placeholder="Contoh: 1 atau G" required></div>
                    <button type="submit" name="add_lantai" class="btn btn-primary">Tambah Lantai</button>
                </form>
                <div><table class="data-table"><thead><tr><th>Nomor Lantai</th><th>Gedung</th><th>Aksi</th></tr></thead>
                    <tbody id="tableBodyLantai">
                        <?php foreach($lantai_list as $lantai): ?><tr data-gedung-id="<?php echo $lantai['gedung_id']; ?>"><td><?php echo htmlspecialchars($lantai['lantai_nomor']); ?></td><td><?php echo htmlspecialchars($lantai['gedung_nama']); ?></td><td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus lantai ini?');"><input type="hidden" name="lantai_id" value="<?php echo $lantai['lantai_id']; ?>"><button type="submit" name="delete_lantai" class="btn btn-delete">Hapus</button></form></td></tr><?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div class="management-card">
            <h2 class="card-header">Kelola Ruangan</h2>
            <div class="filter-bar">
                <div class="form-group"><label>Tampilkan Ruangan untuk Gedung:</label><select id="filterGedungForRuangan"><option value="">Tampilkan Semua</option><?php foreach ($gedung_list as $gedung): ?><option value="<?php echo $gedung['gedung_id']; ?>"><?php echo htmlspecialchars($gedung['gedung_nama']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Tampilkan Ruangan untuk Lantai:</label><select id="filterLantaiForRuangan" disabled><option value="">Pilih Gedung Dulu</option></select></div>
            </div>
            <div class="grid-container">
                <form action="ditmawa_kelolaRuangan.php" method="POST">
                    <input type="hidden" name="lantai_id_for_ruangan" id="lantai_id_for_ruangan_hidden">
                    <div class="form-group"><label for="ruangan_nama">Nama Ruangan Baru (di Lantai terpilih)</label><input type="text" id="ruangan_nama" name="ruangan_nama" placeholder="Contoh: 10317" required></div>
                    <button type="submit" name="add_ruangan" class="btn btn-primary">Tambah Ruangan</button>
                </form>
                <div><table class="data-table"><thead><tr><th>Nama Ruangan</th><th>Lantai</th><th>Gedung</th><th>Aksi</th></tr></thead>
                    <tbody id="tableBodyRuangan">
                        <?php foreach($ruangan_list as $ruangan): ?><tr data-gedung-id="<?php echo $ruangan['gedung_id']; ?>" data-lantai-id="<?php echo $ruangan['lantai_id']; ?>"><td><?php echo htmlspecialchars($ruangan['ruangan_nama']); ?></td><td><?php echo htmlspecialchars($ruangan['lantai_nomor']); ?></td><td><?php echo htmlspecialchars($ruangan['gedung_nama']); ?></td><td><form action="ditmawa_kelolaRuangan.php" method="POST" onsubmit="return confirm('Yakin hapus ruangan ini?');"><input type="hidden" name="ruangan_id" value="<?php echo $ruangan['ruangan_id']; ?>"><button type="submit" name="delete_ruangan" class="btn btn-delete">Hapus</button></form></td></tr><?php endforeach; ?>
                    </tbody>
                </table></div>
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
    const allFloorsData = <?php echo json_encode($lantai_list); ?>;
    const filterGedungLantai = document.getElementById('filterGedungForLantai');
    const tableBodyLantai = document.getElementById('tableBodyLantai').getElementsByTagName('tr');
    const hiddenGedungIdLantai = document.getElementById('gedung_id_for_lantai_hidden');
    filterGedungLantai.addEventListener('change', function() {
        const selectedGedung = this.value;
        hiddenGedungIdLantai.value = selectedGedung;
        for (let row of tableBodyLantai) {
            if (selectedGedung === "" || row.dataset.gedungId === selectedGedung) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
    const filterGedungRuangan = document.getElementById('filterGedungForRuangan');
    const filterLantaiRuangan = document.getElementById('filterLantaiForRuangan');
    const tableBodyRuangan = document.getElementById('tableBodyRuangan').getElementsByTagName('tr');
    const hiddenLantaiIdRuangan = document.getElementById('lantai_id_for_ruangan_hidden');
    filterGedungRuangan.addEventListener('change', function() {
        const selectedGedung = this.value;
        filterLantaiRuangan.innerHTML = '<option value="">Semua Lantai</option>';
        if (selectedGedung) {
             allFloorsData.filter(floor => floor.gedung_id == selectedGedung)
                         .forEach(floor => {
                             const option = document.createElement('option');
                             option.value = floor.lantai_id;
                             option.textContent = floor.lantai_nomor;
                             filterLantaiRuangan.appendChild(option);
                         });
            filterLantaiRuangan.disabled = false;
        } else {
            filterLantaiRuangan.innerHTML = '<option value="">Pilih Gedung Dulu</option>';
            filterLantaiRuangan.disabled = true;
        }
        hiddenLantaiIdRuangan.value = "";
        for (let row of tableBodyRuangan) {
            if (selectedGedung === "" || row.dataset.gedungId === selectedGedung) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
    filterLantaiRuangan.addEventListener('change', function() {
        const selectedLantai = this.value;
        const selectedGedung = filterGedungRuangan.value;
        hiddenLantaiIdRuangan.value = selectedLantai;
        for (let row of tableBodyRuangan) {
            if (row.dataset.gedungId === selectedGedung) {
                 if (selectedLantai === "" || row.dataset.lantaiId === selectedLantai) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            }
        }
    });
</script>

</body>
</html>