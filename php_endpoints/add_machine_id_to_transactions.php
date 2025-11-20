<?php
// Add machine_id column to transactions table if it doesn't exist
// File: php_endpoints/add_machine_id_to_transactions.php

require_once 'db.php';

try {
    // Check if machine_id column exists
    $check_column = $mysqli->query("SHOW COLUMNS FROM transactions LIKE 'machine_id'");
    
    if ($check_column->num_rows == 0) {
        // Add machine_id column
        $alter_query = "ALTER TABLE transactions 
                        ADD COLUMN machine_id VARCHAR(50) DEFAULT 'BIN001',
                        ADD INDEX idx_machine_id (machine_id)";
        
        if ($mysqli->query($alter_query)) {
            echo json_encode([
                'success' => true,
                'message' => 'machine_id column added to transactions table successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding machine_id column: ' . $mysqli->error
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'machine_id column already exists in transactions table'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>







