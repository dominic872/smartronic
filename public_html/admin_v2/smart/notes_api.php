<?php
// --- Always return clean JSON, even on errors ---
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

function notesApiLogError($message, $context = []) {
  $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
  if (!empty($context)) {
    $line .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES);
  }
  @file_put_contents(__DIR__ . '/notes_api_error.log', $line . PHP_EOL, FILE_APPEND);
}

function notesApiErrorPayload($message, $error = '', $extra = []) {
  $payload = array_merge([
    'success' => false,
    'message' => $message
  ], $extra);
  if ($error !== '') {
    $payload['error'] = $error;
  }
  if (isset($payload['error_no']) && !isset($payload['error_number'])) {
    $payload['error_number'] = $payload['error_no'];
  }
  if (isset($payload['error_number']) && !isset($payload['error_no'])) {
    $payload['error_no'] = $payload['error_number'];
  }
  $parts = [$message];
  if (isset($payload['error_number'])) {
    $parts[] = 'Error No: ' . $payload['error_number'];
  }
  if (isset($payload['source_line'])) {
    $parts[] = 'Line: ' . $payload['source_line'];
  }
  if ($error !== '') {
    $parts[] = $error;
  }
  $payload['message'] = implode(' | ', $parts);
  $payload['debug'] = array_merge([
    'api_file' => 'notes_api.php',
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'date' => $_GET['date'] ?? '',
    'log_file' => 'notes_api_error.log'
  ], $payload['debug'] ?? []);
  return $payload;
}

set_exception_handler(function($e){
  notesApiLogError('Server exception', [
    'error_number' => $e->getCode(),
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ]);
  http_response_code(500);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode(notesApiErrorPayload('Server exception', $e->getMessage(), [
    'error_no' => $e->getCode(),
    'error_number' => $e->getCode(),
    'source_file' => $e->getFile(),
    'source_line' => $e->getLine()
  ]));
  exit;
});
set_error_handler(function($severity,$message,$file,$line){
  notesApiLogError('PHP error', [
    'severity' => $severity,
    'error_number' => $severity,
    'error' => $message,
    'file' => $file,
    'line' => $line
  ]);
  http_response_code(500);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode(notesApiErrorPayload('PHP error', "$message @ $file:$line", [
    'error_no' => $severity,
    'error_number' => $severity,
    'source_file' => $file,
    'source_line' => $line
  ]));
  return true;
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e) {
    notesApiLogError('Fatal error', [
      'error_number' => $e['type'] ?? 0,
      'error' => $e['message'] ?? '',
      'file' => $e['file'] ?? '',
      'line' => $e['line'] ?? ''
    ]);
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(notesApiErrorPayload('Fatal error', $e['message'] ?? '', [
      'error_no' => $e['type'] ?? 0,
      'error_number' => $e['type'] ?? 0,
      'source_file' => $e['file'] ?? '',
      'source_line' => $e['line'] ?? ''
    ]));
  }
});

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// --- Load config.php robustly ---
$conn = null;
$paths = [
  __DIR__ . '/config.php',
  __DIR__ . '/../config.php',
  __DIR__ . '/admin/config.php',
  dirname(__DIR__) . '/admin/config.php'
];
foreach ($paths as $p) {
  if (file_exists($p)) { require_once $p; break; }
}
if (!isset($conn) || $conn->connect_error) {
  $dbError = isset($conn) ? $conn->connect_error : 'config.php not found';
  $dbErrorNo = isset($conn) ? (int)$conn->connect_errno : 0;
  notesApiLogError('DB connection not available', ['error_no' => $dbErrorNo, 'error_number' => $dbErrorNo, 'error' => $dbError]);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode(notesApiErrorPayload('DB connection not available from notes_api.php', $dbError, [
    'error_no' => $dbErrorNo,
    'error_number' => $dbErrorNo,
    'source_file' => __FILE__,
    'source_line' => __LINE__
  ]));
  exit;
}

