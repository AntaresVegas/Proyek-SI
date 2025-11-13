<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../config/db_connection.php');

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../index.php");
    exit();
}

// Check if all required fields are filled
if (!isset($_POST['email'], $_POST['password'], $_POST['user_type'])) {
    $_SESSION['error'] = "Semua field harus diisi";
    header("Location: ../index.php");
    exit();
}

$email = sanitize($_POST['email']);
$password = sanitize($_POST['password']);
$user_type = $_POST['user_type'];

// Validate the user type
if ($user_type !== 'asp') {
    $_SESSION['error'] = "Tipe pengguna tidak sesuai";
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
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }

    // Prepare statement to select user from the 'asp' table
    $stmt = $conn->prepare("SELECT asp_id, asp_nama, asp_email, asp_password FROM asp WHERE asp_email = ?");
    if (!$stmt) {
        throw new Exception("Query error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user with the given email exists
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

    $row = $result->fetch_assoc();

    // ============================================
    // PERBAIKAN DI SINI
    // ============================================
    // Verifikasi password menggunakan fungsi password_verify()
    if (password_verify($password, $row['asp_password'])) {
        // Password benar, set session variables
        $_SESSION['user_id'] = $row['asp_id'];
        $_SESSION['username'] = $row['asp_email'];
        $_SESSION['nama'] = $row['asp_nama'];
        $_SESSION['user_type'] = 'asp';
        
        // Redirect to the ASP dashboard
        header("Location: asp_dashboard.php");
        exit();
    } else {
        // Handle incorrect password
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: ../index.php");
    exit();

} finally {
    // Close the statement and connection
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}