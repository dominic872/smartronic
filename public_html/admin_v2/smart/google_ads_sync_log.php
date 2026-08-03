<?php
require_once __DIR__ . '/../auth.php';
requireSmartPageAccess('gads_stats', $role, $authPages);

$logPath = __DIR__ . '/google_ads_sync.log';
$txtPath = __DIR__ . '/google_ads_sync.txt';
$path = is_file($logPath) ? $logPath : $txtPath;
$content = is_file($path) ? (string)file_get_contents($path) : '';
$lines = $content !== '' ? preg_split('/\R/', trim($content)) : [];
$lines = array_slice(array_filter($lines, static fn($line) => trim((string)$line) !== ''), -250);
$display = $lines ? implode(PHP_EOL, $lines) : 'No Google Ads sync log has been created yet. Run the cron once or click Sync This Period.';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SM Ads | Sync Log</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      background: #f7f9fc;
      color: #1f2937;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
    }
    main {
      width: min(980px, calc(100% - 24px));
      margin: 0 auto;
      padding: 22px 0;
    }
    .head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
      flex-wrap: wrap;
    }
    h1 {
      margin: 0;
      font-size: 24px;
      font-weight: 650;
    }
    .actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 1px solid #dbe3ef;
      background: #fff;
      color: #243044;
      text-decoration: none;
      border-radius: 6px;
      padding: 8px 11px;
      font-size: 13px;
    }
    .meta {
      margin-bottom: 10px;
      color: #6b7280;
      font-size: 13px;
    }
    pre {
      margin: 0;
      min-height: 340px;
      white-space: pre-wrap;
      word-break: break-word;
      background: #111827;
      color: #e5e7eb;
      border-radius: 8px;
      padding: 16px;
      font-size: 13px;
      line-height: 1.55;
      box-shadow: 0 10px 28px rgba(15, 23, 42, .12);
    }
  </style>
</head>
<body>
  <main>
    <div class="head">
      <h1>Google Ads Sync Log</h1>
      <div class="actions">
        <a href="gads_conversion.php">Dashboard</a>
        <a href="sync_google_ads.php?run=1">Run Sync Now</a>
      </div>
    </div>
    <div class="meta">Showing latest 250 lines from <?php echo htmlspecialchars(basename($path), ENT_QUOTES, 'UTF-8'); ?></div>
    <pre><?php echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8'); ?></pre>
  </main>
</body>
</html>
