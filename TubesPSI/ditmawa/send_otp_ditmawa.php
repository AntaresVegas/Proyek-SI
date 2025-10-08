<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya mengambil data yang ada di form
    $nama = trim($_POST['ditmawa_nama']);
    $nik = trim($_POST['ditmawa_nik']);
    $email = trim($_POST['ditmawa_email']);

    $_SESSION['old_data_ditmawa'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    
    // Validasi yang sudah diperbarui (tanpa Divisi dan Bagian)
    if (empty($nama) || empty($nik) || empty($email)) {
        $_SESSION['error_ditmawa'] = "Semua field harus diisi.";
        header("Location: register_ditmawa.php");
        exit();
    }
    if (!preg_match('/^[0-9]{10,}$/', $nik)) {
        $_SESSION['error_ditmawa'] = "NIK harus terdiri dari minimal 10 digit angka.";
        header("Location: register_ditmawa.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_ditmawa'] = "Format email tidak valid.";
        header("Location: register_ditmawa.php");
        exit();
    } else {
        $domain = substr(strrchr($email, "@"), 1);
        $allowed_domains = ['unpar.ac.id', 'gmail.com'];
        if (!in_array(strtolower($domain), $allowed_domains)) {
            $_SESSION['error_ditmawa'] = "Hanya email domain @unpar.ac.id atau @gmail.com yang diizinkan.";
            header("Location: register_ditmawa.php");
            exit();
        }
    }

    // Cek Duplikasi NIK atau Email di tabel ditmawa
    $stmt = $conn->prepare("SELECT ditmawa_NIK FROM ditmawa WHERE ditmawa_NIK = ? OR ditmawa_email = ?");
    $stmt->bind_param("ss", $nik, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_ditmawa'] = "NIK atau Email sudah terdaftar.";
        header("Location: register_ditmawa.php");
        exit();
    }
    $stmt->close();

    // Generate dan Kirim OTP
    $otp = rand(100000, 999999);
    
    // Simpan data ke session (tanpa Divisi dan Bagian)
    $_SESSION['reg_data_ditmawa'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    $_SESSION['reg_otp_ditmawa'] = $otp;
    $_SESSION['otp_expiry_ditmawa'] = time() + 300; // 5 menit

    $mail = new PHPMailer(true);
    try {
        // Konfigurasi SMTP Anda
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'audricaurelius.aa@gmail.com';
        $mail->Password   = 'leyp iuwc jxfs emlm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@unpar.ac.id', 'Registrasi Akun Ditmawa');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran Akun Ditmawa';
        $mail->Body    = "Halo <b>$nama</b>,<br><br>Gunakan kode berikut untuk menyelesaikan pendaftaran akun Ditmawa Anda. Kode ini berlaku 5 menit.<br><br>Kode Verifikasi: <h1>$otp</h1><br><br>Abaikan email ini jika Anda tidak mendaftar.<br><br>Terima kasih.";
        
        $mail->send();
        
        unset($_SESSION['old_data_ditmawa'], $_SESSION['error_ditmawa']);
        
        header("Location: verify_ditmawa.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_ditmawa'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: register_ditmawa.php");
        exit();
    }
}
?>