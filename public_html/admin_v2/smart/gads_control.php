<?php

/*********************************************************************
 * CONFIG — Paste your credentials below
 *********************************************************************/

// From API Center in your MCC (463-065-6250)
$developerToken  = "bFRBQas0OuZQqMbaY9n49g";

// Your NORMAL Ads account (the one with campaigns)
$customerId      = "3669769058";    // NO dashes

// Your MANAGER (MCC) account ID
$loginCustomerId = "4630656250";    // NO dashes

// From Google Cloud Console (same project used in OAuth Playground)
$clientId       = "587618814920-n59ojbksctekeqgjth31edudepq88333.apps.googleusercontent.com";
$clientSecret   = "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";

// From OAuth Playground (scope: https://www.googleapis.com/auth/adwords,
// using "Use your own OAuth credentials" with the above client)
$refreshToken   = "1//04uhaPid-lRCOCgYIARAAGAQSNwF-L9IrKa-48E1C4crFuzTc49fiaq6Y-YOjJsMgy_BA1yxaNPy9kCI9R7Uit4kkDPj8v6KPM_k";


/*********************************************************************
 * 1. Refresh access token (with debug)
 *********************************************************************/
function getAccessToken($clientId, $clientSecret, $refreshToken) {

    $url = "https://oauth2.googleapis.com/token";

    $params = [
        "client_id" => $clientId,
        "client_secret" => $clientSecret,
        "refresh_token" => $refreshToken,
        "grant_type" => "refresh_token"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        echo "<pre>cURL error while getting token:\n" . htmlspecialchars($err) . "</pre>";
        return false;
    }

    curl_close($ch);

    echo "<pre>Token response from Google (OAuth):\n" . htmlspecialchars($response) . "</pre>";

    $result = json_decode($response, true);

    if (!isset($result["access_token"])) {
        echo "<pre>No access_token in OAuth response (parsed):\n";
        print_r($result);
        echo "</pre>";
        return false;
    }

    return $result["access_token"];
}


/*********************************************************************
 * 2. Pause or Enable All Campaigns (with debug)
 *********************************************************************/
function updateCampaigns($status, $developerToken, $customerId, $loginCustomerId, $clientId, $clientSecret, $refreshToken) {

    // 1. Get access token
    $accessToken = getAccessToken($clientId, $clientSecret, $refreshToken);

    if (!$accessToken) {
        return "❌ Could not refresh access token.";
    }

    // 2. Fetch all campaigns
    $searchUrl = "https://googleads.googleapis.com/v16/customers/$customerId/googleAds:search";

    $query = [
        'query' => 'SELECT campaign.resource_name, campaign.id, campaign.name, campaign.status FROM campaign WHERE campaign.status != "REMOVED"'
    ];

    $headers = [
        "Authorization: Bearer $accessToken",
        "developer-token: $developerToken",
        "login-customer-id: $loginCustomerId",
        "Content-Type: application/json"
    ];

    $ch = curl_init($searchUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($query),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);
    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        echo "<pre>cURL error while fetching campaigns:\n" . htmlspecialchars($err) . "</pre>";
        return "❌ Could not fetch campaigns (cURL error).";
    }

    curl_close($ch);

    echo "<pre>Campaign search response from Google Ads:\n" . htmlspecialchars($response) . "</pre>";

    $result = json_decode($response, true);

    // If Google Ads returns an error object
    if (isset($result["error"])) {
        echo "<pre>Parsed Google Ads API error:\n";
        print_r($result["error"]);
        echo "</pre>";
        return "❌ Google Ads API error while fetching campaigns.";
    }

    if (!isset($result["results"]) || !is_array($result["results"]) || count($result["results"]) === 0) {
        echo "<pre>No 'results' in campaign search response (parsed):\n";
        print_r($result);
        echo "</pre>";
        return "❌ No campaigns found or unexpected response.";
    }

    // 3. Prepare updates
    $updateUrl = "https://googleads.googleapis.com/v16/customers/$customerId/campaigns:mutate";

    foreach ($result["results"] as $row) {

        $resourceName = $row["campaign"]["resourceName"];
        $campaignId   = $row["campaign"]["id"] ?? "unknown";
        $campaignName = $row["campaign"]["name"] ?? "unknown";

        $body = [
            "operations" => [
                [
                    "update" => [
                        "resourceName" => $resourceName,
                        "status" => $status
                    ],
                    "updateMask" => "status"
                ]
            ]
        ];

        $ch = curl_init($updateUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true
        ]);
        $updateResponse = curl_exec($ch);

        if ($updateResponse === false) {
            $err = curl_error($ch);
            curl_close($ch);
            echo "<pre>cURL error while updating campaign $campaignId ($campaignName):\n" . htmlspecialchars($err) . "</pre>";
            continue;
        }

        curl_close($ch);

        echo "<pre>Update response for campaign $campaignId ($campaignName):\n" . htmlspecialchars($updateResponse) . "</pre>";
    }

    return "✅ All campaigns updated to: $status";
}


/*********************************************************************
 * 3. Handle Web Buttons
 *********************************************************************/
if (isset($_GET["action"])) {
    if ($_GET["action"] === "pause") {
        echo updateCampaigns("PAUSED", $developerToken, $customerId, $loginCustomerId, $clientId, $clientSecret, $refreshToken);
    }
    if ($_GET["action"] === "start") {
        echo updateCampaigns("ENABLED", $developerToken, $customerId, $loginCustomerId, $clientId, $clientSecret, $refreshToken);
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Ads Controller</title>
    <style>
        body { font-family: Arial, sans-serif; margin:40px; }
        button {
            padding: 15px 25px;
            font-size: 18px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            margin-right: 10px;
        }
        .pause { background:#e74c3c; color:#fff; }
        .start { background:#27ae60; color:#fff; }
        pre { background:#f4f4f4; padding:10px; border-radius:6px; }
    </style>
</head>
<body>

<h2>Google Ads Controller</h2>
<p>Click a button below to pause or start all campaigns in account <strong><?php echo htmlspecialchars($customerId); ?></strong>.</p>

<a href="?action=pause"><button class="pause">Pause All Ads</button></a>
<a href="?action=start"><button class="start">Start All Ads</button></a>

</body>
</html>
