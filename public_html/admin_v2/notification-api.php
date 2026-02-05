<?php
header('Content-Type: application/json');

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$dbname = 'u398852039_smartronic';


// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Fetch the latest record
$sql = "SELECT * FROM wp_cctv_requirements ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $latestRecord = $result->fetch_assoc();
    echo json_encode($latestRecord);
} else {
    echo json_encode(["message" => "No records found"]);
}

$conn->close();
?>
