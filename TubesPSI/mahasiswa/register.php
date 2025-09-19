<?php
session_start();

// Daftar jurusan lengkap (tidak diubah)
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

// START: MODIFIKASI (KODE NPM DIPERBARUI SESUAI DAFTAR BARU)
// Daftar ini berisi kode yang sudah fix dari Anda, dan sisa kode dari versi sebelumnya.
$jurusan_to_npm_map = [
    // -- Kode baru & yang diutamakan dari Anda --
    "Hukum" => "605",
    "Matematika" => "616",
    "Fisika" => "617",
    "Teknik Industri" => "613",
    "Teknik Kimia" => "614",
    "Informatika" => "618",
    "Akuntansi" => "604",
    "Hubungan Internasional" => "609",
    "Administrasi Bisnis" => "608",
    "D-3 Manajemen Perusahaan" => "503",
    "Manajemen" => "603",
    "Ekonomi Pembangunan" => "602",
    "Teknik Sipil" => "610",
    "Filsafat" => "612",
    "Administrasi Publik" => "607",

    // -- Sisa kode dari versi sebelumnya yang dipertahankan --
    "Arsitektur" => "622",
    "D-4 Agribisnis Pangan" => "624",
    "D-4 Bisnis Kreatif" => "625",
    "D-4 Teknologi Rekayasa Pangan" => "626",
    "Doktor Arsitektur" => "627",
    "Doktor Ekonomi" => "628",
    "Doktor Hukum" => "629",
    "Doktor Teknik Sipil" => "630",
    "Kedokteran" => "633",
    "Magister Administrasi Bisnis" => "634",
    "Magister Arsitektur" => "635",
    "Magister Filsafat Keilahian" => "636",
    "Magister Hubungan Internasional" => "637",
    "Magister Hukum" => "638",
    "Magister Manajemen" => "639",
    "Magister Pendidikan Ilmu Pengetahuan Alam" => "640",
    "Magister Studi Pembangunan" => "641",
    "Magister Teknik Industri" => "642",
    "Magister Teknik Kimia" => "643",
    "Magister Teknik Sipil" => "644",
    "Pendidikan Bahasa Inggris" => "646",
    "Pendidikan Fisika" => "647",
    "Pendidikan Guru Sekolah Dasar" => "648",
    "Pendidikan Kimia" => "649",
    "Pendidikan Matematika" => "650",
    "Pendidikan Teknik Informatika & Komputer" => "651",
    "Profesi Arsitek" => "652",
    "Profesi Dokter" => "653",
    "Profesi Insinyur" => "654",
    "Studi Humanitas" => "655",
    "Teknik Mekatronika" => "656"
];


// Buat mapping sebaliknya (NPM ke Jurusan) untuk JavaScript
$npm_to_jurusan_map = array_flip($jurusan_to_npm_map);
// END: MODIFIKASI

