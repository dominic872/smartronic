<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

$safe_error = function($message, $extra = []) {
    $payload = array_merge(['success' => false, 'message' => $message], is_array($extra) ? $extra : []);
    echo json_encode($payload);
    exit;
};

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $database);
if ($conn->connect_error) $safe_error('DB connection failed');

$role = $_COOKIE['auth_role'] ?? '';
$isAdmin = in_array(strtolower(trim((string)$role)), ['admin', 'manager'], true);
$isMarket = ($role === 'market');
if (!$isAdmin && !$isMarket) {
    http_response_code(403);
    $safe_error('No access');
}

$conn->query("SET time_zone = '+05:30'");
date_default_timezone_set('Asia/Kolkata');

$rawInput = file_get_contents('php://input');
$data = null;
if ($rawInput !== false && trim($rawInput) !== '') {
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) $data = null;
}
if ($data === null) $data = $_POST;
if (!is_array($data)) $safe_error('Invalid request');

$authName = $_COOKIE['auth_name'] ?? '';
$authUser = $_COOKIE['auth_user'] ?? '';
$codeSource = $authName !== '' ? $authName : $authUser;
$letters = preg_replace('/[^a-zA-Z]/', '', $codeSource);
$letters = strtoupper($letters);
$userCode = $letters !== '' ? substr($letters, 0, 3) : '';
$assign = $userCode !== '' ? $userCode : 'UNK';

$sanitize_phone = function($n) {
    $s = preg_replace('/\D+/', '', (string)$n);
    if ($s === '') return '';
    if (strlen($s) > 10 && substr($s, 0, 2) === '91') $s = substr($s, -10);
    if (strlen($s) > 10) $s = substr($s, -10);
    return $s;
};

$num_cameras = isset($data['num_cameras']) ? trim((string)$data['num_cameras']) : '';
$dvr_type = isset($data['dvr_type']) ? trim((string)$data['dvr_type']) : '';
$hdd_size = isset($data['hdd_size']) ? trim((string)$data['hdd_size']) : '';
$camera_resolution = isset($data['camera_resolution']) ? trim((string)$data['camera_resolution']) : '';
$whatsapp_number = $sanitize_phone($data['whatsapp_number'] ?? '');
$force_create = !empty($data['force_create']) && ($data['force_create'] === true || $data['force_create'] === 1 || $data['force_create'] === '1' || $data['force_create'] === 'true');
$debug = !empty($data['debug']) && ($data['debug'] === true || $data['debug'] === 1 || $data['debug'] === '1' || $data['debug'] === 'true');
$dbg = [];
$dbg['force_create'] = $force_create ? 1 : 0;
$dbg['input_whatsapp'] = isset($data['whatsapp_number']) ? (string)$data['whatsapp_number'] : '';
$dbg['normalized_whatsapp'] = $whatsapp_number;
if ($debug) {
    $dbg['server_info'] = (string)($conn->server_info ?? '');
    $dbg['host_info'] = (string)($conn->host_info ?? '');
    $dbRes = $conn->query("SELECT DATABASE() AS db");
    $dbg['database'] = ($dbRes && ($r = $dbRes->fetch_assoc())) ? (string)($r['db'] ?? '') : '';
}

if ($num_cameras === '' || $dvr_type === '' || $hdd_size === '' || $camera_resolution === '' || $whatsapp_number === '') {
    $safe_error('All fields are required.');
}

$camsInt = (int)preg_replace('/\D+/', '', $num_cameras);
if ($camsInt <= 0) $safe_error('Invalid cameras value.');
if ($camsInt > 32) $camsInt = 32;
$num_cameras = (string)$camsInt;

$esc = $conn->real_escape_string($whatsapp_number);
$normExpr = "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(whatsapp_number), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', ''), '/', ''), ',', ''), ':', ''), '|', ''), 10)";
$dupSql = "SELECT id, MID, whatsapp_number
           FROM leads
           WHERE ($normExpr = '$esc'
              OR whatsapp_number = '$esc'
              OR whatsapp_number = CONCAT('91', '$esc')
              OR whatsapp_number = CONCAT('+91', '$esc')
              OR whatsapp_number LIKE CONCAT('%', '$esc', '%'))
           ORDER BY id DESC
           LIMIT 50";
