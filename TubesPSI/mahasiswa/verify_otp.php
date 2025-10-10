<?php
session_start();

// Redirect jika pengguna mencoba akses halaman ini secara langsung
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}
$error_message = $_SESSION['error'] ?? null;
$success_message = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi & Reset Password - Sistem Event Unpar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary-color: #347ab8;
            --secondary-color: #2c3e50;
            --text-color: #222; /* --- PERUBAIKAN: Teks digelapkan dari #333 menjadi #222 --- */
            --light-text-color: #555;
            --border-color: rgba(255, 255, 255, 0.4);
            --input-bg-color: rgba(255, 255, 255, 0.5);
            --success-bg: #e0f8e9;
            --success-text: #1d7c46;
            --success-color: #2ecc71;
            --error-bg: #fde2e4;
            --error-text: #932028;
            --error-color: #e74c3c;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex; justify-content: center; align-items: center; min-height: 100vh;
            background-image: url('../img/backgroundUnpar.jpeg'); background-size: cover; background-position: center;
            padding: 20px;
        }

        .container {
            width: 100%; max-width: 480px;
            /* --- PERUBAIKAN: Opacity dinaikkan dari 0.15 ke 0.6 untuk kontras lebih baik --- */
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            padding: 40px; border: 1px solid var(--border-color);
            color: var(--text-color); /* --- PERUBAIKAN: Menggunakan warna teks utama yang lebih gelap --- */
        }
        
        .header { text-align: center; margin-bottom: 30px; }
        .header img { width: 80px; margin-bottom: 15px; }
        .header h1 { font-size: 1.8rem; font-weight: 600; color: var(--secondary-color); }
        .header p { font-size: 0.95rem; color: var(--light-text-color); margin-top: 5px; }
        .header p strong { color: var(--primary-color); }
        
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group input {
            width: 100%; padding: 12px 45px; border: 1px solid var(--border-color);
            border-radius: 10px; font-size: 1rem; font-family: 'Poppins', sans-serif;
            background: var(--input-bg-color); color: var(--text-color); transition: all 0.3s;
        }
        
        .form-group input::placeholder { /* --- TAMBAHAN: Memastikan placeholder juga jelas --- */
            color: #777;
        }

        .form-group .input-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #aaa; transition: color 0.3s ease;
        }
        
        .form-group .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            cursor: pointer; color: #aaa;
        }

        .form-group input:focus {
            outline: none; border-color: var(--primary-color); background: #fff;
            box-shadow: 0 0 0 3px rgba(52, 122, 184, 0.2);
        }

        .form-group input:focus ~ .input-icon, .form-group input:focus ~ .toggle-password {
            color: var(--primary-color);
        }
        
        .btn {
            width: 100%; padding: 14px; background: var(--success-color); color: white;
            border: none; border-radius: 10px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: all 0.3s; margin-top: 10px;
        }
        
        .btn:hover { background: #28b463; transform: translateY(-2px); }

        .message-box {
            padding: 12px 15px; margin-bottom: 20px; border-radius: 8px;
            font-size: 0.9rem; text-align: center;
        }
        .error-message { background-color: var(--error-bg); color: var(--error-text); }
        .success-message { background-color: var(--success-bg); color: var(--success-text); }
        
        #password-criteria {
            font-size: 0.9rem; margin-top: -10px; margin-bottom: 20px;
            padding-left: 5px;
        }
        #password-criteria p {
            margin: 8px 0; transition: color 0.3s ease;
            display: flex; align-items: center;
        }
        #password-criteria p i { margin-right: 8px; }
        #password-criteria p.invalid { color: var(--error-color); }
        #password-criteria p.valid { color: var(--success-color); }
    </style>
</head>
<body>
    <main class="container">
        <div class="header">
            <img src="../img/logo.png" alt="Logo Unpar">
            <h1>Reset Password Baru</h1>
            <p>Kode telah dikirim ke email <strong><?= htmlspecialchars($_SESSION['reset_email']); ?></strong></p>
        </div>

        <?php if ($error_message): ?>
            <div class="message-box error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message-box success-message"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="reset_password.php" method="POST">
            <div class="form-group">
                <input type="text" id="otp" name="otp" required placeholder="Kode OTP">
                <i class="input-icon fa-solid fa-key"></i>
            </div>
            <div class="form-group">
                <input type="password" id="new_password" name="new_password" required placeholder="Password Baru">
                <i class="input-icon fa-solid fa-lock"></i>
                <span class="toggle-password" onclick="toggleVisibility('new_password', 'icon-1')"><i class="fas fa-eye" id="icon-1"></i></span>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Konfirmasi Password Baru">
                <i class="input-icon fa-solid fa-lock"></i>
                <span class="toggle-password" onclick="toggleVisibility('confirm_password', 'icon-2')"><i class="fas fa-eye" id="icon-2"></i></span>
            </div>

            <div id="password-criteria">
                <p id="length-check" class="invalid"><i class="fa-solid fa-circle-xmark"></i> Minimal 8 karakter.</p>
                <p id="match-check" class="invalid"><i class="fa-solid fa-circle-xmark"></i> Password harus sama.</p>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const lengthCheck = document.getElementById('length-check');
            const matchCheck = document.getElementById('match-check');

            const validIcon = 'fa-solid fa-circle-check';
            const invalidIcon = 'fa-solid fa-circle-xmark';

            function updatePasswordCriteria() {
                // 1. Cek Panjang Karakter
                if (newPassword.value.length >= 8) {
                    lengthCheck.className = 'valid';
                    lengthCheck.innerHTML = `<i class="${validIcon}"></i> Minimal 8 karakter.`;
                } else {
                    lengthCheck.className = 'invalid';
                    lengthCheck.innerHTML = `<i class="${invalidIcon}"></i> Minimal 8 karakter.`;
                }

                // 2. Cek Kecocokan Password
                if (newPassword.value && confirmPassword.value && newPassword.value === confirmPassword.value) {
                    matchCheck.className = 'valid';
                    matchCheck.innerHTML = `<i class="${validIcon}"></i> Password sama.`;
                } else {
                    matchCheck.className = 'invalid';
                    matchCheck.innerHTML = `<i class="${invalidIcon}"></i> Password harus sama.`;
                }
            }

            newPassword.addEventListener('input', updatePasswordCriteria);
            confirmPassword.addEventListener('input', updatePasswordCriteria);
        });

        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId).classList;
            if (input.type === 'password') {
                input.type = 'text';
                icon.remove('fa-eye');
                icon.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.remove('fa-eye-slash');
                icon.add('fa-eye');
            }
        }
    </script>
</body>
</html>