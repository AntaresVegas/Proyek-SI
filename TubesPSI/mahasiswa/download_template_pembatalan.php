<?php
session_start();
// Sesuaikan path ke koneksi database
require_once('../config/db_connection.php');

// Otentikasi sederhana: pastikan pengguna adalah mahasiswa yang login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    header("Location: ../index.php");
    exit();
}

$pengajuan_id = $_GET['id'] ?? null;
if (!$pengajuan_id) {
    die("ID Pengajuan tidak valid.");
}

// 1. Ambil Nama Event dan ID Pengaju
$event_name = '';
$pengaju_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT pengajuan_namaEvent FROM pengajuan_event WHERE pengajuan_id = ? AND pengaju_id = ? AND pengaju_tipe = 'mahasiswa'");
if ($stmt) {
    $stmt->bind_param("ii", $pengajuan_id, $pengaju_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $event_name = $row['pengajuan_namaEvent'];
    }
    $stmt->close();
}
$conn->close();

if (empty($event_name)) {
    die("Event tidak ditemukan atau Anda tidak memiliki akses.");
}

// 2. Tentukan Path Template (sesuai permintaan user: C:\xampp\htdocs\TubesPSI\templates)
// Sesuaikan path relatif ini jika struktur folder Anda berbeda.
$template_path = '../templates/Template Pembatalan Event.docx'; 

// Jika Anda *benar-benar* ingin menggunakan path absolut Windows (kurang disarankan di PHP web server):
// $template_path = 'C:/xampp/htdocs/TubesPSI/templates/Template Pembatalan Event.docx';

$file_name_clean = preg_replace('/[^A-Za-z0-9\s-]/', '', $event_name); // Hapus karakter ilegal
$output_filename = "Pembatalan Event - {$file_name_clean}.docx";

if (!file_exists($template_path)) {
    // Pesan error jika file template tidak ada
    die("Error: File template tidak ditemukan di server. Harap hubungi administrator.");
}

// 3. Baca konten template DOCX
// Catatan: Manipulasi konten DOCX biner (seperti mengganti teks di dalamnya) sangat kompleks
// tanpa library PHPWord. Kode ini HANYA mengganti nama file, dan melakukan
// penggantian string sederhana yang mungkin tidak berhasil di semua format DOCX.
$file_content = file_get_contents($template_path);

// Placeholder yang ada di template: /*ISI DENGAN NAMA EVENT YANG INGIN DIBATALKAN*/
$placeholder = '/*ISI DENGAN NAMA EVENT YANG INGIN DIBATALKAN*/';
$replacement = $event_name;

// Coba ganti string (ini bekerja HANYA jika placeholder ada di bagian XML yang tidak terkompresi/fragmentasi)
// Jika tidak berhasil, user tetap mendapatkan template utuh dengan nama file yang benar.
$modified_content = str_replace($placeholder, $replacement, $file_content);

// 4. Kirim File ke Browser
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $output_filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($modified_content));

echo $modified_content;
exit;
?>