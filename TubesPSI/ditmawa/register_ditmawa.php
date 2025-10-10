<?php
session_start();
$background_path = '../img/backgroundDitmawa.jpeg'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrasi Akun Ditmawa - Sistem Event Unpar</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary-color: #347ab8; --secondary-color: #2c3e50; --text-color: #222;
            --light-text-color: #555; --border-color: rgba(255, 255, 255, 0.4);
            --input-bg-color: rgba(255, 255, 255, 0.5); --error-bg: #fde2e4; --error-text: #932028;
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
        
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group .input-icon {
            position: absolute; left: 15px; top: 56px; transform: translateY(-50%);
            color: #aaa; transition: color 0.3s ease; z-index: 2; pointer-events: none;
        }
        
        .form-group input {
            width: 100%; padding: 12px 15px 12px 45px; border: 1px solid var(--border-color);
            border-radius: 10px; font-size: 1rem; font-family: 'Poppins', sans-serif;
            background: var(--input-bg-color); color: var(--text-color); transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none; border-color: var(--primary-color); background: #fff;
            box-shadow: 0 0 0 3px rgba(52, 122, 184, 0.2);
        }
        .form-group input:focus ~ .input-icon { color: var(--primary-color); }
        .form-group input::placeholder { color: #777; }

        .btn {
            width: 100%; padding: 14px; background: var(--primary-color); color: white; border: none;
            border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s;
        }
        .btn:hover { background: #2a6da7; transform: translateY(-2px); }
        .message-box { padding: 12px 15px; margin-bottom: 20px; border-radius: 8px; font-size: 0.9rem; text-align: center; }
        .error-message { background-color: var(--error-bg); color: var(--error-text); }
        .bottom-link { text-align: center; margin-top: 25px; }
        .bottom-link a { color: var(--primary-color); text-decoration: none; font-weight: 500; }
        .bottom-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
  <main class="container">
    <div class="header">
        <img src="../img/logoDitmawa.png" alt="Logo Ditmawa Unpar">
        <h1>Registrasi Akun Ditmawa</h1>
        <p>Lengkapi data Anda untuk melanjutkan.</p>
    </div>

    <?php if (isset($_SESSION['error_ditmawa'])) : ?>
      <div class="message-box error-message">
        <?php echo htmlspecialchars($_SESSION['error_ditmawa']); ?>
      </div>
      <?php unset($_SESSION['error_ditmawa']); ?>
    <?php endif; ?>

    <form action="send_otp_ditmawa.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="ditmawa_nama">Nama Lengkap</label>
            <input type="text" id="ditmawa_nama" name="ditmawa_nama" required value="<?php echo isset($_SESSION['old_data_ditmawa']['nama']) ? htmlspecialchars($_SESSION['old_data_ditmawa']['nama']) : ''; ?>" placeholder="Masukkan nama sesuai data karyawan">
            <i class="input-icon fa-solid fa-user"></i>
        </div>
        <div class="form-group">
            <label for="ditmawa_nik">NIK (Nomor Induk Karyawan)</label>
            <input type="text" id="ditmawa_nik" name="ditmawa_nik" required value="<?php echo isset($_SESSION['old_data_ditmawa']['nik']) ? htmlspecialchars($_SESSION['old_data_ditmawa']['nik']) : ''; ?>" pattern="\d*" placeholder="Masukkan NIK Anda">
            <i class="input-icon fa-solid fa-id-card"></i>
        </div>
        <div class="form-group">
            <label for="ditmawa_email">Email</label>
            <input type="email" id="ditmawa_email" name="ditmawa_email" required value="<?php echo isset($_SESSION['old_data_ditmawa']['email']) ? htmlspecialchars($_SESSION['old_data_ditmawa']['email']) : ''; ?>" placeholder="Gunakan email Unpar">
            <i class="input-icon fa-solid fa-envelope"></i>
        </div>
        <?php unset($_SESSION['old_data_ditmawa']); ?>
        <button type="submit" class="btn">Kirim Kode Verifikasi</button>
    </form>

    <div class="bottom-link">
        <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama</a>
    </div>
  </main>
</body>
</html>