<?php
require_once __DIR__ . '/config.php';

echo "Checking leaves tablew...\n";

// Ensure table exists
$createTableQuery = "
CREATE TABLE IF NOT EXISTS `leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($createTableQuery) === TRUE) {
    echo "Table verified.\n";
} else {
    echo "Error checking table: " . $conn->error . "\n";
}

// Add leave_type column if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM `leaves` LIKE 'leave_type'");
if ($checkColumn && $checkColumn->num_rows == 0) {
    $alterTable = "ALTER TABLE `leaves` ADD COLUMN `leave_type` ENUM('planned', 'unplanned', 'sick', 'weekly_off', 'extra', 'worked_holiday') NOT NULL DEFAULT 'planned' AFTER `leave_date`";
    if ($conn->query($alterTable) === TRUE) {
        echo "Added column 'leave_type'.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    // Modify existing ENUM to include worked_holiday
    $alterEnum = "ALTER TABLE `leaves` MODIFY COLUMN `leave_type` ENUM('planned', 'unplanned', 'sick', 'weekly_off', 'extra', 'worked_holiday') NOT NULL DEFAULT 'planned'";
    if ($conn->query($alterEnum) === TRUE) {
        echo "Updated column ENUM 'leave_type'.\n";
    } else {
        echo "Error updating column ENUM: " . $conn->error . "\n";
    }
}

echo "Done.\n";
?>
