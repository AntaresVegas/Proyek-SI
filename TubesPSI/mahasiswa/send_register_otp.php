<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan sanitasi data input
    $nama = trim($_POST['mahasiswa_nama']);
    $npm = trim($_POST['mahasiswa_npm']);
    $email = trim($_POST['mahasiswa_email']);
    $jurusan = trim($_POST['mahasiswa_jurusan']);

    $_SESSION['old_data'] = ['nama' => $nama, 'npm' => $npm, 'email' => $email, 'jurusan' => $jurusan];
    
    // Validasi Input
    if (empty($nama) || empty($npm) || empty($email) || empty($jurusan)) {
        $_SESSION['error'] = "Semua field harus diisi.";
        header("Location: register.php");
        exit();
    }
    if (!preg_match('/^[0-9]{10}$/', $npm)) {
        $_SESSION['error'] = "NPM harus terdiri dari 10 digit angka.";
        header("Location: register.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header("Location: register.php");
        exit();
    } else {
        $domain = substr(strrchr($email, "@"), 1);
        $allowed_domains = ['gmail.com', 'student.unpar.ac.id'];
        if (!in_array(strtolower($domain), $allowed_domains)) {
            $_SESSION['error'] = "Hanya email @gmail.com atau @student.unpar.ac.id yang diizinkan.";
            header("Location: register.php");
            exit();
        }
    }

    // Cek Duplikasi NPM atau Email
    $stmt = $conn->prepare("SELECT mahasiswa_npm FROM mahasiswa WHERE mahasiswa_npm = ? OR mahasiswa_email = ?");
    $stmt->bind_param("ss", $npm, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "NPM atau Email sudah terdaftar.";
        header("Location: register.php");
        exit();
    }
    $stmt->close();

    // Generate dan Kirim OTP
    $otp = rand(100000, 999999);
    
    $_SESSION['registration_data'] = ['nama' => $nama, 'npm' => $npm, 'email' => $email, 'jurusan' => $jurusan];
    $_SESSION['registration_otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // OTP berlaku 5 menit

    $mail = new PHPMailer(true);
    try {
        // ========== KONFIGURASI SERVER SMTP (WAJIB DIISI!) ==========
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Ganti dengan host SMTP Anda
        $mail->SMTPAuth   = true;
        $mail->Username   = 'audricaurelius.aa@gmail.com'; // GANTI DENGAN USERNAME SMTP ANDA
        $mail->Password   = 'leyp iuwc jxfs emlm'; // GANTI DENGAN PASSWORD/APP PASSWORD ANDA
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        // ==========================================================

        $mail->setFrom('no-reply@unpar.ac.id', 'Registrasi Event Unpar');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran Akun Anda';
        $mail->Body    = "Halo <b>$nama</b>,<br><br>Gunakan kode berikut untuk menyelesaikan pendaftaran Anda. Kode ini hanya berlaku selama 5 menit.<br><br>Kode Verifikasi Anda: <h1>$otp</h1><br><br>Jika Anda tidak merasa mendaftar, abaikan email ini.<br><br>Terima kasih.";
        $mail->AltBody = "Kode Verifikasi Anda adalah $otp. Kode ini berlaku selama 5 menit.";

        $mail->send();
        
        unset($_SESSION['old_data'], $_SESSION['error']);
        
        header("Location: verify_registration.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: register.php");
        exit();
    }

} else {
    header("Location: register.php");
    exit();
}
?>