function respond($arr, $code = 200){
  http_response_code($code);
  if (ob_get_length()) { ob_clean(); }
  if ($code >= 500) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $trace[1] ?? [];
    global $conn;
    $mysqlErrno = (isset($conn) && $conn instanceof mysqli) ? (int)$conn->errno : 0;
    $errorNumber = $arr['error_number'] ?? $arr['error_no'] ?? $mysqlErrno;
    notesApiLogError($arr['message'] ?? 'notes_api.php error', [
      'error_no' => $errorNumber,
      'error_number' => $errorNumber,
      'error' => $arr['error'] ?? '',
      'file' => $arr['source_file'] ?? ($caller['file'] ?? __FILE__),
      'line' => $arr['source_line'] ?? ($caller['line'] ?? null),
      'date' => $_GET['date'] ?? ''
    ]);
    $arr = notesApiErrorPayload($arr['message'] ?? 'notes_api.php error', $arr['error'] ?? '', array_merge([
      'error_no' => $errorNumber,
      'error_number' => $errorNumber,
      'source_file' => $arr['source_file'] ?? ($caller['file'] ?? __FILE__),
      'source_line' => $arr['source_line'] ?? ($caller['line'] ?? null)
    ], $arr));
  }
  echo json_encode($arr);
  exit;
}

function allowType($t) {
  return in_array($t, ['General','Issue','Inspection'], true) ? $t : 'General';
}

function notesColumnExists($conn, $columnName) {
  try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param("s", $columnName);
    if (!$stmt->execute()) return false;
    $row = $stmt->get_result()->fetch_assoc();
    return ((int)($row['c'] ?? 0)) > 0;
  } catch (Throwable $e) {
    notesApiLogError('Column check failed', ['column' => $columnName, 'error' => $e->getMessage()]);
    return false;
  }
}

function ensureNotesColumn($conn, $columnName, $alterSql) {
  if (notesColumnExists($conn, $columnName)) return true;
  try {
    if (!$conn->query($alterSql)) return false;
  } catch (Throwable $e) {
    notesApiLogError('Column ensure failed', ['column' => $columnName, 'error' => $e->getMessage()]);
    return false;
  }
  return notesColumnExists($conn, $columnName);
}

function ensureDoneColumn($conn) {
  return ensureNotesColumn($conn, 'is_done', "ALTER TABLE notes ADD COLUMN is_done TINYINT(1) NOT NULL DEFAULT 0");
}

function ensureDoneMetaColumns($conn) {
  $columns = [
    'done_by' => "ALTER TABLE notes ADD COLUMN done_by VARCHAR(255) NULL DEFAULT NULL",
    'done_at' => "ALTER TABLE notes ADD COLUMN done_at DATETIME NULL DEFAULT NULL"
  ];
  $available = [];
  foreach ($columns as $columnName => $alterSql) {
    $available[$columnName] = ensureNotesColumn($conn, $columnName, $alterSql);
  }
  return $available;
}

function ensureAssignmentColumns($conn) {
  $columns = [
    'assigned_to' => "ALTER TABLE notes ADD COLUMN assigned_to VARCHAR(64) NULL DEFAULT NULL",
    'assigned_history' => "ALTER TABLE notes ADD COLUMN assigned_history TEXT NULL DEFAULT NULL"
  ];
  $available = [];
  foreach ($columns as $columnName => $alterSql) {
    $available[$columnName] = ensureNotesColumn($conn, $columnName, $alterSql);
  }
  return $available;
}

function ensureReopenColumns($conn) {
  $columns = [
    'reopen_reason' => "ALTER TABLE notes ADD COLUMN reopen_reason TEXT NULL DEFAULT NULL",
    'reopened_by' => "ALTER TABLE notes ADD COLUMN reopened_by VARCHAR(255) NULL DEFAULT NULL",
    'reopened_at' => "ALTER TABLE notes ADD COLUMN reopened_at DATETIME NULL DEFAULT NULL",
    'reopen_history' => "ALTER TABLE notes ADD COLUMN reopen_history LONGTEXT NULL DEFAULT NULL"
  ];
  $available = [];
  foreach ($columns as $columnName => $alterSql) {
    $available[$columnName] = ensureNotesColumn($conn, $columnName, $alterSql);
  }
  return $available;
}

