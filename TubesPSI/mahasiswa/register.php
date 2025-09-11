<?php
session_start();
require_once(__DIR__ . '/../config/db_connection.php'); // Sesuaikan path koneksi DB

if (isset($_POST['register'])) {
    $nama = trim($_POST['mahasiswa_nama']);
    $npm = trim($_POST['mahasiswa_npm']);
    $email = trim($_POST['mahasiswa_email']);
    $jurusan = trim($_POST['mahasiswa_jurusan']);
    $password = $_POST['mahasiswa_password'];
    $errors = [];

    // Validasi sederhana
    if (empty($nama) || empty($npm) || empty($email) || empty($jurusan) || empty($password)) {
        $errors[] = "Semua field harus diisi";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter";
    }

    // Cek npm sudah ada atau belum
    $stmt = $conn->prepare("SELECT mahasiswa_npm FROM mahasiswa WHERE mahasiswa_npm = ?");
    $stmt->bind_param("s", $npm);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "NPM sudah terdaftar";
    }
    $stmt->close();

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO mahasiswa (mahasiswa_nama, mahasiswa_npm, mahasiswa_email, mahasiswa_jurusan, mahasiswa_password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $npm, $email, $jurusan, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Registrasi berhasil! Selamat datang, $nama.";
            header("Location: ../index.php");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        }
        $stmt->close();
    }
}
?>
<?php
// Path gambar background
$background_path = '../img/backgroundUnpar.jpeg'; // sesuaikan pathnya
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrasi Mahasiswa</title>
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
    background: rgba(255, 255, 255, 0.85);
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
  input[type=password] {
    width: 100%;
    padding: 12px 14px;
    margin-bottom: 20px;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
  }

  input[type=text]:focus,
  input[type=email]:focus,
  input[type=password]:focus {
    border-color: #3498db;
    outline: none;
  }

  button {
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
  }

  button:hover {
    background-color: #1f6391;
  }

  .error-message {
    background-color: #e74c3c;
    color: white;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 6px;
  }

  .success-message {
    background-color: #2ecc71;
    color: white;
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 6px;
  }
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

<form action="" method="POST" novalidate autocomplete="off">
  <!-- Input tersembunyi untuk mencegah autofill -->
  <input type="text" name="fakeusernameremembered" style="display:none;">
  <input type="password" name="fakepasswordremembered" style="display:none;">

  <label for="mahasiswa_nama">Nama Lengkap</label>
  <input type="text" id="mahasiswa_nama" name="mahasiswa_nama" required autocomplete="off"
         value="<?php echo isset($_POST['mahasiswa_nama']) ? htmlspecialchars($_POST['mahasiswa_nama']) : ''; ?>">

  <label for="mahasiswa_npm">NPM</label>
  <input type="text" id="mahasiswa_npm" name="mahasiswa_npm" required autocomplete="off"
         value="<?php echo isset($_POST['mahasiswa_npm']) ? htmlspecialchars($_POST['mahasiswa_npm']) : ''; ?>">

  <label for="mahasiswa_email">Email</label>
  <input type="email" id="mahasiswa_email" name="mahasiswa_email" required autocomplete="off"
         value="<?php echo isset($_POST['mahasiswa_email']) ? htmlspecialchars($_POST['mahasiswa_email']) : ''; ?>">

  <label for="mahasiswa_jurusan">Jurusan</label>
  <input type="text" id="mahasiswa_jurusan" name="mahasiswa_jurusan" required autocomplete="off"
         value="<?php echo isset($_POST['mahasiswa_jurusan']) ? htmlspecialchars($_POST['mahasiswa_jurusan']) : ''; ?>">

  <label for="mahasiswa_password">Password</label>
  <input type="password" id="mahasiswa_password" name="mahasiswa_password" required autocomplete="new-password">

  <button type="submit" name="register">Daftar</button>
</form>

  </div>
</body>
</html>

