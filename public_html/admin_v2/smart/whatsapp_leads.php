
<?php

// ==========================================
// CONFIGURABLE VARIABLES
// ==========================================
$whatsappMessage = "Hey, I'm looking for CCTV camera information. Please share the details.";

// Load WhatsApp number from config (.php preferred, .txt fallback)
$configPhp = __DIR__ . '/whatsapp_leads_config.php';
$configTxt = __DIR__ . '/whatsapp_leads_config.txt';
$whatsappNumberList = [];
$whatsappNumber = null;
 
if (file_exists($configPhp)) {
    // Config PHP expected to define $whatsappNumber and $whatsappNumberList
    include $configPhp;
    if (isset($whatsappNumber) && (is_string($whatsappNumber) || $whatsappNumber === 'AUTO')) {
        if ($whatsappNumber !== 'AUTO') {
            $whatsappNumber = preg_replace('/\D+/', '', $whatsappNumber);
        }
    }
    
    // Resolve AUTO mode
    if ($whatsappNumber === 'AUTO') {
        // Set timezone to IST to ensure correct day
        date_default_timezone_set('Asia/Kolkata');
        $today = date('D'); // Mon, Tue, Wed...
        
        if (isset($whatsappSchedule) && isset($whatsappSchedule[$today])) { 
            $whatsappNumber = $whatsappSchedule[$today];
        } else {
            // Fallback if schedule missing: Use first in list
            if (isset($whatsappNumberList) && count($whatsappNumberList) > 0) {
                $whatsappNumber = preg_replace('/\D+/', '', $whatsappNumberList[0]);
            }
        }
    }

    if (!isset($whatsappNumber) || $whatsappNumber === '') {
        if (isset($whatsappNumberList) && is_array($whatsappNumberList) && count($whatsappNumberList) > 0) {
            $whatsappNumber = preg_replace('/\D+/', '', $whatsappNumberList[0]);
        }
    }
} elseif (file_exists($configTxt)) {
    // TXT format: first non-empty line is active number; all lines form options
    $lines = file($configTxt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_map('trim', $lines);
    $lines = array_filter($lines, function ($l) { return $l !== ''; });
    if (!empty($lines)) {
        $whatsappNumber = preg_replace('/\D+/', '', $lines[0]);
        $whatsappNumberList = array_values(array_unique(array_map(function ($n) { return preg_replace('/\D+/', '', $n); }, $lines)));
    }
}

// Fallback default if config not found
if (!$whatsappNumber) {
    $whatsappNumber = "919886735991";
}

$encodedMessage = urlencode($whatsappMessage);
$whatsappURL = "https://wa.me/$whatsappNumber?text=$encodedMessage";


// ==========================================
// STRICT SECURITY LAYER
// ==========================================

// Allow ONLY POST access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Access denied. Only POST requests allowed.");
}

// Secret POST token
$secretKey = "9f4a73c2e9b84bdc902f1a7e5d13acbd";

if (!isset($_POST['key']) || $_POST['key'] !== $secretKey) {
    die("Access denied. Invalid token.");
}


$gads = isset($_POST['gads']) ? $_POST['gads'] : '';
$gads = is_string($gads) ? trim($gads) : '';
if ($gads !== '') {
    $whatsappMessage = $whatsappMessage . ' [' . $gads . ']';
}
$encodedMessage = urlencode($whatsappMessage);
$whatsappURL = "https://wa.me/$whatsappNumber?text=$encodedMessage";



// ==========================================
// AUTO-LOAD CONFIG FILE
// ==========================================
$paths = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/admin/config.php',
    dirname(__DIR__) . '/admin/config.php'
];

$configLoaded = false;

foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    die("config.php not found.");
}


// ==========================================
// CREATE DB TABLE IF NOT EXISTS
// ==========================================
$tableSQL = "
CREATE TABLE IF NOT EXISTS whatsapp_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_wa VARCHAR(12),
    ip VARCHAR(100),
    user_agent TEXT,
    referer TEXT,
    click_time DATETIME
) ENGINE=InnoDB;
";

$conn->query($tableSQL);


// ==========================================
// LOG THE DETAILS
// ==========================================
$admin_wa = $whatsappNumber;
$ip = $_SERVER['REMOTE_ADDR'];
$agent = $_SERVER['HTTP_USER_AGENT'];
$referer = $_SERVER['HTTP_REFERER'] ?? "";
$time = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO whatsapp_clicks (admin_wa,ip, user_agent, referer, click_time) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $admin_wa, $ip, $agent, $referer, $time);
$stmt->execute();
$stmt->close();


// ==========================================
// REDIRECT TO WHATSAPP
// ==========================================
header("Location: $whatsappURL");
exit;

?>
