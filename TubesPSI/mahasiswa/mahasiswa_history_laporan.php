<?php
session_start();
include '../config/db_connection.php';

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

$laporan_pertanggungjawaban = [];

if ($user_id !== 'No ID') {
    // === PERBAIKAN QUERY DI SINI ===
    // Mengambil data dari tabel 'pengajuan_event'
    // Memilih kolom pengajuan_id sebagai laporan_id (untuk identifikasi baris)
    // Memilih pengajuan_namaEvent sebagai nama_acara
    // Memilih pengajuan_LPJ sebagai dokumen_lpj_file
    // Menambahkan kolom untuk nama dan npm mahasiswa (jika diperlukan untuk tampilan, asumsi dari tabel pengajuan_event bisa didapatkan)
    // Jika nama dan npm mahasiswa ada di tabel 'mahasiswa' dan pengajuan_event hanya menyimpan ID, maka tetap JOIN ke 'mahasiswa'
    // Asumsi: 'tanggal_upload' bisa diwakili oleh 'pengajuan_event_tanggal_selesai' atau 'pengajuan_event_tanggal_mulai'
    // Karena LPJ diajukan setelah event selesai, lebih logis menggunakan tanggal selesai atau tanggal pengajuan LPJ jika ada kolomnya.
    // Jika tidak ada kolom tanggal pengajuan LPJ di tabel pengajuan_event, kita bisa pakai tanggal selesai event sebagai proxy.

    $stmt = $conn->prepare("
        SELECT
            pe.pengajuan_id AS laporan_id,
            m.mahasiswa_nama,
            m.mahasiswa_npm,
            pe.pengajuan_event_tanggal_selesai AS tanggal_upload, -- Menggunakan tanggal selesai event sebagai contoh tanggal upload
            pe.pengajuan_namaEvent AS nama_acara,
            pe.pengajuan_LPJ AS dokumen_lpj_file
        FROM
            pengajuan_event pe
        JOIN
            mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id
        WHERE
            pe.mahasiswa_id = ? AND pe.pengajuan_LPJ IS NOT NULL AND pe.pengajuan_LPJ != ''
        ORDER BY
            pe.pengajuan_event_tanggal_selesai DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $laporan_pertanggungjawaban[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pertanggung Jawaban - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* General Body and Navbar Styling (Keep from your existing code) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e5ec 100%);
            min-height: 100vh;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background:rgb(2, 71, 25);
            width: 100%;
            padding: 10px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', sans-serif;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .navbar-title {
            color:rgb(255, 255, 255);
            font-size: 14px;
            line-height: 1.2;
        }

        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        .navbar-menu li a {
            text-decoration: none;
            color:rgb(253, 253, 253);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .navbar-menu li a:hover {
            color: #007bff;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color:rgb(255, 255, 255);
        }

        .user-name {
            font-weight: 500;
        }

        .icon {
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .icon:hover {
            color: #007bff;
        }

        /* Main Content and Table Styling */
        .container {
            max-width: 900px;
            margin: 100px auto 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 30px;
        }

        .header {
            background:rgb(44, 62, 80);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .header h1 {
            font-size: 24px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }

        .data-table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: 600;
            text-transform: uppercase;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .data-table tr:hover {
            background-color: #f1f1f1;
        }

        .download-button {
            background-color: #f44336; /* Red for download */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }

        .download-button:hover {
            background-color: #d32f2f;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
        }

        /* Message pop-up styling (from image_c5b0f6.png) */
        .download-message {
            position: fixed;
            top: 80px; /* Adjust as needed, below navbar */
            right: 20px;
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1001; /* Above other content */
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            pointer-events: none; /* Allows clicks to pass through when hidden */
        }
        .download-message.show {
            opacity: 1;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>

<div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="header">
        <h1>List Laporan Pertanggung Jawaban</h1>
        <a href="mahasiswa_history.php" class="download-button" style="background-color: #6c757d;">Kembali</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>NAMA</th>
                <th>NPM</th>
                <th>TANGGAL UPLOAD</th>
                <th>NAMA ACARA</th>
                <th>DOKUMEN LPJ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($laporan_pertanggungjawaban)): ?>
                <?php foreach ($laporan_pertanggungjawaban as $laporan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($laporan['mahasiswa_nama']); ?></td>
                        <td><?php echo htmlspecialchars($laporan['mahasiswa_npm']); ?></td>
                        <td><?php echo htmlspecialchars(date('d F Y', strtotime($laporan['tanggal_upload']))); ?></td>
                        <td><?php echo htmlspecialchars($laporan['nama_acara']); ?></td>
                        <td>
                            <?php if (!empty($laporan['dokumen_lpj_file'])): ?>
                                <a href="../uploads/lpj/<?php echo urlencode(basename($laporan['dokumen_lpj_file'])); ?>"
                                   class="download-button" download onclick="showDownloadMessage()">DOWNLOAD</a>
                            <?php else: ?>
                                Tidak Tersedia
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-data">Belum ada laporan pertanggung jawaban yang diunggah.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="downloadMessage" class="download-message">
    Dokumen berhasil di-download!
</div>

<script>
    function showDownloadMessage() {
        const message = document.getElementById('downloadMessage');
        message.classList.add('show');
        setTimeout(() => {
            message.classList.remove('show');
        }, 3000); // Hide after 3 seconds
    }
</script>

</body>
</html>