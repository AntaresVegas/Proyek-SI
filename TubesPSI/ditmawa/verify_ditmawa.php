<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php');

if (!isset($_SESSION['registration_data'])) {
    header('Location: register_ditmawa.php');
    exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $captcha_input = trim($_POST['captcha']);
    
    $otp_session = $_SESSION['registration_otp'] ?? null;
    $otp_expiry = $_SESSION['otp_expiry'] ?? 0;
    $captcha_session = $_SESSION['captcha_text'] ?? '';

    if ($otp_input != $otp_session) $errors[] = "Kode OTP salah.";
    if (time() > $otp_expiry) $errors[] = "Kode OTP sudah kedaluwarsa.";
    if (strlen($password) < 8) $errors[] = "Password minimal 8 karakter.";
    if ($password !== $confirm_password) $errors[] = "Konfirmasi password tidak cocok.";
    if (empty($captcha_input) || strtolower($captcha_input) !== strtolower($captcha_session)) $errors[] = "Kode CAPTCHA salah.";
    unset($_SESSION['captcha_text']);

    if (empty($errors)) {
        $reg_data = $_SESSION['registration_data'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Atur status default untuk pendaftaran baru
        $status_persetujuan = "Menunggu Persetujuan"; 

        // Query INSERT disesuaikan dengan semua kolom di tabel Anda
        $stmt = $conn->prepare("INSERT INTO ditmawa (ditmawa_nama, ditmawa_email, ditmawa_password, ditmawa_NIK, ditmawa_Divisi, ditmawa_Bagian, ditmawa_statusPersetujuan) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $reg_data['nama'], $reg_data['email'], $hashed_password, $reg_data['nik'], $reg_data['divisi'], $reg_data['bagian'], $status_persetujuan);

        if ($stmt->execute()) {
            unset($_SESSION['registration_data'], $_SESSION['registration_otp'], $_SESSION['otp_expiry']);
            
            $_SESSION['success_message'] = "Registrasi Akun Ditmawa berhasil! Mohon tunggu persetujuan dari Admin.";
            // Arahkan ke halaman login admin, pastikan path ini benar
            header("Location: ../admin/login.php"); 
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan data: " . $stmt->error;
        }
        $stmt->close();
    }
}
$background_path = '../img/backgroundUnpar.jpeg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Verifikasi Akun Ditmawa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    /* CSS bisa disamakan dengan file verify_registration mahasiswa sebelumnya */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: url('<?php echo $background_path; ?>') no-repeat center center fixed; background-size: cover; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
    .container { background: rgba(255, 255, 255, 0.9); padding: 40px 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 480px; width: 100%; }
    h2 { font-size: 28px; font-weight: 700; color: #2c3e50; margin-bottom: 10px; text-align: center; }
    .sub-heading { text-align: center; color: #555; margin-bottom: 30px; } .sub-heading strong { color: #2980b9; }
    .error-message { background-color: #e74c3c; color: white; padding: 12px; margin-bottom: 20px; border-radius: 6px; } .error-message ul { list-style-position: inside; padding-left: 0;}
    /* ... sisa CSS dari file sebelumnya ... */
</style>
</head>
<body>
  <div class="container">
    <h2>Satu Langkah Lagi!</h2>
    <p class="sub-heading">Kode verifikasi telah dikirim ke <strong><?php echo htmlspecialchars($_SESSION['registration_data']['email']); ?></strong></p>

    <?php if (!empty($errors)) : ?>
      <div class="error-message">
        <ul>
          <?php foreach ($errors as $error) : ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <div class="form-group">
            <label for="otp">Kode OTP</label>
            <input type="text" id="otp" name="otp" required autocomplete="one-time-code" maxlength="6">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
             </div>
        <div id="password-criteria">
            <p id="length-check" class="invalid">❌ Password minimal 8 karakter.</p>
            <p id="match-check" class="invalid">❌ Password harus sama.</p>
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label for="captcha">Verifikasi</label>
            </div>
        <button type="submit">Selesaikan Pendaftaran</button>
    </form>
  </div>
<script>
// Javascript tidak perlu diubah, tetap sama seperti sebelumnya
</script>
</body>
</html>