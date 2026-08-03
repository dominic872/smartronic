<?php
header('Content-Type: application/json');

$safe_error = function($message) {
    echo json_encode(['error' => $message]);
    exit;
};

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';
// Prevent mysqli from throwing exceptions so we can return JSON cleanly
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    $safe_error('Database connection failed');
}

$conn->query("SET time_zone = '+05:30'");
date_default_timezone_set('Asia/Kolkata');

$role = $_COOKIE['auth_role'] ?? '';
$isAdmin = in_array(strtolower(trim((string)$role)), ['admin', 'manager'], true);
$isMarket = ($role === 'market');
if (!$isAdmin && !$isMarket) {
    http_response_code(403);
    echo json_encode(['error' => 'No access']);
    $conn->close();
    exit;
}

$authName = $_COOKIE['auth_name'] ?? '';
$authUser = $_COOKIE['auth_user'] ?? '';
$codeSource = $authName !== '' ? $authName : $authUser;
$letters = preg_replace('/[^a-zA-Z]/', '', $codeSource);
$letters = strtoupper($letters);
$userCode = $letters !== '' ? substr($letters, 0, 3) : '';
$userCodeLower = strtolower($userCode);
$userAssignCond = '';
if (!$isAdmin) {
    if ($userCodeLower !== '') {
        $userCodeLowerSafe = $conn->real_escape_string($userCodeLower);
        $userAssignCond = "LOWER(TRIM(Assign)) = '$userCodeLowerSafe'";
    } else {
        $userAssignCond = "0=1";
    }
}

$origAssignColRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'originally_assigned'");
if ($origAssignColRes && $origAssignColRes->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN originally_assigned VARCHAR(255) NULL");
}

$hasUpdatedAt = false;
$colRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'updated_at'");
if ($colRes && $colRes->num_rows > 0) $hasUpdatedAt = true;
$hasQuoteLinks = false;
$quoteLinksColRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'quote_links'");
if ($quoteLinksColRes && $quoteLinksColRes->num_rows > 0) $hasQuoteLinks = true;
$tsExpr = $hasUpdatedAt ? "COALESCE(updated_at, created_at)" : "created_at";
$openCond = "(COALESCE(TRIM(Assign), '') = '' OR LOWER(TRIM(Assign)) = 'open')";
$statusCol = null;
$csRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'call_status'");
if ($csRes && $csRes->num_rows > 0) $statusCol = 'call_status';
if ($statusCol === null) {
    $sRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'status'");
    if ($sRes && $sRes->num_rows > 0) $statusCol = 'status';
}
$statusColSql = $statusCol !== null ? "`$statusCol`" : null;
$assignNotOpenCond = "COALESCE(TRIM(Assign), '') <> '' AND TRIM(Assign) <> '-' AND LOWER(TRIM(Assign)) <> 'open' AND LOWER(TRIM(Assign)) <> 'na'";
$quoteNotSentCond = $hasQuoteLinks ? "(quote_links IS NULL OR TRIM(quote_links) = '')" : "1=1";
$staleBaseCond = "$assignNotOpenCond AND $quoteNotSentCond AND $tsExpr IS NOT NULL AND TIMESTAMPDIFF(MINUTE, $tsExpr, NOW()) >= 90";
$hour = (int)date('G');
$inQuietWindow = ($hour >= 20 || $hour < 9);
if (!$inQuietWindow) {
    if ($statusColSql !== null) {
        $statusEmptyCond = "(COALESCE(TRIM($statusColSql), '') = '' OR TRIM($statusColSql) = '-' OR LOWER(TRIM($statusColSql)) = 'na')";
        $staleRes = $conn->query("SELECT id FROM leads WHERE $staleBaseCond AND $statusEmptyCond LIMIT 25");
    } else {
        $staleRes = $conn->query("SELECT id FROM leads WHERE $staleBaseCond LIMIT 25");
    }
    $staleIds = [];
    if ($staleRes) {
        while ($r = $staleRes->fetch_assoc()) {
            $staleIds[] = (int)$r['id'];
        }
    }
    if (!empty($staleIds)) {
        $idList = implode(',', array_map('intval', $staleIds));
        $conn->query("UPDATE leads SET originally_assigned = TRIM(Assign), Assign = 'Open' WHERE id IN ($idList)");
    }
}

