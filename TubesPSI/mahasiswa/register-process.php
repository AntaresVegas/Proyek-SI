<?php
session_start();
require_once('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fungsi sanitize harus kamu definisikan sendiri jika belum ada
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    // Sanitize user input
    $nama = sanitize($_POST['mahasiswa_nama']);
    $npm = sanitize($_POST['mahasiswa_npm']);
    $email = sanitize($_POST['mahasiswa_email']);
    $jurusan = sanitize($_POST['mahasiswa_jurusan']);
    $password = hash('sha256', $_POST['mahasiswa_password']);
    
    // Database connection
    $conn = connectDB();
    
    // Check if NPM (as username) already exists
    $checkNpm = "SELECT mahasiswa_npm FROM Mahasiswa WHERE mahasiswa_npm = '$npm'";
    $result = sqlsrv_query($conn, $checkNpm);
    
    if(sqlsrv_has_rows($result)) {
        $_SESSION['error'] = "NPM sudah digunakan. Silakan gunakan NPM lain.";
        header("Location: register.php");
        exit();
    }
    
    // Insert new mahasiswa
    $sql = "INSERT INTO Mahasiswa (mahasiswa_nama, mahasiswa_npm, mahasiswa_email, mahasiswa_jurusan, mahasiswa_password) 
            VALUES ('$nama', '$npm', '$email', '$jurusan', '$password')";
    
    $result = sqlsrv_query($conn, $sql);
    
    if($result === false) {
        $_SESSION['error'] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        header("Location: register.php");
        exit();
    }
    
    $_SESSION['success'] = "Pendaftaran berhasil! Silakan login.";
    header("Location: login.php");
    exit();
} else {
    header("Location: register.php");
    exit();
}
?>
