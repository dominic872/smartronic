<?php
require_once __DIR__ . '/../auth.php';
requireSmartPageAccess('gads_stats', $role, $authPages);
$isAjax = isset($_GET['range']);
if ($isAjax) {
    error_reporting(0);
    ini_set('display_errors', 0);
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
    }
    ob_start();
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
}

// Load DB config from available paths
$paths = [
  __DIR__ . '/config.php',
  __DIR__ . '/../config.php',
  __DIR__ . '/admin/config.php',
  dirname(__DIR__) . '/admin/config.php'
];

$configLoaded = false;
foreach ($paths as $p) {
  if (file_exists($p)) { require_once $p; $configLoaded = true; break; }
}

if (!$configLoaded || !isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : 'config.php not found'));
}

date_default_timezone_set('Asia/Kolkata'); 

function tableExists(mysqli $conn, string $table): bool {
    $table = trim($table);
    if ($table === '') return false;
    try {
        $escaped = $conn->real_escape_string($table);
        $res = $conn->query("SHOW TABLES LIKE '$escaped'");
        return $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $table = trim($table);
    $column = trim($column);
    if ($table === '' || $column === '') return false;
    if (!in_array($table, ['wp_cctv_requirements', 'leads'], true)) return false;
    try {
        $escaped = $conn->real_escape_string($column);
        $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$escaped'");
        return $res && $res->num_rows > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function jsonResponse(array $payload): void {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
}

$statsTable = tableExists($conn, 'leads') ? 'leads' : (tableExists($conn, 'wp_cctv_requirements') ? 'wp_cctv_requirements' : null);
$createdCol = 'created_at';
$labelCol = ($statsTable === 'wp_cctv_requirements') ? 'comments' : 'Column_1';

// 🔹 Handle AJAX data request
if (isset($_GET['range'])) {
    $range = $_GET['range'];
    $where = "1=1"; 
    if (!$statsTable) {
        jsonResponse(['success' => false, 'error' => 'Stats source table not found.']);
        $conn->close();
        exit;
    }
    if (!columnExists($conn, $statsTable, $createdCol)) {
        jsonResponse(['success' => false, 'error' => 'created_at column missing in stats table.']);
        $conn->close();
        exit;
    }
    if (!columnExists($conn, $statsTable, $labelCol)) {
        $labelCol = columnExists($conn, $statsTable, 'comments') ? 'comments' : (columnExists($conn, $statsTable, 'Column_1') ? 'Column_1' : $labelCol);
    }

    switch ($range) {
        case '7':
            $where = "DATE($createdCol) >= CURDATE() - INTERVAL 6 DAY";
            break;
        case '30':
            $where = "DATE($createdCol) >= CURDATE() - INTERVAL 29 DAY";
            break;
        case 'this_month':
            $where = "MONTH($createdCol) = MONTH(CURDATE()) AND YEAR($createdCol) = YEAR(CURDATE())";
            break;
        case 'last_month':
            $where = "MONTH($createdCol) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR($createdCol) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
            break;
        default:
            $where = "DATE($createdCol) = CURDATE()";
            break;
    }

    // Total records
    $totalResult = $conn->query("SELECT COUNT(*) AS total FROM `$statsTable` WHERE $where");
    if (!$totalResult) {
        jsonResponse(['success' => false, 'error' => 'Failed to load totals.']);
        $conn->close();
        exit;
    }
    $totalRow = $totalResult->fetch_assoc() ?: [];
    $total = $totalRow['total'] ?? 0;

    // Past 4 days data
    $past_days = [];
    for ($i = 0; $i <= 4; $i++) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $r = $conn->query("SELECT COUNT(*) AS c FROM `$statsTable` WHERE DATE($createdCol)='$date'");
        $row = $r ? ($r->fetch_assoc() ?: []) : [];
        $count = $row['c'] ?? 0;
        $past_days[] = ['date' => $date, 'day' => date('D', strtotime($date)), 'count' => $count];
    }

    // Group by comments
    $grouped = [];
    $query = "SELECT `$labelCol` AS label, COUNT(*) AS total FROM `$statsTable` WHERE $where GROUP BY `$labelCol` ORDER BY total DESC";
    $r = $conn->query($query);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $grouped[] = ['label' => $row['label'] ?: 'Unknown', 'total' => $row['total']];
        }
    }

    jsonResponse([
        'success' => true,
        'total' => $total,
        'past_days' => $past_days,
        'grouped' => $grouped
    ]);
    $conn->close();
    exit;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CCTV Enquiry Dashboard 1</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../css/gads_stats.css">
</head>
<body class="page-body">
<div class="stats-container">
    <div class="top-bar">
      <button id="reloadBtn" class="reload-btn" aria-label="Reload">
        <svg class="reload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"></polyline>
          <path d="M20.49 15a9 9 0 1 1 .51-9"></path>
        </svg>
      </button>
    </div>
    <h1 class="text-3xl font-bold text-gray-800 mb-6">CCTV Enquiry Stats1</h1>

    <!-- Range Selector as Links -->
    <div class="flex justify-center mb-6">
  <div class="segmented">
    <button class="range-link segment-btn" data-range="1">Today</button>
    <button class="range-link segment-btn" data-range="7">Last 7 Days</button>
    <button class="range-link segment-btn" data-range="30">Last 30 Days</button>
    <button class="range-link segment-btn" data-range="this_month">This Month</button>
    <button class="range-link segment-btn" data-range="last_month">Last Month</button>
  </div>
</div>

    <!-- Total Count -->
    <div id="total" class="total-number">0</div>
    <div class="total-label" id="totalLabel">Loading...</div>

    <!-- Past 4 Days -->
    <div id="pastDays" class="past-days"></div>

    <!-- Grouped Boxes -->
    <div id="grouped" class="grouped-grid"></div>
</div>

<script>
let currentRange = "1";
const reloadBtn = document.getElementById("reloadBtn");
async function loadStats(range = "1") {
  currentRange = range;
  // highlight segmented control
  document.querySelectorAll(".segment-btn").forEach(el => el.classList.remove("active"));
  const activeEl = document.querySelector(`.segment-btn[data-range="${range}"]`);
  if (activeEl) { activeEl.classList.add("active"); }
  // start loading state
  if (reloadBtn) { reloadBtn.disabled = true; document.body.classList.add("reloading"); }
  try {
    const res = await fetch(`?range=${range}`);
    const data = await res.json().catch(() => null);
    if (!res.ok || !data || !data.success) {
      document.getElementById("total").textContent = "—";
      document.getElementById("totalLabel").textContent = (data && data.error) ? data.error : "Failed to load";
      document.getElementById("pastDays").innerHTML = "";
      document.getElementById("grouped").innerHTML = `<div class="col-span-full text-gray-500 italic">No data.</div>`;
      return;
    }
    document.getElementById("total").textContent = data.total;
    let labelText = "Enquiries";
    if (range === "1") labelText = "Today's Enquiries";
    else if (range === "7") labelText = "Last 7 Days";
    else if (range === "30") labelText = "Last 30 Days";
    else if (range === "this_month") labelText = "This Month";
    else if (range === "last_month") labelText = "Last Month";
    document.getElementById("totalLabel").textContent = labelText;
    const pastDaysDiv = document.getElementById("pastDays");
    pastDaysDiv.innerHTML = "";
    data.past_days.slice(1).forEach(d => {
      const div = document.createElement("div");
      div.className = "past-day";
      div.innerHTML = `<span class="font-bold">${d.count}</span><span>${d.day}</span>`;
      pastDaysDiv.appendChild(div);
    });
    const groupedDiv = document.getElementById("grouped");
    groupedDiv.innerHTML = "";
    if (data.grouped.length === 0) {
      groupedDiv.innerHTML = `<div class="col-span-full text-gray-500 italic">No records found.</div>`;
    } else {
      data.grouped.forEach(g => {
        const box = document.createElement("div");
        box.className = "group-box";
        box.innerHTML = `
          <div class="count">${g.total}</div>
          <div class="label">${g.label}</div>`;
        groupedDiv.appendChild(box);
      });
    }
  } finally {
    if (reloadBtn) { reloadBtn.disabled = false; document.body.classList.remove("reloading"); }
  }
}
// initial load
loadStats();
// segmented control clicks
document.querySelectorAll(".range-link").forEach(link => {
  link.addEventListener("click", e => {
    e.preventDefault();
    loadStats(link.dataset.range);
  });
});
// reload button
if (reloadBtn) { reloadBtn.addEventListener("click", () => loadStats(currentRange)); }
// auto reload every 15 minutes
setInterval(() => loadStats(currentRange), 15 * 60 * 1000);
</script>
</body>
</html>
