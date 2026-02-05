<style>
    .card {
        border: 1px solid red;
    }
</style>
<?php

$sheetId = '1OKNT1h9SLEOOGPeCiOIAP_QgQAlVnkzS84_EUypsX24'; // Replace with your actual Google Sheet ID
$sheetName = 'Current Leads';      // Optional: set if your sheet name is not "Sheet1"
$scriptUrl = 'https://script.google.com/macros/s/AKfycbze-MFu-PcOHNvpo0Tgb8BDlid8i88O0q4iW-y9AMGKQE33t3EE6x-bi8RQP5OeMHA/exec';

$url = $scriptUrl . '?id=' . urlencode($sheetId) . '&sheet=' . urlencode($sheetName);

$response = file_get_contents($url);

if ($response === FALSE) {
    die("Error fetching data from Google Apps Script");
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Invalid JSON response");
}

// Display results
echo "<h2>Rows with 'FOLLOW' in Column N</h2>";
echo "<div>";
foreach ($data as $row) {
    echo "<div class='card'>";
    foreach ($row['values'] as $index => $cell) {
        if ($index === 10) {
             echo "<h2>" . htmlspecialchars($cell) . "</h2>";
         }

         if ($index === 6) {
            echo "<u>" . htmlspecialchars($cell) . "</u>";
        }
    }
    echo "</div>";
}
echo "</div>";
?>

