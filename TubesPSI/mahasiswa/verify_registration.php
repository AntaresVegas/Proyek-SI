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
<title>Verifikasi Registrasi - Sistem Event Unpar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
    /* CSS ini telah disamakan sepenuhnya dengan file register.php yang sudah benar */
    :root {
        --primary-color: #347ab8; --secondary-color: #2c3e50; --text-color: #222;
        --light-text-color: #555; --border-color: rgba(255, 255, 255, 0.4);
        --input-bg-color: rgba(255, 255, 255, 0.5); --error-bg: #fde2e4; --error-text: #932028;
        --success-color: #2ecc71; --error-color: #e74c3c;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center;
        min-height: 100vh; background: url('<?php echo $background_path; ?>') no-repeat center center fixed;
        background-size: cover; padding: 20px;
    }
    .container {
        width: 100%; max-width: 480px; background: rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border-radius: 20px; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
        padding: 40px; border: 1px solid var(--border-color); color: var(--text-color);
    }
    .header { text-align: center; margin-bottom: 30px; }
    .header img {
        width: 80px; margin-bottom: 15px;
        filter: drop-shadow(0 3px 5px rgba(0, 0, 0, 0.2));
    }
    .header h1 { font-size: 1.8rem; font-weight: 600; color: var(--secondary-color); }
    .header p { font-size: 0.95rem; color: var(--light-text-color); margin-top: 5px; }
    .header p strong { color: var(--primary-color); }

    .form-group { margin-bottom: 20px; position: relative; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group .input-icon { position: absolute; left: 15px; top: 56px; transform: translateY(-50%); color: #aaa; z-index: 2; pointer-events: none;}
    .form-group .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #aaa; }
    
    .form-group input {
        width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color);
        border-radius: 10px; font-size: 1rem; font-family: 'Poppins', sans-serif;
        background: var(--input-bg-color); color: var(--text-color); transition: all 0.3s;
    }
    .form-group input::placeholder { color: #777; }
    
    .form-group input:focus {
        outline: none; border-color: var(--primary-color); background: #fff;
        box-shadow: 0 0 0 3px rgba(52, 122, 184, 0.2);
    }
    .form-group input:focus ~ .input-icon, 
    .form-group input:focus ~ .toggle-password { 
        color: var(--primary-color); 
    }

    .btn {
        width: 100%; padding: 14px; background: var(--success-color); color: white; border: none;
        border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 10px;
    }
    .btn:hover { background: #28b463; transform: translateY(-2px); }
    
    .message-box { padding: 12px 15px; margin-bottom: 20px; border-radius: 8px; font-size: 0.9rem; background-color: var(--error-bg); color: var(--error-text); }
    .message-box ul { list-style-position: inside; padding-left: 5px; margin:0; }
    .message-box li { margin-bottom: 5px; list-style-type: none; }
    
    #password-criteria { font-size: 0.9rem; margin-top: -10px; margin-bottom: 20px; padding-left: 5px; }
    #password-criteria p { margin: 8px 0; transition: color 0.3s ease; display: flex; align-items: center; }
    #password-criteria p i { margin-right: 8px; }
    #password-criteria p.invalid { color: var(--error-color); }
    #password-criteria p.valid { color: var(--success-color); }

    .captcha-container { display: flex; align-items: center; gap: 10px; }
    .captcha-container img { border-radius: 10px; border: 1px solid var(--border-color); flex-shrink: 0; }
    .reload-btn { padding: 10px 14px; background: var(--input-bg-color); border: 1px solid var(--border-color); border-radius: 10px; cursor: pointer; color: var(--secondary-color); font-size: 1rem; transition: all 0.3s; }
    .reload-btn:hover { background-color: #e9ecef; border-color: #ccc; }
</style>
</head>
<body>
  <main class="container">
    <div class="header">
        <img src="../img/logo.png" alt="Logo Unpar">
        <h1>Satu Langkah Lagi!</h1>
        <p>Kode verifikasi telah dikirim ke <strong><?php echo htmlspecialchars($_SESSION['registration_data']['email']); ?></strong></p>
    </div>

    <?php if (!empty($errors)) : ?>
      <div class="message-box">
        <ul>
          <?php foreach ($errors as $error) : ?>
            <li><i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="otp">Kode OTP</label>
            <input type="text" id="otp" name="otp" required autocomplete="one-time-code" maxlength="6" placeholder="Masukkan 6 digit kode">
            <i class="input-icon fa-solid fa-key"></i>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Buat password baru">
            <i class="input-icon fa-solid fa-lock"></i>
            <span class="toggle-password" onclick="togglePasswordVisibility('password', 'icon-1')"><i class="fas fa-eye" id="icon-1"></i></span>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password baru">
            <i class="input-icon fa-solid fa-lock"></i>
            <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', 'icon-2')"><i class="fas fa-eye" id="icon-2"></i></span>
        </div>
        <div id="password-criteria">
            <p id="length-check" class="invalid"><i class="fa-solid fa-circle-xmark"></i> Minimal 8 karakter.</p>
            <p id="match-check" class="invalid"><i class="fa-solid fa-circle-xmark"></i> Password harus sama.</p>
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label for="captcha">Verifikasi Anti-Bot</label>
            <div class="captcha-container">
                <img src="captcha.php" alt="CAPTCHA Image" id="captcha-image">
                <button type="button" class="reload-btn" onclick="reloadCaptcha()" title="Muat ulang gambar"><i class="fas fa-sync-alt"></i></button>
            </div>
        </div>
        <div class="form-group">
            <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Masukkan kode di atas">
            <i class="input-icon fa-solid fa-shield-halved"></i>
        </div>
        <button type="submit" class="btn">Selesaikan Pendaftaran</button>
    </form>
  </main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const lengthCheck = document.getElementById('length-check');
    const matchCheck = document.getElementById('match-check');
    const validIcon = 'fa-solid fa-circle-check';
    const invalidIcon = 'fa-solid fa-circle-xmark';

    function validatePassword() {
        if (passwordInput.value.length >= 8) {
            lengthCheck.className = 'valid';
            lengthCheck.innerHTML = `<i class="${validIcon}"></i> Minimal 8 karakter.`;
        } else {
            lengthCheck.className = 'invalid';
            lengthCheck.innerHTML = `<i class="${invalidIcon}"></i> Minimal 8 karakter.`;
        }
        if (confirmPasswordInput.value && passwordInput.value && passwordInput.value === confirmPasswordInput.value) {
            matchCheck.className = 'valid';
            matchCheck.innerHTML = `<i class="${validIcon}"></i> Password sama.`;
        } else {
            matchCheck.className = 'invalid';
            matchCheck.innerHTML = `<i class="${invalidIcon}"></i> Password harus sama.`;
        }
    }
    passwordInput.addEventListener('input', validatePassword);
    confirmPasswordInput.addEventListener('input', validatePassword);
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
    document.getElementById('captcha-image').src = 'captcha.php?v=' + new Date().getTime();
}
</script>
</body>
</html>