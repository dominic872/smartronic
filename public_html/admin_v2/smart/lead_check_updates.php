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

$origAssignColRes = $conn->query("SHOW COLUMNS FROM leads LIKE 'originally_assigned'");
if ($origAssignColRes && $origAssignColRes->num_rows === 0) {
    $conn->query("ALTER TABLE leads ADD COLUMN originally_assigned VARCHAR(255) NULL");
}

date_default_timezone_set('Asia/Kolkata');
$role = $_COOKIE['auth_role'] ?? '';
$isAdmin = in_array(strtolower(trim((string)$role)), ['admin', 'manager'], true);
$isMarket = ($role === 'market');
$authName = $_COOKIE['auth_name'] ?? '';
$authUser = $_COOKIE['auth_user'] ?? '';
if (!$isAdmin && !$isMarket) {
    http_response_code(403);
    echo json_encode(['error' => 'No access']);
    $conn->close();
    exit;
}
$canonicalPresenceCode = static function(string $codeSource, string $displayName = ''): string {
    $candidates = [$codeSource, $displayName];
    foreach ($candidates as $candidate) {
        $letters = strtolower(preg_replace('/[^a-zA-Z]/', '', (string)$candidate));
        if ($letters === '') continue;
        if (strpos($letters, 'amreen') === 0 || strpos($letters, 'amr') === 0) return 'amr';
        if (strpos($letters, 'varsha') === 0 || strpos($letters, 'var') === 0) return 'var';
        if (strpos($letters, 'zoya') === 0 || strpos($letters, 'zoy') === 0) return 'zoy';
        if (strpos($letters, 'surya') === 0 || strpos($letters, 'sur') === 0) return 'sur';
        return substr($letters, 0, 3);
    }
    return '';
};

$codeSource = $authName !== '' ? $authName : $authUser;
$userCode = $canonicalPresenceCode($codeSource, $authName);
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
$presenceStateRaw = isset($_GET['presence_state']) ? trim((string)$_GET['presence_state']) : '';
$presenceState = $presenceStateRaw !== '' ? strtolower($presenceStateRaw) : '';
$presenceState = in_array($presenceState, ['active', 'inactive'], true) ? $presenceState : '';
$presenceLastActive = isset($_GET['presence_last_active']) ? (int)$_GET['presence_last_active'] : 0;

$conn->query("CREATE TABLE IF NOT EXISTS user_presence (user_code VARCHAR(20) NOT NULL PRIMARY KEY, display_name VARCHAR(80) NULL, last_seen DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($userCode !== '' && $presenceState !== 'inactive') {
    $userCodeSafe = $conn->real_escape_string($userCode);
    $displayName = $authName !== '' ? $authName : $authUser;
    $displayName = trim((string)$displayName);
    $displayNameSafe = $conn->real_escape_string($displayName);
    $lastSeenExpr = $presenceLastActive > 0 ? "FROM_UNIXTIME(" . intval($presenceLastActive) . ")" : "NOW()";
    $conn->query("INSERT INTO user_presence (user_code, display_name, last_seen) VALUES ('$userCodeSafe', '$displayNameSafe', $lastSeenExpr) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), last_seen = IF($lastSeenExpr > last_seen, $lastSeenExpr, last_seen)");
}

// Get the last seen ID from the client
$lastId = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
$mineRaw = isset($_GET['mine']) ? trim($_GET['mine']) : '';
$mineNorm = strtolower(preg_replace('/[^a-z]/', '', $mineRaw));
$mineNorm = substr($mineNorm, 0, 10);
$mine = $mineNorm !== '' ? $conn->real_escape_string($mineNorm) : '';
$followRaw = isset($_GET['follow']) ? trim($_GET['follow']) : '';
$followNorm = strtolower($followRaw);
$follow = in_array($followNorm, ['today', 'tomorrow', 'yesterday'], true) ? $conn->real_escape_string($followNorm) : '';

