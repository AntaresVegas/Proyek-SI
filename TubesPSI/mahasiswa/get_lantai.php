<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// 1. Verifikasi Path Koneksi Database
$db_connection_file = __DIR__ . '/../config/db_connection.php';

if (!file_exists($db_connection_file)) {
    echo json_encode(['error' => 'File koneksi database tidak ditemukan. Path: ' . $db_connection_file]);
    exit();
}

include $db_connection_file;

// 2. Cek Apakah Koneksi Berhasil
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['error' => 'Gagal terhubung ke database: ' . ($conn->connect_error ?? 'Variabel koneksi tidak didefinisikan.')]);
    exit();
}

// Cek apakah parameter gedung_ids ada dan merupakan array
if (!isset($_GET['gedung_ids']) || !is_array($_GET['gedung_ids'])) {
    echo json_encode([]); // Kembalikan array kosong jika tidak ada input
    exit();
}

$gedung_ids = $_GET['gedung_ids'];
if (empty($gedung_ids)) {
    echo json_encode([]); // Kembalikan array kosong jika array input kosong
    exit();
}

$placeholders = implode(',', array_fill(0, count($gedung_ids), '?'));
$types = str_repeat('i', count($gedung_ids));

// KODE SQL DIPERBAIKI: Mengurutkan berdasarkan angka gedung, lalu nomor lantai
$sql = "
    SELECT l.lantai_id, l.lantai_nomor, g.gedung_nama 
    FROM lantai l
    JOIN gedung g ON l.gedung_id = g.gedung_id
    WHERE l.gedung_id IN ($placeholders) 
    ORDER BY CAST(SUBSTRING(g.gedung_nama, 7) AS UNSIGNED) ASC, l.lantai_nomor ASC
";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['error' => 'Gagal menyiapkan query: ' . $conn->error]);
    exit();
}

$stmt->bind_param($types, ...$gedung_ids);
$stmt->execute();
$result = $stmt->get_result();

$lantai_options = [];
while ($row = $result->fetch_assoc()) {
    $lantai_options[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($lantai_options);
?>