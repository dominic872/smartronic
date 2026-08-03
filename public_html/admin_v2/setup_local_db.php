<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<h2>Starting database repair and setup...</h2><pre>";

// 1. Create `users` table if not exists
echo "Checking 'users' table...\n";
$createUsersQuery = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `roll` varchar(50) NOT NULL,
  `Pages` varchar(255) NOT NULL DEFAULT 'all',
  `phone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($createUsersQuery) === TRUE) {
    echo "✓ 'users' table verified/created successfully.\n";
} else {
    echo "❌ Error creating 'users' table: " . $conn->error . "\n";
    exit;
}

// 2. Populate `users` from `users.json` if empty
$userCheck = $conn->query("SELECT COUNT(*) AS count FROM users");
$userCount = $userCheck->fetch_assoc()['count'] ?? 0;

if ($userCount == 0) {
    echo "Populating 'users' table from users.json...\n";
    $usersJsonPath = __DIR__ . '/users.json';
    if (file_exists($usersJsonPath)) {
        $usersData = json_decode(file_get_contents($usersJsonPath), true);
        if (is_array($usersData)) {
            $stmt = $conn->prepare("INSERT INTO users (id, username, password, fullname, roll, Pages, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $idCounter = 1;
            foreach ($usersData as $u) {
                $hashedPassword = password_hash($u['password'], PASSWORD_DEFAULT);
                $pages = ($u['role'] === 'admin' || $u['role'] === 'manager') ? 'all' : 'install,quote,lead';
                $stmt->bind_param("issssss", 
                    $idCounter,
                    $u['username'],
                    $hashedPassword,
                    $u['name'],
                    $u['role'],
                    $pages,
                    $u['phone']
                );
                $stmt->execute();
                echo "  ✓ Added user: {$u['username']} (ID: {$idCounter}, role: {$u['role']})\n";
                $idCounter++;
            }
            $stmt->close();
            echo "✓ 'users' table populated successfully.\n";
        } else {
            echo "❌ Error parsing users.json\n";
        }
    } else {
        echo "❌ users.json not found!\n";
    }
} else {
    echo "✓ 'users' table already has {$userCount} records.\n";
}

// 3. Create and populate `orders` table from orders_structure.sql
echo "Checking 'orders' table...\n";
$ordersCheck = $conn->query("SHOW TABLES LIKE 'orders'");
if ($ordersCheck && $ordersCheck->num_rows == 0) {
    echo "'orders' table does not exist. Importing orders_structure.sql...\n";
    $sqlPath = dirname(__DIR__) . '/orders_structure.sql';
    if (file_exists($sqlPath)) {
        $sqlContent = file_get_contents($sqlPath);
        
        // Let's use mysqli multi_query which is designed exactly for this!
        if ($conn->multi_query($sqlContent)) {
            do {
                // store first result set
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            echo "✓ Main orders_structure.sql multi-query execution completed.\n";
        } else {
            echo "❌ Error in multi-query execution: " . $conn->error . "\n";
        }
        
    } else {
        echo "❌ orders_structure.sql not found at $sqlPath!\n";
    }
} else {
    echo "✓ 'orders' table already exists.\n";
}

// Let's also run the brand/cam_type alterations if they aren't in the table yet
$checkBrand = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'brand'");
if ($checkBrand && $checkBrand->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `brand` VARCHAR(50) NULL AFTER `resolution`");
}
$checkCamType = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'cam_type'");
if ($checkCamType && $checkCamType->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `cam_type` VARCHAR(50) NULL AFTER `brand`");
}
$checkAdminEventComment = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'admin_event_comment'");
if ($checkAdminEventComment && $checkAdminEventComment->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `admin_event_comment` TEXT NULL DEFAULT NULL AFTER `notes`");
}
echo "✓ 'orders' brand, cam_type, and admin_event_comment columns verified.\n";

echo "\n🎉 Setup and repair complete! Check admin_v2/smart/installs.php now.\n</pre>";
?>
