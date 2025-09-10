<?php
session_start();
require_once('../config/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot_password.php");
    exit();
}

$email = $_POST['email'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// 1. Validasi Input
if (empty($email) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header("Location: forgot_password.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "Password baru dan konfirmasi password tidak cocok.";
    header("Location: forgot_password.php");
    exit();
}

// 2. Cek apakah email ada di database
$stmt = $conn->prepare("SELECT mahasiswa_id FROM mahasiswa WHERE mahasiswa_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Email tidak ditemukan di sistem kami.";
    header("Location: forgot_password.php");
    exit();
}
$stmt->close();

// 3. Jika semua valid, update password
// Gunakan password_hash() untuk keamanan!
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$update_stmt = $conn->prepare("UPDATE mahasiswa SET mahasiswa_password = ? WHERE mahasiswa_email = ?");
$update_stmt->bind_param("ss", $hashed_password, $email);

if ($update_stmt->execute()) {
    // Jika berhasil, arahkan ke halaman login dengan pesan sukses
    header("Location: ../index.php?status=pw_reset_success");
    exit();
} else {
    // Jika gagal
    $_SESSION['error'] = "Gagal mereset password. Silakan coba lagi.";
    header("Location: forgot_password.php");
    exit();
}

$update_stmt->close();
$conn->close();
?>