<?php
session_start();

// Check if user is logged in and is a mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'User';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Event Management Unpar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .main-content {
            padding: 30px;
        }

        .welcome-section {
            background: #ecf0f1;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }

        .info-card h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-card p {
            color: #6c757d;
            margin-bottom: 8px;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .features-section {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }

        .features-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .feature-list {
            list-style: none;
            padding-left: 0;
        }

        .feature-list li {
            padding: 8px 0;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard Mahasiswa</h1>
            <div class="user-info">
                <span>Selamat datang, <?php echo htmlspecialchars($nama); ?>!</span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="success-message">
                <strong>Login Berhasil!</strong> Anda telah berhasil masuk ke sistem pengelolaan event Unpar.
            </div>

            <div class="welcome-section">
                <h2>Selamat Datang di Sistem Pengelolaan Event UNPAR</h2>
                <p>Gunakan sistem ini untuk mengelola dan mengikuti berbagai event yang ada di Universitas Parahyangan.</p>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <h3>Informasi Login</h3>
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($nama); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
                    <p><strong>Tipe User:</strong> Mahasiswa</p>
                </div>

                <div class="info-card">
                    <h3>Session Information</h3>
                    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    <p><strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p><strong>Status:</strong> <span style="color: #28a745;">Active</span></p>
                </div>
            </div>

            <div class="features-section">
                <h3>Fitur yang Tersedia (Coming Soon)</h3>
                <ul class="feature-list">
                    <li>Melihat daftar event yang tersedia</li>
                    <li>Mendaftar untuk mengikuti event</li>
                    <li>Melihat riwayat event yang pernah diikuti</li>
                    <li>Mengupdate profil mahasiswa</li>
                    <li>Notifikasi event terbaru</li>
                    <li>Download sertifikat event</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>