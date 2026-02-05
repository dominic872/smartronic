<?php
// Direct connection to check databases
$conn = new mysqli('localhost', 'root', 'root');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== AVAILABLE DATABASES ===\n\n";
$result = $conn->query("SHOW DATABASES");
while ($row = $result->fetch_assoc()) {
    echo $row['Database'] . "\n";
}

$conn->close();
?>
