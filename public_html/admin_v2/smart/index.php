<?php
require_once __DIR__ . '/../auth.php';
date_default_timezone_set('Asia/Kolkata');

$roleValue = strtolower(trim((string)($role ?? ($_COOKIE['auth_role'] ?? ''))));
$displayName = trim((string)($nameAssign ?? ($_COOKIE['auth_name'] ?? $_COOKIE['auth_user'] ?? 'User')));
$isElevated = function_exists('isElevatedRole') && isElevatedRole($roleValue);
$authUserValue = strtolower(trim((string)($authUser ?? ($_COOKIE['auth_user'] ?? ''))));
$adminRoleValues = ['admin', 'administrator', 'superadmin', 'super_admin', 'owner', 'manager'];
$isAdmin = $isElevated
    || in_array($roleValue, $adminRoleValues, true)
    || (function_exists('isStrictAdminRole') && isStrictAdminRole($roleValue))
    || (function_exists('canAccessSmartPage') && canAccessSmartPage('gads_stats', $roleValue, $authPages))
    || in_array($authUserValue, ['dominic'], true);
$messagesFile = __DIR__ . '/home_messages.txt';

function home_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function renderWhatsAppPreview(string $message): string {
    $html = home_h($message);
    $html = preg_replace('/```([\s\S]*?)```/', '<code>$1</code>', $html);
    $html = preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/_([^_\n]+)_/', '<em>$1</em>', $html);
    $html = preg_replace('/~([^~\n]+)~/', '<s>$1</s>', $html);
    return nl2br($html, false);
}

function normalizeWhatsAppMessage(string $message): string {
    $message = str_replace(["\r\n", "\r"], "\n", $message);
    $message = preg_replace('/\*\*([^*\n][\s\S]*?[^*\n])\*\*/', '*$1*', $message);
    $message = preg_replace('/^\s*\*\s+/m', '- ', $message);
    return trim($message);
}

function loadHomeMessages(string $path): array {
    if (!is_file($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') return [];
    $items = json_decode($raw, true);
    if (!is_array($items)) return [];
    $clean = [];
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $id = trim((string)($item['id'] ?? ''));
        $title = trim((string)($item['title'] ?? ''));
        $message = normalizeWhatsAppMessage((string)($item['message'] ?? ''));
        if ($id === '' || ($title === '' && $message === '')) continue;
        $clean[] = [
            'id' => $id,
            'title' => $title,
            'message' => $message,
            'updated_at' => trim((string)($item['updated_at'] ?? '')),
        ];
    }
    return $clean;
}

function saveHomeMessages(string $path, array $messages): void {
    file_put_contents($path, json_encode(array_values($messages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, LOCK_EX);
}

$homeMessages = loadHomeMessages($messagesFile);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $action = trim((string)($_POST['message_action'] ?? ''));
    $id = trim((string)($_POST['message_id'] ?? ''));
    $title = trim((string)($_POST['message_title'] ?? ''));
    $message = normalizeWhatsAppMessage((string)($_POST['message_body'] ?? ''));
    $now = date('Y-m-d H:i:s');

    if ($action === 'add' && ($title !== '' || $message !== '')) {
        $homeMessages[] = [
            'id' => 'msg_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)),
            'title' => $title !== '' ? $title : 'Untitled Message',
            'message' => $message,
            'updated_at' => $now,
        ];
        saveHomeMessages($messagesFile, $homeMessages);
    } elseif ($action === 'edit' && $id !== '') {
        foreach ($homeMessages as &$item) {
            if (($item['id'] ?? '') === $id) {
                $item['title'] = $title !== '' ? $title : 'Untitled Message';
                $item['message'] = $message;
                $item['updated_at'] = $now;
                break;
            }
        }
        unset($item);
        saveHomeMessages($messagesFile, $homeMessages);
    } elseif ($action === 'delete' && $id !== '') {
        $homeMessages = array_values(array_filter($homeMessages, fn($item) => ($item['id'] ?? '') !== $id));
        saveHomeMessages($messagesFile, $homeMessages);
    }

    header('Location: /admin_v2/smart/#messages');
    exit;
}

function home_db_ready(): bool {
    global $conn;
    return isset($conn) && ($conn instanceof mysqli) && !$conn->connect_error;
}

function home_table_exists(string $table): bool {
    static $cache = [];
    global $conn;
    if (!home_db_ready()) return false;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;
    if (array_key_exists($table, $cache)) return $cache[$table];
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return $cache[$table] = ($res && $res->num_rows > 0);
}

function home_column_exists(string $table, string $column): bool {
    static $cache = [];
    global $conn;
    if (!home_db_ready()) return false;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table . $column)) return false;
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) return $cache[$key];
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '" . $conn->real_escape_string($column) . "'");
    return $cache[$key] = ($res && $res->num_rows > 0);
}

function home_money($value): string {
    if ($value === null || $value === '') return 'N/A';
    return '₹' . number_format((float)$value, 0);
}

function home_profit_parse_price($value): float {
    $n = (float)preg_replace('/[^0-9.\-]/', '', (string)($value ?? ''));
    return is_finite($n) ? $n : 0.0;
}

function home_profit_norm($value): string {
    return preg_replace('/\s+/', '', strtoupper(trim((string)($value ?? ''))));
}

function home_profit_norm_brand($value): string {
    $text = strtoupper(trim((string)($value ?? '')));
    if ($text === 'HIKVISION') return 'HIKVISION';
    if ($text === 'CPPLUS' || $text === 'CP PLUS') return 'CP PLUS';
    if ($text === 'PRAMA') return 'PRAMA';
    if ($text === 'SECUREYE') return 'SECUREYE';
    return $text !== '' ? $text : 'PRAMA';
}

function home_profit_norm_resolution($value): string {
    $text = home_profit_norm($value);
    if (preg_match('/(\d+)(MP|K)?/i', $text, $m)) {
        return $m[1] . (!empty($m[2]) ? strtoupper($m[2]) : 'MP');
    }
    return $text !== '' ? $text : '2MP';
}

function home_profit_norm_system_type($value): string {
    $text = home_profit_norm($value);
    if (strpos($text, 'NVR') !== false) return 'NVR';
    if (strpos($text, 'WIFI') !== false) return 'WIFI';
    if (strpos($text, 'WIRELESS') !== false) return 'Wireless';
    return 'DVR';
}

function home_profit_norm_cam_type($value): string {
    $text = strtolower(trim((string)($value ?? '')));
    if (strpos($text, 'hybrid') !== false || strpos($text, 'hybid') !== false) return 'hybrid';
    if (strpos($text, 'full') !== false || strpos($text, 'colour') !== false || strpos($text, 'color') !== false) return 'full';
    return 'normal';
}

