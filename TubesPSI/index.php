<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Selamat Datang di Sistem Pengelolaan Event Unpar</title>
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
        .container {
            display: flex;
            flex-wrap: wrap; /* Agar responsif di layar kecil */
            gap: 40px;
            background: rgba(255, 255, 255, 0.92);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            max-width: 950px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .product-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-width: 300px;
        }
        .product-item img {
            width: 250px; /* Ukuran logo disesuaikan */
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .welcome-text {
            text-align: center;
            color: #2c3e50;
        }
        .welcome-text h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .welcome-text p {
            font-size: 16px;
            color: #555;
            line-height: 1.5;
        }

        .login-box {
            flex: 1;
            min-width: 320px;
            border-left: 1px solid #ddd;
            padding-left: 40px;
        }
        .header {
            font-size: 22px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        .btn {
            width: 100%; padding: 14px;
            background: #3498db; color: white;
            border: none; border-radius: 8px;
            font-size: 16px; font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover { background: #2980b9; }
        .form-links {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
        }
        .form-links a { color:rgb(248, 4, 0); text-decoration: none; }
        .message-box { padding: 12px; margin-bottom: 15px; border-radius: 5px; border: 1px solid; }
        .error-message { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        .success-message { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }

        @media (max-width: 850px) {
            .container { flex-direction: column; }
            .login-box { border-left: none; padding-left: 0; }
        }
    </style>
</head>
<body id="body">
    <div class="container">
        <div class="product-item">
            <img id="logo-img" src="./img/logo.png" alt="Logo Unpar" />
            <div class="welcome-text">
                <h1>Selamat Datang</h1>
                <p>Di Situs Pengelolaan Event Mahasiswa Universitas Katolik Parahyangan</p>
            </div>
        </div>

        <div class="login-box">
            <div class="header">Silakan Login</div>

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
            const forgotLink = document.getElementById('forgot-password-link');
            const body = document.getElementById('body');
            const logoImg = document.getElementById('logo-img');
            const welcomeText = document.querySelector('.welcome-text p');

            if (userType === 'mahasiswa') {
                form.action = './mahasiswa/process_login.php';
                registerLink.style.display = 'inline';
                forgotLink.style.display = 'inline';
                body.style.backgroundImage = "url('./img/backgroundUnpar.jpeg')";
                logoImg.src = "./img/logo.png";
                welcomeText.textContent = "Di Situs Pengelolaan Event Mahasiswa Universitas Katolik Parahyangan";
            } else { // Ditmawa
                form.action = './ditmawa/process_login.php';
                registerLink.style.display = 'none';
                forgotLink.style.display = 'none';
                body.style.backgroundImage = "url('./img/backgroundDitmawa.jpeg')";
                logoImg.src = "./img/logoDitmawa.png";
                welcomeText.textContent = "Portal khusus untuk manajemen dan persetujuan kegiatan oleh Ditmawa.";
            }
        }
        document.addEventListener('DOMContentLoaded', updateLoginView);
    </script>
</body>
</html>