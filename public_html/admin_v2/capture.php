<?php
$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

// Connect to the database
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get visitor details
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct';

// Fetch location details using IP
$ip_info = json_decode(file_get_contents("http://ip-api.com/json/$ip_address"), true);
$region = $ip_info['regionName'] ?? 'Unknown';
$country = $ip_info['country'] ?? 'Unknown';
$city = $ip_info['city'] ?? 'Unknown';

$vals = "$ip_address | $region | $country | $city | $referer | $user_agent | $is_returning";
// Check if the user is returning
session_start();
$is_returning = isset($_SESSION['is_returning']) ? 1 : 0;
$_SESSION['is_returning'] = true;

// Insert visitor details into the database
$stmt = $conn->prepare("
    INSERT INTO visitor_analytics 
    (ip_address, region, country, city, referer, user_agent, is_returning) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssssi', $ip_address, $region, $country, $city, $referer, $user_agent, $is_returning);
$stmt->execute();
$visitor_id = $stmt->insert_id;
$stmt->close();

echo $visitor_id; // Return the visitor ID to track events
$conn->close();
?>

<?php
$botToken = "7650074875:AAGuFoUncn_CE2CMCPadV_wjqHg4D47kXvI"; // Replace with your bot token
$chatID = "7994221275"; // Replace with your Chat ID

$vals = "$ip_address | $region | $country | $city | $referer | $user_agent | $is_returning";
// Check if the visitor came from Google Ads (Google Ads referers contain 'google.com')
if (strpos($referer, 'google.com') !== false) {
    $message = "Visit from G!Ads \n $vals";
} else {
    $message = "Visit from \n $vals";
}

// Send message to Telegram
$url = "https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatID&text=" . urlencode($message);
file_get_contents($url);
?>

 