function home_profit_get_channel(int $cameraCount): int {
    if ($cameraCount <= 4) return 4;
    if ($cameraCount <= 8) return 8;
    if ($cameraCount <= 16) return 16;
    return 32;
}

function home_profit_first_priced_option($options): ?array {
    foreach ((array)$options as $option) {
        if (home_profit_parse_price($option['value'] ?? 0) > 0) return $option;
    }
    return null;
}

function home_profit_pick_camera_option($options, string $camType): ?array {
    $list = array_values(array_filter((array)$options, function ($option) {
        return home_profit_parse_price($option['value'] ?? 0) > 0;
    }));
    if (!$list) return home_profit_first_priced_option($options);
    $wanted = home_profit_norm_cam_type($camType);
    usort($list, function ($a, $b) use ($wanted) {
        $score = function ($option) use ($wanted) {
            $label = strtolower((string)($option['label'] ?? ''));
            if ($wanted === 'hybrid') return (strpos($label, 'hybrid') !== false || strpos($label, 'hybid') !== false) ? 10 : 0;
            if ($wanted === 'full') return (strpos($label, 'full') !== false || strpos($label, 'colour') !== false || strpos($label, 'color') !== false) ? 10 : 0;
            if (strpos($label, 'hybrid') !== false || strpos($label, 'hybid') !== false || strpos($label, 'full') !== false || strpos($label, 'colour') !== false || strpos($label, 'color') !== false) return 0;
            if (strpos($label, 'mic') !== false) return 9;
            if (strpos($label, 'normal') !== false || strpos($label, 'nv') !== false || strpos($label, 'night') !== false) return 8;
            return 1;
        };
        return $score($b) <=> $score($a);
    });
    return $list[0] ?? null;
}

function home_profit_find_brand_node(array $data, string $systemType, string $brand): ?array {
    $typeNode = $data['Type'][$systemType] ?? null;
    if (!is_array($typeNode)) return null;
    if (isset($typeNode[$brand]) && is_array($typeNode[$brand])) return $typeNode[$brand];
    foreach ($typeNode as $key => $node) {
        if (home_profit_norm_brand($key) === $brand && is_array($node)) return $node;
    }
    return null;
}

function home_profit_pick_recorder(?array $brandNode, string $systemType, string $resolution, int $channel): ?array {
    if (!$brandNode) return null;
    $channelKey = $channel . 'CH';
    $recorderNode = $systemType === 'DVR'
        ? ($brandNode[$resolution]['Recorder'] ?? null)
        : ($brandNode['Recorder'] ?? null);
    if (!is_array($recorderNode)) return null;
    $matchedKey = null;
    foreach (array_keys($recorderNode) as $key) {
        if (home_profit_norm($key) === $channelKey) {
            $matchedKey = $key;
            break;
        }
    }
    $matchedKey = $matchedKey ?? array_key_first($recorderNode);
    return $matchedKey !== null ? home_profit_first_priced_option($recorderNode[$matchedKey] ?? []) : null;
}

function home_profit_pick_camera(array $data, ?array $brandNode, string $systemType, string $brand, string $resolution, string $camType): ?array {
    if ($brandNode) {
        if ($systemType === 'DVR') {
            return home_profit_pick_camera_option($brandNode[$resolution]['Camera'] ?? [], $camType);
        }
        if (isset($brandNode['Camera'])) {
            if (array_is_list($brandNode['Camera'])) return home_profit_pick_camera_option($brandNode['Camera'], $camType);
            $cameraNode = $brandNode['Camera'];
            $resolutionOptions = $cameraNode[$resolution] ?? $cameraNode[array_key_first($cameraNode)] ?? [];
            return home_profit_pick_camera_option($resolutionOptions, $camType);
        }
    }

    $wifiCameraNode = $data['Type']['WIFI']['Camera'] ?? null;
    if (!is_array($wifiCameraNode)) return null;
    $byResolution = $wifiCameraNode[$resolution] ?? $wifiCameraNode[array_key_first($wifiCameraNode)] ?? null;
    if (!is_array($byResolution)) return null;
    $byBrand = $byResolution[$brand] ?? $byResolution[array_key_first($byResolution)] ?? [];
    return home_profit_pick_camera_option($byBrand, $camType);
}

function home_profit_pick_hdd(array $data, string $hdd): float {
    $wanted = home_profit_norm($hdd);
    if ($wanted === '') return 0.0;
    $groups = [];
    foreach (($data['HDD'] ?? []) as $group) {
        if (is_array($group)) $groups = array_merge($groups, $group);
    }
    $exact = null;
    $preferred = null;
    foreach ($groups as $option) {
        $capacityMatches = home_profit_norm($option['capacity'] ?? '') === $wanted;
        $labelMatches = strpos(home_profit_norm($option['label'] ?? ''), $wanted) !== false;
        if (!$exact && ($capacityMatches || $labelMatches)) $exact = $option;
        if (!$preferred && $capacityMatches && strtolower((string)($option['preferred'] ?? '')) === 'true') $preferred = $option;
    }
    $option = $preferred ?: $exact;
    return is_array($option) ? home_profit_parse_price($option['value'] ?? 0) : 0.0;
}

function home_profit_item_price(array $data, string $key, float $fallback = 0): float {
    $price = home_profit_parse_price($data['items'][$key]['value'] ?? 0);
    return $price ?: $fallback;
}

function home_profit_load_snapshot(string $monthKey): array {
    static $cache = [];
    if (isset($cache[$monthKey])) return $cache[$monthKey];

    $sourcePath = __DIR__ . '/data.json';
    $snapshotDir = __DIR__ . '/data_snapshots';
    $snapshotPath = $snapshotDir . '/data_' . preg_replace('/[^0-9-]/', '', $monthKey) . '.json';
    if (!is_dir($snapshotDir)) {
        @mkdir($snapshotDir, 0755, true);
    }
    if (!is_file($snapshotPath) && is_file($sourcePath)) {
        @copy($sourcePath, $snapshotPath);
    }
    $path = is_file($snapshotPath) ? $snapshotPath : $sourcePath;
    $data = is_file($path) ? json_decode((string)file_get_contents($path), true) : [];
    return $cache[$monthKey] = is_array($data) ? $data : [];
}