function ensurePriorityColumn($conn) {
  return ensureNotesColumn($conn, 'priority_level', "ALTER TABLE notes ADD COLUMN priority_level TINYINT(1) NOT NULL DEFAULT 0");
}

function getClosedDates($conn, $hasDoneCol) {
  $dates = [];
  if (!$hasDoneCol) return $dates;
  $sql = "
    SELECT DISTINCT date
    FROM notes
    WHERE COALESCE(date, '') <> ''
      AND is_done = 1
    ORDER BY STR_TO_DATE(date, '%Y-%m-%d') DESC, date DESC
  ";
  try {
    $res = $conn->query($sql);
    if (!$res) return $dates;
    while ($row = $res->fetch_assoc()) {
      $date = trim((string)($row['date'] ?? ''));
      if ($date !== '') {
        $dates[] = $date;
      }
    }
  } catch (Throwable $e) {
    notesApiLogError('Closed dates query failed', ['error' => $e->getMessage()]);
    return [];
  }
  return $dates;
}

function normalizeAssignee($value) {
  $allowed = ['SYED', 'KARTHIK', 'GOWTHAM', 'AKIB', 'ZAIN', 'SIRENJIVI'];
  $candidate = strtoupper(trim((string)$value));
  return in_array($candidate, $allowed, true) ? $candidate : '';
}

function extractPhoneFromText($title, $desc = '', $extra = '') {
  $pool = trim(implode(' | ', array_filter([
    (string)$extra,
    (string)$title,
    (string)$desc
  ], static function ($value) {
    return trim((string)$value) !== '';
  })));

  if ($pool === '') return '';

  if (preg_match('/(?:phone|ph|mobile|mob|contact)\s*[:#-]?\s*(\+?[\d\s-]{10,15})/i', $pool, $match)) {
    $digits = preg_replace('/\D+/', '', $match[1]);
    if (strlen($digits) >= 10) return substr($digits, -10);
  }

  if (preg_match('/(?:(?:\+|00)91[\s-]?)?(?:0)?([6-9](?:[\s-]?\d){9})/', $pool, $match)) {
    $digits = preg_replace('/\D+/', '', $match[0]);
    if (strlen($digits) >= 10) return substr($digits, -10);
  }

  if (preg_match('/(?:^|[^\d])(\d{10})(?:[^\d]|$)/', $pool, $match)) {
    return $match[1];
  }

  foreach (explode('|', $pool) as $part) {
    $digits = preg_replace('/\D+/', '', (string)$part);
    if (strlen($digits) === 10) return $digits;
    if (strlen($digits) === 11 && strpos($digits, '0') === 0) return substr($digits, -10);
    if (strlen($digits) === 12 && strpos($digits, '91') === 0) return substr($digits, -10);
  }

  return '';
}

function findRecentPhoneDuplicate($conn, $phone, $excludeId = 0, $windowDays = 15) {
  $digits = preg_replace('/\D+/', '', (string)$phone);
  if (strlen($digits) < 10) return null;
  $needle = substr($digits, -10);

  $sql = "
    SELECT id, type, title, description, date, sort_order, username, created_at
    FROM notes
    WHERE (
      (created_at IS NOT NULL AND DATE(created_at) >= CURDATE() - INTERVAL ? DAY)
      OR
      (COALESCE(date, '') <> '' AND STR_TO_DATE(date, '%Y-%m-%d') >= CURDATE() - INTERVAL ? DAY)
    )
      AND (? <= 0 OR id <> ?)
    ORDER BY
      COALESCE(created_at, STR_TO_DATE(date, '%Y-%m-%d')) DESC,
      id DESC
  ";
  $stmt = $conn->prepare($sql);
  if (!$stmt) return null;
  $stmt->bind_param("iiii", $windowDays, $windowDays, $excludeId, $excludeId);
  if (!$stmt->execute()) return null;
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $foundPhone = extractPhoneFromText($row['title'] ?? '', $row['description'] ?? '');
    if ($foundPhone !== '' && $foundPhone === $needle) {
      return $row;
    }
  }
  return null;
}

