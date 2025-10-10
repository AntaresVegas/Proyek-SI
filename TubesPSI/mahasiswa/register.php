<?php
session_start();

// Daftar jurusan lengkap
$daftar_jurusan = [
    "Administrasi Bisnis", "Administrasi Publik", "Akuntansi", "Arsitektur",
    "D-3 Manajemen Perusahaan", "D-4 Agribisnis Pangan", "D-4 Bisnis Kreatif",
    "D-4 Teknologi Rekayasa Pangan", "Doktor Arsitektur", "Doktor Ekonomi",
    "Doktor Hukum", "Doktor Teknik Sipil", "Ekonomi Pembangunan", "Filsafat",
    "Fisika", "Hubungan Internasional", "Hukum", "Informatika", "Kedokteran",
    "Magister Administrasi Bisnis", "Magister Arsitektur", "Magister Filsafat Keilahian",
    "Magister Hubungan Internasional", "Magister Hukum", "Magister Manajemen",
    "Magister Pendidikan Ilmu Pengetahuan Alam", "Magister Studi Pembangunan",
    "Magister Teknik Industri", "Magister Teknik Kimia", "Magister Teknik Sipil",
    "Manajemen", "Matematika", "Pendidikan Bahasa Inggris", "Pendidikan Fisika",
    "Pendidikan Guru Sekolah Dasar", "Pendidikan Kimia", "Pendidikan Matematika",
    "Pendidikan Teknik Informatika & Komputer", "Profesi Arsitek", "Profesi Dokter",
    "Profesi Insinyur", "Studi Humanitas", "Teknik Industri", "Teknik Kimia",
    "Teknik Mekatronika", "Teknik Sipil"
];

// Mapping Jurusan ke 3 digit awal NPM
$jurusan_to_npm_map = [
    "Hukum" => "605", "Matematika" => "616", "Fisika" => "617", "Teknik Industri" => "613",
    "Teknik Kimia" => "614", "Informatika" => "618", "Akuntansi" => "604",
    "Hubungan Internasional" => "609", "Administrasi Bisnis" => "608", "D-3 Manajemen Perusahaan" => "503",
    "Manajemen" => "603", "Ekonomi Pembangunan" => "602", "Teknik Sipil" => "610",
    "Filsafat" => "612", "Administrasi Publik" => "607", "Arsitektur" => "622",
    "D-4 Agribisnis Pangan" => "624", "D-4 Bisnis Kreatif" => "625", "D-4 Teknologi Rekayasa Pangan" => "626",
    "Doktor Arsitektur" => "627", "Doktor Ekonomi" => "628", "Doktor Hukum" => "629",
    "Doktor Teknik Sipil" => "630", "Kedokteran" => "633", "Magister Administrasi Bisnis" => "634",
    "Magister Arsitektur" => "635", "Magister Filsafat Keilahian" => "636",
    "Magister Hubungan Internasional" => "637", "Magister Hukum" => "638", "Magister Manajemen" => "639",
    "Magister Pendidikan Ilmu Pengetahuan Alam" => "640", "Magister Studi Pembangunan" => "641",
    "Magister Teknik Industri" => "642", "Magister Teknik Kimia" => "643", "Magister Teknik Sipil" => "644",
    "Pendidikan Bahasa Inggris" => "646", "Pendidikan Fisika" => "647", "Pendidikan Guru Sekolah Dasar" => "648",
    "Pendidikan Kimia" => "649", "Pendidikan Matematika" => "650",
    "Pendidikan Teknik Informatika & Komputer" => "651", "Profesi Arsitek" => "652",
    "Profesi Dokter" => "653", "Profesi Insinyur" => "654", "Studi Humanitas" => "655", "Teknik Mekatronika" => "656"
];

