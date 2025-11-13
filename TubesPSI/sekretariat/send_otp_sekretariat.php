<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); 
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [DIUBAH] Mengambil data dari form sekretariat
    $nama = trim($_POST['sekuniv_nama']);
    $nik = trim($_POST['sekuniv_NIK']);
    $email = trim($_POST['sekuniv_email']);

    // [DIUBAH] Menggunakan session old data sekretariat
    $_SESSION['old_data_sekretariat'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    
    // Validasi
    if (empty($nama) || empty($nik) || empty($email)) {
        $_SESSION['error_sekretariat'] = "Semua field harus diisi.";
        header("Location: register_sekretariat.php");
        exit();
    }
    // Asumsi NIK sekretariat juga punya aturan minimal 10 digit
    if (!preg_match('/^[0-9]{10,}$/', $nik)) {
        $_SESSION['error_sekretariat'] = "NIK harus terdiri dari minimal 10 digit angka.";
        header("Location: register_sekretariat.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_sekretariat'] = "Format email tidak valid.";
        header("Location: register_sekretariat.php");
        exit();
    } else {
        $domain = substr(strrchr($email, "@"), 1);
        $allowed_domains = ['unpar.ac.id', 'gmail.com']; // Sesuaikan jika perlu
        if (!in_array(strtolower($domain), $allowed_domains)) {
            $_SESSION['error_sekretariat'] = "Hanya email domain @unpar.ac.id atau @gmail.com yang diizinkan.";
            header("Location: register_sekretariat.php");
            exit();
        }
    }

    // [DIUBAH] Cek Duplikasi NIK atau Email di tabel sekretariat_universitas
    $stmt = $conn->prepare("SELECT sekuniv_NIK FROM sekretariat_universitas WHERE sekuniv_NIK = ? OR sekuniv_email = ?");
    $stmt->bind_param("ss", $nik, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_sekretariat'] = "NIK atau Email sudah terdaftar.";
        header("Location: register_sekretariat.php");
        exit();
    }
    $stmt->close();

    // Generate dan Kirim OTP
    $otp = rand(100000, 999999);
    
    // [DIUBAH] Simpan data ke session sekretariat
    $_SESSION['reg_data_sekretariat'] = ['nama' => $nama, 'nik' => $nik, 'email' => $email];
    $_SESSION['reg_otp_sekretariat'] = $otp;
    $_SESSION['otp_expiry_sekretariat'] = time() + 300; // 5 menit

    $mail = new PHPMailer(true);
    try {
        // Konfigurasi SMTP Anda
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'audricaurelius.aa@gmail.com'; // Ganti dengan email pengirim
        $mail->Password   = 'leyp iuwc jxfs emlm'; // Ganti dengan password/app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // [DIUBAH] Sesuaikan pengirim dan subjek email
        $mail->setFrom('no-reply@unpar.ac.id', 'Registrasi Akun Sekretariat');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Pendaftaran Akun Sekretariat';
        $mail->Body    = "Halo <b>$nama</b>,<br><br>Gunakan kode berikut untuk menyelesaikan pendaftaran akun Sekretariat Universitas Anda. Kode ini berlaku 5 menit.<br><br>Kode Verifikasi: <h1>$otp</h1><br><br>Abaikan email ini jika Anda tidak mendaftar.<br><br>Terima kasih.";
        
        $mail->send();
        
        unset($_SESSION['old_data_sekretariat'], $_SESSION['error_sekretariat']);
        
        // [DIUBAH] Redirect ke verify_sekretariat.php
        header("Location: verify_sekretariat.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_sekretariat'] = "Gagal mengirim OTP. Mailer Error: {$mail->ErrorInfo}";
        header("Location: register_sekretariat.php");
        exit();
    }
}
?>