function moveOpenPastNotesToToday($conn, $hasDoneCol) {
  if (!$hasDoneCol) return 0;

  try {
    $todayRes = $conn->query("SELECT CURDATE() AS today");
    if (!$todayRes) return 0;
    $todayRow = $todayRes->fetch_assoc();
    $today = $todayRow['today'] ?? '';
    if ($today === '') return 0;

    $maxQ = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) AS m FROM notes WHERE date = ?");
    if (!$maxQ) return 0;
    $maxQ->bind_param("s", $today);
    if (!$maxQ->execute()) return 0;
    $maxRes = $maxQ->get_result()->fetch_assoc();
    $nextOrder = ((int)($maxRes['m'] ?? -1)) + 1;

    $selectSql = "
      SELECT id
      FROM notes
      WHERE is_done = 0
        AND date IS NOT NULL
        AND date <> ''
        AND STR_TO_DATE(date, '%Y-%m-%d') < CURDATE()
      ORDER BY STR_TO_DATE(date, '%Y-%m-%d') ASC, sort_order ASC, id ASC
    ";
    $res = $conn->query($selectSql);
    if (!$res) return 0;

    $stmt = $conn->prepare("UPDATE notes SET date = ?, sort_order = ? WHERE id = ?");
    if (!$stmt) return 0;

    $moved = 0;
    while ($row = $res->fetch_assoc()) {
      $id = (int)($row['id'] ?? 0);
      if ($id <= 0) continue;
      $order = $nextOrder + $moved;
      $stmt->bind_param("sii", $today, $order, $id);
      if ($stmt->execute()) $moved++;
    }

    return $moved;
  } catch (Throwable $e) {
    notesApiLogError('Auto-move open notes failed', ['error' => $e->getMessage()]);
    return 0;
  }
}

$HAS_DONE_COL = ensureDoneColumn($conn);
$DONE_META_COLS = ensureDoneMetaColumns($conn);
$ASSIGNMENT_COLS = ensureAssignmentColumns($conn);
$REOPEN_COLS = ensureReopenColumns($conn);
$HAS_PRIORITY_COL = ensurePriorityColumn($conn);

// This file is dedicated to NOTES; no action switch needed
$method = $_SERVER['REQUEST_METHOD'];

