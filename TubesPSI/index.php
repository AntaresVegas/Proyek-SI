<?php
session_start();
// Hapus pesan session setelah ditampilkan agar tidak muncul lagi saat halaman di-refresh
$error_message = $_SESSION['error'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error'], $_SESSION['success_message']);

$pw_reset_success = isset($_GET['status']) && $_GET['status'] === 'pw_reset_success';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Selamat Datang di Sistem Pengelolaan Event Unpar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary-color: #347ab8; /* Biru yang lebih modern, terinspirasi dari Unpar */
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --text-color: #333;
            --light-text-color: #666;
            --border-color: rgba(255, 255, 255, 0.4);
            --input-bg-color: rgba(255, 255, 255, 0.5);
            --success-bg: #e0f8e9;
            --success-text: #1d7c46;
            --error-bg: #fde2e4;
            --error-text: #932028;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: url('./img/backgroundUnpar.jpeg');
            background-size: cover;
            background-position: center;
            padding: 20px;
            transition: background-image 0.5s ease-in-out;
        }

        .login-container {
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            overflow: hidden;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            background: rgba(0, 0, 0, 0.1);
        }

        /* --- Welcome Section (Left Panel) --- */
        .welcome-panel {
            padding: 50px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-right: 1px solid var(--border-color);
        }

        .welcome-panel img {
            width: 150px;
            height: auto;
            margin: 0 auto 25px;
            transition: transform 0.4s ease, opacity 0.4s ease;
        }

        .welcome-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .welcome-content p {
            font-size: 1rem;
            line-height: 1.6;
            font-weight: 300;
            transition: opacity 0.4s ease;
        }

        /* --- Login Section (Right Panel) --- */
        .login-panel {
            padding: 50px 40px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
            margin-bottom: 20px;
            text-align: center;
        }

        /* User Choice Cards */
        .user-choice-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .user-choice-card {
            padding: 12px;
            border: 1px solid transparent;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .user-choice-card i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            color: var(--secondary-color);
            transition: color 0.3s ease;
        }

        .user-choice-card h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .user-choice-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .user-choice-card.active {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 6px 20px rgba(52, 122, 184, 0.2);
        }

        .user-choice-card.active i, .user-choice-card.active h3 {
            color: var(--primary-color);
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Padding left untuk ikon */
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: var(--input-bg-color);
            color: var(--text-color);
            transition: all 0.3s;
        }

        .form-group input::placeholder {
            color: #999;
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
            box-shadow: 0 4px 15px rgba(52, 122, 184, 0.3);
        }

        .btn:hover {
            background: #2a6da7;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 122, 184, 0.4);
        }

        .form-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .form-links a {
            color: red;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .form-links a:hover {
            text-decoration: underline;
            color: #fafafaff;
        }

        /* Message Boxes */
        .message-box {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 0.9rem;
            text-align: center;
        }
        .error-message { background-color: var(--error-bg); color: var(--error-text); border-color: var(--error-text); }
        .success-message { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-text); }


        /* Responsive Design */
        @media (max-width: 950px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
                min-height: 0;
            }
            .welcome-panel {
                display: none; /* Sembunyikan panel kiri di mobile */
            }
            .login-panel {
                border-radius: 20px;
            }
        }

        @media (max-width: 500px) {
            body {
                padding: 10px;
            }
            .login-panel {
                padding: 30px 20px;
            }
            .login-header {
                font-size: 1.5rem;
            }
            .user-choice-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body id="body">

    <main class="login-container">
        <section class="welcome-panel">
            <img id="logo-img" src="./img/logo.png" alt="Logo Unpar" />
            <div class="welcome-content">
                <h1>Selamat Datang</h1>
                <p id="welcome-text">Di Situs Pengelolaan Event Mahasiswa Universitas Katolik Parahyangan</p>
            </div>
        </section>

        <section class="login-panel">
            <h2 class="login-header">Login</h2>

            <?php if ($error_message): ?>
                <div class="message-box error-message"><?= htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message-box success-message"><?= htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($pw_reset_success): ?>
                <div class="message-box success-message">Password berhasil direset! Silakan login dengan password baru Anda.</div>
            <?php endif; ?>

            <form action="./mahasiswa/process_login.php" method="POST" class="login-form">
                <div class="user-choice-container">
                    <div class="user-choice-card active" data-type="mahasiswa">
                        <i class="fa-solid fa-user-graduate"></i>
                        <h3>Mahasiswa</h3>
                    </div>
                    <div class="user-choice-card" data-type="ditmawa">
                        <i class="fa-solid fa-landmark"></i>
                        <h3>Ditmawa</h3>
                    </div>
                    <div class="user-choice-card" data-type="asp">
                        <i class="fa-solid fa-building-user"></i>
                        <h3>ASP</h3>
                    </div>
                </div>

                <input type="hidden" name="user_type" id="user_type" value="mahasiswa">
                
                <div class="form-group">
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="Masukkan email Anda" />
                    <i class="input-icon fa-solid fa-envelope"></i>
                </div>
                
                <div class="form-group">
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
                    <i class="input-icon fa-solid fa-lock"></i>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Login</button>
                </div>
                
                <div class="form-links">
                    <a href="./mahasiswa/forgot_password.php" id="forgot-password-link">Lupa Password?</a>
                    <a href="./mahasiswa/register.php" id="register-link">Daftar Akun Baru</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.user-choice-card');
            const form = document.querySelector('.login-form');
            const registerLink = document.getElementById('register-link');
            const forgotLink = document.getElementById('forgot-password-link');
            const body = document.getElementById('body');
            const logoImg = document.getElementById('logo-img');
            const welcomeText = document.getElementById('welcome-text');
            const hiddenInput = document.getElementById('user_type');

            const visualData = {
                mahasiswa: {
                    action: './mahasiswa/process_login.php',
                    registerHref: './mahasiswa/register.php',
                    bgImage: "url('./img/backgroundUnpar.jpeg')",
                    logoSrc: "./img/logo.png",
                    text: "Di Situs Pengelolaan Event Mahasiswa Universitas Katolik Parahyangan"
                },
                ditmawa: {
                    action: './ditmawa/process_login.php',
                    registerHref: './ditmawa/register_ditmawa.php',
                    bgImage: "url('./img/backgroundDitmawa.jpeg')",
                    logoSrc: "./img/logoDitmawa.png",
                    text: "Portal khusus untuk manajemen dan persetujuan kegiatan oleh Ditmawa."
                },
                asp: {
                    action: './asp/process_login.php',
                    registerHref: './asp/register_asp.php',
                    bgImage: "url('./img/backgroundASP.jpeg')",
                    logoSrc: "./img/logoASP.png",
                    text: "Portal khusus untuk persetujuan sarana prasarana kegiatan oleh ASP."
                }
            };

            function updateVisuals(userType) {
                const data = visualData[userType];
                if (!data) return;

                // Update form attributes
                form.action = data.action;
                registerLink.href = data.registerHref;
                hiddenInput.value = userType;
                
                // Transisi untuk teks dan o
                welcomeText.style.opacity = '0';
                logoImg.style.transform = 'scale(0.9)';
                logoImg.style.opacity = '0';

                setTimeout(() => {
                    // Update content after fade out
                    body.style.backgroundImage = data.bgImage;
                    logoImg.src = data.logoSrc;
                    welcomeText.textContent = data.text;

                    // Fade in new content
                    welcomeText.style.opacity = '1';
                    logoImg.style.transform = 'scale(1)';
                    logoImg.style.opacity = '1';
                }, 400); // Match transition duration

                // Tampilkan semua link secara default
                registerLink.style.display = 'inline';
                forgotLink.style.display = 'inline';
            }

            cards.forEach(card => {
                card.addEventListener('click', () => {
                    // Hanya update jika card yang diklik belum aktif
                    if (card.classList.contains('active')) {
                        return;
                    }
                    
                    cards.forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    
                    const userType = card.getAttribute('data-type');
                    updateVisuals(userType);
                });
            });
            
            // Inisialisasi tampilan awal berdasarkan card yang aktif
            const initialActiveCard = document.querySelector('.user-choice-card.active');
            if (initialActiveCard) {
                updateVisuals(initialActiveCard.getAttribute('data-type'));
            }
        });
    </script>
</body>
</html>