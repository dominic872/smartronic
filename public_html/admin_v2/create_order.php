<?php

//*** revert to this working version */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1:3306';
$username = 'u398852039_smartronic';
$password = 'Chennai@40!';
$database = 'u398852039_smartronic';

// Prepare default values
$prefilled = array_fill(0, 12, "");

// Parse query string
if (isset($_GET['quote'])) {
    $parts = array_map('trim', explode('|', $_GET['quote']));
    for ($i = 0; $i < count($parts); $i++) {
        $prefilled[$i] = $parts[$i];
    }
}

$editMode = false;
if (isset($_GET['edit']) && $_GET['edit'] === 'true' && isset($_GET['idno'])) {
    $idno = $_GET['idno'];
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT idno, phone, price, quantity, product, storage, resolution, name, area, note, Owner, date, location, Map FROM orders WHERE idno = ?");
    $stmt->bind_param("s", $idno);
    $stmt->execute();

    $prefilled = array_fill(0, 14, "");
    $stmt->bind_result(
        $prefilled[0], $prefilled[1], $prefilled[2], $prefilled[3], $prefilled[4],
        $prefilled[5], $prefilled[6], $prefilled[7], $prefilled[8], $prefilled[9],
        $prefilled[10], $prefilled[11], $prefilled[12], $prefilled[13]
    );

    $stmt->fetch();
    $stmt->close();
    $conn->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editMode = isset($_POST['idno']) && $_POST['idno'] !== '';
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

$phone = $_POST['phone'] ?? '';
$quantity = $_POST['quantity'] ?? '';
$product = $_POST['product'] ?? '';
$storage = $_POST['storage'] ?? '';
$resolution = $_POST['resolution'] ?? '';
$name = $_POST['name'] ?? '';
$owner = $_POST['owner'] ?? '';
$note = $_POST['note'] ?? '';
$area = $_POST['area'] ?? '';
$date = $_POST['date'] ?? '';
$location = $_POST['location'] ?? '';
$map = $_POST['map'] ?? '';
$price = $_POST['price'] ?? ''; // <--- ADD THIS
$idno = $_POST['idno'] ?? '';
$amount_paid = $_POST['amount_paid'] ?? 0;
$fully_paid = isset($_POST['fully_paid']) ? 1 : 0;

$stmt = $conn->prepare("INSERT INTO orders 
    (idno, phone, price, quantity, product, storage, resolution, name, area, note, Owner, date, location, Map, amount_paid, fully_paid)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        phone = VALUES(phone),
        price = VALUES(price),
        quantity = VALUES(quantity),
        product = VALUES(product),
        storage = VALUES(storage),
        resolution = VALUES(resolution),
        name = VALUES(name),
        area = VALUES(area),
        note = VALUES(note),
        Owner = VALUES(Owner),
        date = VALUES(date),
        location = VALUES(location),
        Map = VALUES(Map)
");


$stmt->bind_param("sssissssssssssdi", 
    $idno, $phone, $price, $quantity, $product, $storage, $resolution,
    $name, $area, $note, $owner, $date, $location, $map, $amount_paid, $fully_paid
);



    if ($stmt->execute()) {
        echo "<p style='color:green;text-align:center;'>Order saved successfully!</p>";
    } else {
        echo "<p style='color:red;text-align:center;'>Error: " . $stmt->error . "</p>";
    }

   


    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Order</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 1rem;
            background-color: #f2f2f2;
        }
        .container {
            max-width: 400px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        label {
            margin-top: 1rem;
            display: block;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], input[type="date"], select {
            width: 100%;
            padding: 0.6rem;
            margin-top: 0.4rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.8rem;
            background: #007BFF;
            color: white;
            border: none;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <form method="POST">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($prefilled[0]) ?>" required>

        <label>Quantity</label>
        <input type="number" name="quantity" value="<?= htmlspecialchars($prefilled[1]) ?>" required>

        <label>DVR Type</label>
        <select name="product" required>
            <option value="DVR" <?= $prefilled[2] === 'DVR' ? 'selected' : '' ?>>DVR</option>
            <option value="NVR" <?= $prefilled[2] === 'NVR' ? 'selected' : '' ?>>NVR</option>
             <option value="WIFI">WIFI</option>
        </select>

        <label>Storage</label>
        <select name="storage" required>
            <?php
            $storages = ['64GB', '128GB', '256GB', '500GB', '1 TB', '2 TB', '3 TB', '4 TB'];
            foreach ($storages as $s) {
                $sel = $prefilled[3] === $s ? 'selected' : '';
                echo "<option value=\"$s\" $sel>$s</option>";
            }
            ?>
        </select>

        <label>Resolution</label>
        <select name="resolution" required>
            <option value="2 MP" <?= $prefilled[4] === '2 MP' ? 'selected' : '' ?>>2 MP</option>
            <option value="4 MP" >5 MP</option>
            <option value="5 MP" <?= $prefilled[4] === '5 MP' ? 'selected' : '' ?>>5 MP</option>
        </select>

        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($prefilled[5]) ?>">

        <label>Owner</label>
        <input type="text" name="owner" value="<?= htmlspecialchars($prefilled[6]) ?>">

        <label>Note</label>
        <input type="text" name="note" value="<?= htmlspecialchars($prefilled[7]) ?>">

        <label>Area</label>
        <input type="text" name="area" value="<?= htmlspecialchars($prefilled[9]) ?>">

        <label>Date</label>
        <input type="date" name="date" id="date">

        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($prefilled[10]) ?>">

        <label>Map Link</label>
        <input type="text" name="map" id="map" >

        <input type="text" name="idno" value="<?= htmlspecialchars($prefilled[11]) ?>">
        <label>Price</label>
        <input type="text" name="price">


        <label>Amount Paid</label>
<input type="text" name="amount_paid">

<label>Fully Paid</label>
<input type="checkbox" name="fully_paid" value="1">

<button type="submit">Submit Order</button>
    </form>
</div>

<script>
function serialToDate(serial) {
  const baseDate = new Date(1899, 11, 30);
  const resultDate = new Date(baseDate.getTime() + serial * 86400000);
  const day = String(resultDate.getDate()).padStart(2, '0');
  const month = String(resultDate.getMonth() + 1).padStart(2, '0');
  const year = resultDate.getFullYear();
  return `${year}-${month}-${day}`;
}

<?php if (is_numeric($prefilled[8])): ?>
  document.getElementById('date').value = serialToDate(<?= htmlspecialchars($prefilled[8]) ?>);
<?php else: ?>
  document.getElementById('date').value = "<?= htmlspecialchars($prefilled[8]) ?>";
<?php endif; ?>

async function getDistance(shortUrl) {
    const apiEndpoint = 'https://smartronic.online/admin/google_api.php';
    const response = await fetch(`${apiEndpoint}?url=${encodeURIComponent(shortUrl)}`);
    const result = await response.json();

    if (result.distance_km) {
        console.log(`Distance: ${result.distance_km} km`);
        document.getElementById("map").value = result.distance_km;
    } else {
        console.error('Error:', result.error);
    }
}

getDistance('<?= htmlspecialchars($prefilled[10]) ?>');
</script>

</body>
</html>
