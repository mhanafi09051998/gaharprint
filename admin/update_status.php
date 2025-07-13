<?php
session_start();

// Security check: ensure the user is a logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    echo "Akses ditolak.";
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo "Metode tidak diizinkan.";
    exit;
}

require_once '../includes/db.php';

// Get input from the POST request
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$new_status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate the status to ensure it's one of the allowed values
$allowed_statuses = ['Menunggu Verifikasi', 'Sudah Dicetak', 'Dibatalkan'];
if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
    // Prepare and execute the update statement
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Success
        header("Location: index.php"); // Redirect back to the dashboard
        exit;
    } else {
        // Failure
        echo "Gagal memperbarui status. Silakan coba lagi.";
    }
    
    $stmt->close();
} else {
    // Invalid input
    echo "Data tidak valid.";
}

$conn->close();
?>