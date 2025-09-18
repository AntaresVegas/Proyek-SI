<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Event Management Unpar</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: url('../img/backgroundUnpar.jpeg') no-repeat center center fixed; background-size: cover; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .form-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h1 { text-align: center; margin-bottom: 25px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 500; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; }
        .btn { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; margin-top: 10px; }
        .btn:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Lupa Password</h1>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">Masukkan email Anda untuk menerima kode verifikasi (OTP).</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?= htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success"><?= htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="send_otp.php" method="POST">
            <div class="form-group">
                <label for="email">Email Terdaftar:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">Kirim Kode OTP</button>
        </form>
        <div class="login-link">
            <a href="../index.php">Kembali ke Login</a>
        </div>
    </div>
</body>
</html>