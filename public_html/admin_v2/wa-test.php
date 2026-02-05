<?php

$apiUrl = "https://backend.aisensy.com/campaign/t1/api/v2";

$data = [
    "apiKey" => "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4NGMzODM4YTY1YTQ3MzIyYWRhMjdmMyIsIm5hbWUiOiJBdmUiLCJhcHBOYW1lIjoiQWlTZW5zeSIsImNsaWVudElkIjoiNjg0YzM4MzhhNjVhNDczMjJhZGEyN2VlIiwiYWN0aXZlUGxhbiI6IkZSRUVfRk9SRVZFUiIsImlhdCI6MTc0OTgyNTU5Mn0.xYucagexsW3hc4S0_-yd6_0e2mmvd3-cQXby-v8qyyQ",
    "campaignName" => "camp2",
    "destination" => "918884831000", // 91 + phone number (India)
    "userName" => "Ave",
    "templateParams" => [
        "HIKVISION DVR 123 Full HD 108-Channel | 5 MP X 8 CAM | ₹30,974.00",
        "10MP",
        "100GB",
        "Rs 10.00",
        "20% Discount",
        "ref code here"
    ],
    "source" => "new-landing-page form",
    "media" => new stdClass(),
    "buttons" => [],
    "carouselCards" => [],
    "location" => new stdClass(),
    "attributes" => new stdClass(),
    "paramsFallbackValue" => [
        "FirstName" => "user"
    ]
];

// Initialize CURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Output response
echo "HTTP Code: $httpCode\n";
if ($curlError) {
    echo "cURL Error: $curlError\n";
}
echo "Response: $response\n";