$ensureWhatsAppTables = function() use ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS lead_whatsapp_templates (
        id INT NOT NULL AUTO_INCREMENT,
        user_code VARCHAR(16) NOT NULL,
        message_text TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_code (user_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS lead_whatsapp_sent (
        id INT NOT NULL AUTO_INCREMENT,
        lead_id INT NOT NULL,
        user_code VARCHAR(16) NOT NULL,
        message_text TEXT NOT NULL,
        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_lead_id (lead_id),
        KEY idx_user_code (user_code),
        KEY idx_sent_at (sent_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
};

$assertLeadAccess = function($leadId) use ($conn, $isAdmin, $userAssignCond, $openCond) {
    if ($isAdmin) return true;
    $lid = (int)$leadId;
    if ($lid <= 0) return false;
    $q = "SELECT id FROM leads WHERE id = $lid AND ($userAssignCond OR $openCond) LIMIT 1";
    $res = $conn->query($q);
    return ($res && $res->num_rows > 0);
};

if (isset($_GET['wa_templates'])) {
    $ensureWhatsAppTables();
    $rows = [];
    $res = $conn->query("SELECT id, message_text FROM lead_whatsapp_templates WHERE user_code = 'global' ORDER BY id DESC LIMIT 200");
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    echo json_encode($rows);
    $conn->close();
    exit;
}

if (isset($_GET['wa_history'])) {
    $ensureWhatsAppTables();
    $leadId = isset($_GET['lead_id']) ? (int)$_GET['lead_id'] : 0;
    if ($leadId <= 0 || !$assertLeadAccess($leadId)) {
        echo json_encode([]);
        $conn->close();
        exit;
    }
    $rows = [];
    $res = $conn->query("SELECT message_text, sent_at FROM lead_whatsapp_sent WHERE lead_id = $leadId ORDER BY sent_at DESC, id DESC LIMIT 500");
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    $grouped = [];
    foreach ($rows as $r) {
        $msg = isset($r['message_text']) ? (string)$r['message_text'] : '';
        if ($msg === '') continue;
        if (!isset($grouped[$msg])) {
            $grouped[$msg] = ['message_text' => $msg, 'sent_times' => []];
        }
        $grouped[$msg]['sent_times'][] = $r['sent_at'];
    }
    echo json_encode(array_values($grouped));
    $conn->close();
    exit;
}

if (isset($_GET['wa_template_add'])) {
    $ensureWhatsAppTables();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => 0, 'error' => 'Only admin can add']);
        $conn->close();
        exit;
    }
    $msg = isset($_POST['message_text']) ? trim((string)$_POST['message_text']) : '';
    if ($msg === '') {
        echo json_encode(['success' => 0, 'error' => 'Empty message']);
        $conn->close();
        exit;
    }
    $msgSafe = $conn->real_escape_string($msg);
    $ok = $conn->query("INSERT INTO lead_whatsapp_templates (user_code, message_text) VALUES ('global', '$msgSafe')");
    if ($ok) {
        echo json_encode(['success' => 1, 'id' => $conn->insert_id, 'message_text' => $msg]);
    } else {
        echo json_encode(['success' => 0, 'error' => 'Failed to save']);
    }
    $conn->close();
    exit;
}

if (isset($_GET['wa_template_delete'])) {
    $ensureWhatsAppTables();
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['success' => 0, 'error' => 'Only admin can remove']);
        $conn->close();
        exit;
    }
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => 0, 'error' => 'Invalid id']);
        $conn->close();
        exit;
    }
    $ok = $conn->query("DELETE FROM lead_whatsapp_templates WHERE id = $id AND user_code = 'global' LIMIT 1");
    if ($ok) {
        echo json_encode(['success' => 1]);
    } else {
        echo json_encode(['success' => 0, 'error' => 'Failed to remove']);
    }
    $conn->close();
    exit;
}

