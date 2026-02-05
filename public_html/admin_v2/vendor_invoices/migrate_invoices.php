<?php
require_once '../config.php';

// Check if 'invoices' table exists
$result = $conn->query("SHOW TABLES LIKE 'invoices'");

if ($result && $result->num_rows > 0) {
    echo "Found legacy 'invoices' table. Attempting to rename to 'vendor_invoices'...\n";
    
    // Check if vendor_invoices already exists
    $result2 = $conn->query("SHOW TABLES LIKE 'vendor_invoices'");
    if ($result2 && $result2->num_rows > 0) {
        echo "Error: 'vendor_invoices' already exists. Manual merge might be required.\n";
    } else {
        if ($conn->query("RENAME TABLE invoices TO vendor_invoices")) {
            echo "Success: 'invoices' renamed to 'vendor_invoices'.\n";
        } else {
            echo "Error renaming table: " . $conn->error . "\n";
        }
    }
} else {
    echo "No legacy 'invoices' table found.\n";
}

// Also ensure the structure is correct (using the setup logic)
include 'invoices_db_setup.php';

$conn->close();
?>
