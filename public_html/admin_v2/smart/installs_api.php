<?php
header('Content-Type: application/json');

// Connect based on environment
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
requireSmartPageAccess('install', $role, $authPages);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MySQL connection


// Robust config loader
$paths = [
  __DIR__ . '/config.php',
  __DIR__ . '/../config.php',
  __DIR__ . '/admin/config.php',
  dirname(__DIR__) . '/admin/config.php'
];

$configLoaded = false;
foreach ($paths as $p) {
  if (file_exists($p)) { require $p; $configLoaded = true; break; }
}

if (!$configLoaded || !isset($conn) || $conn->connect_error) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => 'DB connection not available from installs_api.php',
    'error'   => isset($conn) ? $conn->connect_error : 'config.php not loaded'
  ]);
  exit;
}

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

ensureOrdersMapCoordColumns($conn);

function installsApiJsonResponse($payload, int $code = 200): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function installsApiOrdersColumns(mysqli $conn): array {
    static $cols = null;
    if ($cols !== null) {
        return $cols;
    }
    $cols = [];
    $res = $conn->query('SHOW COLUMNS FROM orders');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['Field'])) {
                $cols[] = (string)$row['Field'];
            }
        }
    }
    return $cols;
}

function installsApiNormalizeDate($value): ?string {
    if ($value === null) {
        return null;
    }
    $text = trim((string)$value);
    if ($text === '' || $text === '0000-00-00' || strtolower($text) === 'null') {
        return null;
    }
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $text, $m)) {
        return $m[1];
    }
    $ts = strtotime($text);
    return $ts ? date('Y-m-d', $ts) : $text;
}

function installsApiFetchOrders(mysqli $conn) {
    $cols = installsApiOrdersColumns($conn);
    $lower = array_map('strtolower', $cols);

    $where = '1=1';
    if (in_array('record_status', $lower, true)) {
        $where = "(record_status IS NULL OR record_status <> 'DELETED')";
    }

    $orderParts = [];
    if (in_array('created_at', $lower, true)) {
        $orderParts[] = 'created_at ASC';
    } elseif (in_array('date', $lower, true)) {
        $orderParts[] = '`date` DESC';
    }

    $idCol = null;
    foreach ($cols as $col) {
        if (strtolower($col) === 'id') {
            $idCol = $col;
            break;
        }
    }
    $orderParts[] = $idCol
        ? ('`' . str_replace('`', '``', $idCol) . '` DESC')
        : 'idno DESC';

    $sql = 'SELECT * FROM orders WHERE ' . $where . ' ORDER BY ' . implode(', ', $orderParts);
    return $conn->query($sql);
}

function installsApiMapOrderRow(array $row): array {
    return [
        'id' => $row['idno'] ?? '',
        'phone' => $row['phone'] ?? '',
        'name' => $row['name'] ?? '',
        'cams' => $row['quantity'] ?? 0,
        'bullets' => $row['bullets'] ?? 0,
        'dome' => $row['dome'] ?? 0,
        'hdd' => $row['storage'] ?? '',
        'monitor' => $row['monitor'] ?? '',
        'type' => $row['product'] ?? '',
        'location' => $row['area'] ?? '',
        'city' => isset($row['city']) && $row['city'] !== '' ? $row['city'] : 'Bangalore',
        'time' => $row['time'] ?? '',
        'date' => installsApiNormalizeDate($row['date'] ?? null),
        'owner' => $row['Owner'] ?? '',
        'technician' => $row['technician'] ?? '',
        'helper' => $row['helper'] ?? '',
        'order' => $row['order'] ?? 0,
        'lead_campaign' => $row['lead_campaign'] ?? '',
        'resolution' => $row['resolution'] ?? '',
        'brand' => $row['brand'] ?? '',
        'cam_type' => $row['cam_type'] ?? '',
        'map' => $row['Map'] ?? '',
        'map_lat' => $row['map_lat'] ?? null,
        'map_lng' => $row['map_lng'] ?? null,
        'rack' => $row['rack'] ?? '',
        'notes' => $row['notes'] ?? '',
        'admin_event_comment' => $row['admin_event_comment'] ?? '',
        'price' => $row['price'] ?? '',
        'amount_paid' => $row['amount_paid'] ?? '',
        'pending_amount' => $row['pending_amount'] ?? 0,
        'fully_paid' => !empty($row['fully_paid']),
        'pdf_sent' => $row['pdf_sent'] ?? '',
    ];
}

