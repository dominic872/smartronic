<?php
$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT phone, quantity, product, storage, resolution, name, owner, note, area, date, location, map FROM orders";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: scale(1.01);
        }
        .card h2 {
            margin-top: 0;
            font-size: 24px;
            color: #333;
            text-transform: uppercase;
        }
        .card p {
            margin: 8px 0;
            color: #555;
        }
        .icon-label {
            margin-right: 8px;
            color: #666;
            width: 22px;
            display: inline-block;
            text-align: center;
        }
        .container {
            max-width: 1000px;
            margin: auto;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>All Orders</h1>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <?php
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['date']);
                $formattedDate = $dateObj ? $dateObj->format('d-m-y') : 'Invalid date';
            ?>
            <div class="card">
                <h2><?= htmlspecialchars($row['name']) ?></h2>
                <p><span class="icon-label"><i class="fas fa-phone"></i></span><?= htmlspecialchars($row['phone']) ?></p>
                <p><span class="icon-label"><i class="fas fa-box"></i></span><?= htmlspecialchars($row['product']) ?> (<?= htmlspecialchars($row['quantity']) ?>)</p>
                <p><span class="icon-label"><i class="fas fa-hdd"></i></span><?= htmlspecialchars($row['storage']) ?></p>
                <p><span class="icon-label"><i class="fas fa-image"></i></span><?= htmlspecialchars($row['resolution']) ?></p>
                <p><span class="icon-label"><i class="fas fa-user"></i></span><?= htmlspecialchars($row['owner']) ?></p>
                <p><span class="icon-label"><i class="fas fa-sticky-note"></i></span><?= nl2br(htmlspecialchars($row['note'])) ?></p>
                <p><span class="icon-label"><i class="fas fa-map-marker-alt"></i></span><?= htmlspecialchars($row['area']) ?></p>
                <p><span class="icon-label"><i class="fas fa-calendar-alt"></i></span><?= $formattedDate ?></p>
                <p><span class="icon-label"><i class="fas fa-location-dot"></i></span><?= htmlspecialchars($row['location']) ?></p>
                <p><span class="icon-label"><i class="fas fa-link"></i></span>
                    <a href="<?= htmlspecialchars($row['map']) ?>" target="_blank">
                        <?= htmlspecialchars($row['map']) ?>
                    </a>
                </p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>

</div>

</body>
</html>

<?php $conn->close(); ?>
 