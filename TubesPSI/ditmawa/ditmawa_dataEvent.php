<?php
session_start();
require_once('../config/db_connection.php');

// Default user fallback (jika login tidak berhasil)
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = "Peter Carera";
}
$username = $_SESSION['username'];

// Query gabungan Pengajuan_Event dan Mahasiswa
$query = "
    SELECT 
        m.mahasiswa_nama AS nama,
        m.mahasiswa_email AS email,
        m.mahasiswa_npm AS npm,
        pe.pengajuan_status AS status,
        pe.pengajuan_event_proposal_file AS form_file
    FROM Pengajuan_Event pe
    JOIN Mahasiswa m ON pe.mahasiwa_id = m.mahasiswa_id
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Event Mahasiswa - Ditmawa UNPAR</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f6f5ef;
            color: #333;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffa726;
            padding: 15px 30px;
            color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: black;
            font-weight: bold;
        }

        .logo img {
            height: 50px;
        }

        nav a {
            margin: 0 15px;
            color: black;
            font-weight: bold;
            text-decoration: none;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: black;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background: #ddd;
            border-radius: 50%;
        }

        main {
            padding: 50px 20px;
            max-width: 1000px;
            margin: auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f2f2f2;
            color: #333;
        }

        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            background: #2ecc71;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .form-link a {
            color: #0d47a1;
            font-weight: bold;
            text-decoration: none;
        }

        .form-link a:hover {
            text-decoration: underline;
        }

        .pagination {
            margin-top: 15px;
            text-align: right;
            color: #333;
            font-weight: bold;
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo">
        <img src="../img/logoUnpar.jpg" alt="UNPAR" />
        <span>Pengelolaan Event UNPAR</span>
    </div>
    <nav>
        <a href="ditmawa_home.php">Home</a>
        <a href="ditmawa_dataEvent.php">Data Event</a>
        <a href="#">Laporan</a>
    </nav>
    <div class="user-info">
        <span><?= htmlspecialchars($username); ?></span>
        <div class="avatar"></div>
        <a href="logout.php" title="Logout" style="color:black;">⎋</a>
    </div>
</header>

<main>
    <h2>List Data Pengajuan Event Mahasiswa</h2>

    <table>
        <thead>
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>NPM</th>
            <th>Status</th>
            <th>FORM</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['npm']) ?></td>
                    <td><span class="status"><?= strtoupper($row['status']) ?></span></td>
                    <td class="form-link">
                        <?php if (!empty($row['form_file'])): ?>
                            <a href="uploads/<?= urlencode($row['form_file']) ?>" target="_blank">FORM EVENT</a>
                        <?php else: ?>
                            <em>Tidak tersedia</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">Belum ada data pengajuan.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">Page 1</div>
</main>

</body>
</html>
