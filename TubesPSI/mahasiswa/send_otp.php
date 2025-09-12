<?php
session_start();
// Sesuaikan path ke file koneksi database Anda
require_once(__DIR__ . '/../config/db_connection.php'); 

// Panggil autoloader dari Composer
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Cek apakah email ada di database
    $stmt = $conn->prepare("SELECT mahasiswa_id FROM mahasiswa WHERE mahasiswa_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Email tidak terdaftar di sistem.";
        header("Location: forgot_password.php");
        exit();
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    
    // Simpan OTP, email, dan waktu kedaluwarsa (5 menit) di session
    $_SESSION['otp'] = $otp;
    $_SESSION['reset_email'] = $email;
    $_SESSION['otp_expiry'] = time() + 300; // 300 detik = 5 menit

    // Kirim email menggunakan PHPMailer
    $mail = new PHPMailer(true);
    try {
        // ========== KONFIGURASI SERVER SMTP (WAJIB DIISI!) ==========
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Ganti dengan host SMTP Anda (contoh untuk Gmail)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'audricaurelius.aa@gmail.com'; // Ganti dengan username SMTP Anda
        $mail->Password   = 'leyp iuwc jxfs emlm'; // Ganti dengan password SMTP/App Password Anda
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        // ==========================================================

        // Penerima
        $mail->setFrom('no-reply@unpar.ac.id', 'Sistem Event Unpar');
        $mail->addAddress($email);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP untuk Reset Password Akun Anda';
        $mail->Body    = "Halo,<br><br>Gunakan kode OTP berikut untuk mereset password Anda. Kode ini hanya berlaku selama 5 menit.<br><br>Kode OTP Anda: <b>$otp</b><br><br>Jika Anda tidak merasa meminta reset password, abaikan email ini.<br><br>Terima kasih.";
        $mail->AltBody = "Kode OTP Anda adalah $otp. Kode ini berlaku selama 5 menit.";

        $mail->send();
        
        $_SESSION['success'] = "Kode OTP telah dikirim ke email Anda.";
        header("Location: verify_otp.php");
        exit();

    } catch (Exception $e) {
        // Tampilkan error yang lebih detail jika pengiriman gagal
        $_SESSION['error'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: forgot_password.php");
        exit();
    }

} else {
    header("Location: forgot_password.php");
    exit();
}
?>