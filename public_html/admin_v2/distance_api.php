<?php
header('Content-Type: application/json');

// Replace with your API key
$apiKey = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';

if (!isset($_GET['origin']) || !isset($_GET['destination'])) {
    echo json_encode(['error' => 'Missing origin or destination']);
    exit;
}

$origin = urlencode($_GET['origin']);
$destination = urlencode($_GET['destination']);

$url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=$origin&destinations=$destination&key=$apiKey";

$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data['status'] === 'OK') {
    $distance = $data['rows'][0]['elements'][0]['distance']['value'] ?? null; // in meters
    if ($distance !== null) {
        echo json_encode([
            'distance_km' => round($distance / 1000, 2),
            'text' => $data['rows'][0]['elements'][0]['distance']['text']
        ]);
    } else {
        echo json_encode(['error' => 'Could not fetch distance']);
    }
} else {
    echo json_encode(['error' => 'API error', 'details' => $data['status']]);
}
?>
