<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['asp_nama']);
    $nik = trim($_POST['asp_nik']);
    $email = trim($_POST['asp_email']);

    $_SESSION['old_data_asp'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    
    if (empty($nama) || empty($nik) || empty($email)) {
        $_SESSION['error_asp'] = "Semua field harus diisi.";
        header("Location: register_asp.php");
        exit();
    }
    if (!preg_match('/^[0-9]{10,}$/', $nik)) {
        $_SESSION['error_asp'] = "NIK harus terdiri dari minimal 10 digit angka.";
        header("Location: register_asp.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_asp'] = "Format email tidak valid.";
        header("Location: register_asp.php");
        exit();
    } else {
        $domain = substr(strrchr($email, "@"), 1);
        $allowed_domains = ['unpar.ac.id', 'gmail.com'];
        if (!in_array(strtolower($domain), $allowed_domains)) {
            $_SESSION['error_asp'] = "Hanya email domain @unpar.ac.id atau @gmail.com yang diizinkan.";
            header("Location: register_asp.php");
            exit();
        }
    }

    $stmt = $conn->prepare("SELECT asp_NIK FROM asp WHERE asp_NIK = ? OR asp_email = ?");
    $stmt->bind_param("ss", $nik, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_asp'] = "NIK atau Email sudah terdaftar.";
        header("Location: register_asp.php");
        exit();
    }
    $stmt->close();

    $otp = rand(100000, 999999);
    
    $_SESSION['reg_data_asp'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    $_SESSION['reg_otp_asp'] = $otp;
    $_SESSION['otp_expiry_asp'] = time() + 300;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'audricaurelius.aa@gmail.com';
        $mail->Password   = 'leyp iuwc jxfs emlm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@unpar.ac.id', 'Registrasi Akun ASP');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran Akun ASP';
        $mail->Body    = "Halo <b>$nama</b>,<br><br>Gunakan kode berikut untuk menyelesaikan pendaftaran akun ASP Anda. Kode ini berlaku 5 menit.<br><br>Kode Verifikasi: <h1>$otp</h1><br><br>Abaikan email ini jika Anda tidak mendaftar.<br><br>Terima kasih.";
        
        $mail->send();
        
        unset($_SESSION['old_data_asp'], $_SESSION['error_asp']);
        
        header("Location: verify_asp.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_asp'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: register_asp.php");
        exit();
    }
}
?>