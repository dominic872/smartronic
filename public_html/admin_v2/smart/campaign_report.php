<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../auth.php';
if (!isset($role) || $role !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}
require_once __DIR__ . '/../config.php';

$errors = [];

$getCols = function(mysqli $conn, string $table) use (&$errors): array {
    $cols = [];
    try {
        if ($res = $conn->query("SHOW COLUMNS FROM `$table`")) {
            while ($r = $res->fetch_assoc()) {
                if (isset($r['Field'])) $cols[] = (string)$r['Field'];
            }
        }
    } catch (Throwable $e) {
        $errors[] = $table . " table error: " . $e->getMessage();
    }
    return $cols; 
};

$findCol = function(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        foreach ($cols as $existing) {
            if (strcasecmp($existing, $c) === 0) return $existing;
        }
    }
    return null;
};

$q = function(string $col): string {
    return '`' . str_replace('`', '``', $col) . '`';
};

$ordersCols = $getCols($conn, 'orders');
$leadsCols = $getCols($conn, 'leads');

$ordersIdnoCol = $findCol($ordersCols, ['idno']);
$ordersDateCol = $findCol($ordersCols, ['date', 'created_at', 'order_date', 'install_date', 'installation_date', 'scheduled_date']);

$leadsMidCol = $findCol($leadsCols, ['MID', 'mid']);
$leadsCampaignCol = $findCol($leadsCols, ['Column_1', 'coloum_1', 'column_1', 'campaign', 'campaign_name', 'utm_campaign']);
$leadsDateCol = $findCol($leadsCols, ['created_at', 'updated_at', 'date', 'createdon', 'created_on']);

$today = date('Y-m-d');
$defaultFrom = date('Y-m-01');
$defaultTo = $today;

$from = $_GET['from'] ?? $defaultFrom;
$to = $_GET['to'] ?? $defaultTo;

$dtFrom = DateTime::createFromFormat('Y-m-d', $from);
$dtTo = DateTime::createFromFormat('Y-m-d', $to);
if (!$dtFrom) $dtFrom = new DateTime($defaultFrom);
if (!$dtTo) $dtTo = new DateTime($defaultTo);

$from = $dtFrom->format('Y-m-d');
$to = $dtTo->format('Y-m-d');
if ($from > $to) {
    $tmp = $from;
    $from = $to;
    $to = $tmp;
}
if ($ordersIdnoCol === null) $errors[] = "orders.idno column not found";
if ($ordersDateCol === null) $errors[] = "orders date column not found";
if ($leadsMidCol === null) $errors[] = "leads.MID column not found";
if ($leadsCampaignCol === null) $errors[] = "leads campaign column not found";
if ($leadsDateCol === null) $errors[] = "leads date column not found";

$sortRaw = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : '';
$dirRaw = isset($_GET['dir']) ? strtolower(trim((string)$_GET['dir'])) : '';
$allowedSorts = ['campaign', 'leads', 'matched', 'conversion', 'leads_per_order'];
$sort = in_array($sortRaw, $allowedSorts, true) ? $sortRaw : 'matched';
$dir = $dirRaw === 'asc' ? 'asc' : 'desc';

$totals = [
    'orders_total' => 0,
    'leads_total' => 0,
];
$rows = [];

