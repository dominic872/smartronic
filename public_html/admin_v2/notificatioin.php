<?php

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$dbname = 'u398852039_smartronic';


// Create connection
$conn = new mysqli($host , $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch unprocessed entries from api_trigger_log
$sql = "SELECT * FROM api_trigger_log WHERE table_name = 'wp_cctv_requirements' ORDER BY created_at ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rowId = $row["row_id"];

        // Fetch actual row data from wp_cctv_requirements
        $dataSql = "SELECT * FROM wp_cctv_requirements WHERE id = $rowId";
        $dataResult = $conn->query($dataSql);
        $rowData = $dataResult->fetch_assoc();

        // Convert row data to JSON
        $jsonData = json_encode($rowData);

        // Send API request
        $apiUrl = "https://smartronic.online/admin/api.php";
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        // Remove processed row from api_trigger_log
        $deleteSql = "DELETE FROM api_trigger_log WHERE id = " . $row["id"];
        $conn->query($deleteSql);
    }
}

$conn->close();
?>