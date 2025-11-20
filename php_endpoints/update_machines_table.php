<?php
// Add missing columns to machines table
// File: php_endpoints/update_machines_table.php

require_once 'db.php';

try {
    // Check and add fill_level column
    $check_fill = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'fill_level'");
    if ($check_fill->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN fill_level INT DEFAULT 0");
        echo "Added fill_level column\n";
    }
    
    // Check and add battery_level column
    $check_battery = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'battery_level'");
    if ($check_battery->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN battery_level INT DEFAULT 100");
        echo "Added battery_level column\n";
    }
    
    // Check and add temperature column
    $check_temp = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'temperature'");
    if ($check_temp->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN temperature DECIMAL(5,2) DEFAULT 25.00");
        echo "Added temperature column\n";
    }
    
    // Check and add last_maintenance column
    $check_maintenance = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'last_maintenance'");
    if ($check_maintenance->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN last_maintenance DATE NULL");
        echo "Added last_maintenance column\n";
    }
    
    // Check and add last_seen column
    $check_seen = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'last_seen'");
    if ($check_seen->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "Added last_seen column\n";
    }
    
    // Check and add created_at column
    $check_created = $mysqli->query("SHOW COLUMNS FROM machines LIKE 'created_at'");
    if ($check_created->num_rows == 0) {
        $mysqli->query("ALTER TABLE machines ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Added created_at column\n";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All missing columns added to machines table successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>







