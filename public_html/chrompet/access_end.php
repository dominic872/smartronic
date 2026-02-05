<?php
require 'db.php';
session_start();
header('Content-Type: application/json');

try {
    $logId = isset($_SESSION['access_log_id']) ? (int)$_SESSION['access_log_id'] : 0;
    if ($logId <= 0) {
        echo json_encode(['success' => true]); // nothing to end
        exit;
    }

    // Ensure table exists (idempotent, MySQL syntax)
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        ip VARCHAR(64),
        user_agent TEXT,
        start_time_ist DATETIME NOT NULL,
        end_time_ist DATETIME NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // IST now
    $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $end = $dt->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare('UPDATE access_logs SET end_time_ist = ? WHERE id = ?');
    $stmt->execute([$end, $logId]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
