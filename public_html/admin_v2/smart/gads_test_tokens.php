<?php

require_once __DIR__ . '/lib/GoogleAdsEnv.php';

$googleAdsEnv = new GoogleAdsEnv();
$clientId = $googleAdsEnv->get('GOOGLE_ADS_CLIENT_ID');
$clientSecret = $googleAdsEnv->get('GOOGLE_ADS_CLIENT_SECRET');
$refreshToken = $googleAdsEnv->get('GOOGLE_ADS_REFRESH_TOKEN');

$url = "https://oauth2.googleapis.com/token";

$post = http_build_query([
    "client_id" => $clientId,
    "client_secret" => $clientSecret,
    "refresh_token" => $refreshToken,
    "grant_type" => "refresh_token"
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
curl_close($ch);

echo "<pre>";
print_r($response);
echo "</pre>";
