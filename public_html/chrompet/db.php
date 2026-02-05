<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// db.php
$DB_HOST = '127.0.0.1:3306';
$DB_NAME = 'u398852039_smartronic';
$DB_USER = 'u398852039_smartronic';
$DB_PASS = 'Chennai@40!';


try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die('DB connection error: ' . htmlspecialchars($e->getMessage()));
}
?>