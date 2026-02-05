<?php
if ($_SERVER['HTTP_HOST'] === 'smartronic.online') {
    $servername = '127.0.0.1:3306';
    $username = 'u398852039_smartronic';
    $password = 'Chennai@40!';
    $database = 'u398852039_smartronic';
} else {
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $database = "smarthome";
}


// Connect to the database
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define date ranges for "today"
$todayStart = (new DateTime('today', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d 00:00:00');
$todayEnd = (new DateTime('today', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d 23:59:59');

// Query to get new visitors today
$newVisitorsTodayQuery = "SELECT COUNT(*) AS new_visitors 
                          FROM visitor_analytics 
                          WHERE is_returning = 0 AND visit_time BETWEEN '$todayStart' AND '$todayEnd'";
$newVisitorsTodayResult = $conn->query($newVisitorsTodayQuery);
$newVisitorsToday = $newVisitorsTodayResult->fetch_assoc()['new_visitors'] ?? 0;

// Query to get returning visitors today
$returningVisitorsTodayQuery = "SELECT COUNT(*) AS returning_visitors 
                                FROM visitor_analytics 
                                WHERE is_returning = 1 AND visit_time BETWEEN '$todayStart' AND '$todayEnd'";
$returningVisitorsTodayResult = $conn->query($returningVisitorsTodayQuery);
$returningVisitorsToday = $returningVisitorsTodayResult->fetch_assoc()['returning_visitors'] ?? 0;

// Fetch all visitor data for analytics (as per the original script)
$query = "SELECT id, ip_address, region, country, referer, user_agent, time_on_page, scroll_depth, visit_time 
          FROM visitor_analytics";
$result = $conn->query($query);

// Fetch visitor data
$query = "SELECT id, ip_address, region, country, referer, user_agent, time_on_page, scroll_depth, visit_time 
          FROM visitor_analytics";
$result = $conn->query($query);

// Prepare data for charts
$locations = [];
$referrers = [];
$devices = [];
$timeData = [];
$tableData = [
    'today' => [],
    'yesterday' => [],
    'last_week' => []
];

$today = new DateTime('today', new DateTimeZone('Asia/Kolkata'));
$yesterday = new DateTime('yesterday', new DateTimeZone('Asia/Kolkata'));
$sevenDaysAgo = new DateTime('-7 days', new DateTimeZone('Asia/Kolkata'));

while ($row = $result->fetch_assoc()) {
    // Convert time to IST
    $visitTime = new DateTime($row['visit_time'], new DateTimeZone('UTC'));
    $visitTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
    $formattedTime = $visitTime->format('d M Y, H:i:s');

    // Categorize by date
    if ($visitTime >= $today) {
        $tableData['today'][] = $row + ['formatted_time' => $formattedTime];
        $locations[] = $row['country'] . ', ' . $row['region'];
        $referrers[] = $row['referer'];
        $devices[] = $device;
    } elseif ($visitTime >= $yesterday) {
        $tableData['yesterday'][] = $row + ['formatted_time' => $formattedTime];
    } elseif ($visitTime >= $sevenDaysAgo) {
        $tableData['last_week'][] = $row + ['formatted_time' => $formattedTime];
    }

    // Parse user agent
    $parsedUA = parse_user_agent($row['user_agent']);
    $device = $parsedUA['platform'] . ' (' . $parsedUA['browser'] . ')';

    // Aggregate data
  //  $locations[] = $row['country'] . ', ' . $row['region'];
   // $referrers[] = $row['referer'];
   // $devices[] = $device;
    $timeData[] = [
        'time' => $formattedTime,
        'duration' => $row['time_on_page'],
        'scroll' => $row['scroll_depth']
    ];
}

// Function to parse user agent
function parse_user_agent($userAgent) {
    $browserList = [
        'Firefox' => '/Firefox/i',
        'Chrome' => '/Chrome/i',
        'Safari' => '/Safari/i',
        'Edge' => '/Edg/i',
        'Internet Explorer' => '/MSIE/i',
    ];

    $platformList = [
        'Windows' => '/Windows/i',
        'Mac' => '/Macintosh/i',
        'Linux' => '/Linux/i',
        'iPhone' => '/iPhone/i',
        'Android' => '/Android/i',
    ];

    $browser = 'Unknown';
    foreach ($browserList as $name => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            $browser = $name;
            break;
        }
    }

    $platform = 'Unknown';
    foreach ($platformList as $name => $pattern) {
        if (preg_match($pattern, $userAgent)) {
            $platform = $name;
            break;
        }
    }

    return [
        'browser' => $browser,
        'platform' => $platform,
    ];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1, h2 {
            text-align: center;
        }

        .visitor-summary {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .charts {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .chart-container {
            width: 48%;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .chart-container {
                width: 100%;
            }
            #visitorData {
                width: 100%;
                overflow: auto;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .group-header {
            background-color: #007bff;
            color: #fff;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Visitor Analytics Dashboard</h1>
    <div class="visitor-summary">
        <span>New Visitors Today: <?php echo $newVisitorsToday; ?></span>
        <span>Returning Visitors Today: <?php echo $returningVisitorsToday; ?></span>
    </div>
    <div class="charts">
        <div class="chart-container">
            <canvas id="locationChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="referrerChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="deviceChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="timeChart"></canvas>
        </div>
    </div>

    <h2>Visitor Data</h2>
    <div id="visitorData"> 
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>IP Address</th>
                    <th>Region</th>
                    <th>Country</th>
                    <th>Referrer</th>
                    <th>Time on Page (s)</th>
                    <th>Scroll Depth (%)</th>
                    <th>Visit Time (IST)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="group-header">
                    <td colspan="8">Today's Visitors</td>
                </tr>
                <?php foreach ($tableData['today'] as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['ip_address']; ?></td>
                        <td><?php echo $row['region']; ?></td>
                        <td><?php echo $row['country']; ?></td>
                        <td><?php echo $row['referer']; ?></td>
                        <td><?php echo $row['time_on_page']; ?></td>
                        <td><?php echo $row['scroll_depth']; ?></td>
                        <td><?php echo $row['formatted_time']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-header">
                    <td colspan="8">Yesterday's Visitors</td>
                </tr>
                <?php foreach ($tableData['yesterday'] as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['ip_address']; ?></td>
                        <td><?php echo $row['region']; ?></td>
                        <td><?php echo $row['country']; ?></td>
                        <td><?php echo $row['referer']; ?></td>
                        <td><?php echo $row['time_on_page']; ?></td>
                        <td><?php echo $row['scroll_depth']; ?></td>
                        <td><?php echo $row['formatted_time']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-header">
                    <td colspan="8">Last Week's Visitors</td>
                </tr>
                <?php foreach ($tableData['last_week'] as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['ip_address']; ?></td>
                        <td><?php echo $row['region']; ?></td>
                        <td><?php echo $row['country']; ?></td>
                        <td><?php echo $row['referer']; ?></td>
                        <td><?php echo $row['time_on_page']; ?></td>
                        <td><?php echo $row['scroll_depth']; ?></td>
                        <td><?php echo $row['formatted_time']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        const locationData = <?php echo json_encode(array_count_values($locations)); ?>;
        const referrerData = <?php echo json_encode(array_count_values($referrers)); ?>;
        const deviceData = <?php echo json_encode(array_count_values($devices)); ?>;
        const timeData = <?php echo json_encode($timeData); ?>;

        // Location Chart
        new Chart(document.getElementById('locationChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(locationData),
                datasets: [{
                    label: 'Visitors by Location',
                    data: Object.values(locationData),
                    backgroundColor: ['#ff6384', '#36a2eb', '#cc65fe', '#ffce56']
                }]
            }
        });

        // Referrer Chart
        new Chart(document.getElementById('referrerChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(referrerData),
                datasets: [{
                    label: 'Visitors by Referrer',
                    data: Object.values(referrerData),
                    backgroundColor: ['#36a2eb', '#cc65fe', '#ffce56', '#ff6384']
                }]
            }
        });

        // Device Chart
        new Chart(document.getElementById('deviceChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(deviceData),
                datasets: [{
                    label: 'Visitors by Device',
                    data: Object.values(deviceData),
                    backgroundColor: ['#cc65fe', '#ffce56', '#36a2eb', '#ff6384']
                }]
            },
            options: {
                indexAxis: 'y'
            }
        });

        // Time Chart
        const timeChartLabels = timeData.map(data => data.time);
        const timeChartDurations = timeData.map(data => data.duration);
        const timeChartScrolls = timeData.map(data => data.scroll);
        new Chart(document.getElementById('timeChart'), {
            type: 'line',
            data: {
                labels: timeChartLabels,
                datasets: [
                    {
                        label: 'Time on Page (s)',
                        data: timeChartDurations,
                        borderColor: '#36a2eb',
                        fill: false
                    },
                    {
                        label: 'Scroll Depth (%)',
                        data: timeChartScrolls,
                        borderColor: '#ff6384',
                        fill: false
                    }
                ]
            }
        });
    </script>
</body>
</html>
