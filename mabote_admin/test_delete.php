<?php
require_once 'config.php';

// Test delete functionality
$user_id = 21;

echo "<h2>Testing Delete Functionality for User $user_id</h2>";

// Check if user exists before deletion
$stmt = $mysqli->prepare("SELECT user_id, first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    echo "<p>✅ User exists: {$user['first_name']} {$user['last_name']}</p>";
    
    // Check wallet before deletion
    $stmt = $mysqli->prepare("SELECT wallet_id, current_balance FROM wallet WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($wallet) {
        echo "<p>✅ Wallet exists: ID {$wallet['wallet_id']}, Balance: {$wallet['current_balance']}</p>";
    } else {
        echo "<p>❌ No wallet found</p>";
    }
    
    // Perform deletion
    echo "<h3>Performing Deletion...</h3>";
    
    // Disable foreign key checks temporarily
    $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
    
    try {
        // Delete from tables that reference user_id
        $tables_to_clean = [
            'points_history',
            'redemption', 
            'notification',
            'transactions',
            'wallet',
            'sessions',
            'password_reset_tokens'
        ];
        
        foreach ($tables_to_clean as $table) {
            $stmt = $mysqli->prepare("DELETE FROM $table WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $result = $stmt->execute();
                $affected = $mysqli->affected_rows;
                echo "<p>Deleted from $table: $affected rows</p>";
                $stmt->close();
            }
        }
        
        // Now delete the user
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $result = $stmt->execute();
            $affected = $mysqli->affected_rows;
            echo "<p>Deleted from users: $affected rows</p>";
            $stmt->close();
        }
        
        echo "<p>✅ Deletion completed successfully!</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error during deletion: " . $e->getMessage() . "</p>";
    } finally {
        // Re-enable foreign key checks
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Check if user still exists after deletion
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_after = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($user_after) {
        echo "<p>❌ User still exists after deletion!</p>";
    } else {
        echo "<p>✅ User successfully deleted!</p>";
    }
    
    // Check if wallet still exists after deletion
    $stmt = $mysqli->prepare("SELECT wallet_id FROM wallet WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet_after = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($wallet_after) {
        echo "<p>❌ Wallet still exists after deletion!</p>";
    } else {
        echo "<p>✅ Wallet successfully deleted!</p>";
    }
    
} else {
    echo "<p>❌ User $user_id does not exist</p>";
}

echo "<p><a href='users.php'>Back to Users</a></p>";
?>


