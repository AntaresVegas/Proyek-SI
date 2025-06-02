<?php
session_start();
require_once('../config/db_connection.php');

// Ambil ID pengajuan dari URL
if (!isset($_GET['id'])) {
    die("ID tidak ditemukan");
}
$id = intval($_GET['id']);

// Query detail
$query = "
    SELECT 
        m.mahasiswa_nama, m.mahasiswa_email, m.mahasiswa_npm, m.mahasiswa_jurusan,
        u.unit_nama,
        o.organisasi_nama,
        pe.pengajuan_namaEvent, pe.pengajuan_event_jam_mulai, pe.pengajuan_event_jam_selesai,
        pe.pengajuan_event_tanggal_mulai, pe.pengajuan_event_tanggal_selesai,
        pe.jadwal_event_rundown_file, pe.pengajuan_event_proposal_file,
        r.ruangan_nama, g.gedung_nama, l.lantai_nomor,
        pe.pengajuan_status, pe.pengajuan_komentarDitmawa
    FROM pengajuan_event pe
    LEFT JOIN mahasiswa m ON pe.mahasiswa_id = m.mahasiswa_id
    LEFT JOIN unit u ON pe.unit_id = u.unit_id
    LEFT JOIN organisasi o ON pe.organisasi_id = o.organisasi_id
    LEFT JOIN peminjaman_ruangan pr ON pe.peminjaman_id = pr.peminjaman_id
    LEFT JOIN ruangan r ON pr.ruangan_id = r.ruangan_id
    LEFT JOIN lantai l ON r.lantai_id = l.lantai_id
    LEFT JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE pe.pengajuan_id = $id
";

$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    die("Data tidak ditemukan");
}
$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pengajuan Event</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #fefbe9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #ffa726;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo img {
            height: 50px;
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin: 30px 0 10px;
            font-weight: bold;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .row label {
            font-weight: bold;
            width: 40%;
        }
        .row .value {
            width: 55%;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .buttons {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 20px;
        }
        .buttons button {
            padding: 10px 20px;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .approve { background-color: #27ae60; color: white; }
        .reject { background-color: #e74c3c; color: white; }
        .back { background-color: #f0f0f0; color: black; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../img/logoUnpar.jpg" alt="Logo UNPAR">
    </div>
    <div style="font-weight:bold;">Peter Carera</div>
</header>

<div class="title">Form Pengajuan Event Mahasiswa</div>

<div class="container">
    <?php
    function showRow($label, $value) {
        echo "<div class='row'><label>$label</label><div class='value'>".htmlspecialchars($value ?? '-')."</div></div>";
    }

    showRow("Nama", $data['mahasiswa_nama']);
    showRow("Email", $data['mahasiswa_email']);
    showRow("NPM", $data['mahasiswa_npm']);
    showRow("Jurusan", $data['mahasiswa_jurusan']);
    showRow("Nama Unit", $data['unit_nama']);
    showRow("Organisasi Penyelenggara", $data['organisasi_nama']);
    showRow("Nama Event", $data['pengajuan_namaEvent']);
    showRow("Nama Ruangan", $data['gedung_nama']);
    showRow("Nama dan Nomor Ruangan", "Lantai {$data['lantai_nomor']} - {$data['ruangan_nama']}");
    showRow("Jam Mulai", $data['pengajuan_event_jam_mulai']);
    showRow("Jam Selesai", $data['pengajuan_event_jam_selesai']);
    showRow("Tanggal Mulai", $data['pengajuan_event_tanggal_mulai']);
    showRow("Tanggal Selesai", $data['pengajuan_event_tanggal_selesai']);

    echo "<div class='row'><label>Rundown Acara</label><div class='value'>";
    if (!empty($data['jadwal_event_rundown_file'])) {
        echo "<a href='uploads/".urlencode($data['jadwal_event_rundown_file'])."' target='_blank'>Download Rundown</a>";
    } else {
        echo "<em>Tidak tersedia</em>";
    }
    echo "</div></div>";

    echo "<div class='row'><label>Proposal Penyelenggaraan</label><div class='value'>";
    if (!empty($data['pengajuan_event_proposal_file'])) {
        echo "<a href='uploads/".urlencode($data['pengajuan_event_proposal_file'])."' target='_blank'>Download Proposal</a>";
    } else {
        echo "<em>Tidak tersedia</em>";
    }
    echo "</div></div>";
    ?>

    <div class="buttons">
        <button class="back" onclick="window.history.back()">Kembali</button>
        <button class="approve">SETUJUI</button>
        <button class="reject">TOLAK</button>
    </div>

    <div style="margin-top: 30px;">
        <label for="komentar">Alasan Status :</label><br>
        <textarea id="komentar" rows="4" placeholder="Tuliskan alasannya di sini..."><?= htmlspecialchars($data['pengajuan_komentarDitmawa']) ?></textarea>
    </div>
</div>

</body>
</html>
