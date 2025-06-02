<?php
session_start();

// Check if user is logged in and is a ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    header("Location: ../index.php"); // Redirect to login page if not authorized
    exit();
}

// Include database connection
require_once('../config/db_connection.php');

// Set default values for session variables
$nama = $_SESSION['nama'] ?? 'Staff Ditmawa';
$email = $_SESSION['username'] ?? 'No email'; // Assuming username is email
$user_id = $_SESSION['user_id'] ?? 'N/A'; // This should be the ditmawa_id from the session

// Initialize ditmawa_data with session values and placeholders
$ditmawa_data = [
    'full_name' => $nama,
    'email' => $email,
    'nik' => 'Data tidak ditemukan', // Default if not fetched
    'divisi' => 'Data tidak ditemukan', // Default if not fetched
    'bagian' => 'Data tidak ditemukan', // Default if not fetched
    // 'profile_picture' => '../img/gugie.jpeg' // Baris ini tidak lagi digunakan untuk src gambar profil di HTML
];

// Fetch more detailed profile data from the database
try {
    if (isset($conn) && $conn->ping()) { // Check if connection is active
        $stmt = $conn->prepare("SELECT ditmawa_NIK, ditmawa_Divisi, ditmawa_Bagian FROM ditmawa WHERE ditmawa_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']); // Assuming ditmawa_id is an integer
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $ditmawa_data['nik'] = htmlspecialchars($row['ditmawa_NIK']);
                $ditmawa_data['divisi'] = htmlspecialchars($row['ditmawa_Divisi']);
                $ditmawa_data['bagian'] = htmlspecialchars($row['ditmawa_Bagian']);
                // Jika Anda punya path gambar profil di database dan ingin menggunakannya nanti,
                // Anda tetap bisa menyimpannya di sini:
                // $ditmawa_data['profile_picture_from_db'] = htmlspecialchars($row['nama_kolom_path_gambar_profil']);
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for ditmawa profile: " . $conn->error);
        }
    } else {
        error_log("Database connection not established or lost in ditmawa_profile.php");
    }
} catch (Exception $e) {
    error_log("Error fetching Ditmawa profile data: " . $e->getMessage());
    // Optionally set a user-friendly message
    // $_SESSION['error_message'] = "Gagal memuat detail profil. Silakan coba lagi.";
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
    <title>Profil Ditmawa - Event Management Unpar</title>
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

        /* Navbar styles (copied from your existing dashboard) */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
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
            object-fit: cover; /* Ensures logo stays square and fills space */
            /* border-radius: 50%; /* Uncomment if you want a circular logo */
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

        /* Profile Page specific styles */
        .profile-container {
            max-width: 800px;
            margin: 40px auto; /* Adjust margin-top to clear navbar */
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            width: 100%;
        }

        .profile-header h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .profile-picture-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #e0e0e0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
        }

        .profile-picture {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures image fills the circle */
        }

        .profile-info {
            width: 100%;
            max-width: 600px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 500;
        }

        .actions-section {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .action-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease, transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-button:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .action-button.secondary {
            background: #6c757d;
        }

        .action-button.secondary:hover {
            background: #545b62;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                padding-top: 100px;
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
            .profile-container {
                margin: 20px auto;
                padding: 20px;
            }
            .profile-info {
                padding: 15px;
            }
            .profile-picture-container {
                width: 120px;
                height: 120px;
            }
            .profile-header h1 {
                font-size: 26px;
            }
            .info-label, .info-value {
                font-size: 14px;
            }
            .action-button {
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-left">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa" class="navbar-logo">
        <div class="navbar-title">
            <span>Pengelolaan</span><br>
            <strong>Event UNPAR</strong>
        </div>
    </div>

    <ul class="navbar-menu">
        <li><a href="ditmawa_dashboard.php">Home</a></li>
        <li><a href="#">Data Event</a></li>
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

<div class="profile-container">
    <div class="profile-header">
        <h1>Profil Ditmawa</h1>
    </div>

    <div class="profile-picture-container">
        <img src="../img/gugie.png" alt="Profile Picture" class="profile-picture">
    </div>

    <div class="profile-info">
        <div class="info-item">
            <span class="info-label">Nama Lengkap</span>
            <span class="info-value"><?php echo htmlspecialchars($ditmawa_data['full_name']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value"><?php echo htmlspecialchars($ditmawa_data['email']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">NIK</span>
            <span class="info-value"><?php echo htmlspecialchars($ditmawa_data['nik']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Divisi</span>
            <span class="info-value"><?php echo htmlspecialchars($ditmawa_data['divisi']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Bagian</span>
            <span class="info-value"><?php echo htmlspecialchars($ditmawa_data['bagian']); ?></span>
        </div>
    </div>
</body>
</html>