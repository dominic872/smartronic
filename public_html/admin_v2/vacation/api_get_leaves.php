<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

function leavesApiLogError($message, $context = []) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents(__DIR__ . '/api_get_leaves_error.log', $line . PHP_EOL, FILE_APPEND);
}

function leavesApiErrorPayload($message, $error = '', $extra = []) {
    $payload = array_merge([
        'success' => false,
        'message' => $message
    ], $extra);
    if ($error !== '') {
        $payload['error'] = $error;
    }
    if (isset($payload['error_no']) && !isset($payload['error_number'])) {
        $payload['error_number'] = $payload['error_no'];
    }
    if (isset($payload['error_number']) && !isset($payload['error_no'])) {
        $payload['error_no'] = $payload['error_number'];
    }

    $parts = [$message];
    if (isset($payload['error_number'])) {
        $parts[] = 'Error No: ' . $payload['error_number'];
    }
    if (isset($payload['source_line'])) {
        $parts[] = 'Line: ' . $payload['source_line'];
    }
    if ($error !== '') {
        $parts[] = $error;
    }
    $payload['message'] = implode(' | ', $parts);
    $payload['debug'] = array_merge([
        'api_file' => 'api_get_leaves.php',
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'date' => $_GET['date'] ?? '',
        'log_file' => 'api_get_leaves_error.log'
    ], $payload['debug'] ?? []);
    return $payload;
}

function leavesApiRespond($payload, $code = 200) {
    http_response_code($code);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($payload);
    exit;
}

function leavesApiFail($message, $error = '', $code = 500, $extra = []) {
    leavesApiLogError($message, array_merge([
        'error' => $error,
        'date' => $_GET['date'] ?? ''
    ], $extra));
    leavesApiRespond(leavesApiErrorPayload($message, $error, $extra), $code);
}

set_exception_handler(function($e) {
    leavesApiFail('Server exception', $e->getMessage(), 500, [
        'error_type' => get_class($e),
        'error_no' => $e->getCode(),
        'error_number' => $e->getCode(),
        'source_file' => $e->getFile(),
        'source_line' => $e->getLine()
    ]);
});

register_shutdown_function(function() {
    $e = error_get_last();
    if ($e) {
        leavesApiFail('Fatal error', $e['message'] ?? '', 500, [
            'error_no' => $e['type'] ?? 0,
            'error_number' => $e['type'] ?? 0,
            'source_file' => $e['file'] ?? '',
            'source_line' => $e['line'] ?? ''
        ]);
    }
});

$date = $_GET['date'] ?? '';
if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    leavesApiFail('Invalid date', '', 422, [
        'source_file' => __FILE__,
        'source_line' => __LINE__
    ]);
}

try {
    require_once __DIR__ . '/../config.php';
} catch (Throwable $e) {
    leavesApiFail('Config/DB load failed', $e->getMessage(), 500, [
        'error_type' => get_class($e),
        'error_no' => $e->getCode(),
        'error_number' => $e->getCode(),
        'source_file' => $e->getFile(),
        'source_line' => $e->getLine()
    ]);
}

if (!isset($conn) || $conn->connect_error) {
    $dbError = isset($conn) ? $conn->connect_error : 'Connection not established';
    $dbErrorNo = isset($conn) ? (int)$conn->connect_errno : 0;
    leavesApiFail('Database connection failed', $dbError, 500, [
        'error_no' => $dbErrorNo,
        'error_number' => $dbErrorNo,
        'source_file' => __FILE__,
        'source_line' => __LINE__
    ]);
}

$stmt = $conn->prepare("
    SELECT users.fullname, leaves.reason
    FROM leaves
    JOIN users ON users.id = leaves.user_id
    WHERE leave_date = ?
");

if (!$stmt) {
    leavesApiFail('SQL prepare failed', $conn->error, 500, [
        'error_no' => (int)$conn->errno,
        'error_number' => (int)$conn->errno,
        'source_file' => __FILE__,
        'source_line' => __LINE__
    ]);
}

$stmt->bind_param('s', $date);

if (!$stmt->execute()) {
    leavesApiFail('SQL execute failed', $stmt->error, 500, [
        'error_no' => (int)$stmt->errno,
        'error_number' => (int)$stmt->errno,
        'source_file' => __FILE__,
        'source_line' => __LINE__
    ]);
}

$result = $stmt->get_result();
$leaves = [];
while ($row = $result->fetch_assoc()) {
    $leaves[] = [
        'fullname' => $row['fullname'],
        'reason' => $row['reason']
    ];
}

leavesApiRespond(['success' => true, 'leaves' => $leaves]);
