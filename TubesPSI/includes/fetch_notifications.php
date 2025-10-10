<?php
session_start();
// Sesuaikan path ke file koneksi database Anda
// __DIR__ adalah direktori dari file ini (includes/), lalu naik 1 level (ke your_project/), lalu masuk ke config/
require_once(__DIR__ . '/../config/db_connection.php');

header('Content-Type: application/json');

$response = ['notifications' => [], 'unread_count' => 0];

// Pastikan user sudah login dan tipenya mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'mahasiswa') {
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($conn) && $conn->ping()) {
        // Ambil notifikasi untuk user_id yang login, diurutkan terbaru, limit 10
        $stmt = $conn->prepare("SELECT message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                // Hitung "waktu yang lalu"
                $datetime = new DateTime($row['created_at']);
                $now = new DateTime();
                $interval = $now->diff($datetime);

                $time_ago = '';
                if ($interval->y >= 1) {
                    $time_ago = $interval->y . ' tahun yang lalu';
                } elseif ($interval->m >= 1) {
                    $time_ago = $interval->m . ' bulan yang lalu';
                } elseif ($interval->d >= 1) {
                    $time_ago = $interval->d . ' hari yang lalu';
                } elseif ($interval->h >= 1) {
                    $time_ago = $interval->h . ' jam yang lalu';
                } elseif ($interval->i >= 1) {
                    $time_ago = $interval->i . ' menit yang lalu';
                } else {
                    $time_ago = 'Baru saja';
                }

                $response['notifications'][] = [
                    'message' => htmlspecialchars($row['message']),
                    'link' => 'mahasiswa_history.php', // Diubah agar selalu mengarah ke history
                    'is_read' => (bool)$row['is_read'],
                    'time_ago' => $time_ago
                ];
            }
            $stmt->close();

            // Hitung jumlah notifikasi yang belum dibaca
            $stmt_unread = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
            if ($stmt_unread) {
                $stmt_unread->bind_param("i", $user_id);
                $stmt_unread->execute();
                $result_unread = $stmt_unread->get_result();
                $unread_row = $result_unread->fetch_assoc();
                $response['unread_count'] = $unread_row['unread_count'];
                $stmt_unread->close();
            }
        } else {
            error_log("Failed to prepare statement for fetching notifications: " . $conn->error);
        }
    } else {
        error_log("Database connection not established or lost in fetch_notifications.php");
    }
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

echo json_encode($response);
?>