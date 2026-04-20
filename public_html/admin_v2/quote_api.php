<?php
header("Content-Type: application/json");

// ------------------- Helpers -------------------

$rawInput = json_decode(file_get_contents('php://input'), true);
function get_json_param($key, $default = '') {
    global $rawInput;
    return isset($rawInput[$key]) ? trim($rawInput[$key]) : $default;
}

function fallback_if_unknown(string $value, string $default): string {
    return $value === 'DONT-KNOW' ? $default : $value;
}

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
    throw new Exception("Too many cameras.");
}

// ------------------- Inputs -------------------



$whatsapp = get_json_param('whatsapp_number', '9999999999');
$numCams  = (int) get_json_param('num_cameras', 4);
$dvrType  = strtoupper(get_json_param('dvr_type', 'DVR'));
$hddSize  = strtoupper(get_json_param('hdd_size', '500GB'));
$resolution = strtoupper(get_json_param('camera_resolution', '2 MP'));
$brand = get_json_param('brand', 'PRAMA');
$camType = get_json_param('cam_type', '');

// ------------------- Pricing Configs -------------------

$hddOptions = [
    '500GB' => 1950, '1TB' => 6600, '2TB' => 6850, '4TB (2YR)' => 8000,
    '500 GB (1YR)' => 850, '1TB (1YR)' => 1950, '1TB (3YR)' => 3600,
    '2TB (1YR)' => 2850, '2TB (3YR)' => 4050, '3TB (1YR)' => 3250,
    '4TB (1YR)' => 5250, '4TB (3YR)' => 6650
];

$recorderOptions = [
    'DVR 2 MP 4CH' => 1950, 'DVR 2 MP 8CH' => 2850, 'DVR 2 MP 16CH' => 4950, 'DVR 2 MP 32CH' => 9500,
    'DVR 5 MP 4CH' => 2780, 'DVR 5 MP 8CH' => 4500, 'DVR 5 MP 16CH' => 10000, 'DVR 5 MP 32CH' => 13500,
    'NVR 4CH' => 2850, 'NVR 8CH' => 3390, 'NVR 16CH' => 4950, 'NVR 32CH' => 11050
];

$cameraOptions = [
    'DVR 2 MP' => 825, 'DVR 5 MP' => 1200, 'NVR 2 MP' => 3500, 'NVR 5 MP' => 3550
];

// ------------------- Derived Values -------------------

$channel = getChannel($numCams);
$recorderKey = $dvrType === 'DVR' ? "$dvrType $resolution {$channel}CH" : "$dvrType {$channel}CH";
$cameraKey = "$dvrType $resolution";

$cameraUnitPrice = getPriceFromOptions($cameraOptions, $cameraKey);
$cameraTotal = $cameraUnitPrice * $numCams;

$recorderPrice = getPriceFromOptions($recorderOptions, $recorderKey);
$hddPrice = getPriceFromOptions($hddOptions, $hddSize);
$poePrice = ($dvrType === 'NVR') ? calculatePoETotal($numCams) : 0;
$cablePrice = ($dvrType === 'DVR') ? 1000 : 1600;
$smpsPrice = ($dvrType === 'DVR') ? (($numCams > 8) ? 750 : 500) : 0;

// ------------------- Cost Calculation -------------------

$accessories = ($numCams * 80) + $cablePrice;
$subtotal = $hddPrice + $cameraTotal + $recorderPrice + $poePrice + $smpsPrice + $accessories;

$gst = $subtotal * 0.18;
$withTax = $subtotal + $gst;
$installCharge = 3000;
$grandTotalBeforeProfit = $withTax + $installCharge;

$profitMargin = 0.60;
$profit = $grandTotalBeforeProfit * $profitMargin;

$totalBeforeDiscount = $grandTotalBeforeProfit + $profit;
$discount = $totalBeforeDiscount * 0.20;
$finalTotal = $totalBeforeDiscount - $discount;

$perCamValue = 600;
$profitValue = 3000;

$install_cam_cost = $numCams * $perCamValue;
$install_cam_min_cost = ($install_cam_cost <= 1499) ? 1500 : $install_cam_cost;
$final_limited_profit = $withTax + $profitValue + $install_cam_min_cost;

// ------------------- Response -------------------

$response = [
    "whatsapp" => $whatsapp,
    "brand" => $brand,
    "cam_type" => $camType,
    "camera_key" => $cameraKey,
    "num_cams" => $numCams,
    "camera_unit_price" => $cameraUnitPrice,
    "camera_total" => $cameraTotal,
    "recorder_key" => $recorderKey,
    "recorder_price" => $recorderPrice,
    "hdd_size" => $hddSize,
    "hdd_price" => $hddPrice,
    "smps_price" => $smpsPrice,
    "poe_price" => $poePrice,
    "accessories" => $accessories,
    "subtotal" => $subtotal,
    "gst" => round($gst, 2),
    "install_charge" => $installCharge,
    "profit" => round($profit, 2),
    "before_discount" => round($totalBeforeDiscount),
    "discount" => round($discount),
    "final_total" => round($finalTotal),
    "install_per_cam" => $perCamValue,
    "install_cam_cost" => $install_cam_cost,
    "install_min_cost" => $install_cam_min_cost,
    "extra_profit" => $profitValue,
    "with_gst" => round($withTax, 2),
    "final_limited_profit" => round($final_limited_profit, 2)
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
