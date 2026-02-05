<?php
/**
 * Resolve Google Maps short URLs to extract coordinates
 * Usage: resolve_map_url.php?url=https://maps.app.goo.gl/xxxxx
 */

header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['url'])) {
    echo json_encode(['success' => false, 'error' => 'No URL provided']);
    exit;
}

$shortUrl = $_GET['url'];

// Validate it's a Google Maps URL
if (!preg_match('/goo\.gl|maps\.app|google\.com\/maps/i', $shortUrl)) {
    echo json_encode(['success' => false, 'error' => 'Not a valid Google Maps URL']);
    exit;
}

// Follow redirects to get the final URL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $shortUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($finalUrl)) {
    echo json_encode(['success' => false, 'error' => 'Could not resolve URL', 'httpCode' => $httpCode]);
    exit;
}

// Try to extract coordinates from the final URL
$coords = null;

// Pattern 1: Protobuf coordinates !3d and !4d (Most accurate for specific pins)
if (preg_match('/!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 2: ?q=lat,lng or &q=lat,lng
if (!$coords && preg_match('/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 3: destination=lat,lng
if (!$coords && preg_match('/[?&]destination=(-?\d+\.?\d*)[,|%2C](-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 4: ll=lat,lng
if (!$coords && preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 5: /place/lat,lng (rarely used with just coords, usually has place name)
if (!$coords && preg_match('/\/place\/(-?\d+\.?\d*),(-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 6: @lat,lng (Viewport center - least accurate, fallback)
if (!$coords && preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

// Pattern 7: Try to find any lat,lng pair in the URL as last resort
if (!$coords && preg_match('/(-?\d{1,3}\.\d{4,}),(-?\d{1,3}\.\d{4,})/', $finalUrl, $matches)) {
    $coords = ['lat' => floatval($matches[1]), 'lng' => floatval($matches[2])];
}

if ($coords) {
    echo json_encode([
        'success' => true,
        'coords' => $coords,
        'resolvedUrl' => $finalUrl
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Could not extract coordinates',
        'resolvedUrl' => $finalUrl
    ]);
}
