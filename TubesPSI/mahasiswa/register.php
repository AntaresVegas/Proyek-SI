<?php
session_start();
// Pastikan path ke file koneksi database sudah benar
require_once(__DIR__ . '/../config/db_connection.php');

// Daftar jurusan untuk dropdown
$daftar_jurusan = [
    "D-3 Manajemen Perusahaan", "D-4 Teknologi Rekayasa Pangan", "D-4 Bisnis Kreatif",
    "D-4 Agribisnis Pangan", "Ekonomi Pembangunan", "Manajemen", "Akuntansi", "Hukum",
    "Administrasi Publik", "Administrasi Bisnis", "Hubungan Internasional", "Teknik Sipil",
    "Arsitektur", "Filsafat", "Studi Humanitas", "Teknik Industri", "Teknik Kimia",
    "Teknik Mekatronika", "Matematika", "Fisika", "Informatika", "Kedokteran",
    "Pendidikan Kimia", "Pendidikan Fisika", "Pendidikan Matematika",
    "Pendidikan Teknik Informatika & Komputer", "Pendidikan Bahasa Inggris",
    "Pendidikan Guru Sekolah Dasar", "Magister Manajemen", "Magister Hukum",
    "Magister Studi Pembangunan", "Magister Hubungan Internasional", "Magister Administrasi Bisnis",
    "Magister Teknik Sipil", "Magister Arsitektur", "Magister Filsafat Keilahian",
    "Magister Teknik Industri", "Magister Teknik Kimia", "Magister Pendidikan Ilmu Pengetahuan Alam",
    "Doktor Ekonomi", "Doktor Hukum", "Doktor Teknik Sipil", "Doktor Arsitektur",
    "Profesi Insinyur", "Profesi Dokter", "Profesi Arsitek"
];