function home_profit_material_cost(array $data, array $order): float {
    $cams = (int)($order['quantity'] ?? 0);
    if ($cams <= 0) {
        $cams = (int)($order['bullets'] ?? 0) + (int)($order['dome'] ?? 0);
    }
    $systemType = home_profit_norm_system_type($order['product'] ?? 'DVR');
    $brand = home_profit_norm_brand($order['brand'] ?? 'PRAMA');
    $resolution = home_profit_norm_resolution($order['resolution'] ?? '2MP');
    $camType = (string)($order['cam_type'] ?? '');
    $channel = home_profit_get_channel($cams);
    $brandNode = home_profit_find_brand_node($data, $systemType, $brand);
    $cameraOption = home_profit_pick_camera($data, $brandNode, $systemType, $brand, $resolution, $camType);
    $recorderOption = home_profit_pick_recorder($brandNode, $systemType, $resolution, $channel);
    $cameraUnitPrice = home_profit_parse_price($cameraOption['value'] ?? 0);
    $recorderPrice = home_profit_parse_price($recorderOption['value'] ?? 0);
    $hddPrice = home_profit_pick_hdd($data, (string)($order['storage'] ?? ''));
    $smpsPrice = $cams <= 4 ? home_profit_item_price($data, 'SMPS 4', 500) : home_profit_item_price($data, 'SMPS 8', 800);
    $poePrice = $cams <= 4 ? home_profit_item_price($data, 'POE 4', 1500) : ($cams <= 8 ? home_profit_item_price($data, 'POE 8', 2500) : home_profit_item_price($data, 'POE 16', 4500));
    $bncPrice = home_profit_item_price($data, 'BNC WIRED', 20);
    $dcPrice = home_profit_item_price($data, 'DC', 20);
    $backBoxPrice = home_profit_item_price($data, 'BACK BOX', 20);
    $dlinkPrice = home_profit_item_price($data, 'Dlink', 875);
    $cat6Price = home_profit_item_price($data, 'Dlink Cat 6', $dlinkPrice ?: 975);
    $accessories = $systemType === 'NVR'
        ? $poePrice + ($backBoxPrice * $cams) + ($cat6Price * ceil($cams / 4))
        : $smpsPrice + ($bncPrice * $cams * 2) + ($dcPrice * $cams) + ($backBoxPrice * $cams) + ($dlinkPrice * ceil($cams / 4));
    $subtotal = ($cameraUnitPrice * $cams) + $recorderPrice + $hddPrice + $accessories;
    return $subtotal + ($subtotal * 0.18);
}

function home_profit_total_between(string $from, string $to): float {
    global $conn;
    if (!home_db_ready() || !home_table_exists('orders')) return 0.0;
    $customerAmountSql = "CASE
        WHEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2)) > 0
        THEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2))
        ELSE CAST(REPLACE(COALESCE(NULLIF(amount_paid, ''), '0'), ',', '') AS DECIMAL(10,2))
    END";
    $where = "`date` BETWEEN ? AND ? AND ($customerAmountSql) > 0";
    if (home_column_exists('orders', 'record_status')) {
        $where .= " AND (record_status IS NULL OR record_status <> 'DELETED')";
    }
    $stmt = $conn->prepare("SELECT * FROM orders WHERE $where");
    if (!$stmt) return 0.0;
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    $profit = 0.0;
    while ($order = $res->fetch_assoc()) {
        $priceValue = home_profit_parse_price($order['price'] ?? 0);
        $amountPaidValue = home_profit_parse_price($order['amount_paid'] ?? 0);
        $customerPrice = $priceValue > 0 ? $priceValue : $amountPaidValue;
        if ($customerPrice <= 0) continue;
        $orderMonth = date('Y-m', strtotime((string)($order['date'] ?? $from)) ?: time());
        $data = home_profit_load_snapshot($orderMonth);
        $profit += $customerPrice - home_profit_material_cost($data, $order);
    }
    $stmt->close();
    return $profit;
}

function home_get_profit_summary(): string {
    $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata'));
    $thisWeekStart = $today->modify('monday this week');
    $lastWeekStart = $thisWeekStart->modify('-7 days');
    $lastWeekEnd = $thisWeekStart->modify('-1 day');
    $thisMonthStart = $today->modify('first day of this month');
    $lastMonthStart = $thisMonthStart->modify('-1 month');
    $lastMonthEnd = $thisMonthStart->modify('-1 day');

    return home_summary_chips([
        ['label' => 'Last Week', 'value' => home_money(home_profit_total_between($lastWeekStart->format('Y-m-d'), $lastWeekEnd->format('Y-m-d')))],
        ['label' => 'This Week', 'value' => home_money(home_profit_total_between($thisWeekStart->format('Y-m-d'), $today->format('Y-m-d')))],
        ['label' => 'Last Month', 'value' => home_money(home_profit_total_between($lastMonthStart->format('Y-m-01'), $lastMonthEnd->format('Y-m-d')))],
        ['label' => 'This Month', 'value' => home_money(home_profit_total_between($thisMonthStart->format('Y-m-01'), $today->format('Y-m-d')))],
    ]);
}

function home_phone_last10($value): string {
    $digits = preg_replace('/\D+/', '', (string)$value);
    if ($digits === '') return '-';
    return strlen($digits) > 10 ? substr($digits, -10) : $digits;
}

function home_number($value): string {
    return number_format((int)$value);
}

function home_initials_code(string $displayName, string $authUser): string {
    $source = trim($displayName) !== '' ? $displayName : $authUser;
    $letters = strtoupper(preg_replace('/[^a-zA-Z]/', '', $source));
    $code = $letters !== '' ? substr($letters, 0, 3) : '';
    return in_array($code, ['AMR', 'VAR', 'SUR', 'DOM', 'ZOY'], true) ? $code : '';
}

function home_summary_chips(array $chips): string {
    if (!$chips) return '';
    return '<div class="card-summary">' . implode('', array_map(static function ($chip) {
        $label = home_h($chip['label'] ?? '');
        $value = home_h($chip['value'] ?? '');
        $class = trim((string)($chip['class'] ?? ''));
        return '<span class="summary-chip ' . home_h($class) . '"><span>' . $label . '</span><strong>' . $value . '</strong></span>';
    }, $chips)) . '</div>';
}

function home_get_installs_summary(bool $isAdmin): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('orders')) return '<div class="card-summary muted-line">Install data unavailable</div>';
    $today = date('Y-m-d');
    $where = "`date` = ?";
    if (home_column_exists('orders', 'record_status')) {
        $where .= " AND (record_status IS NULL OR record_status <> 'DELETED')";
    }
    $sql = "SELECT COALESCE(NULLIF(TRIM(Owner), ''), 'Open') AS owner, COUNT(*) AS c FROM orders WHERE $where GROUP BY owner ORDER BY owner";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return '<div class="card-summary muted-line">Install data unavailable</div>';
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $owners = [];
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $owner = strtoupper(trim((string)($row['owner'] ?? 'Open')));
        $count = (int)($row['c'] ?? 0);
        $owners[] = $owner . ' ' . $count;
        $total += $count;
    }
    $stmt->close();
    $chips = [
        ['label' => 'Today', 'value' => home_number($total)],
        ['label' => 'Owners', 'value' => $owners ? implode(', ', $owners) : '-'],
    ];
    if (home_column_exists('orders', 'pending_amount')) {
        $pendingWhere = "CAST(COALESCE(pending_amount, 0) AS DECIMAL(12,2)) > 0";
        if (home_column_exists('orders', 'record_status')) {
            $pendingWhere .= " AND (record_status IS NULL OR record_status <> 'DELETED')";
        }
        $pendingRes = $conn->query("SELECT COUNT(*) AS c, SUM(CAST(COALESCE(pending_amount, 0) AS DECIMAL(12,2))) AS total FROM orders WHERE $pendingWhere");
        $pendingRow = $pendingRes ? ($pendingRes->fetch_assoc() ?: []) : [];
        $pendingCount = (int)($pendingRow['c'] ?? 0);
        $pendingTotal = (float)($pendingRow['total'] ?? 0);
        $chips[] = [
            'label' => 'Pending Payments',
            'value' => home_money($pendingTotal) . ' / ' . home_number($pendingCount) . ' pendings',
            'class' => $pendingCount > 0 ? 'danger' : '',
        ];
    }
    return home_summary_chips($chips);
}

