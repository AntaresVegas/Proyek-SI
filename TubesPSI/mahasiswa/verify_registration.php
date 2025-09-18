<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php');

if (!isset($_SESSION['registration_data'])) {
    header('Location: register.php');
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

    // Validasi
    if ($otp_input != $otp_session) $errors[] = "Kode OTP salah.";
    if (time() > $otp_expiry) $errors[] = "Kode OTP sudah kedaluwarsa. Silakan mulai ulang registrasi.";
    if (strlen($password) < 8) $errors[] = "Password minimal 8 karakter.";
    if ($password !== $confirm_password) $errors[] = "Konfirmasi password tidak cocok.";
    if (empty($captcha_input) || strtolower($captcha_input) !== strtolower($captcha_session)) $errors[] = "Kode CAPTCHA salah.";
    unset($_SESSION['captcha_text']);

    if (empty($errors)) {
        $reg_data = $_SESSION['registration_data'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO mahasiswa (mahasiswa_nama, mahasiswa_npm, mahasiswa_email, mahasiswa_jurusan, mahasiswa_password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $reg_data['nama'], $reg_data['npm'], $reg_data['email'], $reg_data['jurusan'], $hashed_password);

        if ($stmt->execute()) {
            unset($_SESSION['registration_data'], $_SESSION['registration_otp'], $_SESSION['otp_expiry']);
            
            $_SESSION['success_message'] = "Registrasi berhasil! Silakan login.";
            header("Location: ../index.php");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
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
<title>Verifikasi & Atur Password</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; background: url('<?php echo $background_path; ?>') no-repeat center center fixed; background-size: cover; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
    .container { background: rgba(255, 255, 255, 0.9); padding: 40px 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 480px; width: 100%; }
    h2 { font-size: 28px; font-weight: 700; color: #2c3e50; margin-bottom: 10px; text-align: center; }
    .sub-heading { text-align: center; color: #555; margin-bottom: 30px; }
    .sub-heading strong { color: #2980b9; }
    label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 600; }
    input[type=text], input[type=password] { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease; background-color: #fff; }
    .form-group { margin-bottom: 20px; }
    button[type=submit] { width: 100%; padding: 14px; background-color: #28a745; border: none; border-radius: 8px; color: white; font-size: 18px; font-weight: 700; cursor: pointer; transition: background-color 0.3s ease; margin-top: 10px; }
    button[type=submit]:hover { background-color: #218838; }
    .error-message { background-color: #e74c3c; color: white; padding: 12px; margin-bottom: 20px; border-radius: 6px; } .error-message ul { list-style-position: inside; padding-left: 0;} .error-message li { margin-bottom: 5px; }
    .password-container { position: relative; }
    .password-container input { padding-right: 45px; }
    .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; }
    #password-criteria { font-size: 14px; margin-top: 10px; }
    #password-criteria p { margin: 5px 0; transition: color 0.3s ease; }
    #password-criteria p.invalid { color: #e74c3c; }
    #password-criteria p.valid { color: #2ecc71; }
    .captcha-container { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .captcha-container img { border-radius: 8px; border: 1.5px solid #ddd; }
    .reload-btn { padding: 8px 12px; background-color: #ecf0f1; border: 1.5px solid #bdc3c7; border-radius: 8px; cursor: pointer; color: #2c3e50; font-size: 16px; }
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
            <div class="password-container">
                <input type="password" id="password" name="password" required>
                <span class="toggle-password" onclick="togglePasswordVisibility('password', 'icon-1')"><i class="fas fa-eye" id="icon-1"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', 'icon-2')"><i class="fas fa-eye" id="icon-2"></i></span>
            </div>
        </div>
        <div id="password-criteria">
            <p id="length-check" class="invalid">❌ Password minimal 8 karakter.</p>
            <p id="match-check" class="invalid">❌ Password harus sama.</p>
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label for="captcha">Verifikasi</label>
            <div class="captcha-container">
                <img src="captcha.php" alt="CAPTCHA Image" id="captcha-image">
                <button type="button" class="reload-btn" onclick="reloadCaptcha()"><i class="fas fa-sync-alt"></i></button>
            </div>
            <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Masukkan kode captcha">
        </div>
        <button type="submit">Selesaikan Pendaftaran</button>
    </form>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const lengthCheck = document.getElementById('length-check');
    const matchCheck = document.getElementById('match-check');

    function validatePassword() {
        if (passwordInput.value.length >= 8) {
            lengthCheck.className = 'valid';
            lengthCheck.textContent = '✅ Password minimal 8 karakter.';
        } else {
            lengthCheck.className = 'invalid';
            lengthCheck.textContent = '❌ Password minimal 8 karakter.';
        }
        if (confirmPasswordInput.value && passwordInput.value === confirmPasswordInput.value) {
            matchCheck.className = 'valid';
            matchCheck.textContent = '✅ Password sama.';
        } else {
            matchCheck.className = 'invalid';
            matchCheck.textContent = '❌ Password harus sama.';
        }
    }
    passwordInput.addEventListener('keyup', validatePassword);
    confirmPasswordInput.addEventListener('keyup', validatePassword);
});

function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function reloadCaptcha() {
    const captchaImage = document.getElementById('captcha-image');
    captchaImage.src = 'captcha.php?v=' + new Date().getTime();
}
</script>
</body>
</html>