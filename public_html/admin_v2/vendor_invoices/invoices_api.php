<?php
header('Content-Type: application/json');
require_once '../config.php';

// Authenticate (Basic check - extend as needed)
// session_start(); // config.php usually starts session
if (!isset($_SESSION) && !isset($_COOKIE['auth_user'])) {
   // For API, maybe just check cookie or session
   // echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
   // exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Helper to get JSON input
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

if ($method === 'GET') {
    // Fetch all invoices
    $sql = "SELECT * FROM vendor_invoices ORDER BY invoice_date DESC, created_at DESC";
    $result = $conn->query($sql);
    
    $invoices = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // content of line_items is JSON string, keep it that way or decode?
            // Client expects object probably.
            if ($row['line_items']) {
                $row['line_items'] = json_decode($row['line_items']);
            }
            $invoices[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $invoices]);

} elseif ($method === 'POST') {
    // Create new invoice
    $data = getJsonInput();
    
    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'No data provided']);
        exit;
    }
    
    $invNum = $data['invoice_number'] ?? 'Unknown';
    $invDate = $data['invoice_date'] ?? date('Y-m-d');
    $total = $data['total_amount'] ?? 0.00;
    $items = isset($data['line_items']) ? json_encode($data['line_items']) : '[]';
    $img = $data['image_path'] ?? null;

    // Check for duplicate invoice number
    $checkStmt = $conn->prepare("SELECT id FROM vendor_invoices WHERE invoice_number = ?");
    $checkStmt->bind_param("s", $invNum);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Duplicate Invoice: This invoice number already exists. Please delete the existing invoice first.']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Prepare statement
    $stmt = $conn->prepare("INSERT INTO vendor_invoices (invoice_number, invoice_date, total_amount, line_items, image_path) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssdss", $invNum, $invDate, $total, $items, $img);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    
    $stmt->close();

} elseif ($method === 'DELETE') {
    // Delete invoice
    $id = $_GET['id'] ?? null;
    if (!$id) {
        $data = getJsonInput();
        $id = $data['id'] ?? null;
    }
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM vendor_invoices WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>
