<?php
// --- Always return clean JSON, even on errors ---
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

set_exception_handler(function($e){
  http_response_code(500);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode(['success'=>false,'message'=>'Server exception','error'=>$e->getMessage()]);
  exit;
});
set_error_handler(function($severity,$message,$file,$line){
  http_response_code(500);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode(['success'=>false,'message'=>'PHP error','error'=>"$message @ $file:$line"]);
  return true;
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e) {
    http_response_code(500);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success'=>false,'message'=>'Fatal error','error'=>$e['message']]);
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
  if (ob_get_length()) { ob_clean(); }
  echo json_encode([
    'success' => false,
    'message' => 'DB connection not available from notes_api.php',
    'error'   => isset($conn) ? $conn->connect_error : 'config.php not found'
  ]);
  exit;
}

function respond($arr, $code = 200){
  http_response_code($code);
  if (ob_get_length()) { ob_clean(); }
  echo json_encode($arr);
  exit;
}

function allowType($t) {
  return in_array($t, ['General','Issue','Inspection'], true) ? $t : 'General';
}

function ensureDoneColumn($conn) {
  $res = $conn->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notes' AND COLUMN_NAME = 'is_done'");
  if (!$res) return false;
  $row = $res->fetch_assoc();
  $exists = ((int)($row['c'] ?? 0)) > 0;
  if ($exists) return true;
  $ok = $conn->query("ALTER TABLE notes ADD COLUMN is_done TINYINT(1) NOT NULL DEFAULT 0");
  return (bool)$ok;
}

function moveOpenPastNotesToToday($conn, $hasDoneCol) {
  if (!$hasDoneCol) return 0;

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
}

$HAS_DONE_COL = ensureDoneColumn($conn);

// This file is dedicated to NOTES; no action switch needed
$method = $_SERVER['REQUEST_METHOD'];

/* ===================== GET ===================== */
if ($method === 'GET') {
  $date = isset($_GET['date']) ? trim($_GET['date']) : '';
  $movedCount = moveOpenPastNotesToToday($conn, $HAS_DONE_COL);
  $selDone = $HAS_DONE_COL ? "is_done" : "0 AS is_done";
  if ($date !== '') {
    $stmt = $conn->prepare(
      "SELECT id, type, title, description, date, sort_order, username, created_at, $selDone
       FROM notes
       WHERE date = ?
       ORDER BY sort_order ASC, id ASC"
    );
    if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
    $stmt->bind_param("s", $date);
    if (!$stmt->execute()) respond(['success'=>false,'message'=>'Query failed','error'=>$stmt->error], 500);
    $res = $stmt->get_result();
  } else {
    $res = $conn->query(
      "SELECT id, type, title, description, date, sort_order, username, created_at, $selDone
       FROM notes
       WHERE date >= CURDATE() - INTERVAL 30 DAY
       ORDER BY date DESC, id DESC"
    );
    if (!$res) respond(['success'=>false,'message'=>'Query failed','error'=>$conn->error], 500);
  }
  $notes = [];
  while ($row = $res->fetch_assoc()) $notes[] = $row;
  respond(['success'=>true,'notes'=>$notes,'moved_count'=>$movedCount]);
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

    $id    = isset($body['id']) ? (int)$body['id'] : 0;
    $type  = $hasType  ? trim((string)($body['type'] ?? '')) : '';         // keep '' if not provided
    $title = $hasTitle ? trim((string)($body['title'] ?? '')) : '';
    $desc  = $hasDesc  ? trim((string)($body['description'] ?? '')) : '';
    $date  = $hasDate  ? trim((string)($body['date'] ?? '')) : '';
    $is_done = $hasDone ? (int)(!empty($body['is_done'])) : null;
    $username = isset($body['username']) ? trim($body['username']) : '';

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
        $curSelDone = $HAS_DONE_COL ? ", is_done" : "";
        $cur = $conn->prepare("SELECT type, title, description, date, username{$curSelDone} FROM notes WHERE id = ?");
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
        $oldUsername = $old['username'] ?? '';
        $newUsername = $oldUsername;
        if ($username && strpos($oldUsername, $username) === false) {
            $newUsername = $oldUsername ? ($oldUsername . ', ' . $username) : $username;
        }

        if ($newTitle === '' || $newDate === '') {
            respond(['success'=>false,'message'=>'Missing required fields: title, date'], 422);
        }

        if ($newDate !== $old['date']) {
            $maxQ = $conn->prepare("SELECT COALESCE(MAX(sort_order), -1) AS m FROM notes WHERE date = ?");
            $maxQ->bind_param("s", $newDate);
            $maxQ->execute();
            $maxRes = $maxQ->get_result()->fetch_assoc();
            $nextOrder = ((int)$maxRes['m']) + 1;

            if ($HAS_DONE_COL) {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, sort_order=?, username=?, is_done=? WHERE id=?");
              $stmt->bind_param("ssssisii", $newType, $newTitle, $newDesc, $newDate, $nextOrder, $newUsername, $newDone, $id);
            } else {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, sort_order=?, username=? WHERE id=?");
              $stmt->bind_param("ssssisi", $newType, $newTitle, $newDesc, $newDate, $nextOrder, $newUsername, $id);
            }
        } else {
            if ($HAS_DONE_COL) {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, username=?, is_done=? WHERE id=?");
              $stmt->bind_param("sssssii", $newType, $newTitle, $newDesc, $newDate, $newUsername, $newDone, $id);
            } else {
              $stmt = $conn->prepare("UPDATE notes SET type=?, title=?, description=?, date=?, username=? WHERE id=?");
              $stmt->bind_param("sssssi", $newType, $newTitle, $newDesc, $newDate, $newUsername, $id);
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
            if ($HAS_DONE_COL) {
              $stmt = $conn->prepare("INSERT INTO notes (type, title, description, date, sort_order, created_at, username, is_done) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
              if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
              $insDone = $hasDone ? $is_done : 0;
              $stmt->bind_param("ssssisi", $insType, $title, $desc, $date, $nextOrder, $username, $insDone);
            } else {
              $stmt = $conn->prepare("INSERT INTO notes (type, title, description, date, sort_order, created_at, username) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
              if (!$stmt) respond(['success'=>false,'message'=>'Prepare failed','error'=>$conn->error], 500);
              $stmt->bind_param("ssssis", $insType, $title, $desc, $date, $nextOrder, $username);
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
