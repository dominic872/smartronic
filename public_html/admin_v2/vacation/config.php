<?php
session_start();

// Detect if running locally
$isLocalhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// Set credentials based on environment
$host = $isLocalhost ? 'localhost' : '127.0.0.1:3306';
$username = $isLocalhost ? 'root' : 'u398852039_smartronic';
$password = $isLocalhost ? 'root' : 'Chennai@40!';
$database = 'u398852039_smartronic';

// Attempt connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Database Connection Failed: " . $mysqli->connect_error);
}
?>
