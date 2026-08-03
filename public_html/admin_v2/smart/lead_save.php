<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = @new mysqli($host, $username, $password, $database);
} catch (mysqli_sql_exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = null;
if ($rawInput !== false && trim($rawInput) !== '') {
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = null;
    }
}
if ($data === null) {
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
}

$conn->query("SET time_zone = '+05:30'");
date_default_timezone_set('Asia/Kolkata');

$role = $_COOKIE['auth_role'] ?? '';
$isAdmin = in_array(strtolower(trim((string)$role)), ['admin', 'manager'], true);
$canDeleteLead = strtolower(trim((string)$role)) === 'admin';
$authName = $_COOKIE['auth_name'] ?? '';
$authUser = $_COOKIE['auth_user'] ?? '';
$codeSource = $authName !== '' ? $authName : $authUser;
$letters = preg_replace('/[^a-zA-Z]/', '', $codeSource);
$letters = strtoupper($letters);
$userCode = $letters !== '' ? substr($letters, 0, 3) : '';

$isUnassignedAssign = function ($assignRaw) {
    $v = strtolower(trim((string)$assignRaw));
    return ($v === '' || $v === 'open' || $v === '-' || $v === 'na');
};

$isAssignedAssign = function ($assignRaw) use ($isUnassignedAssign) {
    return !$isUnassignedAssign($assignRaw);
};

$normalizeAssign = function ($assignRaw) {
    $v = strtolower(trim((string)$assignRaw));
    $allowed = [
        '' => 'Open',
        '-' => 'Open',
        'na' => 'Open',
        'open' => 'Open',
        'amr' => 'AMR',
        'var' => 'VAR',
        'sur' => 'SUR',
    ];
    return array_key_exists($v, $allowed) ? $allowed[$v] : null;
};

$requestedAssign = null;
if (array_key_exists('Assign', $data)) {
    $requestedAssign = $normalizeAssign($data['Assign']);
    if ($requestedAssign === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid assignee.']);
        $conn->close();
        exit;
    }
    $data['Assign'] = $requestedAssign;
}

$editTraceColCheck = $conn->query("SHOW COLUMNS FROM leads LIKE 'edit_trace'");
if ($editTraceColCheck && $editTraceColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN edit_trace LONGTEXT NULL");
}
$originalAssignColCheck = $conn->query("SHOW COLUMNS FROM leads LIKE 'originally_assigned'");
if ($originalAssignColCheck && $originalAssignColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN originally_assigned VARCHAR(255) NULL");
}
$quoteColCheck = $conn->query("SHOW COLUMNS FROM leads LIKE 'quote'");
if ($quoteColCheck && $quoteColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN quote VARCHAR(50) NULL");
}
$installColCheck = $conn->query("SHOW COLUMNS FROM leads LIKE 'installation_date'");
if ($installColCheck && $installColCheck->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN installation_date VARCHAR(20) NULL");
}
$stampDate = strtoupper(date('d M'));
$stampTime = date('H:i');
$editEntry = ($userCode !== '' ? $userCode : 'UNK') . " | " . $stampDate . " | " . $stampTime;
$appendEditTrace = function($existing, $entry) {
    $existing = trim((string)$existing);
    $entry = trim((string)$entry);
    if ($entry === '') return $existing;
    if ($existing === '') return $entry;
    $suffix = ' > ' . $entry;
    if (substr($existing, -strlen($suffix)) === $suffix) return $existing;
    if (substr($existing, -strlen($entry)) === $entry) return $existing;
    return $existing . ' > ' . $entry;
};

$leadId = $data['leadId'] ?? null; 

if (!empty($data['deleteLead'])) {
    if (!$canDeleteLead) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only admin can delete leads.']);
        $conn->close();
        exit;
    }

    $deleteLeadIdRaw = $leadId ?? ($data['id'] ?? '');
    if (is_string($deleteLeadIdRaw) && stripos($deleteLeadIdRaw, 'L-') === 0) {
        $deleteLeadIdRaw = substr($deleteLeadIdRaw, 2);
    }
    $deleteLeadId = intval($deleteLeadIdRaw);
    if ($deleteLeadId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Lead id is required.']);
        $conn->close();
        exit;
    }

    $exists = $conn->query("SELECT id FROM leads WHERE id = " . $deleteLeadId . " LIMIT 1");
    if (!$exists || $exists->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        $conn->close();
        exit;
    }

    $deleted = $conn->query("DELETE FROM leads WHERE id = " . $deleteLeadId . " LIMIT 1");
    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Lead deleted.', 'id' => $deleteLeadId]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
    }
    $conn->close();
    exit;
}

