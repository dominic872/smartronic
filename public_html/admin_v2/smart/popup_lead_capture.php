<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

$respond = function($payload, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$rawInput = file_get_contents('php://input');
$data = null;
if ($rawInput !== false && trim($rawInput) !== '') {
    $data = json_decode($rawInput, true);
}
if (!is_array($data)) {
    $data = $_POST;
}
if (!is_array($data)) {
    $respond(['success' => false, 'message' => 'Invalid request.'], 400);
}

$name = isset($data['name']) ? trim((string)$data['name']) : '';
$phoneRaw = isset($data['phone']) ? (string)$data['phone'] : '';
$phone = preg_replace('/\D+/', '', $phoneRaw);
if (strlen($phone) > 10 && substr($phone, 0, 2) === '91') {
    $phone = substr($phone, -10);
}
if (strlen($phone) > 10) {
    $phone = substr($phone, -10);
}

if ($name === '') {
    $respond(['success' => false, 'message' => 'Name is required.'], 422);
}
if (strlen($phone) !== 10) {
    $respond(['success' => false, 'message' => 'Please enter a valid WhatsApp number.'], 422);
}

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    $respond(['success' => false, 'message' => 'DB connection failed.'], 500);
}

$conn->query("SET time_zone = '+05:30'");
date_default_timezone_set('Asia/Kolkata');

$getMonthCode = function() {
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $month = (int)$date->format('n');
    $monthLetters = [
        1 => 'J',
        2 => 'F',
        3 => 'C',
        4 => 'R',
        5 => 'M',
        6 => 'U',
        7 => 'L',
        8 => 'G',
        9 => 'S',
        10 => 'O',
        11 => 'N',
        12 => 'D'
    ];
    return $monthLetters[$month] ?? 'X';
};

$assignees = ['AMR', 'VAR', 'ZOY'];
$assign = $assignees[0];
$newId = 0;
$newMid = '';
$maxRetries = 6;

for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $year = (int)$now->format('Y');
    $month = (int)$now->format('n');

    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM leads WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    if (!$countStmt) {
        $conn->close();
        $respond(['success' => false, 'message' => 'Failed to generate lead id.'], 500);
    }
    $countStmt->bind_param('ii', $year, $month);
    $countStmt->execute();
    $row = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();

    $cnt = (int)($row['cnt'] ?? 0);
    $newMid = $getMonthCode() . '-' . ($cnt + 1);
    $assign = $assignees[$cnt % count($assignees)];
    $column1 = 'popup-20off';

    $insStmt = $conn->prepare("INSERT INTO leads (name, whatsapp_number, created_at, MID, Assign, Column_1) VALUES (?, ?, NOW(), ?, ?, ?)");
    if (!$insStmt) {
        $conn->close();
        $respond(['success' => false, 'message' => 'Failed to create lead.'], 500);
    }
    $insStmt->bind_param('sssss', $name, $phone, $newMid, $assign, $column1);
    $ok = $insStmt->execute();
    $errNo = $conn->errno;
    $newId = (int)$conn->insert_id;
    $insStmt->close();

    if ($ok && $newId > 0) {
        $conn->close();
        $respond([
            'success' => true,
            'message' => 'Lead captured successfully.',
            'id' => $newId,
            'mid' => $newMid,
            'assign' => $assign
        ]);
    }

    if ($errNo !== 1062) {
        break;
    }

    usleep(120000);
}

$conn->close();
$respond(['success' => false, 'message' => 'Failed to save lead. Please try again.'], 500);
