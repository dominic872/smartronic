<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require '../config.php'; // contains $mysqli = new mysqli(...);
$error = '';


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the cookie exists and has the right value
requireSmartPageAccess('install', $role, $authPages);

// If the user passes the check, the rest of your page code runs below...


// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

function ensureOrdersMapCoordColumns(mysqli $conn): void {
    static $done = false;
    if ($done || $conn->connect_error) return;
    $done = true;

    $needed = [
        'map_lat' => "ALTER TABLE orders ADD COLUMN map_lat DECIMAL(10,7) NULL DEFAULT NULL",
        'map_lng' => "ALTER TABLE orders ADD COLUMN map_lng DECIMAL(10,7) NULL DEFAULT NULL",
        'admin_event_comment' => "ALTER TABLE orders ADD COLUMN admin_event_comment TEXT NULL DEFAULT NULL",
        'city' => "ALTER TABLE orders ADD COLUMN city VARCHAR(40) NOT NULL DEFAULT 'Bangalore'",
        'pending_amount' => "ALTER TABLE orders ADD COLUMN pending_amount DECIMAL(12,2) NOT NULL DEFAULT 0",
    ];

    foreach ($needed as $column => $sql) {
        $safeColumn = $conn->real_escape_string($column);
        $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = '{$safeColumn}'");
        $row = $res ? $res->fetch_assoc() : null;
        if (!$row || (int)$row['c'] === 0) {
            $conn->query($sql);
        }
    }
}

function ensureInstallHistoryTable(mysqli $conn): void {
    static $done = false;
    if ($done || $conn->connect_error) return;
    $done = true;

    $conn->query("CREATE TABLE IF NOT EXISTS install_history (
        id INT NOT NULL AUTO_INCREMENT,
        order_idno VARCHAR(50) NOT NULL,
        order_name VARCHAR(255) NULL DEFAULT NULL,
        action_type VARCHAR(80) NOT NULL,
        action_label VARCHAR(255) NOT NULL,
        action_details LONGTEXT NULL DEFAULT NULL,
        username VARCHAR(255) NULL DEFAULT NULL,
        user_role VARCHAR(64) NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_install_history_order (order_idno),
        KEY idx_install_history_created (created_at),
        KEY idx_install_history_action (action_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureOrdersMapCoordColumns($conn);
ensureInstallHistoryTable($conn);

function installsIsAdminRole(): bool {
    $role = isset($_COOKIE['auth_role']) ? trim((string)$_COOKIE['auth_role']) : '';
    return strtolower($role) === 'admin';
}

function updateOrderAdminEventComment(mysqli $conn, string $id, string $comment): void {
    if ($id === '' || $conn->connect_error || !installsIsAdminRole()) return;
    $stmt = $conn->prepare("UPDATE orders SET admin_event_comment = ? WHERE idno = ?");
    if (!$stmt) return;
    $stmt->bind_param("ss", $comment, $id);
    $stmt->execute();
    $stmt->close();
}

function installHistoryUsername(): string {
    global $nameAssign, $authUsername, $displayName;
    $candidates = [
        $nameAssign ?? '',
        $displayName ?? '',
        $authUsername ?? '',
        $_COOKIE['auth_username'] ?? '',
        $_COOKIE['username'] ?? '',
        $_COOKIE['auth_user'] ?? '',
        $_COOKIE['name'] ?? '',
    ];
    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if ($candidate !== '') return $candidate;
    }
    return 'Unknown';
}

function installHistoryRole(): string {
    global $role;
    $candidate = trim((string)($role ?? ($_COOKIE['auth_role'] ?? '')));
    return $candidate !== '' ? $candidate : 'unknown';
}

function fetchInstallSnapshot(mysqli $conn, string $id): ?array {
    if ($id === '' || $conn->connect_error) return null;
    $sql = "SELECT idno, name, quantity, bullets, dome, storage, monitor, product, area, city, time, date, Owner, technician, helper, `order`, resolution, brand, cam_type, Map, map_lat, map_lng, rack, notes, admin_event_comment, amount_paid, fully_paid, price, extras, pending_amount, pdf_sent, record_status FROM orders WHERE idno = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function logInstallHistory(mysqli $conn, string $id, string $actionType, string $actionLabel, array $details = [], ?array $snapshot = null): void {
    try {
        if ($id === '' || $conn->connect_error) return;
        $snapshot = $snapshot ?: fetchInstallSnapshot($conn, $id);
        $orderName = isset($snapshot['name']) ? trim((string)$snapshot['name']) : '';
        if ($orderName === '' && isset($details['name'])) $orderName = trim((string)$details['name']);
        $detailsJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $username = installHistoryUsername();
        $userRole = installHistoryRole();
        $stmt = $conn->prepare("INSERT INTO install_history (order_idno, order_name, action_type, action_label, action_details, username, user_role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) return;
        $stmt->bind_param("sssssss", $id, $orderName, $actionType, $actionLabel, $detailsJson, $username, $userRole);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Install history log failed: ' . $e->getMessage());
    }
}

function installHistoryValue($value): string {
    if ($value === null) return '';
    return trim((string)$value);
}

function buildInstallChangedFields(?array $before, ?array $after): array {
    if (!$before || !$after) return [];
    $fields = [
        'name' => 'Name',
        'quantity' => 'Cameras',
        'bullets' => 'Bullets',
        'dome' => 'Dome',
        'storage' => 'HDD',
        'monitor' => 'Monitor',
        'product' => 'Type',
        'area' => 'Location',
        'city' => 'City',
        'time' => 'Time',
        'Owner' => 'Owner',
        'technician' => 'Technician',
        'helper' => 'Helper',
        'order' => 'Order',
        'resolution' => 'Resolution',
        'brand' => 'Brand',
        'cam_type' => 'Camera type',
        'Map' => 'Map',
        'map_lat' => 'Map latitude',
        'map_lng' => 'Map longitude',
        'rack' => 'Rack',
        'notes' => 'Notes',
    ];
    $changed = [];
    foreach ($fields as $field => $label) {
        $old = installHistoryValue($before[$field] ?? '');
        $new = installHistoryValue($after[$field] ?? '');
        if ($old !== $new) {
            $changed[$label] = ['old' => $old, 'new' => $new];
        }
    }
    return $changed;
}

function logInstallSaveHistory(mysqli $conn, string $id, ?array $before, ?array $after): void {
    if (!$after) return;
    if (!$before) {
        logInstallHistory($conn, $id, 'created', 'Created install', [
            'name' => $after['name'] ?? '',
            'date' => normalizeInstallDateInput($after['date'] ?? null),
            'time' => $after['time'] ?? '',
        ], $after);
        return;
    }

    $oldDate = normalizeInstallDateInput($before['date'] ?? null) ?: '';
    $newDate = normalizeInstallDateInput($after['date'] ?? null) ?: '';
    if ($oldDate !== $newDate) {
        logInstallHistory($conn, $id, 'moved', 'Moved install', [
            'old_date' => $oldDate,
            'new_date' => $newDate,
            'old_time' => $before['time'] ?? '',
            'new_time' => $after['time'] ?? '',
        ], $after);
    }

    $changed = buildInstallChangedFields($before, $after);
    if (!empty($changed)) {
        logInstallHistory($conn, $id, 'edited', 'Edited install', ['changed' => $changed], $after);
    }

    $oldComment = installHistoryValue($before['admin_event_comment'] ?? '');
    $newComment = installHistoryValue($after['admin_event_comment'] ?? '');
    if ($oldComment !== $newComment) {
        logInstallHistory($conn, $id, $newComment === '' ? 'admin_comment_deleted' : 'admin_comment_updated', $newComment === '' ? 'Admin comment deleted' : 'Admin comment updated', [
            'old_comment' => $oldComment,
            'new_comment' => $newComment,
        ], $after);
    }
}

function normalizeInstallDateInput($date): ?string {
    if ($date === null) {
        return null;
    }
    $date = trim((string)$date);
    if ($date === '' || strtolower($date) === 'null' || $date === '0000-00-00') {
        return null;
    }
    return $date;
}

function isPastInstallDateForNonAdmin($date): bool {
    $date = normalizeInstallDateInput($date);
    if ($date === null || installsIsAdminRole()) {
        return false;
    }

    $target = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('Asia/Kolkata'));
    if (!$target || $target->format('Y-m-d') !== $date) {
        return false;
    }

    $today = new DateTime('today', new DateTimeZone('Asia/Kolkata'));
    return $target < $today;
}

function isRestrictedPastInstallMoveForNonAdmin(mysqli $conn, string $id, $newDate): bool {
    $newDate = normalizeInstallDateInput($newDate);
    if ($newDate === null || installsIsAdminRole()) {
        return false;
    }

    if (!isPastInstallDateForNonAdmin($newDate)) {
        return false;
    }

    $currentDate = null;
    if ($id !== '') {
        $stmt = $conn->prepare("SELECT `date` FROM orders WHERE idno = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $row = $result ? $result->fetch_assoc() : null;
                $currentDate = $row && array_key_exists('date', $row) ? $row['date'] : null;
            }
            $stmt->close();
        }
    }

    return normalizeInstallDateInput($currentDate) !== $newDate;
}

function findDompdfAutoloadPath(): ?string {
    $candidates = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/../../admin/vendor/autoload.php',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function streamInstallInvoicePdf(string $html, string $filename = 'Smartronic_Invoice.pdf'): void {
    $autoloadPath = findDompdfAutoloadPath();
    if ($autoloadPath === null) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Dompdf autoload not found.';
        exit;
    }

    require_once $autoloadPath;

    $optionsClass = 'Dompdf\\Options';
    $dompdfClass = 'Dompdf\\Dompdf';

    if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Dompdf is not available.';
        exit;
    }

    $cleanHtml = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $documentHtml = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body style="margin:0;padding:25px;background:#fff;font-family:\'DejaVu Sans\',sans-serif;">'
        . ($cleanHtml ?: '')
        . '</body></html>';

    $options = new $optionsClass();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('tempDir', sys_get_temp_dir());

    $dompdf = new $dompdfClass($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($documentHtml, 'UTF-8');
    $dompdf->render();

    $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
    header('Content-Type: application/pdf');
    $dompdf->stream($safeFilename ?: 'Smartronic_Invoice.pdf', ['Attachment' => 1]);
    exit;
}

