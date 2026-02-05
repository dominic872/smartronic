<?php

$fields = array(
    "sender_id" => "DLT_SENDER_ID", // Replace with your DLT Sender ID
    "message" => "YOUR_MESSAGE_ID", // Replace with your Message Template ID
    "variables_values" => "1234", // OTP or variable values
    "route" => "otp", // Route type
    "numbers" => "8884831000", // Recipient numbers separated by commas
);

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2", // API Endpoint
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($fields), // Convert fields to JSON format
    CURLOPT_HTTPHEADER => array(
        "authorization: e8fmOACN1x4qkZBh3RXQSrGnHFPIcayLW9o5dV6MvJ0tup27EjYgkW5X4JF2NmRznQwPqoBcfM8TEDtA", // Your API key
        "accept: */*",
        "cache-control: no-cache",
        "content-type: application/json",
    ),
));

$response = curl_exec($curl); // Execute the cURL request
$err = curl_error($curl); // Capture errors

curl_close($curl); // Close the cURL session

// Handle the response or errors
if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo $response; // Print the response
}
?>
