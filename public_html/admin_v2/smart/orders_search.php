<?php
ob_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

set_exception_handler(function($e){
  http_response_code(500);
  if (ob_get_length()) ob_clean();
  echo json_encode(['results'=>[], 'error'=>$e->getMessage()]);
  exit;
});

// --- Load DB config ---
$conn = null;
foreach ([__DIR__.'/config.php', __DIR__.'/../config.php', dirname(__DIR__).'/config.php'] as $p) {
  if (file_exists($p)) { require_once $p; break; }
}
if (!isset($conn) || $conn->connect_error) {
  echo json_encode(['results'=>[], 'error'=>'DB connection failed']); exit;
}

// --- Input ---
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
// Extract numeric part for phone search and alphabetic part for name search
$digitsOnly = preg_replace('/\D+/', '', $q); // keep only digits
$nameOnly   = preg_replace('/[^\p{L}\s]/u', '', $q); // keep letters and spaces
$nameOnly   = trim(preg_replace('/\s+/', ' ', $nameOnly));

if (strlen($digitsOnly) > 10 && substr($digitsOnly, 0, 2) === '91') {
  $digitsOnly = substr($digitsOnly, -10);
}

$hasPhone = strlen($digitsOnly) >= 3;
$hasName  = strlen($nameOnly)   >= 3;

// If neither a phone-like nor a name-like term is present, return empty
if (!$hasPhone && !$hasName) {
  echo json_encode(['results'=>[]]); exit;
}

// Escape LIKE wildcards in a parameter-safe way
function like_pattern(string $term): string {
  return '%' . str_replace(["\\", "%", "_"], ["\\\\", "\\%", "\\_"], $term) . '%';
}

$phonePattern = $hasPhone ? like_pattern($digitsOnly) : null;
$namePattern  = $hasName  ? like_pattern($nameOnly)   : null;

// keep only digits and leading + (optional)
function sanitizePhone(?string $p): ?string {
  if ($p === null) return null;
  $p = trim($p);
  if ($p === '') return null;
  // keep + if it is the first char, then digits
  $p = preg_replace('/(?!^)\D+/', '', $p); // strip non-digits except possible leading +
  $p = preg_replace('/[^+\d]/', '', $p);
  return $p;
}

// Helpers using INFORMATION_SCHEMA (safe for prepared statements)
function table_exists(mysqli $conn, string $table): bool {
  $sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
  $st = $conn->prepare($sql);
  if (!$st) return false;
  $st->bind_param("s", $table);
  $st->execute();
  return (bool)$st->get_result()->fetch_row();
}

function column_exists(mysqli $conn, string $table, string $col): bool {
  $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
  $st = $conn->prepare($sql);
  if (!$st) return false;
  $st->bind_param("ss", $table, $col);
  $st->execute();
  return (bool)$st->get_result()->fetch_row();
}

function find_first_col(mysqli $conn, string $table, array $candidates): ?string {
  foreach ($candidates as $c) {
    if (column_exists($conn, $table, $c)) return $c;
  }
  return null;
}

// List all tables
$tables = [];
if ($res = $conn->query("SHOW TABLES")) {
  while ($row = $res->fetch_array(MYSQLI_NUM)) $tables[] = $row[0];
}

// Priorities
$preferredTables = ['installs','orders','order','work_orders','tickets','jobs','projects','service_calls','leads','customers','clients'];
$phoneCandidates = ['phone','mobile','contact_phone','contact_number','customer_phone','primary_phone','whatsapp','whatsapp_number'];
$optional = [
  'id'          => ['id','order_id','install_id','ticket_id','job_id'],
  'name'        => ['name','customer_name','client_name','fullname','contact_name'],
  'date'        => ['date','order_date','install_date','scheduled_date','created_at','createdon','created_on'],
  'location'    => ['location','address','address1','site','place','area'],
  'cams'        => ['cams','cameras','num_cameras','camera_count','total'],
  'type'        => ['type','dvr_type','job_type','order_type'],
  'resolution'  => ['resolution','camera_resolution','res'],
  'hdd'         => ['hdd','hdd_size','storage'],
  'map'         => ['map','map_url','google_map','maplink','map_link'],
  'owner'       => ['owner','team','assigned_owner','division'],
  'technician'  => ['technician','tech','assigned_to','assigned_tech','engineer'],
];

// Choose table: preferred first, else any with a phone-like column
$ordered = array_values(array_unique(array_merge(
  array_values(array_intersect($preferredTables, $tables)),
  $tables
)));

$chosen = null;
$colMap = []; // canonical => actual
foreach ($ordered as $tbl) {
  if (!table_exists($conn, $tbl)) continue;
  $phoneCol = find_first_col($conn, $tbl, $phoneCandidates);
  if (!$phoneCol) continue;

  $chosen = $tbl;
  $colMap['phone'] = $phoneCol;
  foreach ($optional as $canon => $cands) {
    $hit = find_first_col($conn, $tbl, $cands);
    if ($hit) $colMap[$canon] = $hit;
  }
  break;
}

if (!$chosen) {
  echo json_encode(['results'=>[], 'error'=>'No table with a phone-like column found.']); exit;
}

// Build SELECT (alias to uniform names); fill missing with NULL
$select = [];
foreach (['id','name','phone','date','location','cams','type','resolution','hdd','map','owner','technician'] as $canon) {
  if (isset($colMap[$canon])) $select[] = "`{$colMap[$canon]}` AS `$canon`";
  else                        $select[] = "NULL AS `$canon`";
}
$selectSql = implode(', ', $select);

// Order by date if present, else by id if present, else by phone
$orderBy = isset($colMap['date']) ? "`{$colMap['date']}` DESC"
        : (isset($colMap['id'])   ? "`{$colMap['id']}` DESC"
                                  : "`{$colMap['phone']}` DESC");

// Final query (only the LIKE value is parameterized)
// Build dynamic WHERE based on available search terms
$whereParts = [];
$params = [];
if ($hasPhone) {
  $phoneExpr = "`{$colMap['phone']}`";
  $phoneExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($phoneExpr, ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', '')";
  $whereParts[] = "$phoneExpr LIKE ?";
  $params[] = $phonePattern;
}
if ($hasName && isset($colMap['name'])) {
  $whereParts[] = "`{$colMap['name']}` LIKE ?";
  $params[] = $namePattern;
}

// If no valid where parts (e.g., searching by name but table has no name column), return empty
if (!$whereParts) {
  echo json_encode(['results'=>[], 'meta'=>['table'=>$chosen, 'columns'=>$colMap, 'note'=>'No matching searchable columns for the given query.']]);
  exit;
}

$whereSql = implode(' OR ', $whereParts);
$sql = "SELECT $selectSql FROM `$chosen` WHERE ($whereSql) ORDER BY $orderBy LIMIT 20";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['results'=>[], 'error'=>'Prepare failed: '.$conn->error, 'meta'=>['table'=>$chosen,'columns'=>$colMap]]); exit;
}
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
  echo json_encode(['results'=>[], 'error'=>'Execute failed: '.$stmt->error, 'meta'=>['table'=>$chosen,'sql'=>$sql]]); exit;
}
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
  if (isset($row['cams'])) $row['cams'] = is_null($row['cams']) ? null : (int)$row['cams'];
  $out[] = $row;
}

echo json_encode([
  'results' => $out,
  'meta' => ['table'=>$chosen, 'columns'=>$colMap]
]);
