<?php
// Comprehensive Database Structure Check
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo "❌ Database connection failed: " . $mysqli->connect_error;
        exit;
    }
    
    echo "<h1>🔍 MaBote.ph Database Structure Check</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
    </style>";
    
    // List of required tables
    $requiredTables = [
        'users', 'wallet', 'transactions', 'redemption', 'reward', 
        'qr_codes', 'deposit_session', 'notification', 'password_reset_tokens'
    ];
    
    // Check if all tables exist
    echo "<div class='section'>";
    echo "<h2>📋 Table Existence Check</h2>";
    $existingTables = [];
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "<p class='success'>✅ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' is MISSING</p>";
        }
    }
    echo "</div>";
    
    // Check each table structure
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "<div class='section'>";
            echo "<h2>🔧 Table: $table</h2>";
            
            // Get table structure
            $result = $mysqli->query("DESCRIBE $table");
            if ($result) {
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><strong>" . $row['Field'] . "</strong></td>";
                    echo "<td>" . $row['Type'] . "</td>";
                    echo "<td>" . $row['Null'] . "</td>";
                    echo "<td>" . $row['Key'] . "</td>";
                    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . $row['Extra'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                // Get record count
                $countResult = $mysqli->query("SELECT COUNT(*) as count FROM $table");
                if ($countResult) {
                    $count = $countResult->fetch_assoc()['count'];
                    echo "<p><strong>Record Count:</strong> $count</p>";
                }
                
                // Show sample data for key tables
                if (in_array($table, ['users', 'transactions', 'redemption', 'wallet'])) {
                    $sampleResult = $mysqli->query("SELECT * FROM $table LIMIT 3");
                    if ($sampleResult && $sampleResult->num_rows > 0) {
                        echo "<h3>Sample Data:</h3>";
                        echo "<table>";
                        $first = true;
                        while ($row = $sampleResult->fetch_assoc()) {
                            if ($first) {
                                echo "<tr>";
                                foreach (array_keys($row) as $key) {
                                    echo "<th>" . $key . "</th>";
                                }
                                echo "</tr>";
                                $first = false;
                            }
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
            } else {
                echo "<p class='error'>❌ Error describing table: " . $mysqli->error . "</p>";
            }
            echo "</div>";
        }
    }
    
    // Check foreign key relationships
    echo "<div class='section'>";
    echo "<h2>🔗 Foreign Key Relationships</h2>";
    $result = $mysqli->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = '$database' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Table</th><th>Column</th><th>References</th><th>Constraint</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['TABLE_NAME'] . "</td>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No foreign key relationships found</p>";
    }
    echo "</div>";
    
    // Check critical data integrity
    echo "<div class='section'>";
    echo "<h2>🔍 Data Integrity Check</h2>";
    
    // Check if users have wallets
    $result = $mysqli->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
            (SELECT COUNT(*) FROM wallet WHERE is_active = 1) as active_wallets,
            (SELECT COUNT(*) FROM users u LEFT JOIN wallet w ON u.user_id = w.user_id WHERE u.is_active = 1 AND w.user_id IS NULL) as users_without_wallets
    ");
    
    if ($result) {
        $data = $result->fetch_assoc();
        echo "<p><strong>Active Users:</strong> " . $data['active_users'] . "</p>";
        echo "<p><strong>Active Wallets:</strong> " . $data['active_wallets'] . "</p>";
        if ($data['users_without_wallets'] > 0) {
            echo "<p class='warning'>⚠️ " . $data['users_without_wallets'] . " users without wallets</p>";
        } else {
            echo "<p class='success'>✅ All active users have wallets</p>";
        }
    }
    
    // Check transaction data
    $result = $mysqli->query("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(points_earned) as total_points_earned,
            SUM(bottle_deposited) as total_bottles
        FROM transactions 
        WHERE transaction_status = 'completed'
    ");
    
    if ($result) {
        $data = $result->fetch_assoc();
        echo "<p><strong>Total Transactions:</strong> " . $data['total_transactions'] . "</p>";
        echo "<p><strong>Total Points Earned:</strong> " . ($data['total_points_earned'] ?? 0) . "</p>";
        echo "<p><strong>Total Bottles Recycled:</strong> " . ($data['total_bottles'] ?? 0) . "</p>";
    }
    
    // Check redemption data
    $result = $mysqli->query("
        SELECT 
            COUNT(*) as total_redemptions,
            SUM(points_used) as total_points_redeemed
        FROM redemption 
        WHERE redemption_status = 'completed'
    ");
    
    if ($result) {
        $data = $result->fetch_assoc();
        echo "<p><strong>Total Redemptions:</strong> " . $data['total_redemptions'] . "</p>";
        echo "<p><strong>Total Points Redeemed:</strong> " . ($data['total_points_redeemed'] ?? 0) . "</p>";
    }
    
    echo "</div>";
    
    // API Endpoints Check
    echo "<div class='section'>";
    echo "<h2>🌐 API Endpoints Check</h2>";
    $apiFiles = [
        'login.php', 'signup_extended.php', 'forgot_password.php',
        'get_profile.php', 'update_profile.php', 'upload_profile_image.php',
        'get_wallet.php', 'transactions.php', 'leaderboard.php',
        'list_rewards.php', 'claim_reward.php', 'notifications.php',
        'send_notification.php', 'notification_count.php',
        'start_session.php', 'finalize_deposit.php', 'scan.php'
    ];
    
    $apiPath = __DIR__;
    foreach ($apiFiles as $file) {
        if (file_exists($apiPath . '/' . $file)) {
            echo "<p class='success'>✅ $file exists</p>";
        } else {
            echo "<p class='error'>❌ $file is MISSING</p>";
        }
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<h2>✅ Database Check Complete</h2>";
    echo "<p>All critical tables and relationships have been verified.</p>";
    echo "</div>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Database Error</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>