function streamInstallInvoiceWord(string $html, string $filename = 'Smartronic_Invoice.doc'): void {
    if (!installsIsAdminRole()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Only admin users can download Word invoices']);
        exit;
    }

    $cleanHtml = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $documentHtml = '<!DOCTYPE html>'
        . '<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">'
        . '<head><meta charset="UTF-8"><title>Smartronic Invoice</title>'
        . '<style>@page WordSection1{size:595.3pt 841.9pt;margin:1.2cm;}div.WordSection1{page:WordSection1;}body{margin:0;padding:25px;background:#fff;font-family:\'DejaVu Sans\',Arial,sans-serif;}</style>'
        . '</head><body><div class="WordSection1">'
        . ($cleanHtml ?: '')
        . '</div></body></html>';

    $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . ($safeFilename ?: 'Smartronic_Invoice.doc') . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $documentHtml;
    exit;
}

// Handle query string parameters - this is legacy code that should be removed
// as it conflicts with the proper API endpoints below
/*
$queryParams = $_GET;
$existingRecord = null;

if (!empty($queryParams['id']) && !isset($queryParams['render_invoice']) && !isset($queryParams['get_extras']) && !isset($queryParams['render_material'])) {

    if (!$conn->connect_error) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE idno = ?");
        $stmt->bind_param("s", $queryParams['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $existingRecord = $result->fetch_assoc();
            echo json_encode($existingRecord);
        }
        $stmt->close();
        $conn->close();
    }
}
*/




$isApiCall = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) || $_SERVER['REQUEST_METHOD'] === 'POST';

// Debug: Log API call detection
error_log("API Call Detection - Method: " . $_SERVER['REQUEST_METHOD'] . ", X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set') . ", Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . ", isApiCall: " . ($isApiCall ? 'true' : 'false'));

if (isset($_GET['get_leaves_bulk'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $rawDates = isset($_GET['dates']) ? (string)$_GET['dates'] : '';
    $dates = array_values(array_unique(array_filter(array_map('trim', explode(',', $rawDates)), function ($date) {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    })));

    if (empty($dates)) {
        echo json_encode(['success' => true, 'leaves' => []]);
        exit;
    }

    $dates = array_slice($dates, 0, 45);
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    $types = str_repeat('s', count($dates));
    $sql = "
        SELECT leaves.leave_date, users.fullname, leaves.reason
        FROM leaves
        JOIN users ON users.id = leaves.user_id
        WHERE leaves.leave_date IN ($placeholders)
        ORDER BY leaves.leave_date ASC, users.fullname ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Leaves query prepare failed',
            'error_number' => (int)$conn->errno,
            'error' => $conn->error,
            'source_line' => __LINE__
        ]);
        exit;
    }

    $stmt->bind_param($types, ...$dates);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Leaves query execute failed',
            'error_number' => (int)$stmt->errno,
            'error' => $stmt->error,
            'source_line' => __LINE__
        ]);
        exit;
    }

    $result = $stmt->get_result();
    $leaves = [];
    while ($row = $result->fetch_assoc()) {
        $date = (string)($row['leave_date'] ?? '');
        if ($date === '') continue;
        if (!isset($leaves[$date])) $leaves[$date] = [];
        $leaves[$date][] = [
            'fullname' => $row['fullname'] ?? '',
            'reason' => $row['reason'] ?? ''
        ];
    }

    echo json_encode(['success' => true, 'leaves' => $leaves]);
    exit;
}

if (isset($_GET['get_notes_bulk'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $rawDates = isset($_GET['dates']) ? (string)$_GET['dates'] : '';
    $dates = array_values(array_unique(array_filter(array_map('trim', explode(',', $rawDates)), function ($date) {
        return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    })));

    if (empty($dates)) {
        echo json_encode(['success' => true, 'notes' => []]);
        exit;
    }

    $notesColumnExists = function (string $column) use ($conn): bool {
        $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = ?");
        if (!$stmt) return false;
        $stmt->bind_param("s", $column);
        if (!$stmt->execute()) return false;
        $row = $stmt->get_result()->fetch_assoc();
        return ((int)($row['c'] ?? 0)) > 0;
    };

    $dates = array_slice($dates, 0, 45);
    $placeholders = implode(',', array_fill(0, count($dates), '?'));
    $types = str_repeat('s', count($dates));
    $optionalSelect = [
        $notesColumnExists('is_done') ? 'is_done' : '0 AS is_done',
        $notesColumnExists('done_by') ? 'done_by' : 'NULL AS done_by',
        $notesColumnExists('done_at') ? 'done_at' : 'NULL AS done_at',
        $notesColumnExists('assigned_to') ? 'assigned_to' : 'NULL AS assigned_to',
        $notesColumnExists('assigned_history') ? 'assigned_history' : 'NULL AS assigned_history',
        $notesColumnExists('reopen_reason') ? 'reopen_reason' : 'NULL AS reopen_reason',
        $notesColumnExists('reopened_by') ? 'reopened_by' : 'NULL AS reopened_by',
        $notesColumnExists('reopened_at') ? 'reopened_at' : 'NULL AS reopened_at',
        $notesColumnExists('reopen_history') ? 'reopen_history' : 'NULL AS reopen_history',
        $notesColumnExists('priority_level') ? 'priority_level' : '0 AS priority_level'
    ];
    $optionalSql = implode(', ', $optionalSelect);
    $sql = "
        SELECT id, type, title, description, date, sort_order, username, created_at, $optionalSql
        FROM notes
        WHERE date IN ($placeholders)
        ORDER BY date ASC, sort_order ASC, id ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Notes query prepare failed',
            'error_number' => (int)$conn->errno,
            'error' => $conn->error,
            'source_line' => __LINE__
        ]);
        exit;
    }

    $stmt->bind_param($types, ...$dates);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Notes query execute failed',
            'error_number' => (int)$stmt->errno,
            'error' => $stmt->error,
            'source_line' => __LINE__
        ]);
        exit;
    }

    $result = $stmt->get_result();
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $date = (string)($row['date'] ?? '');
        if ($date === '') continue;
        if (!isset($notes[$date])) $notes[$date] = [];
        $notes[$date][] = $row;
    }

    echo json_encode(['success' => true, 'notes' => $notes]);
    exit;
}