$errors = [];
if (isset($_POST['register'])) {
    $nama = trim($_POST['mahasiswa_nama']);
    $npm = trim($_POST['mahasiswa_npm']);
    $email = trim($_POST['mahasiswa_email']);
    $jurusan = trim($_POST['mahasiswa_jurusan']);
    $password = $_POST['mahasiswa_password'];
    $confirm_password = $_POST['confirm_password'];
    // Ambil input CAPTCHA dari user
    $captcha_input = trim($_POST['captcha']);

    // ======================================================
    // ## PENAMBAHAN: Validasi CAPTCHA ##
    // ======================================================
    if (empty($captcha_input) || !isset($_SESSION['captcha_text']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha_text'])) {
        $errors[] = "Kode CAPTCHA yang Anda masukkan salah.";
    }
    // Hapus session captcha setelah divalidasi untuk mencegah pemakaian ulang
    unset($_SESSION['captcha_text']);
    // ======================================================

    // 1. Validasi Input
    if (empty($nama) || empty($npm) || empty($email) || empty($jurusan) || empty($password) || empty($confirm_password)) {
        $errors[] = "Semua field harus diisi.";
    }

    // 2. Validasi NPM (harus 10 digit angka)
    if (!preg_match('/^[0-9]{10}$/', $npm)) {
        $errors[] = "NPM harus terdiri dari 10 digit angka.";
    }

    // 3. Validasi Email
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $errors[] = "Format email tidak valid.";
      } else {
          $domain = substr(strrchr($email, "@"), 1);
          $allowed_domains = ['gmail.com', 'student.unpar.ac.id'];
          if (!in_array($domain, $allowed_domains)) {
              $errors[] = "Hanya email dengan domain @gmail.com atau @student.unpar.ac.id yang diizinkan.";
          }
      }
      
    // 4. Validasi Password
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok.";
    }

    // Cek duplikasi NPM jika tidak ada error validasi sebelumnya
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT mahasiswa_npm FROM mahasiswa WHERE mahasiswa_npm = ?");
        $stmt->bind_param("s", $npm);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "NPM sudah terdaftar.";
        }
        $stmt->close();
    }

    // Jika tidak ada error sama sekali, proses data
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO mahasiswa (mahasiswa_nama, mahasiswa_npm, mahasiswa_email, mahasiswa_jurusan, mahasiswa_password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $npm, $email, $jurusan, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registrasi berhasil! Silakan login.";
            header("Location: ../index.php"); // Arahkan ke halaman login
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        }
        $stmt->close();
    }
}
?>
<?php
$background_path = '../img/backgroundUnpar.jpeg';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrasi Mahasiswa</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  * {
    margin: 0; padding: 0; box-sizing: border-box;
  }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('<?php echo $background_path; ?>') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
  }
  .container {
    background: rgba(255, 255, 255, 0.9);
    padding: 40px 35px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-width: 480px;
    width: 100%;
  }
  h2 {
    font-size: 28px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 30px;
    text-align: center;
  }
  label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 600;
  }
  input[type=text],
  input[type=email],
  input[type=password],
  select {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
    background-color: #fff;
  }
  .form-group {
      margin-bottom: 20px;
  }
  input[type=text]:focus,
  input[type=email]:focus,
  input[type=password]:focus,
  select:focus {
    border-color: #3498db;
    outline: none;
  }
  button[type=submit] {
    width: 100%;
    padding: 14px;
    background-color: #2980b9;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 10px;
  }
  button[type=submit]:hover {
    background-color: #1f6391;
  }
  .error-message {
    background-color: #e74c3c;
    color: white;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 6px;
    list-style-position: inside;
  }
  .password-container {
    position: relative;
  }
  .password-container input {
    padding-right: 45px; /* Ruang untuk ikon */
  }
  .toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #888;
  }
  #password-criteria {
    font-size: 14px;
    margin-top: 10px;
  }
  #password-criteria p { margin: 5px 0; transition: color 0.3s ease; }
  #password-criteria p.invalid { color: #e74c3c; }
  #password-criteria p.valid { color: #2ecc71; }
  
  /* ====================================================== */
  /* ## CSS BARU: Untuk Styling CAPTCHA ## */
  /* ====================================================== */
  .captcha-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }
  .captcha-container img {
    border-radius: 8px;
    border: 1.5px solid #ddd;
  }
  .reload-btn {
    padding: 8px 12px;
    background-color: #ecf0f1;
    border: 1.5px solid #bdc3c7;
    border-radius: 8px;
    cursor: pointer;
    color: #2c3e50;
    font-size: 16px;
    transition: background-color 0.3s ease;
  }
  .reload-btn:hover {
    background-color: #dfe6e9;
  }
  /* ====================================================== */

  .back-link { text-align: center; margin-top: 20px; }
  .back-link a { color: #007bff; text-decoration: none; font-weight: 600; }
  .back-link a:hover { text-decoration: underline; }
</style>
</head>
<body>
  <div class="container">
    <h2>Registrasi Mahasiswa</h2>

    <?php if (!empty($errors)) : ?>
      <div class="error-message">
        <ul>
          <?php foreach ($errors as $error) : ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="POST" novalidate>
        <div class="form-group">
            <label for="mahasiswa_nama">Nama Lengkap</label>
            <input type="text" id="mahasiswa_nama" name="mahasiswa_nama" required value="<?php echo isset($_POST['mahasiswa_nama']) ? htmlspecialchars($_POST['mahasiswa_nama']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="mahasiswa_npm">NPM</label>
            <input type="text" id="mahasiswa_npm" name="mahasiswa_npm" required value="<?php echo isset($_POST['mahasiswa_npm']) ? htmlspecialchars($_POST['mahasiswa_npm']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="mahasiswa_email">Email</label>
            <input type="email" id="mahasiswa_email" name="mahasiswa_email" required value="<?php echo isset($_POST['mahasiswa_email']) ? htmlspecialchars($_POST['mahasiswa_email']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="mahasiswa_jurusan">Jurusan</label>
            <select id="mahasiswa_jurusan" name="mahasiswa_jurusan" required>
                <option value="">-- Pilih Jurusan --</option>
                <?php foreach ($daftar_jurusan as $jur) : ?>
                    <option value="<?php echo htmlspecialchars($jur); ?>" <?php echo (isset($_POST['mahasiswa_jurusan']) && $_POST['mahasiswa_jurusan'] == $jur) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jur); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="mahasiswa_password">Password</label>
            <div class="password-container">
                <input type="password" id="mahasiswa_password" name="mahasiswa_password" required autocomplete="new-password">
                <span class="toggle-password" onclick="togglePasswordVisibility('mahasiswa_password', 'toggle-icon-1')"><i class="fas fa-eye" id="toggle-icon-1"></i></span>
            </div>
        </div>
        <div class="form-group">
            <label for="confirm_password">Konfirmasi Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', 'toggle-icon-2')"><i class="fas fa-eye" id="toggle-icon-2"></i></span>
            </div>
        </div>
        <div id="password-criteria">
            <p id="length-check" class="invalid">❌ Password minimal 8 karakter.</p>
            <p id="match-check" class="invalid">❌ Password harus sama.</p>
        </div>
        
        <div class="form-group" style="margin-top: 20px;">
            <label for="captcha">Verifikasi (Masukkan kode di bawah)</label>
            <div class="captcha-container">
                <img src="captcha.php" alt="CAPTCHA Image" id="captcha-image">
                <button type="button" class="reload-btn" onclick="reloadCaptcha()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <input type="text" id="captcha" name="captcha" required autocomplete="off" placeholder="Masukkan kode captcha">
        </div>
        <button type="submit" name="register">Daftar</button>
    </form>

    <div class="back-link">
        <a href="../index.php">Kembali ke Halaman Utama</a>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('mahasiswa_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const lengthCheck = document.getElementById('length-check');
    const matchCheck = document.getElementById('match-check');

    function validatePassword() {
        if (passwordInput.value.length >= 8) {
            lengthCheck.classList.replace('invalid', 'valid');
            lengthCheck.textContent = '✅ Password minimal 8 karakter.';
        } else {
            lengthCheck.classList.replace('valid', 'invalid');
            lengthCheck.textContent = '❌ Password minimal 8 karakter.';
        }
        if (confirmPasswordInput.value.length > 0 && passwordInput.value === confirmPasswordInput.value) {
            matchCheck.classList.replace('invalid', 'valid');
            matchCheck.textContent = '✅ Password sama.';
        } else {
            matchCheck.classList.replace('valid', 'invalid');
            matchCheck.textContent = '❌ Password harus sama.';
        }
    }

    passwordInput.addEventListener('keyup', validatePassword);
    confirmPasswordInput.addEventListener('keyup', validatePassword);
});

function togglePasswordVisibility(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ======================================================
// ## JAVASCRIPT BARU: Untuk Reload CAPTCHA ##
// ======================================================
function reloadCaptcha() {
    const captchaImage = document.getElementById('captcha-image');
    // Menambahkan parameter acak ke URL untuk mencegah caching oleh browser
    captchaImage.src = 'captcha.php?_=' + new Date().getTime();
}
// ======================================================
</script>

</body>
</html>