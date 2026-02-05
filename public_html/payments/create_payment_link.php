<?php

$KEY_ID = "rzp_live_S9ixiAt25pt68C";
$KEY_SECRET = "tIKbWpdvfgrqJ4xDhedzt3ZE";

$data = json_decode(file_get_contents("php://input"), true);

$name = $data["name"];
$phone = $data["phone"];
$amount = $data["amount"] * 100;
$description = $data["description"];

// Unique reference for tracking
$reference_id = "ORDER_" . time();

$url = "https://api.razorpay.com/v1/payment_links";

$payload = [
  "amount" => $amount,
  "currency" => "INR",
  "description" => $description,
  "reference_id" => $reference_id,
  "customer" => [
    "name" => $name,
    "contact" => $phone
  ],
  "notify" => [
    "sms" => true,
    "email" => false
  ],
  "reminder_enable" => true
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_USERPWD, $KEY_ID . ":" . $KEY_SECRET);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status == 200) {
  $result = json_decode($response, true);
  echo json_encode([
    "link" => $result["short_url"],
    "reference_id" => $reference_id
  ]);
} else {
  echo json_encode([
    "error" => "Failed",
    "details" => $response
  ]);
}
