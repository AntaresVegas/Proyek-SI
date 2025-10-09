<?php
session_start();
$error_message = $_SESSION['error'] ?? null;
$success_message = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Event Unpar</title>
    
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
            --error-bg: #fde2e4;
            --error-text: #932028;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('../img/backgroundUnpar.jpeg');
            background-size: cover;
            background-position: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 480px;
            /* --- PERUBAIKAN: Opacity dinaikkan dari 0.15 ke 0.6 untuk kontras lebih baik --- */
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            padding: 40px;
            border: 1px solid var(--border-color);
            color: var(--text-color); /* --- PERUBAIKAN: Menggunakan warna teks utama yang lebih gelap --- */
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header img {
            width: 80px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--secondary-color); /* Judul tetap menggunakan warna sekunder */
        }
        
        .header p {
            font-size: 0.95rem;
            color: var(--light-text-color);
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: var(--input-bg-color);
            color: var(--text-color);
            transition: all 0.3s;
        }

        .form-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: color 0.3s ease;
        }
        
        .form-group input::placeholder { /* --- TAMBAHAN: Memastikan placeholder juga jelas --- */
            color: #777;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(52, 122, 184, 0.2);
        }

        .form-group input:focus + .input-icon {
            color: var(--primary-color);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #2a6da7;
            transform: translateY(-2px);
        }

        .message-box {
            padding: 12px 15px; margin-bottom: 20px; border-radius: 8px;
            font-size: 0.9rem; text-align: center;
        }
        .error-message { background-color: var(--error-bg); color: var(--error-text); }
        .success-message { background-color: var(--success-bg); color: var(--success-text); }

        .bottom-link { text-align: center; margin-top: 25px; }
        .bottom-link a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .bottom-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="container">
        <div class="header">
            <img src="../img/logo.png" alt="Logo Unpar">
            <h1>Lupa Password</h1>
            <p>Masukkan email Anda yang terdaftar untuk menerima kode verifikasi.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="message-box error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="message-box success-message"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="send_otp.php" method="POST">
            <div class="form-group">
                <input type="email" id="email" name="email" required placeholder="Email terdaftar">
                <i class="input-icon fa-solid fa-envelope"></i>
            </div>
            <button type="submit" class="btn">Kirim Kode</button>
        </form>

        <div class="bottom-link">
            <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Login</a>
        </div>
    </main>
</body>
</html>