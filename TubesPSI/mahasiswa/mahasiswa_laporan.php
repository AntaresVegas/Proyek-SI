<?php
session_start();
include '../config/db_connection.php'; // Pastikan path ini benar

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 'No ID';

$message = '';
$message_type = ''; // 'success' or 'error'

// Check for status messages from redirect
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = "File berhasil terkirim!";
        $message_type = "success";
    } elseif ($_GET['status'] === 'error') {
        $message = $_GET['msg'] ?? "Terjadi kesalahan saat mengunggah file."; // Dapatkan pesan spesifik jika ada
        $message_type = "error";
    }
}

// Fetch events that are approved/completed and don't have an LPJ yet
$events_for_lpj = [];
if ($user_id !== 'No ID') {
    $stmt = $conn->prepare("
        SELECT pengajuan_id, pengajuan_namaEvent, pengajuan_event_tanggal_mulai
        FROM pengajuan_event
        WHERE mahasiswa_id = ?
        AND (pengajuan_status = 'Approved' OR pengajuan_status = 'Selesai') -- Sesuaikan status event yang bisa di-LPJ
        AND (pengajuan_LPJ IS NULL OR pengajuan_LPJ = '') -- Belum ada LPJ
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_pengajuan_id = $_POST['pengajuan_id'] ?? '';
    
    // Validate selected event ID
    $is_valid_event = false;
    foreach ($events_for_lpj as $event) {
        if ($event['pengajuan_id'] == $selected_pengajuan_id) {
            $is_valid_event = true;
            break;
        }
    }

    if (!$is_valid_event) {
        $error_msg = urlencode("ID Pengajuan Event tidak valid atau sudah memiliki LPJ.");
        header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
        exit();
    } else if (isset($_FILES['dokumen_lpj']) && $_FILES['dokumen_lpj']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['dokumen_lpj']['tmp_name'];
        $file_name = basename($_FILES['dokumen_lpj']['name']);
        $file_size = $_FILES['dokumen_lpj']['size'];
        $file_type = $_FILES['dokumen_lpj']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['pdf', 'doc', 'docx'];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file_ext, $allowed_extensions)) {
            $error_msg = urlencode("Ekstensi file tidak diizinkan. Hanya PDF, DOC, atau DOCX.");
            header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
            exit();
        } elseif ($file_size > $max_file_size) {
            $error_msg = urlencode("Ukuran file terlalu besar. Maksimal 5 MB.");
            header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
            exit();
        } else {
            $upload_dir = '../uploads/lpj/'; // Directory untuk menyimpan file LPJ
            // Pastikan direktori ada dan bisa ditulis
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Buat nama file unik untuk menghindari tabrakan
            $new_file_name = uniqid('lpj_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                // Update the pengajuan_LPJ column in pengajuan_event table
                $update_stmt = $conn->prepare("UPDATE pengajuan_event SET pengajuan_LPJ = ? WHERE pengajuan_id = ? AND mahasiswa_id = ?");
                $update_stmt->bind_param("sii", $new_file_name, $selected_pengajuan_id, $user_id);

                if ($update_stmt->execute()) {
                    // Redirect to show success message and clear form
                    header("Location: mahasiswa_laporan.php?status=success");
                    exit();
                } else {
                    $error_msg = urlencode("Gagal menyimpan data LPJ ke database: " . $update_stmt->error);
                    header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
                    // Optional: Delete the uploaded file if database update fails
                    unlink($upload_path);
                    exit();
                }
                $update_stmt->close();
            } else {
                $error_msg = urlencode("Gagal mengunggah file. Pastikan direktori 'uploads/lpj' dapat ditulis.");
                header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
                exit();
            }
        }
    } else if (isset($_POST['submit_lpj'])) { // Jika form disubmit tapi tidak ada file yang dipilih
        $error_msg = urlencode("Silakan pilih file LPJ untuk diunggah.");
        header("Location: mahasiswa_laporan.php?status=error&msg={$error_msg}");
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
        .navbar-menu li a:hover,
        .navbar-menu li a.active { /* Added active class style */
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

        /* Form Container Styling */
        .container {
            max-width: 700px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            background-color: #fff;
            appearance: none; /* Remove default arrow in select */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000%22%20d%3D%22M287%2C146.2L146.2%2C287c-2.4%2C2.4-5.3%2C3.6-8.2%2C3.6s-5.8-1.2-8.2-3.6L5.4%2C146.2c-4.8-4.8-4.8-12.5%2C0-17.3c4.8-4.8%2C12.5-4.8%2C17.3%2C0l123.5%2C123.5L269.7%2C128.9c4.8-4.8%2C12.5-4.8%2C17.3%2C0C291.8%2C133.7%2C291.8%2C141.4%2C287%2C146.2z%22%2F%3E%3C%2Fsvg%3E'); /* Custom arrow */
            background-repeat: no-repeat;
            background-position: right 10px top 50%;
            background-size: 12px auto;
        }

        /* Custom File Upload Styling (from previous response) */
        .custom-file-upload {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f8f8f8;
            padding: 5px;
        }

        .hidden-file-input {
            display: none;
        }

        .upload-button {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
            flex-shrink: 0;
        }

        .upload-button:hover {
            background-color: #0056b3;
        }

        .file-name {
            flex-grow: 1;
            color: #555;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-upload-label {
            margin-bottom: 8px; /* Adjusted to match other labels */
            display: block;
            font-weight: 600;
            color: #333;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end; /* Align buttons to the right */
            margin-top: 30px;
        }

        .form-actions button {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .form-actions button:hover {
            transform: translateY(-2px);
        }

        .btn-clear {
            background-color: #ffc107; /* Yellow */
            color: #333;
        }

        .btn-clear:hover {
            background-color: #e0a800;
        }

        .btn-submit {
            background-color: #28a745; /* Green */
            color: white;
        }

        .btn-submit:hover {
            background-color: #218838;
        }

        /* Message pop-up styling */
        .upload-message {
            position: fixed;
            top: 80px; /* Adjust as needed, below navbar */
            right: 20px;
            background-color: #28a745; /* Green for success */
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1001; /* Above other content */
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            pointer-events: none; /* Allows clicks to pass through when hidden */
        }
        .upload-message.error {
            background-color: #dc3545; /* Red for error */
        }
        .upload-message.show {
            opacity: 1;
            pointer-events: auto; /* Enable clicks when visible */
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
        <li><a href="mahasiswa_laporan.php"class="active">Laporan</a></li>
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
        <h1>Laporan Pertanggungjawaban Event</h1>
    </div>

    <form action="" method="POST" enctype="multipart/form-data" id="lpjForm">
        <div class="form-group">
            <label for="pengajuan_id">Pilih Event untuk LPJ:</label>
            <select id="pengajuan_id" name="pengajuan_id" required>
                <option value="">-- Pilih Event --</option>
                <?php if (!empty($events_for_lpj)): ?>
                    <?php foreach ($events_for_lpj as $event): ?>
                        <option value="<?php echo htmlspecialchars($event['pengajuan_id']); ?>">
                            <?php echo htmlspecialchars($event['pengajuan_namaEvent']); ?> (<?php echo htmlspecialchars(date('d-m-Y', strtotime($event['pengajuan_event_tanggal_mulai']))); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>Tidak ada event yang siap di-LPJ-kan</option>
                <?php endif; ?>
            </select>
            <?php if (empty($events_for_lpj)): ?>
                <p style="color: #dc3545; font-size: 14px; margin-top: 5px;">*Anda belum memiliki event yang selesai atau disetujui dan belum memiliki LPJ yang belum diunggah.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="dokumen_lpj" class="file-upload-label">Upload Dokumen LPJ (PDF, DOCX, Max 5MB)</label>
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
    // Script for custom file upload UI
    document.getElementById('dokumen_lpj').addEventListener('change', function() {
        const fileNameSpan = document.getElementById('lpj_file_name');
        if (this.files && this.files.length > 0) {
            fileNameSpan.textContent = this.files[0].name;
        } else {
            fileNameSpan.textContent = 'Belum ada file dipilih';
        }
    });

    // Function to clear the form
    function clearForm() {
        document.getElementById('lpjForm').reset();
        document.getElementById('lpj_file_name').textContent = 'Belum ada file dipilih';
        // Reset dropdown to default option (if the first option is the placeholder)
        const selectElement = document.getElementById('pengajuan_id');
        if (selectElement.options.length > 0) {
            selectElement.selectedIndex = 0;
        }
    }

    // Show and hide notification message
    window.onload = function() {
        const messageDiv = document.getElementById('uploadMessage');
        // Only show if there's an actual message from PHP
        if (messageDiv.textContent.trim() !== '') {
            messageDiv.classList.add('show');
            setTimeout(() => {
                messageDiv.classList.remove('show');
                // Optional: remove the URL parameters after the message fades out
                // so that refreshing the page doesn't show the message again
                const url = new URL(window.location.href);
                url.searchParams.delete('status');
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url.toString());
            }, 5000); // Hide after 5 seconds
        }
    };
</script>

</body>
</html>