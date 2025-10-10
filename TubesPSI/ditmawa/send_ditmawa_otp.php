<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form sesuai kolom database
    $nama = trim($_POST['ditmawa_nama']);
    $nik = trim($_POST['ditmawa_NIK']);
    $email = trim($_POST['ditmawa_email']);
    $divisi = trim($_POST['ditmawa_Divisi']);
    $bagian = trim($_POST['ditmawa_Bagian']);

    // Simpan data untuk diisi ulang jika ada error
    $_SESSION['old_data'] = ['nama' => $nama, 'NIK' => $nik, 'email' => $email, 'Divisi' => $divisi, 'Bagian' => $bagian];
    
    // Validasi Input
    if (empty($nama) || empty($nik) || empty($email) || empty($divisi) || empty($bagian)) {
        $_SESSION['error'] = "Semua field harus diisi.";
        header("Location: register_ditmawa.php");
        exit();
    }
    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $_SESSION['error'] = "NIK harus terdiri dari 16 digit angka.";
        header("Location: register_ditmawa.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header("Location: register_ditmawa.php");
        exit();
    }

    // Cek Duplikasi NIK atau Email
    $stmt = $conn->prepare("SELECT ditmawa_id FROM ditmawa WHERE ditmawa_NIK = ? OR ditmawa_email = ?");
    $stmt->bind_param("ss", $nik, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "NIK atau Email sudah terdaftar.";
        header("Location: register_ditmawa.php");
        exit();
    }
    $stmt->close();

    // Generate dan Kirim OTP
    $otp = rand(100000, 999999);
    
    // Simpan semua data registrasi ke session
    $_SESSION['registration_data'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email, 'divisi' => $divisi, 'bagian' => $bagian];
    $_SESSION['registration_otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // OTP berlaku 5 menit

    $mail = new PHPMailer(true);
    try {
        // ========== KONFIGURASI SERVER SMTP (WAJIB DIISI!) ==========
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'email.anda@gmail.com'; // GANTI DENGAN USERNAME SMTP ANDA
        $mail->Password   = 'password_aplikasi_anda'; // GANTI DENGAN PASSWORD/APP PASSWORD ANDA
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@unpar.ac.id', 'Admin Sistem UNPAR');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran Akun Ditmawa';
        $mail->Body    = "Halo <b>$nama</b>,<br><br>Gunakan kode berikut untuk menyelesaikan pendaftaran akun Ditmawa Anda. Kode ini hanya berlaku selama 5 menit.<br><br>Kode Verifikasi Anda: <h1>$otp</h1><br><br>Terima kasih.";
        
        $mail->send();
        
        unset($_SESSION['old_data'], $_SESSION['error']);
        header("Location: verify_ditmawa.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: register_ditmawa.php");
        exit();
    }
} else {
    header("Location: register_ditmawa.php");
    exit();
}
?>