// Lightweight GET endpoint to fetch extras for a given order idno
if (isset($_GET['get_extras']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT extras, pending_amount FROM orders WHERE idno = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Query failed']); exit; }
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode([
        'success' => true,
        'extras' => $row ? ($row['extras'] ?? '') : '',
        'pending_amount' => $row ? ($row['pending_amount'] ?? 0) : 0
    ]);
    exit;
}

// Lightweight GET endpoint to fetch payment data for a given order idno
if (isset($_GET['get_payment']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT amount_paid, fully_paid, price, pending_amount FROM orders WHERE idno = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Query failed']); exit; }
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode([
        'success'=>true,
        'amount_paid' => $row ? $row['amount_paid'] : null,
        'fully_paid' => $row ? (bool)$row['fully_paid'] : false,
        'actual_amount' => $row ? ($row['price'] ?? null) : null,
        'pending_amount' => $row ? ($row['pending_amount'] ?? 0) : 0
    ]);
    exit;
}

// Lightweight GET endpoint to fetch install history
if (isset($_GET['get_history'])) {
    header('Content-Type: application/json');
    if (!installsIsAdminRole()) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Only admin can view install history']); exit; }
    if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }

    $id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $limit = max(1, min(300, $limit));
    $rows = [];

    if ($id !== '') {
        $stmt = $conn->prepare("SELECT id, order_idno, order_name, action_type, action_label, action_details, username, user_role, created_at FROM install_history WHERE order_idno = ? ORDER BY created_at DESC, id DESC LIMIT ?");
        if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'History query failed']); exit; }
        $stmt->bind_param("si", $id, $limit);
    } else {
        $stmt = $conn->prepare("SELECT id, order_idno, order_name, action_type, action_label, action_details, username, user_role, created_at FROM install_history ORDER BY created_at DESC, id DESC LIMIT ?");
        if (!$stmt) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'History query failed']); exit; }
        $stmt->bind_param("i", $limit);
    }

    if (!$stmt->execute()) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'History query failed']); exit; }
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $details = [];
        if (!empty($row['action_details'])) {
            $decoded = json_decode((string)$row['action_details'], true);
            if (is_array($decoded)) $details = $decoded;
        }
        $row['details'] = $details;
        unset($row['action_details']);
        $rows[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'history' => $rows]);
    exit;
}

// GET endpoint for individual record by ID
if (isset($_GET['id']) && !isset($_GET['get_extras']) && !isset($_GET['get_payment']) && !isset($_GET['get_history']) && !isset($_GET['render_invoice']) && !isset($_GET['render_material'])) {
    header('Content-Type: application/json');
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }
    
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE idno = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        // Format data to match the expected structure
        $data = [
            'id' => $row['idno'],
            'phone' => $row['phone'],
            'name' => $row['name'],
            'cams' => $row['quantity'],
            'bullets' => $row['bullets'],
            'dome' => $row['dome'],
            'hdd' => $row['storage'],
            'monitor' => $row['monitor'],
            'type' => $row['product'],
            'location' => $row['area'],
            'city' => isset($row['city']) && $row['city'] !== '' ? $row['city'] : 'Bangalore',
            'time' => $row['time'],
            'date' => $row['date'],
            'owner' => $row['Owner'],
            'technician' => $row['technician'],
            'helper' => $row['helper'],
            'order' => $row['order'],
            'resolution' => $row['resolution'],
            'brand' => isset($row['brand']) ? $row['brand'] : '',
            'cam_type' => isset($row['cam_type']) ? $row['cam_type'] : '',
            'map' => $row['Map'],
            'map_lat' => isset($row['map_lat']) ? $row['map_lat'] : null,
            'map_lng' => isset($row['map_lng']) ? $row['map_lng'] : null,
            'rack' => $row['rack'],
            'notes' => isset($row['notes']) ? $row['notes'] : '',
            'admin_event_comment' => isset($row['admin_event_comment']) ? $row['admin_event_comment'] : '',
            'pdf_sent' => isset($row['pdf_sent']) ? $row['pdf_sent'] : '',
            'pending_amount' => isset($row['pending_amount']) ? $row['pending_amount'] : 0
        ];
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Record not found']);
    }
    exit;
}

