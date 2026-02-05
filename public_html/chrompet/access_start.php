<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($input['name'] ?? '');
    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }

    // Create table if not exists (MySQL/MariaDB syntax)
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        ip VARCHAR(64),
        user_agent TEXT,
        start_time_ist DATETIME NOT NULL,
        end_time_ist DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Resolve IP address
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // IST time
    $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $start = $dt->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare('INSERT INTO access_logs (name, ip, user_agent, start_time_ist) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $ip, $ua, $start]);
    $logId = (int)$pdo->lastInsertId();

    // Save to session
    $_SESSION['access_log_id'] = $logId;
    $_SESSION['access_name'] = $name;

    echo json_encode(['success' => true, 'id' => $logId]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
