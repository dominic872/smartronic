<?php
require 'public_html/admin_v2/config.php';

function getMonthCode() {
    $date = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $month = (int) $date->format('n');
    $monthLetters = [
        1 => 'J', 2 => 'F', 3 => 'C', 4 => 'R', 5 => 'M', 6 => 'U',
        7 => 'L', 8 => 'G', 9 => 'S', 10 => 'O', 11 => 'N', 12 => 'D'
    ];
    return $monthLetters[$month] ?? 'X';
}

$conn->query("SET time_zone = '+05:30'");
$now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$year  = (int)$now->format('Y');
$month = (int)$now->format('n');

$countSql = "SELECT COUNT(*) AS cnt FROM leads WHERE YEAR(created_at) = $year AND MONTH(created_at) = $month";
$res = $conn->query($countSql);
$row = $res->fetch_assoc();
$totalRows = ((int)$row['cnt']) + 1;
$mid = getMonthCode() . '-' . $totalRows;

echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n";
echo "Year: $year, Month: $month\n";
echo "Existing Count: " . $row['cnt'] . "\n";
echo "Generated MID: $mid\n";
?>
