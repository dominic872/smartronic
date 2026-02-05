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


// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM wp_cctv_requirements";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCTV Requirements</title>
    <link rel="stylesheet" href="css/styles-cards.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        function populateFollowUpOptions() {
            let selects = document.querySelectorAll(".follow-up-select");
            selects.forEach(select => {
                select.innerHTML = ""; // Clear existing options
                let options = ["FOLLOW UP", "Morning", "Afternoon", "Evening", "Night", "Tomorrow"];
                let currentDate = new Date();
                for (let i = 0; i < 7; i++) {
                    currentDate.setDate(currentDate.getDate() + 1);
                    options.push(currentDate.toDateString());
                }
                options.push(new Date().toLocaleString('default', { month: 'long' }));
                options.forEach(option => {
                    let opt = document.createElement("option");
                    opt.textContent = option;
                    select.appendChild(opt);
                });
            });
        }
        window.onload = populateFollowUpOptions;
    </script>
</head>
<body>
    <div class="container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <div class="card-head">
                    <div class="product-properties">
                        <span class="product-price">
                            <i class="fa fa-phone"></i> <a href="tel:<?php echo htmlspecialchars($row['whatsapp_number']); ?>"><b><?php echo htmlspecialchars($row['whatsapp_number']); ?></b></a>
                        </span>
                    </div>
                    <div class="call-status">
                        <span class="badge btn "><i class="fa fa-times"></i> DROP</span>
                        <span class="badge btn "><i class="fa fa-phone"></i> CALL AGAIN</span>
                        <span class="badge btn ">
                            <i class="fa fa-calendar"></i> 
                            <select class="follow-up-select"></select>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="product-desc">
                        <span class="product-title">
                        <div class="product-detail">
                            <h2>
                            <i class="fa fa-paper-plane"></i>
                                <?php 
                                    echo htmlspecialchars($row['num_cameras']) . " | " . 
                                        htmlspecialchars($row['camera_resolution']) . " | " . 
                                        htmlspecialchars($row['hdd_size']) . " | " . 
                                        htmlspecialchars($row['dvr_type']); 
                                ?>
                            </h2>
                            <span class="badge">New</span>
                        </div>
                        </span>
                        <span class="product-caption">
                        <textarea>Name: 
Area:
                        </textarea>
                        <span class="badge btn submit"><i class="fa fa-check"></i></span>
                        </span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>
