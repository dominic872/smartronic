<?php
// Debugging on (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Your Numverify / apilayer API key (you provided)
$access_key = 'd91487ee9f9f7e167e65a3207a0c05f7';

// Get phone number from query parameter
$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

if ($phone === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a phone number using ?phone=XXXXXXXXXX']);
    exit;
}

// Basic sanitization: keep plus and digits only
$cleanPhone = preg_replace('/[^\d+]/', '', $phone);

// Build request URL (http for free tier; use https if available for your account)
$apiUrl = 'http://apilayer.net/api/validate';
$query = http_build_query([
    'access_key' => $access_key,
    'number'     => $cleanPhone,
    'country_code' => '',   // optional: e.g. 'IN' — leave empty to auto-detect
    'format'     => 1
]);

$url = $apiUrl . '?' . $query;

// Use cURL with timeout and error handling
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'cURL error', 'details' => $curlErr]);
    exit;
}

// Parse API response
$decoded = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from API', 'raw' => $response]);
    exit;
}

// If the API itself returned an error structure
if (isset($decoded['success']) && $decoded['success'] === false && isset($decoded['error'])) {
    http_response_code(502);
    echo json_encode(['error' => 'API error', 'api_error' => $decoded['error']]);
    exit;
}

// Build output: include raw API data and a simplified summary
$output = [
    'queried_phone' => $phone,
    'clean_phone' => $cleanPhone,
    'http_code' => $httpCode,
    'api_response' => $decoded
];

// Numverify usually returns fields like: valid, country_code, country_name, location, carrier, line_type
// Note: Numverify does NOT guarantee a "name" field for most numbers/countries.
// If your provider returns a name key, it will appear under api_response.
if (!empty($decoded['valid'])) {
    $output['summary'] = [
        'valid' => $decoded['valid'],
        'country' => $decoded['country_name'] ?? null,
        'country_code' => $decoded['country_code'] ?? null,
        'location' => $decoded['location'] ?? null,
        'carrier' => $decoded['carrier'] ?? null,
        'line_type' => $decoded['line_type'] ?? null
    ];
} else {
    $output['summary'] = ['valid' => false];
}

// If provider supplies name (rare), include it explicitly
if (!empty($decoded['name'])) {
    $output['name'] = $decoded['name'];
} else {
    $output['name'] = null; // no name field from API
    $output['note'] = 'Numverify usually provides validation/carrier info; it may not return subscriber name for many countries.';
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
