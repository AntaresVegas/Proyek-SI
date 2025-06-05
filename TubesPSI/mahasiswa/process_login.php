<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('../config/db_connection.php');

function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: ../index.php");
    exit();
}

if (!isset($_POST['email'], $_POST['password'], $_POST['user_type'])) {
    $_SESSION['error'] = "Semua field harus diisi";
    header("Location: ../index.php");
    exit();
}

$email = sanitize($_POST['email']);
$password = $_POST['password'];
$user_type = $_POST['user_type'];

if ($user_type !== 'mahasiswa') {
    $_SESSION['error'] = "Tipe pengguna tidak sesuai";
    header("Location: ../index.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid";
    header("Location: ../index.php");
    exit();
}

try {
    if (!$conn) {
        throw new Exception("Koneksi database gagal");
    }

    $stmt = $conn->prepare("SELECT mahasiswa_id, mahasiswa_nama, mahasiswa_email, mahasiswa_password FROM mahasiswa WHERE mahasiswa_email = ?");
    if (!$stmt) throw new Exception("Query error: " . $conn->error);

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }

    $row = $result->fetch_assoc();

    if (
        password_verify($password, $row['mahasiswa_password']) ||
        $password === $row['mahasiswa_password']
    ) {
        $_SESSION['user_id'] = $row['mahasiswa_id'];
        $_SESSION['username'] = $row['mahasiswa_email'];
        $_SESSION['nama'] = $row['mahasiswa_nama'];
        $_SESSION['user_type'] = 'mahasiswa';
        header("Location: mahasiswa_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Email atau password salah";
        header("Location: ../index.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: ../index.php");
    exit();
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
