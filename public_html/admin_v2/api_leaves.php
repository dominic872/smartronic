<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

// Auth checks
if (!isset($_COOKIE['auth_user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$authUser = trim($_COOKIE['auth_user']);
$role = strtolower(trim($_COOKIE['auth_role'] ?? ''));
$isAdmin = $role === 'admin';

// Get current acting user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'DB error fetching user: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $authUser);
$stmt->execute();
$stmt->bind_result($actorUserId);
$stmt->fetch();
$stmt->close();

if (!$actorUserId) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

function logAction($conn, $actor, $target, $actionType, $date, $details = '') {
    $stmt = $conn->prepare("INSERT INTO leave_logs (actor_user_id, target_user_id, action_type, leave_date, details) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iisss", $actor, $target, $actionType, $date, $details);
        $stmt->execute();
        $stmt->close();
    }
}

function getUserRoleById($conn, int $userId): string {
    $stmt = $conn->prepare("SELECT LOWER(TRIM(roll)) FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) return '';
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($userRole);
    $stmt->fetch();
    $stmt->close();
    return is_string($userRole) ? trim($userRole) : '';
}

function classifyLeaveType(string $requestedType, int $daysDiff, bool $isWorkedHoliday, bool $isWeeklyOff, bool $hasAccumulatedLeave): string {
    if ($isWorkedHoliday) return 'worked_holiday';
    if ($isWeeklyOff) return 'weekly_off';
    if (!$hasAccumulatedLeave) return 'unplanned';
    if ($daysDiff < 2) return 'unplanned';
    return 'planned';
}

function ensureLeaveTypeEnumSupportsWorkedHoliday($conn): void {
    $sql = "ALTER TABLE `leaves` MODIFY COLUMN `leave_type` ENUM('planned', 'unplanned', 'sick', 'weekly_off', 'extra', 'worked_holiday') NOT NULL DEFAULT 'planned'";
    @$conn->query($sql);
}

function ensureAdminLopOverrideColumn($conn): void {
    $result = $conn->query("SHOW COLUMNS FROM `leaves` LIKE 'admin_lop_override'");
    if ($result && $result->num_rows === 0) {
        @$conn->query("ALTER TABLE `leaves` ADD COLUMN `admin_lop_override` TINYINT(1) NULL DEFAULT NULL AFTER `leave_type`");
    }
}

function extractWeeklyOffSourceDate(string $reason): string {
    if (preg_match('/Weekly Off From (\d{4}-\d{2}-\d{2})/i', $reason, $m)) {
        return trim((string)$m[1]);
    }
    return '';
}

function getAvailableWeeklyOffSources($conn, int $userId, string $todayStr): array {
    $workedSources = [];
    $stmtWorked = $conn->prepare("SELECT leave_date FROM leaves WHERE user_id = ? AND leave_type = 'worked_holiday' ORDER BY leave_date ASC");
    if ($stmtWorked) {
        $stmtWorked->bind_param("i", $userId);
        $stmtWorked->execute();
        $resWorked = $stmtWorked->get_result();
        while ($row = $resWorked->fetch_assoc()) {
            $sourceDate = trim((string)($row['leave_date'] ?? ''));
            if ($sourceDate === '') continue;
            $expiryDate = date('Y-m-d', strtotime($sourceDate . ' +14 days'));
            if ($todayStr < $expiryDate) {
                $workedSources[$sourceDate] = [
                    'source_date' => $sourceDate,
                    'expiry_date' => $expiryDate
                ];
            }
        }
        $stmtWorked->close();
    }

    if (!$workedSources) return [];

    $usedSources = [];
    $stmtWeekly = $conn->prepare("SELECT reason FROM leaves WHERE user_id = ? AND leave_type = 'weekly_off'");
    if ($stmtWeekly) {
        $stmtWeekly->bind_param("i", $userId);
        $stmtWeekly->execute();
        $resWeekly = $stmtWeekly->get_result();
        while ($row = $resWeekly->fetch_assoc()) {
            $source = extractWeeklyOffSourceDate((string)($row['reason'] ?? ''));
            if ($source !== '') $usedSources[$source] = true;
        }
        $stmtWeekly->close();
    }

    return array_values(array_filter($workedSources, function ($item) use ($usedSources) {
        return empty($usedSources[$item['source_date']]);
    }));
}

function getMonthlyLeaveCreditWindow(DateTimeInterface $asOf): ?array {
    $tz = new DateTimeZone('Asia/Kolkata');
    $creditStart = new DateTimeImmutable('2026-05-01 00:00:00', $tz);
    $asOfDate = new DateTimeImmutable($asOf->format('Y-m-d') . ' 00:00:00', $tz);
    if ($asOfDate < $creditStart) return null;

    $year = (int)$asOfDate->format('Y');
    $periodStart = $year === 2026
        ? $creditStart
        : new DateTimeImmutable($year . '-01-01 00:00:00', $tz);
    $periodEnd = new DateTimeImmutable($year . '-12-31 23:59:59', $tz);
    $monthDiff = (((int)$asOfDate->format('Y') - (int)$periodStart->format('Y')) * 12)
        + ((int)$asOfDate->format('n') - (int)$periodStart->format('n'));

    return [
        'start' => $periodStart,
        'end' => $periodEnd,
        'credited' => max(0, $monthDiff + 1),
    ];
}

function getAccumulatedLeaveBalance($conn, int $userId, DateTime $today): array {
    $window = getMonthlyLeaveCreditWindow($today);
    $totalCredited = $window ? (int)$window['credited'] : 0;
    $usedLeaves = 0;
    if ($window) {
        $periodStartStr = $window['start']->format('Y-m-d');
        $todayStr = $today->format('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND leave_type IN ('planned', 'sick', 'extra') AND leave_date >= ? AND leave_date <= ?");
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $periodStartStr, $todayStr);
            $stmt->execute();
            $stmt->bind_result($usedLeaves);
            $stmt->fetch();
            $stmt->close();
        }
    }

    return [
        'allowed' => $totalCredited,
        'credited' => $totalCredited,
        'used' => $usedLeaves,
        'balance' => max(0, $totalCredited - $usedLeaves)
    ];
}

function getAccumulatedLeaveWindow(DateTime $today): ?array {
    return getMonthlyLeaveCreditWindow($today);
}

function isWithinTwoWorkingDaysForRole(string $dateStr, string $userRole, DateTime $today): bool {
    $leaveDate = new DateTime($dateStr . ' 00:00:00');
    $cursor = clone $today;
    $cursor->setTime(0, 0, 0);
    if ($leaveDate < $cursor) return false;

    $workingDays = 0;
    while ($cursor < $leaveDate && $workingDays < 2) {
        $cursor->modify('+1 day');
        if ($cursor > $leaveDate) break;
        $dow = (int)$cursor->format('w');
        $isSundayHoliday = ($userRole === 'market' && $dow === 0);
        if (!$isSundayHoliday) $workingDays++;
    }

    return $workingDays < 2;
}

function recalculateOrdinaryLeaveTypes($conn, int $userId): void {
    $today = new DateTime('today');
    $window = getAccumulatedLeaveWindow($today);
    if (!$window) return;

    $userRole = getUserRoleById($conn, $userId);
    $periodStartStr = $window['start']->format('Y-m-d');
    $periodEndStr = $window['end']->format('Y-m-d');

    $allowed = (int)$window['credited'];

    $rows = [];
    $stmt = $conn->prepare("
        SELECT id, leave_date, leave_type, admin_lop_override
        FROM leaves
        WHERE user_id = ?
          AND leave_date >= ?
          AND leave_date <= ?
          AND leave_type NOT IN ('weekly_off', 'worked_holiday')
        ORDER BY leave_date ASC, id ASC
    ");
    if (!$stmt) return;
    $stmt->bind_param("iss", $userId, $periodStartStr, $periodEndStr);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();

    if (!$rows) return;

    $balanceUsed = 0;
    $update = $conn->prepare("UPDATE leaves SET leave_type = ? WHERE id = ?");
    if (!$update) return;

    foreach ($rows as $row) {
        $leaveId = (int)($row['id'] ?? 0);
        $leaveDate = trim((string)($row['leave_date'] ?? ''));
        if ($leaveId <= 0 || $leaveDate === '') continue;

        $manualOverride = $row['admin_lop_override'];
        if ($manualOverride !== null) {
            $targetType = ((int)$manualOverride === 1) ? 'unplanned' : 'planned';
            if ($targetType === 'planned') $balanceUsed++;
        } elseif (!isWithinTwoWorkingDaysForRole($leaveDate, $userRole, $today) && $balanceUsed < $allowed) {
            $targetType = 'planned';
            $balanceUsed++;
        } else {
            $targetType = 'unplanned';
        }

        if (($row['leave_type'] ?? '') !== $targetType) {
            $update->bind_param("si", $targetType, $leaveId);
            $update->execute();
        }
    }
    $update->close();
}

ensureLeaveTypeEnumSupportsWorkedHoliday($conn);
ensureAdminLopOverrideColumn($conn);

$action = $_GET['action'] ?? '';

if ($action === 'get_users') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $users = [];
    $result = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($action === 'get_logs') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $logs = [];
    $query = "SELECT l.id, u.username as actor_name, t.username as target_name, l.action_type, l.leave_date, l.details, l.created_at 
              FROM leave_logs l 
              LEFT JOIN users u ON l.actor_user_id = u.id 
              LEFT JOIN users t ON l.target_user_id = t.id 
              ORDER BY l.created_at DESC LIMIT 50";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

if ($action === 'get') {
    $month = $_GET['month'] ?? date('n');
    $year = $_GET['year'] ?? date('Y');
    $targetUserId = isset($_GET['target_user_id']) ? (int)$_GET['target_user_id'] : $actorUserId;
    
    // Only Admin can view other specific users' detailed leaves directly
    if (!$isAdmin && $targetUserId !== $actorUserId) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    $targetUserRole = getUserRoleById($conn, $targetUserId);
    recalculateOrdinaryLeaveTypes($conn, $targetUserId);
    
    // Fetch all leaves for TARGET user
    $stmt = $conn->prepare("SELECT id, leave_date, reason, leave_type, admin_lop_override FROM leaves WHERE user_id = ?");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $leaves = [];
    while ($row = $result->fetch_assoc()) {
        $leaves[] = $row;
    }
    $stmt->close();
    
    // GLOBAL Leaves Logic
    $globalLeaves = [];
    $startDateStr = sprintf("%04d-%02d-01", $year, $month);
    $endDateStr = sprintf("%04d-%02d-%02d", $year, $month, date('t', strtotime($startDateStr)));
    
    $stmtG = $conn->prepare("SELECT l.leave_date, u.username FROM leaves l INNER JOIN users u ON l.user_id = u.id WHERE l.leave_date >= ? AND l.leave_date <= ? AND l.user_id != ?");
    $stmtG->bind_param("ssi", $startDateStr, $endDateStr, $targetUserId);
    $stmtG->execute();
    $resG = $stmtG->get_result();
    while ($row = $resG->fetch_assoc()) {
        $d = $row['leave_date'];
        if (!isset($globalLeaves[$d])) {
            $globalLeaves[$d] = [];
        }
        $globalLeaves[$d][] = $row['username'];
    }
    $stmtG->close();
    
    $now = new DateTime('today');
    $leaveBalanceMeta = getAccumulatedLeaveBalance($conn, $targetUserId, $now);
    $extraBalance = (int)$leaveBalanceMeta['balance'];

    // Count Loss of Pay leaves for the selected user only.
    $lopLeaves = 0;
    $stmtLop = $conn->prepare("SELECT COUNT(*) FROM leaves WHERE user_id = ? AND leave_type = 'unplanned'");
    $stmtLop->bind_param("i", $targetUserId);
    $stmtLop->execute();
    $stmtLop->bind_result($lopLeaves);
    $stmtLop->fetch();
    $stmtLop->close();

    $todayStr = date('Y-m-d');
    $availableWeeklyOffSources = getAvailableWeeklyOffSources($conn, $targetUserId, $todayStr);
    $unredeemedSundays = array_map(function ($row) {
        return $row['source_date'];
    }, $availableWeeklyOffSources);
    
    echo json_encode([
        'success' => true, 
        'leaves' => $leaves,
        'globalLeaves' => $globalLeaves,
        'extraBalance' => $extraBalance,
        'monthlyLeavesCredited' => (int)$leaveBalanceMeta['credited'],
        'monthlyLeavesUsed' => (int)$leaveBalanceMeta['used'],
        'lopLeaves' => $lopLeaves,
        'weeklyOffSources' => $availableWeeklyOffSources,
        'unredeemedSundays' => $unredeemedSundays,
        'isAdmin' => $isAdmin,
        'userRole' => $targetUserRole
    ]);
    exit;
}

if ($action === 'set_lop_status') {
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Only admin can change LOP status']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $date = trim((string)($data['date'] ?? ''));
    $requestedTargetUserId = (int)($data['target_user_id'] ?? 0);
    $targetUserId = $requestedTargetUserId > 0 ? $requestedTargetUserId : (int)$actorUserId;
    $makeLop = isset($data['is_lop']) ? (int)(bool)$data['is_lop'] : -1;
    if ($targetUserId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($makeLop, [0, 1], true)) {
        echo json_encode(['success' => false, 'error' => 'Invalid LOP update']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, leave_type FROM leaves WHERE user_id = ? AND leave_date = ? LIMIT 1");
    $stmt->bind_param("is", $targetUserId, $date);
    $stmt->execute();
    $stmt->bind_result($leaveId, $existingType);
    $stmt->fetch();
    $stmt->close();

    if (!$leaveId) {
        echo json_encode(['success' => false, 'error' => 'Leave not found']);
        exit;
    }
    if (in_array($existingType, ['weekly_off', 'worked_holiday'], true)) {
        echo json_encode(['success' => false, 'error' => 'Weekly off and working-day entries cannot be converted to LOP']);
        exit;
    }

    $newType = $makeLop === 1 ? 'unplanned' : 'planned';
    $stmt = $conn->prepare("UPDATE leaves SET leave_type = ?, admin_lop_override = ? WHERE id = ?");
    $stmt->bind_param("sii", $newType, $makeLop, $leaveId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        echo json_encode(['success' => false, 'error' => 'Unable to update LOP status']);
        exit;
    }

    logAction(
        $conn,
        $actorUserId,
        $targetUserId,
        'Updated',
        $date,
        $makeLop === 1 ? 'Admin marked leave as LOP' : 'Admin marked leave as Non-LOP'
    );
    echo json_encode(['success' => true, 'leave_type' => $newType, 'is_lop' => $makeLop]);
    exit;
}

if ($action === 'toggle') {
    $data = json_decode(file_get_contents('php://input'), true);
    $date = $data['date'] ?? '';
    $targetUserId = isset($data['target_user_id']) ? (int)$data['target_user_id'] : $actorUserId;
    
    if (!$isAdmin && $targetUserId !== $actorUserId) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to modify other users']);
        exit;
    }
    $targetUserRole = getUserRoleById($conn, $targetUserId);
    
    if (!$date) {
        echo json_encode(['success' => false, 'error' => 'Date missing']);
        exit;
    }
    
    $leaveDateObj = new DateTime($date);
    $todayObj = new DateTime('today');
    $interval = $todayObj->diff($leaveDateObj);
    $daysDiff = (int)$interval->format('%R%a');
    
    $dayOfWeek = (int)$leaveDateObj->format('w');
    if ($dayOfWeek === 0) { // SUNDAY
        if ($targetUserRole === 'market') {
            echo json_encode(['success' => false, 'error' => 'Market users have Sunday as holiday. Leave cannot be added on Sunday.']);
            exit;
        }
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'Only admin can mark Sunday as working day for this user.']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("SELECT id, leave_type FROM leaves WHERE user_id = ? AND leave_date = ?");
    $stmt->bind_param("is", $targetUserId, $date);
    $stmt->execute();
    $stmt->bind_result($existingId, $existingType);
    $stmt->fetch();
    $stmt->close();
    
    if ($existingId) {
        if (!$isAdmin && $daysDiff < 0) {
            echo json_encode(['success' => false, 'error' => 'Past leaves can only be removed by admin.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM leaves WHERE id = ?");
        $stmt->bind_param("i", $existingId);
        $stmt->execute();
        $stmt->close();
        
        logAction($conn, $actorUserId, $targetUserId, 'Removed', $date, "Removed type: $existingType");
        recalculateOrdinaryLeaveTypes($conn, $targetUserId);
        
        echo json_encode(['success' => true, 'state' => 'removed']);
        exit;
    }
    
    // Adding new leave
    $type = trim((string)($data['type'] ?? 'planned'));
    $reason = trim((string)($data['reason'] ?? ''));
    $weeklyOffSource = trim((string)($data['weekly_off_source'] ?? ''));
    $todayStr = date('Y-m-d');
    $isSundayWorked = ($dayOfWeek === 0 && $isAdmin);
    $isWeeklyOff = ($type === 'weekly_off');

    if ($isWeeklyOff && $weeklyOffSource !== '') {
        $reason = $reason !== '' ? ($reason . ' | Weekly Off From ' . $weeklyOffSource) : ('Weekly Off From ' . $weeklyOffSource);
    }

    $leaveBalanceMeta = getAccumulatedLeaveBalance($conn, $targetUserId, new DateTime('today'));
    $type = classifyLeaveType($type, $daysDiff, $isSundayWorked, $isWeeklyOff, ((int)$leaveBalanceMeta['balance']) > 0);

    if ($type === 'weekly_off' && $weeklyOffSource === '') {
        echo json_encode(['success' => false, 'error' => 'Weekly off source Sunday is required.']);
        exit;
    }
    if ($type === 'weekly_off') {
        $availableSources = getAvailableWeeklyOffSources($conn, $targetUserId, $todayStr);
        $allowedSources = array_column($availableSources, 'source_date');
        if (!in_array($weeklyOffSource, $allowedSources, true)) {
            echo json_encode(['success' => false, 'error' => 'This weekly off has lapsed or was already used.']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO leaves (user_id, leave_date, reason, leave_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $targetUserId, $date, $reason, $type);
    if (!$stmt->execute()) {
        $dbError = $stmt->error ?: $conn->error;
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'Unable to save leave: ' . $dbError]);
        exit;
    }
    $newId = $stmt->insert_id;
    $stmt->close();
    
    logAction($conn, $actorUserId, $targetUserId, 'Added', $date, "Type: $type. Reason: $reason");
    recalculateOrdinaryLeaveTypes($conn, $targetUserId);
    
    $finalType = $type;
    $refreshStmt = $conn->prepare("SELECT leave_type FROM leaves WHERE id = ? LIMIT 1");
    if ($refreshStmt) {
        $refreshStmt->bind_param("i", $newId);
        $refreshStmt->execute();
        $refreshStmt->bind_result($refreshedType);
        if ($refreshStmt->fetch() && is_string($refreshedType) && $refreshedType !== '') {
            $finalType = $refreshedType;
        }
        $refreshStmt->close();
    }
    
    echo json_encode(['success' => true, 'state' => 'added', 'id' => $newId, 'final_type' => $finalType]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
