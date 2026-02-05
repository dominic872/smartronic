<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php'; // contains $mysqli = new mysqli(...);
$error = '';


// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the cookie exists and has the right value
if (
    !isset($_COOKIE['auth_role']) || 
    ($_COOKIE['auth_role'] !== 'admin' && $_COOKIE['auth_role'] !== 'market')
) {
    echo "No access";
    exit; // Stop processing the rest of the page
}

// If the user passes the check, the rest of your page code runs below...


// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

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

// Lightweight GET endpoint to fetch extras for a given order idno
if (isset($_GET['get_extras']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT extras FROM orders WHERE idno = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Query failed']); exit; }
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true,'extras'=> $row ? ($row['extras'] ?? '') : '' ]);
    exit;
}

// Lightweight GET endpoint to fetch payment data for a given order idno
if (isset($_GET['get_payment']) && isset($_GET['id'])) {
    header('Content-Type: application/json');
    if ($conn->connect_error) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT amount_paid, fully_paid FROM orders WHERE idno = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    if (!$stmt->execute()) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Query failed']); exit; }
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    echo json_encode(['success'=>true, 'amount_paid' => $row ? $row['amount_paid'] : null, 'fully_paid' => $row ? (bool)$row['fully_paid'] : false]);
    exit;
}

// GET endpoint for individual record by ID
if (isset($_GET['id']) && !isset($_GET['get_extras']) && !isset($_GET['get_payment']) && !isset($_GET['render_invoice']) && !isset($_GET['render_material'])) {
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
            'time' => $row['time'],
            'date' => $row['date'],
            'owner' => $row['Owner'],
            'technician' => $row['technician'],
            'helper' => $row['helper'],
            'order' => $row['order'],
            'resolution' => $row['resolution'],
            'map' => $row['Map'],
            'rack' => $row['rack'],
            'notes' => isset($row['notes']) ? $row['notes'] : ''
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
    
    if (isset($data['updatePayment']) && $data['updatePayment'] === true && !empty($data['id'])) {
        $idno = $data['id'];
        $amount_paid = isset($data['amount_paid']) ? floatval($data['amount_paid']) : null;
        $fully_paid = isset($data['fully_paid']) ? (bool)$data['fully_paid'] : false;
        
        $stmt = $conn->prepare("UPDATE orders SET amount_paid = ?, fully_paid = ? WHERE idno = ?");
        $stmt->bind_param("dis", $amount_paid, $fully_paid, $idno);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Payment updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update payment']);
        }
        exit;
    }
}

