<?php
session_start();

// Redirect jika pengguna mencoba akses halaman ini secara langsung
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP & Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: url('../img/backgroundUnpar.jpeg') no-repeat center center fixed; background-size: cover; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h1 { text-align: center; margin-bottom: 25px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .btn:hover { background: #218838; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .password-container { position: relative; }
        .password-container input { padding-right: 40px; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; }
        
        /* CSS untuk Automatic Checker (SUDAH DIPERBAIKI) */
        #password-criteria {
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 20px;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #e74c3c; /* Mulai dengan border merah */
            border-radius: 5px;
            transition: border-left-color 0.3s ease;
        }
        #password-criteria p {
            margin: 5px 0;
            transition: color 0.3s ease;
        }
        #password-criteria p.invalid {
            color: #e74c3c; /* Merah */
        }
        #password-criteria p.valid {
            color: #2ecc71; /* Hijau */
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Reset Password Baru</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="reset_password.php" method="POST">
            <div class="form-group">
                <label for="otp">Kode OTP:</label>
                <input type="text" id="otp" name="otp" required>
            </div>
            <div class="form-group">
                <label for="new_password">Password Baru:</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" required>
                    <span class="toggle-password" onclick="toggleVisibility('new_password', 'icon-1')"><i class="fas fa-eye" id="icon-1"></i></span>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru:</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="toggle-password" onclick="toggleVisibility('confirm_password', 'icon-2')"><i class="fas fa-eye" id="icon-2"></i></span>
                </div>
            </div>

            <div id="password-criteria">
                <p id="length-check" class="invalid">❌ Password minimal 8 karakter.</p>
                <p id="match-check" class="invalid">❌ Password harus sama.</p>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const lengthCheck = document.getElementById('length-check');
            const matchCheck = document.getElementById('match-check');
            const criteriaContainer = document.getElementById('password-criteria');

            function updatePasswordCriteria() {
                let isLengthValid = false;
                let isMatchValid = false;

                // 1. Cek Panjang Karakter
                if (newPassword.value.length >= 8) {
                    lengthCheck.classList.remove('invalid');
                    lengthCheck.classList.add('valid');
                    lengthCheck.textContent = '✅ Password minimal 8 karakter.';
                    isLengthValid = true;
                } else {
                    lengthCheck.classList.remove('valid');
                    lengthCheck.classList.add('invalid');
                    lengthCheck.textContent = '❌ Password minimal 8 karakter.';
                    isLengthValid = false;
                }

                // 2. Cek Kecocokan Password
                if (newPassword.value && confirmPassword.value && newPassword.value === confirmPassword.value) {
                    matchCheck.classList.remove('invalid');
                    matchCheck.classList.add('valid');
                    matchCheck.textContent = '✅ Password sama.';
                    isMatchValid = true;
                } else {
                    matchCheck.classList.remove('valid');
                    matchCheck.classList.add('invalid');
                    matchCheck.textContent = '❌ Password harus sama.';
                    isMatchValid = false;
                }

                // 3. Update warna border kontainer
                if (isLengthValid && isMatchValid) {
                    criteriaContainer.style.borderLeftColor = '#2ecc71'; // Hijau
                } else {
                    criteriaContainer.style.borderLeftColor = '#e74c3c'; // Merah
                }
            }

            // Tambahkan event listener ke kedua input password
            newPassword.addEventListener('keyup', updatePasswordCriteria);
            confirmPassword.addEventListener('keyup', updatePasswordCriteria);
        });

        // Fungsi untuk toggle ikon mata tetap ada
        function toggleVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>