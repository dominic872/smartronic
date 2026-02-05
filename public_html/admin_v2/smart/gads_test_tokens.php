<?php

$clientId = "587618814920-n59ojbksctekeqgjth31edudepq88333.apps.googleusercontent.com";
$clientSecret = "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";
$refreshToken = "1//04uhaPid-lRCOCgYIARAAGAQSNwF-L9IrKa-48E1C4crFuzTc49fiaq6Y-YOjJsMgy_BA1yxaNPy9kCI9R7Uit4kkDPj8v6KPM_k";

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

