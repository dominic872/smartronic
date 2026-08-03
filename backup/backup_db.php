<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');
set_time_limit(0);

$projectRoot = dirname(__DIR__);
$backupDir = $projectRoot . DIRECTORY_SEPARATOR . 'backup';
$maxAgeSeconds = 72 * 60 * 60;
$timestamp = date('Y-m-d-H-i-s');
$baseName = 'smartronic-db-' . $timestamp;
$sqlPath = $backupDir . DIRECTORY_SEPARATOR . $baseName . '.sql';
$zipPath = $backupDir . DIRECTORY_SEPARATOR . $baseName . '.zip';

function getRequestFlag(string $name): bool {
    if (isset($_GET[$name]) && (string)$_GET[$name] === '1') {
        return true;
    }

    if (PHP_SAPI === 'cli') {
        global $argv;
        foreach ((array)($argv ?? []) as $arg) {
            $value = trim((string)$arg);
            if ($value === $name . '=1' || $value === '--' . $name || $value === '--' . $name . '=1') {
                return true;
            }
        }
    }

    return false;
}

$diagnoseOnly = getRequestFlag('diagnose');
$verifyOnly = getRequestFlag('verify');

function isWebRequest(): bool {
    return PHP_SAPI !== 'cli';
}

function renderPage(string $title, string $message, string $details = '', int $statusCode = 200): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $safeDetails = $details !== '' ? nl2br(htmlspecialchars($details, ENT_QUOTES, 'UTF-8')) : '';

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $safeTitle . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#1f2937;margin:0;padding:24px;} .card{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 10px 25px rgba(15,23,42,.08);padding:24px;} h1{margin:0 0 10px;font-size:24px;} .status{display:inline-block;background:#fee2e2;color:#991b1b;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700;margin-bottom:12px;} pre{white-space:pre-wrap;word-break:break-word;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:14px;overflow:auto;} code{background:#f3f4f6;padding:2px 4px;border-radius:4px;}</style>';
    echo '</head><body><div class="card"><div class="status">Backup error</div><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>';
    if ($safeDetails !== '') {
        echo '<pre>' . $safeDetails . '</pre>';
    }
    echo '</div></body></html>';
}

