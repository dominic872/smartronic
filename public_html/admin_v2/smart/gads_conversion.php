<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/lib/GoogleAdsSync.php';
require_once __DIR__ . '/lib/CampaignIdMap.php';
require_once __DIR__ . '/config/google_ads_dashboard.php';

requireSmartPageAccess('gads_stats', $role, $authPages);

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    require_once __DIR__ . '/../config.php';
}

date_default_timezone_set('Asia/Kolkata');

function gc_column_exists(mysqli $conn, string $table, string $column): bool
{
    if (!in_array($table, ['leads', 'google_ads_campaign_stats'], true)) return false;
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

function gc_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gc_money($value): string
{
    return '₹' . number_format((float)$value, 0);
}

function gc_metric_money($value): string
{
    return $value === null ? 'N/A' : gc_money($value);
}

function gc_metric_percent($value): string
{
    return $value === null ? 'N/A' : number_format((float)$value, 1) . '%';
}

function gc_metric_number($value, int $decimals = 0): string
{
    return $value === null ? 'N/A' : number_format((float)$value, $decimals);
}

function gc_campaign_key($value): string
{
    $value = strtolower(trim((string)$value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?: 'unknown';
}

function gc_bind_params(mysqli_stmt $stmt, string $types, array &$params): bool
{
    $refs = [$types];
    foreach ($params as $key => &$value) {
        $refs[] = &$value;
    }
    unset($value);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function gc_parse_date(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') return null;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Kolkata'));
    return $date instanceof DateTimeImmutable ? $date : null;
}

function gc_period_bounds(string $period, string $customFrom, string $customTo): array
{
    $today = new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata'));
    $tomorrow = $today->modify('+1 day');

    if ($period === 'custom') {
        $from = gc_parse_date($customFrom);
        $to = gc_parse_date($customTo);
        if ($from && $to && $from <= $to) {
            return [$from, $to->modify('+1 day'), 'Custom Range'];
        }
        return [$today->modify('-6 days'), $tomorrow, 'Last 7 Days'];
    }

    switch ($period) {
        case 'today':
            return [$today, $tomorrow, 'Today'];
        case 'yesterday':
            return [$today->modify('-1 day'), $today, 'Yesterday'];
        case 'last_10_days':
            return [$today->modify('-9 days'), $tomorrow, 'Last 10 Days'];
        case 'last_30_days':
            return [$today->modify('-29 days'), $tomorrow, 'Last 30 Days'];
        case 'last_2_months':
            return [$today->modify('-2 months'), $tomorrow, 'Last 2 Months'];
        case 'last_3_months':
            return [$today->modify('-3 months'), $tomorrow, 'Last 3 Months'];
        case 'last_6_months':
            return [$today->modify('-6 months'), $tomorrow, 'Last 6 Months'];
        case 'this_month':
            return [$today->modify('first day of this month'), $tomorrow, 'This Month'];
        case 'last_7_days':
        default:
            return [$today->modify('-6 days'), $tomorrow, 'Last 7 Days'];
    }
}

function gc_recommendation(array $row): string
{
    $spend = (float)$row['spend'];
    $leads = (int)$row['leads'];
    $ordered = (int)$row['ordered'];
    $rate = $row['order_rate'];
    $cpo = $row['cost_per_order'];
    $clicks = (int)$row['clicks'];

    if ($clicks >= HIGH_CLICKS_LOW_LEADS_THRESHOLD && $leads <= LOW_LEADS_THRESHOLD) return 'Check Search Terms';
    if ($leads >= HIGH_LEADS_LOW_ORDER_THRESHOLD && ($ordered === 0 || ($rate !== null && $rate < GOOD_MIN_ORDER_RATE))) return 'Improve Lead Quality';
    if ($spend >= ACTION_SPEND_THRESHOLD && $leads === 0 && $ordered === 0) return 'Pause / Review';
    if ($spend >= ACTION_SPEND_THRESHOLD && $leads > 0 && $ordered === 0) return 'Reduce Budget';
    if ($spend < ACTION_SPEND_THRESHOLD && $ordered === 0) return 'Watch Today';
    if ($cpo !== null && $cpo <= LOW_COST_PER_ORDER && $ordered >= GOOD_MIN_ORDERS) return 'Increase Budget';
    if ($ordered > 0 && $cpo !== null && $cpo <= HIGH_COST_PER_ORDER) return 'Continue';
    return 'Review';
}

function gc_campaign_status(array $row): array
{
    $spend = (float)$row['spend'];
    $leads = (int)$row['leads'];
    $ordered = (int)$row['ordered'];
    $rate = $row['order_rate'];
    $cpo = $row['cost_per_order'];

    if ($spend <= 0 && $leads === 0) return ['No Data', 'status-none'];
    if ($spend >= ACTION_SPEND_THRESHOLD && $ordered === 0) return ['Action Required', 'status-action'];
    if ($leads > 0 && $ordered === 0 && $spend >= WATCH_SPEND_THRESHOLD) return ['Watch', 'status-watch'];
    if ($ordered >= GOOD_MIN_ORDERS && $cpo !== null && $cpo <= HIGH_COST_PER_ORDER && $rate !== null && $rate >= GOOD_MIN_ORDER_RATE) {
        return ['Good', 'status-good'];
    }
    return ['Watch', 'status-watch'];
}

function gc_pct_change(?float $current, ?float $previous): ?float
{
    if ($previous === null || abs($previous) < 0.00001) return null;
    return (($current ?? 0) - $previous) / $previous * 100;
}

function gc_change_text(?float $change, bool $lowerIsGood = false): array
{
    if ($change === null) return ['N/A', 'change-flat'];
    $arrow = $change > 0 ? '↑' : ($change < 0 ? '↓' : '→');
    $class = abs($change) < 0.1 ? 'change-flat' : (($change > 0 xor $lowerIsGood) ? 'change-good' : 'change-bad');
    return [$arrow . ' ' . number_format(abs($change), 0) . '%', $class];
}

function gc_health_score(array $row): int
{
    $score = 45;
    $ordered = (int)$row['ordered'];
    $leads = (int)$row['leads'];
    $spend = (float)$row['spend'];
    $orderRate = $row['order_rate'];
    $cpo = $row['cost_per_order'];
    $ctr = $row['ctr'];
    $avgCpc = $row['avg_cpc'];

    if ($ordered > 0) $score += 14;
    if ($ordered >= GOOD_MIN_ORDERS) $score += 10;
    if ($orderRate !== null && $orderRate >= GOOD_MIN_ORDER_RATE) $score += 16;
    elseif ($orderRate !== null && $orderRate > 0) $score += 7;
    if ($cpo !== null && $cpo <= LOW_COST_PER_ORDER) $score += 16;
    elseif ($cpo !== null && $cpo <= HIGH_COST_PER_ORDER) $score += 8;
    elseif ($cpo !== null) $score -= 10;
    if ($spend >= ACTION_SPEND_THRESHOLD && $ordered === 0) $score -= 24;
    elseif ($spend >= WATCH_SPEND_THRESHOLD && $ordered === 0) $score -= 12;
    if ($ctr !== null && $ctr >= GOOD_CTR) $score += 6;
    elseif ($ctr !== null && $ctr < 1) $score -= 5;
    if ($avgCpc !== null && $avgCpc > HIGH_AVG_CPC) $score -= 6;
    if ($leads >= 5) $score += 5;
    elseif ($spend > 0 && $leads === 0) $score -= 10;

    return max(0, min(100, $score));
}

function gc_health_label(int $score): array
{
    if ($score >= GOOD_HEALTH_SCORE) return ['Excellent', 'health-good'];
    if ($score >= WATCH_HEALTH_SCORE) return ['Healthy', 'health-mid'];
    if ($score >= 35) return ['Needs Attention', 'health-bad'];
    return ['Critical', 'health-critical'];
}

function gc_priority(array $row): array
{
    $recommendation = (string)($row['recommendation'] ?? '');
    $health = (int)($row['health_score'] ?? 0);
    if (in_array($recommendation, ['Pause / Review', 'Reduce Budget'], true) || $health < 35) {
        return ['High', 1, 'priority-high'];
    }
    if (in_array($recommendation, ['Improve Lead Quality', 'Check Search Terms', 'Watch Today'], true) || $health < WATCH_HEALTH_SCORE) {
        return ['Medium', 2, 'priority-medium'];
    }
    if ($recommendation === 'Increase Budget') {
        return ['Opportunity', 3, 'priority-good'];
    }
    return ['Review', 4, 'priority-review'];
}

function gc_row_trend(array $days): array
{
    $count = count($days);
    if ($count < 2) return ['→ Stable', 'trend-flat'];
    $half = max(1, (int)floor($count / 2));
    $first = array_slice($days, 0, $half);
    $second = array_slice($days, -$half);
    $firstOrders = array_sum(array_map(fn($d) => (int)($d['orders'] ?? 0), $first));
    $secondOrders = array_sum(array_map(fn($d) => (int)($d['orders'] ?? 0), $second));
    $firstLeads = array_sum(array_map(fn($d) => (int)($d['leads'] ?? 0), $first));
    $secondLeads = array_sum(array_map(fn($d) => (int)($d['leads'] ?? 0), $second));
    $firstRate = $firstLeads > 0 ? $firstOrders / $firstLeads : 0;
    $secondRate = $secondLeads > 0 ? $secondOrders / $secondLeads : 0;
    if ($secondOrders > $firstOrders || $secondRate > ($firstRate * 1.15)) return ['↑ Improving', 'trend-good'];
    if ($secondOrders < $firstOrders || ($firstRate > 0 && $secondRate < ($firstRate * 0.85))) return ['↓ Declining', 'trend-bad'];
    return ['→ Stable', 'trend-flat'];
}

function gc_fetch_summary(mysqli $conn, DateTimeImmutable $start, DateTimeImmutable $end, ?string $statusCol, array $campaignMap, array $mappedKeys, string $campaignFilter): array
{
    $summary = ['spend' => 0.0, 'leads' => 0, 'ordered' => 0];
    $startDay = $start->format('Y-m-d');
    $endDay = $end->format('Y-m-d');
    $stmt = $conn->prepare("SELECT campaign_id, COALESCE(NULLIF(TRIM(campaign_name), ''), 'Unknown') AS campaign_name, SUM(cost) AS spend FROM google_ads_campaign_stats WHERE report_date >= ? AND report_date < ? GROUP BY campaign_id, campaign_name");
    if ($stmt) {
        $stmt->bind_param('ss', $startDay, $endDay);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $campaignId = preg_replace('/\D+/', '', (string)($row['campaign_id'] ?? ''));
            $display = ($campaignId !== '' && isset($campaignMap[$campaignId])) ? $campaignMap[$campaignId] : (string)($row['campaign_name'] ?? 'Unknown');
            if ($campaignFilter !== '' && $display !== $campaignFilter) continue;
            $summary['spend'] += (float)($row['spend'] ?? 0);
        }
        $stmt->close();
    }
    $orderedExpr = $statusCol
        ? "SUM(CASE WHEN LOWER(TRIM(l.`$statusCol`)) IN ('ordered', 'converted', 'booked') OR LOWER(TRIM(l.`$statusCol`)) LIKE '%ordered%' THEN 1 ELSE 0 END)"
        : "0";
    $stmt = $conn->prepare("SELECT COALESCE(NULLIF(TRIM(l.`Column_1`), ''), 'Unknown') AS campaign, COUNT(*) AS leads_count, $orderedExpr AS ordered_count FROM leads l WHERE l.created_at >= ? AND l.created_at < ? GROUP BY campaign");
    if ($stmt) {
        $startSql = $start->format('Y-m-d H:i:s');
        $endSql = $end->format('Y-m-d H:i:s');
        $stmt->bind_param('ss', $startSql, $endSql);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $display = (string)($row['campaign'] ?? 'Unknown');
            $key = gc_campaign_key($display);
            if (!isset($mappedKeys[$key])) continue;
            if ($campaignFilter !== '' && $display !== $campaignFilter) continue;
            $summary['leads'] += (int)($row['leads_count'] ?? 0);
            $summary['ordered'] += (int)($row['ordered_count'] ?? 0);
        }
        $stmt->close();
    }
    return $summary;
}