// Payment update endpoint
if ($isApiCall && $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    header('Content-Type: application/json');
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['deleteAdminEventComment']) && $data['deleteAdminEventComment'] === true && !empty($data['id'])) {
        if (!installsIsAdminRole()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only admin can delete admin event comments']);
            exit;
        }

        $idno = trim((string)$data['id']);
        $beforeSnapshot = fetchInstallSnapshot($conn, $idno);
        $blankComment = '';
        $stmt = $conn->prepare("UPDATE orders SET admin_event_comment = ? WHERE idno = ?");
        $stmt->bind_param("ss", $blankComment, $idno);

        if ($stmt->execute()) {
            logInstallHistory($conn, $idno, 'admin_comment_deleted', 'Admin comment deleted', [
                'old_comment' => $beforeSnapshot['admin_event_comment'] ?? '',
                'new_comment' => '',
            ], $beforeSnapshot);
            echo json_encode(['success' => true, 'message' => 'Admin comment deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete admin comment']);
        }
        exit;
    }
    
    if (isset($data['updateActualAmount']) && $data['updateActualAmount'] === true && !empty($data['id'])) {
        if (!installsIsAdminRole()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only admin can update actual amount']);
            exit;
        }

        $idno = trim((string)$data['id']);
        $actualRaw = isset($data['actual_amount']) ? trim((string)$data['actual_amount']) : '';
        $actualClean = preg_replace('/[^0-9.]/', '', $actualRaw);
        $actualAmount = $actualClean !== '' ? (float)$actualClean : -1;
        if ($actualAmount < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Enter a valid actual amount']);
            exit;
        }

        $actualValue = number_format($actualAmount, 2, '.', '');
        $beforeSnapshot = fetchInstallSnapshot($conn, $idno);
        $stmt = $conn->prepare("UPDATE orders SET price = ? WHERE idno = ?");
        $stmt->bind_param("ss", $actualValue, $idno);

        if ($stmt->execute()) {
            $afterSnapshot = fetchInstallSnapshot($conn, $idno);
            logInstallHistory($conn, $idno, 'actual_amount_updated', 'Actual amount updated', [
                'old_amount' => $beforeSnapshot['price'] ?? '',
                'new_amount' => $actualValue,
            ], $afterSnapshot ?: $beforeSnapshot);
            echo json_encode(['success' => true, 'message' => 'Actual amount updated successfully', 'actual_amount' => $actualValue]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update actual amount']);
        }
        exit;
    }

    if (isset($data['updatePendingPayment']) && $data['updatePendingPayment'] === true && !empty($data['id'])) {
        if (!installsIsAdminRole()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only admin can update pending payment']);
            exit;
        }

        $idno = trim((string)$data['id']);
        $pendingAmount = isset($data['pending_amount']) ? max(0, (float)$data['pending_amount']) : 0.0;
        $fullyPaidAfter = $pendingAmount <= 0 ? 1 : 0;
        $beforeSnapshot = fetchInstallSnapshot($conn, $idno);
        $stmt = $conn->prepare("UPDATE orders SET pending_amount = ?, fully_paid = ? WHERE idno = ?");
        $stmt->bind_param("dis", $pendingAmount, $fullyPaidAfter, $idno);

        if ($stmt->execute()) {
            $afterSnapshot = fetchInstallSnapshot($conn, $idno);
            logInstallHistory($conn, $idno, $pendingAmount > 0 ? 'pending_payment_updated' : 'pending_payment_cleared', $pendingAmount > 0 ? 'Pending payment updated' : 'Pending payment cleared', [
                'old_pending_amount' => $beforeSnapshot['pending_amount'] ?? '',
                'new_pending_amount' => $pendingAmount,
            ], $afterSnapshot ?: $beforeSnapshot);
            echo json_encode(['success' => true, 'message' => 'Pending payment updated successfully', 'pending_amount' => $pendingAmount]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update pending payment']);
        }
        exit;
    }

    if (isset($data['updatePayment']) && $data['updatePayment'] === true && !empty($data['id'])) {
        $idno = $data['id'];
        $amount_paid = isset($data['amount_paid']) ? floatval($data['amount_paid']) : null;
        $fully_paid = isset($data['fully_paid']) ? (bool)$data['fully_paid'] : false;
        
        $beforeSnapshot = fetchInstallSnapshot($conn, (string)$idno);
        $pendingAmount = $fully_paid ? 0.0 : (float)($beforeSnapshot['pending_amount'] ?? 0);
        $stmt = $conn->prepare("UPDATE orders SET amount_paid = ?, fully_paid = ?, pending_amount = ? WHERE idno = ?");
        $stmt->bind_param("dids", $amount_paid, $fully_paid, $pendingAmount, $idno);
        
        if ($stmt->execute()) {
            $afterSnapshot = fetchInstallSnapshot($conn, (string)$idno);
            logInstallHistory($conn, (string)$idno, 'payment_updated', 'Payment updated', [
                'old_amount_paid' => $beforeSnapshot['amount_paid'] ?? '',
                'new_amount_paid' => $amount_paid,
                'old_fully_paid' => isset($beforeSnapshot['fully_paid']) ? (bool)$beforeSnapshot['fully_paid'] : false,
                'new_fully_paid' => $fully_paid,
                'old_pending_amount' => $beforeSnapshot['pending_amount'] ?? '',
                'new_pending_amount' => $pendingAmount,
            ], $afterSnapshot ?: $beforeSnapshot);
            echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update payment']);
        }
        exit;
    }
}

if ($isApiCall && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log the received data
    error_log("Received POST data: " . json_encode($data));
    
    if (!$data) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'No data received or invalid JSON']);
        exit;
    }

    if (isset($data['invoicePdf']) && $data['invoicePdf'] === true) {
        $invoiceHtml = isset($data['html']) ? (string)$data['html'] : '';
        $orderId = isset($data['id']) ? trim((string)$data['id']) : 'invoice';
        if ($invoiceHtml === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invoice HTML is required']);
            exit;
        }

        if ($orderId !== '' && !$conn->connect_error) {
            $pdfStmt = $conn->prepare("UPDATE orders SET pdf_sent = 'yes' WHERE idno = ?");
            if ($pdfStmt) {
                $pdfStmt->bind_param("s", $orderId);
                if ($pdfStmt->execute()) {
                    logInstallHistory($conn, $orderId, 'pdf_sent', 'PDF sent/generated', [
                        'filename' => 'Smartronic_Invoice_' . $orderId . '.pdf',
                    ]);
                }
                $pdfStmt->close();
            }
        }

        streamInstallInvoicePdf($invoiceHtml, 'Smartronic_Invoice_' . $orderId . '.pdf');
    }

    if (isset($data['invoiceWord']) && $data['invoiceWord'] === true) {
        $invoiceHtml = isset($data['html']) ? (string)$data['html'] : '';
        $orderId = isset($data['id']) ? trim((string)$data['id']) : 'invoice';
        if ($invoiceHtml === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invoice HTML is required']);
            exit;
        }

        streamInstallInvoiceWord($invoiceHtml, 'Smartronic_Invoice_' . $orderId . '.doc');
    }

    header('Content-Type: application/json');

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }

    // Special path: update only the 'extras' column for the given idno
    if (isset($data['extrasOnly']) && $data['extrasOnly'] === true && !empty($data['id'])) {
        $idno = $data['id'];
        $extras = isset($data['extras']) ? $data['extras'] : '';
        $pendingAmount = 0.0;
        $extrasPayload = json_decode((string)$extras, true);
        if (is_array($extrasPayload) && isset($extrasPayload['pendingWarranty']) && is_array($extrasPayload['pendingWarranty'])) {
            $pending = $extrasPayload['pendingWarranty'];
            $pendingAmount = isset($pending['amount']) ? max(0, (float)$pending['amount']) : 0.0;
        }
        $beforeSnapshot = fetchInstallSnapshot($conn, (string)$idno);
        $stmt = $conn->prepare("UPDATE orders SET extras = ?, pending_amount = ? WHERE idno = ?");
        $stmt->bind_param("sds", $extras, $pendingAmount, $idno);
        if ($stmt->execute()) {
            $afterSnapshot = fetchInstallSnapshot($conn, (string)$idno);
            logInstallHistory($conn, (string)$idno, 'extras_updated', 'Extras updated', [
                'old_extras' => $beforeSnapshot['extras'] ?? '',
                'new_extras' => $extras,
                'old_pending_amount' => $beforeSnapshot['pending_amount'] ?? '',
                'new_pending_amount' => $pendingAmount,
            ], $afterSnapshot ?: $beforeSnapshot);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save extras']);
        }
        exit;
    }
    $id = $data['id'] ?? '';
    $name = $data['name'] ?? '';
    $cams = (int)($data['cams'] ?? 0);
    $bullets = (int)($data['bullets'] ?? 0);
    $dome = (int)($data['dome'] ?? 0);
    $hdd = $data['hdd'] ?? '';
    $monitor = $data['monitor'] ?? '';
    $type = $data['type'] ?? '';
    $location = $data['location'] ?? '';
    $city = isset($data['city']) && strtolower(trim((string)$data['city'])) === 'chennai' ? 'Chennai' : 'Bangalore';
    $time = $data['time'] ?? '';
    $date = array_key_exists('date', $data) ? $data['date'] : null;
    if (is_string($date)) {
        $t = trim($date);
        if ($t === '' || strtolower($t) === 'null') $date = null;
    }
    if (isRestrictedPastInstallMoveForNonAdmin($conn, (string)$id, $date)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only admin can move an event to a past date.']);
        exit;
    }
    $owner = $data['owner'] ?? '';
    $technician = $data['technician'] ?? '';
    $helper = $data['helper'] ?? '';
    $order = (int)($data['order'] ?? 0);
    $resolution = $data['resolution'] ?? '';
    $brand = $data['brand'] ?? '';
	    $cam_type = $data['cam_type'] ?? '';
	    $map = $data['map'] ?? '';
	    $mapLat = isset($data['map_lat']) && $data['map_lat'] !== '' ? (string)$data['map_lat'] : '';
	    $mapLng = isset($data['map_lng']) && $data['map_lng'] !== '' ? (string)$data['map_lng'] : '';
	    $hasMapLat = $mapLat !== '' ? 1 : 0;
	    $hasMapLng = $mapLng !== '' ? 1 : 0;
	    $mapLatValue = $hasMapLat ? (float)$mapLat : 0.0;
	    $mapLngValue = $hasMapLng ? (float)$mapLng : 0.0;
	    $rack = $data['rack'] ?? '';
	    $notes = isset($data['notes']) ? $data['notes'] : '';
	    $adminEventComment = installsIsAdminRole() && isset($data['admin_event_comment']) ? trim((string)$data['admin_event_comment']) : '';
    
    $beforeSnapshot = fetchInstallSnapshot($conn, (string)$id);

    // Check if record exists
    $checkStmt = $conn->prepare("SELECT idno FROM orders WHERE idno = ? LIMIT 1");
    $checkStmt->bind_param("s", $id);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
	    if ($exists) {
	        // Update existing record
	        $stmt = $conn->prepare("UPDATE orders SET name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, city=?, time=?, date=?, Owner=?, technician=?, helper=?, `order`=?, resolution=?, brand=?, cam_type=?, Map=?, map_lat=IF(? = 1, ?, NULL), map_lng=IF(? = 1, ?, NULL), rack=?, notes=? WHERE idno=?");
	        $stmt->bind_param("siiissssssssssissssididsss", $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $city, $time, $date, $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $hasMapLat, $mapLatValue, $hasMapLng, $mapLngValue, $rack, $notes, $id);
	    } else {
	        // Insert new record
	        $stmt = $conn->prepare("INSERT INTO orders (idno, name, quantity, bullets, dome, storage, monitor, product, area, city, time, date, Owner, technician, helper, `order`, resolution, brand, cam_type, Map, map_lat, map_lng, rack, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = 1, ?, NULL), IF(? = 1, ?, NULL), ?, ?)");
	        $stmt->bind_param("ssiiissssssssssissssididss", $id, $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $city, $time, $date, $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $hasMapLat, $mapLatValue, $hasMapLng, $mapLngValue, $rack, $notes);
	    }
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("MySQL Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    updateOrderAdminEventComment($conn, (string)$id, $adminEventComment);
    $afterSnapshot = fetchInstallSnapshot($conn, (string)$id);
    logInstallSaveHistory($conn, (string)$id, $beforeSnapshot, $afterSnapshot);
    
    echo json_encode(['status' => 'ok', 'message' => 'Install saved successfully']);
    exit;
}