if (isset($_GET['wa_send'])) {
    $ensureWhatsAppTables();
    $leadId = isset($_POST['lead_id']) ? (int)$_POST['lead_id'] : 0;
    $msg = isset($_POST['message_text']) ? trim((string)$_POST['message_text']) : '';
    if ($leadId <= 0 || $msg === '' || !$assertLeadAccess($leadId)) {
        echo json_encode(['success' => 0, 'error' => 'Invalid request']);
        $conn->close();
        exit;
    }
    $userCodeSafe = $conn->real_escape_string($userCodeLower);
    $msgSafe = $conn->real_escape_string($msg);
    $ok = $conn->query("INSERT INTO lead_whatsapp_sent (lead_id, user_code, message_text) VALUES ($leadId, '$userCodeSafe', '$msgSafe')");
    if ($ok) {
        echo json_encode(['success' => 1, 'sent_at' => date('Y-m-d H:i:s')]);
    } else {
        echo json_encode(['success' => 0, 'error' => 'Failed to log']);
    }
    $conn->close();
    exit;
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$limit = max(1, min(200, $limit));
$searchRaw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search = $searchRaw !== '' ? $conn->real_escape_string($searchRaw) : '';
$mineRaw = isset($_GET['mine']) ? trim($_GET['mine']) : '';
$mineNorm = strtolower(preg_replace('/[^a-z]/', '', $mineRaw));
$mineNorm = substr($mineNorm, 0, 10);
$mine = $mineNorm !== '' ? $conn->real_escape_string($mineNorm) : '';
$followRaw = isset($_GET['follow']) ? trim($_GET['follow']) : '';
$followNorm = strtolower($followRaw);
$follow = in_array($followNorm, ['today', 'tomorrow', 'yesterday'], true) ? $conn->real_escape_string($followNorm) : '';
$midsRaw = isset($_GET['mids']) ? trim($_GET['mids']) : '';
$leadId = isset($_GET['leadId']) ? $conn->real_escape_string($_GET['leadId']) : '';
$statusRaw = isset($_GET['status']) ? trim($_GET['status']) : '';
$statusFilter = $statusRaw !== '' ? $conn->real_escape_string($statusRaw) : '';
$col1Raw = isset($_GET['col1']) ? trim($_GET['col1']) : '';
$col1Filter = $col1Raw !== '' ? $conn->real_escape_string($col1Raw) : '';
$col2Raw = isset($_GET['col2']) ? trim($_GET['col2']) : '';
$col2Filter = $col2Raw !== '' ? $conn->real_escape_string($col2Raw) : '';
$dateModeRaw = isset($_GET['date_mode']) ? trim((string)$_GET['date_mode']) : '';
$dateModeNorm = strtolower(preg_replace('/[^a-z_]/', '', $dateModeRaw));
$dateModeNorm = substr($dateModeNorm, 0, 24);
$dateStartRaw = isset($_GET['date_start']) ? trim((string)$_GET['date_start']) : '';
$jumpEndDt = null;

// ✅ Fetch a single lead by ID
if (!empty($leadId)) {
    if (substr($leadId, 0, 2) === 'L-') {
        $id = substr($leadId, 2);
    } else {
        $id = $leadId;
    }

    $sql = "SELECT *, IFNULL(MID, id) as display_id FROM leads WHERE id = '$id'";
    if (!$isAdmin) {
        $sql .= " AND ($userAssignCond OR $openCond)";
    }
    $sql .= " LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!isset($row['display_id'])) {
            $row['display_id'] = $row['id'];
        }

        // Add stats for single lead
        if (!empty($row['created_at'])) {
            $dt = substr($row['created_at'], 0, 10);
            $dtSafe = $conn->real_escape_string($dt);
            $rid = (int)$row['id'];
            $dayStart = $conn->real_escape_string($dt . ' 00:00:00');
            $statsExtra = (!$isAdmin && $userAssignCond !== '') ? " AND ($userAssignCond OR $openCond)" : "";
            
            $countSql = "SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)$statsExtra";
            $cRes = $conn->query($countSql);
            $row['day_total'] = ($cRes && $r = $cRes->fetch_assoc()) ? $r['c'] : 1;

            $createdAtSafe = $conn->real_escape_string($row['created_at']);
            $rankSql = "SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)$statsExtra AND (created_at > '$createdAtSafe' OR (created_at = '$createdAtSafe' AND id >= $rid))";
            $rRes = $conn->query($rankSql);
            $row['day_rank'] = ($rRes && $r = $rRes->fetch_assoc()) ? $r['c'] : 1;
        } else {
            $row['day_total'] = 0;
            $row['day_rank'] = 0;
        }

        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'No record found']);
    }

    $conn->close();
    exit;
}

