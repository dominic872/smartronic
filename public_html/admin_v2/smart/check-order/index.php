<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_OFF);

$format = detectLookupFormat();
$textMode = $format === 'text';

sendLookupHeaders($textMode);

$role = strtolower(trim((string)($_COOKIE['auth_role'] ?? '')));
$expectedApiKey = loadLookupApiKey();
$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
$providedApiKey = '';

if (preg_match('/Bearer\s+(.+)/i', $authorizationHeader, $matches)) {
    $providedApiKey = trim($matches[1]);
}

$validRole = in_array($role, ['admin', 'manager', 'market'], true);
$validApiKey = $expectedApiKey !== '' && $providedApiKey !== '' && hash_equals($expectedApiKey, $providedApiKey);

/*
if (!$validRole && !$validApiKey) {
    if ($providedApiKey !== '' && $expectedApiKey === '') {
        sendLookupResponse(500, [
            'success' => false,
            'available' => false,
            'exists' => false,
            'message' => 'API key is not configured'
        ], $textMode);
    }

    sendLookupResponse(403, [
        'success' => false,
        'available' => false,
        'exists' => false,
        'message' => 'No access'
    ], $textMode);
}
*/

$digits = normalizeLookupPhone($_GET['phone'] ?? $_GET['number'] ?? '');
if ($digits === null) {
    sendLookupResponse(400, [
        'success' => false,
        'available' => false,
        'exists' => false,
        'message' => 'Please provide a valid phone number using ?phone=XXXXXXXXXX'
    ], $textMode);
}

$conn = loadLookupDbConnection();
if (!$conn || $conn->connect_error) {
    sendLookupResponse(500, [
        'success' => false,
        'available' => false,
        'exists' => false,
        'message' => 'DB connection failed'
    ], $textMode);
}

$phoneExpr = "RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', ''), 10)";
$sql = "SELECT idno, name, phone, area, quantity, product, storage, resolution, Owner, date, created_at FROM orders WHERE {$phoneExpr} = ? ORDER BY date DESC, idno DESC LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    sendLookupResponse(500, [
        'success' => false,
        'available' => false,
        'exists' => false,
        'message' => 'Prepare failed'
    ], $textMode);
}

$stmt->bind_param('s', $digits);
if (!$stmt->execute()) {
    sendLookupResponse(500, [
        'success' => false,
        'available' => false,
        'exists' => false,
        'message' => 'Query failed'
    ], $textMode);
}

$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$exists = (bool)$row;

sendLookupResponse(200, [
    'success' => true,
    'available' => $exists,
    'exists' => $exists,
    'phone' => $digits,
    'name' => $exists ? (string)($row['name'] ?? '') : null,
    'details' => $exists ? buildDetailsLine($row) : null,
    'order_id' => $exists ? (string)($row['idno'] ?? '') : null
], $textMode);

