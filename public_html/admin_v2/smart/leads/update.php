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
if (!isset($input['id']) || empty($input['id'])) {
    $safe_error('Lead ID is required');
}

$id = intval($input['id']);

// Build update query dynamically based on provided fields
$allowedFields = [
    'Name' => 's',
    'whatsapp_number' => 's',
    'Area' => 's',
    'Assign' => 's',
    'cameras' => 'i',
    'resolution' => 's',
    'status' => 's',
    'source' => 's',
    'budget' => 'd',
    'comments' => 's',
    'MID' => 's'
];

$updates = [];
$params = [];
$types = '';

foreach ($allowedFields as $field => $type) {
    if (isset($input[$field])) {
        $updates[] = "$field = ?";
        $params[] = $input[$field];
        $types .= $type;
    }
}

if (empty($updates)) {
    $safe_error('No fields to update');
}

// Add ID to params
$params[] = $id;
$types .= 'i';

// Build and execute update query
$sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    $safe_error('Failed to update lead: ' . $stmt->error, 500);
}

if ($stmt->affected_rows === 0) {
    $safe_error('Lead not found or no changes made', 404);
}

$stmt->close();

// Fetch updated record
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
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $safe_error('Lead not found after update', 404);
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return updated record
echo json_encode([
    'success' => true,
    'message' => 'Lead updated successfully',
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
