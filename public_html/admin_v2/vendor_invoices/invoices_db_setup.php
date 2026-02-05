<?php
require_once '../config.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create table
$sql = "CREATE TABLE IF NOT EXISTS vendor_invoices (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) DEFAULT NULL,
    invoice_date DATE DEFAULT NULL,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    line_items LONGTEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'vendor_invoices' created successfully (or already exists).";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
