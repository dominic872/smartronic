<?php
require_once('../../config.php');

// Get table structure
$result = $conn->query("DESCRIBE leads");
echo "=== LEADS TABLE STRUCTURE ===\n\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-20s %-15s %-10s %-10s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key']
    );
}

echo "\n\n=== SAMPLE DATA (1 record) ===\n\n";
$result = $conn->query("SELECT * FROM leads LIMIT 1");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    foreach ($row as $key => $value) {
        echo sprintf("%-20s: %s\n", $key, $value);
    }
} else {
    echo "No records found\n";
}

echo "\n\n=== TOTAL RECORDS ===\n";
$result = $conn->query("SELECT COUNT(*) as total FROM leads");
$row = $result->fetch_assoc();
echo "Total leads: " . $row['total'] . "\n";

$conn->close();
?>
