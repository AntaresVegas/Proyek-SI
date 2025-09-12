<?php
session_start();

// Buat string acak untuk CAPTCHA
$karakter = 'abcdefghijklmnopqrstuvwxyz0123456789';
$captcha_string = '';
for ($i = 0; $i < 5; $i++) {
    $captcha_string .= $karakter[rand(0, strlen($karakter) - 1)];
}

// Simpan string di session untuk validasi nanti
$_SESSION['captcha_text'] = $captcha_string;

// ======================================================
// ## PERUBAHAN: Ukuran gambar diperbesar ##
// ======================================================
$lebar_gambar = 200;
$tinggi_gambar = 70;
$gambar = imagecreatetruecolor($lebar_gambar, $tinggi_gambar);

// Beri warna background, text, dan noise
$warna_background = imagecolorallocate($gambar, 240, 240, 240); // Abu-abu muda
$warna_text = imagecolorallocate($gambar, 44, 62, 80);      // Biru tua
$warna_noise = imagecolorallocate($gambar, 189, 195, 199);  // Abu-abu

// Isi background
imagefilledrectangle($gambar, 0, 0, $lebar_gambar, $tinggi_gambar, $warna_background);

// Tambahkan beberapa garis noise acak
for ($i = 0; $i < 7; $i++) { // Jumlah garis noise bisa ditambah
    imageline($gambar, 0, rand() % $tinggi_gambar, $lebar_gambar, rand() % $tinggi_gambar, $warna_noise);
}

// ======================================================
// ## PERUBAHAN: Posisi teks disesuaikan agar tetap di tengah ##
// ======================================================
// Hitung posisi x agar teks berada di tengah
$font_lebar = imagefontwidth(5);
$teks_lebar = $font_lebar * strlen($captcha_string);
$posisi_x = ($lebar_gambar - $teks_lebar) / 2;

// Hitung posisi y agar teks berada di tengah
$font_tinggi = imagefontheight(5);
$posisi_y = ($tinggi_gambar - $font_tinggi) / 2;

// Tambahkan string CAPTCHA ke gambar
imagestring($gambar, 5, $posisi_x, $posisi_y, $captcha_string, $warna_text);
// ======================================================

// Output gambar sebagai PNG
header('Content-type: image/png');
imagepng($gambar);

// Hancurkan gambar dari memori
imagedestroy($gambar);
?>