<?php
session_start();

// Aktifkan debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug log POST data
error_log("POST data: " . print_r($_POST, true));

require_once('../config/db_connection.php');

// Fungsi sanitasi input
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Validasi metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode permintaan tidak valid";
    header("Location: ../index.php");
    exit();
}

// Validasi input
if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['user_type'])) {
    $_SESSION['error'] = "Semua field harus diisi";
    header("Location: ../index.php");
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password'];
$user_type = $_POST['user_type'];

error_log("Percobaan login - Email: $email, User Type: $user_type");

// Hanya Ditmawa yang boleh login
if ($user_type !== 'ditmawa') {
    $_SESSION['error'] = "Tipe pengguna tidak sesuai";
    header("Location: ../index.php");
    exit();
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@unpar.ac.id')) {
    $_SESSION['error'] = "Email tidak valid atau bukan domain @unpar.ac.id";
    header("Location: ../index.php");
    exit();
}

try {
    // Cek koneksi
    if (!is_object($conn)) {
        throw new Exception("Koneksi database tidak valid");
    }

    // Ambil data pengguna
    $sql = "SELECT ditmawa_id, ditmawa_nama, ditmawa_email, ditmawa_password FROM Ditmawa WHERE ditmawa_email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare gagal: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("Pengguna tidak ditemukan: $email");
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

    $user = $result->fetch_assoc();
    $stored_password = $user['ditmawa_password'];

    // Verifikasi password
    $valid = password_verify($password, $stored_password) || $password === $stored_password;

    if ($valid) {
        // Set session
        $_SESSION['user_id'] = $user['ditmawa_id'];
        $_SESSION['username'] = $user['ditmawa_email'];
        $_SESSION['nama'] = $user['ditmawa_nama'];
        $_SESSION['user_type'] = 'ditmawa';

        error_log("Login berhasil untuk: $email");

        header("Location: ditmawa_home.php");
        exit();
    } else {
        error_log("Password salah untuk: $email");
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

} catch (Exception $e) {
    error_log("Exception login: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem.";
    header("Location: ../index.php");
    exit();
} finally {
    if (isset($stmt) && is_object($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && is_object($conn)) {
        $conn->close();
    }
}
?>
