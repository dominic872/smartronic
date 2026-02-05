<?php
// Manually load Google API client library
require_once __DIR__ . '/../../google-api-php-client/google-api-php-client-main/vendor/autoload.php';

// Set up Google Client
$client = new Google_Client();
$client->setApplicationName('Google Sheets API PHP');
$client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
$client->setAuthConfig(__DIR__ . '/../../smartronic-458614-978896363c6c.json');
$client->setAccessType('offline');

$service = new Google_Service_Sheets($client);

// Google Sheet info
$spreadsheetId = '1OKNT1h9SLEOOGPeCiOIAP_QgQAlVnkzS84_EUypsX24';  // ← Replace with your actual Sheet ID
$sheetName = 'Sheet11'; // Adjust if your tab has a different name

// Fetch all data
$response = $service->spreadsheets_values->get($spreadsheetId, $sheetName);
$values = $response->getValues();

$targetValue = 'R-123'; // The value you're searching for in column A
$rowIndex = null;

// Search for the row where column A equals 'R-123'
foreach ($values as $index => $row) {
    if (isset($row[0]) && $row[0] === $targetValue) {
        $rowIndex = $index + 1;
        break;
    }
}

if ($rowIndex !== null) {
    // Column B of the found row
    $range = "$sheetName!B$rowIndex";
    $body = new Google_Service_Sheets_ValueRange([
        'values' => [['Updated via PHP']]
    ]);
    $params = ['valueInputOption' => 'RAW'];

    $service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
    echo "✅ Updated cell B$rowIndex where A = '$targetValue'";
} else {
    echo "❌ No row found where column A = '$targetValue'";
}
