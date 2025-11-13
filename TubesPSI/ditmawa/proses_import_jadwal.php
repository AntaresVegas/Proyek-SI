<?php
session_start();
require_once('../config/db_connection.php'); // Sesuaikan path
require_once(__DIR__ . '/../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; 

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
$redirect_url = "ditmawa_import_jadwal.php"; // Default redirect jika gagal

if (isset($_FILES['fileJadwal']) && $_FILES['fileJadwal']['error'] == UPLOAD_ERR_OK && isset($_POST['semester_tahun']) && isset($_POST['jurusan_fakultas'])) {
    $fileTmpPath = $_FILES['fileJadwal']['tmp_name'];
    $fileName = $_FILES['fileJadwal']['name'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    $semester_tahun = trim($_POST['semester_tahun']);
    $jurusan_fakultas = trim($_POST['jurusan_fakultas']); 
    $ditmawa_id = $_SESSION['user_id']; 
    $hapus_jadwal_lama = isset($_POST['hapus_jadwal_lama']) && $_POST['hapus_jadwal_lama'] == '1';

    $allowedfileExtensions = ['xlsx', 'xls'];
    
    if (in_array($fileExtension, $allowedfileExtensions) && !empty($semester_tahun) && !empty($jurusan_fakultas)) {
        
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
                $ruangan_nama = trim($sheet->getCell('A' . $row)->getValue());
                $hari = trim($sheet->getCell('B' . $row)->getValue());
                $jam_mulai_raw = $sheet->getCell('C' . $row)->getValue();
                $jam_selesai_raw = $sheet->getCell('D' . $row)->getValue();
                $matakuliah = trim($sheet->getCell('E' . $row)->getValue());

                if (empty($ruangan_nama) || empty($hari) || empty($jam_mulai_raw) || empty($jam_selesai_raw)) {
                    $skippedCount++;
                    $errors[] = "Baris $row: Data tidak lengkap (Ruangan, Hari, Jam Mulai/Selesai wajib diisi).";
                    continue;
                }

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

                $valid_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $hari_formatted = ucfirst(strtolower($hari));
                if (!in_array($hari_formatted, $valid_hari)) {
                     $skippedCount++;
                    $errors[] = "Baris $row: Hari '$hari' tidak valid. Gunakan: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu.";
                    continue;
                }
                
                try {
                    if (is_numeric($jam_mulai_raw)) $jam_mulai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_mulai_raw)->format('H:i:s');
                    else {
                        $time = strtotime($jam_mulai_raw);
                        if ($time === false) throw new Exception("Format jam mulai tidak valid");
                        $jam_mulai = date('H:i:s', $time);
                    }
                     if (is_numeric($jam_selesai_raw)) $jam_selesai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($jam_selesai_raw)->format('H:i:s');
                    else {
                        $time = strtotime($jam_selesai_raw);
                         if ($time === false) throw new Exception("Format jam selesai tidak valid");
                        $jam_selesai = date('H:i:s', $time);
                    }
                    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) throw new Exception("Jam Selesai harus setelah Jam Mulai.");
                } catch (Exception $timeEx) {
                     $skippedCount++;
                    $errors[] = "Baris $row: Format Jam tidak valid ('$jam_mulai_raw' / '$jam_selesai_raw'). Gunakan HH:MM. Error: " . $timeEx->getMessage();
                    continue;
                }

                $insert_stmt->bind_param("isssss", $ruangan_id, $hari_formatted, $jam_mulai, $jam_selesai, $matakuliah, $semester_tahun);
                
                if ($insert_stmt->execute()) $successCount++;
                else {
                    $errorCount++;
                    $errors[] = "Baris $row: Gagal insert ke database - " . $insert_stmt->error;
                }
            }
            
            $ruangan_stmt->close();
            $insert_stmt->close();

            // Simpan ke tabel log SEBELUM commit
            if ($processedRows > 0) {
                $log_stmt = $conn->prepare(
                    "INSERT INTO log_import_jadwal (semester_tahun, jurusan_fakultas, nama_file, diupload_oleh_id) 
                     VALUES (?, ?, ?, ?)"
                );
                if (!$log_stmt) throw new Exception("Gagal prepare statement log: " . $conn->error);
                $log_stmt->bind_param("sssi", $semester_tahun, $jurusan_fakultas, $fileName, $ditmawa_id);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            $conn->commit();

            $_SESSION['import_message'] = "Import selesai. Total baris diproses: $processedRows. Sukses: $successCount, Gagal Insert: $errorCount, Dilewati: $skippedCount.";
            if (!empty($errors)) {
                $display_errors = array_slice($errors, 0, 5);
                $_SESSION['import_message'] .= "<br>Detail Error/Skipped (maks 5 ditampilkan):<br>" . implode("<br>", $display_errors);
                if (count($errors) > 5) $_SESSION['import_message'] .= "<br>...dan " . (count($errors) - 5) . " error lainnya.";
            }
            
            if ($errorCount > 0 || $skippedCount > 0) {
                 $_SESSION['import_message_type'] = 'error';
                 // Biarkan $redirect_url sebagai default (kembali ke tab import)
            } else {
                 $_SESSION['import_message_type'] = 'success';
                 // [DIUBAH] Jika sukses, redirect ke tab history halaman 1
                 $redirect_url = "ditmawa_import_jadwal.php?tab=history&page=1";
            }

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['import_message'] = "Terjadi kesalahan fatal saat import: " . $e->getMessage();
            $_SESSION['import_message_type'] = 'error';
        }

    } else {
         $_SESSION['import_message'] = "Upload gagal. Pastikan tipe file (.xlsx/.xls) dan semua form (Semester & Jurusan) terisi.";
         $_SESSION['import_message_type'] = 'error';
    }
} else {
    $_SESSION['import_message'] = "Tidak ada file yang diunggah atau data form tidak lengkap.";
    $_SESSION['import_message_type'] = 'error';
}

$conn->close();
header("Location: $redirect_url"); // Redirect ke URL yang sudah ditentukan
exit();
?>