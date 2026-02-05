<?php
// Database connection
$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete request
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM wp_cctv_requirements WHERE id = $id");
    echo "<script>showMessage('Row deleted successfully!');</script>";
}

// Handle comment update request
if (isset($_POST['update_comment'])) {
    $id = intval($_POST['id']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $conn->query("UPDATE wp_cctv_requirements SET comments = '$comment' WHERE id = $id");
    echo "<script>showMessage('Comment updated successfully!');</script>";
}

// Fetch rows grouped by date
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

$todayDateFormatted = date('d M Y'); // Format for Today's Records
$yesterdayDateFormatted = date('d M Y', strtotime('-1 day')); // Format for Yesterday's Records

$todayResult = $conn->query("SELECT * FROM wp_cctv_requirements WHERE DATE(created_at) = '$today'");
$yesterdayResult = $conn->query("SELECT * FROM wp_cctv_requirements WHERE DATE(created_at) = '$yesterday'");
$last7DaysResult = $conn->query("SELECT * FROM wp_cctv_requirements WHERE DATE(created_at) > '$sevenDaysAgo' AND DATE(created_at) < '$yesterday'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage CCTV Requirements</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            text-align: center;
            margin-top: 30px;
        }
        table {
            width: 60%;
            margin: 20px auto;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            width: 12%;
        }
        .comments {
            width: 25%;
        }
        .phone-number {
            font-weight: bold;
            color: #007bff;
            
        }
        thead {
            background-color: #508fc1;
            color: #fff;
        }
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        .row-highlight {
            background-color: #dff0d8; /* Highlighted color for rows with comments */
        }
        .actions button {
            border: none;
            background: none;
            cursor: pointer;
        }
        .overlay-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            z-index: 1000;
            display: none;
        }
        @media screen and (max-width: 768px) {
            table {
                width: 100%;
            }
            th, td {
                font-size: 14px;
            }
        }

        button {
            background: none;
            border: 0;
            cursor: pointer;
        }

        td {
            border: 0;
            border-bottom: 1px solid #bbb;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this record?');
        }

        function showMessage(message) {
            const overlay = document.createElement('div');
            overlay.className = 'overlay-message';
            overlay.innerText = message;
            document.body.appendChild(overlay);
            overlay.style.display = 'block';
            setTimeout(() => overlay.remove(), 3000); // Message disappears after 3 seconds
        }
    </script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    
<script>
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
      appId: "e1989b5e-8fd4-454b-8ab5-249269f58c41",
      safari_web_id: "web.onesignal.auto.5462a642-4744-4944-be08-d03aa1430cc8",
      notifyButton: {
        enable: true,
      },
      subdomainName: "smartronic",
    });
  });
</script>
</head>
<body>
    <h1>Manage CCTV Requirements</h1>
    
    <?php
    function displayTable($result, $heading, $date) {
        echo "<h2>$heading ($date)</h2>";
        echo "<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>#Cams</th>
                        <th>Type</th>
                        <th>HDD</th>
                        <th>Resolution</th>
                        <th>Phone</th>
                        <th class='comments'>Comments</th>
                        <th>Trash?</th>
                    </tr>
                </thead>
                <tbody>";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr class='" . (!empty($row['comments']) ? 'row-highlight' : '') . "'>
                        <td>{$row['id']}</td>
                        <td>{$row['num_cameras']}</td>
                        <td>{$row['dvr_type']}</td>
                        <td>{$row['hdd_size']}</td>
                        <td>{$row['camera_resolution']}</td>
                        <td class='phone-number'>
                            <a href='tel:{$row['whatsapp_number']}'>{$row['whatsapp_number']}</a>
                        </td>
                        <td>
                            <form method='post' style='display: inline;'>
                                <textarea name='comment' rows='2' cols='20'>" . htmlspecialchars($row['comments']) . "</textarea>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit' name='update_comment'>💾</button>
                            </form>
                        </td>
                        <td>
                            <form method='post' onsubmit='return confirmDelete();'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit' name='delete'>🗑️</button>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No records found</td></tr>";
        }
        echo "</tbody></table>";
    }

    displayTable($todayResult, "Today's Records", $todayDateFormatted);
    displayTable($yesterdayResult, "Yesterday's Records", $yesterdayDateFormatted);
    displayTable($last7DaysResult, "Last 7 Days' Records", "Past Week");
    ?>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
