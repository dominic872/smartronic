<?php
require 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ensure table exists to avoid errors on fresh installs
$pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    ip VARCHAR(64),
    user_agent TEXT,
    start_time_ist DATETIME NOT NULL,
    end_time_ist DATETIME NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=access_logs.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Name','IP','User Agent','Start (IST)','End (IST)','Duration (mins)']);
    $stmt = $pdo->query("SELECT * FROM access_logs ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $start = $row['start_time_ist'];
        $end = $row['end_time_ist'];
        $durMin = '';
        if ($end) {
            $durMin = round((strtotime($end) - strtotime($start)) / 60, 1);
        }
        fputcsv($out, [
            $row['id'], $row['name'], $row['ip'], $row['user_agent'], $start, $end, $durMin
        ]);
    }
    fclose($out);
    exit;
}

// Fetch logs
$stmt = $pdo->query("SELECT * FROM access_logs ORDER BY id DESC");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Access Logs</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
  <style>
    body { background:#f7f7f7; }
    .container { max-width: 1100px; }
    table.striped tbody tr:nth-child(odd) { background-color: #f9fafb; }
    .ua { max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nowrap { white-space: nowrap; }
    .tools { display:flex; gap:10px; align-items:center; margin: 16px 0; }
  </style>
</head>
<body>
  <div class="container">
    <h5 style="margin-top:22px">Access Logs</h5>
    <div class="tools">
      <a class="btn" href="access_logs.php?export=csv">Export CSV</a>
      <a class="btn grey" href="admin.php">Back to Admin</a>
    </div>

    <table class="striped highlight responsive-table">
      <thead>
        <tr>
          <th class="nowrap">ID</th>
          <th>Name</th>
          <th class="nowrap">IP</th>
          <th>User Agent</th>
          <th class="nowrap">Start (IST)</th>
          <th class="nowrap">End (IST)</th>
          <th class="nowrap">Duration (mins)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $r): 
          $dur = '';
          if (!empty($r['end_time_ist'])) {
            $dur = round((strtotime($r['end_time_ist']) - strtotime($r['start_time_ist']))/60, 1);
          }
        ?>
          <tr>
            <td class="nowrap"><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td class="nowrap"><?= htmlspecialchars($r['ip'] ?? '') ?></td>
            <td class="ua" title="<?= htmlspecialchars($r['user_agent'] ?? '') ?>"><?= htmlspecialchars($r['user_agent'] ?? '') ?></td>
            <td class="nowrap"><?= htmlspecialchars($r['start_time_ist']) ?></td>
            <td class="nowrap"><?= htmlspecialchars($r['end_time_ist'] ?? '') ?></td>
            <td class="nowrap"><?= htmlspecialchars($dur) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
          <tr><td colspan="7" class="center-align">No logs yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
