<?php
session_start();
$background_path = '../img/backgroundUnpar.jpeg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrasi Akun Ditmawa - Langkah 1</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  /* CSS bisa disamakan dengan file sebelumnya */
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: url('<?php echo $background_path; ?>') no-repeat center center fixed; background-size: cover; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
  .container { background: rgba(255, 255, 255, 0.9); padding: 40px 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 480px; width: 100%; }
  h2 { font-size: 28px; font-weight: 700; color: #2c3e50; margin-bottom: 30px; text-align: center; }
  label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 600; }
  input[type=text], input[type=email] { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-size: 16px; }
  .form-group { margin-bottom: 20px; }
  button[type=submit] { width: 100%; padding: 14px; background-color: #2980b9; border: none; border-radius: 8px; color: white; font-size: 18px; font-weight: 700; cursor: pointer; margin-top: 10px; }
  .message { padding: 12px; margin-bottom: 20px; border-radius: 6px; color: white; background-color: #e74c3c; }
  .back-link { text-align: center; margin-top: 20px; }
  .back-link a { color: #007bff; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
  <div class="container">
    <h2>Registrasi Akun Ditmawa</h2>

    <?php if (isset($_SESSION['error'])) : ?>
      <div class="message">
        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="send_ditmawa_otp.php" method="POST" novalidate>
        <div class="form-group">
            <label for="ditmawa_nama">Nama Lengkap</label>
            <input type="text" id="ditmawa_nama" name="ditmawa_nama" required value="<?php echo htmlspecialchars($_SESSION['old_data']['nama'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="ditmawa_NIK">NIK</label>
            <input type="text" id="ditmawa_NIK" name="ditmawa_NIK" required maxlength="16" pattern="\d{16}" title="NIK harus terdiri dari 16 digit angka" value="<?php echo htmlspecialchars($_SESSION['old_data']['NIK'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="ditmawa_email">Email</label>
            <input type="email" id="ditmawa_email" name="ditmawa_email" required value="<?php echo htmlspecialchars($_SESSION['old_data']['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="ditmawa_Divisi">Divisi</label>
            <input type="text" id="ditmawa_Divisi" name="ditmawa_Divisi" required value="<?php echo htmlspecialchars($_SESSION['old_data']['Divisi'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="ditmawa_Bagian">Bagian</label>
            <input type="text" id="ditmawa_Bagian" name="ditmawa_Bagian" required value="<?php echo htmlspecialchars($_SESSION['old_data']['Bagian'] ?? ''); ?>">
        </div>
        <?php unset($_SESSION['old_data']); ?>
        <button type="submit">Kirim Kode Verifikasi</button>
    </form>

    <div class="back-link">
        <a href="../index.php">Kembali ke Halaman Utama</a>
    </div>
  </div>
</body>
</html>