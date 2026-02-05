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

          <link rel="stylesheet" href="css/styles.css">

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
        echo "<div class='tile-container'>";
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dvr = ($row['dvr_type'] === 'dont-know') ? 'X' : $row['dvr_type'];
                $hdd_size = ($row['hdd_size'] === 'dont-know') ? 'X' : $row['hdd_size'];
                $camera_resolution = ($row['camera_resolution'] === 'dont-know') ? 'X' : $row['camera_resolution'];
                echo "<div class='tile'>
                        <div class='row'>
                            
                             <div class='ele red'>{$row['id']} | </div>
                               <div class='ele'> {$row['num_cameras']} |
                                {$dvr} |
                                {$hdd_size} |
                                {$camera_resolution} |</div>

                              <div class='ele'> 
                                <form method='post' onsubmit='return confirmDelete();'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit' name='delete'>🗑️</button>
                                </form>
                            </div>
                        </div>
                   
                        <div class='row'>
          
                        
                        
                        
                            <a href='tel:{$row['whatsapp_number']}'>{$row['whatsapp_number']}</a>
                        </div>
                        
                        <div class='row'>
                            <form method='post' style='display: inline;'>
                                <textarea name='comment' rows='2' cols='20'>" . htmlspecialchars($row['comments']) . "</textarea>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <button type='submit' name='update_comment'>💾</button>
                            </form>
                        </div>
                        
              
                </div>";
            }
        } else {
            echo "No records found";
        }
        echo "</div>";
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
