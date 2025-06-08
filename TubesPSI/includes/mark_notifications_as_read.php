<?php
session_start();
// Sesuaikan path ke file koneksi database Anda
// __DIR__ adalah direktori dari file ini (includes/), lalu naik 1 level (ke your_project/), lalu masuk ke config/
require_once(__DIR__ . '/../config/db_connection.php');

header('Content-Type: application/json');

$response = ['success' => false];

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($conn) && $conn->ping()) {
        // Update semua notifikasi yang belum dibaca untuk user ini menjadi sudah dibaca
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                $response['success'] = true;
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement for marking notifications as read: " . $conn->error);
        }
    } else {
        error_log("Database connection not established or lost in mark_notifications_as_read.php");
    }
} catch (Exception $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>