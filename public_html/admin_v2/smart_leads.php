<?php
$admin_password = "5m@rt@2024";
if (!isset($_POST['password']) || $_POST['password'] !== $admin_password) {
    echo '<form method="POST">
            <h3>Enter Password to Access CCTV Requirements</h3>
            <input type="password" name="password" required>
            <button type="submit">Submit</button>
          </form>';
    exit;
}

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

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM wp_cctv_requirements ORDER BY id DESC";
$result = $conn->query($sql);

$data_by_date = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $created_at = $row['created_at'] ?? null;
        $date = $created_at ? date("F j, Y (l)", strtotime($created_at)) : "Unknown Date";
        $data_by_date[$date][] = $row;
    }
} else {
    echo "No records found.";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>CCTV Requirements</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        tr:nth-child(even) td {
            background-color: #f9f9f9;
        }
        tr:nth-child(odd) td {
            background-color: #fff;
        }
        .date-header {
            background-color: #0073aa;
            color: #fff;
            padding: 10px;
            font-size: 18px;
        }
        .load-more {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            margin-top: 20px;
            border-radius: 5px;
        }
        .date-group {
            display: none;
        }
        .date-group.visible {
            display: block;
        }
    </style>
</head>
<body>

<h2>All CCTV Requirements</h2>

<div id="data-container">
<?php
$date_keys = array_keys($data_by_date);
$index = 0;
foreach ($data_by_date as $date => $rows) {
    $visible_class = ($index === 0) ? "visible" : "";
    echo "<div class='date-group $visible_class'>";
    echo "<div class='date-header'>$date</div>";
    echo "<table><tr>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table></div>";
    $index++;
}
?>

<?php if (count($data_by_date) > 1): ?>
    <button id="loadMoreBtn" class="load-more" onclick="loadNextDate()">Load More</button>
<?php endif; ?>
</div>

<script>
let currentGroup = 1;

function loadNextDate() {
    const groups = document.querySelectorAll('.date-group');
    if (currentGroup < groups.length) {
        groups[currentGroup].classList.add('visible');
        currentGroup++;
        if (currentGroup === groups.length) {
            document.getElementById('loadMoreBtn').style.display = 'none';
        }
    }
}
</script>

</body>
</html>