function generateCeoInsight(array $summaryData, array $campaignData): array
{
    $spend = (float)($summaryData['spend'] ?? 0);
    $leads = (int)($summaryData['leads'] ?? 0);
    $ordered = (int)($summaryData['ordered'] ?? 0);
    $orderRate = $summaryData['order_rate'] ?? null;
    $cpl = $summaryData['cost_per_lead'] ?? null;
    $cpo = $summaryData['cost_per_order'] ?? null;

    if ($spend <= 0 && $leads === 0 && $ordered === 0) {
        return [
            'overall_status' => 'NEEDS ATTENTION',
            'headline' => 'Not enough data available yet to generate CEO Insight.',
            'insight_text' => 'Not enough data available yet to generate CEO Insight.',
            'recommended_action' => 'Run or verify the Google Ads sync, then review again once leads and spend are available.',
            'severity' => 'danger',
        ];
    }

    $bestByOrders = null;
    $bestByCpo = null;
    $worstNoOrders = null;
    $needsAttention = [];

    foreach ($campaignData as $row) {
        if ($bestByOrders === null || (int)$row['ordered'] > (int)$bestByOrders['ordered']) {
            $bestByOrders = $row;
        }
        if ((int)$row['ordered'] > 0 && $row['cost_per_order'] !== null && ($bestByCpo === null || (float)$row['cost_per_order'] < (float)$bestByCpo['cost_per_order'])) {
            $bestByCpo = $row;
        }
        if ((int)$row['ordered'] === 0 && (float)$row['spend'] > 0 && ($worstNoOrders === null || (float)$row['spend'] > (float)$worstNoOrders['spend'])) {
            $worstNoOrders = $row;
        }
        if (in_array((string)($row['priority'] ?? ''), ['High', 'Medium'], true)) {
            $needsAttention[] = $row;
        }
    }

    if ($ordered >= GOOD_MIN_ORDERS && $cpo !== null && $cpo <= HIGH_COST_PER_ORDER && $orderRate !== null && $orderRate >= GOOD_MIN_ORDER_RATE) {
        $status = 'GOOD';
        $severity = 'success';
        $headline = 'The selected period was a GOOD advertising period.';
    } elseif ($ordered > 0 || $leads > 0) {
        $status = 'AVERAGE';
        $severity = 'warning';
        $headline = 'The selected period was an AVERAGE advertising period.';
    } else {
        $status = 'NEEDS ATTENTION';
        $severity = 'danger';
        $headline = 'The selected period NEEDS ATTENTION.';
    }

    $parts = [];
    $parts[] = 'You spent ' . gc_money($spend) . ' across ' . number_format(count($campaignData)) . ' campaigns and generated ' . number_format($leads) . ' leads and ' . number_format($ordered) . ' orders.';
    if ($orderRate !== null) {
        $parts[] = 'Lead to order is ' . number_format((float)$orderRate, 1) . '%, with cost per lead at ' . gc_metric_money($cpl) . ' and cost per order at ' . gc_metric_money($cpo) . '.';
    }
    if ($bestByOrders && (int)$bestByOrders['ordered'] > 0) {
        $parts[] = $bestByOrders['campaign'] . ' produced the highest number of orders.';
    }
    if ($bestByCpo) {
        $parts[] = $bestByCpo['campaign'] . ' has the best cost per order and may deserve more budget.';
    }
    if ($worstNoOrders) {
        $parts[] = $worstNoOrders['campaign'] . ' has spent ' . gc_money($worstNoOrders['spend']) . ' with 0 orders and needs immediate review.';
    }

    $actions = [];
    if ($worstNoOrders) $actions[] = 'pause or reduce ' . $worstNoOrders['campaign'];
    foreach (array_slice($needsAttention, 0, 2) as $row) {
        if (!$worstNoOrders || $row['campaign'] !== $worstNoOrders['campaign']) {
            $actions[] = strtolower($row['recommendation']) . ' for ' . $row['campaign'];
        }
    }
    if ($bestByCpo) $actions[] = 'move some budget to ' . $bestByCpo['campaign'];
    $recommendedAction = $actions
        ? ucfirst(implode(', ', array_unique($actions))) . '.'
        : 'Continue monitoring the current campaigns and keep budget steady today.';

    return [
        'overall_status' => $status,
        'headline' => $headline,
        'insight_text' => implode(' ', $parts),
        'recommended_action' => $recommendedAction,
        'severity' => $severity,
    ];
}

function gc_export_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$periodOptions = [
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'last_7_days' => 'Last 7 Days',
    'last_10_days' => 'Last 10 Days',
    'last_30_days' => 'Last 30 Days',
    'last_2_months' => 'Last 2 Months',
    'last_3_months' => 'Last 3 Months',
    'last_6_months' => 'Last 6 Months',
    'this_month' => 'This Month',
    'custom' => 'Custom Range',
];

$period = isset($_GET['period']) ? trim((string)$_GET['period']) : 'last_7_days';
if (!array_key_exists($period, $periodOptions)) $period = 'last_7_days';
$customFrom = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$customTo = isset($_GET['to']) ? trim((string)$_GET['to']) : '';
$campaignFilter = isset($_GET['campaign']) ? trim((string)$_GET['campaign']) : '';
[$startDate, $endDate, $periodLabel] = gc_period_bounds($period, $customFrom, $customTo);

$startSql = $startDate->format('Y-m-d H:i:s');
$endSql = $endDate->format('Y-m-d H:i:s');
$startDay = $startDate->format('Y-m-d');
$endDay = $endDate->format('Y-m-d');
$toDayDisplay = $endDate->modify('-1 day')->format('Y-m-d');
$syncFrom = $startDay;
$syncTo = $toDayDisplay;

$errors = [];
$rows = [];
$campaignOptions = [];
$unmatchedAds = [];
$unmatchedCrm = [];
$dailyChartRows = [];
$dailyByCampaign = [];
$alerts = [
    'High Spend, No Orders' => [],
    'Many Leads, Poor Orders' => [],
    'Good Campaigns to Scale' => [],
    'No Leads Despite Spend' => [],
];
$totals = [
    'spend' => 0.0,
    'leads' => 0,
    'ordered' => 0,
    'active_campaigns' => 0,
    'wasted_spend' => 0.0,
    'clicks' => 0,
    'impressions' => 0,
    'google_conversions' => 0.0,
];
$adsRowsInRange = 0;
$latestAdsDate = '';
$budgetShift = 'No budget shift recommended yet.';
$budgetShiftPlan = null;
$actionTasks = [];
$previousSummary = ['spend' => 0.0, 'leads' => 0, 'ordered' => 0];
$prediction = ['spend' => null, 'leads' => null, 'ordered' => null, 'cpo' => null, 'basis' => 'Prediction based on historical average'];
$freshness = ['ads' => '', 'crm' => '', 'dashboard' => date('Y-m-d H:i:s')];
$statusCol = null;
$campaignMap = smartronicCampaignIdMap();
$mappedKeys = [];
foreach ($campaignMap as $id => $label) {
    $mappedKeys[gc_campaign_key($label)] = true;
}

