<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php"); // Redirect to login page if not authorized
    exit();
}

// Set default values for session variables if they are not set
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$email = $_SESSION['username'] ?? 'No email';
$user_id = $_SESSION['user_id'] ?? 'No ID'; // Added user_id to session from the login process

// Include database connection
require_once('../config/db_connection.php');

// Initialize statistics variables
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

    // Count pending event approvals
    // Using 'menunggu' or 'pending' or IS NULL for robustness
    $result = $conn->query("SELECT COUNT(*) as total FROM pengjajuan_event WHERE status_persetujuan = 'pending' OR status_persetujuan = 'menunggu' OR status_persetujuan IS NULL");
    if ($result) {
        $row = $result->fetch_assoc();
        $pending_approvals = $row['total'];
    }
} catch (Exception $e) {
    // Log the error but don't show it directly to the user for security
    error_log("Error getting statistics for Ditmawa Dashboard: " . $e->getMessage());
    // Optionally, you could set a session error message here to be displayed on the dashboard
    // $_SESSION['error'] = "Failed to load dashboard statistics.";
} finally {
    // Close the database connection
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Ditmawa - Event Management Unpar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* General styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar styles from mahasiswa_dashboard.php */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ff8c00;
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
            color: #2c3e50;
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
            color: #2c3e50;
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
            color: #2c3e50;
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

        /* Container & Main Content - adjusted for top padding */
        .container {
            max-width: 1400px;
            margin: 20px auto; /* Adjusted margin-top to account for fixed navbar */
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .main-content {
            padding: 30px;
        }

        /* Ditmawa specific styles */
        /* Removed the old .header styles as it's no longer used */

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

        /* Calendar specific styles */
        .calendar-wrapper {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px; /* Added margin for separation */
        }
        .calendar-wrapper h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            text-align: center;
        }
        .day-name, .day {
            padding: 10px;
        }
        .day-name {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .day {
            background-color: #ffffff;
            border: 1px solid #ddd;
            height: 80px; /* Fixed height for calendar cells */
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start; /* Align content to top */
            font-size: 1.1em; /* Larger number */
            font-weight: bold;
            color: #34495e;
        }
        .day.event {
            background-color: #ffefc5;
            border: 2px solid #f4b400;
        }
        .event-label {
            position: absolute;
            bottom: 5px;
            left: 5px;
            right: 5px;
            font-size: 0.75em; /* Smaller font for event label */
            color: #6a6a6a;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 100px; /* Adjust padding for smaller navbar on mobile */
            }
            .navbar {
                flex-direction: column;
                padding: 10px 15px;
                gap: 10px;
            }
            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            .navbar-right {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
            /* Removed old header specific media queries */
            .info-grid {
                grid-template-columns: 1fr;
            }
            .calendar {
                grid-template-columns: repeat(auto-fit, minmax(calc(100%/7 - 10px), 1fr));
            }
            .day {
                height: 60px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo UNPAR" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="ditmawa_dataEvent.php">Data Event</a></li>
        <li><a href="ditmawa_laporan.php">Laporan</a></li>
    </ul>

    <div class="navbar-right">
        <a href="ditmawa_profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 15px;">
            <span class="user-name"><?php echo htmlspecialchars($nama); ?></span>
            <i class="fas fa-user-circle icon"></i>
        </a>
        <a href="../logout.php"><i class="fas fa-right-from-bracket icon"></i></a>
    </div>
</nav>

<div class="container">
    <div class="main-content">
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

        <div class="calendar-wrapper">
            <h2>Kalender Institusional - Juni 2025</h2>
            <div class="calendar">
                <div class="day-name">Ming</div>
                <div class="day-name">Sen</div>
                <div class="day-name">Sel</div>
                <div class="day-name">Rab</div>
                <div class="day-name">Kam</div>
                <div class="day-name">Jum</div>
                <div class="day-name">Sab</div>

                <div class="day"></div> <div class="day"></div> <div class="day"></div> <div class="day"></div> <div class="day"></div> <div class="day"></div> <div class="day"></div> <div class="day">1</div>
                <div class="day">2</div>
                <div class="day">3</div>
                <div class="day">4</div>
                <div class="day">5</div>
                <div class="day">6</div>
                <div class="day">7</div>
                <div class="day">8</div>
                <div class="day event">9<div class="event-label">Webinar PNS</div></div>
                <div class="day">10</div>
                <div class="day">11</div>
                <div class="day">12</div>
                <div class="day">13</div>
                <div class="day">14</div>
                <div class="day">15</div>
                <div class="day">16</div>
                <div class="day">17</div>
                <div class="day">18</div>
                <div class="day">19</div>
                <div class="day">20</div>
                <div class="day">21</div>
                <div class="day">22</div>
                <div class="day">23</div>
                <div class="day">24</div>
                <div class="day">25</div>
                <div class="day">26</div>
                <div class="day">27</div>
                <div class="day">28</div>
                <div class="day">29</div>
                <div class="day">30</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>