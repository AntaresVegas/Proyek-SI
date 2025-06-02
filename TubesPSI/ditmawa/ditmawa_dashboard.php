<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID';
$status_persetujuan = $_SESSION['status_persetujuan'] ?? 'unknown';

// Include database connection to get some statistics
require_once('../config/db_connection.php');

// Get some basic statistics
$total_events = 0;
$total_mahasiswa = 0;
$pending_approvals = 0;

try {
    // Count total events
    $result = $conn->query("SELECT COUNT(*) as total FROM pengjajuan_event");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_events = $row['total'];
    }

    // Count total mahasiswa
    $result = $conn->query("SELECT COUNT(*) as total FROM mahasiswa");
    if ($result) {
        $row = $result->fetch_assoc();
        $total_mahasiswa = $row['total'];
    }

    // Count pending event approvals (assuming there's a status field)
    $result = $conn->query("SELECT COUNT(*) as total FROM pengjajuan_event WHERE status_persetujuan = 'pending' OR status_persetujuan = 'menunggu' OR status_persetujuan IS NULL");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending_approvals = $row['total'];
    }
} catch (Exception $e) {
    error_log("Error getting statistics: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ditmawa - Event Management Unpar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1:before {
            content: "🏛️";
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-details {
            text-align: right;
        }

        .user-details .name {
            font-weight: bold;
            font-size: 16px;
        }

        .user-details .role {
            font-size: 12px;
            opacity: 0.9;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .main-content {
            padding: 30px;
        }

        .welcome-section {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h2 {
            margin-bottom: 10px;
            font-size: 24px;
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-card .label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card.events {
            border-left: 4px solid #3498db;
        }

        .stat-card.students {
            border-left: 4px solid #27ae60;
        }

        .stat-card.pending {
            border-left: 4px solid #f39c12;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .info-card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card p {
            color: #495057;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-card .value {
            font-weight: bold;
            color: #2c3e50;
        }

        .features-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e9ecef;
        }

        .features-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .features-section h3:before {
            content: "⚙️";
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .feature-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: background 0.3s;
        }

        .feature-item:hover {
            background: #e9ecef;
        }

        .feature-item h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .feature-item p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard Ditmawa</h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="name"><?php echo htmlspecialchars($nama); ?></div>
                    <div class="role">Staff Direktorat Kemahasiswaan</div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <?php if ($status_persetujuan === 'disetujui' || $status_persetujuan === 'approved'): ?>
                <div class="welcome-section">
                    <h2>Selamat Datang di Dashboard Ditmawa</h2>
                    <p>Kelola event dan kegiatan kemahasiswaan Universitas Parahyangan dengan mudah dan efisien</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card events">
                        <div class="icon">📅</div>
                        <div class="number"><?php echo $total_events; ?></div>
                        <div class="label">Total Event</div>
                    </div>
                    <div class="stat-card students">
                        <div class="icon">👥</div>
                        <div class="number"><?php echo $total_mahasiswa; ?></div>
                        <div class="label">Total Mahasiswa</div>
                    </div>
                    <div class="stat-card pending">
                        <div class="icon">⏳</div>
                        <div class="number"><?php echo $pending_approvals; ?></div>
                        <div class="label">Menunggu Persetujuan</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-card">
                        <h3>📋 Informasi Login</h3>
                        <p><span>Nama:</span> <span class="value"><?php echo htmlspecialchars($nama); ?></span></p>
                        <p><span>Email:</span> <span class="value"><?php echo htmlspecialchars($email); ?></span></p>
                        <p><span>User ID:</span> <span class="value"><?php echo htmlspecialchars($user_id); ?></span></p>
                        <p><span>Status:</span> <span class="status-badge status-approved"><?php echo htmlspecialchars($status_persetujuan); ?></span></p>
                    </div>

                    <div class="info-card">
                        <h3>🕒 Informasi Session</h3>
                        <p><span>Session ID:</span> <span class="value"><?php echo substr(session_id(), 0, 10) . '...'; ?></span></p>
                        <p><span>Login Time:</span> <span class="value"><?php echo date('d/m/Y H:i:s'); ?></span></p>
                        <p><span>Server Time:</span> <span class="value"><?php echo date('d/m/Y H:i:s'); ?></span></p>
                        <p><span>Status:</span> <span class="status-badge status-approved">Active</span></p>
                    </div>
                </div>

                <div class="features-section">
                    <h3>Fitur Dashboard Ditmawa</h3>
                    <div class="feature-grid">
                        <div class="feature-item">
                            <h4>📝 Persetujuan Event</h4>
                            <p>Meninjau dan menyetujui proposal event yang diajukan oleh mahasiswa dan organisasi kemahasiswaan</p>
                        </div>
                        <div class="feature-item">
                            <h4>📊 Monitoring Event</h4>
                            <p>Memantau pelaksanaan event yang sedang berlangsung dan evaluasi hasil kegiatan</p>
                        </div>
                        <div class="feature-item">
                            <h4>👥 Manajemen Partisipan</h4>
                            <p>Melihat daftar partisipan event dan mengelola pendaftaran peserta kegiatan</p>
                        </div>
                        <div class="feature-item">
                            <h4>📈 Laporan Kegiatan</h4>
                            <p>Menghasilkan laporan statistik dan analisis kegiatan kemahasiswaan per periode</p>
                        </div>
                        <div class="feature-item">
                            <h4>🏛️ Manajemen Ruangan</h4>
                            <p>Mengelola pemesanan dan penjadwalan penggunaan ruangan untuk kegiatan event</p>
                        </div>
                        <div class="feature-item">
                            <h4>📢 Notifikasi & Pengumuman</h4>
                            <p>Mengirim notifikasi dan pengumuman terkait event kepada mahasiswa dan organisasi</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    <h3>⏳ Akun Sedang Menunggu Persetujuan</h3>
                    <p>Akun Ditmawa Anda sedang dalam proses verifikasi. Status saat ini: <strong><?php echo htmlspecialchars($status_persetujuan); ?></strong></p>
                    <p>Silakan hubungi administrator sistem untuk mempercepat proses persetujuan akun Anda.</p>
                </div>

                <div class="info-grid">
                    <div class="info-card">
                        <h3>📋 Informasi Akun</h3>
                        <p><span>Nama:</span> <span class="value"><?php echo htmlspecialchars($nama); ?></span></p>
                        <p><span>Email:</span> <span class="value"><?php echo htmlspecialchars($email); ?></span></p>
                        <p><span>User ID:</span> <span class="value"><?php echo htmlspecialchars($user_id); ?></span></p>
                        <p><span>Status:</span> <span class="status-badge status-pending"><?php echo htmlspecialchars($status_persetujuan); ?></span></p>
                    </div>

                    <div class="info-card">
                        <h3>📞 Kontak Admin</h3>
                        <p>Untuk mempercepat proses persetujuan, silakan hubungi:</p>
                        <p><span>Email Admin:</span> <span class="value">admin@unpar.ac.id</span></p>
                        <p><span>Telepon:</span> <span class="value">(022) 2032655</span></p>
                        <p><span>Jam Kerja:</span> <span class="value">08:00 - 16:00 WIB</span></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>