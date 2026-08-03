<?php
header('Content-Type: application/json');

$apiKey = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';
$origin = isset($_GET['origin']) ? trim((string)$_GET['origin']) : '';
$destination = isset($_GET['destination']) ? trim((string)$_GET['destination']) : '';

if ($origin === '' || $destination === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing origin or destination']);
    exit;
}

$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smartronic-distance-cache';
$cacheTtl = 60 * 60 * 24 * 30;
$cacheKey = sha1(strtolower($origin . '|' . $destination));
$cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}

if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $cached = @file_get_contents($cacheFile);
    if ($cached !== false && $cached !== '') {
        echo $cached;
        exit;
    }
}

$url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=metric'
    . '&origins=' . rawurlencode($origin)
    . '&destinations=' . rawurlencode($destination)
    . '&key=' . rawurlencode($apiKey);

$context = stream_context_create([
    'http' => [
        'timeout' => 8,
        'ignore_errors' => true,
    ],
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Distance service unavailable']);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data) || ($data['status'] ?? '') !== 'OK') {
    http_response_code(502);
    echo json_encode(['error' => 'API error', 'details' => $data['status'] ?? 'UNKNOWN']);
    exit;
}

$element = $data['rows'][0]['elements'][0] ?? null;
$distanceValue = $element['distance']['value'] ?? null;
if ($distanceValue === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not fetch distance']);
    exit;
}

$payload = json_encode([
    'distance_km' => round(((float)$distanceValue) / 1000, 2),
    'text' => $element['distance']['text'] ?? ''
]);

if ($payload !== false) {
    @file_put_contents($cacheFile, $payload, LOCK_EX);
    echo $payload;
    exit;
}

http_response_code(500);
echo json_encode(['error' => 'Failed to encode response']);
