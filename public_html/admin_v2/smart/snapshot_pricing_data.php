<?php
$isCli = PHP_SAPI === 'cli';
date_default_timezone_set('Asia/Kolkata');

function pricingSnapshotLog(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . '/pricing_snapshot.log', $line, FILE_APPEND | LOCK_EX);
    @file_put_contents(__DIR__ . '/pricing_snapshot.txt', $line, FILE_APPEND | LOCK_EX);
}

function pricingSnapshotFail(string $message, int $status = 500): void
{
    if (PHP_SAPI !== 'cli') {
        http_response_code($status);
    }
    pricingSnapshotLog('Failed: ' . $message);
    echo "Pricing snapshot failed\n";
    echo $message . "\n";
    exit(1);
}

function pricingSnapshotMonth(?string $month): string
{
    $month = trim((string)$month);
    if ($month === '') {
        return date('Y-m');
    }
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        pricingSnapshotFail('Invalid month. Use YYYY-MM, for example 2026-07.', 400);
    }
    $dt = DateTime::createFromFormat('!Y-m', $month);
    if (!$dt || $dt->format('Y-m') !== $month) {
        pricingSnapshotFail('Invalid month value.', 400);
    }
    return $month;
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error) return;
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int)$error['type'], $fatalTypes, true)) return;
    pricingSnapshotLog(
        'Fatal error: '
        . ($error['message'] ?? 'Unknown error')
        . ' in ' . ($error['file'] ?? 'unknown file')
        . ':' . ($error['line'] ?? '0')
    );
});

if (!$isCli) {
    require_once __DIR__ . '/../auth.php';
    if (!isset($role) || strtolower(trim((string)$role)) !== 'admin') {
        echo 'No access';
        exit;
    }
    header('Content-Type: text/plain; charset=UTF-8');
    if (!isset($_GET['run'])) {
        echo "Add ?run=1 to create the pricing snapshot.\n";
        echo "Optional: &month=YYYY-MM and &force=1\n";
        exit;
    }
}

$month = $isCli ? ($argv[1] ?? null) : ($_GET['month'] ?? null);
$force = $isCli
    ? in_array('--force', $argv ?? [], true)
    : (isset($_GET['force']) && $_GET['force'] === '1');

$monthKey = pricingSnapshotMonth($month);
$sourcePath = __DIR__ . '/data.json';
$snapshotDir = __DIR__ . '/data_snapshots';
$snapshotPath = $snapshotDir . '/data_' . $monthKey . '.json';

pricingSnapshotLog(($isCli ? 'Cron' : 'Browser') . " requested month {$monthKey}; cwd=" . getcwd());

if (!is_file($sourcePath)) {
    pricingSnapshotFail('Source data.json was not found.');
}

$sourceJson = file_get_contents($sourcePath);
if ($sourceJson === false || trim($sourceJson) === '') {
    pricingSnapshotFail('Source data.json is empty or unreadable.');
}

$decoded = json_decode($sourceJson, true);
if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
    pricingSnapshotFail('Source data.json is not valid JSON: ' . json_last_error_msg());
}

if (!is_dir($snapshotDir) && !@mkdir($snapshotDir, 0755, true)) {
    pricingSnapshotFail('Could not create data_snapshots directory.');
}

if (is_file($snapshotPath) && !$force) {
    pricingSnapshotLog("Snapshot already exists for {$monthKey}: {$snapshotPath}");
    echo "Pricing snapshot already exists\n";
    echo "Month: {$monthKey}\n";
    echo "File: {$snapshotPath}\n";
    echo "No overwrite done. Use --force or force=1 only if you intentionally want to replace it.\n";
    exit;
}

$prettyJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($prettyJson === false) {
    pricingSnapshotFail('Could not encode snapshot JSON: ' . json_last_error_msg());
}

if (@file_put_contents($snapshotPath, $prettyJson . PHP_EOL, LOCK_EX) === false) {
    pricingSnapshotFail('Could not write snapshot file.');
}

pricingSnapshotLog(($force ? 'Replaced' : 'Created') . " snapshot for {$monthKey}: {$snapshotPath}");

echo ($force ? "Pricing snapshot replaced\n" : "Pricing snapshot created\n");
echo "Month: {$monthKey}\n";
echo "Source: {$sourcePath}\n";
echo "File: {$snapshotPath}\n";
echo "Size: " . number_format((int)filesize($snapshotPath)) . " bytes\n";
