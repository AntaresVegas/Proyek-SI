<?php
session_start();
include '../config/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

// Handle file upload
$upload_dir = '../uploads/pembatalan/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'ajukan_pembatalan') {
    $pengajuan_id = $_POST['pengajuan_id'];
    $file_pembatalan = $_FILES['surat_pembatalan'];
    $error_upload = '';

    if ($file_pembatalan['error'] === UPLOAD_ERR_OK) {
        $file_name = uniqid() . '_' . basename($file_pembatalan['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validasi tipe file
        if ($file_type != "pdf" && $file_type != "docx") {
            $_SESSION['error_message'] = "Hanya file PDF dan DOCX yang diizinkan untuk Surat Pembatalan.";
        } else {
            if (move_uploaded_file($file_pembatalan['tmp_name'], $target_file)) {
                $file_path_db = substr($target_file, 3); // Simpan path relatif ke DB

                $conn->begin_transaction();
                try {
                    // Reset komentar pembatalan jika ada pengajuan baru
                    $stmt_update = $conn->prepare("UPDATE pengajuan_event SET pengajuan_status_pembatalan = 'Diajukan', surat_pembatalan_file = ?, komentar_ditmawa_pembatalan = NULL, pengajuan_tanggalEdit = NOW() WHERE pengajuan_id = ? AND pengaju_id = ?");
                    if (!$stmt_update) throw new Exception("Prepare statement update gagal: " . $conn->error);
                    $stmt_update->bind_param("sii", $file_path_db, $pengajuan_id, $user_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Notifikasi ke Ditmawa
                    $ditmawa_id_notif = 1; 
                    $message = "Pengajuan Pembatalan Event #{$pengajuan_id} telah diajukan.";
                    $link = "ditmawa/ditmawa_editForm.php?id=" . $pengajuan_id; 

                    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                    if (!$notif_stmt) throw new Exception("Prepare statement notif gagal: " . $conn->error);
                    $notif_stmt->bind_param("iss", $ditmawa_id_notif, $message, $link); 
                    $notif_stmt->execute();
                    $notif_stmt->close();
                    
                    $conn->commit();
                    $_SESSION['success_message'] = "Pengajuan pembatalan berhasil diunggah dan sedang menunggu persetujuan Ditmawa.";
                } catch (Exception $e) {
                    $conn->rollback();
                    if (isset($target_file) && file_exists($target_file)) {
                        unlink($target_file); 
                    }
                    $_SESSION['error_message'] = "Gagal memproses pengajuan: " . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Gagal mengunggah file surat pembatalan.";
            }
        }
    } elseif ($file_pembatalan['error'] !== UPLOAD_ERR_NO_FILE) {
         $_SESSION['error_message'] = "Terjadi error upload: Code " . $file_pembatalan['error'];
    } else {
        $_SESSION['error_message'] = "Anda harus mengunggah file surat pembatalan.";
    }
    header("Location: mahasiswa_history_pengajuan.php");
    exit();
}


$search_event = $_GET['search_event'] ?? '';
$selected_status_proposal = $_GET['status_proposal'] ?? '';

$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

$pagination_query_params = $_GET;
unset($pagination_query_params['page']);
$pagination_query_string = http_build_query($pagination_query_params);
if (!empty($pagination_query_string)) {
    $pagination_query_string = '&' . $pagination_query_string;
}


$pengajuan_events = [];
$total_rows = 0;
$total_pages = 0;

if ($user_id !== 'No ID') {
    $conditions = ["pe.pengaju_id = ? AND pe.pengaju_tipe = 'mahasiswa'"];
    $params = [$user_id];
    $types = "i";

    if (!empty($search_event)) {
        $conditions[] = "LOWER(pe.pengajuan_namaEvent) LIKE LOWER(?)";
        $search_param = "%" . $search_event . "%";
        $params[] = $search_param;
        $types .= "s";
    }

    if (!empty($selected_status_proposal)) {
        if ($selected_status_proposal === 'Ditolak') {
            $conditions[] = "(pe.pengajuan_status_ditmawa = 'Ditolak' OR pe.pengajuan_status_asp = 'Ditolak' OR pe.pengajuan_status_proposal = 'Ditolak' OR pe.pengajuan_status_pembatalan = 'Disetujui')";
        } elseif ($selected_status_proposal === 'Disetujui') {
            $conditions[] = "(pe.pengajuan_status_ditmawa <> 'Ditolak' AND pe.pengajuan_status_asp <> 'Ditolak' AND pe.pengajuan_status_proposal = 'Disetujui' AND pe.pengajuan_status_pembatalan <> 'Disetujui' AND pe.pengajuan_status_pembatalan <> 'Diajukan')";
        } elseif ($selected_status_proposal === 'Diajukan') {
            $conditions[] = "(pe.pengajuan_status_ditmawa <> 'Ditolak' AND pe.pengajuan_status_asp <> 'Ditolak' AND pe.pengajuan_status_proposal = 'Diajukan' AND pe.pengajuan_status_pembatalan <> 'Disetujui')";
        } elseif ($selected_status_proposal === 'Pembatalan Diajukan') {
            $conditions[] = "(pe.pengajuan_status_pembatalan = 'Diajukan')";
        }
    }
    
    $where_clause = " WHERE " . implode(' AND ', $conditions);

    try {
        $count_sql = "SELECT COUNT(pe.pengajuan_id) as total FROM pengajuan_event pe" . $where_clause;
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        if ($count_result) {
            $total_rows = $count_result->fetch_assoc()['total'];
            $total_pages = ceil($total_rows / $limit);
        }
        $count_stmt->close();

        $stmt = $conn->prepare("
            SELECT
                pe.pengajuan_id,
                pe.pengajuan_event_tanggal_mulai,
                pe.pengajuan_namaEvent,
                pe.pengajuan_status_ditmawa,
                pe.komentar_ditmawa,
                pe.pengajuan_status_asp,
                pe.komentar_asp,
                pe.surat_izin_kegiatan_file, 
                pe.pengajuan_status_pembatalan,
                pe.komentar_ditmawa_pembatalan,
                
                CASE 
                    WHEN pe.pengajuan_status_ditmawa = 'Ditolak' OR pe.pengajuan_status_asp = 'Ditolak' 
                    THEN 'Ditolak' 
                    WHEN pe.pengajuan_status_pembatalan = 'Disetujui'
                    THEN 'Dibatalkan'
                    ELSE pe.pengajuan_status_proposal 
                END AS pengajuan_status_proposal,
                
                pe.pengajuan_tanggalEdit
            FROM pengajuan_event pe
            " . $where_clause . "
            ORDER BY pe.pengajuan_id DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pengajuan_events[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching history pengajuan: " . $e->getMessage());
    }
}
$conn->close();

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Pengajuan Event - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: rgb(2, 71, 25);
            --primary-light: #f7fff8;
            --primary-border: #d4e9d6;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --grey-color: #6c757d;
            --text-light: #8895a7;
            --border-color: #e5e7eb;
            --white: #ffffff;
            --bg-light: #f9fafb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100%;
            padding-top: 80px;
            background-image: url('../img/backgroundUnpar.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper { flex-grow: 1; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:var(--primary-color); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:white; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #87CEEB; } 
        .navbar-right { display: flex; align-items: center; gap: 15px; color:white; }
        .icon { font-size: 20px; cursor: pointer; }
        
        .container { 
            max-width: 1300px; /* Lebarkan container agar kolom baru muat */
            margin: 20px auto 30px; 
            background: rgba(255, 255, 255, 0.98); 
            backdrop-filter: blur(5px); 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); 
            padding: 30px; 
        }
        .header { background:rgb(44, 62, 80); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; margin: -30px -30px 30px -30px; border-radius: 15px 15px 0 0; }
        .header h1 { font-size: 24px; }
        .kembali-button { background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; }
        
        /* [BARU] CSS untuk Peringatan Pembatalan */
        .cancellation-info {
            background-color: #f0f8ff; /* Light Blue BG */
            border-left: 5px solid #007bff;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            color: #333;
            font-size: 14px;
        }
        .cancellation-info strong {
            color: #007bff;
        }


        .filter-form { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 15px; 
            margin-bottom: 25px; 
            justify-content: center; 
            align-items: center; 
            padding: 20px; 
            background-color: var(--bg-light); 
            border-radius: 10px; 
        }
        .filter-form label { font-weight: 600; color: #555; }
        .filter-form select, .filter-form input, .filter-form button { 
            padding: 10px 14px; 
            border-radius: 8px; 
            border: 1px solid var(--border-color); 
            font-size: 14px;
        }
        .filter-form input[type="text"] { min-width: 250px; }
        .filter-form button { 
            background-color: var(--primary-color); 
            color: white; 
            border: none; 
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .filter-form button:hover { background-color: rgb(3, 100, 36); }
        
        .data-table-container { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td { border-bottom: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: top; }
        .data-table th { background-color: #f8f9fa; font-weight: 600; text-transform: uppercase; white-space: nowrap; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .no-data { text-align: center; padding: 20px; color: #777; }
        .status-badge { padding: 5px 12px; border-radius: 15px; font-weight: bold; color: white; text-align: center; font-size: 12px; text-transform: capitalize; display: inline-block; }
        .status-badge.disetujui { background-color: var(--success-color); }
        .status-badge.ditolak { background-color: var(--danger-color); }
        .status-badge.diajukan { background-color: var(--warning-color); color: #333; }
        .status-badge.dibatalkan { background-color: #000; } 
        .status-badge.diajukan_batal { background-color: #ff5722; } 
        .status-badge.tidak_ada { background-color: var(--grey-color); }

        /* Aksi buttons */
        .btn-action { 
            color: white; 
            padding: 8px 15px; 
            border-radius: 5px; 
            text-decoration: none; 
            font-size: 14px; 
            display: inline-flex; 
            align-items: center; 
            gap: 5px; 
            border: none; 
            font-family: 'Segoe UI';
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-edit { background-color: #007bff; }
        .btn-edit:hover { background-color: #0056b3; }
        .btn-detail { background-color: var(--info-color); }
        .btn-detail:hover { background-color: #138496; }
        .btn-download-sik { background-color: var(--success-color); }
        .btn-download-sik:hover { background-color: #218838; }
        .btn-batal { background-color: var(--danger-color); } 
        .btn-batal:hover { background-color: #a71d2a; }

        .action-disabled { display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; border-radius: 5px; background-color: var(--grey-color); color: white; font-size: 14px; font-weight: 500; cursor: not-allowed; }
        
        .alasan-ditolak { font-size: 13px; color: #dc3545; margin-top: 5px; font-style: italic; max-width: 250px; }
        .modified-info { font-size: 12px; color: #666; margin-top: 5px; }

        .page-footer { background-color: var(--primary-color); color: #e9ecef; padding: 40px 0; margin-top: auto; }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px; }
        .footer-left { display: flex; align-items: center; gap: 20px; }
        .footer-logo { width: 60px; height: 60px; }
        .footer-left h4 { font-size: 1.2em; font-weight: 500; line-height: 1.4; }
        .footer-right ul { list-style: none; padding: 0; margin: 0; }
        .footer-right li { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .footer-right .social-icons { margin-top: 20px; display: flex; gap: 15px; }
        .footer-right .social-icons a { color: #e9ecef; font-size: 1.5em; transition: color 0.3s; }
        .footer-right .social-icons a:hover { color: #fff; }
         
        .pagination-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 1.5rem 0; margin-top: 20px; border-top: 1px solid var(--border-color); }
        .pagination-info { color: var(--text-light); font-size: 0.9rem; }
        .pagination-links { display: flex; gap: 5px; }
        .page-link { text-decoration: none; padding: 0.5rem 1rem; border: 1px solid var(--border-color); background: var(--white); color: var(--primary-color); border-radius: 8px; font-weight: 500; transition: background 0.2s, color 0.2s; }
        .page-link:hover { background-color: var(--primary-light); border-color: var(--primary-border); }
        .page-link.active { background-color: var(--primary-color); color: var(--white); border-color: var(--primary-color); }
        .page-link.disabled { color: var(--text-light); pointer-events: none; background-color: var(--bg-light); }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px); }
        .modal-content { 
            background-color: var(--white); 
            margin: 10% auto; 
            padding: 0; 
            border: none;
            width: 90%; 
            max-width: 600px; 
            border-radius: 12px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
            animation: fadeIn 0.3s;
            overflow: hidden;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header-custom {
            background-color: var(--danger-color);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header-custom h2 {
            margin: 0;
            font-size: 22px;
            display: flex;
            align-items: center;
        }
        .modal-header-custom i {
            margin-right: 10px;
        }
        .close-btn { 
            color: white; 
            float: right; 
            font-size: 30px; 
            font-weight: normal;
            transition: color 0.3s;
        }
        .close-btn:hover, .close-btn:focus { 
            color: #ccc; 
            text-decoration: none; 
            cursor: pointer; 
        }

        .modal-body-custom {
            padding: 30px;
        }

        .modal-step {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            background-color: #fffaf7; 
        }
        .modal-step h3 {
            font-size: 16px;
            color: var(--text-dark);
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .modal-step .step-number {
            display: inline-block;
            background-color: var(--danger-color);
            color: white;
            width: 24px;
            height: 24px;
            text-align: center;
            line-height: 24px;
            border-radius: 50%;
            margin-right: 10px;
            font-size: 14px;
        }

        .modal-body-custom input[type="file"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid var(--border-color); 
            border-radius: 5px;
            background-color: var(--white);
            cursor: pointer;
        }
        .modal-footer { 
            padding: 20px 30px;
            border-top: 1px solid var(--border-color);
            display: flex; 
            justify-content: flex-end;
            gap: 10px;
            background-color: var(--bg-light);
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logo.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title"><span>Pengelolaan</span><br><strong>Event UNPAR</strong></div>
    </div>
    <ul class="navbar-menu">
        <li><a href="mahasiswa_dashboard.php">Home</a></li>
        <li><a href="mahasiswa_fasilitas.php">Fasilitas</a></li>
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_kalender_gabungan.php">Kalender Gabungan</a></li> 
        <li><a href="mahasiswa_event.php">Kalender Event</a></li>
        <li><a href="mahasiswa_laporan.php">Laporan</a></li>
        <li><a href="mahasiswa_history.php" class="active">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <div class="header">
            <h1>History Pengajuan Event</h1>
            <a href="mahasiswa_history.php" class="kembali-button"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if ($success_message): ?>
            <div style="padding: 15px; background-color: var(--success-color); color: white; border-radius: 8px; margin-bottom: 20px; text-align: center;"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div style="padding: 15px; background-color: var(--danger-color); color: white; border-radius: 8px; margin-bottom: 20px; text-align: center;"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="cancellation-info">
            <p>
                <i class="fas fa-info-circle"></i> <strong>Informasi Pembatalan Event:</strong>
            </p>
            <ul>
                <li>Pengajuan pembatalan hanya dapat dilakukan untuk event yang **SUDAH DISEUJUI oleh minimal satu pihak (Ditmawa ATAU ASP)**.</li>
                <li>Jika event masih berstatus **'Diajukan'** (Belum ada persetujuan Ditmawa atau ASP), Anda dapat langsung melakukan perubahan/pembatalan dengan **mengedit ulang** pengajuan melalui tombol 'Detail' atau 'Edit Ulang' di kolom Aksi.</li>
                <li>Permohonan pembatalan harus didasari alasan yang **urgen/mendesak** dan memerlukan persetujuan dari Direktorat Kemahasiswaan (Ditmawa).</li>
            </ul>
        </div>
        <form method="GET" class="filter-form">
            <label for="status_proposal">Status Proposal:</label>
            <select name="status_proposal" id="status_proposal">
                <option value="">Semua Status</option>
                <option value="Diajukan" <?php echo ($selected_status_proposal == 'Diajukan' ? 'selected' : ''); ?>>Diajukan</option>
                <option value="Disetujui" <?php echo ($selected_status_proposal == 'Disetujui' ? 'selected' : ''); ?>>Disetujui</option>
                <option value="Ditolak" <?php echo ($selected_status_proposal == 'Ditolak' ? 'selected' : ''); ?>>Ditolak / Dibatalkan</option>
                <option value="Pembatalan Diajukan" <?php echo ($selected_status_proposal == 'Pembatalan Diajukan' ? 'selected' : ''); ?>>Pembatalan Diajukan</option>
            </select>
            
            <label for="search_event" style="margin-left: 10px;">Cari Event:</label>
            <input type="text" id="search_event" name="search_event" placeholder="Masukkan nama event..." value="<?php echo htmlspecialchars($search_event); ?>">
            
            <button type="submit"><i class="fas fa-filter"></i> Filter & Cari</button>
        </form>

        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>TANGGAL & NAMA EVENT</th>
                        <th>STATUS DITMAWA</th>
                        <th>STATUS ASP</th>
                        <th>STATUS BATAL</th> <th>STATUS PROPOSAL</th>
                        <th>LAST MODIFIED</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pengajuan_events)): ?>
                        <?php foreach ($pengajuan_events as $event): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?></strong>
                                    <div class="modified-info"><?php echo htmlspecialchars(date('d M Y', strtotime($event['pengajuan_event_tanggal_mulai']))); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(htmlspecialchars($event['pengajuan_status_ditmawa'])); ?>">
                                        <?php echo htmlspecialchars($event['pengajuan_status_ditmawa']); ?>
                                    </span>
                                    <?php if ($event['pengajuan_status_ditmawa'] == 'Ditolak' && !empty($event['komentar_ditmawa'])): ?>
                                        <div class="alasan-ditolak">
                                            <strong>Alasan:</strong> <?php echo htmlspecialchars($event['komentar_ditmawa']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower(htmlspecialchars($event['pengajuan_status_asp'])); ?>">
                                        <?php echo htmlspecialchars($event['pengajuan_status_asp']); ?>
                                    </span>
                                    <?php if ($event['pengajuan_status_asp'] == 'Ditolak' && !empty($event['komentar_asp'])): ?>
                                        <div class="alasan-ditolak">
                                            <strong>Alasan:</strong> <?php echo htmlspecialchars($event['komentar_asp']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php 
                                        $batal_status = $event['pengajuan_status_pembatalan'];
                                        $batal_class = strtolower(str_replace(' ', '_', $batal_status));
                                        
                                        if ($batal_status == 'Diajukan') {
                                            echo '<span class="status-badge diajukan_batal">Diajukan</span>';
                                        } elseif ($batal_status == 'Disetujui') {
                                            echo '<span class="status-badge dibatalkan">Disetujui</span>';
                                        } elseif ($batal_status == 'Ditolak') {
                                            echo '<span class="status-badge ditolak">Ditolak</span>';
                                            if (!empty($event['komentar_ditmawa_pembatalan'])): 
                                        ?>
                                            <div class="alasan-ditolak" style="font-size: 11px;">
                                                (Alasan Batal Ditolak)
                                            </div>
                                        <?php endif;
                                        } else {
                                            echo '<span class="status-badge tidak_ada">N/A</span>';
                                        }
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower(htmlspecialchars($event['pengajuan_status_proposal'])); ?>">
                                        <?php 
                                            // Status Final (Dibatalkan atau Ditolak)
                                            if ($event['pengajuan_status_pembatalan'] == 'Disetujui') {
                                                echo 'Dibatalkan';
                                            } else {
                                                echo htmlspecialchars($event['pengajuan_status_proposal']);
                                            }
                                        ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if (!empty($event['pengajuan_tanggalEdit'])): ?>
                                        <div class="modified-info">
                                            <?php echo htmlspecialchars(date('d M Y H:i', strtotime($event['pengajuan_tanggalEdit']))); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="modified-info">N/A</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        // --- LOGIKA UTAMA: Kondisi untuk menampilkan Tombol Batalkan atau Edit Ulang ---
                                        $is_fully_approved_proposal = ($event['pengajuan_status_ditmawa'] == 'Disetujui' && $event['pengajuan_status_asp'] == 'Disetujui' && $event['pengajuan_status_proposal'] == 'Disetujui');
                                        $is_approved_by_at_least_one = ($event['pengajuan_status_ditmawa'] == 'Disetujui' || $event['pengajuan_status_asp'] == 'Disetujui');
                                        
                                        if ($is_approved_by_at_least_one && $event['pengajuan_status_pembatalan'] === 'Tidak Ada'): 
                                    ?>
                                        <button class="btn-action btn-batal" onclick="openCancelModal(<?php echo $event['pengajuan_id']; ?>, '<?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?>')">
                                            <i class="fas fa-times-circle"></i> Batalkan
                                        </button>

                                        <?php 
                                            // Tombol SIK (Download atau Menunggu) HANYA jika proposal sudah disetujui penuh
                                            if ($is_fully_approved_proposal):
                                                if (!empty($event['surat_izin_kegiatan_file'])): ?>
                                                    <a href="../<?php echo htmlspecialchars($event['surat_izin_kegiatan_file']); ?>" class="btn-action btn-download-sik" download>
                                                        <i class="fas fa-file-download"></i> Unduh SIK
                                                    </a>
                                                <?php else: ?>
                                                    <span class="action-disabled" title="Event Disetujui, menunggu SIK diterbitkan oleh Sekretariat.">
                                                        <i class="fas fa-hourglass-half"></i> Menunggu SIK
                                                    </span>
                                                <?php endif; 
                                            endif; // end is_fully_approved_proposal check for SIK
                                        ?>
                                    
                                    <?php elseif ($event['pengajuan_status_pembatalan'] === 'Diajukan'): ?>
                                         <span class="action-disabled" title="Menunggu konfirmasi pembatalan dari Ditmawa.">
                                            <i class="fas fa-hourglass-half"></i> Pembatalan Pending
                                        </span>
                                        
                                    <?php elseif ($event['pengajuan_status_proposal'] == 'Ditolak' || $event['pengajuan_status_pembatalan'] === 'Disetujui' || $event['pengajuan_status_pembatalan'] === 'Ditolak'): ?>
                                         <a href="mahasiswa_editForm.php?id=<?php echo $event['pengajuan_id']; ?>&page=<?php echo $page; ?><?php echo $pagination_query_string; ?>" class="btn-action btn-edit">
                                            <i class="fas fa-edit"></i> Edit Ulang
                                        </a>
                                        
                                    <?php else: // Status 'Diajukan' Awal (Perlu di-edit jika ada revisi) ?>
                                        <a href="mahasiswa_editForm.php?id=<?php echo $event['pengajuan_id']; ?>&page=<?php echo $page; ?><?php echo $pagination_query_string; ?>" class="btn-action btn-detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">Belum ada pengajuan event yang cocok dengan kriteria Anda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1 && !empty($pengajuan_events)): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Menampilkan <strong><?php echo count($pengajuan_events); ?></strong> dari <strong><?php echo $total_rows; ?></strong> data
            </div>
            <div class="pagination-links">
                <a href="?page=<?php echo $page - 1; ?><?php echo $pagination_query_string; ?>" class="page-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    &laquo;
                </a>
                
                <?php
                    $window = 2; 
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

<div id="cancelModal" class="modal">
    <div class="modal-content">
        <div class="modal-header-custom">
            <h2><i class="fas fa-exclamation-triangle"></i> Pengajuan Pembatalan Event</h2>
            <span class="close-btn" onclick="closeCancelModal()">&times;</span>
        </div>
        
        <form action="mahasiswa_history_pengajuan.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="ajukan_pembatalan">
            <input type="hidden" name="pengajuan_id" id="modalPengajuanId">

            <div class="modal-body-custom">
                <p style="margin-bottom: 20px;">Anda akan mengajukan pembatalan untuk event: <strong><span id="eventNamaBatal"></span></strong>. Proses ini memerlukan persetujuan dari Direktorat Kemahasiswaan.</p>
                
                <div class="modal-step">
                    <h3><span class="step-number">1</span> Unduh dan Isi Template Surat</h3>
                    <p style="font-size: 14px; color: #555;">Pastikan template diisi lengkap dan ditandatangani sesuai prosedur yang berlaku.</p>
                    <a href="#" id="downloadTemplateLink" class="btn-action btn-download-sik" style="display: block; text-align: center; margin-top: 15px; background-color: #007bff;">
                        <i class="fas fa-file-download"></i> Unduh Template Pembatalan (.DOCX)
                    </a>
                </div>

                <div class="modal-step">
                    <h3><span class="step-number">2</span> Unggah Surat Pembatalan</h3>
                    <label for="surat_pembatalan" style="font-weight: 500; display: block; margin-bottom: 5px;">Unggah File (PDF atau DOCX):</label>
                    <input type="file" name="surat_pembatalan" id="surat_pembatalan" accept=".pdf, .docx" required>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-action" style="background-color: var(--grey-color);" onclick="closeCancelModal()">Tutup</button>
                <button type="submit" class="btn-action btn-batal"><i class="fas fa-upload"></i> Ajukan Pembatalan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCancelModal(id, namaEvent) {
        document.getElementById('modalPengajuanId').value = id;
        document.getElementById('eventNamaBatal').innerText = namaEvent;
        
        // LOGIKA DYNAMIC DOWNLOAD LINK: Memanggil skrip baru dengan ID pengajuan
        const downloadLink = document.getElementById('downloadTemplateLink');
        downloadLink.href = 'download_template_pembatalan.php?id=' + id;
        
        document.getElementById('cancelModal').style.display = 'block';
    }

    function closeCancelModal() {
        document.getElementById('cancelModal').style.display = 'none';
        const fileInput = document.getElementById('surat_pembatalan');
        if(fileInput) fileInput.value = ''; // Reset file input
    }
    
    // Close the modal when the user clicks anywhere outside of the modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('cancelModal')) {
            closeCancelModal();
        }
    }
</script>

<footer class="page-footer">
    <div class="footer-container">
        <div class="footer-left">
            <img src="../img/logo.png" alt="Logo UNPAR" class="footer-logo">
            <div>
                <h4>UNIVERSITAS KATOLIK PARAHYANGAN</h4>
                <h3 style="font-weight: bold; margin-top: 5px;">DIREKTORAT KEMAHASISWAAN</h3>
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