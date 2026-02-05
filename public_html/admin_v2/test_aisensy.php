<?php

// API URL
$url = "https://backend.aisensy.com/campaign/t1/api/v2";

// API Key
$apiKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjY4NGMzODM4YTY1YTQ3MzIyYWRhMjdmMyIsIm5hbWUiOiJBdmUiLCJhcHBOYW1lIjoiQWlTZW5zeSIsImNsaWVudElkIjoiNjg0YzM4MzhhNjVhNDczMjJhZGEyN2VlIiwiYWN0aXZlUGxhbiI6IkZSRUVfRk9SRVZFUiIsImlhdCI6MTc0OTgyNTU5Mn0.xYucagexsW3hc4S0_-yd6_0e2mmvd3-cQXby-v8qyyQ";

// Prepare data
$data = [
    "apiKey" => $apiKey,
    "campaignName" => "camp4",
    "destination" => "+918884831000",
    "userName" => "Dominic sagayraj", 
    "source" => "website",
    "media" => [
        "url" => "https://smartronic.online/invoice/20offer_1.png",
        "filename" => "20_offer_1.png"
    ],
    "templateParams" => [
        "Dominic 1-XYZ", "Quotation #12345", "₹12999,500", "2 days delivery", "this is just a test", "Nothing 2 more"
    ],
    "tags" => [ 
        "lead", "quotation"
    ],
    "attributes" => [
        "attribute_name" => "test_value"
    ]
];

// Encode data to JSON
$jsonData = json_encode($data);

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo "Response:\n$response";
}

// Close cURL
curl_close($ch);

?>
