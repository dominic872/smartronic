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
    $monthEnd = date('Y-m-t', strtotime("$i months"));
    $monthName = date('M', strtotime("$i months"));
    
    $sqlCount = "SELECT COUNT(*) as count, SUM(CAST(REPLACE(price, ',', '') AS DECIMAL(10,2))) as total_paid 
                 FROM orders 
                 WHERE date BETWEEN :from AND :to 
                 AND price IS NOT NULL 
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
    <title>Profit Calculator</title>
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
$currentMonthTo = date('Y-m-t');

// Generate last 6 months data (from oldest to newest)
$months = [];
for ($i = -5; $i <= 0; $i++) {
    $monthStart = date('Y-m-01', strtotime("$i months"));
    $monthEnd = date('Y-m-t', strtotime("$i months"));
    $monthName = date('F Y', strtotime("$i months"));
    $monthShort = date('M', strtotime("$i months"));
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

    function getPriceFromOptions(array $options, string $key): int {
        return $options[$key] ?? 0;
    }

    function calculatePoETotal(int $cameraCount): int {
        $prices = [8 => 1125, 4 => 825];
        $total = 0;
        $remaining = $cameraCount;
        while ($remaining > 8) {
            $total += $prices[8];
            $remaining -= 8;
        }
        if ($remaining > 0) {
            $total += $prices[($remaining <= 4 || $remaining === 4 || $remaining === 8) ? min($remaining, 4) : 8];
        }
        return $total;
    }

    function getChannel(int $numCameras): int {
        foreach ([4, 8, 16, 32] as $ch) {
            if ($numCameras <= $ch) return $ch;
        }
        return 32;
    }

    $hddOptions = [
        '500GB' => 1550, '1TB' => 2750, '2TB' => 3650, '4TB (2YR)' => 5250,
        '500 GB (1YR)' => 850, '1TB (1YR)' => 1950, '1TB (3YR)' => 3600,
        '2TB (1YR)' => 2850, '2TB (3YR)' => 4050, '3TB (1YR)' => 3250,
        '4TB (1YR)' => 5250, '4TB (3YR)' => 6650
    ];

    $recorderOptions = [
        'DVR 2 MP 4CH' => 1950, 'DVR 2 MP 8CH' => 2850, 'DVR 2 MP 16CH' => 4950, 'DVR 2 MP 32CH' => 17750,
        'DVR 5 MP 4CH' => 2780, 'DVR 5 MP 8CH' => 4500, 'DVR 5 MP 16CH' => 10000, 'DVR 5 MP 32CH' => 21500,
        'NVR 4CH' => 2850, 'NVR 8CH' => 3390, 'NVR 16CH' => 4950, 'NVR 32CH' => 11050
    ];

    $cameraOptions = [
        'DVR 2 MP' => 825, 'DVR 5 MP' => 1150, 'NVR 2 MP' => 2500, 'NVR 5 MP' => 2650
    ];

    $sql = "SELECT * 
            FROM orders 
            WHERE date BETWEEN :from AND :to 
            AND price IS NOT NULL 
            AND (record_status IS NULL OR record_status != 'DELETED')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['from' => $fromDate, 'to' => $toDate]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalOrders = 0;
    $totalPaid = 0;
    $totalProfit = 0;
    
    $orderCards = ''; // Store order cards HTML

    foreach ($orders as $order) {
        $numCams = (int) $order['quantity'];
        $dvrType = strtoupper(trim($order['product']));
        $hddSize = strtoupper(trim($order['storage']));
        $resolution = strtoupper(trim($order['resolution']));
        $customerPrice = floatval(str_replace(',', '', $order['price']));

        $channel = getChannel($numCams);
        $recorderKey = $dvrType === 'DVR' ? "$dvrType $resolution {$channel}CH" : "$dvrType {$channel}CH";
        $cameraKey = "$dvrType $resolution";

        $cameraUnitPrice = getPriceFromOptions($cameraOptions, $cameraKey);
        $cameraTotal = $cameraUnitPrice * $numCams;
        $recorderPrice = getPriceFromOptions($recorderOptions, $recorderKey);
        $hddPrice = getPriceFromOptions($hddOptions, $hddSize);
        $poePrice = ($dvrType === 'NVR') ? calculatePoETotal($numCams) : 0;
        $cablePrice = ($dvrType === 'DVR') ? 1000 : 1600;
        $smpsPrice = ($dvrType === 'DVR') ? (($numCams > 8) ? 750 : 400) : 0;
        $accessories = ($numCams * 80) + $cablePrice;

        $subtotal = $hddPrice + $cameraTotal + $recorderPrice + $poePrice + $smpsPrice + $accessories;
        $gst = $subtotal * 0.18;
        $vendorCost = $subtotal + $gst;
        if ($dvrType != 'WIFI') {
             $profit = $customerPrice - $vendorCost;
        } else {
             $profit = $customerPrice * .33;
        }
       

        $totalOrders++;
        $totalPaid += $customerPrice;
        $totalProfit += $profit;

        $orderCards .= "<div class='card $dvrType'>";
        $orderCards .= "<h4>{$order['name']} ({$order['idno']})</h4> ";
        $orderCards .= " <span class='profit label'> ₹" . number_format($profit) . "</span>";
        $orderCards .= "<div><span class='label'>Phone:</span> {$order['phone']} | <span class='label'>Area:</span> {$order['area']} | <span class='label'>Owner:</span> {$order['Owner']}</div>";
        $orderCards .= "<div><span class='label'>Product:</span> $dvrType | <span class='label'>Resolution:</span> $resolution | <span class='label'>Storage:</span> $hddSize</div>";
        $orderCards .= "<div><span class='label'>Quantity:</span> $numCams | <span class='label'>Paid:</span> ₹" . number_format($customerPrice) . "</div>";
        $orderCards .= "<div><span class='label'>Vendor Cost:</span> ₹" . number_format($vendorCost) . "</div>";
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
