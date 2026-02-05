
<?php
// api.php
// Single-file API for Guest Pickup Organizer (uses mysqli + app.php config)
// Actions: list, create, delete (soft-delete)
// Place api.php and app.php in the same folder. app.php must define $conn (mysqli).

header('Content-Type: application/json; charset=utf-8');
session_start();

// Detect if running locally
$isLocalhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

// Set credentials based on environment
$host = $isLocalhost ? 'localhost' : '127.0.0.1:3306';
$username = $isLocalhost ? 'root' : 'u398852039_smartronic';
$password = $isLocalhost ? 'root' : 'Chennai@40!';
$database = 'u398852039_smartronic';

// Attempt connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available.']);
    exit;
}

/**
 * Read incoming data:
 * - If JSON body -> decode and use it
 * - Else use $_POST / $_GET
 */
$rawInput = file_get_contents('php://input');
$inputData = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if ($rawInput && stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) $inputData = $decoded;
} elseif (!empty($_POST)) {
    $inputData = $_POST;
} else {
    // fallback to GET for simple testing
    $inputData = $_GET;
}

$action = trim($inputData['action'] ?? $inputData['q'] ?? $_GET['action'] ?? 'list');

try {
    if ($action === 'list') {
        // Return all records sorted by arrival_date, arrival_time (nulls to end).
        $sql = "SELECT * FROM guests
                 ORDER BY COALESCE(arrival_date,'9999-12-31'), COALESCE(arrival_time,'23:59:59'), created_at ASC";
        $res = $conn->query($sql);
        if ($res === false) {
            throw new Exception($conn->error);
        }
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['ok' => true, 'rows' => $rows]);
        exit;
    }

    if ($action === 'create') {
        // Required fields check
        $name = trim($inputData['name'] ?? '');
        $phone = trim($inputData['phone'] ?? '');
        $members = $inputData['members'] ?? 1;
        $members = (int)$members;
        if ($name === '' || $phone === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Name and phone are required.']);
            exit;
        }

        // Prepare values (convert empty strings to NULL for optional fields)
        $arrival_date = normalize_nullable($inputData['arrival_date'] ?? null);
        $arrival_time = normalize_nullable($inputData['arrival_time'] ?? null);
        $arrival_place = normalize_nullable($inputData['arrival_place'] ?? null);
        $departure_date = normalize_nullable($inputData['departure_date'] ?? null);
        $departure_time = normalize_nullable($inputData['departure_time'] ?? null);
        $departure_place = normalize_nullable($inputData['departure_place'] ?? null);
        $notes = normalize_nullable($inputData['notes'] ?? null);

        // Build dynamic INSERT so that NULLs are inserted as SQL NULL (no placeholder)
        $cols = [
            'name' => $name,
            'phone' => $phone,
            'members' => $members,
            'arrival_date' => $arrival_date,
            'arrival_time' => $arrival_time,
            'arrival_place' => $arrival_place,
            'departure_date' => $departure_date,
            'departure_time' => $departure_time,
            'departure_place' => $departure_place,
            'notes' => $notes
        ];

        $insertCols = [];
        $placeholders = [];
        $params = [];
        $types = '';

        foreach ($cols as $col => $val) {
            $insertCols[] = $col;
            if ($val === null) {
                $placeholders[] = "NULL";
            } else {
                $placeholders[] = "?";
                // Determine type: integer for members, string for others
                $types .= ($col === 'members') ? 'i' : 's';
                $params[] = $val;
            }
        }

        $sql = "INSERT INTO guests (" . implode(',', $insertCols) . ")
                VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) throw new Exception("Prepare failed: " . $conn->error);

        if (count($params) > 0) {
            // bind params dynamically
            $bind_names[] = $types;
            for ($i = 0; $i < count($params); $i++) {
                // Need references
                $bind_names[] = &$params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $ok = $stmt->execute();
        if ($ok === false) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $newId = $stmt->insert_id ?? $conn->insert_id;
        $stmt->close();

        echo json_encode(['ok' => true, 'id' => $newId]);
        exit;
    }

    if ($action === 'delete') {
        // Soft delete: set deleted=1 and deleted_at=NOW()
        $id = (int)($inputData['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid id']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE guests SET deleted = 1, deleted_at = NOW() WHERE id = ?");
        if ($stmt === false) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if ($ok === false) throw new Exception("Execute failed: " . $stmt->error);
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }

    // Unknown action
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action: ' . $action]);
    exit;
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
    exit;
}


/**
 * Helpers
 */
function normalize_nullable($v) {
    if (!isset($v)) return null;
    $v = trim((string)$v);
    if ($v === '') return null;
    return $v;
}
