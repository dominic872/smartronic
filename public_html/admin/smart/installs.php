<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php'; // contains $mysqli = new mysqli(...);
$error = '';


// Ensure error reporting won't expose sensitive data in production
error_reporting(0);

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

// Handle query string parameters
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




$isApiCall = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json');

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
    $stmt = $conn->prepare("INSERT INTO orders (idno, name, quantity, bullets, dome, storage, monitor, product, area, time, date, owner, technician, helper, `order`, resolution, map, rack, notes)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE name=?, quantity=?, bullets=?, dome=?, storage=?, monitor=?, product=?, area=?, time=?, date=?, owner=?, technician=?, helper=?, `order`=?, resolution=?, map=?, rack=?, notes=?");
$stmt->bind_param(
  "ssiiissssssssssisssssiiissssssssssissss",
  $id, $name, $cams, $bullets, $dome,
  $hdd, $monitor, $type, $location, $time,
  $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes,
  $name, $cams, $bullets, $dome,
  $hdd, $monitor, $type, $location, $time,
  $date, $owner, $technician, $helper, $order, $resolution, $map, $rack, $notes
);
    $stmt->execute();
    echo json_encode(['status' => 'ok']);
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
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BKkBQEI0WpbFFjn8K4VWKRaYeIs3GhU&libraries=places"></script>
</head>
<body>
  <div class="controls">
    <div class="logo-section">
      <a href="/" class="custom-logo-link" rel="home" aria-current="page">
        <img width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic | CCTV with Free Installation | Smart Home Automation" decoding="async">
      </a>
      <h2 id="monthLabel"></h2>
    </div>
    <div class="owner-stats">
        <div class="stats-section">
          
          <div class="stats-row">
            <div class="owner-count" id="weekDAR">DAR: <br>0</div>
            <div class="owner-count" id="weekAMR">AMR: 0</div>
            <div class="owner-count" id="weekDOM">DOM: 0</div>
            
      </div>
      <span class="stats-label">This Week: <strong id="thisWeek"></strong></span>
        </div>
        <div class="stats-section">
         
          <div class="stats-row">
            <div class="owner-count" id="monthDAR">DAR: 0</div>
            <div class="owner-count" id="monthAMR">AMR: 0</div>
            <div class="owner-count" id="monthDOM">DOM: 0</div>
          </div>
          <span class="stats-label">This Month: <strong id="thisMonth"></strong></span>
        </div>
      </div>
      <?php

