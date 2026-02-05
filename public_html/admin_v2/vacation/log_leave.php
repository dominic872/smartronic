<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'config.php';
header('Content-Type: application/json');

// Check login
if (!isset($_COOKIE['auth_user'])) {
    echo json_encode(['success' => false, 'message' => 'Please login.']);
    exit;
}

// Now $username holds the username, not user_id
$username = $_COOKIE['auth_user'] ?? null;
$loggedInUserRole = $_COOKIE['auth_role'] ?? null;

$leave_date = $_POST['leave_date'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$leave_date) {
    echo json_encode(['success' => false, 'message' => 'Date required.']);
    exit;
}

if (!strtotime($leave_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}


// Admin can submit leave for others: POST param 'username' (string)
if ($loggedInUserRole === 'admin' && isset($_POST['username']) && is_string($_POST['username']) && $_POST['username'] !== '') {
    $target_username = $_POST['username'];
    $bypassRules = true;
} else {
    $target_username = $username;
    $bypassRules = false;
}

// Fetch role of user with this username (critical for rules)
$stmt = $mysqli->prepare("SELECT roll FROM users WHERE username = ?");
$stmt->bind_param('s', $target_username);
$stmt->execute();
$stmt->bind_result($user_role);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User not found (username: ' . htmlspecialchars($target_username) . ')']);
    exit;
}
$stmt->close();


// Calculate full day difference between today and leave date
$today = new DateTime(date('Y-m-d'));
$leaveDay = new DateTime($leave_date);
$interval = $today->diff($leaveDay);
$daysDiff = (int)$interval->format('%r%a');

if (!$bypassRules) {
    if (($user_role === 'tech' || $user_role === 'associate') && abs($daysDiff) < 3) {
        echo json_encode([
            'success' => false,
            'message' => 'You can only log leave for dates at least 3 full days away from today.'
        ]);
        exit;
    }
}


// Calculate timestamp for leave date
$leave_timestamp = strtotime($leave_date);
$month_start = date('Y-m-01', $leave_timestamp);
$month_end = date('Y-m-t', $leave_timestamp);


// Leave rules apply only if NOT bypassed
if (!$bypassRules) {
    // 1. Max 5 leaves per month for tech/associate
    if ($user_role === 'tech' || $user_role === 'associate') {
        $stmt = $mysqli->prepare("
            SELECT COUNT(*) 
            FROM leaves 
            JOIN users ON users.id = leaves.user_id
            WHERE users.username = ? 
            AND leave_date BETWEEN ? AND ?
        ");
        $stmt->bind_param('sss', $target_username, $month_start, $month_end);
        $stmt->execute();
        $stmt->bind_result($leave_count);
        $stmt->fetch();
        $stmt->close();
        if ($leave_count >= 5) {
            echo json_encode(['success' => false, 'message' => 'Maximum 5 leaves allowed in a month.']);
            exit;
        }
    }

    // 2. Max 2 leaves on Sundays in same month (per user)
    $day_of_week = (int)date('w', $leave_timestamp);
    if (($user_role === 'tech' || $user_role === 'associate') && $day_of_week === 0) {
        $stmt = $mysqli->prepare("
            SELECT COUNT(*) 
            FROM leaves 
            JOIN users ON users.id = leaves.user_id
            WHERE users.username = ? 
            AND leave_date BETWEEN ? AND ?
            AND DAYOFWEEK(leave_date) = 1
        ");
        $stmt->bind_param('sss', $target_username, $month_start, $month_end);
        $stmt->execute();
        $stmt->bind_result($sunday_leave_count);
        $stmt->fetch();
        $stmt->close();
        if ($sunday_leave_count >= 2) {
            echo json_encode(['success' => false, 'message' => 'Maximum 2 leaves allowed on Sundays in a month.']);
            exit;
        }
    }

    // 3. Max number of leave takers per day by role
    if ($user_role === 'tech' || $user_role === 'associate') {
        $stmt = $mysqli->prepare("
            SELECT users.roll, COUNT(leaves.id) AS cnt 
            FROM leaves 
            JOIN users ON users.id = leaves.user_id 
            WHERE leaves.leave_date = ? 
            AND (users.roll = 'tech' OR users.roll = 'associate') 
            GROUP BY users.roll
        ");
        $stmt->bind_param('s', $leave_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $tech_count = 0;
        $associate_count = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['roll'] === 'tech') {
                $tech_count = (int)$row['cnt'];
            } elseif ($row['roll'] === 'associate') {
                $associate_count = (int)$row['cnt'];
            }
        }
        $stmt->close();

        if ($user_role === 'tech' && $tech_count >= 1) {
            echo json_encode(['success' => false, 'message' => 'Only one tech can take leave per day.']);
            exit;
        }
        if ($user_role === 'associate' && $associate_count >= 2) {
            echo json_encode(['success' => false, 'message' => 'Maximum two associates can take leave per day.']);
            exit;
        }
    }
}

// Prevent duplicate leave for same user on same day
$stmt = $mysqli->prepare("
    SELECT leaves.id
    FROM leaves 
    JOIN users ON users.id = leaves.user_id
    WHERE users.username = ? AND leave_date = ?
");
$stmt->bind_param('ss', $target_username, $leave_date);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already marked leave for this day.']);
    exit;
}
$stmt->close();


// Insert leave (need user_id for insertion, so first get user id by username)
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $target_username);
$stmt->execute();
$stmt->bind_result($user_id_for_insert);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User not found (for insert).']);
    exit;
}
$stmt->close();

$stmt = $mysqli->prepare("INSERT INTO leaves (user_id, leave_date, reason) VALUES (?, ?, ?)");
$stmt->bind_param('iss', $user_id_for_insert, $leave_date, $reason);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Leave logged!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not log leave.']);
}
?>