// Ganti path ini jika lokasi gambar background berbeda
$background_path = '../img/backgroundUnpar.jpeg'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrasi Mahasiswa - Langkah 1</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: url('<?php echo $background_path; ?>') no-repeat center center fixed; background-size: cover; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
  .container { background: rgba(255, 255, 255, 0.9); padding: 40px 35px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); max-width: 480px; width: 100%; }
  h2 { font-size: 28px; font-weight: 700; color: #2c3e50; margin-bottom: 30px; text-align: center; }
  label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 600; }
  input[type=text], input[type=email], select { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-size: 16px; transition: border-color 0.3s ease; background-color: #fff; }
  .form-group { margin-bottom: 20px; }
  input[type=text]:focus, input[type=email]:focus, select:focus { border-color: #3498db; outline: none; }
  button[type=submit] { width: 100%; padding: 14px; background-color: #2980b9; border: none; border-radius: 8px; color: white; font-size: 18px; font-weight: 700; cursor: pointer; transition: background-color 0.3s ease; margin-top: 10px; }
  button[type=submit]:hover { background-color: #1f6391; }
  .message { padding: 12px; margin-bottom: 20px; border-radius: 6px; list-style-position: inside; color: white; }
  .error { background-color: #e74c3c; }
  .back-link { text-align: center; margin-top: 20px; }
  .back-link a { color: #007bff; text-decoration: none; font-weight: 600; }
  .back-link a:hover { text-decoration: underline; }
</style>
</head>
<body>
  <div class="container">
    <h2>Registrasi Mahasiswa</h2>

    <?php if (isset($_SESSION['error'])) : ?>
      <div class="message error">
        <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="send_register_otp.php" method="POST" novalidate autocomplete="off">
        <div class="form-group">
            <label for="mahasiswa_nama">Nama Lengkap</label>
            <input type="text" id="mahasiswa_nama" name="mahasiswa_nama" required value="<?php echo isset($_SESSION['old_data']['nama']) ? htmlspecialchars($_SESSION['old_data']['nama']) : ''; ?>" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="mahasiswa_npm">NPM</label>
            <input type="text" id="mahasiswa_npm" name="mahasiswa_npm" required value="<?php echo isset($_SESSION['old_data']['npm']) ? htmlspecialchars($_SESSION['old_data']['npm']) : ''; ?>" maxlength="10" pattern="\d*" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="mahasiswa_jurusan">Jurusan</label>
            <select id="mahasiswa_jurusan" name="mahasiswa_jurusan" required autocomplete="off">
                <option value="">-- Pilih Jurusan --</option>
                <?php foreach ($daftar_jurusan as $jur) : ?>
                    <option value="<?php echo htmlspecialchars($jur); ?>" <?php echo (isset($_SESSION['old_data']['jurusan']) && $_SESSION['old_data']['jurusan'] == $jur) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($jur); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="mahasiswa_email">Email</label>
            <input type="email" id="mahasiswa_email" name="mahasiswa_email" required value="<?php echo isset($_SESSION['old_data']['email']) ? htmlspecialchars($_SESSION['old_data']['email']) : ''; ?>" autocomplete="off">
        </div>
        <?php unset($_SESSION['old_data']); ?>
        <button type="submit" name="register">Kirim Kode Verifikasi</button>
    </form>

    <div class="back-link">
        <a href="../index.php">Kembali ke Halaman Utama</a>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Ambil data mapping dari PHP
    const jurusanToNpm = <?php echo json_encode($jurusan_to_npm_map); ?>;
    const npmToJurusan = <?php echo json_encode($npm_to_jurusan_map); ?>;

    const jurusanSelect = document.getElementById('mahasiswa_jurusan');
    const npmInput = document.getElementById('mahasiswa_npm');

    // Event listener untuk dropdown JURUSAN
    jurusanSelect.addEventListener('change', function() {
        const selectedJurusan = this.value;
        const currentNpmValue = npmInput.value;
        const sisaNpm = currentNpmValue.length > 3 ? currentNpmValue.substring(3) : '';

        if (jurusanToNpm[selectedJurusan]) {
            const prefix = jurusanToNpm[selectedJurusan];
            npmInput.value = prefix + sisaNpm;
        }
    });

    // Event listener untuk input NPM
    npmInput.addEventListener('input', function() {
        const typedNpm = this.value;

        if (typedNpm.length >= 3) {
            const prefix = typedNpm.substring(0, 3);
            
            if (npmToJurusan[prefix]) {
                // Jika prefix valid, pilih jurusan yang sesuai
                jurusanSelect.value = npmToJurusan[prefix];
            } else {
                // Jika prefix TIDAK valid, kosongkan pilihan jurusan
                jurusanSelect.value = '';
            }
        } else {
            // Jika NPM kurang dari 3 digit, kosongkan pilihan jurusan
            jurusanSelect.value = '';
        }
    });
});
</script>

</body>
</html>