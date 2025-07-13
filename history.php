<?php
header('Content-Type: application/json');

// Include database connection
require_once 'includes/db.php';
require_once 'includes/functions.php'; // Include functions file

// Get WhatsApp number from the query string
$whatsapp_number = isset($_GET['whatsapp_number']) ? preg_replace('/[^0-9]/', '', $_GET['whatsapp_number']) : '';

if (empty($whatsapp_number) || strlen($whatsapp_number) < 9) {
    echo json_encode([]); // Return empty array if number is invalid
    exit;
}

// Prepare the SQL statement to prevent SQL injection
// We join the orders table with a subquery that counts files per order
$sql = "
    SELECT 
        o.order_number,
        o.whatsapp_number,
        o.upload_date,
        o.status,
        COUNT(uf.id) AS file_count
    FROM 
        orders o
    LEFT JOIN 
        uploaded_files uf ON o.id = uf.order_id
    WHERE 
        o.whatsapp_number = ?
    GROUP BY
        o.id
    ORDER BY 
        o.upload_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $whatsapp_number);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Censor the numbers for privacy
        $row['order_number'] = censor_string($row['order_number']);
        $row['whatsapp_number'] = censor_string($row['whatsapp_number']);
        
        // Format the date for better readability
        $row['upload_date'] = date("d M Y, H:i", strtotime($row['upload_date']));
        $history[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode($history);
?>