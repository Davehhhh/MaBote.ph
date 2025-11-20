<?php
require_once 'config.php';

// Create machines table
$create_table_sql = "CREATE TABLE IF NOT EXISTS machines (
    machine_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('active', 'maintenance', 'offline') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_maintenance DATE,
    total_bottles INT DEFAULT 0,
    total_transactions INT DEFAULT 0
)";

if ($mysqli->query($create_table_sql)) {
    echo "Machines table created successfully!<br>";
} else {
    echo "Error creating machines table: " . $mysqli->error . "<br>";
}

// Insert sample data
$insert_data_sql = "INSERT INTO machines (name, location, status) VALUES 
('Machine 1', 'Downtown Plaza', 'active'),
('Machine 2', 'Shopping Mall', 'active'),
('Machine 3', 'University Campus', 'maintenance'),
('Machine 4', 'City Park', 'active'),
('Machine 5', 'Community Center', 'offline')";

if ($mysqli->query($insert_data_sql)) {
    echo "Sample machines data inserted successfully!<br>";
} else {
    echo "Error inserting sample data: " . $mysqli->error . "<br>";
}

echo "<a href='dashboard.php'>Go to Dashboard</a>";
?>


