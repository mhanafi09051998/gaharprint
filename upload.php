<?php
// Include database and functions
require_once 'includes/db.php';
require_once 'includes/functions.php';

// --- Configuration ---
$upload_dir = 'uploads/';
$max_files = 1000;
$max_total_size_mb = 500;
$max_total_size_bytes = $max_total_size_mb * 1024 * 1024;

// --- Script Start ---
// Set default response
$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- 1. Input Validation ---
    $order_number = isset($_POST['order_number']) ? trim($_POST['order_number']) : '';
    $whatsapp_number = isset($_POST['whatsapp_number']) ? preg_replace('/[^0-9]/', '', $_POST['whatsapp_number']) : '';
    $files = isset($_FILES['files']) ? $_FILES['files'] : [];

    if (empty($order_number)) {
        $response['message'] = 'Nomor Pemesanan wajib diisi.';
        echo json_encode($response);
        exit;
    }

    if (empty($whatsapp_number) || strlen($whatsapp_number) < 9) {
        $response['message'] = 'Nomor WhatsApp tidak valid.';
        echo json_encode($response);
        exit;
    }

    if (empty($files['name'][0])) {
        $response['message'] = 'Anda harus memilih setidaknya satu file.';
        echo json_encode($response);
        exit;
    }

    // --- 2. File Validation ---
    $file_count = count($files['name']);
    $total_size = array_sum($files['size']);

    if ($file_count > $max_files) {
        $response['message'] = "Anda hanya dapat mengunggah maksimal {$max_files} file.";
        echo json_encode($response);
        exit;
    }

    if ($total_size > $max_total_size_bytes) {
        $response['message'] = "Ukuran total file tidak boleh melebihi {$max_total_size_mb} MB.";
        echo json_encode($response);
        exit;
    }

    // --- 3. Process Order ---
    $conn->begin_transaction(); // Start transaction for data integrity

    try {
        // Sanitize order number to be used as a directory name
        $safe_order_number = preg_replace('/[^a-zA-Z0-9_-]/', '_', $order_number);
        
        // Create a directory for the order using a combination of order number and a unique id
        // to prevent conflicts if the same order number is used twice.
        $order_upload_path = $upload_dir . $safe_order_number . '_' . time();
        if (!is_dir($order_upload_path) && !mkdir($order_upload_path, 0755, true)) {
            throw new Exception('Gagal membuat direktori untuk pesanan.');
        }

        // Insert order into the database
        $stmt = $conn->prepare("INSERT INTO orders (order_number, whatsapp_number, status) VALUES (?, ?, 'Menunggu Verifikasi')");
        $stmt->bind_param("ss", $order_number, $whatsapp_number);
        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan pesanan ke database.');
        }
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Process each file
        $file_insert_stmt = $conn->prepare("INSERT INTO uploaded_files (order_id, original_filename, stored_filename, file_size) VALUES (?, ?, ?, ?)");

        for ($i = 0; $i < $file_count; $i++) {
            $original_filename = basename($files['name'][$i]);
            $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);
            $stored_filename = uniqid('', true) . '.' . $file_extension;
            $destination = $order_upload_path . '/' . $stored_filename;
            $file_size = $files['size'][$i];

            if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
                // File moved successfully, insert into DB
                $file_insert_stmt->bind_param("issi", $order_id, $original_filename, $stored_filename, $file_size);
                if (!$file_insert_stmt->execute()) {
                    throw new Exception("Gagal menyimpan data file: {$original_filename}");
                }
            } else {
                throw new Exception("Gagal memindahkan file: {$original_filename}");
            }
        }
        $file_insert_stmt->close();

        // If all is well, commit the transaction
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'File berhasil diunggah!', 'order_number' => $order_number];

    } catch (Exception $e) {
        // An error occurred, roll back the transaction
        $conn->rollback();
        $response['message'] = $e->getMessage();
        // Optional: Clean up created directory if transaction fails
        if (isset($order_upload_path) && is_dir($order_upload_path)) {
            // Be careful with recursive deletion in production
            // array_map('unlink', glob("$order_upload_path/*.*"));
            // rmdir($order_upload_path);
        }
    }

    $conn->close();
}

// Redirect back to the main page with a status message
// This part is tricky without JS. A better approach is to use JS to handle the response.
// For now, we'll just echo the JSON and handle it on the client side.
header('Content-Type: application/json');
echo json_encode($response);
?>