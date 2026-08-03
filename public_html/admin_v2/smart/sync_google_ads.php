<?php
$isCli = PHP_SAPI === 'cli';
date_default_timezone_set('Asia/Kolkata');

function googleAdsSyncLog(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . '/google_ads_sync.log', $line, FILE_APPEND | LOCK_EX);
    @file_put_contents(__DIR__ . '/google_ads_sync.txt', $line, FILE_APPEND | LOCK_EX);
}

googleAdsSyncLog(($isCli ? 'Cron' : 'Browser') . ' script loaded; cwd=' . getcwd());

if ($isCli) {
    putenv('SMARTRONIC_FORCE_REMOTE_DB=1');
    $_ENV['SMARTRONIC_FORCE_REMOTE_DB'] = '1';
}

register_shutdown_function(static function () use ($isCli): void {
    $error = error_get_last();
    if (!$error) return;
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int)$error['type'], $fatalTypes, true)) return;
    googleAdsSyncLog(
        ($isCli ? 'Cron' : 'Browser') . ' fatal error: '
        . ($error['message'] ?? 'Unknown error')
        . ' in ' . ($error['file'] ?? 'unknown file')
        . ':' . ($error['line'] ?? '0')
    );
});

if (!$isCli) {
    require_once __DIR__ . '/../auth.php';
    requireSmartPageAccess('gads_stats', $role, $authPages);
    header('Content-Type: text/plain; charset=UTF-8');
    if (!isset($_GET['run'])) {
        echo "Add ?run=1 to run the Google Ads sync.\n";
        exit;
    }
}
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    require_once __DIR__ . '/../config.php';
}
require_once __DIR__ . '/lib/GoogleAdsSync.php';

try {
    $runSource = $isCli ? 'Cron' : 'Browser';
    googleAdsSyncLog($runSource . ' sync started');
    $sync = new GoogleAdsSync($conn);
    $from = null;
    $to = null;

    if ($isCli) {
        $from = $argv[1] ?? null;
        $to = $argv[2] ?? null;
    } else {
        $from = isset($_GET['from']) ? trim((string)$_GET['from']) : null;
        $to = isset($_GET['to']) ? trim((string)$_GET['to']) : null;
    }

    if ($from && $to) {
        $result = $sync->syncRange($from, $to);
    } elseif (!$isCli) {
        $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata'));
        $result = $sync->syncRange($today->modify('-6 days')->format('Y-m-d'), $today->format('Y-m-d'));
    } else {
        $result = $sync->syncTodayAndYesterday();
    }

    echo "Google Ads sync complete\n";
    echo "From: {$result['from']}\n";
    echo "To: {$result['to']}\n";
    echo "Fetched: {$result['fetched']}\n";
    echo "Saved: {$result['saved']}\n";
    googleAdsSyncLog("{$runSource} sync complete from {$result['from']} to {$result['to']}; fetched {$result['fetched']}; saved {$result['saved']}");
} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    echo "Google Ads sync failed\n";
    echo $errorMessage . "\n";
    if (stripos($errorMessage, 'invalid_grant') !== false) {
        echo "Action needed: open google_ads_connect.php, clear the refresh token, then click Connect Google Ads and approve access again.\n";
    }
    googleAdsSyncLog(($isCli ? 'Cron' : 'Browser') . ' sync failed: ' . $errorMessage);
    exit(1);
}