/* ===================== GET ===================== */
if ($method === 'GET') {
  $date = isset($_GET['date']) ? trim($_GET['date']) : '';
  $includeExpired = isset($_GET['include_expired']) && $_GET['include_expired'] === '1';
  $movedCount = (isset($_GET['auto_move']) && $_GET['auto_move'] === '1')
    ? moveOpenPastNotesToToday($conn, $HAS_DONE_COL)
    : 0;
  $selDone = [
    $HAS_DONE_COL ? "is_done" : "0 AS is_done",
    !empty($DONE_META_COLS['done_by']) ? "done_by" : "NULL AS done_by",
    !empty($DONE_META_COLS['done_at']) ? "done_at" : "NULL AS done_at"
  ];
  $selAssigned = [
    !empty($ASSIGNMENT_COLS['assigned_to']) ? "assigned_to" : "NULL AS assigned_to",
    !empty($ASSIGNMENT_COLS['assigned_history']) ? "assigned_history" : "NULL AS assigned_history"
  ];
  $selReopen = [
    !empty($REOPEN_COLS['reopen_reason']) ? "reopen_reason" : "NULL AS reopen_reason",
    !empty($REOPEN_COLS['reopened_by']) ? "reopened_by" : "NULL AS reopened_by",
    !empty($REOPEN_COLS['reopened_at']) ? "reopened_at" : "NULL AS reopened_at",
    !empty($REOPEN_COLS['reopen_history']) ? "reopen_history" : "NULL AS reopen_history"
  ];
  $selOptional = implode(', ', array_merge($selDone, $selAssigned, $selReopen));
  $selPriority = $HAS_PRIORITY_COL ? "priority_level" : "0 AS priority_level";
  if ($date !== '') {
    $whereSql = $includeExpired && $HAS_DONE_COL
      ? "WHERE date = ? OR (COALESCE(is_done, 0) = 0 AND created_at IS NOT NULL AND DATE(created_at) <= CURDATE() - INTERVAL 15 DAY)"
      : "WHERE date = ?";
    $stmt = $conn->prepare(
      "SELECT id, type, title, description, date, sort_order, username, created_at, $selOptional, $selPriority
       FROM notes
       $whereSql
       ORDER BY sort_order ASC, id ASC"
    );
    if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
    $stmt->bind_param("s", $date);
    if (!$stmt->execute()) respond(['success'=>false,'message'=>'Query failed','error'=>$stmt->error], 500);
    $res = $stmt->get_result();
  } else {
    $res = $conn->query(
      "SELECT id, type, title, description, date, sort_order, username, created_at, $selOptional, $selPriority
       FROM notes
       WHERE date >= CURDATE() - INTERVAL 30 DAY
       ORDER BY date DESC, id DESC"
    );
    if (!$res) respond(['success'=>false,'message'=>'Query failed','error'=>$conn->error], 500);
  }
  $notes = [];
  while ($row = $res->fetch_assoc()) $notes[] = $row;
  respond([
    'success'=>true,
    'notes'=>$notes,
    'moved_count'=>$movedCount,
    'closed_dates'=>getClosedDates($conn, $HAS_DONE_COL)
  ]);
}