if ($isApiCall && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }

    $result = $conn->query("SELECT * FROM orders");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
    exit;
}

if ($isApiCall && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    header('Content-Type: application/json');

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';

    if ($id) {
        $beforeSnapshot = fetchInstallSnapshot($conn, (string)$id);
        $role = isset($_COOKIE['auth_role']) ? $_COOKIE['auth_role'] : '';
        if (strtolower(trim((string)$role)) !== 'admin') {
            $check = $conn->prepare("SELECT `date` FROM orders WHERE idno = ? LIMIT 1");
            $check->bind_param("s", $id);
            $check->execute();
            $res = $check->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $check->close();
            $dateVal = $row && array_key_exists('date', $row) ? $row['date'] : null;
            $dateTrim = is_string($dateVal) ? trim($dateVal) : '';
            $isUnscheduled = ($dateVal === null) || ($dateTrim === '') || ($dateTrim === '0000-00-00');
            if (!$isUnscheduled) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this.']);
                exit;
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET record_status = 'DELETED' WHERE idno = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            logInstallHistory($conn, (string)$id, 'deleted', 'Deleted install', [
                'record_status' => 'DELETED',
            ], $beforeSnapshot);
            echo json_encode(['success' => true, 'message' => 'Record marked as deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update record']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID is required']);
    }
    exit;
}

// Fallback POST handler (in case API detection fails)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApiCall) {
    error_log("Fallback POST handler triggered");
    header('Content-Type: application/json');
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No data received or invalid JSON']);
        exit;
    }
    
    // Use the same logic as the main POST handler
    $id = $data['id'] ?? '';
    $name = $data['name'] ?? '';
    $cams = (int)($data['cams'] ?? 0);
    $bullets = (int)($data['bullets'] ?? 0);
    $dome = (int)($data['dome'] ?? 0);
    $hdd = $data['hdd'] ?? '';
    $monitor = $data['monitor'] ?? '';
    $type = $data['type'] ?? '';
    $location = $data['location'] ?? '';
    $city = isset($data['city']) && strtolower(trim((string)$data['city'])) === 'chennai' ? 'Chennai' : 'Bangalore';
    $time = $data['time'] ?? '';
    $date = array_key_exists('date', $data) ? $data['date'] : null;
    if (is_string($date)) {
        $t = trim($date);
        if ($t === '' || strtolower($t) === 'null') $date = null;
    }
    if (isRestrictedPastInstallMoveForNonAdmin($conn, (string)$id, $date)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only admin can move an event to a past date.']);
        exit;
    }
    $owner = $data['owner'] ?? '';
    $technician = $data['technician'] ?? '';
    $helper = $data['helper'] ?? '';
    $order = (int)($data['order'] ?? 0);
    $resolution = $data['resolution'] ?? '';
    $brand = $data['brand'] ?? '';
    $cam_type = $data['cam_type'] ?? '';
    $map = $data['map'] ?? '';
    $mapLat = isset($data['map_lat']) && $data['map_lat'] !== '' ? (string)$data['map_lat'] : '';
    $mapLng = isset($data['map_lng']) && $data['map_lng'] !== '' ? (string)$data['map_lng'] : '';
    $rack = $data['rack'] ?? '';
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $adminEventComment = installsIsAdminRole() && isset($data['admin_event_comment']) ? trim((string)$data['admin_event_comment']) : '';
    
    $beforeSnapshot = fetchInstallSnapshot($conn, (string)$id);

    // Check if record exists
    $checkStmt = $conn->prepare("SELECT idno FROM orders WHERE idno = ? LIMIT 1");
    $checkStmt->bind_param("s", $id);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE orders SET name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, city=?, time=?, date=?, Owner=?, technician=?, helper=?, `order`=?, resolution=?, brand=?, cam_type=?, Map=?, map_lat=NULLIF(?, ''), map_lng=NULLIF(?, ''), rack=?, notes=? WHERE idno=?");
        $stmt->bind_param("siiissssssssssisssssssss", $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $city, $time, $date, $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $mapLat, $mapLng, $rack, $notes, $id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO orders (idno, name, quantity, bullets, dome, storage, monitor, product, area, city, time, date, Owner, technician, helper, `order`, resolution, brand, cam_type, Map, map_lat, map_lng, rack, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), ?, ?)");
        $stmt->bind_param("ssiiissssssssssissssssss", $id, $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $city, $time, $date, $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $mapLat, $mapLng, $rack, $notes);
    }
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("MySQL Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    updateOrderAdminEventComment($conn, (string)$id, $adminEventComment);
    $afterSnapshot = fetchInstallSnapshot($conn, (string)$id);
    logInstallSaveHistory($conn, (string)$id, $beforeSnapshot, $afterSnapshot);
    
    echo json_encode(['status' => 'ok', 'message' => 'Install saved successfully']);
    exit;
}

?> 

<?php
// Server-rendered invoice partial
if (isset($_GET['render_invoice'])) {
  $invoiceParams = $_GET;
  unset($invoiceParams['render_invoice']);
  $_GET = $invoiceParams;
  include '../../invoice/index.php';
  exit;
}

