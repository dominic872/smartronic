<?php
header('Content-Type: application/json');

echo json_encode([
    "success" => true,
    "name" => "John Smith",
    "phone" => "9876543210",
    "details" => "4 Cameras | 1TB HDD | Installed 20 days ago",
    "message" => "Hello from PHP!"
]);