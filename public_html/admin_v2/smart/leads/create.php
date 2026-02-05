<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include database configuration
require_once('../../config.php');

// Error handling function
$safe_error = function($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
};

// Check database connection
if ($conn->connect_error) {
    $safe_error('Database connection failed', 500);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $safe_error('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $safe_error('Invalid JSON input');
}

// Validate required fields
$requiredFields = ['Name', 'whatsapp_number'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        $safe_error("Field '$field' is required");
    }
}

// Prepare data with defaults
$name = trim($input['Name']);
$phone = trim($input['whatsapp_number']);
$area = isset($input['Area']) ? trim($input['Area']) : null;
$assign = isset($input['Assign']) ? trim($input['Assign']) : null;
$cameras = isset($input['cameras']) ? intval($input['cameras']) : 0;
$resolution = isset($input['resolution']) ? trim($input['resolution']) : null;
$status = isset($input['status']) ? trim($input['status']) : 'New';
$source = isset($input['source']) ? trim($input['source']) : null;
$budget = isset($input['budget']) ? floatval($input['budget']) : null;
$comments = isset($input['comments']) ? trim($input['comments']) : null;
$mid = isset($input['MID']) ? trim($input['MID']) : null;

// Validate status
$validStatuses = ['New', 'Contacted', 'Quoted', 'Won', 'Lost', 'Follow-up'];
if (!in_array($status, $validStatuses)) {
    $safe_error("Invalid status. Must be one of: " . implode(', ', $validStatuses));
}

// Insert new lead
$sql = "INSERT INTO leads (
    MID, Name, whatsapp_number, Area, Assign, 
    cameras, resolution, status, source, budget, comments
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssissssds',
    $mid, $name, $phone, $area, $assign,
    $cameras, $resolution, $status, $source, $budget, $comments
);

if (!$stmt->execute()) {
    $safe_error('Failed to create lead: ' . $stmt->error, 500);
}

$newId = $stmt->insert_id;
$stmt->close();

// Fetch created record
$sql = "SELECT 
    id,
    IFNULL(MID, CONCAT('L-', LPAD(id, 4, '0'))) as display_id,
    Name,
    whatsapp_number,
    Area,
    Assign,
    cameras,
    resolution,
    status,
    source,
    budget,
    comments,
    created_at,
    updated_at
FROM leads WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $newId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$stmt->close();
$conn->close();

// Return created record
echo json_encode([
    'success' => true,
    'message' => 'Lead created successfully',
    'data' => [
        'id' => (int)$row['id'],
        'display_id' => $row['display_id'],
        'name' => $row['Name'],
        'phone' => $row['whatsapp_number'],
        'area' => $row['Area'],
        'assign' => $row['Assign'],
        'cameras' => (int)$row['cameras'],
        'resolution' => $row['resolution'],
        'status' => $row['status'],
        'source' => $row['source'],
        'budget' => $row['budget'] ? (float)$row['budget'] : null,
        'comments' => $row['comments'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ]
]);
?>
