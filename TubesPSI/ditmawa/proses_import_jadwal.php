<?php
session_start();
require_once('../config/db_connection.php'); // Sesuaikan path
require_once(__DIR__ . '/../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory; // <-- APAKAH BARIS INI ADA?
use PhpOffice\PhpSpreadsheet\Shared\Date; // <-- Tambahkan ini juga untuk konversi tanggal/waktu Excel

// Autentikasi Ditmawa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'ditmawa') {
    $_SESSION['import_message'] = "Akses ditolak.";
    $_SESSION['import_message_type'] = 'error';
    header("Location: ditmawa_import_jadwal.php");
    exit();
}

$successCount = 0;
$errorCount = 0;
$skippedCount = 0;
$processedRows = 0;
$errors = [];

if (isset($_FILES['fileJadwal']) && $_FILES['fileJadwal']['error'] == UPLOAD_ERR_OK && isset($_POST['semester_tahun'])) {
    $fileTmpPath = $_FILES['fileJadwal']['tmp_name'];
    $fileName = $_FILES['fileJadwal']['name'];
    $fileSize = $_FILES['fileJadwal']['size'];
    $fileType = $_FILES['fileJadwal']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    $semester_tahun = trim($_POST['semester_tahun']);
    $hapus_jadwal_lama = isset($_POST['hapus_jadwal_lama']) && $_POST['hapus_jadwal_lama'] == '1';

    $allowedfileExtensions = ['xlsx', 'xls'];
    if (in_array($fileExtension, $allowedfileExtensions) && !empty($semester_tahun)) {
        
        $conn->begin_transaction();
        try {
            // Hapus jadwal lama jika dicentang
            if ($hapus_jadwal_lama) {
                $delete_stmt = $conn->prepare("DELETE FROM jadwal_kelas WHERE semester_tahun = ?");
                if (!$delete_stmt) throw new Exception("Gagal prepare statement hapus: " . $conn->error);
                $delete_stmt->bind_param("s", $semester_tahun);
                $delete_stmt->execute();
                $delete_stmt->close();
            }

            // Siapkan statement insert
            $insert_stmt = $conn->prepare("
                INSERT INTO jadwal_kelas (ruangan_id, hari, jam_mulai, jam_selesai, nama_matakuliah, semester_tahun) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$insert_stmt) throw new Exception("Gagal prepare statement insert: " . $conn->error);

            // Siapkan statement untuk mencari ruangan_id
            $ruangan_stmt = $conn->prepare("SELECT ruangan_id FROM ruangan WHERE LOWER(ruangan_nama) = LOWER(?)");
            if (!$ruangan_stmt) throw new Exception("Gagal prepare statement ruangan: " . $conn->error);

            // Load spreadsheet
            $spreadsheet = IOFactory::load($fileTmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            // Loop mulai dari baris kedua (asumsi baris 1 header)
            for ($row = 2; $row <= $highestRow; $row++) {
                $processedRows++;
                $ruangan_nama = trim($sheet->getCell('A' . $row)->getValue()); // Kolom A: Nama Ruangan
                $hari = trim($sheet->getCell('B' . $row)->getValue());         // Kolom B: Hari
                $jam_mulai_raw = $sheet->getCell('C' . $row)->getValue();      // Kolom C: Jam Mulai
                $jam_selesai_raw = $sheet->getCell('D' . $row)->getValue();    // Kolom D: Jam Selesai
                $matakuliah = trim($sheet->getCell('E' . $row)->getValue());   // Kolom E: Nama Matakuliah (Opsional)

                // Validasi dasar
                if (empty($ruangan_nama) || empty($hari) || empty($jam_mulai_raw) || empty($jam_selesai_raw)) {
                    $skippedCount++;
                    $errors[] = "Baris $row: Data tidak lengkap (Ruangan, Hari, Jam Mulai/Selesai wajib diisi).";
                    continue;
                }

                // Cari ruangan_id
                $ruangan_nama_lower = strtolower($ruangan_nama);
                $ruangan_stmt->bind_param("s", $ruangan_nama_lower);
                $ruangan_stmt->execute();
                $ruangan_result = $ruangan_stmt->get_result();
                if ($ruangan_row = $ruangan_result->fetch_assoc()) {
                    $ruangan_id = $ruangan_row['ruangan_id'];
                } else {
                    $skippedCount++;
                    $errors[] = "Baris $row: Ruangan '$ruangan_nama' tidak ditemukan di database.";
                    continue;
                }

                // Validasi dan format Hari
                $valid_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $hari_formatted = ucfirst(strtolower($hari)); // Coba format
                if (!in_array($hari_formatted, $valid_hari)) {
                     $skippedCount++;
                    $errors[] = "Baris $row: Hari '$hari' tidak valid. Gunakan: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu.";
                    continue;
                }
                
                // Validasi dan format Jam (handle format Excel time)
                try {
                    if (is_numeric($jam_mulai_raw)) {
                        $jam_mulai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_mulai_raw)->format('H:i:s');
                    } else { // Coba parse sebagai string HH:MM
                        $time = strtotime($jam_mulai_raw);
                        if ($time === false) throw new Exception("Format jam mulai tidak valid");
                        $jam_mulai = date('H:i:s', $time);
                    }

                     if (is_numeric($jam_selesai_raw)) {
                        $jam_selesai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_selesai_raw)->format('H:i:s');
                    } else {
                        $time = strtotime($jam_selesai_raw);
                         if ($time === false) throw new Exception("Format jam selesai tidak valid");
                        $jam_selesai = date('H:i:s', $time);
                    }
                     // Validasi jam selesai > jam mulai
                    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) {
                         throw new Exception("Jam Selesai harus setelah Jam Mulai.");
                    }

                } catch (Exception $timeEx) {
                     $skippedCount++;
                    $errors[] = "Baris $row: Format Jam tidak valid ('$jam_mulai_raw' / '$jam_selesai_raw'). Gunakan HH:MM. Error: " . $timeEx->getMessage();
                    continue;
                }

                // Bind parameter dan eksekusi insert
                $insert_stmt->bind_param("isssss", 
                    $ruangan_id, 
                    $hari_formatted, 
                    $jam_mulai, 
                    $jam_selesai, 
                    $matakuliah, 
                    $semester_tahun
                );
                
                if ($insert_stmt->execute()) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Baris $row: Gagal insert ke database - " . $insert_stmt->error;
                }
            }
            
            $ruangan_stmt->close();
            $insert_stmt->close();
            $conn->commit();

            $_SESSION['import_message'] = "Import selesai. Total baris diproses: $processedRows. Sukses: $successCount, Gagal Insert: $errorCount, Dilewati: $skippedCount.";
            if (!empty($errors)) {
                $_SESSION['import_message'] .= "<br>Detail Error/Skipped:<br>" . implode("<br>", $errors);
            }
             $_SESSION['import_message_type'] = ($errorCount > 0 || $skippedCount > 0) ? 'error' : 'success'; // Tampilkan error jika ada yg gagal/skipped

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['import_message'] = "Terjadi kesalahan fatal saat import: " . $e->getMessage();
            $_SESSION['import_message_type'] = 'error';
        }

    } else {
         $_SESSION['import_message'] = "Upload gagal atau tipe file tidak valid (.xlsx/.xls) atau Semester/Tahun Ajaran kosong.";
         $_SESSION['import_message_type'] = 'error';
    }
} else {
    $_SESSION['import_message'] = "Tidak ada file yang diunggah atau data form tidak lengkap.";
    $_SESSION['import_message_type'] = 'error';
}

$conn->close();
header("Location: ditmawa_import_jadwal.php");
exit();
?>