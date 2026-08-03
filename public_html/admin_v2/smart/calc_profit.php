<?php
ob_start();
require_once '../auth.php'; // Assuming we create auth.php in admin folder
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config.php'; // contains $mysqli = new mysqli(...);
$error = '';

// Initialize PDO connection at the top
try {
    $pdo = new PDO("mysql:host=127.0.0.1:3306;dbname=u398852039_smartronic", "u398852039_smartronic", "Chennai@40!");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<strong>Database connection failed:</strong> " . $e->getMessage());
}

// Get monthly order counts for overview
$monthlyStats = [];
for ($i = -5; $i <= 0; $i++) {
    $monthStart = date('Y-m-01', strtotime("$i months"));
    $monthEnd = $i === 0 ? date('Y-m-d') : date('Y-m-t', strtotime("$i months"));
    $monthName = date('M', strtotime("$i months"));
    
    $customerAmountSql = "CASE
                    WHEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2)) > 0
                    THEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2))
                    ELSE CAST(REPLACE(COALESCE(NULLIF(amount_paid, ''), '0'), ',', '') AS DECIMAL(10,2))
                 END";
    $sqlCount = "SELECT COUNT(*) as count, SUM($customerAmountSql) as total_paid
                 FROM orders 
                 WHERE date BETWEEN :from AND :to 
                 AND ($customerAmountSql) > 0
                 AND (record_status IS NULL OR record_status != 'DELETED')";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute(['from' => $monthStart, 'to' => $monthEnd]);
    $result = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
    $monthlyStats[] = [
        'name' => $monthName,
        'count' => $result['count'] ?? 0,
        'total_paid' => $result['total_paid'] ?? 0
    ];
}

echo '
<div class="monthly-overview">';

foreach ($monthlyStats as $stat) {
    echo '<div class="month-stat">
            <div class="month-name">' . $stat['name'] . '</div>
            <div class="month-count">' . $stat['count'] . '</div>
            <div class="month-label">₹' . number_format($stat['total_paid']) . '</div>
          </div>';
}

echo '</div>

';
// Ensure error reporting won't expose sensitive data in production
error_reporting(0);

// Check if the cookie exists and has the right value
if (!isset($_COOKIE['auth_role']) || $_COOKIE['auth_role'] !== 'admin') {
    echo "No access";
    exit; // Stop processing the rest of the page
}

// If the user passes the check, the rest of your page code runs below...


// Set timezone to India
date_default_timezone_set('Asia/Kolkata');