echo "<h2>Hello, $nameAssign!</h2>";
?>
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
        
        /* Mobile-optimized tab buttons */
        .tab-buttons-container {
          position: relative !important;
          display: flex !important;
          border-bottom: 1px solid #ccc !important;
          z-index: 9999 !important;
          overflow-x: auto !important;
        }
        
        .tab-button {
          flex: 1 !important;
          padding: 12px 8px !important;
          font-size: 14px !important;
          white-space: nowrap !important;
          min-width: 80px !important;
          border: none !important;
          background: #f8f9fa !important;
          color: #495057 !important;
          cursor: pointer !important;
          transition: all 0.2s ease !important;
        }
        
        .tab-button.active {
          background: #007bff !important;
          color: white !important;
        }
        
        .close-button {
          position: absolute !important;
          right: 8px !important;
          top: 8px !important;
          z-index: 10000 !important;
          background: #dc3545 !important;
          color: white !important;
          border: none !important;
          border-radius: 50% !important;
          width: 36px !important;
          height: 36px !important;
          display: flex !important;
          align-items: center !important;
          justify-content: center !important;
          cursor: pointer !important;
          font-size: 16px !important;
        }
        
        /* Copy/Share button positioning */
        .copy-share-btn {
          position: fixed !important;
          right: 15px !important;
          bottom: 80px !important;
          z-index: 9998 !important;
          background: #28a745 !important;
          color: white !important;
          border: none !important;
          border-radius: 50% !important;
          width: 48px !important;
          height: 48px !important;
          display: flex !important;
          align-items: center !important;
          justify-content: center !important;
          cursor: pointer !important;
          font-size: 18px !important;
          box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        }
        
        /* Tab content margins */
        .tab-content {
          padding: 15px !important;
          overflow-x: hidden !important;
          overflow-y: auto !important;
          -webkit-overflow-scrolling: touch !important;
        }
        
        /* Material tab specific fixes */
        #tab-material {
          width: 100% !important;
          margin: 0 !important;
        }
        
        #material-content {
          width: 100% !important;
          overflow-x: hidden !important;
        }
        
        /* Invoice tab specific fixes */
        #tab-invoice {
          width: 100% !important;
          margin: 0 !important;
        }
        
        #invoice-content {
          width: 100% !important;
          overflow-x: hidden !important;
          font-size: 12px !important;
          transform: scale(0.85) !important;
          transform-origin: top left !important;
          margin: 0 !important;
          padding: 10px !important;
        }
        
        /* Extras overlay button - only show in invoice tab */
        #extras-open-btn {
          display: none !important;
        }
        
        .tab-content.invoice-active #extras-open-btn {
          display: flex !important;
          position: fixed !important;
          right: 15px !important;
          bottom: 15px !important;
          z-index: 9997 !important;
        }
        
        /* Form optimizations */
        #installForm {
          display: block !important;
        }
        
        #installForm input,
        #installForm select {
          width: 100% !important;
          margin-bottom: 10px !important;
          padding: 10px !important;
          font-size: 16px !important;
          border: 1px solid #ddd !important;
          border-radius: 4px !important;
          box-sizing: border-box !important;
        }
      }
      
      /* Desktop optimizations */
      @media (min-width: 769px) {
        .tab-buttons-container {
          display: flex;
          border-bottom: 1px solid #ccc;
          z-index: 9999;
        }
        
        .tab-button {
          flex: 1;
          padding: 12px;
          font-size: 16px;
          font-weight: 600;
          color: #007bff;
          background: transparent;
          border: none;
          cursor: pointer;
          transition: all 0.2s ease;
        }
        
        .tab-button:hover {
          background: #f8f9fa;
        }
        
        .tab-button.active {
          background: #007bff;
          color: white;
        }
        
        .close-button {
          position: absolute;
          right: 10px;
          top: 10px;
          background: transparent;
          border: none;
          cursor: pointer;
          font-size: 24px;
          color: #dc3545;
          z-index: 10000;
        }
        
        .tab-content {
          padding: 20px;
        }
        
        #tab-material,
        #tab-invoice {
          width: 80%;
          margin: auto;
        }
      }
      
      /* Prevent body scroll when popup is open */
      body.popup-open {
        overflow: hidden;
        height: 100vh;
      }
      
      /* Prevent double-click issues */
      .event, .event * {
        pointer-events: auto;
        user-select: none;
      }
      
      .event .icons i {
        pointer-events: auto;
        touch-action: manipulation;
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
    </style>
    
    <div class="tab-buttons-container">
      <button class="tab-button active" onclick="switchTab('requirement')" data-tab="requirement">Requirement</button>
      <button class="tab-button" onclick="switchTab('material')" data-tab="material">Material</button>
      <button class="tab-button" onclick="switchTab('invoice')" data-tab="invoice">Invoice</button>
      <button onclick="closeOrderPopup()" class="close-button"><i class="fas fa-times" title="close"></i></button>
    </div>

    <div id="tab-requirement" class="tab-content" style="flex:1; overflow: auto; display: block;">
   
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
        <input type="number" id="cams" placeholder="Total Cams" required>
      </div>
      <div class="form-row">
        <input type="number" id="bullets" placeholder="Bullets">
        <input type="number" id="dome" placeholder="Dome">
      </div>
      <select id="type">
        <option value="">Select Type</option>
        <option value="DVR">DVR</option>
        <option value="NVR">NVR</option>
        <option value="WIFI">WIFI</option>
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
      <input type="time" id="time">
      <input type="date" id="date" required>
      <select id="owner">
        <option value="">Select Owner</option>
        <option value="DAR">DAR</option>
        <option value="AMR">AMR</option>
        <option value="DOM">DOM</option>
      </select>
      <select id="technician">
        <option value="">Select Technician</option>
        <option value="SYED">SYED</option>
        <option value="AFREED">AFREED</option>
        <option value="KARTHICK">KARTHICK</option>
        <option value="ABDUL">ABDUL</option>
        <option value="SYED 2">SYED 2</option>
        <option value="DAVID">DAVID</option>
      </select>
      <select id="helper">
        <option value="">Select Helper</option>
        <option value="KARTHIK">KARTHIK</option>
        <option value="ABDUL">ABDUL</option>
        <option value="SYED 2">SYED 2</option>
      </select>
      <input type="text" id="notes" placeholder="Notes" />
      <button type="submit">Save</button>
     
    </form>
   
  </div>
        
      
    </div>
    <div id="tab-material" class="tab-content" style="flex:1; overflow: auto; display: none;">
      <div id="material-content">Click 'Get Material' to load content...</div>
    </div>

    <div id="tab-invoice" class="tab-content" style="flex:1; overflow: auto; display: none;">
      <!-- Copy/Share button for mobile -->
      <button class="copy-share-btn" onclick="copyToClipboard()" style="display: none;"><i class="fas fa-share"></i></button>
      
      <!-- Invoice Content Container -->
      <div id="invoice-content" style="min-height: 240px; margin-bottom: 20px;">Click on Invoice tab to load invoice details...</div>
      
      <!-- Update Payment Details Section -->
      <div class="collapsible-section" style="background: #f9f9f9; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
        <div class="section-header" onclick="toggleSection('payment-section')" style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; color: #333; font-size: 16px;">Update Payment Details</h3>
          <i id="payment-caret" class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
        </div>
        <div id="payment-section" class="section-content" style="display: none; padding: 0 20px 20px; border-top: 1px solid #ddd;">
          <form method="post" id="update-payment-form">
            <div style="margin-bottom: 15px; margin-top: 15px;">
              <label for="fully-paid" style="display: flex; align-items: center; gap: 8px; font-weight: 500;">
                <input type="checkbox" id="fully-paid" name="fully-paid" style="margin: 0;"> Mark as fully paid
              </label>
            </div>
            <div style="margin-bottom: 15px;">
              <label for="amount-paid" style="display: block; margin-bottom: 5px; font-weight: 500;">Amount Paid (₹):</label>
              <input type="number" id="amount-paid" name="amount-paid" placeholder="Enter amount" step="0.01" min="0" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            </div>
            <button type="submit" style="background: #007cba; color: white; padding: 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-save"></i>
            </button>
          </form>
        </div>
      </div>

      <!-- Extra Items Section -->
      <div class="collapsible-section extras-card" style="background: #fff; border: 1px solid #eee; border-radius: 10px; margin-bottom: 12px;">
        <div class="section-header" onclick="toggleSection('extras-section')" style="padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="margin: 0; color: #333; font-size: 16px;">Extra Items</h3>
          <div style="display: flex; align-items: center; gap: 10px;">
            <button id="ex-save-btn" type="button" onclick="event.stopPropagation()" style="padding: 8px; border-radius: 4px; border: none; background: #28a745; color: #fff; cursor: pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
              <i class="fas fa-save"></i>
            </button>
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
        </div>
      </div>

    </div>
  </div>
</div> 
<script src="../js/installs.utils.js"></script>
<script src="../js/installs.core.js"></script>
<script src="../js/installs.notes.js"></script>
<script src="../js/installs.modals.js"></script>
<script src="../js/installs.content.js"></script>
<script src="../js/installs.overlay.js"></script>
<script src="../js/installs.payments.js"></script>
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

// Enhanced tab switching with extras loading and button management
function switchTab(tabName) {
    console.log('🔄 switchTab called with:', tabName);
    
    // Hide all tab contents
    const allTabs = document.querySelectorAll('.tab-content');
    allTabs.forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('invoice-active');
    });
    
    // Remove active class from all tab buttons
    const allButtons = document.querySelectorAll('.tab-button');
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab and mark button as active
    const selectedTab = document.getElementById(`tab-${tabName}`);
    const selectedButton = document.querySelector(`[data-tab="${tabName}"]`);
    
    if (selectedTab) {
        selectedTab.style.display = 'block';
        if (tabName === 'invoice') {
            selectedTab.classList.add('invoice-active');
        }
    }
    
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
    
    // Handle copy/share button visibility
    const copyShareBtn = document.querySelector('.copy-share-btn');
    if (copyShareBtn) {
        copyShareBtn.style.display = (tabName === 'invoice') ? 'flex' : 'none';
    }
    
    // Load content based on tab
    if (tabName === 'material') {
        loadMaterialContent();
    } else if (tabName === 'invoice') {
        loadInvoiceContent();
        
        // Load extras with multiple attempts
        setTimeout(async () => {
            try {
                console.log('⏰ First attempt to load extras (500ms delay)');
                if (typeof window.loadExtrasFromDB === 'function') {
                    await window.loadExtrasFromDB();
                }
            } catch (e) {
                console.warn('⚠️ First fallback extras loading failed:', e);
            }
        }, 500);
        
        setTimeout(async () => {
            try {
                console.log('⏰ Second attempt to load extras (1500ms delay)');
                if (typeof window.loadExtrasFromDB === 'function') {
                    await window.loadExtrasFromDB();
                }
            } catch (e) {
                console.warn('⚠️ Second fallback extras loading failed:', e);
            }
        }, 1500);
    }
}

