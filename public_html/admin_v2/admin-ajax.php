<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection parameters
$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'data' => 'Database connection failed: ' . $conn->connect_error]));
}
    define('GUPSHUP_API_KEY', 'shxu5xiveqtbo20as5brlfsh9ijdnnir'); // your Gupshup API key
    define('GUPSHUP_APP_NAME', 'smartwaapp');
    define('GUPSHUP_SOURCE', '918549930040'); // your WA API number

function gupshupOptIn($phone) {

    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone;
    }

    $url = "https://api.gupshup.io/wa/api/v1/app/opt/in";

    $postData = http_build_query([
        'user'   => $cleanPhone,
        'source' => 'Website'
    ]);

    $headers = [
        "apikey: " . GUPSHUP_API_KEY,
        "Content-Type: application/x-www-form-urlencoded"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    error_log("GUPSHUP OPT-IN RESPONSE: " . $response);

    return $response;
}


function sendWhatsAppTemplate($phone, $name = 'Customer') {

    // Clean & normalise phone number
    $cleanPhone = preg_replace('/\D/', '', $phone);
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '91' . $cleanPhone;
    }

    $url = "https://api.gupshup.io/wa/api/v1/template/msg";

    $postData = http_build_query([
        'channel'    => 'whatsapp',
        'source'     => '918549930040',       // your WA API number
        'destination'=> $cleanPhone,
        'src.name'   => 'smartwaapp',         // APP NAME (important)
        'template'   => json_encode([
            'id'     => 'bbbb8891-6ccb-4e7e-b5a9-7ebfc6a65f09', // TEMPLATE UUID
            'params' => [ $name ]                              // {{1}}
        ])
    ]);

    $headers = [
        "Content-Type: application/x-www-form-urlencoded",
        "apikey: shxu5xiveqtbo20as5brlfsh9ijdnnir"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    // Log response for debugging
    error_log("GUPSHUP WA HTTP $httpCode RESPONSE: " . $response);

    return $response;
}


function getMonthCode() {
    // Create DateTime object in IST
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $month = (int) $date->format('n'); // 1 = January, 2 = February, ..., 12 = December

    $monthLetters = [
        1 => 'J',  // JAN
        2 => 'F',  // FEB
        3 => 'C',  // MAR
        4 => 'R',  // APR
        5 => 'M',  // MAY
        6 => 'U',  // JUN
        7 => 'L',  // JUL
        8 => 'G',  // AUG
        9 => 'S',  // SEP
        10 => 'O', // OCT
        11 => 'N', // NOV
        12 => 'D'  // DEC
    ];
    

    return $monthLetters[$month] ?? 'X'; // fallback to 'X' if not found
}

// Google Ads campaign tracking

// 1️⃣ Get parameters
$gadCampaignId = isset($_POST['gad_campaignid']) ? trim((string)$_POST['gad_campaignid']) : null;
$gadsParam = isset($_GET['gads']) ? trim((string)$_GET['gads']) : null;

// 2️⃣ Campaign ID to Label map
$campaignIdMap = [
    '22154028165' => 'GL',   // Get Leads
    '23084756848' => 'PM',   // Pmax
    '22848100317' => 'DG',   // Demand Gen
    '23085059220' => 'SO25', // Search Oct 25
    '23220779450' => '13K'   // 13,390 CP Plus Offer
];

// 3️⃣ Default label
$label = 'Q';

// 4️⃣ Priority: URL ?gads=... → then campaign ID map
if ($gadsParam) {
    // Use the direct ?gads= value
    $label = $gadsParam;
} elseif ($gadCampaignId && isset($campaignIdMap[$gadCampaignId])) {
    // Fallback to mapped campaign ID
    $label = $campaignIdMap[$gadCampaignId];
}