function installsApiIsAdminRole(): bool {
    $role = isset($_COOKIE['auth_role']) ? trim((string)$_COOKIE['auth_role']) : '';
    return strtolower($role) === 'admin';
}

function installsApiUpdateAdminEventComment(mysqli $conn, string $id, string $comment): void {
    if ($id === '' || $conn->connect_error || !installsApiIsAdminRole()) return;
    $stmt = $conn->prepare("UPDATE orders SET admin_event_comment = ? WHERE idno = ?");
    if (!$stmt) return;
    $stmt->bind_param("ss", $comment, $id);
    $stmt->execute();
    $stmt->close();
}

// Handle POST (insert/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or missing JSON payload']);
        exit;
    }

    // Assign fields
    $idno = $data['id'] ?? '';
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
    $date = $data['date'] ?? '';
    $owner = $data['owner'] ?? '';
    $technician = $data['technician'] ?? '';
    $helper = $data['helper'] ?? '';
    $order = (int)($data['order'] ?? 0);
    // Add new fields with proper validation
    $resolution = $data['resolution'] ?? '';
	    $brand = isset($data['brand']) ? $conn->real_escape_string($data['brand']) : '';
	    $cam_type = isset($data['cam_type']) ? $conn->real_escape_string($data['cam_type']) : '';
	    $map = $data['map'] ?? '';
	    $mapLat = isset($data['map_lat']) && $data['map_lat'] !== '' ? (string)$data['map_lat'] : '';
	    $mapLng = isset($data['map_lng']) && $data['map_lng'] !== '' ? (string)$data['map_lng'] : '';
	    $hasMapLat = $mapLat !== '' ? 1 : 0;
	    $hasMapLng = $mapLng !== '' ? 1 : 0;
	    $mapLatValue = $hasMapLat ? (float)$mapLat : 0.0;
	    $mapLngValue = $hasMapLng ? (float)$mapLng : 0.0;
	    $rack = isset($data['rack']) ? $conn->real_escape_string($data['rack']) : '';
	    $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';
	    $adminEventComment = installsApiIsAdminRole() && isset($data['admin_event_comment']) ? trim((string)$data['admin_event_comment']) : '';

   $stmt = $conn->prepare("
  INSERT INTO orders (
    idno, name, quantity, bullets, dome,
    storage, monitor, product, area, city, time, date,
    Owner, technician, helper, `order`, resolution, brand, cam_type, map, map_lat, map_lng, rack, notes
	  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = 1, ?, NULL), IF(? = 1, ?, NULL), ?, ?)
	  ON DUPLICATE KEY UPDATE
	    name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, city=?, time=?, date=?,
	    Owner=?, technician=?, helper=?, `order`=?, resolution=?, brand=?, cam_type=?, map=?, map_lat=IF(? = 1, ?, NULL), map_lng=IF(? = 1, ?, NULL), rack=?, notes=?
	");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'SQL prepare failed', 'details' => $conn->error]);
        exit;
    }

$types = "ssiiissssssssssissssididss" . "siiissssssssssissssididss";
	$stmt->bind_param(
	  $types,
	  $idno, $name, $cams, $bullets, $dome,
	  $hdd, $monitor, $type, $location, $city, $time, $date,
	  $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $hasMapLat, $mapLatValue, $hasMapLng, $mapLngValue, $rack, $notes,

	  $name, $cams, $bullets, $dome,
	  $hdd, $monitor, $type, $location, $city, $time, $date,
	  $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $hasMapLat, $mapLatValue, $hasMapLng, $mapLngValue, $rack, $notes
	);


    if (!$stmt->execute()) {
        http_response_code(500); 
        echo json_encode(['error' => 'DB insert/update failed', 'details' => $stmt->error]);
        exit;
    }
    installsApiUpdateAdminEventComment($conn, (string)$idno, $adminEventComment);

    echo json_encode(['status' => 'ok']);
    exit;
}

// Handle GET (fetch all installs)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = installsApiFetchOrders($conn);

    if (!$result) {
        installsApiJsonResponse([
            'error' => 'DB fetch failed',
            'details' => $conn->error,
        ], 500);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = installsApiMapOrderRow($row);
    }

    installsApiJsonResponse($rows);
}

// If method not handled
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
exit;
?>
