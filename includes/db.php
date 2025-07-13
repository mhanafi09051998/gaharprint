<?php
// Database configuration
define('DB_HOST', 'db');
define('DB_USERNAME', 'root'); // Replace with your database username
define('DB_PASSWORD', value: 'lsu2zms6vmaqfnnljxoiwxfj5ini5gs0');     // Replace with your database password
define('DB_NAME', 'gaharprint'); // Replace with your database name

// Create a new MySQLi object
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
    // Stop execution and display an error message if the connection fails
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");
?>