/* ===================== POST ===================== */
if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true) ?: [];

    // Presence flags (do not coerce values yet)
    $hasType  = array_key_exists('type', $body);
    $hasTitle = array_key_exists('title', $body);
    $hasDesc  = array_key_exists('description', $body);
    $hasDate  = array_key_exists('date', $body);
    $hasDone  = array_key_exists('is_done', $body);
    $hasPriority = array_key_exists('priority_level', $body);
    $hasAssigned = array_key_exists('assigned_to', $body);
    $hasReopenReason = array_key_exists('reopen_reason', $body);

    $id    = isset($body['id']) ? (int)$body['id'] : 0;
    $type  = $hasType  ? trim((string)($body['type'] ?? '')) : '';         // keep '' if not provided
    $title = $hasTitle ? trim((string)($body['title'] ?? '')) : '';
    $desc  = $hasDesc  ? trim((string)($body['description'] ?? '')) : '';
    $date  = $hasDate  ? trim((string)($body['date'] ?? '')) : '';
    $is_done = $hasDone ? (int)(!empty($body['is_done'])) : null;
    $priorityLevel = $hasPriority ? max(0, min(2, (int)$body['priority_level'])) : null;
    $assignedTo = $hasAssigned ? normalizeAssignee($body['assigned_to'] ?? '') : '';
    $username = isset($body['username']) ? trim($body['username']) : '';
    $reopenReason = $hasReopenReason ? trim((string)($body['reopen_reason'] ?? '')) : '';
    $incomingPhone = extractPhoneFromText($title, $desc, $body['phone'] ?? '');

    // ---------- A) REORDER ----------
    if (!empty($body['reorder']) && $hasDate && is_array($body['order'])) {
        $order = $body['order'];
        $stmt = $conn->prepare("UPDATE notes SET sort_order = ? WHERE id = ? AND date = ?");
        if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);

        $conn->begin_transaction();
        try {
            foreach ($order as $idx => $nid) {
                $i = (int)$idx;
                $nid = (int)$nid;
                $stmt->bind_param("iis", $i, $nid, $date);
                if (!$stmt->execute()) throw new Exception($stmt->error);
            }
            $conn->commit();
            respond(['success'=>true,'message'=>'Order updated']);
        } catch (Exception $e) {
            $conn->rollback();
            respond(['success'=>false,'message'=>'Reorder failed','error'=>$e->getMessage()], 500);
        }
    }

    // ---------- B) MOVE-ONLY (drag to another date) ----------
    // Only id + date are present; no type/title/desc provided
    $isMoveOnly = ($id > 0) && $hasDate && !$hasType && !$hasTitle && !$hasDesc;
    if ($isMoveOnly) {
        $maxQ = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) AS m FROM notes WHERE date = ?");
        if (!$maxQ) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
        $maxQ->bind_param("s", $date);
        $maxQ->execute();
        $maxRes = $maxQ->get_result()->fetch_assoc();
        $nextOrder = ((int)$maxRes['m']) + 1;

        $stmt = $conn->prepare("UPDATE notes SET date = ?, sort_order = ? WHERE id = ?");
        if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
        $stmt->bind_param("sii", $date, $nextOrder, $id);
        $ok = $stmt->execute();
        respond(['success'=>(bool)$ok,'message'=>$ok?'Note moved':'Move failed','error'=>$ok?null:$stmt->error], $ok?200:500);
    }

    // ---------- C) NORMAL UPSERTS ----------
    if ($id > 0) {
        // Fill missing fields from DB row
        $curSelDone = $HAS_DONE_COL ? ", is_done, done_by, done_at" : "";
        $curSelPriority = $HAS_PRIORITY_COL ? ", priority_level" : "";
        $cur = $conn->prepare("SELECT type, title, description, date, username, assigned_to, assigned_history, reopen_reason, reopened_by, reopened_at, reopen_history{$curSelDone}{$curSelPriority} FROM notes WHERE id = ?");
        if (!$cur) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
        $cur->bind_param("i", $id);
        $cur->execute();
        $old = $cur->get_result()->fetch_assoc();
        if (!$old) respond(['success'=>false,'message'=>'Note not found'], 404);

        // Only apply provided fields; else keep old
        $newType  = $hasType  ? (in_array($type, ['General','Issue','Inspection'], true) ? $type : $old['type']) : $old['type'];
        $newTitle = $hasTitle ? $title  : $old['title'];
        $newDesc  = $hasDesc  ? $desc   : $old['description'];
        $newDate  = $hasDate  ? $date   : $old['date'];
        $newDone  = $HAS_DONE_COL ? ($hasDone ? $is_done : (int)($old['is_done'] ?? 0)) : 0;
        $oldDoneBy = $old['done_by'] ?? null;
        $oldUsername = $old['username'] ?? '';
        $oldAssignedTo = trim((string)($old['assigned_to'] ?? ''));
        $oldAssignedHistory = trim((string)($old['assigned_history'] ?? ''));
        $oldReopenReason = trim((string)($old['reopen_reason'] ?? ''));
        $oldReopenedBy = trim((string)($old['reopened_by'] ?? ''));
        $oldReopenHistory = trim((string)($old['reopen_history'] ?? ''));
        $newUsername = $oldUsername;
        if ($username && strpos($oldUsername, $username) === false) {
            $newUsername = $oldUsername ? ($oldUsername . ', ' . $username) : $username;
        }
        $newAssignedTo = $hasAssigned ? $assignedTo : $oldAssignedTo;
        $newPriorityLevel = $HAS_PRIORITY_COL ? ($hasPriority ? $priorityLevel : (int)($old['priority_level'] ?? 0)) : 0;
        $newAssignedHistory = $oldAssignedHistory;
        if ($hasAssigned && $newAssignedTo !== $oldAssignedTo && $newAssignedTo !== '') {
            $normalizedHistory = preg_replace('/\s*\|\s*/', ', ', $oldAssignedHistory);
            $newAssignedHistory = $normalizedHistory ? ($normalizedHistory . ', ' . $newAssignedTo) : $newAssignedTo;
        }

        if ($newTitle === '' || $newDate === '') {
            respond(['success'=>false,'message'=>'Missing required fields: title, date'], 422);
        }

        $newDoneBy = $oldDoneBy;
        $newReopenReason = $oldReopenReason;
        $newReopenedBy = $oldReopenedBy;
        $newReopenHistory = $oldReopenHistory;
        $markingDone = $HAS_DONE_COL && $hasDone && (int)$newDone === 1 && (int)($old['is_done'] ?? 0) === 0;
        $reopening = $HAS_DONE_COL && $hasDone && (int)$newDone === 0 && (int)($old['is_done'] ?? 0) === 1;
        if ($HAS_DONE_COL && $hasDone) {
            if ($markingDone) {
                $newDoneBy = ($username !== '') ? $username : $oldDoneBy;
            } elseif ($reopening) {
                if ($reopenReason === '') {
                    respond(['success'=>false,'message'=>'Reopen reason is required'], 422);
                }
                $newReopenReason = $reopenReason;
                $newReopenedBy = ($username !== '') ? $username : $oldReopenedBy;
                $historyEntry = trim(
                  ($username !== '' ? $username : 'Unknown')
                  . ' @ '
                  . date('Y-m-d H:i:s')
                  . ' - '
                  . preg_replace('/\s+/', ' ', $reopenReason)
                );
                $newReopenHistory = $oldReopenHistory !== '' ? ($oldReopenHistory . "\n" . $historyEntry) : $historyEntry;
            } elseif ((int)$newDone === 0 && !$reopening) {
                $newReopenReason = $hasReopenReason ? $reopenReason : $oldReopenReason;
            }
        }

        if ($newDate !== $old['date']) {
            $maxQ = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) AS m FROM notes WHERE date = ?");
            $maxQ->bind_param("s", $newDate);
            $maxQ->execute();
            $maxRes = $maxQ->get_result()->fetch_assoc();
            $nextOrder = ((int)$maxRes['m']) + 1;

            if ($HAS_DONE_COL) {
              $hasDoneInt = $hasDone ? 1 : 0;
              $markingDoneInt = $markingDone ? 1 : 0;
              $reopeningInt = $reopening ? 1 : 0;
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, sort_order=?, username=?, assigned_to=?, assigned_history=?, priority_level=?, is_done=?, done_by=?, done_at = CASE WHEN ? = 1 AND ? = 1 THEN NOW() WHEN ? = 1 AND ? = 1 THEN NULL ELSE done_at END, reopen_reason=?, reopened_by=?, reopened_at = CASE WHEN ? = 1 THEN NOW() ELSE reopened_at END, reopen_history=? WHERE id=?");
              $stmt->bind_param("ssssisssiisiiiissisi", $newType, $newTitle, $newDesc, $newDate, $nextOrder, $newUsername, $newAssignedTo, $newAssignedHistory, $newPriorityLevel, $newDone, $newDoneBy, $hasDoneInt, $markingDoneInt, $hasDoneInt, $reopeningInt, $newReopenReason, $newReopenedBy, $reopeningInt, $newReopenHistory, $id);
            } else {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, sort_order=?, username=?, assigned_to=?, assigned_history=?, priority_level=? WHERE id=?");
              $stmt->bind_param("ssssisssii", $newType, $newTitle, $newDesc, $newDate, $nextOrder, $newUsername, $newAssignedTo, $newAssignedHistory, $newPriorityLevel, $id);
            }
        } else {
            if ($HAS_DONE_COL) {
              $hasDoneInt = $hasDone ? 1 : 0;
              $markingDoneInt = $markingDone ? 1 : 0;
              $reopeningInt = $reopening ? 1 : 0;
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, username=?, assigned_to=?, assigned_history=?, priority_level=?, is_done=?, done_by=?, done_at = CASE WHEN ? = 1 AND ? = 1 THEN NOW() WHEN ? = 1 AND ? = 1 THEN NULL ELSE done_at END, reopen_reason=?, reopened_by=?, reopened_at = CASE WHEN ? = 1 THEN NOW() ELSE reopened_at END, reopen_history=? WHERE id=?");
              $stmt->bind_param("sssssssiisiiiissisi", $newType, $newTitle, $newDesc, $newDate, $newUsername, $newAssignedTo, $newAssignedHistory, $newPriorityLevel, $newDone, $newDoneBy, $hasDoneInt, $markingDoneInt, $hasDoneInt, $reopeningInt, $newReopenReason, $newReopenedBy, $reopeningInt, $newReopenHistory, $id);
            } else {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, username=?, assigned_to=?, assigned_history=?, priority_level=? WHERE id=?");
              $stmt->bind_param("sssssssii", $newType, $newTitle, $newDesc, $newDate, $newUsername, $newAssignedTo, $newAssignedHistory, $newPriorityLevel, $id);
            }
        }

        $ok = $stmt->execute();
        respond(['success'=>(bool)$ok,'message'=>$ok?'Note updated':'Update failed','id'=>$id,'error'=>$ok?null:$stmt->error], $ok?200:500);

        } else {
            // INSERT: require title + date; default type only here
            if (!$hasTitle || !$hasDate || $title === '' || $date === '') {
                respond(['success'=>false,'message'=>'Missing required fields: title, date'], 422);
            }

            $maxQ = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) AS m FROM notes WHERE date = ?");
            if (!$maxQ) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
            $maxQ->bind_param("s", $date);
            $maxQ->execute();
            $maxRes = $maxQ->get_result()->fetch_assoc();
            $nextOrder = ((int)$maxRes['m']) + 1;

            $insType = $hasType ? (in_array($type, ['General','Issue','Inspection'], true) ? $type : 'General') : 'General';
            $insAssignedTo = $hasAssigned ? $assignedTo : '';
            $insAssignedHistory = $insAssignedTo !== '' ? $insAssignedTo : '';
            $insPriorityLevel = $HAS_PRIORITY_COL && $hasPriority ? $priorityLevel : 0;
            if ($HAS_DONE_COL) {
              $stmt = $conn->prepare("INSERT INTO notes (type, title, description, date, sort_order, created_at, username, assigned_to, assigned_history, priority_level, is_done) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
              if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
              $insDone = 0;
              $stmt->bind_param("ssssisssii", $insType, $title, $desc, $date, $nextOrder, $username, $insAssignedTo, $insAssignedHistory, $insPriorityLevel, $insDone);
            } else {
              $stmt = $conn->prepare("INSERT INTO notes (type, title, description, date, sort_order, created_at, username, assigned_to, assigned_history, priority_level) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
              if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
              $stmt->bind_param("ssssisssi", $insType, $title, $desc, $date, $nextOrder, $username, $insAssignedTo, $insAssignedHistory, $insPriorityLevel);
            }

            $ok = $stmt->execute();
            respond(['success'=>(bool)$ok,'id'=>$ok?(int)$conn->insert_id:null,'message'=>$ok?'Note saved':'Insert failed','error'=>$ok?null:$stmt->error], $ok?200:500);
        }
}

/* ===================== DELETE ===================== */
if ($method === 'DELETE') {
  $body = json_decode(file_get_contents('php://input'), true) ?: [];
  $id = isset($body['id']) ? (int)$body['id'] : 0;
  if ($id <= 0) respond(['success'=>false,'message'=>'Missing id'], 422);

  $stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
  if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
  $stmt->bind_param("i", $id);
  $ok = $stmt->execute();

  respond(['success'=>(bool)$ok,'message'=>$ok?'Note deleted':'Delete failed','error'=>$ok?null:$stmt->error], $ok?200:500);
}

// Method not allowed
respond(['success'=>false,'message'=>'Method not allowed'], 405);
