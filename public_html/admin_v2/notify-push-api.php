<?php
$apiKey = "os_v2_app_4gmjwxup2rcuxcvvesjgt5mmihjss6senykecr46b44jojhcbbvhnf7ezelad7ru6ytbqonl73ksjaztkwibqlfnampiopvxrv4llly";
$appId = "e1989b5e-8fd4-454b-8ab5-249269f58c41";

// Get latest record
$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$dbname = 'u398852039_smartronic';

$conn = new mysqli("127.0.0.1:3306", "u398852039_smartronic", "Chennai@40!", "u398852039_smartronic");
$sql = "SELECT * FROM wp_cctv_requirements ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);
$latestRecord = $result->fetch_assoc();
$conn->close();

// Format notification
$message = [
    "app_id" => $appId,
    "contents" => ["en" => "New CCTV Order: " . $latestRecord['num_cameras'] . " cameras"],
    "included_segments" => ["All"]
];

// Send notification
$ch = curl_init("https://onesignal.com/api/v1/notifications");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $apiKey", "Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
