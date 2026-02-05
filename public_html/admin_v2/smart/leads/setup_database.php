<?php
// Create database and table structure
$conn = new mysqli('localhost', 'root', 'root');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
echo "Creating database...\n";
$conn->query("CREATE DATABASE IF NOT EXISTS u398852039_smartronic");
$conn->select_db('u398852039_smartronic');

// Create leads table
echo "Creating leads table...\n";
$sql = "CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    MID VARCHAR(50) NULL,
    Name VARCHAR(255) NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL,
    Area VARCHAR(255) NULL,
    Assign VARCHAR(255) NULL,
    cameras INT DEFAULT 0,
    resolution VARCHAR(50) NULL,
    status ENUM('New', 'Contacted', 'Quoted', 'Won', 'Lost', 'Follow-up') DEFAULT 'New',
    source VARCHAR(100) NULL COMMENT 'Lead source/disguise',
    budget DECIMAL(10,2) NULL,
    comments TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (Name),
    INDEX idx_phone (whatsapp_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "✓ Leads table created successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Insert sample data
echo "\nInserting sample data...\n";
$sampleData = [
    ['John Doe', '+919876543210', 'Mumbai', 'Sales Team A', 8, '4MP', 'New', 'Website', 50000, 'Interested in outdoor cameras'],
    ['Jane Smith', '+919876543211', 'Delhi', 'Sales Team B', 4, '2MP', 'Contacted', 'Referral', 25000, 'Looking for indoor cameras'],
    ['Raj Kumar', '+919876543212', 'Bangalore', 'Sales Team A', 12, '8MP', 'Quoted', 'Google Ads', 80000, 'Commercial property'],
    ['Priya Sharma', '+919876543213', 'Chennai', 'Sales Team C', 6, '5MP', 'Follow-up', 'Facebook', 35000, 'Residential - 2 floors'],
    ['Amit Patel', '+919876543214', 'Pune', 'Sales Team B', 10, '4MP', 'New', 'Walk-in', 60000, 'Warehouse security'],
];

$stmt = $conn->prepare("INSERT INTO leads (Name, whatsapp_number, Area, Assign, cameras, resolution, status, source, budget, comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($sampleData as $data) {
    $stmt->bind_param("ssssisssds", ...$data);
    $stmt->execute();
}

echo "✓ Inserted " . count($sampleData) . " sample leads\n";

// Add more sample data to test pagination
echo "\nAdding more sample data for testing...\n";
for ($i = 6; $i <= 100; $i++) {
    $names = ['Rahul', 'Sneha', 'Vikram', 'Anjali', 'Karan', 'Pooja', 'Arjun', 'Divya'];
    $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Pune', 'Hyderabad', 'Kolkata', 'Ahmedabad'];
    $teams = ['Sales Team A', 'Sales Team B', 'Sales Team C'];
    $resolutions = ['2MP', '4MP', '5MP', '8MP'];
    $statuses = ['New', 'Contacted', 'Quoted', 'Follow-up'];
    $sources = ['Website', 'Google Ads', 'Facebook', 'Referral', 'Walk-in'];
    
    $name = $names[array_rand($names)] . ' ' . chr(65 + ($i % 26));
    $phone = '+9198765432' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $area = $cities[array_rand($cities)];
    $team = $teams[array_rand($teams)];
    $cameras = rand(2, 16);
    $resolution = $resolutions[array_rand($resolutions)];
    $status = $statuses[array_rand($statuses)];
    $source = $sources[array_rand($sources)];
    $budget = rand(20000, 100000);
    $comment = "Sample lead #$i";
    
    $stmt->bind_param("ssssisssds", $name, $phone, $area, $team, $cameras, $resolution, $status, $source, $budget, $comment);
    $stmt->execute();
}

echo "✓ Added 95 more sample leads (Total: 100)\n";

$stmt->close();
$conn->close();

echo "\n✓ Database setup complete!\n";
?>