// Server-rendered material partial (order items)
if (isset($_GET['render_material'])) {
  $materialParams = $_GET;
  unset($materialParams['render_material']);
  $_GET = $materialParams;
  include '../order_items_2.php';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SM Installs | Calendar</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/installs.css?v=20260706a">
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=places,geometry&loading=async"></script>
  <script src="../js/openlocationcode.js"></script>
  <!-- Transaction Scanner Dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" crossorigin="anonymous"></script>
  <script src="../../invoice/scanner.js"></script>
  <script>
    window.INSTALLS_WA_USERS = <?php
      $usersFile = dirname(__DIR__) . '/users.json';
      $waUsers = [];
      if (is_file($usersFile)) {
        $rawUsers = json_decode(file_get_contents($usersFile), true);
        if (is_array($rawUsers)) {
          foreach ($rawUsers as $u) {
            if (!is_array($u)) continue;
            $name = isset($u['name']) ? trim((string)$u['name']) : '';
            if ($name === '' && isset($u['username'])) $name = trim((string)$u['username']);
            $phone = isset($u['phone']) ? trim((string)$u['phone']) : '';
            if ($name === '' || $phone === '') continue;
            $waUsers[] = ['name' => $name, 'phone' => $phone];
          }
        }
      }
      echo json_encode($waUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>;
  </script>
  <style>
    @media (max-width: 768px) {
      #main-logo {
        content: url('https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png');
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>
<body>
  <div class="controls">
    <div class="logo-section">
      <a href="/admin_v2/smart/" class="custom-logo-link" rel="home" aria-current="page">
        <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
      </a>
      <?php echo "<h2>Hello, $nameAssign!</h2>";?>
    </div>

  </div>

  <div class="calendar">
    <div class="weekdays">
      <div>Mon</div>
      <div>Tue</div>
      <div>Wed</div>
      <div>Thu</div>
      <div>Fri</div>
      <div>Sat</div>
      <div>Sun</div>
    </div>
    <div id="calendarWeeks" class="weeks"></div>
  </div>

  

  <!-- Records Modal -->
  <div class="popup2" id="recordsModal">
    
    <div id="recordsContent" style="max-height: 400px; overflow-y: auto;">
      <!-- Records will be populated here -->
      </div>
    
</div>

<div id="order-popup"  style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 9999; justify-content: center; align-items: center;">
  <div id="install-popup" style="background: white; width: 70%; height: 90%; position: relative; box-shadow: 0 0 10px black; display: flex; flex-direction: column;">
    <style>
      @media (max-width: 768px) {
        #install-popup {
          width: 100% !important;
          height: 100% !important;
          top: 0 !important;
          left: 0 !important;
          border-radius: 0 !important;
        }
        #order-popup { align-items: stretch !important; }
      }
      
      /* Prevent body scroll when popup is open */
      body.popup-open {
        overflow: hidden;
        height: 100vh;
      }
      
      /* Collapsible section styling */
      .collapsible-section .section-header:hover {
        background: rgba(0,0,0,0.02);
      }
      
      .collapsible-section .section-content {
        transition: all 0.3s ease;
      }

      .paylink-collapsible-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 14px 0 10px;
        cursor: pointer;
        color: #1e40af;
        font-weight: 700;
      }

      .paylink-caret {
        margin-left: auto;
        transition: transform 0.3s ease;
      }

      .paylink-panel {
        margin-bottom: 24px;
        display: none;
      }

      .paylink-form-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
        padding: 18px;
      }

      .paylink-form-title {
        margin: 0 0 12px;
        font-weight: 700;
        font-size: 16px;
        color: #111827;
      }

      .paylink-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
      }

      @media (max-width: 900px) {
        .paylink-grid { grid-template-columns: 1fr; }
      }

      .paylink-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 6px;
      }

      .paylink-field input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        outline: none;
        font-size: 14px;
        background: #fff;
      }

      .paylink-field input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
      }

      .paylink-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 12px;
      }

      .paylink-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid transparent;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
      }

      .paylink-btn-primary {
        background: #2563eb;
        color: #fff;
      }

      .paylink-btn-primary:disabled {
        opacity: 0.65;
        cursor: not-allowed;
      }

      .paylink-btn-flat {
        background: transparent;
        border-color: #e5e7eb;
        color: #374151;
      }

      .paylink-btn-wa {
        background: #16a34a;
        color: #fff;
        text-decoration: none;
      }

      .paylink-status {
        color: #6b7280;
        font-weight: 700;
        font-size: 13px;
      }

      .paylink-result-box {
        margin-top: 14px;
        padding: 12px;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        background: rgba(248, 250, 252, 0.6);
      }

      .paylink-result-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 10px;
      }

      .paylink-result-link {
        word-break: break-all;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 13px;
      }

      .invoice-proforma-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 8px;
      }

      .invoice-proforma-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        color: #374151;
        font-size: 13px;
        cursor: pointer;
      }

      .invoice-proforma-label input[type="checkbox"] {
        width: 16px;
        height: 16px;
        margin: 0;
      }
      
      /* Extras overlay styling */
      #extras-overlay {
        border-left: 3px solid #007bff;
      }
      
      #extras-overlay .extras-card {
        background: #fff;
        border: none;
        border-radius: 0;
        margin: 0;
        padding: 20px;
        height: 100%;
        overflow-y: auto;
      }
      #extras-overlay #profit-content{
        display: none;
      }
      /* Move Payment Details and Final Profit into Overlay */
      #extras-overlay .payment-details-overlay,
      #extras-overlay .profit-details-overlay {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
      }
      
      #extras-overlay .payment-details-overlay h4,
      #extras-overlay .profit-details-overlay h4 {
        margin: 0 0 15px 0;
        color: #495057;
        font-size: 16px;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 8px;
      }
      
      #extras-overlay .extras-form > div {
        margin-bottom: 15px;
        border: 1px solid #e0e0e0 !important;
        border-radius: 8px !important;
        padding: 12px !important;
        background: #f8f9fa !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
      }
      
      #extras-overlay .extras-form > div > div:first-child {
        font-weight: 600 !important;
        margin-bottom: 8px !important;
        color: #495057 !important;
        font-size: 14px !important;
        border-bottom: 1px solid #dee2e6 !important;
        padding-bottom: 6px !important;
      }
      
      #extras-overlay input {
        width: 100% !important;
        padding: 6px 8px !important;
        margin-top: 2px !important;
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        font-size: 12px !important;
        transition: border-color 0.3s ease !important;
      }
      
      #extras-overlay input:focus {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.1rem rgba(0,123,255,.25) !important;
        outline: none !important;
      }
      
      #extras-overlay #ex-save-btn {
        background: #28a745 !important;
        border: none !important;
        color: white !important;
        padding: 8px !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        width: 100% !important;
        transition: background-color 0.3s ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 40px !important;
      }
      
      #extras-overlay #ex-save-btn:hover {
        background: #218838 !important;
      }
      /* Tabs header: prevent global button styles (from material content) from breaking layout */
      .tabs-header {
        display: flex !important;
        align-items: center;
        gap: 6px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
        position: sticky;
        top: 0;
        z-index: 1000;
      }
      .tabs-header .tab-button {
        flex: 1 1 0;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        padding: 12px 10px !important;
        background: transparent !important;
        border: 0 !important;
        color: #555 !important;
        font-weight: 600;
        width: auto !important; /* override material's button { width:100% } */
        box-sizing: border-box;
        cursor: pointer;
      }
      .tabs-header .tab-button:hover { color: #111 !important; }
      .tabs-header .tab-button.active {
        color: #007cba !important;
        position: relative;
      }
      .tabs-header .tab-button.active:after {
        content: '';
        position: absolute;
        left: 10%; right: 10%; bottom: 0;
        height: 3px; background: #007cba; border-radius: 3px 3px 0 0;
      }
      .tabs-header .close-button {
        flex: 0 0 auto;
        width: 40px; height: 40px;
        display: inline-flex; align-items: center; justify-content: center;
        background: transparent; border: none; color: #333; cursor: pointer;
      }
      @media (max-width: 768px) {
        .tabs-header { gap: 4px; }
        .tabs-header .tab-button { padding: 10px 8px !important; font-size: 14px; }
        .tabs-header .close-button { width: 36px; height: 36px; }
      }
      
      /* Ensure global body margin doesn't change due to injected material styles */
      body { margin: 0 !important; }

      .install-history-panel {
        position: fixed;
        top: 90px;
        right: 18px;
        z-index: 100001;
        width: min(390px, calc(100vw - 24px));
        max-height: calc(100vh - 118px);
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-shadow: 0 18px 48px rgba(15,23,42,.24);
        display: none;
        flex-direction: column;
        overflow: hidden;
      }

      .install-history-panel.is-open {
        display: flex;
      }

      .install-history-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 11px 12px;
        background: #111827;
        color: #fff;
        cursor: move;
        user-select: none;
      }

      .install-history-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 14px;
      }

      .install-history-close {
        width: 28px;
        height: 28px;
        border: 0;
        border-radius: 6px;
        background: rgba(255,255,255,.14);
        color: #fff;
        cursor: pointer;
      }

      .install-history-subtitle {
        padding: 9px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        background: #f9fafb;
      }

      .install-history-list {
        padding: 8px;
        overflow: auto;
      }

      .install-history-item {
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        padding: 9px;
        margin-bottom: 8px;
        background: #fff;
      }

      .install-history-item-title {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        font-weight: 800;
        color: #111827;
        font-size: 13px;
      }

      .install-history-meta {
        margin-top: 4px;
        color: #6b7280;
        font-size: 12px;
        line-height: 1.35;
      }

      .install-history-details {
        margin-top: 6px;
        color: #374151;
        font-size: 12px;
        line-height: 1.4;
        word-break: break-word;
      }

      .install-history-empty {
        padding: 18px 10px;
        text-align: center;
        color: #6b7280;
        font-size: 13px;
      }

      /* Keep header controls aligned and owner-stats pinned to right */
      .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
      }
      .controls .logo-section {
        display: inline-flex;
        align-items: center;
        gap: 10px;
      }
      .controls > h2 {
        margin: 0;
      }
      .controls .owner-stats-container {
        flex-grow: 1;
        text-align: right;
      }
      .controls .nav-buttons {
        display: inline-flex;
        gap: 8px;
        align-items: center;
      }

      @media (max-width: 768px) {
        .controls {
          flex-wrap: nowrap;
        }
        .controls .owner-stats-container {
          order: 2;
         
          text-align: right;
        }
        .controls .logo-section {
          order: 1;
        }
        .controls .nav-buttons {
          order: 2;
        }
      }
    </style>
    
    <div class="tabs-header" style="z-index: 9999;">
      <button id="tab-btn-requirement" class="tab-button active" onclick="switchTab('requirement')" style="flex:1; padding: 10px;">Requirement</button>
      <button id="tab-btn-material" class="tab-button" onclick="switchTab('material')" style="flex:1; padding: 10px;">Material</button>
      <button id="tab-btn-invoice" class="tab-button" onclick="switchTab('invoice')" style="flex:1; padding: 10px;">Invoice</button>
      <button onclick="closeOrderPopup()" class="close-button"><i class="fas fa-close" title="close"></i></button>
    </div>

    <div id="tab-requirement" class="tab-content" style="flex:1; overflow: auto; padding: 10px; display: block;">
   
    <div class="popup" id="popupForm">
    <h2>Add/Edit Install</h2>
    <form id="installForm">
      <input type="text" id="id" placeholder="ID" required>
      <input type="text" id="name" placeholder="Name" required>
      <div class="form-row">
        <select id="resolution" required>
          <option value="">Resolution</option>
          <option value="2 MP">2 MP</option>
          <option value="5 MP">5 MP</option>
        </select>
        <select id="brand">
          <option value="">Brand</option>
          <option value="PRAMA" selected>PRAMA</option>
          <option value="SECUREYE">SECUREYE</option>
          <option value="CP PLUS">CP PLUS</option>
          <option value="Hikvision">Hikvision</option>

        </select>
        <select id="cam_type">
          <option value="">Camera Type</option>
          <option value="Normal with mic">Normal with mic</option>
          <option value="Hybrid">Hybrid</option>
          <option value="Full colour">Full colour</option>
        </select>
       
      </div>
      <div class="form-row">
         <input type="number" id="cams" placeholder="Total Cams" required>
        <input type="number" id="bullets" placeholder="Bullets">
        <input type="number" id="dome" placeholder="Dome">
      </div>
      <select id="type">
        <option value="">Select Type</option>
        <option value="DVR">DVR</option>
        <option value="NVR">NVR</option>
        <option value="WIFI">WIFI</option>
      </select>
      <select id="city">
        <option value="Bangalore" selected>Bangalore</option>
        <option value="Chennai">Chennai</option>
      </select>
      
      <select id="hdd">
        <option value="">Select HDD</option>
        <option value="500GB">500GB</option>
        <option value="1TB">1TB</option> 
        <option value="2TB">2TB</option>
        <option value="3TB">3TB</option>
        <option value="4TB">4TB</option>
        <option value="6TB">6TB</option>
      </select>
      <select id="monitor">
        <option value="">Select Monitor</option>
        <option value="15 Inches">15 Inches</option>
        <option value="19 Inches">19 Inches</option>
        <option value="22 Inches">22 Inches</option>
        <option value="24 Inches">24 Inches</option>
      </select>
      <select id="rack">
        <option value="">Rack</option>
        <option value="2U">2U</option>
        <option value="4U">4U</option> 
      </select>
      
      <input type="text" id="location" placeholder="Location">
      
      <input type="text" id="map" placeholder="Map Link">
      <input type="hidden" id="map_lat" value="">
      <input type="hidden" id="map_lng" value="">
      <input type="time" id="time">
      <input type="date" id="date" required>
      <select id="owner">
        <option value="">Select Owner</option>
        <option value="VAR">VAR</option>
        <option value="AMR">AMR</option>
        <option value="ZOY">ZOY</option>
        <option value="DOM">DOM</option>
        <option value="SUR">SUR</option>
      </select>
      <select id="technician">
        <option value="">Select Technician</option>
        <option value="SYED">SYED</option>
        <option value="KARTHICK">KARTHICK</option>
        <option value="PAWAN">PAWAN</option>
        <option value="SIREN">SIREN</option>
        <option value="Chandan">Chandan</option>
        <option value="Uday (Chennai)">Uday (Chennai)</option>
        <option value="Zain">Zain</option>
      </select>
      <select id="helper">
        <option value="">Select Helper</option>
        <option value="KARTHIK">KARTHIK</option>
        <option value="SYED 2">SYED 2</option>
        <option value="Gowtham">Gowtham</option>
        <option value="Chandan">Chandan</option>
        <option value="Uday (Chennai)">Uday (Chennai)</option>
        <option value="Zain">Zain</option>
      </select>
      <?php if (installsIsAdminRole()): ?>
      <textarea id="admin_event_comment" placeholder="Admin event comment" rows="2" style="width:100%; min-height:46px; padding:9px 10px; border:1px solid #dc2626; border-radius:6px; background:#fff5f5; color:#991b1b; font-size:14px; box-sizing:border-box; resize:vertical;"></textarea>
      <?php endif; ?>
      <input type="text" id="notes" placeholder="Notes" />
      <button type="submit">Save</button>
      
    </form>
   
  </div>
        
      
    </div>
    <div id="tab-material" class="tab-content" style="flex:1; overflow: auto; padding: 10px; align-items: center; margin: auto; width: 80%; display: none;">
      <div id="material-content">Click 'Get Material' to load content...</div>
    </div>

    <div id="tab-invoice" class="tab-content" style="flex:1; overflow: auto; padding: 8px; align-items: center; margin: auto; width: 95%; display: none;">
      
     

      <!-- Extra Items Section -->
      <div  id="extra-card-section" class="collapsible-section extras-card" style="background: #fff; border: 1px solid #eee; border-radius: 10px; margin-bottom: 12px;">
        <div class="section-header" onclick="toggleSection('extras-section')" style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; color: #333; font-size: 16px;">Extra Items</h3>
          <div style="display: flex; align-items: center; gap: 10px;">
            <i id="extras-caret" class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
          </div>
        </div>
        <div id="extras-section" class="section-content" style="display: none; padding: 0 20px 20px; border-top: 1px solid #ddd;">
          <div class="extras-form" style="margin-top: 15px; display: flex; flex-direction: column; gap: 15px;">
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 12px;">
              <div style="font-weight: 600; margin-bottom: 8px; color: #495057;">Cable 3+1</div>
              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                <label style="font-size: 12px;">Rate <input type="number" id="ex_cable_rate" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Length <input type="number" id="ex_cable_len" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Total <input type="number" id="ex_cable_total" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
              </div>
            </div>
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 12px;">
              <div style="font-weight: 600; margin-bottom: 8px; color: #495057;">Rack</div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <label style="font-size: 12px;">Size <input type="text" id="ex_rack_size" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Rate <input type="number" id="ex_rack_rate" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
              </div>
            </div>
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 12px;">
              <div style="font-weight: 600; margin-bottom: 8px; color: #495057;">Monitor</div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <label style="font-size: 12px;">Size <input type="text" id="ex_monitor_size" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Rate <input type="number" id="ex_monitor_rate" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
              </div>
            </div>
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 12px;">
              <div style="font-weight: 600; margin-bottom: 8px; color: #495057;">Router</div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <label style="font-size: 12px;">Size <input type="text" id="ex_router_size" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Rate <input type="number" id="ex_router_rate" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
              </div>
            </div>
            <div style="border: 1px solid #eee; border-radius: 8px; padding: 12px;">
              <div style="font-weight: 600; margin-bottom: 8px; color: #495057;">Custom</div>
              <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 12px;">Label <input type="text" id="ex_custom_label" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: end;">
                  <label style="font-size: 12px;">Item <input type="text" id="ex_custom_item" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                  <label style="font-size: 12px;">Total <input type="number" id="ex_custom_total" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                  <button id="ex-add-custom-btn" type="button" title="Add custom item" aria-label="Add custom item" style="border: none; background: transparent; color: #28a745; cursor: pointer; font-size: 28px; font-weight: 700; line-height: 1; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center;">+</button>
                </div>
              </div>
              <div style="margin-top: 8px; font-size: 12px; color: #6c757d;">Add any number of custom items, then click Save Extras to retain them.</div>
            </div>
            <div style="border: 1px solid #fecaca; border-radius: 8px; background: #fff1f2; overflow: hidden;">
              <div onclick="toggleSection('pending-warranty-section')" style="padding: 12px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; color: #991b1b;">
                <div>
                  <div style="font-weight: 700; font-size: 14px;">Custom - Pending Amount</div>
                  <div style="font-size: 12px; margin-top: 2px;">If filled, no warranty will be provided from Smartronic until cleared.</div>
                </div>
                <i id="pending-warranty-caret" class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
              </div>
              <div id="pending-warranty-section" style="display: none; padding: 0 12px 12px; border-top: 1px solid #fecaca;">
                <label style="display:block; font-size: 12px; color: #7f1d1d; margin-top: 10px;">Pending amount label
                  <input type="text" id="ex_pending_label" value="Amount stands as unpaid" style="width: 100%; padding: 7px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 3px; background: #fff; color: #7f1d1d;">
                </label>
                <label style="display:block; font-size: 12px; color: #7f1d1d; margin-top: 8px;">Pending amount
                  <input type="number" id="ex_pending_amount" step="0.01" min="0" placeholder="Enter unpaid amount" style="width: 100%; padding: 7px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 3px; background: #fff; color: #7f1d1d;">
                </label>
                <label style="display:block; font-size: 12px; color: #7f1d1d; margin-top: 8px;">Note
                  <textarea id="ex_pending_note" rows="2" style="width: 100%; padding: 7px; border: 1px solid #fca5a5; border-radius: 4px; margin-top: 3px; background: #fff; color: #7f1d1d; resize: vertical;">No warranty or support from Smartronic is applicable unless the outstanding amount is cleared within 48 hours.</textarea>
                </label>
              </div>
            </div>
          </div>
          <div style="margin-top: 15px;">
            <table id="extras-table" style="width: 100%; border-collapse: collapse; font-size: 14px;">
              <thead>
                <tr style="background: #f7f7f7;">
                  <th style="text-align: left; padding: 8px; border: 1px solid #eee;">Item</th>
                  <th style="text-align: left; padding: 8px; border: 1px solid #eee;">Details</th>
                  <th style="text-align: right; padding: 8px; border: 1px solid #eee;">Amount</th>
                  <th style="text-align: center; padding: 8px; border: 1px solid #eee; width: 90px;">Action</th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <td colspan="2" style="text-align: right; padding: 8px; border: 1px solid #eee; font-weight: 600;">Extras total</td>
                  <td id="extras-total-cell" style="text-align: right; padding: 8px; border: 1px solid #eee; font-weight: 600;">0</td>
                </tr>
              </tfoot>
            </table>
          </div>
          <div style="margin-top: 20px; display: flex; justify-content: center;">
            <button id="ex-save-btn" type="button" style="padding: 12px 24px; border-radius: 6px; border: none; background: #28a745; color: #fff; cursor: pointer; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
              <i class="fas fa-save"></i> Save Extras
            </button>
          </div>
        </div>
      </div>


     <div id="invoice-content" style="min-height: 240px;">Click on Invoice tab to load invoice details...</div>
        
    </div>
  </div>