function sendLookupHeaders($textMode)
{
    header($textMode ? 'Content-Type: text/plain; charset=utf-8' : 'Content-Type: application/json; charset=utf-8');
    header($textMode ? 'Content-Disposition: inline; filename="response.txt"' : 'Content-Disposition: inline; filename="response.json"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}

function detectLookupFormat()
{
    $format = strtolower(trim((string)($_GET['format'] ?? 'json')));
    if ($format === 'text' || $format === 'txt') {
        return 'text';
    }

    $requestUri = strtolower((string)($_SERVER['REQUEST_URI'] ?? ''));
    $path = parse_url($requestUri, PHP_URL_PATH);
    if (is_string($path) && substr($path, -4) === '.txt') {
        return 'text';
    }

    return 'json';
}

function sendLookupResponse($statusCode, array $payload, $textMode)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);

    if ($textMode) {
        echo buildTextResponse($payload);
    } else {
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    exit;
}

function buildTextResponse(array $payload)
{
    if (empty($payload['success'])) {
        return (string)($payload['message'] ?? 'Request failed') . "\n";
    }

    if (empty($payload['exists'])) {
        return "No existing order found\n\nPhone: " . (string)($payload['phone'] ?? '') . "\n";
    }

    return "Please call now\n\n"
        . "Name: " . (string)($payload['name'] ?? '') . "\n"
        . "Phone: " . (string)($payload['phone'] ?? '') . "\n"
        . "Order ID: " . (string)($payload['order_id'] ?? '') . "\n\n"
        . "Details:\n"
        . (string)($payload['details'] ?? '') . "\n";
}

function loadLookupApiKey()
{
    $envKey = getenv('SMARTRONIC_ORDER_LOOKUP_API_KEY');
    if (is_string($envKey) && trim($envKey) !== '') {
        return trim($envKey);
    }

    foreach ([
        dirname(__DIR__, 4) . '/config/smart_order_api.php',
        dirname(__DIR__, 5) . '/config/smart_order_api.php'
    ] as $path) {
        if (!is_file($path)) {
            continue;
        }

        $config = require $path;
        if (is_array($config) && !empty($config['api_key'])) {
            return trim((string)$config['api_key']);
        }
        if (defined('SMARTRONIC_ORDER_LOOKUP_API_KEY') && SMARTRONIC_ORDER_LOOKUP_API_KEY !== '') {
            return trim((string)SMARTRONIC_ORDER_LOOKUP_API_KEY);
        }
    }

    return '';
}

function normalizeLookupPhone($rawPhone)
{
    $digits = preg_replace('/\D+/', '', trim((string)$rawPhone));
    if (strlen($digits) < 10) {
        return null;
    }

    return substr($digits, -10);
}

function loadLookupDbConnection()
{
    $conn = null;
    $previousBackupMode = getenv('SMARTRONIC_BACKUP_MODE');
    putenv('SMARTRONIC_BACKUP_MODE=1');

    ob_start();
    try {
        foreach ([__DIR__ . '/../config.php', __DIR__ . '/../../config.php', dirname(__DIR__, 2) . '/config.php'] as $path) {
            if (is_file($path)) {
                require $path;
                break;
            }
        }
    } catch (Throwable $e) {
        $conn = null;
    }
    ob_end_clean();

    if ($previousBackupMode === false) {
        putenv('SMARTRONIC_BACKUP_MODE');
    } else {
        putenv('SMARTRONIC_BACKUP_MODE=' . $previousBackupMode);
    }

    return isset($conn) && $conn instanceof mysqli ? $conn : null;
}

function cleanValue($value)
{
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    return $value;
}

function buildDetailsLine(array $row)
{
    $dateAge = describeDateAge(cleanValue($row['date'] ?? null) ?: cleanValue($row['created_at'] ?? null));
    $parts = [
        cleanValue($row['area'] ?? null),
        cleanValue($row['quantity'] ?? null),
        cleanValue($row['product'] ?? null),
        cleanValue($row['storage'] ?? null),
        cleanValue($row['resolution'] ?? null),
        cleanValue($row['Owner'] ?? null),
        $dateAge
    ];

    $parts = array_map(static function ($value) {
        return $value === null ? '' : (string)$value;
    }, $parts);

    return implode(' | ', $parts);
}

function describeDateAge($dateValue)
{
    if (!$dateValue) {
        return '';
    }

    try {
        $date = new DateTime((string)$dateValue);
        $today = new DateTime('today');
    } catch (Exception $e) {
        return '';
    }

    if ($date > $today) {
        $interval = $today->diff($date);
        return formatAgeInterval($interval) . ' from now';
    }

    return formatAgeInterval($date->diff($today));
}

function formatAgeInterval(DateInterval $interval)
{
    if ((int)$interval->y > 0) {
        $parts = [pluralizeAge((int)$interval->y, 'year')];
        if ((int)$interval->m > 0) {
            $parts[] = pluralizeAge((int)$interval->m, 'month');
        }
        return implode(' ', $parts);
    }

    if ((int)$interval->m > 0) {
        $parts = [pluralizeAge((int)$interval->m, 'month')];
        if ((int)$interval->d > 0) {
            $parts[] = pluralizeAge((int)$interval->d, 'day');
        }
        return implode(' ', $parts);
    }

    return pluralizeAge((int)$interval->d, 'day');
}

function pluralizeAge($value, $unit)
{
    return $value . ' ' . $unit . ($value === 1 ? '' : 's');
}