function home_get_leads_summary(bool $isAdmin, string $userCode): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('leads')) return '<div class="card-summary muted-line">Lead data unavailable</div>';
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 00:00:00', strtotime('+1 day'));
    $hasAssign = home_column_exists('leads', 'Assign');
    $whereUser = (!$isAdmin && $userCode !== '' && $hasAssign) ? " AND UPPER(TRIM(Assign)) = ?" : '';
    $sql = "SELECT
            SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) AS today_rows
        FROM leads WHERE 1=1 $whereUser";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return '<div class="card-summary muted-line">Lead data unavailable</div>';
    if ($whereUser) $stmt->bind_param('sss', $todayStart, $todayEnd, $userCode);
    else $stmt->bind_param('ss', $todayStart, $todayEnd);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    $chips = [
        ['label' => 'Today', 'value' => home_number($row['today_rows'] ?? 0)],
    ];
    if ($isAdmin && $hasAssign) { 
        $touchedChecks = [];
        foreach (['call_status', 'comments', 'Message', 'quote', 'quote_links', 'Follow_up'] as $touchCol) {
            if (!home_column_exists('leads', $touchCol)) continue;
            $safeCol = '`' . str_replace('`', '``', $touchCol) . '`';
            $touchedChecks[] = "UPPER(TRIM(COALESCE($safeCol, ''))) NOT IN ('', '-', 'NA', 'N/A')";
        }
        $touchedExpr = $touchedChecks ? '(' . implode(' OR ', $touchedChecks) . ')' : '0';
        $res = $conn->query("SELECT COALESCE(NULLIF(UPPER(TRIM(Assign)), ''), 'OPEN') AS owner, COUNT(*) AS c, SUM(CASE WHEN $touchedExpr THEN 1 ELSE 0 END) AS touched FROM leads WHERE created_at >= '" . $conn->real_escape_string($todayStart) . "' AND created_at < '" . $conn->real_escape_string($todayEnd) . "' GROUP BY owner ORDER BY owner");
        $parts = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $parts[] = ($r['owner'] ?? 'OPEN') . ' ' . (int)($r['touched'] ?? 0) . '/' . (int)($r['c'] ?? 0);
            }
        }
        if ($parts) $chips[] = ['label' => 'By user today', 'value' => implode(', ', $parts)];
    }
    return home_summary_chips($chips);
}

function home_get_issues_summary(): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('notes')) return '<div class="card-summary muted-line">Issue data unavailable</div>';
    $today = date('Y-m-d');
    $hasDone = home_column_exists('notes', 'is_done');
    $doneCond = $hasDone ? " AND COALESCE(is_done, 0) = 0" : '';
    $stmt = $conn->prepare("SELECT COALESCE(NULLIF(TRIM(type), ''), 'General') AS type_name, COUNT(*) AS c FROM notes WHERE `date` = ? $doneCond GROUP BY type_name");
    if (!$stmt) return '<div class="card-summary muted-line">Issue data unavailable</div>';
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $counts = ['Issue' => 0, 'Inspection' => 0, 'General' => 0];
    while ($row = $res->fetch_assoc()) {
        $type = strtolower(trim((string)($row['type_name'] ?? 'General')));
        if ($type === 'issue') $counts['Issue'] += (int)$row['c'];
        elseif ($type === 'inspection') $counts['Inspection'] += (int)$row['c'];
        else $counts['General'] += (int)$row['c'];
    }
    $stmt->close();
    $total = array_sum($counts);
    return home_summary_chips([
        ['label' => 'Total', 'value' => home_number($total)],
        ['label' => 'Issues', 'value' => home_number($counts['Issue']), 'class' => 'danger'],
        ['label' => 'Inspections', 'value' => home_number($counts['Inspection']), 'class' => 'warn'],
        ['label' => 'General', 'value' => home_number($counts['General'])],
    ]);
}

function home_get_leaves_summary(): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('leaves') || !home_table_exists('users')) return '<div class="card-summary muted-line">Leave data unavailable</div>';
    $today = date('Y-m-d');
    $until = date('Y-m-d', strtotime('+10 days'));
    $stmt = $conn->prepare("SELECT l.leave_date, l.leave_type, u.username FROM leaves l INNER JOIN users u ON l.user_id = u.id WHERE l.leave_date >= ? AND l.leave_date <= ? ORDER BY l.leave_date ASC, u.username ASC LIMIT 8");
    if (!$stmt) return '<div class="card-summary muted-line">Leave data unavailable</div>';
    $stmt->bind_param('ss', $today, $until);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $date = date('d M', strtotime((string)$row['leave_date']));
        $type = (string)($row['leave_type'] ?? '');
        $items[] = strtoupper((string)$row['username']) . ' ' . $date . ($type === 'unplanned' ? ' LOP' : '');
    }
    $stmt->close();
    return home_summary_chips([
        ['label' => 'Next 10 days', 'value' => $items ? implode(', ', $items) : 'No leaves'],
    ]);
}