// ✅ Fetch paginated list
// We will use a subquery/manual calculation approach to ensure "Total in DB for that day" 
// is consistent regardless of whether a search filter is applied to the main query.
$sql = "SELECT *, IFNULL(MID, id) as display_id FROM leads";
$andConditions = [];

if (!empty($midsRaw)) {
    $tokens = array_filter(array_map('trim', explode(',', $midsRaw)));
    $unique = [];
    foreach ($tokens as $t) {
        if ($t === '') continue;
        $t = substr($t, 0, 64);
        $unique[$t] = true;
        if (count($unique) >= 60) break;
    }
    $midsList = array_keys($unique);
    if (count($midsList) === 0) {
        echo json_encode([]);
        $conn->close();
        exit;
    }

    $midQuoted = [];
    $idNums = [];
    foreach ($midsList as $m) {
        $midQuoted[] = "'" . $conn->real_escape_string($m) . "'";
        if (ctype_digit($m)) {
            $idNums[] = (int)$m;
        }
    }

    $midIn = "MID IN (" . implode(',', $midQuoted) . ")";
    if (!empty($idNums)) {
        $idIn = "id IN (" . implode(',', $idNums) . ")";
        $andConditions[] = "(" . $midIn . " OR " . $idIn . ")";
    } else {
        $andConditions[] = "(" . $midIn . ")";
    }

    $offset = 0;
    $limit = max(1, min(200, count($midsList)));
}

if (!$isAdmin) {
    $andConditions[] = "(" . $userAssignCond . " OR " . $openCond . ")";
} else if (!empty($mine)) {
    $andConditions[] = "(Assign LIKE '%$mine%' OR $openCond)";
}

if (!empty($follow)) {
    if ($follow === 'today') {
        $andConditions[] = "((Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = CURDATE()) OR (DATE(created_at) = CURDATE() AND (Follow_up IS NULL OR Follow_up = '' OR Follow_up = '-')))";
    } else if ($follow === 'tomorrow') {
        $andConditions[] = "(Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
    } else if ($follow === 'yesterday') {
        $andConditions[] = "(Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_SUB(CURDATE(), INTERVAL 1 DAY))";
    }
}

$dateClauseWrapped = '';

$dateCond = '';
if (in_array($dateModeNorm, ['this_month', 'last_month', 'month_minus_2', 'last_two_months', 'custom'], true)) {
    $startDt = null;
    $endDt = null;
    $tz = new DateTimeZone('Asia/Kolkata');
    $now = new DateTime('now', $tz);
    $thisMonthStart = new DateTime($now->format('Y-m-01 00:00:00'), $tz);

    if ($dateModeNorm === 'this_month') {
        $startDt = clone $thisMonthStart;
        $endDt = (clone $thisMonthStart)->modify('+1 month');
    } elseif ($dateModeNorm === 'last_month') {
        $startDt = (clone $thisMonthStart)->modify('-1 month');
        $endDt = clone $thisMonthStart;
    } elseif ($dateModeNorm === 'month_minus_2') {
        $startDt = (clone $thisMonthStart)->modify('-2 month');
        $endDt = (clone $thisMonthStart)->modify('-1 month');
    } elseif ($dateModeNorm === 'last_two_months') {
        $startDt = (clone $thisMonthStart)->modify('-1 month');
        $endDt = (clone $thisMonthStart)->modify('+1 month');
    } elseif ($dateModeNorm === 'custom') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStartRaw)) {
            $endBase = DateTime::createFromFormat('!Y-m-d', $dateStartRaw, $tz) ?: null;
            if ($endBase && $endBase->format('Y-m-d') === $dateStartRaw) {
                $jumpEndDt = (clone $endBase)->modify('+1 day');
            }
        }
    }

    if ($startDt && $endDt) {
        $startStr = $conn->real_escape_string($startDt->format('Y-m-d H:i:s'));
        $endStr = $conn->real_escape_string($endDt->format('Y-m-d H:i:s'));
        $dateCond = "created_at >= '$startStr' AND created_at < '$endStr'";
        $dateClauseWrapped = "($dateCond)";
        $andConditions[] = $dateClauseWrapped;
    }
}