$dupRes = $conn->query($dupSql);
if ($debug) {
    $dbg['dup_sql_used'] = 1;
    $dbg['dup_ok'] = $dupRes ? 1 : 0;
    $dbg['dup_error'] = $dupRes ? '' : (string)$conn->error;
    $dbg['dup_num_rows'] = $dupRes ? (int)$dupRes->num_rows : 0;
}
if ($dupRes && $dupRes->num_rows > 0) {
    $matches = [];
    while ($r = $dupRes->fetch_assoc()) {
        $matches[] = [
            'lead_id' => (int)($r['id'] ?? 0),
            'mid' => (string)($r['MID'] ?? ''),
            'whatsapp_number' => (string)($r['whatsapp_number'] ?? ''),
        ];
    }
    $dbg['dup_count'] = count($matches);
    $dbg['dup_sample'] = array_slice($matches, 0, 3);
    if (!$force_create) {
        $conn->close();
        $first = $matches[0] ?? ['lead_id' => 0, 'mid' => '', 'whatsapp_number' => $whatsapp_number];
        echo json_encode([
            'success' => false,
            'duplicate' => true,
            'message' => 'WhatsApp already exists.',
            'lead_id' => (int)($first['lead_id'] ?? 0),
            'mid' => (string)($first['mid'] ?? ''),
            'whatsapp_number' => (string)($first['whatsapp_number'] ?? $whatsapp_number),
            'matches_count' => count($matches),
            'matches' => $matches,
            'debug' => $debug ? $dbg : null
        ]);
        exit;
    }
}

$getMonthCode = function() {
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $month = (int) $date->format('n');
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

$maxRetries = 6;
$attempt = 0;
$newId = 0;
$newMid = '';
while ($attempt < $maxRetries) {
    $attempt++;
    $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $year = (int)$now->format('Y');
    $month = (int)$now->format('n');

    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM leads WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    if (!$countStmt) $safe_error('Failed to generate MID.');
    $countStmt->bind_param('ii', $year, $month);
    $countStmt->execute();
    $row = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();

    $cnt = (int)($row['cnt'] ?? 0);
    $newMid = $getMonthCode() . '-' . ($cnt + 1);

    $insStmt = $conn->prepare("INSERT INTO leads (num_cameras, dvr_type, hdd_size, camera_resolution, whatsapp_number, created_at, MID, Assign) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
    if (!$insStmt) $safe_error('Failed to create lead.');
    $insStmt->bind_param('sssssss', $num_cameras, $dvr_type, $hdd_size, $camera_resolution, $whatsapp_number, $newMid, $assign);
    $ok = $insStmt->execute();
    $errNo = $conn->errno;
    $errMsg = $conn->error;
    $newId = (int)$conn->insert_id;
    $insStmt->close();

    if ($ok) break;
    if ($errNo === 1062) {
        usleep(120000);
        continue;
    }
    $safe_error('Failed to create lead.', ['db_error' => $errMsg]);
}

if ($newId <= 0) $safe_error('Failed to create lead.');

if ($debug) {
    $idEsc = (int)$newId;
    $post = $conn->query("SELECT whatsapp_number, MID, created_at FROM leads WHERE id = $idEsc LIMIT 1");
    if ($post && ($r = $post->fetch_assoc())) {
        $dbg['inserted_whatsapp_db'] = (string)($r['whatsapp_number'] ?? '');
        $dbg['inserted_mid_db'] = (string)($r['MID'] ?? '');
        $dbg['inserted_created_at_db'] = (string)($r['created_at'] ?? '');
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Lead created.',
    'id' => $newId,
    'mid' => $newMid,
    'assign' => $assign,
    'debug' => $debug ? $dbg : null
]);
$conn->close();
?>
