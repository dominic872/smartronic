<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require '../config.php'; // Use the main config file
    
    $date = $_GET['date'] ?? '';
    header('Content-Type: application/json');
    
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date']);
        exit;
    }
    
    // Check if database connection exists (using $conn from main config)
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not established'));
    }
    
    $stmt = $conn->prepare("
        SELECT users.fullname, leaves.reason 
        FROM leaves 
        JOIN users ON users.id = leaves.user_id
        WHERE leave_date = ?
    ");
    
    if (!$stmt) {
        throw new Exception('SQL prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $date);
    
    if (!$stmt->execute()) {
        throw new Exception('SQL execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $leaves = [];
    while ($row = $result->fetch_assoc()) {
        $leaves[] = ['fullname' => $row['fullname'], 'reason' => $row['reason']];
    }
    
    echo json_encode(['success' => true, 'leaves' => $leaves]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_type' => get_class($e)
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error: ' . $e->getMessage(),
        'error_type' => get_class($e)
    ]);
}