function home_get_google_ads_summary(): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('google_ads_campaign_stats')) {
        return '<div class="card-summary muted-line">Google Ads data unavailable</div>';
    }

    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT SUM(cost) AS spend, SUM(clicks) AS clicks, SUM(conversions) AS conversions FROM google_ads_campaign_stats WHERE report_date = ?");
    if (!$stmt) return '<div class="card-summary muted-line">Google Ads data unavailable</div>';
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $spend = (float)($row['spend'] ?? 0);
    $clicks = (int)($row['clicks'] ?? 0);
    $conversions = (float)($row['conversions'] ?? 0);
    if ($spend <= 0 && $clicks <= 0 && $conversions <= 0) {
        return '<div class="card-summary muted-line">No Google Ads data today</div>';
    }

    $secondsElapsed = max(1, time() - strtotime($today . ' 00:00:00'));
    $daySeconds = 86400;
    $projectedSpend = $secondsElapsed > 0 ? min($spend / min(1, $secondsElapsed / $daySeconds), $spend * 24) : $spend;
    $avgConversionCost = $conversions > 0 ? $spend / $conversions : null;
    $conversionRate = $clicks > 0 ? ($conversions / $clicks) * 100 : null;

    return home_summary_chips([
        ['label' => 'Cost / Projected', 'value' => home_money($spend) . ' / ' . home_money($projectedSpend)],
        ['label' => 'Clicks / Conv', 'value' => home_number($clicks) . ' / ' . number_format($conversions, 1)],
        ['label' => 'Avg Conv Cost', 'value' => $avgConversionCost !== null ? home_money($avgConversionCost) : 'N/A'],
        ['label' => 'Conv Rate', 'value' => $conversionRate !== null ? number_format($conversionRate, 1) . '%' : 'N/A'],
    ]);
}

