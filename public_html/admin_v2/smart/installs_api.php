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
    $rack = isset($data['rack']) ? $conn->real_escape_string($data['rack']) : '';
    $notes = isset($data['notes']) ? $conn->real_escape_string($data['notes']) : '';

   $stmt = $conn->prepare("
  INSERT INTO orders (
    idno, name, quantity, bullets, dome,
    storage, monitor, product, area, time, date,
    Owner, technician, helper, `order`, resolution, brand, cam_type, map, rack, notes
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, time=?, date=?,
    Owner=?, technician=?, helper=?, `order`=?, resolution=?, brand=?, cam_type=?, map=?, rack=?, notes=?
");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'SQL prepare failed', 'details' => $conn->error]);
        exit;
    }

$types = str_repeat('s', 39);
$stmt->bind_param(
  $types,
  $idno, $name, $cams, $bullets, $dome,
  $hdd, $monitor, $type, $location, $time, $date,
  $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $rack, $notes,

  $name, $cams, $bullets, $dome,
  $hdd, $monitor, $type, $location, $time, $date,
  $owner, $technician, $helper, $order, $resolution, $brand, $cam_type, $map, $rack, $notes
);


    if (!$stmt->execute()) {
        http_response_code(500); 
        echo json_encode(['error' => 'DB insert/update failed', 'details' => $stmt->error]);
        exit;
    }

    echo json_encode(['status' => 'ok']);
    exit;
}

// Handle GET (fetch all installs)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM orders WHERE record_status IS NULL OR record_status != 'DELETED' ORDER BY created_at ASC, `ID` DESC");

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'DB fetch failed', 'details' => $conn->error]);
        exit;
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
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
            'time' => $row['time'],
            'date' => $row['date'],
            'owner' => $row['Owner'],
            'technician' => $row['technician'],
            'helper' => $row['helper'],
            'order' => $row['order'],
            'lead_campaign' => isset($row['lead_campaign']) ? $row['lead_campaign'] : '',
            'resolution' => $row['resolution'],
            'brand' => isset($row['brand']) ? $row['brand'] : '',
            'cam_type' => isset($row['cam_type']) ? $row['cam_type'] : '',
            'map' => $row['Map'],
            'rack' => $row['rack'],
            'notes' => isset($row['notes']) ? $row['notes'] : '',
            'fully_paid' => isset($row['fully_paid']) ? (bool)$row['fully_paid'] : false,
            'pdf_sent' => isset($row['pdf_sent']) ? $row['pdf_sent'] : ''
        ];
    }

    echo json_encode($rows);
    exit;
}

// If method not handled
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
exit;
?>