// Copy to clipboard function
function copyToClipboard() {
    const invoiceContent = document.getElementById('invoice-content');
    if (invoiceContent) {
        const textContent = invoiceContent.innerText;
        navigator.clipboard.writeText(textContent).then(() => {
            alert('Invoice content copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
}

// Load material content
function loadMaterialContent() {
    const orderId = getCurrentOrderId();
    if (!orderId) {
        document.getElementById('material-content').innerHTML = 'Please select an order first';
        return;
    }
    
    document.getElementById('material-content').innerHTML = 'Loading...';
    
    fetch(`installs.php?render_material=1&id=${encodeURIComponent(orderId)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('material-content').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading material:', error);
            document.getElementById('material-content').innerHTML = 'Error loading material content';
        });
}

// Load invoice content
function loadInvoiceContent() {
    const orderId = getCurrentOrderId();
    if (!orderId) {
        document.getElementById('invoice-content').innerHTML = 'Please select an order first';
        return;
    }
    
    document.getElementById('invoice-content').innerHTML = 'Loading invoice...';
    
    fetch(`installs.php?render_invoice=1&id=${encodeURIComponent(orderId)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('invoice-content').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading invoice:', error);
            document.getElementById('invoice-content').innerHTML = 'Error loading invoice content';
        });
}

// Prevent double-click issues
function preventDoubleClick() {
    const events = document.querySelectorAll('.event');
    events.forEach(event => {
        event.addEventListener('click', function(e) {
            if (this.dataset.clicked === 'true') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
            
            this.dataset.clicked = 'true';
            setTimeout(() => {
                this.dataset.clicked = 'false';
            }, 1000);
        });
    });
}

// Initialize calendar and mobile optimizations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize double-click prevention
    preventDoubleClick();
    
    // Show copy/share button on mobile for invoice tab
    const copyShareBtn = document.querySelector('.copy-share-btn');
    if (copyShareBtn && window.innerWidth <= 768) {
        copyShareBtn.style.display = 'flex';
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const copyShareBtn = document.querySelector('.copy-share-btn');
        if (copyShareBtn) {
            if (window.innerWidth <= 768) {
                const invoiceTab = document.getElementById('tab-invoice');
                if (invoiceTab && invoiceTab.style.display !== 'none') {
                    copyShareBtn.style.display = 'flex';
                }
            } else {
                copyShareBtn.style.display = 'none';
            }
        }
    });
    
    // Wait for all modules to load
    setTimeout(function() {
        if (typeof window.render === 'function') {
            window.render();
        }
        
        // Re-apply double-click prevention after render
        setTimeout(preventDoubleClick, 500);
    }, 100);
    
    // Add touch improvements for mobile
    if ('ontouchstart' in window) {
        document.body.style.touchAction = 'manipulation';
        
        // Improve button touch targets
        const buttons = document.querySelectorAll('button, .tab-button');
        buttons.forEach(btn => {
            btn.style.minHeight = '44px';
            btn.style.minWidth = '44px';
        });
    }
});
</script>


</body>
</html>
