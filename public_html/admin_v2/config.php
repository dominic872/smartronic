<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Detect if running locally or CLI
$isLocalhost = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) || php_sapi_name() === 'cli';

// Set credentials based on environment
$host = $isLocalhost ? 'localhost' : '127.0.0.1:3306';
$username = $isLocalhost ? 'root' : 'u398852039_smartronic';
$password = $isLocalhost ? 'root' : 'Chennai@40!';
$database = 'u398852039_smartronic';

// Attempt connection
 $conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " .  $conn->connect_error);
}
?>