function renderDiagnostics(array $items): void {
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Backup diagnostics</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#1f2937;margin:0;padding:24px;} .card{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 10px 25px rgba(15,23,42,.08);padding:24px;} h1{margin:0 0 10px;font-size:24px;} table{width:100%;border-collapse:collapse;margin-top:16px;} th,td{padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top;} th{background:#f9fafb;} .ok{color:#166534;font-weight:700;} .bad{color:#b91c1c;font-weight:700;} code,pre{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:2px 4px;} pre{white-space:pre-wrap;word-break:break-word;padding:12px;} .small{color:#6b7280;font-size:13px;}</style>';
    echo '</head><body><div class="card"><h1>Backup diagnostics</h1><p class="small">Use this page to see what the host allows. Green means the capability is available. Red means the provider is blocking it or the path is not writable.</p><table><thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';

    foreach ($items as $item) {
        $label = htmlspecialchars((string)($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ok = !empty($item['ok']);
        $status = $ok ? '<span class="ok">OK</span>' : '<span class="bad">Blocked</span>';
        $details = htmlspecialchars((string)($item['details'] ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<tr><td>' . $label . '</td><td>' . $status . '</td><td><pre>' . $details . '</pre></td></tr>';
    }

    echo '</tbody></table></div></body></html>';
}

function fail(string $message, int $code = 1, string $details = ''): void {
    if (isWebRequest()) {
        renderPage('Backup failed', $message, $details, 500);
        exit($code);
    }
    fwrite(STDERR, $message . PHP_EOL);
    if ($details !== '') {
        fwrite(STDERR, $details . PHP_EOL);
    }
    exit($code);
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($error['type'] ?? 0, $fatalTypes, true)) {
        return;
    }

    $message = sprintf(
        '%s in %s on line %d',
        (string)($error['message'] ?? 'Unknown fatal error'),
        (string)($error['file'] ?? 'unknown file'),
        (int)($error['line'] ?? 0)
    );

    if (isWebRequest()) {
        renderPage('Backup failed', 'A fatal error occurred while running the backup script.', $message, 500);
    } else {
        fwrite(STDERR, $message . PHP_EOL);
    }
});

function ensureBackupDir(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        fail('Unable to create backup directory: ' . $dir);
    }
    if (!is_writable($dir)) {
        fail('Backup directory is not writable: ' . $dir);
    }
}

function sqlIdentifier(string $name): string { 
    return '`' . str_replace('`', '``', $name) . '`';
}

function sqlValue(mysqli $conn, $value): string {
    if ($value === null) { 
        return 'NULL';
    }
    return "'" . $conn->real_escape_string((string)$value) . "'";
}

function writeLine($handle, string $line = ''): void {
    fwrite($handle, $line . PHP_EOL);
}

function firstRowValue(?array $row): string {
    if (!$row) {
        return '';
    }
    foreach ($row as $value) {
        return (string)$value;
    }
    return '';
}

function dumpTableData(mysqli $conn, $handle, string $table): void {
    $tableSql = sqlIdentifier($table);
    $result = $conn->query('SELECT * FROM ' . $tableSql);
    if (!$result || $result->num_rows === 0) {
        return;
    }

    $fields = $result->fetch_fields();
    $columnNames = [];
    foreach ($fields as $field) {
        $columnNames[] = sqlIdentifier($field->name);
    }

    $insertPrefix = 'INSERT INTO ' . $tableSql . ' (' . implode(', ', $columnNames) . ') VALUES';
    $batch = [];

    while ($row = $result->fetch_assoc()) {
        $values = [];
        foreach ($fields as $field) {
            $values[] = sqlValue($conn, $row[$field->name] ?? null);
        }
        $batch[] = '(' . implode(', ', $values) . ')';

        if (count($batch) >= 100) {
            writeLine($handle, $insertPrefix);
            writeLine($handle, implode(',' . PHP_EOL, $batch) . ';');
            writeLine($handle);
            $batch = [];
        }
    }

    if ($batch) {
        writeLine($handle, $insertPrefix);
        writeLine($handle, implode(',' . PHP_EOL, $batch) . ';');
        writeLine($handle);
    }
}

function dumpDatabase(mysqli $conn, string $sqlPath): void {
    $handle = fopen($sqlPath, 'wb');
    if ($handle === false) {
        fail('Unable to open SQL output file: ' . $sqlPath);
    }

    $databaseName = '';
    $dbNameResult = $conn->query('SELECT DATABASE()');
    if ($dbNameResult) {
        $dbNameRow = $dbNameResult->fetch_row();
        $databaseName = (string)($dbNameRow[0] ?? '');
    }

    writeLine($handle, '-- Smartronic database backup');
    writeLine($handle, '-- Generated: ' . date('Y-m-d H:i:s'));
    writeLine($handle);
    writeLine($handle, 'SET NAMES utf8mb4;');
    writeLine($handle, 'SET FOREIGN_KEY_CHECKS=0;');
    if ($databaseName !== '') {
        writeLine($handle, 'CREATE DATABASE IF NOT EXISTS ' . sqlIdentifier($databaseName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
        writeLine($handle, 'USE ' . sqlIdentifier($databaseName) . ';');
    }
    writeLine($handle);

    $tablesResult = $conn->query('SHOW FULL TABLES');
    if (!$tablesResult) {
        fclose($handle);
        fail('Unable to list database tables: ' . $conn->error);
    }

    while ($tableRow = $tablesResult->fetch_array(MYSQLI_NUM)) {
        $tableName = (string)($tableRow[0] ?? '');
        $tableType = strtoupper((string)($tableRow[1] ?? 'BASE TABLE'));
        if ($tableName === '') {
            continue;
        }

        $tableSql = sqlIdentifier($tableName);

        if ($tableType === 'VIEW') {
            $createView = $conn->query('SHOW CREATE VIEW ' . $tableSql);
            if ($createView) {
                $viewRow = $createView->fetch_assoc();
                $viewSql = $viewRow['Create View'] ?? firstRowValue($viewRow);
                if ($viewSql !== '') {
                    writeLine($handle, 'DROP VIEW IF EXISTS ' . $tableSql . ';');
                    writeLine($handle, $viewSql . ';');
                    writeLine($handle);
                }
            }
            continue;
        }

        $createTable = $conn->query('SHOW CREATE TABLE ' . $tableSql);
        if (!$createTable) {
            fclose($handle);
            fail('Unable to read table definition for ' . $tableName . ': ' . $conn->error);
        }

        $createRow = $createTable->fetch_assoc();
        $createSql = $createRow['Create Table'] ?? firstRowValue($createRow);
        if ($createSql === '') {
            fclose($handle);
            fail('Empty CREATE TABLE statement for ' . $tableName);
        }

        writeLine($handle, 'DROP TABLE IF EXISTS ' . $tableSql . ';');
        writeLine($handle, $createSql . ';');
        writeLine($handle);
        dumpTableData($conn, $handle, $tableName);
    }

    writeLine($handle, 'SET FOREIGN_KEY_CHECKS=1;');
    fclose($handle);
}

function zipSqlFile(string $sqlPath, string $zipPath): void {
    if (!class_exists(ZipArchive::class)) {
        fail('ZipArchive is not available on this hosting account.');
    }

    $zip = new ZipArchive();
    $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($result !== true) {
        fail('Unable to create ZIP archive: ' . $zipPath . ' (code ' . (string)$result . ')');
    }

    $zip->addFile($sqlPath, basename($sqlPath));
    $zip->close();
}

function cleanupOldBackups(string $dir, int $maxAgeSeconds): int {
    $deleted = 0;
    $cutoff = time() - $maxAgeSeconds;
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.zip') ?: [] as $file) {
        if (is_file($file) && filemtime($file) !== false && filemtime($file) < $cutoff) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
    }
    return $deleted;
}

function makeDiagnostics(string $projectRoot, string $backupDir): array {
    $configPath = resolveConfigPath($projectRoot);
    $checks = [];

    $checks[] = [
        'label' => 'Backup folder exists',
        'ok' => is_dir($backupDir),
        'details' => $backupDir,
    ];
    $checks[] = [
        'label' => 'Backup folder writable',
        'ok' => is_writable($backupDir),
        'details' => is_dir($backupDir) ? (is_writable($backupDir) ? 'Writable' : 'Not writable') : 'Folder missing',
    ];
    $checks[] = [
        'label' => 'Config path exists',
        'ok' => is_file($configPath),
        'details' => $configPath,
    ];
    $checks[] = [
        'label' => 'ZipArchive available',
        'ok' => class_exists(ZipArchive::class),
        'details' => class_exists(ZipArchive::class) ? 'ZipArchive class is available' : 'ZipArchive class is missing or blocked',
    ];
    $checks[] = [
        'label' => 'exec available',
        'ok' => true,
        'details' => 'Not required. This backup script uses pure PHP and ZipArchive, not exec().',
    ];
    $checks[] = [
        'label' => 'Open basedir',
        'ok' => (string)ini_get('open_basedir') === '' || strpos($backupDir, (string)ini_get('open_basedir')) !== false,
        'details' => (string)ini_get('open_basedir'),
    ];
    $checks[] = [
        'label' => 'PHP SAPI',
        'ok' => true,
        'details' => PHP_SAPI,
    ];
    $checks[] = [
        'label' => 'Disable functions',
        'ok' => true,
        'details' => (string)ini_get('disable_functions'),
    ];
    $checks[] = [
        'label' => 'SMARTRONIC_FORCE_REMOTE_DB',
        'ok' => getenv('SMARTRONIC_FORCE_REMOTE_DB') === '1',
        'details' => getenv('SMARTRONIC_FORCE_REMOTE_DB') === '1' ? 'Enabled' : 'Disabled',
    ];
    $checks[] = [
        'label' => 'SMARTRONIC_BACKUP_MODE',
        'ok' => getenv('SMARTRONIC_BACKUP_MODE') === '1',
        'details' => getenv('SMARTRONIC_BACKUP_MODE') === '1' ? 'Enabled' : 'Disabled',
    ];

    return $checks;
}

function validateSqlDump(string $sqlPath): array {
    if (!is_file($sqlPath)) {
        return ['ok' => false, 'details' => 'SQL file was not created: ' . $sqlPath];
    }

    $size = filesize($sqlPath);
    $contents = file_get_contents($sqlPath);
    if ($contents === false) {
        return ['ok' => false, 'details' => 'Unable to read SQL file: ' . $sqlPath];
    }

    $checks = [
        'SET NAMES utf8mb4;' => stripos($contents, 'SET NAMES utf8mb4;') !== false,
        'FOREIGN_KEY_CHECKS disabled' => stripos($contents, 'SET FOREIGN_KEY_CHECKS=0;') !== false,
        'FOREIGN_KEY_CHECKS enabled' => stripos($contents, 'SET FOREIGN_KEY_CHECKS=1;') !== false,
        'CREATE DATABASE or USE' => stripos($contents, 'USE ') !== false || stripos($contents, 'CREATE DATABASE IF NOT EXISTS') !== false,
        'Has schema statements' => stripos($contents, 'DROP TABLE IF EXISTS') !== false || stripos($contents, 'DROP VIEW IF EXISTS') !== false,
    ];

    $missing = [];
    foreach ($checks as $label => $passed) {
        if (!$passed) {
            $missing[] = $label;
        }
    }

    return [
        'ok' => empty($missing),
        'details' => 'File: ' . $sqlPath . "\nSize: " . number_format((int)$size) . " bytes\nMissing checks: " . (empty($missing) ? 'none' : implode(', ', $missing)),
    ];
}

function resolveConfigPath(string $projectRoot): string {
    $override = trim((string)getenv('SMARTRONIC_CONFIG_PATH'));
    if ($override !== '' && is_file($override)) {
        return $override;
    }

    $candidates = [
        $projectRoot . '/admin_v2/config.php',
        $projectRoot . '/public_html/admin_v2/config.php',
        dirname($projectRoot) . '/public_html/admin_v2/config.php',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
}

putenv('SMARTRONIC_FORCE_REMOTE_DB=1');
putenv('SMARTRONIC_BACKUP_MODE=1');

if ($diagnoseOnly) {
    renderDiagnostics(makeDiagnostics($projectRoot, $backupDir));
    exit;
}

try {
    $configPath = resolveConfigPath($projectRoot);
    if (!is_file($configPath)) {
        fail(
            'Could not locate config.php.',
            1,
            "Tried:\n- " . implode("\n- ", [
                $projectRoot . '/admin_v2/config.php',
                $projectRoot . '/public_html/admin_v2/config.php',
                dirname($projectRoot) . '/public_html/admin_v2/config.php',
            ]) . "\n\nYou can also set SMARTRONIC_CONFIG_PATH to the exact config.php path."
        );
    }

    require_once $configPath;

    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        fail('Database connection failed.', 1, isset($conn) && $conn instanceof mysqli ? $conn->connect_error : 'No mysqli connection available.');
    }

    $conn->set_charset('utf8mb4');

    ensureBackupDir($backupDir);
    dumpDatabase($conn, $sqlPath);
    zipSqlFile($sqlPath, $zipPath);
    $sqlValidation = validateSqlDump($sqlPath);
    if (!$verifyOnly) {
        @unlink($sqlPath);
    }
    $deleted = cleanupOldBackups($backupDir, $maxAgeSeconds);

    if (isWebRequest()) {
        $details = "Backup file: {$zipPath}\nOld backups deleted: {$deleted}\n\nSQL verify: " . ($sqlValidation['ok'] ? 'PASS' : 'FAIL') . "\n" . ($sqlValidation['details'] ?? '');
        if ($verifyOnly) {
            $details .= "\n\nSQL file was kept because verify=1 was used.";
        }
        renderPage('Backup complete', 'Backup created successfully.', $details, 200);
    } else {
        echo 'Backup created: ' . $zipPath . PHP_EOL;
        echo 'Old backups deleted: ' . $deleted . PHP_EOL;
        echo 'SQL verify: ' . ($sqlValidation['ok'] ? 'PASS' : 'FAIL') . PHP_EOL;
        echo $sqlValidation['details'] . PHP_EOL;
    }
} catch (Throwable $e) {
    fail('Backup script failed.', 1, $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
}
