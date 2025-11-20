<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log all incoming data for debugging
error_log("SEKRETARIAT LOGIN POST data: " . print_r($_POST, true));

require_once('../config/db_connection.php');

// Function to sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../index.php");
    exit();
}

// Check if all required fields are present
if (!isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['user_type'])) {
    $_SESSION['error'] = "Semua field harus diisi";
    header("Location: ../index.php");
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password']; // Don't sanitize password
$user_type = $_POST['user_type'];

// Log the attempt
error_log("SEKRETARIAT Login attempt - Email: $email, User Type: $user_type");

// [DIUBAH] Validate user type
if ($user_type !== 'sekretariat') {
    $_SESSION['error'] = "Tipe pengguna tidak sesuai untuk halaman Sekretariat";
    header("Location: ../index.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid";
    header("Location: ../index.php");
    exit();
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // [DIUBAH] Prepare and execute query untuk tabel sekretariat_universitas
    $sql = "SELECT sekuniv_id, sekuniv_nama, sekuniv_email, sekuniv_password FROM sekretariat_universitas WHERE sekuniv_email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("SEKRETARIAT User not found: $email");
        // [DIUBAH] Pesan error spesifik
        $_SESSION['error'] = "Email atau password salah. Pastikan Anda sudah terdaftar sebagai staff Sekretariat.";
        header("Location: ../index.php");
        exit();
    }

    $row = $result->fetch_assoc();
    error_log("SEKRETARIAT User found: " . $row['sekuniv_email']);

    // Check password
    $password_match = false;

    // Try password_verify first (for hashed passwords)
    if (password_verify($password, $row['sekuniv_password'])) {
        $password_match = true;
        error_log("SEKRETARIAT Password verified with password_verify()");
    } 
    // If that fails, try direct comparison (for plain text passwords)
    else if ($password === $row['sekuniv_password']) {
        $password_match = true;
        error_log("SEKRETARIAT Password matched with direct comparison (plain text)");
    }

    if ($password_match) {
        // Login successful
        // [DIUBAH] Set session data dari kolom sekuniv
        $_SESSION['user_id'] = $row['sekuniv_id'];
        $_SESSION['username'] = $row['sekuniv_email'];
        $_SESSION['nama'] = $row['sekuniv_nama'];
        $_SESSION['user_type'] = 'sekretariat';

        error_log("SEKRETARIAT Login successful for: $email");

        // [DIUBAH] Redirect ke dashboard sekretariat
        $dashboard_file = 'sekretariat_dashboard.php';
        if (!file_exists($dashboard_file)) {
            $_SESSION['error'] = "Dashboard Sekretariat tidak ditemukan. File: $dashboard_file";
            header("Location: ../index.php");
            exit();
        }

        header("Location: $dashboard_file");
        exit();
    } else {
        error_log("SEKRETARIAT Password mismatch for: $email");
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

} catch (Exception $e) {
    error_log("SEKRETARIAT Login error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan dalam proses login: " . $e->getMessage();
    header("Location: ../index.php");
    exit();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>