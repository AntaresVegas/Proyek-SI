<?php
session_start();

include '../config/db_connection.php'; // Pastikan path ini benar

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'];

$message = '';
$message_type = ''; // 'success' or 'error'

// Check for status messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = "File LPJ berhasil terkirim!";
        $message_type = "success";
    } elseif ($_GET['status'] === 'error') {
        $message = urldecode($_GET['msg'] ?? "Terjadi kesalahan saat mengunggah file.");
        $message_type = "error";
    }
}
// Fetch events that are approved and don't have an LPJ yet
$events_for_lpj = [];
if (isset($user_id)) { // <-- PERBAIKAN: Menggunakan isset() untuk memeriksa keberadaan variabel, bukan nilainya
    
    // Kueri ini sekarang akan selalu dijalankan jika user_id ada (termasuk untuk nilai 0)
    $stmt = $conn->prepare("
        SELECT pengajuan_id, pengajuan_namaEvent, pengajuan_event_tanggal_mulai
        FROM pengajuan_event
        WHERE mahasiswa_id = ?
        AND pengajuan_status = 'Disetujui'
        AND (pengajuan_LPJ IS NULL OR pengajuan_LPJ = '') 
        ORDER BY pengajuan_event_tanggal_mulai DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $events_for_lpj[] = $row;
    }
    $stmt->close();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_lpj'])) {
    $selected_pengajuan_id = $_POST['pengajuan_id'] ?? '';
    
    if (empty($selected_pengajuan_id)) {
        header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Silakan pilih event terlebih dahulu."));
        exit();
    }
    
    // Validasi file
    if (isset($_FILES['dokumen_lpj']) && $_FILES['dokumen_lpj']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['dokumen_lpj']['tmp_name'];
        $file_name = basename($_FILES['dokumen_lpj']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['pdf', 'doc', 'docx'];
        if (!in_array($file_ext, $allowed_extensions)) {
            header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Ekstensi file tidak valid."));
            exit();
        }

        // Gunakan path relatif dari root proyek untuk disimpan di DB
        $db_path = 'uploads/lpj/' . uniqid('lpj_', true) . '.' . $file_ext;
        $upload_path = '../' . $db_path;

        if (!is_dir(dirname($upload_path))) {
            mkdir(dirname($upload_path), 0777, true);
        }

        if (move_uploaded_file($file_tmp_name, $upload_path)) {
            // Mengubah status LPJ menjadi 'Diterima' setelah upload berhasil
            $update_stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_LPJ = ?, pengajuan_statusLPJ = 'Diterima' WHERE pengajuan_id = ? AND mahasiswa_id = ?");
            $update_stmt->bind_param("sii", $db_path, $selected_pengajuan_id, $user_id);

            if ($update_stmt->execute()) {
                header("Location: mahasiswa_laporan.php?status=success");
                exit();
            } else {
                unlink($upload_path); // Hapus file jika gagal update DB
                header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Gagal menyimpan data LPJ."));
                exit();
            }
            $update_stmt->close();
        } else {
            header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Gagal mengunggah file."));
            exit();
        }
    } else {
        header("Location: mahasiswa_laporan.php?status=error&msg=" . urlencode("Silakan pilih file LPJ untuk diunggah."));
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Laporan Pertanggungjawaban - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* CSS tidak ada perubahan, bisa gunakan yang dari sebelumnya */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; min-height: 100vh; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background:rgb(2, 71, 25); width: 100%; padding: 10px 30px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); position: fixed; top: 0; left: 0; z-index: 1000; }
        .navbar-left { display: flex; align-items: center; gap: 10px; }
        .navbar-logo { width: 50px; height: 50px; }
        .navbar-title { color:white; font-size: 14px; line-height: 1.2; }
        .navbar-menu { display: flex; list-style: none; gap: 25px; }
        .navbar-menu li a { text-decoration: none; color:white; font-weight: 500; }
        .navbar-menu li a.active, .navbar-menu li a:hover { color: #a7d8de; }
        .navbar-right { display: flex; align-items: center; gap: 15px; color:white; }
        .icon { font-size: 20px; cursor: pointer; }
        .container { max-width: 700px; margin: 100px auto 30px; background: white; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        .custom-file-upload { display: flex; align-items: center; gap: 10px; border: 1px dashed #ccc; border-radius: 5px; padding: 10px; }
        .hidden-file-input { display: none; }
        .upload-button { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .file-name { color: #555; }
        .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; }
        .form-actions button { padding: 10px 25px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .btn-clear { background-color: #6c757d; color: white; }
        .btn-submit { background-color: #28a745; color: white; }
        .upload-message { position: fixed; top: 80px; right: 20px; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1001; opacity: 0; transition: opacity 0.5s; pointer-events: none; }
        .upload-message.success { background-color: #28a745; color: white; }
        .upload-message.error { background-color: #dc3545; color: white; }
        .upload-message.show { opacity: 1; }
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
        <li><a href="mahasiswa_rules.php">Rules</a></li>
        <li><a href="mahasiswa_pengajuan.php">Form</a></li>
        <li><a href="mahasiswa_event.php">Event</a></li>
        <li><a href="mahasiswa_laporan.php" class="active">Laporan</a></li>
        <li><a href="mahasiswa_history.php">History</a></li>
    </ul>
    <div class="navbar-right">
        <a href="mahasiswa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span><i class="fas fa-user-circle icon"></i>
        </a>
        <a href="logout.php"><i class="fas fa-sign-out-alt icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="header">
        <h1>Laporan Pertanggungjawaban Event</h1>
    </div>

    <form action="mahasiswa_laporan.php" method="POST" enctype="multipart/form-data" id="lpjForm">
        <div class="form-group">
            <label for="pengajuan_id">Pilih Event untuk LPJ:</label>
            <select id="pengajuan_id" name="pengajuan_id" required>
                <option value="">-- Pilih Event yang Sudah Disetujui --</option>
                <?php if (!empty($events_for_lpj)): ?>
                    <?php foreach ($events_for_lpj as $event): ?>
                        <option value="<?php echo htmlspecialchars($event['pengajuan_id']); ?>">
                            <?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?> (<?php echo htmlspecialchars(date('d-m-Y', strtotime($event['pengajuan_event_tanggal_mulai']))); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>Tidak ada event yang perlu di-LPJ-kan</option>
                <?php endif; ?>
            </select>
            <?php if (empty($events_for_lpj)): ?>
                <p style="color: #6c757d; font-size: 14px; margin-top: 5px;">*Hanya event yang berstatus 'Disetujui' dan belum mengunggah LPJ yang akan tampil di sini.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="dokumen_lpj">Upload Dokumen LPJ (PDF, DOCX, Max 5MB)</label>
            <div class="custom-file-upload">
                <input type="file" id="dokumen_lpj" name="dokumen_lpj" accept=".pdf,.doc,.docx" class="hidden-file-input" required>
                <button type="button" class="upload-button" onclick="document.getElementById('dokumen_lpj').click()">Pilih File</button>
                <span class="file-name" id="lpj_file_name">Belum ada file dipilih</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn-clear" onclick="clearForm()">CLEAR</button>
            <button type="submit" name="submit_lpj" class="btn-submit">SUBMIT</button>
        </div>
    </form>
</div>

<div id="uploadMessage" class="upload-message <?php echo !empty($message) ? 'show ' . $message_type : ''; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>

<script>
    document.getElementById('dokumen_lpj').addEventListener('change', function() {
        const fileNameSpan = document.getElementById('lpj_file_name');
        fileNameSpan.textContent = this.files.length > 0 ? this.files[0].name : 'Belum ada file dipilih';
    });

    function clearForm() {
        document.getElementById('lpjForm').reset();
        document.getElementById('lpj_file_name').textContent = 'Belum ada file dipilih';
    }

    window.onload = function() {
        const messageDiv = document.getElementById('uploadMessage');
        if (messageDiv.textContent.trim() !== '') {
            setTimeout(() => {
                messageDiv.classList.add('show');
                setTimeout(() => {
                    messageDiv.classList.remove('show');
                    if (window.history.replaceState) {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('status');
                        url.searchParams.delete('msg');
                        window.history.replaceState({path: url.href}, '', url.href);
                    }
                }, 5000);
            }, 100);
        }
    };
</script>

</body>
</html>