if ($statusFilter !== '' && $statusColSql !== null) {
    $andConditions[] = "(LOWER(TRIM($statusColSql)) LIKE LOWER('%" . $statusFilter . "%'))";
}

if ($col1Filter !== '') {
    $andConditions[] = "(LOWER(TRIM(COALESCE(Column_1, ''))) LIKE LOWER('%" . $col1Filter . "%'))";
}

if ($col2Filter !== '') {
    $andConditions[] = "(LOWER(TRIM(COALESCE(Column_2, ''))) LIKE LOWER('%" . $col2Filter . "%'))";
}

if (!empty($search)) {
    $dateCandidate = null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchRaw)) {
        $dateCandidate = $searchRaw;
    } else if (preg_match('/[a-zA-Z]/', $searchRaw) || strpos($searchRaw, '-') !== false || strpos($searchRaw, '/') !== false) {
        $ts = strtotime($searchRaw);
        if ($ts !== false) {
            $y = (int)date('Y', $ts);
            if ($y >= 2000 && $y <= 2100) {
                $dateCandidate = date('Y-m-d', $ts);
            }
        }
    }

    $like = "%$search%";
    $orConditions = [
        "Name LIKE '$like'",
        "whatsapp_number LIKE '$like'",
        "Area LIKE '$like'",
        "Assign LIKE '$like'",
        "comments LIKE '$like'",
        "MID LIKE '$like'",
        "CAST(id AS CHAR) LIKE '$like'",
        "DATE_FORMAT(created_at, '%Y-%m-%d') LIKE '$like'",
        "DATE_FORMAT(created_at, '%d %b %y') LIKE '$like'",
        "DATE_FORMAT(created_at, '%d %b %Y') LIKE '$like'"
    ];
    if ($dateCandidate) {
        $dateCandidateEsc = $conn->real_escape_string($dateCandidate);
        $orConditions[] = "DATE(created_at) = '$dateCandidateEsc'";
    }

    $andConditions[] = "(" . implode(" OR ", $orConditions) . ")";
}

if ($dateModeNorm === 'custom' && $jumpEndDt && empty($midsRaw)) {
    $jumpEndStr = $conn->real_escape_string($jumpEndDt->format('Y-m-d H:i:s'));
    $anchorConditions = $andConditions;
    $anchorConditions[] = "(created_at < '$jumpEndStr')";
    $anchorSql = "SELECT id FROM leads";
    if (!empty($anchorConditions)) {
        $anchorSql .= " WHERE " . implode(" AND ", $anchorConditions);
    }
    $anchorSql .= " ORDER BY id DESC LIMIT 1";
    $anchorRes = $conn->query($anchorSql);

    $jumpConditions = $andConditions;
    if ($anchorRes && ($anchorRow = $anchorRes->fetch_assoc())) {
        $anchorId = (int)($anchorRow['id'] ?? 0);
        $jumpConditions[] = "(id > $anchorId)";
    }
    $jumpCountSql = "SELECT COUNT(*) AS c FROM leads";
    if (!empty($jumpConditions)) {
        $jumpCountSql .= " WHERE " . implode(" AND ", $jumpConditions);
    }
    $jumpCountRes = $conn->query($jumpCountSql);
    $jumpAnchorOffset = 0;
    if ($jumpCountRes && ($jumpCountRow = $jumpCountRes->fetch_assoc())) {
        $jumpAnchorOffset = max(0, (int)($jumpCountRow['c'] ?? 0));
    }
    $offset = max(0, $jumpAnchorOffset - (int)floor($limit / 2));
    header('X-Lead-Start-Offset: ' . $offset);
}