</div> 
<?php if (installsIsAdminRole()): ?>
<aside id="install-history-panel" class="install-history-panel" aria-label="Install history">
  <div class="install-history-head" id="install-history-drag">
    <div class="install-history-title"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i><span>Install History</span></div>
    <button class="install-history-close" id="install-history-close" type="button" aria-label="Close history"><i class="fas fa-times" aria-hidden="true"></i></button>
  </div>
  <div class="install-history-subtitle" id="install-history-subtitle">Latest actions</div>
  <div class="install-history-list" id="install-history-list">
    <div class="install-history-empty">No history loaded</div>
  </div>
</aside>
<?php endif; ?>
<script>
window.INSTALLS_CURRENT_ROLE = <?php echo json_encode(strtolower(trim((string)$role))); ?>;
window.INSTALLS_TODAY = <?php echo json_encode(date('Y-m-d')); ?>;
</script>
<script src="../js/installs.utils.js?v=20260404"></script>
<script src="../js/installs.pricing.js?v=20260623a"></script>
<script src="../js/installs.core.js?v=20260726e"></script>
<script src="../js/installs.week-scroll.js?v=20260618a"></script>
<script src="../js/installs.notes.js?v=20260726b"></script>
<script src="../js/floating_icon_menu.js?v=20260618a"></script>
<script src="../js/installs.stats.js?v=20260711b"></script>
<script src="../js/installs.modals.js?v=20260616a"></script>
<script src="../js/installs.content.js?v=20260728a"></script>
<script src="../js/installs.overlay.js?v=20260728a"></script>
<script src="../js/installs.payments.js?v=20260711a"></script>
<script src="../js/installs.integrations.js?v=20260623a"></script>
<script src="../js/installs.main.js?v=20260623a"></script>