function home_get_google_ads_stats_summary(): string {
    global $conn;
    if (!home_db_ready() || !home_table_exists('google_ads_campaign_stats')) {
        return '<div class="card-summary muted-line">Google Ads data unavailable</div>';
    }

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $sevenDaysAgo = date('Y-m-d', strtotime('-6 days'));
    $stmt = $conn->prepare("SELECT
            SUM(CASE WHEN report_date = ? THEN cost ELSE 0 END) AS today_spend,
            SUM(CASE WHEN report_date = ? THEN cost ELSE 0 END) AS yesterday_spend,
            SUM(CASE WHEN report_date >= ? AND report_date <= ? THEN cost ELSE 0 END) AS seven_day_spend
        FROM google_ads_campaign_stats
        WHERE report_date >= ? AND report_date <= ?");
    if (!$stmt) return '<div class="card-summary muted-line">Google Ads data unavailable</div>';
    $stmt->bind_param('ssssss', $today, $yesterday, $sevenDaysAgo, $today, $sevenDaysAgo, $today);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $todaySpend = (float)($row['today_spend'] ?? 0);
    $yesterdaySpend = (float)($row['yesterday_spend'] ?? 0);
    $sevenDayAverage = ((float)($row['seven_day_spend'] ?? 0)) / 7;

    return home_summary_chips([
        ['label' => 'Today Total', 'value' => home_money($todaySpend)],
        ['label' => 'Yesterday', 'value' => home_money($yesterdaySpend)],
        ['label' => 'Avg 7 Days', 'value' => home_money($sevenDayAverage)],
    ]);
}

function home_get_whatsapp_leads_summary(): string {
    $configPhp = __DIR__ . '/whatsapp_leads_config.php';
    if (!is_file($configPhp)) {
        return '<div class="card-summary muted-line">WhatsApp config unavailable</div>';
    }

    $whatsappNumber = null;
    $whatsappNumberList = [];
    $whatsappNumberLabels = [];
    $whatsappSchedule = [];
    include $configPhp;

    $todayKey = date('D');
    $mode = ((string)($whatsappNumber ?? '') === 'AUTO') ? 'Auto' : 'Fixed';
    if ((string)($whatsappNumber ?? '') === 'AUTO') {
        $activeNumber = (string)($whatsappSchedule[$todayKey] ?? ($whatsappNumberList[0] ?? ''));
    } else {
        $activeNumber = (string)($whatsappNumber ?? ($whatsappNumberList[0] ?? ''));
    }

    $activeDigits = preg_replace('/\D+/', '', $activeNumber);
    $label = $whatsappNumberLabels[$activeDigits] ?? $whatsappNumberLabels[$activeNumber] ?? 'Unassigned';

    return home_summary_chips([
        ['label' => 'Today', 'value' => (string)$label],
        ['label' => 'Number', 'value' => home_phone_last10($activeDigits)],
        ['label' => 'Mode', 'value' => $mode],
    ]);
}

$userCode = home_initials_code($displayName, $authUserValue);
$dashboardSummaries = [
    'Installs Calendar' => home_get_installs_summary($isAdmin),
    'Lead List' => home_get_leads_summary($isAdmin, $userCode),
    'Today Issues' => home_get_issues_summary(),
    'Leave Calendar' => home_get_leaves_summary(),
    'Google Ads Operations' => $isAdmin ? home_get_google_ads_summary() : '',
    'Google Ads Stats' => $isAdmin ? home_get_google_ads_stats_summary() : '',
    'WhatsApp Leads Admin' => $isAdmin ? home_get_whatsapp_leads_summary() : '',
    'Profit Summary' => $isAdmin ? home_get_profit_summary() : '',
    'Messages' => home_summary_chips([
        ['label' => 'Saved', 'value' => home_number(count($homeMessages))],
    ]),
];

$sections = [
    'Main Tools' => [
        [
            'title' => 'Installs Calendar',
            'desc' => 'Schedule installations, payments, event notes and team work.',
            'url' => '/admin_v2/smart/installs.php',
            'icon' => 'fa-solid fa-screwdriver-wrench',
            'accent' => '#2563eb',
            'allowed' => function_exists('canAccessSmartPage') && canAccessSmartPage('install', $roleValue, $authPages),
        ],
        [
            'title' => 'Quote Tool',
            'desc' => 'Create CCTV quotes and send customer-ready estimates.',
            'url' => '/admin_v2/smart/quote.php',
            'icon' => 'fa-solid fa-calculator',
            'accent' => '#7c3aed',
            'allowed' => function_exists('canAccessSmartPage') && canAccessSmartPage('quote', $roleValue, $authPages),
        ],
        [
            'title' => 'Lead List',
            'desc' => 'Track leads, call status, follow-ups and quote actions.',
            'url' => '/admin_v2/smart/lead_list_enhanced.php',
            'icon' => 'fa-solid fa-users',
            'accent' => '#e11d48',
            'allowed' => function_exists('canAccessSmartPage') && canAccessSmartPage('lead', $roleValue, $authPages),
        ],
    ],
    'Issues' => [
        [
            'title' => 'Today Issues',
            'desc' => 'Open service issues, inspections and field follow-ups.',
            'url' => '/admin_v2/smart/issues_today.php',
            'icon' => 'fa-solid fa-triangle-exclamation',
            'accent' => '#dc2626',
            'allowed' => true,
        ],
    ],
    'Profit' => [
        [
            'title' => 'Profit Summary',
            'desc' => 'Compare weekly and monthly profit using saved pricing snapshots.',
            'url' => '/admin_v2/smart/calc_profit.php',
            'icon' => 'fa-solid fa-indian-rupee-sign',
            'accent' => '#059669',
            'allowed' => $isAdmin,
        ],
    ],
    'Admin' => [
        [
            'title' => 'Google Ads Operations',
            'desc' => 'Review spend, leads, orders, profit and campaign performance.',
            'url' => '/admin_v2/smart/gads_conversion.php',
            'icon' => 'fa-solid fa-chart-line',
            'accent' => '#0f766e',
            'allowed' => $isAdmin,
        ],
        [
            'title' => 'Google Ads Stats',
            'desc' => 'Open the detailed Google Ads stats and sync dashboard.',
            'url' => '/admin_v2/smart/gads_stats.php',
            'icon' => 'fa-solid fa-bullhorn',
            'accent' => '#ca8a04',
            'allowed' => $isAdmin,
        ],
        [
            'title' => 'WhatsApp Leads Admin',
            'desc' => 'Manage active WhatsApp routing and lead recipient settings.',
            'url' => '/admin_v2/smart/whatsapp_leads_admin.php',
            'icon' => 'fa-brands fa-whatsapp',
            'accent' => '#16a34a',
            'allowed' => $isAdmin,
        ],
        [
            'title' => 'Get Reviews',
            'desc' => 'Open the review request and review management page.',
            'url' => '/admin_v2/smart/get_review.php',
            'icon' => 'fa-solid fa-star',
            'accent' => '#d97706',
            'allowed' => $isAdmin,
        ],
        [
            'title' => 'JSON Data Admin',
            'desc' => 'Manage admin JSON data and structured settings.',
            'url' => '/admin_v2/smart/json_data_admin.php',
            'icon' => 'fa-solid fa-database',
            'accent' => '#475569',
            'allowed' => $isAdmin,
        ],
    ],
    'People' => [
        [
            'title' => 'Leave Calendar',
            'desc' => 'Apply, view and manage team leave records.',
            'url' => '/admin_v2/leaves.php',
            'icon' => 'fa-solid fa-calendar-days',
            'accent' => '#f97316',
            'allowed' => true,
        ],
    ],
    'Messages' => [
        [
            'title' => 'Messages',
            'desc' => 'Open saved WhatsApp messages and copy the right reply quickly.',
            'url' => '#messages',
            'icon' => 'fa-solid fa-message',
            'accent' => '#2563eb',
            'allowed' => true,
            'modal' => 'messages',
        ],
    ],
];

$visibleSections = [];
foreach ($sections as $sectionName => $items) {
    $visibleItems = array_values(array_filter($items, fn($item) => !empty($item['allowed'])));
    if ($visibleItems) {
        $visibleSections[$sectionName] = $visibleItems;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>SM Admin | Home</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --bg: #f7f8fb;
            --panel: #ffffff;
            --text: #1f2937;
            --muted: #64748b;
            --border: #e7ebf0;
            --shadow: 0 8px 22px rgba(15, 23, 42, .05);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #f9fbff 0%, var(--bg) 58%, #ffffff 100%);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
        }
        .page-header {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 18px;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid rgba(229, 231, 235, .9);
            backdrop-filter: blur(10px);
            box-shadow: 0 3px 12px rgba(15, 23, 42, .035);
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .custom-logo {
            width: 200px;
            height: 40px;
            object-fit: contain;
            display: block;
        }
        .logo-section h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.2;
            font-weight: 600;
            color: var(--text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .logout-link {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            border: 1px solid var(--border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            background: #fff;
            text-decoration: none;
        }
        .wrap {
            width: min(1120px, calc(100% - 28px));
            margin: 0 auto;
            padding: 22px 0 44px;
        }
        .hero {
            background: rgba(255,255,255,.9);
            border: 1px solid var(--border);
            border-radius: 4px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 18px;
        }
        .hero h1 {
            margin: 0;
            font-size: clamp(26px, 5vw, 42px);
            line-height: 1.05;
            letter-spacing: 0;
            font-weight: 600;
        }
        .hero p {
            margin: 10px 0 0;
            color: var(--muted);
            font-weight: 400;
        }
        .section {
            margin-top: 20px;
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 10px;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
            gap: 14px;
        }
        .app-card {
            position: relative;
            min-height: 132px;
            display: grid;
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            align-items: start;
            padding: 16px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: rgba(255,255,255,.96);
            box-shadow: 0 7px 18px rgba(15, 23, 42, .045);
            color: inherit;
            text-decoration: none;
            text-align: left;
            font: inherit;
            cursor: pointer;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .app-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .07);
            border-color: rgba(37, 99, 235, .28);
        }
        .app-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: var(--accent);
        }
        .app-icon {
            width: 52px;
            height: 52px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 10%, #ffffff);
            font-size: 22px;
            box-shadow: none;
        }
        .app-card h3 {
            margin: 2px 0 7px;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0;
        }
        .app-card p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
            font-weight: 400;
        }
        .open-row {
            grid-column: 2;
            align-self: end;
            margin-top: 14px;
            color: var(--accent);
            font-size: 13px;
            font-weight: 600;
        }
        .card-summary {
            grid-column: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }
        .summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 25px;
            max-width: 100%;
            padding: 3px 7px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            line-height: 1.25;
        }
        .summary-chip span {
            color: #64748b;
            font-weight: 400;
        }
        .summary-chip strong {
            min-width: 0;
            overflow-wrap: anywhere;
            font-weight: 600;
        }
        .summary-chip.good { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
        .summary-chip.warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .summary-chip.danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .muted-line {
            color: var(--muted);
            font-size: 13px;
        }
        .empty {
            padding: 18px;
            border-radius: 4px;
            border: 1px solid var(--border);
            background: #fff;
            color: var(--muted);
            font-weight: 400;
        }
        .messages-panel {
            border: 1px solid var(--border);
            background: rgba(255,255,255,.96);
            box-shadow: var(--shadow);
            border-radius: 4px;
            padding: 16px;
        }
        .add-message-toggle {
            width: 54px;
            height: 54px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(37, 99, 235, .08);
        }
        .admin-add-panel {
            border: 1px solid #dbeafe;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 14px;
            width: 100%;
        }
        .admin-add-panel h3 {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 600;
            color: #1e3a8a;
        }
        .admin-add-panel p {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 13px;
        }
        .messages-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .messages-head h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 600;
        }
        .message-list {
            display: grid;
            gap: 8px;
        }
        .message-accordion {
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, .04);
            overflow: hidden;
        }
        .message-accordion summary {
            list-style: none;
            cursor: pointer;
        }
        .message-accordion summary::-webkit-details-marker {
            display: none;
        }
        .message-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 13px;
        }
        .message-summary-title {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .message-summary-title i {
            color: #64748b;
            transition: transform .18s ease;
        }
        .message-accordion[open] .message-summary-title i {
            transform: rotate(90deg);
        }
        .message-body {
            border-top: 1px solid var(--border);
            padding: 13px;
        }
        .message-title-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }
        .message-title-row h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .message-text {
            margin: 9px 0 0;
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .message-text code {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
        }
        .icon-btn, .soft-btn {
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            border-radius: 4px;
            min-height: 34px;
            cursor: pointer;
            font: inherit;
        }
        .icon-btn {
            width: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }
        .soft-btn {
            padding: 7px 10px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .admin-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .message-edit-panel[hidden] {
            display: none;
        }
        .message-form {
            display: grid;
            gap: 11px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .message-form input,
        .message-form textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 9px 10px;
            font: inherit;
            background: #fbfdff;
            color: var(--text);
        }
        .message-form textarea {
            min-height: 150px;
            resize: vertical;
        }
        .format-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .format-toolbar button {
            border: 1px solid var(--border);
            background: #f8fafc;
            color: #334155;
            border-radius: 4px;
            min-height: 36px;
            min-width: 36px;
            padding: 4px 9px;
            cursor: pointer;
            font: inherit;
            font-size: 13px;
        }
        .format-toolbar button i {
            pointer-events: none;
        }
        .form-row-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .primary-btn {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .danger-btn {
            color: #b91c1c;
        }
        details.soft-details summary {
            cursor: pointer;
            list-style: none;
        }
        details.soft-details summary::-webkit-details-marker {
            display: none;
        }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, .42);
        }
        .modal-backdrop.is-open {
            display: flex;
        }
        .messages-modal {
            width: min(980px, 100%);
            max-height: min(760px, calc(100vh - 36px));
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 4px;
            background: #fff;
            box-shadow: 0 20px 56px rgba(15, 23, 42, .24);
        }
        .messages-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            background: #f8fafc;
        }
        .messages-modal-head h2 {
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 18px;
            font-weight: 600;
        }
        .messages-modal-body {
            overflow: auto;
            padding: 16px;
        }
        @media (max-width: 680px) {
            .page-header { padding: 10px 12px; }
            .custom-logo {
                content: url('https://smartronic.online/content/uploads/2025/01/smartronic_small_logo.png');
                width: 40px;
                height: 40px;
            }
            .logo-section { gap: 9px; }
            .logo-section h2 { font-size: 15px; max-width: calc(100vw - 118px); }
            .wrap { width: min(100% - 20px, 1120px); padding-top: 14px; }
            .hero { padding: 16px; border-radius: 4px; }
            .grid { grid-template-columns: 1fr; gap: 11px; }
            .app-card { min-height: 122px; padding: 14px; grid-template-columns: 46px minmax(0, 1fr); }
            .app-icon { width: 46px; height: 46px; border-radius: 4px; font-size: 19px; }
            .app-card h3 { font-size: 16px; }
            .app-card p { font-size: 13px; }
            .messages-head { align-items: center; }
            .add-message-toggle { width: 48px; height: 48px; font-size: 22px; }
            .message-summary { padding: 12px; }
            .modal-backdrop { padding: 10px; align-items: stretch; }
            .messages-modal { max-height: calc(100vh - 20px); }
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="logo-section">
            <a href="/admin_v2/smart/" class="custom-logo-link" rel="home" aria-current="page">
                <img id="main-logo" width="200" height="40" src="https://smartronic.online/content/uploads/2025/01/smarthome-black2.svg" class="custom-logo" alt="Smartronic">
            </a>
            <h2>Hello, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>!</h2>
        </div>
        <a class="logout-link" href="/admin_v2/logout.php" aria-label="Logout" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </header>

    <main class="wrap">
        <section class="hero">
            <h1>Admin Home</h1>
            <p>Quick access to the Smartronic tools available for your login.</p>
        </section>

        <?php if (!$visibleSections): ?>
            <div class="empty">No pages are available for this login.</div>
        <?php endif; ?>

        <?php foreach ($visibleSections as $sectionName => $items): ?>
            <section class="section" aria-labelledby="section-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $sectionName)), ENT_QUOTES, 'UTF-8'); ?>">
                <h2 class="section-title" id="section-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $sectionName)), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($sectionName === 'Main Tools'): ?><i class="fa-solid fa-table-cells-large"></i><?php endif; ?>
                    <?php if ($sectionName === 'Issues'): ?><i class="fa-solid fa-circle-exclamation"></i><?php endif; ?>
                    <?php if ($sectionName === 'Profit'): ?><i class="fa-solid fa-indian-rupee-sign"></i><?php endif; ?>
                    <?php if ($sectionName === 'Admin'): ?><i class="fa-solid fa-gear"></i><?php endif; ?>
                    <?php if ($sectionName === 'People'): ?><i class="fa-solid fa-user-group"></i><?php endif; ?>
                    <?php if ($sectionName === 'Messages'): ?><i class="fa-solid fa-message"></i><?php endif; ?>
                    <?php echo htmlspecialchars($sectionName, ENT_QUOTES, 'UTF-8'); ?>
                </h2>
                <div class="grid">
                    <?php foreach ($items as $item): ?>
                        <?php
                            $isModalCard = !empty($item['modal']);
                            $tag = $isModalCard ? 'button' : 'a';
                            $summaryHtml = $dashboardSummaries[$item['title']] ?? '';
                        ?>
                        <<?php echo $tag; ?> class="app-card" <?php if ($isModalCard): ?>type="button" data-open-modal="<?php echo home_h($item['modal']); ?>"<?php else: ?>href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"<?php endif; ?> style="--accent: <?php echo htmlspecialchars($item['accent'], ENT_QUOTES, 'UTF-8'); ?>;">
                            <span class="app-icon"><i class="<?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
                            <span>
                                <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($item['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </span>
                            <?php echo $summaryHtml; ?>
                            <span class="open-row"><?php echo $isModalCard ? 'View' : 'Open'; ?> <i class="fa-solid fa-arrow-right"></i></span>
                        </<?php echo $tag; ?>>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

    </main>

    <div class="modal-backdrop" id="messagesModal" aria-hidden="true">
        <section class="messages-modal" aria-labelledby="messages-title">
            <div class="messages-head">
                <h2 id="messages-title"><i class="fa-solid fa-message"></i> Messages</h2>
                <div style="display:flex;gap:8px;align-items:center;">
                    <?php if ($isAdmin): ?>
                        <button class="add-message-toggle" type="button" aria-label="Add message" title="Add message" data-toggle-add-message>
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    <?php endif; ?>
                    <button class="icon-btn" type="button" data-close-modal aria-label="Close messages" title="Close"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="messages-modal-body">

            <?php if ($isAdmin): ?>
                <div class="admin-add-panel" id="addMessagePanel" hidden>
                    <h3>New WhatsApp Message</h3>
                    <p>Use the toolbar to insert WhatsApp formatting markers before saving.</p>
                    <form class="message-form" method="post">
                        <input type="hidden" name="message_action" value="add">
                        <input type="text" name="message_title" placeholder="Message title" required>
                        <div class="format-toolbar" data-for="add-message-body">
                            <button type="button" data-wrap="*" aria-label="Bold" title="Bold"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" data-wrap="_" aria-label="Italic" title="Italic"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" data-wrap="~" aria-label="Strike" title="Strike"><i class="fa-solid fa-strikethrough"></i></button>
                            <button type="button" data-wrap="```" aria-label="Monospace" title="Monospace"><i class="fa-solid fa-code"></i></button>
                            <button type="button" data-prefix="- " aria-label="Bullet" title="Bullet"><i class="fa-solid fa-list-ul"></i></button>
                        </div>
                        <textarea id="add-message-body" name="message_body" placeholder="Type WhatsApp message..." required></textarea>
                        <div class="form-row-actions">
                            <button class="soft-btn primary-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Message</button>
                            <button class="soft-btn" type="button" data-toggle-add-message>Cancel</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (!$homeMessages): ?>
                <div class="empty">No messages added yet.</div>
            <?php else: ?>
                <div class="message-list">
                    <?php foreach ($homeMessages as $msg): ?>
                        <?php
                            $msgId = (string)$msg['id'];
                            $textareaId = 'message-body-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $msgId);
                        ?>
                        <details class="message-accordion">
                            <summary class="message-summary">
                                <span class="message-summary-title"><i class="fa-solid fa-chevron-right"></i><?php echo home_h($msg['title']); ?></span>
                                <button class="icon-btn copy-message-btn" type="button" data-copy="<?php echo home_h($msg['message']); ?>" aria-label="Copy message" title="Copy message">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </summary>
                            <div class="message-body">
                                <div class="message-text"><?php echo renderWhatsAppPreview((string)$msg['message']); ?></div>
                                <?php if ($isAdmin): ?>
                                    <div class="admin-actions">
                                        <button class="soft-btn" type="button" data-toggle-edit="<?php echo home_h($textareaId); ?>"><i class="fa-solid fa-pen"></i> Edit</button>
                                        <form method="post" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="message_action" value="delete">
                                            <input type="hidden" name="message_id" value="<?php echo home_h($msgId); ?>">
                                            <button class="soft-btn danger-btn" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                    <div class="message-edit-panel" id="edit-panel-<?php echo home_h($textareaId); ?>" hidden>
                                        <form class="message-form" method="post">
                                            <input type="hidden" name="message_action" value="edit">
                                            <input type="hidden" name="message_id" value="<?php echo home_h($msgId); ?>">
                                            <div class="format-toolbar" data-for="<?php echo home_h($textareaId); ?>">
                                                <button type="button" data-wrap="*" aria-label="Bold" title="Bold"><i class="fa-solid fa-bold"></i></button>
                                                <button type="button" data-wrap="_" aria-label="Italic" title="Italic"><i class="fa-solid fa-italic"></i></button>
                                                <button type="button" data-wrap="~" aria-label="Strike" title="Strike"><i class="fa-solid fa-strikethrough"></i></button>
                                                <button type="button" data-wrap="```" aria-label="Monospace" title="Monospace"><i class="fa-solid fa-code"></i></button>
                                                <button type="button" data-prefix="- " aria-label="Bullet" title="Bullet"><i class="fa-solid fa-list-ul"></i></button>
                                            </div>
                                            <input type="text" name="message_title" value="<?php echo home_h($msg['title']); ?>" required>
                                            <textarea id="<?php echo home_h($textareaId); ?>" name="message_body" required><?php echo home_h($msg['message']); ?></textarea>
                                            <div class="form-row-actions">
                                                <button class="soft-btn primary-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Update</button>
                                                <button class="soft-btn" type="button" data-toggle-edit="<?php echo home_h($textareaId); ?>">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </div>
        </section>
    </div>
    <script>
        function openMessagesModal() {
            const modal = document.getElementById('messagesModal');
            if (!modal) return;
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeMessagesModal() {
            const modal = document.getElementById('messagesModal');
            if (!modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-open-modal="messages"]').forEach((button) => {
            button.addEventListener('click', openMessagesModal);
        });
        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', closeMessagesModal);
        });
        document.getElementById('messagesModal')?.addEventListener('click', (event) => {
            if (event.target && event.target.id === 'messagesModal') closeMessagesModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMessagesModal();
        });
        document.querySelectorAll('.copy-message-btn').forEach((button) => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();
                event.stopPropagation();
                const text = normalizeWhatsAppText(button.getAttribute('data-copy') || '');
                try {
                    await navigator.clipboard.writeText(text);
                    const oldHtml = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-check"></i>';
                    setTimeout(() => { button.innerHTML = oldHtml; }, 1200);
                } catch (err) {
                    const temp = document.createElement('textarea');
                    temp.value = text;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    temp.remove();
                }
            });
        });

        function normalizeWhatsAppText(text) {
            return String(text || '')
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .replace(/\*\*([^*\n][\s\S]*?[^*\n])\*\*/g, '*$1*')
                .replace(/^\s*\*\s+/gm, '- ')
                .trim();
        }

        document.querySelectorAll('[data-toggle-add-message]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = document.getElementById('addMessagePanel');
                if (!panel) return;
                panel.hidden = !panel.hidden;
                if (!panel.hidden) {
                    const titleInput = panel.querySelector('input[name="message_title"]');
                    if (titleInput) titleInput.focus();
                }
            });
        });

        document.querySelectorAll('[data-toggle-edit]').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-toggle-edit');
                const panel = document.getElementById('edit-panel-' + id);
                if (!panel) return;
                panel.hidden = !panel.hidden;
                if (!panel.hidden) {
                    const textarea = panel.querySelector('textarea');
                    if (textarea) textarea.focus();
                }
            });
        });

        function applyWhatsAppFormat(textarea, wrapper, prefix) {
            if (!textarea) return;
            textarea.focus();
            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || 0;
            const value = textarea.value;
            const selected = value.slice(start, end);
            let replacement;
            if (prefix) {
                const base = selected || 'message';
                replacement = base.split('\n').map((line) => prefix + line).join('\n');
            } else {
                const marker = wrapper || '';
                replacement = marker + (selected || 'message') + marker;
            }
            textarea.value = value.slice(0, start) + replacement + value.slice(end);
            textarea.selectionStart = start;
            textarea.selectionEnd = start + replacement.length;
        }

        document.querySelectorAll('.format-toolbar').forEach((toolbar) => {
            toolbar.addEventListener('click', (event) => {
                const button = event.target.closest('button');
                if (!button) return;
                const textarea = document.getElementById(toolbar.getAttribute('data-for'));
                applyWhatsAppFormat(textarea, button.getAttribute('data-wrap'), button.getAttribute('data-prefix'));
            });
        });
    </script>
</body>
</html>