echo '<!DOCTYPE html>
<html>
<head>
    <title>SM Profit | Calculator</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fa;
            padding: 30px;
        }
        h2 {
            color: #333;
        }
        form {
            margin-bottom: 30px;
        }
        input[type="date"], button {
            padding: 10px;
            margin-right: 10px;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .quick-links {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .quick-links a {
            display: inline-block;
            padding: 10px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .quick-links a:hover {
            background: #218838;
        }
        .quick-links a.active {
            background: #007bff;
        }
        .quick-links select {
            padding: 10px 15px;
            font-size: 16px;
            border: 2px solid #28a745;
            border-radius: 5px;
            background: white;
            color: #333;
            cursor: pointer;
            margin-left: 10px;
        }
        .summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 40px;
            justify-content: start;
            font-size: 18px;
        }
        .monthly-overview {
          

            padding: 10px;

            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: space-around;
        }
        .monthly-overview .month-stat {
            text-align: center;
          
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            min-width: 70px;
        }
        .monthly-overview .month-stat .month-name {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .monthly-overview .month-stat .month-count {
            font-size: 22px;
            font-weight: 700;
        }
        .monthly-overview .month-stat .month-label {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
        }
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }

        .card.WIFI {
            background:rgb(255, 209, 209);
            
        }
        .card.NVR {
            background:rgb(238, 255, 209);
            
        }
        .card h4 {
            margin-top: 0;
            color: #007bff;
            display: inline-block;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
        }
        .label.profit {
            display: inline-block;
            background-color: rgb(124 202 124);
            color: #ffffff;
            padding: 0px 4px;
            border-radius: 2px;
            font-weight: 400;
            line-height: 1.2;
        }
    </style>
</head>
<body>
';

// Calculate date ranges for last 6 months
$currentMonthFrom = date('Y-m-01');
$currentMonthTo = date('Y-m-d');

// Generate last 6 months data (from oldest to newest)
$months = [];
for ($i = -5; $i <= 0; $i++) {
    $monthStart = date('Y-m-01', strtotime("$i months"));
    $monthEnd = $i === 0 ? date('Y-m-d') : date('Y-m-t', strtotime("$i months"));
    $monthName = date('F Y', strtotime("$i months")) . ($i === 0 ? ' so far' : '');
    $monthShort = date('M', strtotime("$i months")) . ($i === 0 ? '*' : '');
    $months[] = [
        'name' => $monthName,
        'short' => $monthShort,
        'from' => $monthStart,
        'to' => $monthEnd
    ];
}

$currentFrom = $_GET['from'] ?? '';
$currentTo = $_GET['to'] ?? '';

// Check if current selection matches
$isCurrentMonth = ($currentFrom === $currentMonthFrom && $currentTo === $currentMonthTo);
$isLast3Months = ($currentFrom === $last3MonthsFrom && $currentTo === $last3MonthsTo);

echo '
<h2>Profit Calculator</h2>

<div class="quick-links">';

// Display individual month links
foreach ($months as $month) {
    $isActive = ($currentFrom === $month['from'] && $currentTo === $month['to']);
    $activeClass = $isActive ? 'active' : '';
    echo '<a href="?from=' . $month['from'] . '&to=' . $month['to'] . '" class="' . $activeClass . '">' . 
         $month['short'] . '</a>';
}

echo '
    <select onchange="if(this.value) window.location.href=this.value;" style="vertical-align: middle;">
        <option value="">Select Month...</option>';
        
foreach ($months as $month) {
    $isSelected = ($currentFrom === $month['from'] && $currentTo === $month['to']) ? 'selected' : '';
    echo '<option value="?from=' . $month['from'] . '&to=' . $month['to'] . '" ' . $isSelected . '>' . 
         $month['name'] . '</option>';
}

echo '
    </select>
</div>

<form method="GET">
    <label>From: <input type="date" name="from" value="' . ($_GET['from'] ?? '') . '" required></label>
    <label>To: <input type="date" name="to" value="' . ($_GET['to'] ?? '') . '" required></label>
    <button type="submit">Calculate Profit</button>
</form>
';

if (isset($_GET['from']) && isset($_GET['to'])) {
    $fromDate = $_GET['from'];
    $toDate = $_GET['to'];

    function profitParsePrice($value): float {
        $n = (float)preg_replace('/[^0-9.\-]/', '', (string)($value ?? ''));
        return is_finite($n) ? $n : 0.0;
    }

    function profitNorm($value): string {
        return preg_replace('/\s+/', '', strtoupper(trim((string)($value ?? ''))));
    }

    function profitNormBrand($value): string {
        $text = strtoupper(trim((string)($value ?? '')));
        if ($text === 'HIKVISION') return 'HIKVISION';
        if ($text === 'CPPLUS' || $text === 'CP PLUS') return 'CP PLUS';
        if ($text === 'PRAMA') return 'PRAMA';
        if ($text === 'SECUREYE') return 'SECUREYE';
        return $text !== '' ? $text : 'PRAMA';
    }

    function profitNormResolution($value): string {
        $text = profitNorm($value);
        if (preg_match('/(\d+)(MP|K)?/i', $text, $m)) {
            return $m[1] . (!empty($m[2]) ? strtoupper($m[2]) : 'MP');
        }
        return $text !== '' ? $text : '2MP';
    }

    function profitNormSystemType($value): string {
        $text = profitNorm($value);
        if (strpos($text, 'NVR') !== false) return 'NVR';
        if (strpos($text, 'WIFI') !== false) return 'WIFI';
        if (strpos($text, 'WIRELESS') !== false) return 'Wireless';
        return 'DVR';
    }

    function profitNormCamType($value): string {
        $text = strtolower(trim((string)($value ?? '')));
        if (strpos($text, 'hybrid') !== false || strpos($text, 'hybid') !== false) return 'hybrid';
        if (strpos($text, 'full') !== false || strpos($text, 'colour') !== false || strpos($text, 'color') !== false) return 'full';
        return 'normal';
    }

    function profitGetChannel(int $cameraCount): int {
        if ($cameraCount <= 4) return 4;
        if ($cameraCount <= 8) return 8;
        if ($cameraCount <= 16) return 16;
        return 32;
    }

    function profitFirstPricedOption($options): ?array {
        foreach ((array)$options as $option) {
            if (profitParsePrice($option['value'] ?? 0) > 0) return $option;
        }
        return null;
    }

    function profitPickCameraOption($options, string $camType): ?array {
        $list = array_values(array_filter((array)$options, function ($option) {
            return profitParsePrice($option['value'] ?? 0) > 0;
        }));
        if (!$list) return profitFirstPricedOption($options);
        $wanted = profitNormCamType($camType);
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

    function profitFindBrandNode(array $data, string $systemType, string $brand): ?array {
        $typeNode = $data['Type'][$systemType] ?? null;
        if (!is_array($typeNode)) return null;
        if (isset($typeNode[$brand]) && is_array($typeNode[$brand])) return $typeNode[$brand];
        foreach ($typeNode as $key => $node) {
            if (profitNormBrand($key) === $brand && is_array($node)) return $node;
        }
        return null;
    }

    function profitPickRecorder(?array $brandNode, string $systemType, string $resolution, int $channel): ?array {
        $channelKey = $channel . 'CH';
        $recorderNode = null;
        if ($systemType === 'DVR') {
            $recorderNode = $brandNode[$resolution]['Recorder'] ?? null;
        } else {
            $recorderNode = $brandNode['Recorder'] ?? null;
        }
        if (!is_array($recorderNode)) return null;
        $matchedKey = null;
        foreach (array_keys($recorderNode) as $key) {
            if (profitNorm($key) === $channelKey) {
                $matchedKey = $key;
                break;
            }
        }
        $matchedKey = $matchedKey ?? array_key_first($recorderNode);
        return $matchedKey !== null ? profitFirstPricedOption($recorderNode[$matchedKey] ?? []) : null;
    }

    function profitPickCamera(array $data, ?array $brandNode, string $systemType, string $brand, string $resolution, string $camType): ?array {
        if ($brandNode) {
            if ($systemType === 'DVR') {
                return profitPickCameraOption($brandNode[$resolution]['Camera'] ?? [], $camType);
            }
            if (isset($brandNode['Camera'])) {
                if (array_is_list($brandNode['Camera'])) return profitPickCameraOption($brandNode['Camera'], $camType);
                $cameraNode = $brandNode['Camera'];
                $resolutionOptions = $cameraNode[$resolution] ?? $cameraNode[array_key_first($cameraNode)] ?? [];
                return profitPickCameraOption($resolutionOptions, $camType);
            }
        }

        $wifiCameraNode = $data['Type']['WIFI']['Camera'] ?? null;
        if (!is_array($wifiCameraNode)) return null;
        $byResolution = $wifiCameraNode[$resolution] ?? $wifiCameraNode[array_key_first($wifiCameraNode)] ?? null;
        if (!is_array($byResolution)) return null;
        $byBrand = $byResolution[$brand] ?? $byResolution[array_key_first($byResolution)] ?? [];
        return profitPickCameraOption($byBrand, $camType);
    }

    function profitPickHdd(array $data, string $hdd): array {
        $wanted = profitNorm($hdd);
        if ($wanted === '') return ['option' => null, 'price' => 0.0];
        $groups = [];
        foreach (($data['HDD'] ?? []) as $group) {
            if (is_array($group)) $groups = array_merge($groups, $group);
        }
        $exact = null;
        $preferred = null;
        foreach ($groups as $option) {
            $capacityMatches = profitNorm($option['capacity'] ?? '') === $wanted;
            $labelMatches = strpos(profitNorm($option['label'] ?? ''), $wanted) !== false;
            if (!$exact && ($capacityMatches || $labelMatches)) $exact = $option;
            if (!$preferred && $capacityMatches && strtolower((string)($option['preferred'] ?? '')) === 'true') $preferred = $option;
        }
        $option = $preferred ?: $exact;
        return ['option' => $option, 'price' => profitParsePrice($option['value'] ?? 0)];
    }

    function profitItemPrice(array $data, string $key, float $fallback = 0): float {
        $price = profitParsePrice($data['items'][$key]['value'] ?? 0);
        return $price ?: $fallback;
    }

    function calculateProfitMaterialFromData(array $data, array $order): array {
        $cams = (int)($order['quantity'] ?? 0);
        if ($cams <= 0) {
            $cams = (int)($order['bullets'] ?? 0) + (int)($order['dome'] ?? 0);
        }
        $systemType = profitNormSystemType($order['product'] ?? 'DVR');
        $brand = profitNormBrand($order['brand'] ?? 'PRAMA');
        $resolution = profitNormResolution($order['resolution'] ?? '2MP');
        $camType = (string)($order['cam_type'] ?? '');
        $hdd = (string)($order['storage'] ?? '');
        $channel = profitGetChannel($cams);
        $missing = [];

        $brandNode = profitFindBrandNode($data, $systemType, $brand);
        if (!$brandNode) $missing[] = "$systemType $brand";

        $cameraOption = profitPickCamera($data, $brandNode, $systemType, $brand, $resolution, $camType);
        $recorderOption = profitPickRecorder($brandNode, $systemType, $resolution, $channel);
        $hddResult = profitPickHdd($data, $hdd);

        $cameraUnitPrice = profitParsePrice($cameraOption['value'] ?? 0);
        $recorderPrice = profitParsePrice($recorderOption['value'] ?? 0);
        $hddPrice = $hddResult['price'];

        if (!$cameraUnitPrice) $missing[] = "$brand $resolution " . ($camType ?: 'camera');
        if (!$recorderPrice && $systemType !== 'WIFI' && $systemType !== 'Wireless') $missing[] = "$brand {$channel}CH recorder";
        if ($hdd !== '' && !$hddPrice) $missing[] = "$hdd HDD";

        $smpsPrice = $cams <= 4 ? profitItemPrice($data, 'SMPS 4', 500) : profitItemPrice($data, 'SMPS 8', 800);
        $poePrice = $cams <= 4 ? profitItemPrice($data, 'POE 4', 1500) : ($cams <= 8 ? profitItemPrice($data, 'POE 8', 2500) : profitItemPrice($data, 'POE 16', 4500));
        $bncPrice = profitItemPrice($data, 'BNC WIRED', 20);
        $dcPrice = profitItemPrice($data, 'DC', 20);
        $backBoxPrice = profitItemPrice($data, 'BACK BOX', 20);
        $dlinkPrice = profitItemPrice($data, 'Dlink', 875);
        $cat6Price = profitItemPrice($data, 'Dlink Cat 6', $dlinkPrice ?: 975);

        $accessories = $systemType === 'NVR'
            ? $poePrice + ($backBoxPrice * $cams) + ($cat6Price * ceil($cams / 4))
            : $smpsPrice + ($bncPrice * $cams * 2) + ($dcPrice * $cams) + ($backBoxPrice * $cams) + ($dlinkPrice * ceil($cams / 4));

        $cameraTotal = $cameraUnitPrice * $cams;
        $subtotal = $cameraTotal + $recorderPrice + $hddPrice + $accessories;
        $gst = $subtotal * 0.18;

        return [
            'systemType' => $systemType,
            'brand' => $brand,
            'resolution' => $resolution,
            'camType' => $camType,
            'cams' => $cams,
            'channel' => $channel,
            'cameraLabel' => $cameraOption['label'] ?? '',
            'cameraUnitPrice' => $cameraUnitPrice,
            'cameraTotal' => $cameraTotal,
            'recorderLabel' => $recorderOption['label'] ?? '',
            'recorderPrice' => $recorderPrice,
            'hddLabel' => $hddResult['option']['label'] ?? '',
            'hddPrice' => $hddPrice,
            'accessories' => $accessories,
            'subtotal' => $subtotal,
            'gst' => $gst,
            'materialCost' => $subtotal + $gst,
            'missing' => $missing,
        ];
    }

    function profitGetOrderMonthKey($date): string {
        $ts = strtotime((string)$date);
        return $ts ? date('Y-m', $ts) : date('Y-m');
    }

    function profitLoadPricingSnapshot(string $monthKey): array {
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
        if (!is_array($data)) $data = [];

        $cache[$monthKey] = [
            'data' => $data,
            'path' => $path,
            'label' => basename($path)
        ];
        return $cache[$monthKey];
    }

    $snapshotNotice = "<div style='background:#eef6ff;border:1px solid #bfdbfe;color:#1e3a8a;padding:10px 12px;border-radius:6px;margin-bottom:14px;font-size:14px;'>Pricing source: monthly snapshots in <strong>data_snapshots</strong>. If a month has no snapshot yet, it is created from the current data.json the first time this report runs.</div>";
    echo $snapshotNotice;

    $customerAmountSql = "CASE
            WHEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2)) > 0
            THEN CAST(REPLACE(COALESCE(NULLIF(price, ''), '0'), ',', '') AS DECIMAL(10,2))
            ELSE CAST(REPLACE(COALESCE(NULLIF(amount_paid, ''), '0'), ',', '') AS DECIMAL(10,2))
        END";
    $sql = "SELECT * 
            FROM orders 
            WHERE date BETWEEN :from AND :to 
            AND ($customerAmountSql) > 0
            AND (record_status IS NULL OR record_status != 'DELETED')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalOrders = 0;
    $totalPaid = 0;
    $totalProfit = 0;
    
    $orderCards = ''; // Store order cards HTML

    foreach ($orders as $order) {
        $priceValue = profitParsePrice($order['price'] ?? 0);
        $amountPaidValue = profitParsePrice($order['amount_paid'] ?? 0);
        $customerPrice = $priceValue > 0 ? $priceValue : $amountPaidValue;
        if ($customerPrice <= 0) {
            continue;
        }

        $monthKey = profitGetOrderMonthKey($order['date'] ?? '');
        $pricingSnapshot = profitLoadPricingSnapshot($monthKey);
        $breakup = calculateProfitMaterialFromData($pricingSnapshot['data'], $order);
        $numCams = $breakup['cams'];
        $dvrType = $breakup['systemType'];
        $hddSize = strtoupper(trim((string)$order['storage']));
        $resolution = $breakup['resolution'];
        $vendorCost = $breakup['materialCost'];
        $profit = $customerPrice - $vendorCost;
        $missingHtml = '';
        if (!empty($breakup['missing'])) {
            $missingHtml = "<div style='color:#b91c1c;font-size:13px;margin-top:6px;'><span class='label'>Missing data.json pricing:</span> " . htmlspecialchars(implode(', ', $breakup['missing'])) . "</div>";
        }
       

        $totalOrders++;
        $totalPaid += $customerPrice;
        $totalProfit += $profit;

        $orderCards .= "<div class='card $dvrType'>";
        $orderCards .= "<h4>{$order['name']} ({$order['idno']})</h4> ";
        $orderCards .= " <span class='profit label'> ₹" . number_format($profit) . "</span>";
        $orderCards .= "<div><span class='label'>Phone:</span> {$order['phone']} | <span class='label'>Area:</span> {$order['area']} | <span class='label'>Owner:</span> {$order['Owner']}</div>";
        $orderCards .= "<div><span class='label'>Product:</span> $dvrType | <span class='label'>Brand:</span> {$breakup['brand']} | <span class='label'>Resolution:</span> $resolution | <span class='label'>Storage:</span> $hddSize</div>";
        $orderCards .= "<div><span class='label'>Quantity:</span> $numCams | <span class='label'>Paid:</span> ₹" . number_format($customerPrice) . " <span style='color:#6b7280;font-size:13px;'>(" . ($priceValue > 0 ? 'actual amount' : 'amount paid') . ")</span></div>";
        $orderCards .= "<div><span class='label'>Vendor Cost:</span> ₹" . number_format($vendorCost) . " <span style='color:#6b7280;font-size:13px;'>(Camera ₹" . number_format($breakup['cameraTotal']) . " + Recorder ₹" . number_format($breakup['recorderPrice']) . " + HDD ₹" . number_format($breakup['hddPrice']) . " + Accessories ₹" . number_format($breakup['accessories']) . " + GST ₹" . number_format($breakup['gst']) . ")</span></div>";
        $orderCards .= "<div style='color:#6b7280;font-size:12px;margin-top:4px;'><span class='label'>Pricing snapshot:</span> " . htmlspecialchars($pricingSnapshot['label']) . "</div>";
        $orderCards .= $missingHtml;
        $orderCards .= "</div>";
    }
    
    // Display summary at the top
    if ($totalOrders > 0) {
        echo "<div class='summary'>
            <div>🧾 <strong>Total Orders:</strong> $totalOrders</div>
            <div>💰 <strong>Total Paid:</strong> ₹" . number_format($totalPaid) . "</div>
            <div>📈 <strong>Total Profit:</strong> ₹" . number_format($totalProfit) . "</div>
        </div>";
        
        // Display order cards
        echo $orderCards;
    } else {
        echo "<p>No orders found in this date range.</p>";
    }
}

echo '</body></html>';