if ($lastId <= 0 && $presenceState === '' && $presenceLastActive <= 0) {
    $safe_error('Invalid last_id');
}
if ($lastId <= 0) $lastId = 0;

$getUsersOut = function() use ($conn, $canonicalPresenceCode) {
    $usersOut = [];
    $uRes = $conn->query("SELECT user_code, display_name, last_seen, TIMESTAMPDIFF(SECOND, last_seen, NOW()) as age_seconds FROM user_presence ORDER BY user_code ASC");
    if ($uRes) {
        while ($u = $uRes->fetch_assoc()) {
            $codeRaw = isset($u['user_code']) ? (string)$u['user_code'] : '';
            $displayNameRaw = isset($u['display_name']) ? (string)$u['display_name'] : '';
            $codeNorm = $canonicalPresenceCode($codeRaw, $displayNameRaw);
            if (!in_array($codeNorm, ['amr', 'var', 'zoy', 'sur'], true)) continue;
            $age = isset($u['age_seconds']) ? (int)$u['age_seconds'] : 999999;
            $usersOut[] = [
                'code' => $codeNorm,
                'name' => $displayNameRaw,
                'active' => ($age <= 180)
            ];
        }
    }
    return $usersOut;
};

if ($lastId === 0) {
    echo json_encode(['success' => true, 'new_leads' => [], 'reopened_leads' => [], 'users' => $getUsersOut(), 'me' => $userCode]);
    $conn->close();
    exit;
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

$reopenedLeads = [];
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

    $reSql = "SELECT *, IFNULL(MID, id) as display_id FROM leads WHERE id IN ($idList)";
    if (!$isAdmin) {
        $reSql .= " AND ($userAssignCond OR $openCond)";
    }
    if (!empty($follow)) {
        if ($follow === 'today') {
            $reSql .= " AND ((Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = CURDATE()) OR (DATE(created_at) = CURDATE() AND (Follow_up IS NULL OR Follow_up = '' OR Follow_up = '-')))";
        } else if ($follow === 'tomorrow') {
            $reSql .= " AND (Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
        } else if ($follow === 'yesterday') {
            $reSql .= " AND (Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_SUB(CURDATE(), INTERVAL 1 DAY))";
        }
    }
    $reSql .= " ORDER BY id DESC";
    $reRes = $conn->query($reSql);
    if ($reRes) {
        while ($row = $reRes->fetch_assoc()) {
            $reopenedLeads[] = $row;
        }
    }
}

// Check for any leads with ID greater than lastId
// Sort by ID ASC so we get them in chronological order of creation
$sql = "SELECT *, IFNULL(MID, id) as display_id FROM leads WHERE id > $lastId";
if (!$isAdmin) {
    $sql .= " AND ($userAssignCond OR $openCond)";
} else if (!empty($mine)) {
    $sql .= " AND (Assign LIKE '%$mine%' OR $openCond)";
}
if (!empty($follow)) {
    if ($follow === 'today') {
        $sql .= " AND ((Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = CURDATE()) OR (DATE(created_at) = CURDATE() AND (Follow_up IS NULL OR Follow_up = '' OR Follow_up = '-')))";
    } else if ($follow === 'tomorrow') {
        $sql .= " AND (Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
    } else if ($follow === 'yesterday') {
        $sql .= " AND (Follow_up IS NOT NULL AND Follow_up <> '' AND Follow_up <> '-' AND Follow_up <> 'NA' AND STR_TO_DATE(Follow_up, '%d %b %y') = DATE_SUB(CURDATE(), INTERVAL 1 DAY))";
    }
}
$sql .= " ORDER BY id ASC";

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
} else {
    $safe_error($conn->error);
}

echo json_encode(['success' => true, 'new_leads' => $data, 'reopened_leads' => $reopenedLeads, 'users' => $getUsersOut(), 'me' => $userCode]);
$conn->close();
?>