if ($isApiCall && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');


    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection failed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log the received data
    error_log("Received POST data: " . json_encode($data));
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No data received or invalid JSON']);
        exit;
    }

    // Special path: update only the 'extras' column for the given idno
    if (isset($data['extrasOnly']) && $data['extrasOnly'] === true && !empty($data['id'])) {
        $idno = $data['id'];
        $extras = isset($data['extras']) ? $data['extras'] : '';
        $stmt = $conn->prepare("UPDATE orders SET extras = ? WHERE idno = ?");
        $stmt->bind_param("ss", $extras, $idno);
        if ($stmt->execute()) {
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
    $time = $data['time'] ?? '';
    $date = $data['date'] ?? '';
    $owner = $data['owner'] ?? '';
    $technician = $data['technician'] ?? '';
    $helper = $data['helper'] ?? '';
    $order = (int)($data['order'] ?? 0);
    $resolution = $data['resolution'] ?? '';
    $map = $data['map'] ?? '';
    $rack = $data['rack'] ?? '';
    $notes = isset($data['notes']) ? $data['notes'] : '';
    
    // Check if record exists
    $checkStmt = $conn->prepare("SELECT idno FROM orders WHERE idno = ? LIMIT 1");
    $checkStmt->bind_param("s", $id);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE orders SET name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, time=?, date=?, Owner=?, technician=?, helper=?, `order`=?, resolution=?, Map=?, rack=?, notes=? WHERE idno=?");
        $stmt->bind_param("siiisssssssssisssss", $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $time, $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes, $id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO orders (idno, name, quantity, bullets, dome, storage, monitor, product, area, time, date, Owner, technician, helper, `order`, resolution, Map, rack, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiissssssssissss", $id, $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $time, $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes);
    }
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("MySQL Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    
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

    // ✅ Check if user is admin
    if (!isset($_COOKIE['auth_role']) || $_COOKIE['auth_role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';

    if ($id) {
        $stmt = $conn->prepare("UPDATE orders SET record_status = 'DELETED' WHERE idno = ?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
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
    $time = $data['time'] ?? '';
    $date = $data['date'] ?? '';
    $owner = $data['owner'] ?? '';
    $technician = $data['technician'] ?? '';
    $helper = $data['helper'] ?? '';
    $order = (int)($data['order'] ?? 0);
    $resolution = $data['resolution'] ?? '';
    $map = $data['map'] ?? '';
    $rack = $data['rack'] ?? '';
    $notes = isset($data['notes']) ? $data['notes'] : '';
    
    // Check if record exists
    $checkStmt = $conn->prepare("SELECT idno FROM orders WHERE idno = ? LIMIT 1");
    $checkStmt->bind_param("s", $id);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE orders SET name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, time=?, date=?, Owner=?, technician=?, helper=?, `order`=?, resolution=?, Map=?, rack=?, notes=? WHERE idno=?");
        $stmt->bind_param("siiisssssssssisssss", $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $time, $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes, $id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO orders (idno, name, quantity, bullets, dome, storage, monitor, product, area, time, date, Owner, technician, helper, `order`, resolution, Map, rack, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiiissssssssissss", $id, $name, $cams, $bullets, $dome, $hdd, $monitor, $type, $location, $time, $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes);
    }
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("MySQL Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }
    
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
  <title>CCTV Install Calendar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="../css/installs.css">
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=places,geometry"></script>
  <script src="../js/openlocationcode.js"></script>
  <!-- Transaction Scanner Dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js" crossorigin="anonymous"></script>
  <script src="../../invoice/scanner.js"></script>
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
      <a href="/" class="custom-logo-link" rel="home" aria-current="page">
        <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
      </a>
      <?php echo "<h2>Hello, $nameAssign!</h2>";?>
    </div>

      
    <div class="nav-buttons">
     
      <i class="fas fa-chevron-up" onclick="changeWeek(-1)" title="Previous Week"></i>
      <i class="fas fa-chevron-down" onclick="changeWeek(1)" title="Next Week"></i>
     
      
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
      <div id="popupForm" class="inline-edit-form install-inline-edit-form">
        <div class="install-popup-title">Add/Edit Install</div>
        <form id="installForm"></form>
      </div>
    </div>
    <div id="tab-material" class="tab-content" style="flex:1; overflow: auto; padding: 10px; align-items: center; margin: auto; width: 80%; display: none;">
      <div id="material-content">Click 'Get Material' to load content...</div>
    </div>

    <div id="tab-invoice" class="tab-content" style="flex:1; overflow: auto; padding: 10px; align-items: center; margin: auto; width: 80%; display: none;">
      
     

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
              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">
                <label style="font-size: 12px;">Label <input type="text" id="ex_custom_label" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Item <input type="text" id="ex_custom_item" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
                <label style="font-size: 12px;">Total <input type="number" id="ex_custom_total" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 2px;"></label>
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
<script src="../js/install_form_component.js"></script>
<script>
  (function () {
    const form = document.getElementById('installForm');
    if (form && window.SmartronicComponents && window.SmartronicComponents.InstallRequirementForm) {
      window.SmartronicComponents.InstallRequirementForm.mount(form);
    }
  })();
</script>
<script src="../js/installs.utils.js"></script>
<script src="../js/installs.core.js"></script>
<script src="../js/installs.notes.js"></script>
<script src="../js/installs.stats.js"></script>
<script src="../js/installs.modals.js"></script>
<script src="../js/installs.content.js"></script>
<script src="../js/installs.overlay.js"></script>
<script src="../js/installs.payments.js"></script>
<script src="../js/installs.integrations.js"></script>
<script src="../js/installs.main.js"></script>

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
        'location', 'time', 'date', 'owner', 'technician', 'helper', 
        'resolution', 'map', 'rack', 'notes'
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
                
                if (fullyPaidCheckbox) fullyPaidCheckbox.checked = data.fully_paid;
                if (amountPaidInput) amountPaidInput.value = data.amount_paid || '';
                if (overlayFullyPaid) overlayFullyPaid.checked = data.fully_paid;
                if (overlayAmountPaid) overlayAmountPaid.value = data.amount_paid || '';
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
        if (typeof window.render === 'function') {
            window.render(function() {
                if (typeof window.openOwnerStatsModal === 'function') {
                    window.openOwnerStatsModal(true);
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
