<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Database config
if ($_SERVER['HTTP_HOST'] === 'smartronic.online') {
    $servername = '127.0.0.1:3306';
    $dbusername = 'u398852039_smartronic';
    $password = 'Chennai@40!';
    $database = 'u398852039_smartronic';
} else {
    $servername = "localhost";
    $dbusername = "root";
    $password = "root";
    $database = "smarthome";
}  

$conn = new mysqli($servername, $dbusername, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

switch ($method)  {
    case 'GET':
        // ✅ Filter by usemethodrname if provided (admin can use ?user=All to get all)
        $filterUser = $_GET['user'] ?? '';
        if ($filterUser && strtolower($filterUser) !== 'all') {
            $stmt = $conn->prepare("SELECT * FROM install WHERE username = ? ORDER BY idno DESC");
            $stmt->bind_param("s", $filterUser);
        } else {
            $stmt = $conn->prepare("SELECT * FROM install ORDER BY idno DESC");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        echo json_encode($rows);
        break;

    case 'POST':
        $idno = $_POST['idno'];
        $name = $_POST['name'];
        $area = $_POST['area'];
        $cableLength = (int)$_POST['cableLength'];
        $cameraCount = (int)$_POST['cameraCount'];
        $bncCount = (int)$_POST['bncCount'];
        $tvInstalled = isset($_POST['tvInstalled']) ? 1 : 0;
        $rackInstalled = isset($_POST['rackInstalled']) ? 1 : 0;
        $kmRange = (int)$_POST['kmRange'];
        $extraLabel = $_POST['extraLabel'];
        $extraValue = (float)$_POST['extraValue'];
        $totalCostInput = (float)$_POST['totalCostInput'];
        $username = $_POST['username'] ?? 'unknown'; // ✅ Capture username

        // Convert DD-MM-YYYY to YYYY-MM-DD
        $dateInput = $_POST['date'];
        $date = date('Y-m-d', strtotime(str_replace('/', '-', $dateInput)));

        // ✅ Add username to query
        $stmt = $conn->prepare("REPLACE INTO install (idno, name, area, cable_length, camera_count, bnc_count, tv_installed, rack_installed, km_range, extra_label, extra_value, total_cost, date, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiiiissddss", $idno, $name, $area, $cableLength, $cameraCount, $bncCount, $tvInstalled, $rackInstalled, $kmRange, $extraLabel, $extraValue, $totalCostInput, $date, $username);

        $stmt->execute();

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $delVars);
        $idno = $conn->real_escape_string($delVars['idno']);
        $conn->query("DELETE FROM install WHERE idno = '$idno'");
        echo json_encode(['success' => true]);
        break;

    case 'PATCH':
        parse_str(file_get_contents("php://input"), $patchVars);
        $idno = $conn->real_escape_string($patchVars['idno'] ?? '');
        $action = $conn->real_escape_string($patchVars['action'] ?? '');
        if (!$idno || !$action) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing idno or action']);
            exit;
        }
        switch ($action) {
            case 'submit':
                $conn->query("UPDATE install SET Submitted = 'Yes' WHERE idno = '$idno'");
                break;

            case 'approve':
                $conn->query("UPDATE install SET Approved = 'Yes' WHERE idno = '$idno'");
                break;

            case 'pay':
                $conn->query("UPDATE install SET Payment = 'Yes' WHERE idno = '$idno'");
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                exit;
        }

        echo json_encode(['success' => true]);
        break;
}
?>