$mid = isset($data['mid']) ? trim((string)$data['mid']) : '';
$quoteLinkAppend = isset($data['quote_link_append']) ? trim((string)$data['quote_link_append']) : '';

if ($quoteLinkAppend !== '') {
    $colCheck = $conn->query("SHOW COLUMNS FROM leads LIKE 'quote_links'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $alterOk = $conn->query("ALTER TABLE leads ADD COLUMN quote_links LONGTEXT NULL");
        if (!$alterOk) {
            echo json_encode(['success' => false, 'message' => 'Failed to add quote_links column: ' . $conn->error]);
            $conn->close();
            exit;
        }
    }

    $idInt = $leadId !== null ? intval($leadId) : 0;
    $midEsc = $conn->real_escape_string($mid);
    $where = '';
    if ($idInt > 0) {
        $where = "id = $idInt";
    } else if ($midEsc !== '') {
        $where = "MID = '$midEsc'";
    }

    if ($where === '') {
        echo json_encode(['success' => false, 'message' => 'Missing leadId or mid for quote save.']);
        $conn->close();
        exit;
    }

    $res = $conn->query("SELECT id, quote_links, Assign, edit_trace FROM leads WHERE $where LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead not found for quote save.']);
        $conn->close();
        exit;
    }

    $row = $res->fetch_assoc();
    $existingRaw = isset($row['quote_links']) ? (string)$row['quote_links'] : '';
    $existing = array_values(array_filter(array_map('trim', explode(',', $existingRaw)), function($v) { return $v !== ''; }));
    $currentAssign = isset($row['Assign']) ? trim((string)$row['Assign']) : '';
    $currentIsOpen = $isUnassignedAssign($currentAssign);
    $assignToSet = null;
    if (!$isAdmin && $currentIsOpen && $userCode !== '') $assignToSet = $userCode;
    $existingEditTrace = isset($row['edit_trace']) ? (string)$row['edit_trace'] : '';
    $nextEditTrace = $appendEditTrace($existingEditTrace, $editEntry);

    if (!in_array($quoteLinkAppend, $existing, true)) {
        $existing[] = $quoteLinkAppend;
    }

    $newVal = $conn->real_escape_string(implode(',', $existing));
    $idSaved = intval($row['id']);

    $setParts = ["quote_links = '$newVal'", "edit_trace = '" . $conn->real_escape_string($nextEditTrace) . "'"];
    if ($assignToSet !== null && $assignToSet !== $currentAssign) {
        $assignEsc = $conn->real_escape_string($assignToSet);
        $setParts[] = "Assign = '$assignEsc'";
        $currentAssign = $assignToSet;
    }
    $upd = $conn->query("UPDATE leads SET " . implode(', ', $setParts) . " WHERE id = $idSaved");
    if (!$upd) {
        echo json_encode(['success' => false, 'message' => 'Failed to save quote link: ' . $conn->error]);
        $conn->close();
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Quote saved.', 'id' => $idSaved, 'quote_links' => implode(',', $existing), 'assign' => $currentAssign, 'edit_trace' => $nextEditTrace]);
    $conn->close();
    exit;
}

$placeOrder = isset($data['place_order']) && ($data['place_order'] === true || $data['place_order'] === 1 || $data['place_order'] === '1');
if ($placeOrder) {
    $leadIdInt = $leadId !== null ? intval($leadId) : 0;
    if ($leadIdInt <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing leadId for order placement.']);
        $conn->close();
        exit;
    }

    $rawPrice = isset($data['quote']) ? trim((string)$data['quote']) : '';
    if ($rawPrice === '') {
        echo json_encode(['success' => false, 'message' => 'Quote amount is required to place order.']);
        $conn->close();
        exit;
    }
    $cleanPrice = preg_replace('/[^\d.]/', '', $rawPrice);
    if ($cleanPrice === '' || !is_numeric($cleanPrice)) {
        echo json_encode(['success' => false, 'message' => 'Invalid quote amount.']);
        $conn->close();
        exit;
    }
    $data['quote'] = $cleanPrice;
    $data['call_status'] = 'Ordered';

    $allowed_fields = [
        'Name', 'whatsapp_number', 'num_cameras', 'dvr_type', 'hdd_size',
        'camera_resolution', 'Assign', 'Area', 'Follow_up',
        'comments', 'Message', 'map_link', 'quote', 'installation_date', 'call_status'
    ];

    $table_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM leads");
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to load leads schema.']);
        $conn->close();
        exit;
    }
    while ($row = $result->fetch_assoc()) {
        $table_columns[] = $row['Field'];
    }

    $fields_to_process = [];
    $escaped_values = [];
    foreach ($allowed_fields as $f) {
        if (isset($data[$f]) && in_array($f, $table_columns, true)) {
            $fields_to_process[] = $f;
            $escaped_values[$f] = $conn->real_escape_string($data[$f]);
        }
    }

    $assignRow = $conn->query("SELECT Assign, originally_assigned, edit_trace FROM leads WHERE id = $leadIdInt LIMIT 1");
    if (!$assignRow || $assignRow->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        $conn->close();
        exit;
    }
    $assignDb = $assignRow->fetch_assoc();
    $currentAssign = isset($assignDb['Assign']) ? trim((string)$assignDb['Assign']) : '';
    $currentIsOpen = $isUnassignedAssign($currentAssign);
    $originallyAssigned = isset($assignDb['originally_assigned']) ? trim((string)$assignDb['originally_assigned']) : '';
    $existingEditTrace = isset($assignDb['edit_trace']) ? (string)$assignDb['edit_trace'] : '';

    if (in_array('Assign', $table_columns, true)) {
        $fields_to_process = array_values(array_filter($fields_to_process, function ($f) { return $f !== 'Assign'; }));
        unset($escaped_values['Assign']);

        if ($isAdmin && $requestedAssign !== null) {
            $fields_to_process[] = 'Assign';
            $escaped_values['Assign'] = $conn->real_escape_string($requestedAssign);
            if ($requestedAssign === 'Open' && $isAssignedAssign($currentAssign)) {
                $fields_to_process[] = 'originally_assigned';
                $escaped_values['originally_assigned'] = $conn->real_escape_string($currentAssign);
                $originallyAssigned = $currentAssign;
            }
            $currentAssign = $requestedAssign;
        } else if ($currentIsOpen && $userCode !== '') {
            $fields_to_process[] = 'Assign';
            $escaped_values['Assign'] = $conn->real_escape_string($userCode);
            $currentAssign = $userCode;
        }
    }
    if (in_array('edit_trace', $table_columns, true)) {
        $fields_to_process = array_values(array_filter($fields_to_process, function ($f) { return $f !== 'edit_trace'; }));
        unset($escaped_values['edit_trace']);
        $fields_to_process[] = 'edit_trace';
        $escaped_values['edit_trace'] = $conn->real_escape_string($appendEditTrace($existingEditTrace, $editEntry));
    }

    $updates = [];
    foreach ($fields_to_process as $f) {
        $updates[] = "$f = '{$escaped_values[$f]}'";
    }
    if (count($updates) === 0) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to update for order placement.']);
        $conn->close();
        exit;
    }

    $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = " . $leadIdInt;
    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Failed to update lead before placing order: ' . $conn->error]);
        $conn->close();
        exit;
    }

    $leadSelectFields = [
        'id',
        'MID',
        'whatsapp_number',
        'quote',
        'installation_date',
        'num_cameras',
        'dvr_type',
        'hdd_size',
        'camera_resolution',
        'Name',
        'Area',
        'Assign',
        'created_at'
    ];
    if (in_array('lead_campaign', $table_columns, true)) {
        $leadSelectFields[] = 'lead_campaign';
    }
    $leadRes = $conn->query("SELECT " . implode(', ', $leadSelectFields) . " FROM leads WHERE id = $leadIdInt LIMIT 1");
    if (!$leadRes || $leadRes->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Failed to load lead details for order placement.']);
        $conn->close();
        exit;
    }
    $leadRow = $leadRes->fetch_assoc();

    $orderColsRes = $conn->query("SHOW COLUMNS FROM orders");
    if (!$orderColsRes) {
        echo json_encode(['success' => false, 'message' => 'Failed to load orders schema.']);
        $conn->close();
        exit;
    }
    $orders_columns = [];
    while ($r = $orderColsRes->fetch_assoc()) {
        $orders_columns[] = $r['Field'];
    }

    $findOrderCol = function ($candidates) use ($orders_columns) {
        foreach ($candidates as $c) {
            foreach ($orders_columns as $existing) {
                if (strcasecmp($existing, $c) === 0) return $existing;
            }
        }
        return null;
    };

    $idnoCol = $findOrderCol(['idno']);
    if ($idnoCol === null) {
        echo json_encode(['success' => false, 'message' => 'orders table is missing idno column.']);
        $conn->close();
        exit;
    }

    $phoneCol = $findOrderCol(['phone']);
    $priceCol = $findOrderCol([
        'price',
        'amount',
        'total',
        'total_amount',
        'final_amount',
        'final',
        'order_amount',
        'order_value',
        'value',
        'cost',
        'total_cost',
        'quoted_amount',
        'quote_amount',
        'quotation',
        'quotation_amount',
        'quote'
    ]);
    $qtyCol = $findOrderCol(['quantity']);
    $productCol = $findOrderCol(['product']);
    $storageCol = $findOrderCol(['storage', 'storge']);
    $resolutionCol = $findOrderCol(['resolution']);
    $nameCol = $findOrderCol(['name']);
    $areaCol = $findOrderCol(['area']);
    $notesCol = $findOrderCol(['notes', 'note']);
    $ownerCol = $findOrderCol(['Owner', 'owner']);
    $leadCampaignCol = $findOrderCol(['lead_campaign']);
    $createdAtCol = $findOrderCol(['created_at', 'createdon', 'created_on']);
    $dateCol = $findOrderCol([
        'date',
        'install_date',
        'installation_date',
        'installationdate',
        'installDate',
        'installationDate',
        'installtion_date',
        'installtiondate',
        'scheduled_date',
        'schedule_date',
        'order_date',
        'date_of_installation',
        'installation_day'
    ]);

    $idnoVal = isset($leadRow['MID']) ? trim((string)$leadRow['MID']) : '';
    if ($idnoVal === '') $idnoVal = (string)$leadIdInt;

    $valuesByCol = [];
    $valuesByCol[$idnoCol] = $idnoVal;
    if ($phoneCol !== null) $valuesByCol[$phoneCol] = isset($leadRow['whatsapp_number']) ? (string)$leadRow['whatsapp_number'] : '';
    if ($priceCol !== null) $valuesByCol[$priceCol] = $cleanPrice;
    if ($qtyCol !== null) $valuesByCol[$qtyCol] = isset($leadRow['num_cameras']) ? (string)$leadRow['num_cameras'] : '';
    if ($productCol !== null) $valuesByCol[$productCol] = isset($leadRow['dvr_type']) ? (string)$leadRow['dvr_type'] : '';
    if ($storageCol !== null) $valuesByCol[$storageCol] = isset($leadRow['hdd_size']) ? (string)$leadRow['hdd_size'] : '';
    if ($resolutionCol !== null) $valuesByCol[$resolutionCol] = isset($leadRow['camera_resolution']) ? (string)$leadRow['camera_resolution'] : '';
    if ($nameCol !== null) $valuesByCol[$nameCol] = isset($leadRow['Name']) ? (string)$leadRow['Name'] : '';
    if ($areaCol !== null) $valuesByCol[$areaCol] = isset($leadRow['Area']) ? (string)$leadRow['Area'] : '';
    if ($notesCol !== null) $valuesByCol[$notesCol] = '';
    if ($ownerCol !== null) $valuesByCol[$ownerCol] = isset($leadRow['Assign']) ? (string)$leadRow['Assign'] : '';
    if ($leadCampaignCol !== null) $valuesByCol[$leadCampaignCol] = isset($leadRow['lead_campaign']) ? (string)$leadRow['lead_campaign'] : '';
    if ($createdAtCol !== null) $valuesByCol[$createdAtCol] = isset($leadRow['created_at']) ? (string)$leadRow['created_at'] : '';
    if ($dateCol !== null) {
        $installationDate = isset($data['installation_date']) ? trim((string)$data['installation_date']) : '';
        if ($installationDate === '') $installationDate = date('Y-m-d');
        $valuesByCol[$dateCol] = $installationDate;
    }

    $insertCols = [];
    $placeholders = [];
    $types = '';
    $values = [];
    foreach ($valuesByCol as $col => $val) {
        $insertCols[] = "`$col`";
        $placeholders[] = "?";
        $types .= 's';
        $values[] = $val === null ? null : (string)$val;
    }

    $updateParts = [];
    foreach (array_keys($valuesByCol) as $col) {
        if (strcasecmp($col, $idnoCol) === 0) continue;
        $updateParts[] = "`$col` = VALUES(`$col`)";
    }

    $insertSql = "INSERT INTO orders (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    if (!empty($updateParts)) {
        $insertSql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
    }

    $stmt = $conn->prepare($insertSql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare order insert: ' . $conn->error]);
        $conn->close();
        exit;
    }
    $bindParams = [];
    $bindParams[] = &$types;
    foreach ($values as $k => $v) {
        $bindParams[] = &$values[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $ok = $stmt->execute();
    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Failed to insert order: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    $qr = $conn->query("SELECT Assign, originally_assigned, quote_links, edit_trace FROM leads WHERE id = $leadIdInt LIMIT 1");
    $outAssign = $currentAssign;
    $outOriginallyAssigned = $originallyAssigned;
    $outQuoteLinks = null;
    $outEditTrace = null;
    if ($qr && $qr->num_rows > 0) {
        $qrRow = $qr->fetch_assoc();
        if (isset($qrRow['Assign'])) $outAssign = $qrRow['Assign'];
        if (isset($qrRow['originally_assigned'])) $outOriginallyAssigned = $qrRow['originally_assigned'];
        if (isset($qrRow['quote_links'])) $outQuoteLinks = $qrRow['quote_links'];
        if (isset($qrRow['edit_trace'])) $outEditTrace = $qrRow['edit_trace'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order placed.',
        'id' => $leadIdInt,
        'assign' => $outAssign,
        'originally_assigned' => $outOriginallyAssigned,
        'quote_links' => $outQuoteLinks,
        'edit_trace' => $outEditTrace,
        'order_idno' => $idnoVal
    ]);
    $conn->close();
    exit;
}

// Whitelist of allowed fields from the client
$allowed_fields = [
    'Name', 'whatsapp_number', 'num_cameras', 'dvr_type', 'hdd_size',
    'camera_resolution', 'Assign', 'Area', 'Follow_up',
    'comments', 'Message', 'map_link', 'quote', 'installation_date', 'call_status'
];

$fields_to_process = [];
$escaped_values = [];

// Get table columns to prevent errors from unknown columns
$table_columns = [];
$result = $conn->query("SHOW COLUMNS FROM leads");
while($row = $result->fetch_assoc()){
    $table_columns[] = $row['Field'];
}

foreach ($allowed_fields as $f) {
    if (isset($data[$f]) && in_array($f, $table_columns)) {
        // Special handling for 'quote' field to remove commas
        if ($f === 'quote') {
            $cleanQuote = preg_replace('/[^\d.]/', '', $data[$f]);
            if ($cleanQuote === '' || !is_numeric($cleanQuote)) {
                $data[$f] = ''; // Set to empty string if not a valid number after cleaning
            } else {
                $data[$f] = $cleanQuote;
            }
        }
        $fields_to_process[] = $f;
        $escaped_values[$f] = $conn->real_escape_string($data[$f]);
    }
}

if (empty($leadId) && empty($data['whatsapp_number'])) {
    echo json_encode(['success' => false, 'message' => 'WhatsApp number is required.']);
    exit;
}

if (empty($leadId)) {
    if (in_array('Assign', $table_columns)) {
        $fields_to_process = array_values(array_filter($fields_to_process, function($f) { return $f !== 'Assign'; }));
        unset($escaped_values['Assign']);
        $newAssign = $isAdmin
            ? ($requestedAssign !== null ? $requestedAssign : 'Open')
            : ($userCode !== '' ? $userCode : 'Open');
        $fields_to_process[] = 'Assign';
        $escaped_values['Assign'] = $conn->real_escape_string($newAssign);
    }
    if (in_array('edit_trace', $table_columns) && !isset($escaped_values['edit_trace'])) {
        $fields_to_process[] = 'edit_trace';
        $escaped_values['edit_trace'] = $conn->real_escape_string($editEntry);
    }
    // INSERT
    if (empty($fields_to_process)) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to insert.']);
        exit;
    }
    $cols = implode(',', $fields_to_process);
    $vals = "'" . implode("','", $escaped_values) . "'";
    $sql = "INSERT INTO leads ($cols) VALUES ($vals)";
    $result = $conn->query($sql);

    if ($result) {
        $newId = (int)$conn->insert_id;
        $assignOut = isset($escaped_values['Assign']) ? $escaped_values['Assign'] : null;
        $traceOut = isset($escaped_values['edit_trace']) ? $escaped_values['edit_trace'] : null;
        echo json_encode(['success' => true, 'message' => 'New lead created.', 'id' => $newId, 'assign' => $assignOut, 'edit_trace' => $traceOut]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: '.$conn->error]);
    }
} else {
    // UPDATE
    $leadIdInt = intval($leadId);
    $assignRow = $conn->query("SELECT Assign, originally_assigned, edit_trace FROM leads WHERE id = $leadIdInt LIMIT 1");
    if (!$assignRow || $assignRow->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lead not found.']);
        $conn->close();
        exit;
    }
    $assignDb = $assignRow->fetch_assoc();
    $currentAssign = isset($assignDb['Assign']) ? trim((string)$assignDb['Assign']) : '';
    $currentIsOpen = $isUnassignedAssign($currentAssign);
    $originallyAssigned = isset($assignDb['originally_assigned']) ? trim((string)$assignDb['originally_assigned']) : '';
    $existingEditTrace = isset($assignDb['edit_trace']) ? (string)$assignDb['edit_trace'] : '';

    if (in_array('Assign', $table_columns)) {
        $fields_to_process = array_values(array_filter($fields_to_process, function($f) { return $f !== 'Assign'; }));
        unset($escaped_values['Assign']);

        if ($isAdmin && $requestedAssign !== null) {
            $fields_to_process[] = 'Assign';
            $escaped_values['Assign'] = $conn->real_escape_string($requestedAssign);
            if ($requestedAssign === 'Open' && $isAssignedAssign($currentAssign)) {
                $fields_to_process[] = 'originally_assigned';
                $escaped_values['originally_assigned'] = $conn->real_escape_string($currentAssign);
                $originallyAssigned = $currentAssign;
            }
            $currentAssign = $requestedAssign;
        } else if ($currentIsOpen && $userCode !== '') {
            $fields_to_process[] = 'Assign';
            $escaped_values['Assign'] = $conn->real_escape_string($userCode);
            $currentAssign = $userCode;
        }
    }
    if (in_array('edit_trace', $table_columns)) {
        $fields_to_process = array_values(array_filter($fields_to_process, function($f) { return $f !== 'edit_trace'; }));
        unset($escaped_values['edit_trace']);
        $fields_to_process[] = 'edit_trace';
        $escaped_values['edit_trace'] = $conn->real_escape_string($appendEditTrace($existingEditTrace, $editEntry));
    }

    $updates = [];
    foreach ($fields_to_process as $f) {
        // Allow whatsapp_number to be updated as well if it's provided.
        $updates[] = "$f = '{$escaped_values[$f]}'";
    }
    
    if (count($updates) > 0) {
        $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = " . $leadIdInt;
        $result = $conn->query($sql);

        if ($result) {
            $outAssign = $currentAssign;
            $qr = $conn->query("SELECT Assign, originally_assigned, quote_links, edit_trace FROM leads WHERE id = $leadIdInt LIMIT 1");
            $outQuoteLinks = null;
            $outEditTrace = null;
            $outOriginallyAssigned = $originallyAssigned;
            if ($qr && $qr->num_rows > 0) {
                $qrRow = $qr->fetch_assoc();
                if (isset($qrRow['Assign'])) $outAssign = $qrRow['Assign'];
                if (isset($qrRow['originally_assigned'])) $outOriginallyAssigned = $qrRow['originally_assigned'];
                if (isset($qrRow['quote_links'])) $outQuoteLinks = $qrRow['quote_links'];
                if (isset($qrRow['edit_trace'])) $outEditTrace = $qrRow['edit_trace'];
            }
            echo json_encode(['success' => true, 'message' => 'Lead updated successfully.', 'id' => $leadIdInt, 'assign' => $outAssign, 'originally_assigned' => $outOriginallyAssigned, 'quote_links' => $outQuoteLinks, 'edit_trace' => $outEditTrace]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed: '.$conn->error]);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'No valid fields to update.', 'id' => $leadIdInt, 'assign' => $currentAssign, 'originally_assigned' => $originallyAssigned, 'edit_trace' => $existingEditTrace]);
    }
}

$conn->close();
?>