<script>
// Helper function to get current order ID
function getCurrentOrderId() {
    // Try to get from the ID field in the requirement form
    const idField = document.getElementById('id');
    if (idField && idField.value) {
        return idField.value;
    }
    
    return null;
}

// Function to generate quote from current form data
function generateQuoteFromForm() {
    // Helper function to get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    // Check if user has admin role
    const authRole = getCookie('auth_role');
    if (authRole !== 'admin') {
        console.log('Quote generation restricted to admin users only');
        const resultsEl = document.getElementById('results');
        if (resultsEl) {
            resultsEl.innerHTML = '<div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; font-family: Arial, sans-serif; font-size: 14px;">🔒 Quote generation is only available for admin users.</div>';
        }
        return;
    }
    
    const fields = [
        'id', 'name', 'cams', 'bullets', 'dome', 'hdd', 'monitor', 'type',
        'city', 'location', 'time', 'date', 'owner', 'technician', 'helper',
        'resolution', 'brand', 'cam_type', 'map', 'rack', 'admin_event_comment', 'notes'
    ];
    
    const formData = {};
    fields.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            formData[field] = input.value || '';
        }
    });
    
    console.log('Generating quote for:', formData);
    
    if (typeof window.getQuoteDetails === 'function') {
        window.getQuoteDetails(formData);
    } else {
        console.error('getQuoteDetails function not available');
        const resultsEl = document.getElementById('results');
        if (resultsEl) {
            resultsEl.innerHTML = '<div style="color: red; padding: 10px;">❌ Quote function not loaded. Please refresh the page.</div>';
        }
    }
}

// Function to toggle collapsible sections
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    const caretId = sectionId.replace('-section', '-caret');
    const caret = document.getElementById(caretId);
    
    if (section && caret) {
        const isVisible = section.style.display !== 'none';
        section.style.display = isVisible ? 'none' : 'block';
        caret.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

// Function to load payment data when order is selected
function loadPaymentData(orderId) {
    if (!orderId) return;
    
    // Fetch payment data from server
    fetch(`installs.php?get_payment=1&id=${encodeURIComponent(orderId)}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                // Update both forms with existing data
                const fullyPaidCheckbox = document.getElementById('fully-paid');
                const amountPaidInput = document.getElementById('amount-paid');
                const overlayFullyPaid = document.getElementById('overlay-fully-paid');
                const overlayAmountPaid = document.getElementById('overlay-amount-paid');
                const overlayActualAmount = document.getElementById('overlay-actual-amount');
                
                if (fullyPaidCheckbox) fullyPaidCheckbox.checked = data.fully_paid;
                if (amountPaidInput) amountPaidInput.value = data.amount_paid || '';
                if (overlayFullyPaid) overlayFullyPaid.checked = data.fully_paid;
                if (overlayAmountPaid) overlayAmountPaid.value = data.amount_paid || '';
                if (overlayActualAmount) overlayActualAmount.value = String(data.actual_amount || '').replace(/[^0-9.]/g, '');
            }
        })
        .catch(error => {
            console.error('Error loading payment details:', error);
        });
}

// Initialize calendar on page load
document.addEventListener('DOMContentLoaded', function() {
    // Helper function to get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    // Check if user has admin role and hide quote button if not
    const authRole = getCookie('auth_role');
    const quoteButton = document.querySelector('button[onclick="generateQuoteFromForm()"]');
    if (quoteButton && authRole !== 'admin') {
        quoteButton.style.display = 'none';
    }
    
    // Wait for all modules to load
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const shouldOpenStats = urlParams.get('open_stats') === '1';
        if (typeof window.render === 'function') {
            window.render(function() {
                if (typeof window.openOwnerStatsModal === 'function') {
                    if (shouldOpenStats) {
                        window.openOwnerStatsModal(false);
                    } else {
                        window.openOwnerStatsModal(true);
                    }
                }
                if (window.innerWidth <= 768) {
                    const todayElement = document.querySelector('.day.today');
                    if (todayElement) {
                        todayElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
        // Ensure stats button is available
        if (typeof window.updateStats === 'function') {
            window.updateStats();
        }
    }, 100);
});
</script>


</body>
</html>