if (isset($_GET['count_summary'])) {
    $summaryConditions = $andConditions;
    if ($dateClauseWrapped !== '') {
        $summaryConditions = array_values(array_filter($summaryConditions, function($cond) use ($dateClauseWrapped) {
            return $cond !== $dateClauseWrapped;
        }));
    }

    $tz = new DateTimeZone('Asia/Kolkata');
    $now = new DateTime('now', $tz);
    $thisMonthStart = new DateTime($now->format('Y-m-01 00:00:00'), $tz);

    $makeCountSql = function($startDt, $endDt) use ($conn, $summaryConditions) {
        $conds = $summaryConditions;
        $startStr = $conn->real_escape_string($startDt->format('Y-m-d H:i:s'));
        $endStr = $conn->real_escape_string($endDt->format('Y-m-d H:i:s'));
        $conds[] = "(created_at >= '$startStr' AND created_at < '$endStr')";
        $sql = "SELECT COUNT(*) AS c FROM leads";
        if (!empty($conds)) {
            $sql .= " WHERE " . implode(" AND ", $conds);
        }
        return $sql;
    };

    $monthCounts = [];
    for ($i = 0; $i < 6; $i++) {
        $startDt = (clone $thisMonthStart)->modify("-{$i} month");
        $endDt = (clone $startDt)->modify('+1 month');
        $count = 0;
        $resMonth = $conn->query($makeCountSql($startDt, $endDt));
        if ($resMonth && ($row = $resMonth->fetch_assoc())) {
            $count = (int)($row['c'] ?? 0);
        }
        $monthCounts[] = [
            'offset' => $i,
            'label' => $startDt->format('M Y'),
            'month_short' => $startDt->format('M'),
            'count' => $count
        ];
    }

    echo json_encode([
        'this_month_count' => isset($monthCounts[0]['count']) ? (int)$monthCounts[0]['count'] : 0,
        'last_month_count' => isset($monthCounts[1]['count']) ? (int)$monthCounts[1]['count'] : 0,
        'month_counts' => $monthCounts
    ]);
    $conn->close();
    exit;
}

if (!empty($andConditions)) {
    $sql .= " WHERE " . implode(" AND ", $andConditions);
}

// ✅ Correct ordering for 'mids' view (History/Favorites)
if (!empty($midsRaw) && !empty($midsList)) {
    // Escape all mids for the FIELD function
    $midFieldItems = array_map(function($m) use ($conn) {
        return "'" . $conn->real_escape_string($m) . "'";
    }, $midsList);
    
    // Use FIELD on both id and MID to ensure we match whichever was passed
    // We prioritize the order in midsList
    $fieldStr = implode(',', $midFieldItems);
    $sql .= " ORDER BY FIELD(id, $fieldStr) ASC, FIELD(MID, $fieldStr) ASC, id DESC";
} else {
    $sql .= " ORDER BY id DESC";
}

$sql .= " LIMIT $offset, $limit";

$result = $conn->query($sql);
$data = [];

if ($result) {
    $statsCache = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($row['display_id'])) {
            $row['display_id'] = $row['id'];
        }

        // Add stats (cached per date for performance)
        if (!empty($row['created_at'])) {
            $dt = substr($row['created_at'], 0, 10);
            $dtSafe = $conn->real_escape_string($dt);
            $dayStart = $conn->real_escape_string($dt . ' 00:00:00');
            $statsExtra = (!$isAdmin && $userAssignCond !== '') ? " AND ($userAssignCond OR $openCond)" : "";
            if (!isset($statsCache[$dt])) {
                $totalRes = $conn->query("SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)$statsExtra");
                $statsCache[$dt] = ($totalRes && $r = $totalRes->fetch_assoc()) ? $r['c'] : 0;
            }
            $row['day_total'] = $statsCache[$dt];
            
            // Rank must be calculated per ID
            $rid = (int)$row['id'];
            $createdAtSafe = $conn->real_escape_string($row['created_at']);
            $rankRes = $conn->query("SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)$statsExtra AND (created_at > '$createdAtSafe' OR (created_at = '$createdAtSafe' AND id >= $rid))");
            $row['day_rank'] = ($rankRes && $r = $rankRes->fetch_assoc()) ? $r['c'] : 0;
        } else {
            $row['day_total'] = 0;
            $row['day_rank'] = 0;
        }

        $data[] = $row;
    }
}

echo json_encode($data);
$conn->close();
?>
