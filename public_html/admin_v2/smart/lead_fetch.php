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

$origAssignColRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'originally_assigned'");
if ($origAssignColRes && $origAssignColRes->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN originally_assigned VARCHAR(255) NULL");
}

$hasUpdatedAt = false;
$colRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'updated_at'");
if ($colRes && $colRes->num_rows > 0) $hasUpdatedAt = true;
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
$hour = (int)date('G');
$inQuietWindow = ($hour >= 20 || $hour < 9);
if (!$inQuietWindow) {
    if ($statusColSql !== null) {
        $statusEmptyCond = "(COALESCE(TRIM($statusColSql), '') = '' OR TRIM($statusColSql) = '-' OR LOWER(TRIM($statusColSql)) = 'na')";
        $staleRes = $conn->query("SELECT id FROM leads WHERE $assignNotOpenCond AND $statusEmptyCond AND created_at IS NOT NULL AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) >= 90 LIMIT 25");
    } else {
        $staleRes = $conn->query("SELECT id FROM leads WHERE $assignNotOpenCond AND $tsExpr IS NOT NULL AND TIMESTAMPDIFF(MINUTE, $tsExpr, NOW()) >= 90 LIMIT 25");
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

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 100;
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

// ✅ Fetch a single lead by ID
if (!empty($leadId)) {
    if (substr($leadId, 0, 2) === 'L-') {
        $id = substr($leadId, 2);
    } else {
        $id = $leadId;
    }

    $sql = "SELECT *, IFNULL(MID, id) as display_id FROM leads WHERE id = '$id' LIMIT 1";
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
            
            $countSql = "SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)";
            $cRes = $conn->query($countSql);
            $row['day_total'] = ($cRes && $r = $cRes->fetch_assoc()) ? $r['c'] : 1;

            $createdAtSafe = $conn->real_escape_string($row['created_at']);
            $rankSql = "SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY) AND (created_at > '$createdAtSafe' OR (created_at = '$createdAtSafe' AND id >= $rid))";
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
if (!empty($mine)) {
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
            if (!isset($statsCache[$dt])) {
                $totalRes = $conn->query("SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY)");
                $statsCache[$dt] = ($totalRes && $r = $totalRes->fetch_assoc()) ? $r['c'] : 0;
            }
            $row['day_total'] = $statsCache[$dt];
            
            // Rank must be calculated per ID
            $rid = (int)$row['id'];
            $createdAtSafe = $conn->real_escape_string($row['created_at']);
            $rankRes = $conn->query("SELECT COUNT(*) as c FROM leads WHERE created_at >= '$dayStart' AND created_at < DATE_ADD('$dtSafe', INTERVAL 1 DAY) AND (created_at > '$createdAtSafe' OR (created_at = '$createdAtSafe' AND id >= $rid))");
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
