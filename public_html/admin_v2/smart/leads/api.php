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

// Get request parameters
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 50; // Max 50
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$team = isset($_GET['team']) ? trim($_GET['team']) : '';
$source = isset($_GET['source']) ? trim($_GET['source']) : '';
$dateFrom = isset($_GET['dateFrom']) ? trim($_GET['dateFrom']) : '';
$dateTo = isset($_GET['dateTo']) ? trim($_GET['dateTo']) : '';

// Build WHERE clause
$where = [];
$params = [];
$types = '';

// Search filter
if (!empty($search)) {
    $where[] = "(Name LIKE ? OR whatsapp_number LIKE ? OR Area LIKE ? OR comments LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ssss';
}

// Status filter
if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

// Team filter
if (!empty($team)) {
    $where[] = "Assign = ?";
    $params[] = $team;
    $types .= 's';
}

// Source filter
if (!empty($source)) {
    $where[] = "source = ?";
    $params[] = $source;
    $types .= 's';
}

// Date range filter
if (!empty($dateFrom)) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
}

if (!empty($dateTo)) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

// Build WHERE clause string
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM leads $whereClause";
if (!empty($params)) {
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $countResult = $conn->query($countSql);
    $total = $countResult->fetch_assoc()['total'];
}

// Fetch leads with pagination
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
FROM leads 
$whereClause 
ORDER BY id DESC 
LIMIT ? OFFSET ?";

// Add limit and offset to params
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Format data for frontend
    $data[] = [
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
    ];
}

$stmt->close();
$conn->close();

// Return response
echo json_encode([
    'success' => true,
    'data' => $data,
    'total' => (int)$total,
    'offset' => $offset,
    'limit' => $limit,
    'hasMore' => ($offset + $limit) < $total,
    'filters' => [
        'search' => $search,
        'status' => $status,
        'team' => $team,
        'source' => $source,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo
    ]
]);
?>