if (empty($errors)) {
    try {
        $stmtOrdersTotal = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE DATE(" . $q($ordersDateCol) . ") BETWEEN ? AND ?");
        if ($stmtOrdersTotal) {
            $stmtOrdersTotal->bind_param("ss", $from, $to);
            $stmtOrdersTotal->execute();
            $resOrdersTotal = $stmtOrdersTotal->get_result();
            if ($resOrdersTotal && ($r = $resOrdersTotal->fetch_assoc())) $totals['orders_total'] = (int)($r['c'] ?? 0);
        } else {
            $errors[] = "Failed to prepare orders total query";
        }

        $stmtLeadsTotal = $conn->prepare("SELECT COUNT(*) AS c FROM leads WHERE DATE(" . $q($leadsDateCol) . ") BETWEEN ? AND ?");
        if ($stmtLeadsTotal) {
            $stmtLeadsTotal->bind_param("ss", $from, $to);
            $stmtLeadsTotal->execute();
            $resLeadsTotal = $stmtLeadsTotal->get_result();
            if ($resLeadsTotal && ($r = $resLeadsTotal->fetch_assoc())) $totals['leads_total'] = (int)($r['c'] ?? 0);
        } else {
            $errors[] = "Failed to prepare leads total query";
        }

        $campaignExpr = "COALESCE(NULLIF(TRIM(l." . $q($leadsCampaignCol) . "), ''), '(blank)')";
        $joinOn =
            "CONVERT(TRIM(o." . $q($ordersIdnoCol) . ") USING utf8mb4) COLLATE utf8mb4_unicode_ci" .
            " = " .
            "CONVERT(TRIM(l." . $q($leadsMidCol) . ") USING utf8mb4) COLLATE utf8mb4_unicode_ci";

        $orderBySql = "matched_orders DESC, leads_count DESC, campaign ASC";
        if ($sort === 'campaign') {
            $orderBySql = "campaign " . strtoupper($dir) . ", matched_orders DESC, leads_count DESC";
        } else if ($sort === 'leads') {
            $orderBySql = "leads_count " . strtoupper($dir) . ", matched_orders DESC, campaign ASC";
        } else if ($sort === 'matched') {
            $orderBySql = "matched_orders " . strtoupper($dir) . ", leads_count DESC, campaign ASC";
        } else if ($sort === 'conversion') {
            $orderBySql = "conversion_pct " . strtoupper($dir) . ", matched_orders DESC, leads_count DESC, campaign ASC";
        } else if ($sort === 'leads_per_order') {
            $orderBySql = "leads_per_order " . strtoupper($dir) . ", matched_orders DESC, leads_count DESC, campaign ASC";
        }

        $sqlGrouped = "
            SELECT
                $campaignExpr AS campaign,
                COUNT(*) AS leads_count,
                COUNT(DISTINCT o." . $q($ordersIdnoCol) . ") AS matched_orders,
                (COUNT(DISTINCT o." . $q($ordersIdnoCol) . ") * 100.0 / NULLIF(COUNT(*), 0)) AS conversion_pct,
                (COUNT(*) * 1.0 / NULLIF(COUNT(DISTINCT o." . $q($ordersIdnoCol) . "), 0)) AS leads_per_order
            FROM leads l
            LEFT JOIN orders o
                ON ($joinOn)
                AND (DATE(o." . $q($ordersDateCol) . ") BETWEEN ? AND ?)
            WHERE DATE(l." . $q($leadsDateCol) . ") BETWEEN ? AND ?
            GROUP BY campaign
            ORDER BY $orderBySql
        ";

        $stmtGrouped = $conn->prepare($sqlGrouped);
        if ($stmtGrouped) {
            $stmtGrouped->bind_param("ssss", $from, $to, $from, $to);
            $stmtGrouped->execute();
            $resGrouped = $stmtGrouped->get_result();
            if ($resGrouped) {
                while ($r = $resGrouped->fetch_assoc()) {
                    $rows[] = [
                        'campaign' => (string)($r['campaign'] ?? ''),
                        'leads_count' => (int)($r['leads_count'] ?? 0),
                        'matched_orders' => (int)($r['matched_orders'] ?? 0),
                        'conversion_pct' => (float)($r['conversion_pct'] ?? 0),
                        'leads_per_order' => isset($r['leads_per_order']) ? (float)$r['leads_per_order'] : null,
                    ];
                }
            }
        } else {
            $errors[] = "Failed to prepare campaign grouped query";
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$monthButtons = [];
$base = new DateTime(date('Y-m-01'));
for ($i = 0; $i < 12; $i++) {
    $mStart = (clone $base)->modify("-$i months");
    $mEnd = (clone $mStart)->modify('last day of this month');
    $label = $mStart->format('M Y');
    $monthButtons[] = [
        'label' => $label,
        'from' => $mStart->format('Y-m-d'),
        'to' => $mEnd->format('Y-m-d'),
    ];
}
$last12Start = (clone $base)->modify('-11 months')->format('Y-m-d');
$last12To = $today;

$baseQuery = ['from' => $from, 'to' => $to];
if ($sort !== '') $baseQuery['sort'] = $sort;
if ($dir !== '') $baseQuery['dir'] = $dir;

$buildHref = function(array $overrides = []) use ($baseQuery): string {
    $q = array_merge($baseQuery, $overrides);
    return 'campaign_report.php?' . http_build_query($q);
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Report</title>
</head>
<body style="margin:0; background:#f3f4f6;">
    <div style="max-width: 1100px; margin: 16px auto; padding: 0 12px; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; color:#111827;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius: 14px; padding: 16px;">
            <div style="display:flex; gap: 10px; align-items: baseline; flex-wrap: wrap;">
                <h1 style="margin:0; font-size: 22px;">Campaign Report</h1>
                <div style="color:#6b7280; font-weight:600;">Leads MID → Orders idno</div>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="margin-top: 12px; padding: 12px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
                    <div style="font-weight:800; margin-bottom: 6px;">Report cannot run</div>
                    <div style="font-family: monospace; font-size: 12px; white-space: pre-wrap;"><?php echo htmlspecialchars(implode("\n", $errors)); ?></div>
                </div>
            <?php endif; ?>

            <form method="get" style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap; align-items:end; padding: 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff;">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; color:#374151; font-weight:700;">From</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>" style="padding:8px; border:1px solid #d1d5db; border-radius:10px;">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; color:#374151; font-weight:700;">To</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>" style="padding:8px; border:1px solid #d1d5db; border-radius:10px;">
                </div>
                <button type="submit" style="padding:10px 14px; border-radius:12px; border:1px solid #111827; background:#111827; color:#fff; font-weight:800; cursor:pointer;">Apply</button>
            </form>

            <div style="margin-top: 10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <?php
                    $isLast12Active = ($from === $last12Start && $to === $last12To);
                    $last12Href = $buildHref(['from' => $last12Start, 'to' => $last12To]);
                ?>
                <a href="<?php echo htmlspecialchars($last12Href); ?>" style="text-decoration:none; padding:8px 10px; border-radius:999px; border:1px solid <?php echo $isLast12Active ? '#111827' : '#d1d5db'; ?>; background:<?php echo $isLast12Active ? '#111827' : '#fff'; ?>; color:<?php echo $isLast12Active ? '#fff' : '#111827'; ?>; font-weight:800; font-size:12px;">Last 12 months</a>
                <?php foreach ($monthButtons as $b): ?>
                    <?php
                        $active = ($from === $b['from'] && $to === $b['to']);
                        $href = $buildHref(['from' => $b['from'], 'to' => $b['to']]);
                    ?>
                    <a href="<?php echo htmlspecialchars($href); ?>" style="text-decoration:none; padding:8px 10px; border-radius:999px; border:1px solid <?php echo $active ? '#2563eb' : '#d1d5db'; ?>; background:<?php echo $active ? '#2563eb' : '#fff'; ?>; color:<?php echo $active ? '#fff' : '#111827'; ?>; font-weight:800; font-size:12px;"><?php echo htmlspecialchars($b['label']); ?></a>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 14px; display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px;">
                <div style="padding: 12px; border:1px solid #e5e7eb; border-radius: 14px; background:#fff;">
                    <div style="font-size:12px; color:#6b7280; font-weight:800;">Orders (range)</div>
                    <div style="font-size:22px; font-weight:900; color:#111827;"><?php echo number_format((int)$totals['orders_total']); ?></div>
                </div>
                <div style="padding: 12px; border:1px solid #e5e7eb; border-radius: 14px; background:#fff;">
                    <div style="font-size:12px; color:#6b7280; font-weight:800;">Leads (range)</div>
                    <div style="font-size:22px; font-weight:900; color:#111827;"><?php echo number_format((int)$totals['leads_total']); ?></div>
                </div>
            </div>

            <div style="margin-top: 14px; overflow:auto; border:1px solid #e5e7eb; border-radius: 14px; background:#fff;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <?php
                                $nextDir = function(string $k) use ($sort, $dir): string {
                                    if ($sort !== $k) return 'desc';
                                    return $dir === 'asc' ? 'desc' : 'asc';
                                };
                                $hdr = function(string $label, string $k) use ($sort, $dir, $buildHref, $nextDir): string {
                                    $active = $sort === $k;
                                    $indicator = $active ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
                                    $href = $buildHref(['sort' => $k, 'dir' => $nextDir($k)]);
                                    $style = 'color:#111827; font-weight:900; text-decoration:none;';
                                    return '<a href="' . htmlspecialchars($href) . '" style="' . $style . '">' . htmlspecialchars($label . $indicator) . '</a>';
                                };
                            ?>
                            <th style="text-align:left; padding:12px; border-bottom:1px solid #e5e7eb; background:#f9fafb;"><?php echo $hdr('Campaign', 'campaign'); ?></th>
                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e5e7eb; background:#f9fafb;"><?php echo $hdr('Leads', 'leads'); ?></th>
                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e5e7eb; background:#f9fafb;"><?php echo $hdr('Matched Orders', 'matched'); ?></th>
                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e5e7eb; background:#f9fafb;"><?php echo $hdr('Conversion %', 'conversion'); ?></th>
                            <th style="text-align:right; padding:12px; border-bottom:1px solid #e5e7eb; background:#f9fafb;"><?php echo $hdr('Leads/Order', 'leads_per_order'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="5" style="padding: 18px; text-align:center; color:#6b7280;">No data for selected range</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; font-weight:800; color:#111827;"><?php echo htmlspecialchars($r['campaign']); ?></td>
                                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-align:right;"><?php echo number_format((int)$r['leads_count']); ?></td>
                                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-align:right; font-weight:900; color:#111827;"><?php echo number_format((int)$r['matched_orders']); ?></td>
                                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-align:right;"><?php echo htmlspecialchars(number_format((float)$r['conversion_pct'], 2)); ?></td>
                                    <td style="padding:12px; border-bottom:1px solid #f3f4f6; text-align:right;"><?php echo ($r['leads_per_order'] === null) ? '-' : htmlspecialchars(number_format((float)$r['leads_per_order'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