try {
    (new GoogleAdsSync($conn))->ensureTable();

    foreach (['created_at', 'Column_1'] as $requiredColumn) {
        if (!gc_column_exists($conn, 'leads', $requiredColumn)) {
            $errors[] = "leads.$requiredColumn column is missing.";
        }
    }

    foreach (['order_status', 'call_status', 'status'] as $candidate) {
        if (gc_column_exists($conn, 'leads', $candidate)) {
            $statusCol = $candidate;
            break;
        }
    }

    $periodSeconds = max(86400, $endDate->getTimestamp() - $startDate->getTimestamp());
    $previousStart = $startDate->modify('-' . $periodSeconds . ' seconds');
    $previousEnd = $startDate;

    if (empty($errors)) {
        $adsByKey = [];
        $leadByKey = [];
        $allCampaigns = [];

        $adsSql = "SELECT
                       campaign_id,
                       COALESCE(NULLIF(TRIM(campaign_name), ''), 'Unknown') AS campaign_name,
                       SUM(cost) AS spend,
                       SUM(clicks) AS clicks,
                       SUM(impressions) AS impressions,
                       SUM(conversions) AS google_conversions
                   FROM google_ads_campaign_stats
                   WHERE report_date >= ? AND report_date < ?
                   GROUP BY campaign_id, campaign_name";
        $stmtAds = $conn->prepare($adsSql);
        if (!$stmtAds) {
            $errors[] = 'Could not prepare Google Ads campaign query.';
        } else {
            $stmtAds->bind_param('ss', $startDay, $endDay);
            $stmtAds->execute();
            $adsRes = $stmtAds->get_result();
            while ($adsRes && ($ad = $adsRes->fetch_assoc())) {
                $campaignId = preg_replace('/\D+/', '', (string)($ad['campaign_id'] ?? ''));
                $googleName = (string)($ad['campaign_name'] ?? 'Unknown');
                $isMapped = $campaignId !== '' && isset($campaignMap[$campaignId]);
                $display = $isMapped ? $campaignMap[$campaignId] : $googleName;
                $key = gc_campaign_key($display);
                $allCampaigns[$display] = true;
                if (!isset($adsByKey[$key])) {
                    $adsByKey[$key] = [
                        'campaign' => $display,
                        'google_campaign_names' => [],
                        'campaign_ids' => [],
                        'spend' => 0.0,
                        'clicks' => 0,
                        'impressions' => 0,
                        'google_conversions' => 0.0,
                        'is_unmatched_ads' => !$isMapped,
                    ];
                }
                $adsByKey[$key]['spend'] += (float)($ad['spend'] ?? 0);
                $adsByKey[$key]['clicks'] += (int)($ad['clicks'] ?? 0);
                $adsByKey[$key]['impressions'] += (int)($ad['impressions'] ?? 0);
                $adsByKey[$key]['google_conversions'] += (float)($ad['google_conversions'] ?? 0);
                if ($campaignId !== '' && !in_array($campaignId, $adsByKey[$key]['campaign_ids'], true)) {
                    $adsByKey[$key]['campaign_ids'][] = $campaignId;
                }
                if ($googleName !== '' && !in_array($googleName, $adsByKey[$key]['google_campaign_names'], true)) {
                    $adsByKey[$key]['google_campaign_names'][] = $googleName;
                }
            }
            $stmtAds->close();
        }

        $orderedExpr = $statusCol
            ? "SUM(CASE WHEN LOWER(TRIM(l.`$statusCol`)) IN ('ordered', 'converted', 'booked') OR LOWER(TRIM(l.`$statusCol`)) LIKE '%ordered%' THEN 1 ELSE 0 END)"
            : "0";
        $leadSql = "SELECT
                        COALESCE(NULLIF(TRIM(l.`Column_1`), ''), 'Unknown') AS campaign,
                        COUNT(*) AS leads_count,
                        $orderedExpr AS ordered_count
                    FROM leads l
                    WHERE l.created_at >= ? AND l.created_at < ?
                    GROUP BY campaign";
        $stmtLeads = $conn->prepare($leadSql);
        if (!$stmtLeads) {
            $errors[] = 'Could not prepare CRM lead query.';
        } else {
            $stmtLeads->bind_param('ss', $startSql, $endSql);
            $stmtLeads->execute();
            $leadRes = $stmtLeads->get_result();
            while ($leadRes && ($lead = $leadRes->fetch_assoc())) {
                $display = (string)($lead['campaign'] ?? 'Unknown');
                $key = gc_campaign_key($display);
                $leadByKey[$key] = [
                    'campaign' => $display,
                    'leads' => (int)($lead['leads_count'] ?? 0),
                    'ordered' => (int)($lead['ordered_count'] ?? 0),
                ];
                $allCampaigns[$display] = true;
            }
            $stmtLeads->close();
        }

        $daily = [];
        for ($cursor = $startDate; $cursor < $endDate; $cursor = $cursor->modify('+1 day')) {
            $dayKey = $cursor->format('Y-m-d');
            $daily[$dayKey] = ['date' => $dayKey, 'spend' => 0.0, 'leads' => 0, 'orders' => 0];
        }

        $stmtDailyAds = $conn->prepare("SELECT report_date, campaign_id, COALESCE(NULLIF(TRIM(campaign_name), ''), 'Unknown') AS campaign_name, SUM(cost) AS spend FROM google_ads_campaign_stats WHERE report_date >= ? AND report_date < ? GROUP BY report_date, campaign_id, campaign_name");
        if ($stmtDailyAds) {
            $stmtDailyAds->bind_param('ss', $startDay, $endDay);
            $stmtDailyAds->execute();
            $dailyAdsRes = $stmtDailyAds->get_result();
            while ($dailyAdsRes && ($d = $dailyAdsRes->fetch_assoc())) {
                $campaignId = preg_replace('/\D+/', '', (string)($d['campaign_id'] ?? ''));
                $display = ($campaignId !== '' && isset($campaignMap[$campaignId]))
                    ? $campaignMap[$campaignId]
                    : (string)($d['campaign_name'] ?? 'Unknown');
                $key = gc_campaign_key($display);
                $day = (string)$d['report_date'];
                $spend = (float)($d['spend'] ?? 0);
                if (isset($daily[$day])) $daily[$day]['spend'] += $spend;
                if (!isset($dailyByCampaign[$key])) $dailyByCampaign[$key] = [];
                if (!isset($dailyByCampaign[$key][$day])) $dailyByCampaign[$key][$day] = ['date' => $day, 'spend' => 0.0, 'leads' => 0, 'orders' => 0];
                $dailyByCampaign[$key][$day]['spend'] += $spend;
            }
            $stmtDailyAds->close();
        }

        $dailyLeadSql = "SELECT
                             DATE(l.created_at) AS day_key,
                             COALESCE(NULLIF(TRIM(l.`Column_1`), ''), 'Unknown') AS campaign,
                             COUNT(*) AS leads_count,
                             $orderedExpr AS ordered_count
                         FROM leads l
                         WHERE l.created_at >= ? AND l.created_at < ?
                         GROUP BY DATE(l.created_at), campaign";
        $stmtDailyLeads = $conn->prepare($dailyLeadSql);
        if ($stmtDailyLeads) {
            $stmtDailyLeads->bind_param('ss', $startSql, $endSql);
            $stmtDailyLeads->execute();
            $dailyLeadRes = $stmtDailyLeads->get_result();
            while ($dailyLeadRes && ($d = $dailyLeadRes->fetch_assoc())) {
                $display = (string)($d['campaign'] ?? 'Unknown');
                $key = gc_campaign_key($display);
                $day = (string)$d['day_key'];
                $orders = (int)($d['ordered_count'] ?? 0);
                if (isset($daily[$day]) && (isset($adsByKey[$key]) || isset($mappedKeys[$key]))) {
                    $daily[$day]['leads'] += (int)($d['leads_count'] ?? 0);
                    $daily[$day]['orders'] += $orders;
                }
                if (!isset($dailyByCampaign[$key])) $dailyByCampaign[$key] = [];
                if (!isset($dailyByCampaign[$key][$day])) $dailyByCampaign[$key][$day] = ['date' => $day, 'spend' => 0.0, 'leads' => 0, 'orders' => 0];
                $dailyByCampaign[$key][$day]['leads'] += (int)($d['leads_count'] ?? 0);
                $dailyByCampaign[$key][$day]['orders'] += $orders;
            }
            $stmtDailyLeads->close();
        }

        $rowKeys = array_unique(array_merge(array_keys($adsByKey), array_keys($leadByKey)));
        foreach ($rowKeys as $key) {
            $ads = $adsByKey[$key] ?? null;
            $lead = $leadByKey[$key] ?? null;
            $isMappedLead = isset($mappedKeys[$key]);
            if (!$ads && !$isMappedLead) {
                if ($lead) $unmatchedCrm[] = $lead;
                continue;
            }

            $campaign = $ads['campaign'] ?? ($lead['campaign'] ?? 'Unknown');
            if ($campaignFilter !== '' && $campaign !== $campaignFilter) {
                continue;
            }

            $spend = (float)($ads['spend'] ?? 0);
            $leads = (int)($lead['leads'] ?? 0);
            $ordered = (int)($lead['ordered'] ?? 0);
            $clicks = (int)($ads['clicks'] ?? 0);
            $impressions = (int)($ads['impressions'] ?? 0);
            $googleConversions = (float)($ads['google_conversions'] ?? 0);
            $orderRate = $leads > 0 ? ($ordered / $leads) * 100 : null;
            $costPerLead = $leads > 0 ? $spend / $leads : null;
            $costPerOrder = $ordered > 0 ? $spend / $ordered : null;
            $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : null;
            $avgCpc = $clicks > 0 ? $spend / $clicks : null;

            $row = [
                'key' => $key,
                'campaign' => $campaign,
                'budget' => null,
                'spend' => $spend,
                'leads' => $leads,
                'ordered' => $ordered,
                'order_rate' => $orderRate,
                'cost_per_lead' => $costPerLead,
                'cost_per_order' => $costPerOrder,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
                'avg_cpc' => $avgCpc,
                'google_conversions' => $googleConversions,
                'google_campaign_names' => $ads['google_campaign_names'] ?? [],
                'campaign_ids' => $ads['campaign_ids'] ?? [],
                'is_unmatched_ads' => (bool)($ads['is_unmatched_ads'] ?? false),
            ];
            [$row['status'], $row['status_class']] = gc_campaign_status($row);
            $row['recommendation'] = gc_recommendation($row);
            $row['health_score'] = gc_health_score($row);
            [$row['health_label'], $row['health_class']] = gc_health_label($row['health_score']);
            [$row['priority'], $row['priority_rank'], $row['priority_class']] = gc_priority($row);
            [$row['trend'], $row['trend_class']] = gc_row_trend($dailyByCampaign[$key] ?? []);
            $rows[] = $row;

            $totals['spend'] += $spend;
            $totals['leads'] += $leads;
            $totals['ordered'] += $ordered;
            $totals['clicks'] += $clicks;
            $totals['impressions'] += $impressions;
            $totals['google_conversions'] += $googleConversions;
            if ($spend > 0) $totals['active_campaigns']++;
            if ($leads > 0 && $ordered === 0) $totals['wasted_spend'] += $spend;

            if ($row['is_unmatched_ads'] && $spend > 0) {
                $unmatchedAds[] = $row;
            }
            if ($spend >= ACTION_SPEND_THRESHOLD && $ordered === 0) $alerts['High Spend, No Orders'][] = $row;
            if ($leads >= HIGH_LEADS_LOW_ORDER_THRESHOLD && ($orderRate === null || $orderRate < POOR_ORDER_RATE)) $alerts['Many Leads, Poor Orders'][] = $row;
            if ($ordered >= GOOD_MIN_ORDERS && $costPerOrder !== null && $costPerOrder <= LOW_COST_PER_ORDER) $alerts['Good Campaigns to Scale'][] = $row;
            if ($spend >= WATCH_SPEND_THRESHOLD && $leads === 0) $alerts['No Leads Despite Spend'][] = $row;
        }

        usort($rows, function ($a, $b) {
            return [
                (int)$a['priority_rank'],
                -(int)$a['health_score'],
                -(float)$a['spend'],
            ] <=> [
                (int)$b['priority_rank'],
                -(int)$b['health_score'],
                -(float)$b['spend'],
            ];
        });

        ksort($allCampaigns);
        $campaignOptions = array_keys($allCampaigns);
        $dailyChartRows = array_values($daily);
        foreach ($dailyByCampaign as $key => $items) {
            ksort($items);
            $dailyByCampaign[$key] = array_values($items);
        }

        $reduceRows = array_values(array_filter($rows, fn($r) => (float)$r['spend'] >= ACTION_SPEND_THRESHOLD && (int)$r['ordered'] === 0));
        usort($reduceRows, fn($a, $b) => (float)$b['spend'] <=> (float)$a['spend']);
        $scaleRows = array_values(array_filter($rows, fn($r) => (int)$r['ordered'] >= GOOD_MIN_ORDERS && $r['cost_per_order'] !== null));
        usort($scaleRows, fn($a, $b) => (float)$a['cost_per_order'] <=> (float)$b['cost_per_order']);
        if ($reduceRows && $scaleRows) {
            $budgetShift = 'Move ₹' . BUDGET_SHIFT_AMOUNT . ' from ' . $reduceRows[0]['campaign'] . ' to ' . $scaleRows[0]['campaign'] . ' because ' . $scaleRows[0]['campaign'] . ' has better order rate.';
            $budgetShiftPlan = ['amount' => BUDGET_SHIFT_AMOUNT, 'from' => $reduceRows[0]['campaign'], 'to' => $scaleRows[0]['campaign'], 'reason' => $scaleRows[0]['campaign'] . ' generates better orders.', 'expected' => 'Lower wasted spend and improve cost per order.'];
        } elseif ($scaleRows) {
            $budgetShift = 'Consider increasing budget on ' . $scaleRows[0]['campaign'] . ' because it has the lowest cost per order.';
            $budgetShiftPlan = ['amount' => BUDGET_SHIFT_AMOUNT, 'from' => 'Unassigned budget', 'to' => $scaleRows[0]['campaign'], 'reason' => 'Lowest cost per order.', 'expected' => 'Potential additional orders from a healthier campaign.'];
        } elseif ($reduceRows) {
            $budgetShift = 'Review spend on ' . $reduceRows[0]['campaign'] . ' before adding more budget.';
            $budgetShiftPlan = ['amount' => BUDGET_SHIFT_AMOUNT, 'from' => $reduceRows[0]['campaign'], 'to' => 'Hold', 'reason' => 'High spend without orders.', 'expected' => 'Estimated saving from reducing wasted spend.'];
        }

        foreach ($rows as $row) {
            if (count($actionTasks) >= 6) break;
            $benefit = $row['recommendation'] === 'Increase Budget' ? 'Estimated additional orders' : 'Estimated saving';
            $actionTasks[] = [
                'title' => $row['recommendation'] . ' - ' . $row['campaign'],
                'priority' => $row['priority'],
                'benefit' => $benefit,
                'improvement' => $row['cost_per_order'] !== null ? gc_money($row['cost_per_order']) . ' current CPO' : 'Improve campaign efficiency',
            ];
        }

        $previousSummary = gc_fetch_summary($conn, $previousStart, $previousEnd, $statusCol, $campaignMap, $mappedKeys, $campaignFilter);
        $last7Start = (new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata')))->modify('-6 days');
        $last7End = (new DateTimeImmutable('today', new DateTimeZone('Asia/Kolkata')))->modify('+1 day');
        $last7 = gc_fetch_summary($conn, $last7Start, $last7End, $statusCol, $campaignMap, $mappedKeys, $campaignFilter);
        $prediction = [
            'spend' => $last7['spend'] / 7,
            'leads' => $last7['leads'] / 7,
            'ordered' => $last7['ordered'] / 7,
            'cpo' => $last7['ordered'] > 0 ? $last7['spend'] / $last7['ordered'] : null,
            'basis' => 'Prediction based on historical average',
        ];

        $stmtDiag = $conn->prepare("SELECT COUNT(*) AS row_count, MAX(report_date) AS latest_date, MAX(updated_at) AS latest_sync FROM google_ads_campaign_stats WHERE report_date >= ? AND report_date < ?");
        if ($stmtDiag) {
            $stmtDiag->bind_param('ss', $startDay, $endDay);
            $stmtDiag->execute();
            $diagRes = $stmtDiag->get_result();
            if ($diagRes && ($diag = $diagRes->fetch_assoc())) {
                $adsRowsInRange = (int)($diag['row_count'] ?? 0);
                $latestAdsDate = (string)($diag['latest_date'] ?? '');
                $freshness['ads'] = (string)($diag['latest_sync'] ?? '');
            }
            $stmtDiag->close();
        }
        $crmSyncRes = $conn->query("SELECT MAX(created_at) AS latest_crm FROM leads");
        if ($crmSyncRes && ($crmRow = $crmSyncRes->fetch_assoc())) {
            $freshness['crm'] = (string)($crmRow['latest_crm'] ?? '');
        }
    }
} catch (Throwable $e) {
    error_log('Google Ads Operations Center failed: ' . $e->getMessage());
    $errors[] = $e->getMessage();
}

$overallOrderRate = $totals['leads'] > 0 ? ($totals['ordered'] / $totals['leads']) * 100 : null;
$overallCpl = $totals['leads'] > 0 ? $totals['spend'] / $totals['leads'] : null;
$overallCpo = $totals['ordered'] > 0 ? $totals['spend'] / $totals['ordered'] : null;
$spendChange = gc_pct_change($totals['spend'], $previousSummary['spend']);
$ordersChange = gc_pct_change((float)$totals['ordered'], (float)$previousSummary['ordered']);
$previousCpo = $previousSummary['ordered'] > 0 ? $previousSummary['spend'] / $previousSummary['ordered'] : null;
$cpoChange = gc_pct_change($overallCpo, $previousCpo);
[$spendChangeText, $spendChangeClass] = gc_change_text($spendChange, true);
[$ordersChangeText, $ordersChangeClass] = gc_change_text($ordersChange, false);
[$cpoChangeText, $cpoChangeClass] = gc_change_text($cpoChange, true);
$overallHealth = $rows ? (int)round(array_sum(array_map(fn($r) => (int)$r['health_score'], $rows)) / count($rows)) : 0;
[$overallHealthLabel, $overallHealthClass] = gc_health_label($overallHealth);
$overallStatus = $overallHealth >= GOOD_HEALTH_SCORE ? 'GOOD DAY' : ($overallHealth >= WATCH_HEALTH_SCORE ? 'AVERAGE' : 'NEEDS ATTENTION');
$expectedStatus = ($prediction['ordered'] ?? 0) >= 1 && (($prediction['cpo'] ?? null) === null || $prediction['cpo'] <= HIGH_COST_PER_ORDER) ? 'GOOD' : 'WATCH';
$ceoInsight = generateCeoInsight([
    'spend' => $totals['spend'],
    'leads' => $totals['leads'],
    'ordered' => $totals['ordered'],
    'order_rate' => $overallOrderRate,
    'cost_per_lead' => $overallCpl,
    'cost_per_order' => $overallCpo,
], $rows);
$healthDistribution = [
    'Excellent' => count(array_filter($rows, fn($r) => (int)$r['health_score'] >= GOOD_HEALTH_SCORE)),
    'Healthy' => count(array_filter($rows, fn($r) => (int)$r['health_score'] >= WATCH_HEALTH_SCORE && (int)$r['health_score'] < GOOD_HEALTH_SCORE)),
    'Needs Attention' => count(array_filter($rows, fn($r) => (int)$r['health_score'] < WATCH_HEALTH_SCORE)),
];

if (isset($_GET['export']) && $_GET['export'] === 'campaigns') {
    $csvRows = [];
    foreach ($rows as $row) {
        $csvRows[] = [
            $row['campaign'],
            $row['status'],
            $row['health_score'],
            $row['priority'],
            $row['trend'],
            'N/A',
            round((float)$row['spend'], 2),
            (int)$row['leads'],
            (int)$row['ordered'],
            $row['order_rate'] === null ? 'N/A' : round((float)$row['order_rate'], 2),
            $row['cost_per_lead'] === null ? 'N/A' : round((float)$row['cost_per_lead'], 2),
            $row['cost_per_order'] === null ? 'N/A' : round((float)$row['cost_per_order'], 2),
            (int)$row['clicks'],
            (int)$row['impressions'],
            $row['ctr'] === null ? 'N/A' : round((float)$row['ctr'], 2),
            $row['avg_cpc'] === null ? 'N/A' : round((float)$row['avg_cpc'], 2),
            round((float)$row['google_conversions'], 2),
            $row['recommendation'],
        ];
    }
    gc_export_csv('google_ads_operations_center_campaigns.csv', ['Campaign Name', 'Status', 'Health Score', 'Priority', 'Trend', 'Budget', 'Spend', 'Leads', 'Ordered Leads', 'Lead To Order %', 'Cost / Lead', 'Cost / Order', 'Clicks', 'Impressions', 'CTR', 'Avg CPC', 'Google Conversions', 'Recommendation'], $csvRows);
}

if (isset($_GET['export']) && $_GET['export'] === 'alerts') {
    $csvRows = [];
    foreach ($alerts as $alertName => $items) {
        foreach ($items as $row) {
            $csvRows[] = [$alertName, $row['campaign'], round((float)$row['spend'], 2), (int)$row['leads'], (int)$row['ordered'], $row['recommendation']];
        }
    }
    gc_export_csv('google_ads_operations_center_alerts.csv', ['Alert', 'Campaign', 'Spend', 'Leads', 'Ordered Leads', 'Recommendation'], $csvRows);
}

$fromDisplay = $startDate->format('d M Y');
$toDisplay = $endDate->modify('-1 day')->format('d M Y');
$baseQuery = $_GET;
unset($baseQuery['export']);
$exportCampaignUrl = '?' . http_build_query($baseQuery + ['export' => 'campaigns']);
$exportAlertsUrl = '?' . http_build_query($baseQuery + ['export' => 'alerts']);
$chartLabels = array_map(fn($r) => $r['campaign'], $rows);
$chartSpend = array_map(fn($r) => round((float)$r['spend'], 2), $rows);
$chartOrders = array_map(fn($r) => (int)$r['ordered'], $rows);
$chartLeads = array_map(fn($r) => (int)$r['leads'], $rows);
$chartCpo = array_map(fn($r) => $r['cost_per_order'] === null ? 0 : round((float)$r['cost_per_order'], 2), $rows);
$chartHealth = array_values($healthDistribution);
$dailyLabels = array_map(fn($r) => date('d M', strtotime($r['date'])), $dailyChartRows);
$dailySpend = array_map(fn($r) => round((float)$r['spend'], 2), $dailyChartRows);
$dailyOrders = array_map(fn($r) => (int)$r['orders'], $dailyChartRows);
$dailyCpo = array_map(fn($r) => (int)$r['orders'] > 0 ? round((float)$r['spend'] / (int)$r['orders'], 2) : 0, $dailyChartRows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Ads | Operations</title>
  <link rel="icon" type="image/png" sizes="32x32" href="/content/uploads/2025/01/cropped-Site-Icon-32x32.png">
  <link rel="apple-touch-icon" href="/content/uploads/2025/01/cropped-Site-Icon-180x180.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f9fc;
            --panel: #ffffff;
            --ink: #243044;
            --muted: #6b7280;
            --line: #e6edf5;
            --green: #0f9f7a;
            --yellow: #b7791f;
            --red: #c2410c;
            --blue: #2563eb;
            --shadow: 0 8px 22px rgba(15, 23, 42, .055);
        }
        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(180deg, #fbfdff 0%, var(--bg) 52%, #ffffff 100%);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            font-weight: 400;
        }
        .page { width: min(1440px, calc(100% - 24px)); margin: 0 auto; padding: 22px 0 40px; }
        .fw-bold { font-weight: 650 !important; }
        .topbar, .filters, .panel, .metric, .alert-box { background: rgba(255,255,255,.94); border: 1px solid var(--line); box-shadow: var(--shadow); }
        .topbar { border-radius: 10px; padding: 18px; display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; }
        .title h1 { margin: 0; font-size: clamp(24px, 4vw, 34px); font-weight: 650; letter-spacing: 0; color: #1f2937; }
        .title p { margin: 8px 0 0; color: var(--muted); font-weight: 400; }
        .top-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .btn-soft { border: 1px solid var(--line); background: #fff; color: var(--ink); font-weight: 500; box-shadow: 0 2px 7px rgba(15, 23, 42, .035); }
        .btn-soft:hover { background: #f8fafc; border-color: #d8e3ef; }
        .sync-status {
            flex-basis: 100%;
            text-align: right;
            color: var(--muted);
            font-size: 12px;
            min-height: 18px;
        }
        .sync-status.success { color: #047857; }
        .sync-status.error { color: #b91c1c; }
        .filters {
            position: sticky;
            top: 0;
            z-index: 30;
            border-radius: 10px;
            padding: 14px;
            margin: 14px 0;
            backdrop-filter: blur(10px);
        }
        .field-label { color: var(--muted); font-size: 12px; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; }
        .form-control, .form-select, .btn { border-radius: 6px; }
        .btn-dark { background: #243044; border-color: #243044; font-weight: 500; }
        .metric { border-radius: 10px; padding: 15px; min-height: 128px; transition: transform .18s ease, box-shadow .18s ease; }
        .metric:hover { transform: translateY(-1px); box-shadow: 0 10px 26px rgba(15, 23, 42, .07); }
        .today-grid { display: grid; grid-template-columns: 1.25fr 1fr 1fr; gap: 14px; margin: 14px 0; }
        .ops-card { background: rgba(255,255,255,.97); border: 1px solid var(--line); border-radius: 10px; padding: 16px; box-shadow: var(--shadow); }
        .ops-card h2, .ops-card h3 { margin: 0 0 12px; font-size: 16px; font-weight: 650; }
        .summary-money { font-size: clamp(30px, 5vw, 46px); line-height: 1; font-weight: 650; color: #111827; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
        .summary-cell { border: 1px solid #edf2f7; background: #fbfcfe; border-radius: 8px; padding: 10px; }
        .summary-cell span { display: block; color: var(--muted); font-size: 12px; font-weight: 500; }
        .summary-cell strong { display: block; margin-top: 4px; font-size: 18px; font-weight: 650; }
        .change-row { display: flex; justify-content: space-between; gap: 10px; border-top: 1px solid #edf2f7; padding: 9px 0; font-weight: 500; }
        .change-good { color: #047857; }
        .change-bad { color: #b91c1c; }
        .change-flat { color: #64748b; }
        .health-pill, .priority-pill, .trend-pill { display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .health-good { background: #dcfce7; color: #166534; }
        .health-mid { background: #fef3c7; color: #92400e; }
        .health-bad { background: #ffedd5; color: #9a3412; }
        .health-critical { background: #fee2e2; color: #991b1b; }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #ffedd5; color: #9a3412; }
        .priority-good { background: #dcfce7; color: #166534; }
        .priority-review { background: #e0f2fe; color: #075985; }
        .trend-good { color: #047857; }
        .trend-bad { color: #b91c1c; }
        .trend-flat { color: #64748b; }
        .task-row { display: grid; grid-template-columns: 22px 1fr; gap: 8px; align-items: start; padding: 9px 0; border-top: 1px solid #edf2f7; }
        .task-row input { margin-top: 4px; }
        .task-title { font-weight: 600; }
        .task-meta { color: var(--muted); font-size: 12px; font-weight: 400; margin-top: 2px; }
        .shift-card { display: grid; grid-template-columns: repeat(5, auto); gap: 10px; align-items: center; justify-content: start; }
        .shift-token { background: #fbfcfe; border: 1px solid #edf2f7; border-radius: 8px; padding: 10px 12px; font-weight: 500; }
        .freshness { display: flex; flex-wrap: wrap; gap: 8px; }
        .fresh-pill { border-radius: 999px; border: 1px solid var(--line); padding: 7px 10px; background: #fff; font-size: 12px; font-weight: 500; }
        .fresh-good { color: #047857; border-color: #bbf7d0; background: #f0fdf4; }
        .fresh-old { color: #b91c1c; border-color: #fecaca; background: #fff7f7; }
        .ceo-insight-card {
            position: relative;
            overflow: hidden;
            margin: 14px 0;
            padding: 22px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .28);
            background:
                linear-gradient(135deg, rgba(255,255,255,.99), rgba(245,249,255,.96)),
                radial-gradient(circle at 92% 8%, rgba(37,99,235,.10), transparent 26rem);
            box-shadow: 0 12px 32px rgba(15, 23, 42, .075);
        }
        .ceo-insight-card::after {
            content: "";
            position: absolute;
            inset: auto -80px -120px auto;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(15,159,122,.12), transparent 66%);
            pointer-events: none;
        }
        .ceo-head { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; position: relative; z-index: 1; }
        .ceo-title-wrap { display: flex; gap: 14px; align-items: center; }
        .ceo-icon {
            width: 54px;
            height: 54px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #243044;
            color: #fff;
            font-size: 25px;
            box-shadow: 0 8px 18px rgba(23,32,51,.14);
        }
        .ceo-title { margin: 0; font-size: clamp(22px, 3vw, 30px); font-weight: 650; letter-spacing: 0; }
        .ceo-subtitle { color: var(--muted); font-size: 13px; font-weight: 500; margin-top: 3px; text-transform: uppercase; letter-spacing: .03em; }
        .ceo-badge { border-radius: 999px; padding: 8px 12px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .ceo-badge.success { background: #dcfce7; color: #166534; }
        .ceo-badge.warning { background: #fef3c7; color: #92400e; }
        .ceo-badge.danger { background: #fee2e2; color: #991b1b; }
        .ceo-body { position: relative; z-index: 1; margin-top: 16px; display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(260px, .8fr); gap: 16px; align-items: stretch; }
        .ceo-headline { margin: 0 0 10px; font-size: 20px; font-weight: 650; }
        .ceo-copy { margin: 0; color: #334155; font-size: 15px; line-height: 1.65; font-weight: 400; }
        .ceo-action { background: rgba(255,255,255,.82); border: 1px solid rgba(148,163,184,.3); border-radius: 10px; padding: 14px; }
        .ceo-action span { display: block; color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 7px; }
        .ceo-action strong { display: block; color: var(--ink); font-size: 16px; line-height: 1.45; font-weight: 600; }
        .metric-label { color: var(--muted); font-size: 13px; font-weight: 500; }
        .metric-value { margin-top: 9px; font-size: clamp(24px, 4vw, 34px); line-height: 1; font-weight: 650; color: #1f2937; }
        .metric-hint { margin-top: 8px; color: var(--muted); font-size: 12px; font-weight: 400; }
        .panel { border-radius: 10px; padding: 16px; margin-top: 16px; }
        .panel-title { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 12px; }
        .panel-title h2 { margin: 0; font-size: 18px; font-weight: 650; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .status-good { background: #dcfce7; color: #166534; }
        .status-watch { background: #fef3c7; color: #92400e; }
        .status-action { background: #fee2e2; color: #991b1b; }
        .status-none { background: #eef2f7; color: #475569; }
        .recommend { font-weight: 600; color: var(--blue); min-width: 130px; }
        .table-wrap { overflow-x: auto; }
        table { min-width: 1240px; }
        th { white-space: nowrap; color: var(--muted); font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .035em; }
        .campaign-table th[data-sort] { cursor: pointer; user-select: none; }
        .sort-title { display: inline-flex; align-items: center; gap: 5px; }
        .sort-title::after { content: "↕"; color: #94a3b8; font-size: 11px; }
        .campaign-table th.sort-asc .sort-title::after { content: "↑"; color: var(--blue); }
        .campaign-table th.sort-desc .sort-title::after { content: "↓"; color: var(--blue); }
        td { vertical-align: middle; font-weight: 400; color: #334155; }
        .campaign-name { font-weight: 650; color: #1f2937; }
        .subtext { color: var(--muted); font-size: 12px; margin-top: 2px; }
        .action-stack { display: flex; flex-wrap: wrap; gap: 6px; min-width: 230px; }
        .action-stack .btn { font-size: 12px; font-weight: 500; padding: 5px 8px; }
        .alert-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .alert-box { border-radius: 10px; padding: 14px; }
        .alert-box h3 { margin: 0 0 8px; font-size: 15px; font-weight: 650; }
        .alert-item { display: flex; justify-content: space-between; gap: 8px; padding: 8px 0; border-top: 1px solid #eef2f7; font-size: 13px; }
        .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 12px; }
        .chart-card { height: 340px; }
        .drawer-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.35); z-index: 40; display: none; }
        .drawer-backdrop.open { display: block; }
        .drawer { position: fixed; top: 0; right: -460px; width: min(460px, 100%); height: 100vh; background: #fff; z-index: 50; box-shadow: -18px 0 40px rgba(15,23,42,.18); transition: right .22s ease; overflow: auto; }
        .drawer.open { right: 0; }
        .drawer-head { padding: 18px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; gap: 12px; }
        .drawer-body { padding: 18px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .detail-cell { background: #fbfcfe; border: 1px solid #eef2f7; border-radius: 8px; padding: 10px; }
        .detail-cell span { color: var(--muted); font-size: 12px; font-weight: 500; display: block; }
        .detail-cell strong { font-size: 18px; font-weight: 650; }
        @media (max-width: 760px) {
            .topbar { display: grid; }
            .top-actions { justify-content: stretch; }
            .top-actions .btn { flex: 1 1 auto; }
            .sync-status { text-align: left; }
            .metric { min-height: auto; }
            .chart-card { height: 300px; }
            .today-grid { grid-template-columns: 1fr; }
            .ceo-head, .ceo-body { grid-template-columns: 1fr; display: grid; }
            .ceo-title-wrap { align-items: flex-start; }
            .summary-grid { grid-template-columns: 1fr; }
            .shift-card { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <div class="title">
            <h1>Google Ads Operations Center</h1>
            <p>Spend, leads, orders, and the daily decision for each campaign.</p>
        </div>
        <div class="top-actions">
            <a class="btn btn-soft" href="google_ads_connect.php">Connect Google Ads</a>
            <button class="btn btn-soft" type="button" id="syncGoogleAdsBtn" data-sync-url="sync_google_ads.php?run=1&from=<?php echo gc_h($syncFrom); ?>&to=<?php echo gc_h($syncTo); ?>">Sync This Period</button>
            <a class="btn btn-soft" href="<?php echo gc_h($exportCampaignUrl); ?>">Export Campaign Table CSV</a>
            <a class="btn btn-soft" href="<?php echo gc_h($exportAlertsUrl); ?>">Export Alerts CSV</a>
            <button class="btn btn-soft" type="button" onclick="window.print()">Print Dashboard</button>
            <button class="btn btn-soft" type="button" onclick="window.print()">Export Dashboard PDF</button>
            <div class="sync-status" id="syncGoogleAdsStatus" aria-live="polite"></div>
        </div>
    </header>

    <form class="filters" method="get">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="field-label" for="period">Date Range</label>
                <select class="form-select" id="period" name="period">
                    <?php foreach ($periodOptions as $value => $label): ?>
                        <option value="<?php echo gc_h($value); ?>" <?php echo $period === $value ? 'selected' : ''; ?>><?php echo gc_h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 custom-date">
                <label class="field-label" for="from">From</label>
                <input class="form-control" type="date" id="from" name="from" value="<?php echo gc_h($customFrom); ?>">
            </div>
            <div class="col-6 col-md-2 custom-date">
                <label class="field-label" for="to">To</label>
                <input class="form-control" type="date" id="to" name="to" value="<?php echo gc_h($customTo); ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="field-label" for="campaign">Campaign</label>
                <select class="form-select" id="campaign" name="campaign">
                    <option value="">All campaigns</option>
                    <?php foreach ($campaignOptions as $campaign): ?>
                        <option value="<?php echo gc_h($campaign); ?>" <?php echo $campaignFilter === $campaign ? 'selected' : ''; ?>><?php echo gc_h($campaign); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-1">
                <label class="field-label" for="source">Lead Source</label>
                <select class="form-select" id="source" disabled>
                    <option>Google Ads</option>
                </select>
            </div>
            <div class="col-12 col-md-1">
                <button class="btn btn-dark w-100" type="submit">Apply</button>
            </div>
        </div>
    </form>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo gc_h(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <?php if (function_exists('isStrictAdminRole') && isStrictAdminRole($role)): ?>
        <section class="ceo-insight-card" aria-label="CEO Insight">
            <div class="ceo-head">
                <div class="ceo-title-wrap">
                    <div class="ceo-icon" aria-hidden="true">✦</div>
                    <div>
                        <h2 class="ceo-title">CEO Insight</h2>
                        <div class="ceo-subtitle">Auto-generated business summary</div>
                    </div>
                </div>
                <span class="ceo-badge <?php echo gc_h($ceoInsight['severity']); ?>"><?php echo gc_h($ceoInsight['overall_status']); ?></span>
            </div>
            <div class="ceo-body">
                <div>
                    <h3 class="ceo-headline"><?php echo gc_h($ceoInsight['headline']); ?></h3>
                    <p class="ceo-copy"><?php echo gc_h($ceoInsight['insight_text']); ?></p>
                </div>
                <div class="ceo-action">
                    <span>Recommended action today</span>
                    <strong><?php echo gc_h($ceoInsight['recommended_action']); ?></strong>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section aria-label="Today's Summary">
        <div class="panel-title mt-3">
            <h2>Today's Summary</h2>
            <div class="freshness">
                <?php
                $adsFresh = $freshness['ads'] !== '' && strtotime($freshness['ads']) >= time() - 3600;
                $crmFresh = $freshness['crm'] !== '' && strtotime($freshness['crm']) >= time() - 3600;
                ?>
                <span class="fresh-pill <?php echo $adsFresh ? 'fresh-good' : 'fresh-old'; ?>">Last Google Ads Sync: <?php echo gc_h($freshness['ads'] ?: 'N/A'); ?></span>
                <span class="fresh-pill <?php echo $crmFresh ? 'fresh-good' : 'fresh-old'; ?>">Last CRM Sync: <?php echo gc_h($freshness['crm'] ?: 'N/A'); ?></span>
                <span class="fresh-pill fresh-good">Dashboard Refresh: <?php echo gc_h($freshness['dashboard']); ?></span>
            </div>
        </div>
        <div class="today-grid">
            <article class="ops-card">
                <h2>What Actually Happened</h2>
                <div class="subtext"><?php echo gc_h($periodLabel); ?> performance</div>
                <div class="summary-money"><?php echo gc_money($totals['spend']); ?></div>
                <div class="summary-grid">
                    <div class="summary-cell"><span>Generated Leads</span><strong><?php echo number_format($totals['leads']); ?></strong></div>
                    <div class="summary-cell"><span>Generated Orders</span><strong><?php echo number_format($totals['ordered']); ?></strong></div>
                    <div class="summary-cell"><span>Lead → Order</span><strong><?php echo gc_metric_percent($overallOrderRate); ?></strong></div>
                    <div class="summary-cell"><span>Cost Per Lead</span><strong><?php echo gc_metric_money($overallCpl); ?></strong></div>
                    <div class="summary-cell"><span>Cost Per Order</span><strong><?php echo gc_metric_money($overallCpo); ?></strong></div>
                    <div class="summary-cell"><span>Overall Status</span><strong><?php echo gc_h($overallStatus); ?></strong></div>
                </div>
                <div class="mt-3">
                    <div class="change-row"><span>Spend</span><strong class="<?php echo gc_h($spendChangeClass); ?>"><?php echo gc_h($spendChangeText); ?></strong></div>
                    <div class="change-row"><span>Orders</span><strong class="<?php echo gc_h($ordersChangeClass); ?>"><?php echo gc_h($ordersChangeText); ?></strong></div>
                    <div class="change-row"><span>Cost Per Order</span><strong class="<?php echo gc_h($cpoChangeClass); ?>"><?php echo gc_h($cpoChangeText); ?></strong></div>
                </div>
            </article>
            <article class="ops-card">
                <h2>What Could Happen Next</h2>
                <div class="subtext"><?php echo gc_h($prediction['basis']); ?></div>
                <div class="summary-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="summary-cell"><span>Expected Spend Today</span><strong><?php echo gc_metric_money($prediction['spend']); ?></strong></div>
                    <div class="summary-cell"><span>Expected Leads</span><strong><?php echo gc_metric_number($prediction['leads'], 0); ?></strong></div>
                    <div class="summary-cell"><span>Expected Orders</span><strong><?php echo gc_metric_number($prediction['ordered'], 0); ?></strong></div>
                    <div class="summary-cell"><span>Expected CPO</span><strong><?php echo gc_metric_money($prediction['cpo']); ?></strong></div>
                </div>
                <div class="mt-3"><span class="health-pill <?php echo gc_h($overallHealthClass); ?>">Expected Daily Status: <?php echo gc_h($expectedStatus); ?></span></div>
            </article>
            <article class="ops-card">
                <h2>Today's Action Plan</h2>
                <?php if (!$actionTasks): ?>
                    <div class="subtext">No optimization tasks for the current filters.</div>
                <?php endif; ?>
                <?php foreach ($actionTasks as $task): ?>
                    <label class="task-row">
                        <input type="checkbox">
                        <span>
                            <span class="task-title"><?php echo gc_h($task['title']); ?></span>
                            <span class="task-meta"><?php echo gc_h($task['priority']); ?> priority · <?php echo gc_h($task['benefit']); ?> · <?php echo gc_h($task['improvement']); ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </article>
        </div>
    </section>

    <section class="row g-3" aria-label="Main KPI cards">
        <?php
        $metricCards = [
            ['Google Ads Spend', gc_money($totals['spend']), number_format($adsRowsInRange) . ' synced rows' . ($latestAdsDate ? ', latest ' . $latestAdsDate : '')],
            ['Leads', number_format($totals['leads']), $periodLabel . ', ' . $fromDisplay . ' to ' . $toDisplay],
            ['Ordered Leads', number_format($totals['ordered']), $statusCol ? 'Using ' . $statusCol : 'No order status column found'],
            ['Lead to Order %', gc_metric_percent($overallOrderRate), 'Ordered leads / leads'],
            ['Cost Per Lead', gc_metric_money($overallCpl), 'Spend / leads'],
            ['Cost Per Order', gc_metric_money($overallCpo), 'Spend / ordered leads'],
            ['Active Campaigns', number_format($totals['active_campaigns']), 'Campaigns with spend > 0'],
            ['Wasted Spend', gc_money($totals['wasted_spend']), 'Spend where leads exist but no orders'],
        ];
        ?>
        <?php foreach ($metricCards as [$label, $value, $hint]): ?>
            <div class="col-12 col-sm-6 col-lg-3">
                <article class="metric">
                    <div class="metric-label"><?php echo gc_h($label); ?></div>
                    <div class="metric-value"><?php echo gc_h($value); ?></div>
                    <div class="metric-hint"><?php echo gc_h($hint); ?></div>
                </article>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Budget Shift Suggestions</h2>
            <span class="status-badge status-none">Recommendation only</span>
        </div>
        <div class="shift-card">
            <?php if ($budgetShiftPlan): ?>
                <span class="shift-token">Move</span>
                <span class="shift-token"><?php echo gc_money($budgetShiftPlan['amount']); ?></span>
                <span class="shift-token">From: <?php echo gc_h($budgetShiftPlan['from']); ?></span>
                <span class="shift-token">To: <?php echo gc_h($budgetShiftPlan['to']); ?></span>
                <span class="shift-token"><?php echo gc_h($budgetShiftPlan['expected']); ?></span>
            <?php else: ?>
                <span class="shift-token"><?php echo gc_h($budgetShift); ?></span>
            <?php endif; ?>
        </div>
        <?php if ($budgetShiftPlan): ?><div class="subtext mt-2">Reason: <?php echo gc_h($budgetShiftPlan['reason']); ?></div><?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Alerts</h2>
            <span class="subtext">Thresholds are configurable in <code>GoogleAdsCommandConfig.php</code></span>
        </div>
        <div class="alert-grid">
            <?php foreach ($alerts as $alertName => $items): ?>
                <article class="alert-box">
                    <?php
                    $severity = in_array($alertName, ['High Spend, No Orders'], true) ? 'Critical' : (in_array($alertName, ['Many Leads, Poor Orders', 'No Leads Despite Spend'], true) ? 'Warning' : 'Information');
                    $severityClass = $severity === 'Critical' ? 'priority-high' : ($severity === 'Warning' ? 'priority-medium' : 'priority-review');
                    ?>
                    <h3><?php echo gc_h($alertName); ?> <span class="priority-pill <?php echo gc_h($severityClass); ?>"><?php echo gc_h($severity); ?></span></h3>
                    <?php if (!$items): ?>
                        <div class="subtext">No campaigns in this alert.</div>
                    <?php endif; ?>
                    <?php foreach (array_slice($items, 0, 5) as $item): ?>
                        <div class="alert-item">
                            <strong><?php echo gc_h($item['campaign']); ?></strong>
                            <span><?php echo gc_money($item['spend']); ?> / <?php echo number_format((int)$item['ordered']); ?> orders</span>
                        </div>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Campaign Performance</h2>
            <span class="subtext">Sorted by priority, health, and spend</span>
        </div>
        <?php if (empty($rows) && empty($errors)): ?>
            <div class="alert alert-light border">No campaigns match current filters.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table table-hover align-middle campaign-table" id="campaignPerformanceTable">
                    <thead>
                    <tr>
                        <th data-sort="text"><span class="sort-title">Campaign Name</span></th>
                        <th data-sort="text"><span class="sort-title">Status</span></th>
                        <th data-sort="number"><span class="sort-title">Health Score</span></th>
                        <th data-sort="text"><span class="sort-title">Priority</span></th>
                        <th data-sort="text"><span class="sort-title">Trend</span></th>
                        <th data-sort="number"><span class="sort-title">Budget</span></th>
                        <th data-sort="number"><span class="sort-title">Spend</span></th>
                        <th data-sort="number"><span class="sort-title">Leads</span></th>
                        <th data-sort="number"><span class="sort-title">Ordered Leads</span></th>
                        <th data-sort="number"><span class="sort-title">Lead → Order %</span></th>
                        <th data-sort="number"><span class="sort-title">Cost / Lead</span></th>
                        <th data-sort="number"><span class="sort-title">Cost / Order</span></th>
                        <th data-sort="number"><span class="sort-title">Clicks</span></th>
                        <th data-sort="number"><span class="sort-title">Impressions</span></th>
                        <th data-sort="number"><span class="sort-title">CTR</span></th>
                        <th data-sort="number"><span class="sort-title">Avg CPC</span></th>
                        <th data-sort="number"><span class="sort-title">Google Conversions</span></th>
                        <th data-sort="text"><span class="sort-title">Recommendation</span></th>
                        <th data-sort="text"><span class="sort-title">Action</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $idx => $row): ?>
                        <tr>
                            <td>
                                <div class="campaign-name"><?php echo gc_h($row['campaign']); ?></div>
                                <?php if (!empty($row['google_campaign_names'])): ?>
                                    <div class="subtext"><?php echo gc_h(implode(', ', $row['google_campaign_names'])); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?php echo gc_h($row['status_class']); ?>"><?php echo gc_h($row['status']); ?></span></td>
                            <td><span class="health-pill <?php echo gc_h($row['health_class']); ?>"><?php echo (int)$row['health_score']; ?> · <?php echo gc_h($row['health_label']); ?></span></td>
                            <td><span class="priority-pill <?php echo gc_h($row['priority_class']); ?>"><?php echo gc_h($row['priority']); ?></span></td>
                            <td><span class="trend-pill <?php echo gc_h($row['trend_class']); ?>"><?php echo gc_h($row['trend']); ?></span></td>
                            <td>N/A</td>
                            <td><?php echo gc_money($row['spend']); ?></td>
                            <td><?php echo number_format((int)$row['leads']); ?></td>
                            <td><?php echo number_format((int)$row['ordered']); ?></td>
                            <td><?php echo gc_metric_percent($row['order_rate']); ?></td>
                            <td><?php echo gc_metric_money($row['cost_per_lead']); ?></td>
                            <td><?php echo gc_metric_money($row['cost_per_order']); ?></td>
                            <td><?php echo number_format((int)$row['clicks']); ?></td>
                            <td><?php echo number_format((int)$row['impressions']); ?></td>
                            <td><?php echo gc_metric_percent($row['ctr']); ?></td>
                            <td><?php echo gc_metric_money($row['avg_cpc']); ?></td>
                            <td><?php echo gc_metric_number($row['google_conversions'], 1); ?></td>
                            <td><span class="recommend"><?php echo gc_h($row['recommendation']); ?></span></td>
                            <td>
                                <div class="action-stack">
                                    <button class="btn btn-outline-primary btn-detail" type="button" data-row="<?php echo (int)$idx; ?>">View Details</button>
                                    <button class="btn btn-outline-secondary" type="button">View Search Terms</button>
                                    <button class="btn btn-outline-dark" type="button">Mark for Review</button>
                                    <button class="btn btn-outline-danger" type="button" disabled title="Google Ads action API not connected yet.">Pause Campaign</button>
                                    <button class="btn btn-outline-warning" type="button" disabled title="Google Ads action API not connected yet.">Reduce Budget</button>
                                    <button class="btn btn-outline-success" type="button" disabled title="Google Ads action API not connected yet.">Increase Budget</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Charts</h2>
        </div>
        <div class="chart-grid">
            <div class="chart-card"><canvas id="spendOrdersChart"></canvas></div>
            <div class="chart-card"><canvas id="cpoChart"></canvas></div>
            <div class="chart-card"><canvas id="leadsOrdersChart"></canvas></div>
            <div class="chart-card"><canvas id="dailySpendChart"></canvas></div>
            <div class="chart-card"><canvas id="dailyOrdersChart"></canvas></div>
            <div class="chart-card"><canvas id="healthDistributionChart"></canvas></div>
            <div class="chart-card"><canvas id="dailyCpoChart"></canvas></div>
        </div>
        <div id="chartFallback" class="alert alert-light border mt-3 d-none">Charts could not load. The campaign table above has the same numbers.</div>
    </section>

    <section class="panel">
        <div class="panel-title">
            <h2>Unmatched Data</h2>
            <span class="subtext">Use this to fix campaign naming mismatches</span>
        </div>
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <h3 class="h6 fw-bold">Google Ads campaigns with spend but no matching CRM campaign name</h3>
                <?php if (!$unmatchedAds): ?><div class="subtext">No unmatched Google Ads campaigns.</div><?php endif; ?>
                <?php foreach ($unmatchedAds as $item): ?>
                    <div class="alert-item"><strong><?php echo gc_h($item['campaign']); ?></strong><span><?php echo gc_money($item['spend']); ?></span></div>
                    <div class="subtext">Fix suggestion: add this campaign ID/name to <code>CampaignIdMap.php</code> or align CRM Column_1.</div>
                <?php endforeach; ?>
            </div>
            <div class="col-12 col-lg-6">
                <h3 class="h6 fw-bold">CRM campaign names with leads but no matching Google Ads campaign</h3>
                <?php if (!$unmatchedCrm): ?><div class="subtext">No unmatched CRM campaigns.</div><?php endif; ?>
                <?php foreach ($unmatchedCrm as $item): ?>
                    <div class="alert-item"><strong><?php echo gc_h($item['campaign']); ?></strong><span><?php echo number_format((int)$item['leads']); ?> leads</span></div>
                    <div class="subtext">Fix suggestion: CRM campaign name may differ by spacing, spelling, or one character from the Google Ads mapping.</div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<div class="drawer-backdrop" id="drawerBackdrop"></div>
<aside class="drawer" id="detailDrawer" aria-hidden="true">
    <div class="drawer-head">
        <div>
            <h2 class="h5 fw-bold mb-1" id="drawerTitle">Campaign Details</h2>
            <div class="subtext" id="drawerSub"></div>
        </div>
        <button class="btn btn-sm btn-outline-dark" type="button" id="drawerClose">Close</button>
    </div>
    <div class="drawer-body">
        <div class="detail-grid" id="drawerMetrics"></div>
        <h3 class="h6 fw-bold mt-4">Daily Breakdown</h3>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Spend</th><th>Leads</th><th>Orders</th><th>CPL</th><th>CPO</th></tr></thead>
                <tbody id="drawerDaily"></tbody>
            </table>
        </div>
        <h3 class="h6 fw-bold mt-4">Top Keywords</h3>
        <div class="alert alert-light border">Keyword-level Google Ads data is not synced yet.</div>
        <h3 class="h6 fw-bold mt-4">Top Search Terms</h3>
        <div class="alert alert-light border">Search-term data is not synced yet.</div>
        <h3 class="h6 fw-bold mt-4">Recent Leads</h3>
        <div class="alert alert-light border">Recent lead drill-down can be connected after adding a campaign lead endpoint.</div>
    </div>
</aside>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const campaignRows = <?php echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const dailyByCampaign = <?php echo json_encode($dailyByCampaign, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const charts = {
  labels: <?php echo json_encode($chartLabels); ?>,
  spend: <?php echo json_encode($chartSpend); ?>,
  orders: <?php echo json_encode($chartOrders); ?>,
  leads: <?php echo json_encode($chartLeads); ?>,
  cpo: <?php echo json_encode($chartCpo); ?>,
  healthDistribution: <?php echo json_encode($chartHealth); ?>,
  dailyLabels: <?php echo json_encode($dailyLabels); ?>,
  dailySpend: <?php echo json_encode($dailySpend); ?>,
  dailyOrders: <?php echo json_encode($dailyOrders); ?>,
  dailyCpo: <?php echo json_encode($dailyCpo); ?>
};

function money(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
  return '₹' + Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 });
}
function percent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
  return Number(value).toFixed(1) + '%';
}

function setupAutoFilters() {
  const form = document.querySelector('form.filters');
  const period = document.getElementById('period');
  const campaign = document.getElementById('campaign');
  const customDateInputs = form?.querySelectorAll('input[type="date"][name="from"], input[type="date"][name="to"]');
  if (!form || !period) return;

  const rememberScroll = () => {
    sessionStorage.setItem('gadsConversionScrollY', String(window.scrollY));
  };

  const submitWhenPreset = () => {
    if (period.value === 'custom') return;
    rememberScroll();
    form.requestSubmit ? form.requestSubmit() : form.submit();
  };

  form.addEventListener('submit', rememberScroll);
  period.addEventListener('change', submitWhenPreset);
  campaign?.addEventListener('change', submitWhenPreset);
  customDateInputs?.forEach(input => {
    input.addEventListener('change', () => {
      period.value = 'custom';
    });
  });
}

function restoreFilterScroll() {
  const savedScrollY = sessionStorage.getItem('gadsConversionScrollY');
  if (savedScrollY === null) return;

  sessionStorage.removeItem('gadsConversionScrollY');
  requestAnimationFrame(() => {
    window.scrollTo({ top: Number(savedScrollY) || 0, left: 0, behavior: 'instant' });
  });
}

function getSortableCellValue(row, index, type) {
  const cell = row.cells[index];
  const raw = (cell?.textContent || '').trim();
  if (type !== 'number') return raw.toLocaleLowerCase('en-IN');

  if (!raw || /^n\/a$/i.test(raw)) return null;
  const firstNumber = raw.replace(/,/g, '').match(/-?\d+(?:\.\d+)?/);
  return firstNumber ? Number(firstNumber[0]) : null;
}

function compareSortableValues(a, b, direction) {
  if (a === null && b === null) return 0;
  if (a === null) return 1;
  if (b === null) return -1;
  if (typeof a === 'number' && typeof b === 'number') return (a - b) * direction;
  return String(a).localeCompare(String(b), 'en-IN', { numeric: true, sensitivity: 'base' }) * direction;
}

function setupCampaignTableSorting() {
  const table = document.getElementById('campaignPerformanceTable');
  const tbody = table?.tBodies[0];
  if (!table || !tbody) return;
  const sortStorageKey = 'gadsConversionCampaignSort';

  Array.from(tbody.rows).forEach((row, index) => {
    row.dataset.originalIndex = String(index);
  });

  const headers = Array.from(table.querySelectorAll('thead th[data-sort]'));

  const sortColumn = (th, index, forceDirection = null, persist = true) => {
    const isAscending = forceDirection ? forceDirection === 'asc' : !th.classList.contains('sort-asc');
    const direction = isAscending ? 1 : -1;
    const type = th.dataset.sort || 'text';

    headers.forEach(header => {
      header.classList.remove('sort-asc', 'sort-desc');
      header.setAttribute('aria-sort', 'none');
    });

    th.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    th.setAttribute('aria-sort', isAscending ? 'ascending' : 'descending');

    const sortedRows = Array.from(tbody.rows).sort((rowA, rowB) => {
      const result = compareSortableValues(
        getSortableCellValue(rowA, index, type),
        getSortableCellValue(rowB, index, type),
        direction
      );
      return result || (Number(rowA.dataset.originalIndex) - Number(rowB.dataset.originalIndex));
    });

    sortedRows.forEach(row => tbody.appendChild(row));

    if (persist) {
      sessionStorage.setItem(sortStorageKey, JSON.stringify({
        index,
        direction: isAscending ? 'asc' : 'desc'
      }));
    }
  };

  headers.forEach((th, index) => {
    th.setAttribute('role', 'button');
    th.setAttribute('tabindex', '0');
    th.setAttribute('aria-sort', 'none');

    th.addEventListener('click', () => sortColumn(th, index));
    th.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        sortColumn(th, index);
      }
    });
  });

  try {
    const savedSort = JSON.parse(sessionStorage.getItem(sortStorageKey) || 'null');
    if (
      savedSort &&
      Number.isInteger(savedSort.index) &&
      headers[savedSort.index] &&
      ['asc', 'desc'].includes(savedSort.direction)
    ) {
      sortColumn(headers[savedSort.index], savedSort.index, savedSort.direction, false);
    }
  } catch (error) {
    sessionStorage.removeItem(sortStorageKey);
  }
}

setupAutoFilters();
restoreFilterScroll();
setupCampaignTableSorting();
function renderCharts() {
  if (!window.Chart) {
    document.getElementById('chartFallback')?.classList.remove('d-none');
    return;
  }
  const baseOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
  new Chart(document.getElementById('spendOrdersChart'), {
    type: 'bar',
    data: { labels: charts.labels, datasets: [{ label: 'Spend', data: charts.spend, backgroundColor: '#2563eb' }, { label: 'Ordered Leads', data: charts.orders, backgroundColor: '#0f9f7a' }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Spend vs Ordered Leads by Campaign' } } }
  });
  new Chart(document.getElementById('cpoChart'), {
    type: 'bar',
    data: { labels: charts.labels, datasets: [{ label: 'Cost Per Order', data: charts.cpo, backgroundColor: '#c2410c' }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Cost Per Order by Campaign' } } }
  });
  new Chart(document.getElementById('leadsOrdersChart'), {
    type: 'bar',
    data: { labels: charts.labels, datasets: [{ label: 'Leads', data: charts.leads, backgroundColor: '#64748b' }, { label: 'Orders', data: charts.orders, backgroundColor: '#0f9f7a' }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Leads vs Orders by Campaign' } } }
  });
  new Chart(document.getElementById('dailySpendChart'), {
    type: 'line',
    data: { labels: charts.dailyLabels, datasets: [{ label: 'Daily Spend', data: charts.dailySpend, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .3 }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Daily Spend Trend' } } }
  });
  new Chart(document.getElementById('dailyOrdersChart'), {
    type: 'line',
    data: { labels: charts.dailyLabels, datasets: [{ label: 'Daily Orders', data: charts.dailyOrders, borderColor: '#0f9f7a', backgroundColor: 'rgba(15,159,122,.12)', fill: true, tension: .3 }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Order Trend' } } }
  });
  new Chart(document.getElementById('healthDistributionChart'), {
    type: 'doughnut',
    data: { labels: ['Excellent', 'Healthy', 'Needs Attention'], datasets: [{ label: 'Campaigns', data: charts.healthDistribution, backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'] }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Campaign Health Distribution' } } }
  });
  new Chart(document.getElementById('dailyCpoChart'), {
    type: 'line',
    data: { labels: charts.dailyLabels, datasets: [{ label: 'Cost Per Order', data: charts.dailyCpo, borderColor: '#c2410c', backgroundColor: 'rgba(194,65,12,.12)', fill: true, tension: .3 }] },
    options: { ...baseOptions, plugins: { ...baseOptions.plugins, title: { display: true, text: 'Cost Per Order Trend' } } }
  });
}

function openDrawer(index) {
  const row = campaignRows[index];
  if (!row) return;
  document.getElementById('drawerTitle').textContent = row.campaign || 'Campaign Details';
  document.getElementById('drawerSub').textContent = row.recommendation || '';
  const metrics = [
    ['Spend', money(row.spend)],
    ['Leads', Number(row.leads || 0).toLocaleString('en-IN')],
    ['Ordered Leads', Number(row.ordered || 0).toLocaleString('en-IN')],
    ['Cost/Lead', money(row.cost_per_lead)],
    ['Cost/Order', money(row.cost_per_order)],
    ['Health Score', `${row.health_score} · ${row.health_label}`],
    ['Recommendation', row.recommendation || 'Review'],
    ['Clicks', Number(row.clicks || 0).toLocaleString('en-IN')],
    ['Impressions', Number(row.impressions || 0).toLocaleString('en-IN')],
    ['CTR', percent(row.ctr)],
    ['Avg CPC', money(row.avg_cpc)],
    ['Google Conversions', Number(row.google_conversions || 0).toFixed(1)]
  ];
  document.getElementById('drawerMetrics').innerHTML = metrics.map(([label, value]) => `<div class="detail-cell"><span>${label}</span><strong>${value}</strong></div>`).join('');
  const daily = dailyByCampaign[row.key] || [];
  document.getElementById('drawerDaily').innerHTML = daily.length ? daily.map(day => {
    const leads = Number(day.leads || 0);
    const orders = Number(day.orders || 0);
    const spend = Number(day.spend || 0);
    return `<tr><td>${day.date}</td><td>${money(spend)}</td><td>${leads}</td><td>${orders}</td><td>${leads ? money(spend / leads) : 'N/A'}</td><td>${orders ? money(spend / orders) : 'N/A'}</td></tr>`;
  }).join('') : '<tr><td colspan="6">No daily data.</td></tr>';
  document.getElementById('drawerBackdrop').classList.add('open');
  document.getElementById('detailDrawer').classList.add('open');
  document.getElementById('detailDrawer').setAttribute('aria-hidden', 'false');
}
function closeDrawer() {
  document.getElementById('drawerBackdrop').classList.remove('open');
  document.getElementById('detailDrawer').classList.remove('open');
  document.getElementById('detailDrawer').setAttribute('aria-hidden', 'true');
}
document.querySelectorAll('.btn-detail').forEach(btn => btn.addEventListener('click', () => openDrawer(Number(btn.dataset.row))));
document.getElementById('drawerClose')?.addEventListener('click', closeDrawer);
document.getElementById('drawerBackdrop')?.addEventListener('click', closeDrawer);

const syncGoogleAdsBtn = document.getElementById('syncGoogleAdsBtn');
const syncGoogleAdsStatus = document.getElementById('syncGoogleAdsStatus');
syncGoogleAdsBtn?.addEventListener('click', async () => {
  const syncUrl = syncGoogleAdsBtn.getAttribute('data-sync-url');
  if (!syncUrl || syncGoogleAdsBtn.disabled) return;

  const originalText = syncGoogleAdsBtn.textContent;
  syncGoogleAdsBtn.disabled = true;
  syncGoogleAdsBtn.textContent = 'Syncing...';
  syncGoogleAdsStatus.className = 'sync-status';
  syncGoogleAdsStatus.textContent = 'Google Ads sync is running in the background. Please keep this page open.';

  try {
    const response = await fetch(syncUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const text = await response.text();
    if (!response.ok) throw new Error(text || `Sync failed with HTTP ${response.status}`);

    syncGoogleAdsStatus.className = 'sync-status success';
    syncGoogleAdsStatus.textContent = (text || 'Google Ads sync complete.').trim().split('\n')[0] + ' Refreshing numbers...';
    setTimeout(() => window.location.reload(), 1200);
  } catch (error) {
    syncGoogleAdsStatus.className = 'sync-status error';
    const message = error.message || 'Google Ads sync failed.';
    if (/invalid_grant|expired or revoked|refresh token/i.test(message)) {
      syncGoogleAdsStatus.innerHTML = 'Google Ads token expired or was revoked. <a href="google_ads_connect.php?action=clear_token" style="color:#991b1b;font-weight:800;text-decoration:underline;">Clear token</a>, then <a href="google_ads_connect.php?action=connect" style="color:#991b1b;font-weight:800;text-decoration:underline;">Reconnect Google Ads</a>.';
    } else {
      syncGoogleAdsStatus.textContent = message;
    }
    syncGoogleAdsBtn.disabled = false;
    syncGoogleAdsBtn.textContent = originalText;
  }
});
renderCharts();
</script>
</body>
</html>
