<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login.']);
    exit;
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserRoll = $_SESSION['roll'] ?? null;
$leave_date = $_POST['leave_date'] ?? '';

if (!$leave_date) {
    echo json_encode(['success' => false, 'message' => 'Date required.']);
    exit;
}

// Fetch leave to delete and related user_id and leave_date
$stmt = $mysqli->prepare("SELECT user_id, leave_date FROM leaves WHERE leave_date = ? LIMIT 1");
$stmt->bind_param('s', $leave_date);
$stmt->execute();
$stmt->bind_result($leaveUserId, $leaveDateFromDb);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'No leave found for the given date.']);
    exit;
}
$stmt->close();

// Check delete permission:

// Admin can delete any leave
if ($loggedInUserRoll === 'admin') {
    $allowedToDelete = true;
} else {
    // Non-admin can delete only their own leave
    // and only if leave_date is today or future (not for past leave dates)
    if ($loggedInUserId === $leaveUserId) {
        $today = new DateTime(date('Y-m-d'));
        $leaveDateObj = new DateTime($leaveDateFromDb);
        if ($leaveDateObj >= $today) {
            $allowedToDelete = true;
        } else {
            $allowedToDelete = false;
        }
    } else {
        $allowedToDelete = false;
    }
}

if (!$allowedToDelete) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this leave.']);
    exit;
}

// Proceed with deletion
$stmt = $mysqli->prepare("DELETE FROM leaves WHERE leave_date = ? AND user_id = ?");
$stmt->bind_param('si', $leave_date, $leaveUserId);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Leave deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete leave.']);
}
