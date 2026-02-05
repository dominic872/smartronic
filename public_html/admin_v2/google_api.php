

<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Your Google Maps API key
$apiKey = 'AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU';

// Fixed origin point
$coords1 = ['lat' => 12.964815, 'lng' => 77.5786324];

// Read the `url` query parameter
if (!isset($_GET['url'])) {
    echo json_encode(['error' => 'Missing `url` query parameter']);
    exit;
}

$shortUrl = $_GET['url'];

function expandShortUrl($shortUrl) {
    $ch = curl_init($shortUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return $finalUrl;
}

function extractLatLong($url) {
    if (preg_match('/@([-.\d]+),([-.\d]+)/', $url, $matches)) {
        return ['lat' => $matches[1], 'lng' => $matches[2]];
    }
    return null;
}

function getDistanceInKm($originCoords, $destinationCoords, $apiKey) {
    $origin = $originCoords['lat'] . ',' . $originCoords['lng'];
    $destination = $destinationCoords['lat'] . ',' . $destinationCoords['lng'];

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric"
         . "&origins={$origin}&destinations={$destination}&key={$apiKey}";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data['status'] === "OK" && $data['rows'][0]['elements'][0]['status'] === "OK") {
        $distance = $data['rows'][0]['elements'][0]['distance']['value']; // meters
        return $distance / 1000; // km
    } else {
        return null;
    }
}

// Expand and extract
$expandedUrl = expandShortUrl($shortUrl);
$coords2 = extractLatLong($expandedUrl);

if ($coords2) {
    $distance = getDistanceInKm($coords1, $coords2, $apiKey);
    echo json_encode(['distance_km' => $distance]);
} else {
    echo json_encode(['error' => 'Could not extract coordinates from URL']);
}
