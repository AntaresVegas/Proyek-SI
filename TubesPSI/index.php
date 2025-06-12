<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sistem Pengelolaan Event Unpar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('./img/backgroundUnpar.jpeg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            transition: background-image 0.5s ease-in-out;
        }
        .container { display: flex; gap: 20px; background: rgba(255, 255, 255, 0.9); padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); max-width: 900px; width: 100%; }
        .product-item { flex: 1; display: flex; justify-content: center; align-items: center; }
        .product-item img { width: 350px; height: auto; border-radius: 8px; }
        .login-box { flex: 1.5; max-width: 450px; }
        .header { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
        .btn:hover { background: #2980b9; }
        .form-links { display: flex; justify-content: space-between; margin-top: 15px; font-size: 14px; }
        .form-links a { color:rgb(219, 52, 52); text-decoration: none; }
        .message-box { padding: 10px; margin-bottom: 15px; border-radius: 5px; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body id="body">
    <div class="container">
        <div class="product-item">
            <img id="logo-img" src="./img/logo.png" alt="Logo Unpar" />
        </div>

        <div class="login-box">
            <div class="header">Pengelolaan Event UNPAR</div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="message-box error-message"><?= htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] === 'pw_reset_success'): ?>
                <div class="message-box success-message">Password berhasil direset! Silakan login dengan password baru Anda.</div>
            <?php endif; ?>

            <form action="./mahasiswa/process_login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="user_type">Login Sebagai:</label>
                    <select name="user_type" id="user_type" required onchange="updateLoginView()">
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="ditmawa">Ditmawa</option>
                    </select>
                </div>
                <div class="form-group"><label for="email">Email:</label><input type="text" id="email" name="email" required autocomplete="email" /></div>
                <div class="form-group"><label for="password">Password:</label><input type="password" id="password" name="password" required autocomplete="current-password" /></div>
                <div class="form-group"><button type="submit" class="btn">Login</button></div>
                
                <div class="form-links">
                    <a href="./mahasiswa/forgot_password.php" id="forgot-password-link">Lupa Password?</a>
                    <a href="./mahasiswa/register.php" id="register-link">Daftar Akun Baru</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateLoginView() {
            const form = document.querySelector('.login-form');
            const userType = document.getElementById('user_type').value;
            const registerLink = document.getElementById('register-link');
            const forgotLink = document.getElementById('forgot-password-link'); // Ambil link lupa password
            const body = document.getElementById('body');
            const logoImg = document.getElementById('logo-img');

            if (userType === 'mahasiswa') {
                form.action = './mahasiswa/process_login.php';
                registerLink.style.display = 'inline'; // Tampilkan link daftar
                forgotLink.style.display = 'inline'; // Tampilkan link lupa password
                body.style.backgroundImage = "url('./img/backgroundUnpar.jpeg')";
                logoImg.src = "./img/logo.png";
            } else { // Ditmawa
                form.action = './ditmawa/process_login.php';
                registerLink.style.display = 'none'; // Sembunyikan link daftar
                forgotLink.style.display = 'none'; // Sembunyikan link lupa password
                body.style.backgroundImage = "url('./img/backgroundDitmawa.jpeg')";
                logoImg.src = "./img/logoDitmawa.png";
            }
        }
        // Panggil fungsi saat halaman pertama kali dimuat
        document.addEventListener('DOMContentLoaded', updateLoginView);
    </script>
</body>
</html>