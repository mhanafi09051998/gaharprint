<?php
session_start();

// Security check: ensure the user is a logged-in admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die("Akses ditolak.");
}

require_once '../includes/db.php';

// Get the order ID from the query string
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("ID Pesanan tidak valid.");
}

// --- 1. Fetch file information from the database ---
$stmt = $conn->prepare("
    SELECT o.order_number, uf.stored_filename, uf.original_filename 
    FROM uploaded_files uf
    JOIN orders o ON uf.order_id = o.id
    WHERE uf.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$files_to_zip = [];
$order_number = '';
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (empty($order_number)) {
            $order_number = $row['order_number'];
        }
        $files_to_zip[] = $row;
    }
} else {
    die("Tidak ada file yang ditemukan untuk pesanan ini.");
}
$stmt->close();
$conn->close();

// --- 2. Create the ZIP archive ---
$zip = new ZipArchive();
$zip_filename = sys_get_temp_dir() . '/' . $order_number . '.zip'; // Create zip in temp directory

if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Tidak dapat membuat arsip ZIP.");
}

// Add files to the zip
$upload_dir = '../uploads/' . $order_number . '/';
foreach ($files_to_zip as $file) {
    $file_path = $upload_dir . $file['stored_filename'];
    if (file_exists($file_path)) {
        // Add file to the zip with its original name
        $zip->addFile($file_path, $file['original_filename']);
    }
}
$zip->close();

// --- 3. Send the ZIP file to the browser for download ---
if (file_exists($zip_filename)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zip_filename) . '"');
    header('Content-Length: ' . filesize($zip_filename));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zip_filename);
    
    // Clean up the temporary zip file
    unlink($zip_filename);
    
    exit;
} else {
    die("Gagal membuat file ZIP.");
}
?>