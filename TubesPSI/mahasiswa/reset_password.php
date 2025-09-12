<?php
session_start();
// Sesuaikan path ke file koneksi database Anda
require_once(__DIR__ . '/../config/db_connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi dasar: apakah data yang dibutuhkan ada di session
    if (!isset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email'])) {
        $_SESSION['error'] = "Sesi reset password tidak valid atau telah berakhir. Silakan coba lagi.";
        header("Location: forgot_password.php");
        exit();
    }

    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Cek apakah OTP sudah kedaluwarsa
    if (time() > $_SESSION['otp_expiry']) {
        $_SESSION['error'] = "Kode OTP sudah kedaluwarsa. Silakan minta yang baru.";
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email']);
        header("Location: forgot_password.php");
        exit();
    }

    // 2. Cek apakah OTP cocok
    if ($otp != $_SESSION['otp']) {
        $_SESSION['error'] = "Kode OTP yang Anda masukkan salah.";
        header("Location: verify_otp.php");
        exit();
    }

    // 3. Cek validasi password baru
    if (strlen($new_password) < 8) {
        $_SESSION['error'] = "Password baru minimal harus 8 karakter.";
        header("Location: verify_otp.php");
        exit();
    }
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Password dan Konfirmasi Password tidak cocok.";
        header("Location: verify_otp.php");
        exit();
    }

    // Jika semua validasi lolos, update password di database
    $email = $_SESSION['reset_email'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE mahasiswa SET mahasiswa_password = ? WHERE mahasiswa_email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        $_SESSION['success_message_login'] = "Password Anda telah berhasil direset. Silakan login.";
        // Hapus semua data sesi reset setelah berhasil
        unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['reset_email']);
        header("Location: ../index.php"); // Arahkan ke halaman login
        exit();
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat memperbarui password. Silakan coba lagi.";
        header("Location: verify_otp.php");
        exit();
    }
    $stmt->close();
    $conn->close();

} else {
    header("Location: forgot_password.php");
    exit();
}
?>