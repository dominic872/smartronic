<?php
require_once __DIR__ . '/config.php';

echo "Checking leave_logs table.2..\n";

// Ensure table exists
$createTableQuery = "
CREATE TABLE IF NOT EXISTS `leave_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_user_id` int(11) NOT NULL,
  `target_user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `leave_date` date NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($createTableQuery) === TRUE) {
    echo "Table verified2 / created.\n";
} else {
    echo "Error checking table: " . $conn->error . "\n";
}

echo "Done.\n";
?>