// Mapping sebaliknya untuk JavaScript
$npm_to_jurusan_map = array_flip($jurusan_to_npm_map);
$background_path = '../img/backgroundUnpar.jpeg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrasi Mahasiswa - Sistem Event Unpar</title>
    
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
        
        .form-group input, .form-group select {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            background: var(--input-bg-color);
            color: var(--text-color);
            transition: all 0.3s;
        }
        
        .form-group input {
            padding: 12px 15px 12px 45px;
        }
        
        .form-group select {
            padding: 12px 15px 12px 45px;
            appearance: none; 
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23333333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e"); 
            background-repeat: no-repeat; 
            background-position: right .75rem center; 
            background-size: 16px 12px;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: var(--primary-color); background: #fff;
            box-shadow: 0 0 0 3px rgba(52, 122, 184, 0.2);
        }

        .form-group input:focus ~ .input-icon,
        .form-group select:focus ~ .input-icon {
            color: var(--primary-color); 
        }
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
        <img src="../img/logo.png" alt="Logo Unpar">
        <h1>Registrasi Akun Baru</h1>
        <p>Lengkapi data diri Anda untuk memulai.</p>
    </div>

    <?php if (isset($_SESSION['error'])) : ?>
      <div class="message-box error-message"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="send_register_otp.php" method="POST" autocomplete="off">
        <div class="form-group">
            <label for="mahasiswa_nama">Nama Lengkap</label>
            <input type="text" id="mahasiswa_nama" name="mahasiswa_nama" required value="<?php echo isset($_SESSION['old_data']['nama']) ? htmlspecialchars($_SESSION['old_data']['nama']) : ''; ?>" placeholder="Masukkan nama lengkap">
            <i class="input-icon fa-solid fa-user"></i>
        </div>
        <div class="form-group">
            <label for="mahasiswa_npm">NPM</label>
            <input type="text" id="mahasiswa_npm" name="mahasiswa_npm" required value="<?php echo isset($_SESSION['old_data']['npm']) ? htmlspecialchars($_SESSION['old_data']['npm']) : ''; ?>" maxlength="10" pattern="\d*" placeholder="Contoh: 6182201001">
            <i class="input-icon fa-solid fa-id-card"></i>
        </div>
        <div class="form-group">
            <label for="mahasiswa_jurusan">Jurusan</label>
            <select id="mahasiswa_jurusan" name="mahasiswa_jurusan" required>
                <option value="">-- Pilih Jurusan --</option>
                <?php foreach ($daftar_jurusan as $jur) : ?>
                    <option value="<?php echo htmlspecialchars($jur); ?>" <?php echo (isset($_SESSION['old_data']['jurusan']) && $_SESSION['old_data']['jurusan'] == $jur) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jur); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="input-icon fa-solid fa-graduation-cap"></i>
        </div>
        <div class="form-group">
            <label for="mahasiswa_email">Email</label>
            <input type="email" id="mahasiswa_email" name="mahasiswa_email" required value="<?php echo isset($_SESSION['old_data']['email']) ? htmlspecialchars($_SESSION['old_data']['email']) : ''; ?>" placeholder="Gunakan email Unpar">
            <i class="input-icon fa-solid fa-envelope"></i>
        </div>
        <?php unset($_SESSION['old_data']); ?>
        <button type="submit" name="register" class="btn">Kirim Kode Verifikasi</button>
    </form>

    <div class="bottom-link">
        <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Login</a>
    </div>
  </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const jurusanToNpm = <?php echo json_encode($jurusan_to_npm_map); ?>;
    const npmToJurusan = <?php echo json_encode($npm_to_jurusan_map); ?>;
    const jurusanSelect = document.getElementById('mahasiswa_jurusan');
    const npmInput = document.getElementById('mahasiswa_npm');

    jurusanSelect.addEventListener('change', function() {
        const selectedJurusan = this.value;
        const currentNpmValue = npmInput.value;
        const sisaNpm = currentNpmValue.length > 3 ? currentNpmValue.substring(3) : '';
        if (jurusanToNpm[selectedJurusan]) {
            npmInput.value = jurusanToNpm[selectedJurusan] + sisaNpm;
        }
    });

    npmInput.addEventListener('input', function() {
        const typedNpm = this.value;
        if (typedNpm.length >= 3) {
            const prefix = typedNpm.substring(0, 3);
            jurusanSelect.value = npmToJurusan[prefix] || '';
        } else {
            jurusanSelect.value = '';
        }
    });
});
</script>
</body>
</html>