// Check if the AJAX request contains the expected action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crf_save_form_data') {

    // Sanitize input values
    $num_cameras = $_POST['num_cameras'] ?? '';
    $dvr_type = $_POST['dvr_type'] ?? '';
    $hdd_size = $_POST['hdd_size'] ?? '';
    $camera_resolution = $_POST['camera_resolution'] ?? '';
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';

    // Validate required fields
    if (empty($num_cameras) || empty($dvr_type) || empty($hdd_size) || empty($camera_resolution) || empty($whatsapp_number)) {
        echo json_encode(['success' => false, 'data' => 'All fields are required.']);
        exit;
    }

    // Proceed to process (wp_cctv_requirements deprecated)
    if (true) {
        
        // 👉 SEND WHATSAPP HERE (new)
        // TEMP values (you can improve later)
        $customerName = "";
        $repName = "from the sales team";
        
        $optinResponse = gupshupOptIn($whatsapp_number);

        // OPTIONAL: small delay helps in some cases
        usleep(300000); // 300ms

        sendWhatsAppTemplate(
            $whatsapp_number,
            $customerName ?: 'Customer'
        );


        
        // Data saved successfully
        //echo json_encode(['success' => true, 'data' => 'Data saved successfully.']);
        // Get visitor details
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Fetch location details using IP
        $ip_info = @json_decode(file_get_contents("http://ip-api.com/json/$ip_address"), true) ?: [];
        $region = $ip_info['regionName'] ?? 'Unknown';
        $country = $ip_info['country'] ?? 'Unknown';
        $city = $ip_info['city'] ?? 'Unknown';

        $user_vals = "$ip_address | $region | $country | $city ";

        // Email settings
        $to = "hello@smartronic.online"; // Replace with your email address
        $subject = "$whatsapp_number | $num_cameras | $dvr_type | $hdd_size | $camera_resolution";
        $headers = "From: hello@smartronic.online\r\n";
        $headers .= "Reply-To: hello@smartronic.online\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Email body
        $body = "CCTV Requirement Details:\n";
        $body .= "Number of Cameras: $num_cameras\n";
        $body .= "DVR Type: $dvr_type\n";
        $body .= "HDD Size: $hdd_size\n";
        $body .= "Camera Resolution: $camera_resolution\n";
        $body .= "WhatsApp Number: $whatsapp_number";

        // Email SEND
          mail($to, $subject, $body, $headers);

           // Telegram Notification
        $botToken = "7729323805:AAFBIBS2M1FJM5pozzcb7AZNiyOXLyO8Xig";
        $chatID = "7994221275";

        $vals = "$whatsapp_number | $num_cameras | $dvr_type | $hdd_size | $camera_resolution";
        $message = "$label | Request Quote: \n $vals \n $user_vals \n https://smartronic.online/admin_v2/quote.php?quote=" . urlencode($vals);

        // Detect Google Ads referral
        $referer = $_SERVER['HTTP_REFERER'] ?? 'Unknown';
        if (strpos($referer, 'google.com') !== false || isset($_GET['gclid'])) {
            $message = "Request Quote (G!Ads) \n $vals \n $user_vals \n https://smartronic.online/admin_v2/quote.php";
        }

        // Send message to Telegram
        $url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatID&text=" . urlencode($message);
        $telegramResponse = @file_get_contents($url);
        $urlSheets = "https://smartronic.online/admin_v2/quote.php?quote=" . urlencode($vals);

        if (!$telegramResponse) {
            error_log("Telegram API request failed.");
        }
        echo json_encode(['success' => true, 'data' =>  urlencode($message)]);


        $insert_id = $conn->insert_id;

        // ========================= 
        // INSERT INTO LEADS (MID = MONTH COUNT) 
        // ========================= 
        
        $conn->query("SET time_zone = '+05:30'"); 
 
        $assignees = ['VAR', 'AMR', 'ZOY']; 
        $maxRetries = 5; 
        $attempt = 0; 
        
        while ($attempt < $maxRetries) { 
            try { 
                $attempt++; 
        
                // Current month range (IST) 
                $now = new DateTime('now', new DateTimeZone('Asia/Kolkata')); 
                
                // Recount leads for this month 
                $countStmt = $conn->prepare( 
                    "SELECT COUNT(*) AS cnt 
                     FROM leads 
                     WHERE YEAR(created_at) = ? 
                       AND MONTH(created_at) = ?" 
                ); 
                
                $year  = (int)$now->format('Y'); 
                $month = (int)$now->format('n'); 
                
                $countStmt->bind_param('ii', $year, $month); 
                $countStmt->execute(); 
                $row = $countStmt->get_result()->fetch_assoc(); 
                $countStmt->close(); 
        
                // Total rows including this new one 
                $totalRows = ((int)$row['cnt']) + 1; 
                $mid       = getMonthCode() . '-' . $totalRows; 
        
                // Assign (round robin) 
                $assign = $assignees[((int)$row['cnt']) % count($assignees)]; 
        
                // Insert 
                $leadStmt = $conn->prepare( 
                    "INSERT INTO leads 
                     (num_cameras, dvr_type, hdd_size, camera_resolution, 
                      whatsapp_number, created_at, MID, assign, Column_1) 
                     VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)" 
                ); 
        
                $leadStmt->bind_param( 
                    'ssssssss', 
                    $num_cameras, 
                    $dvr_type, 
                    $hdd_size, 
                    $camera_resolution, 
                    $whatsapp_number, 
                    $mid, 
                    $assign,
                    $label
                );
        
                if (!$leadStmt->execute()) { 
                    // Duplicate MID → retry 
                    if ($conn->errno == 1062) { 
                        $leadStmt->close(); 
                        usleep(100000); // 100ms backoff 
                        continue; 
                    } 
                    throw new Exception($leadStmt->error); 
                } 
        
                $leadStmt->close(); 
                break; // ✅ success 
        
            } catch (Throwable $e) { 
                if ($attempt >= $maxRetries) { 
                    error_log("FINAL MID FAILURE: " . $e->getMessage()); 
                    throw $e; 
                } 
            } 
        }


        $google_data = [
            'no' => '="' . getMonthCode() . '-" & ROW()', //  Optional if you want to handle row number in Sheets
            'cams' => $num_cameras,
            'dvr_type' => $dvr_type,
            'hdd_size' => $hdd_size, 
            'camera_resolution' => $camera_resolution,
            'quote_send' => 'No',
            'whatsapp' => $whatsapp_number,
            'created_at' => (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('d-m-Y H:i:s'),
            'message' => $urlSheets,
            'noVal' => '',
           'assign' => '=CHOOSE(MOD(ROW()-1,4)+1,"VAR","AMR","BHA","ZOY")', // If you ever add more people, Just increase the number: MOD(ROW()-1,5)+1
           'quote' => '=HYPERLINK(
                "https://smartronic.online/admin_v2/quote.php?quote=" & 
                ENCODEURL(
                    INDIRECT("G" & ROW()) & " | " & 
                    INDIRECT("B" & ROW()) & " | " & 
                    INDIRECT("C" & ROW()) & " | " & 
                    INDIRECT("D" & ROW()) & " | " & 
                    INDIRECT("E" & ROW()) & " | " & 
                    INDIRECT("K" & ROW()) & " | " & 
                    INDIRECT("A" & ROW())
                ),
                "' . $label . '"
                )',
             'smart_quote' => '=HYPERLINK(
                "https://smartronic.online/admin_v2/smart_quote.php?quote=" & 
                ENCODEURL(
                    INDIRECT("G" & ROW()) & " | " & 
                    INDIRECT("B" & ROW()) & " | " & 
                    INDIRECT("C" & ROW()) & " | " & 
                    INDIRECT("D" & ROW()) & " | " & 
                    INDIRECT("E" & ROW()) & " | " & 
                    INDIRECT("K" & ROW()) & " | " & 
                    INDIRECT("A" & ROW())
                ),
                "SQ"
                )',
             'order' => '=HYPERLINK(
                "https://smartronic.online/admin_v2/create_order.php?quote=" & 
                ENCODEURL(
                    INDIRECT("G" & ROW()) & " | " & 
                    INDIRECT("B" & ROW()) & " | " & 
                    INDIRECT("C" & ROW()) & " | " & 
                    INDIRECT("D" & ROW()) & " | " & 
                    INDIRECT("E" & ROW()) & " | " & 
                    INDIRECT("K" & ROW()) & " | " &  
                    INDIRECT("L" & ROW()) & " | " & 
                    INDIRECT("M" & ROW()) & " | " &  
                    INDIRECT("P" & ROW()) & " | " & 
                    INDIRECT("Q" & ROW()) & " | " & 
                    INDIRECT("R" & ROW()) & " | " & 
                    INDIRECT("A" & ROW())
                ),
                "O"
                )'
        ]; 
    
        // ✅ Your webhook URL from Google Apps Script
        //$webhook_url = "https://script.google.com/macros/s/AKfycbxZ7gJOhZgMbEgube-Bcov7GuGg5rZcznqsAATRBEvLCAUqIlQefUdmSeBCXBt6EncN/exec";
       $webhook_url = "https://script.google.com/macros/s/AKfycbzap_NRgdTdMtoSBtg5ScJ1Dlyhg1J4nq4P84cK6buSn6MaYwWfc2IYK_GJHPWhVLg/exec";
        //$webhook_url = "https://script.google.com/macros/s/AKfycbxOxqVSQpT5j0HkJPBthAlqHsNEuI66SWzJly-jMiNkZzupidTVTcFzu0B8chbSAg/exec";
    
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($google_data),
            ]
        ];
    
        $context = stream_context_create($options);
      file_get_contents($webhook_url, false, $context);


    }
    // End of process
} else {
    echo json_encode(['success' => false, 'data' => 'Invalid request.']);
}

// Close connection
$conn->close();


?>
