<?php
// Database Check Script
// File: check_database.php

echo "<h2>🔍 DATABASE STATUS CHECK</h2>";

// Database connection parameters
$host = 'localhost';
$port = 3307; // Your XAMPP MySQL port
$username = 'root';
$password = '';

try {
    // Connect to MySQL server (without specifying database)
    $mysqli = new mysqli($host, $username, $password, '', $port);
    
    if ($mysqli->connect_error) {
        echo "<p style='color: red;'>❌ <strong>MySQL Connection Failed:</strong> " . $mysqli->connect_error . "</p>";
        echo "<p><strong>Possible Issues:</strong></p>";
        echo "<ul>";
        echo "<li>XAMPP MySQL service not running</li>";
        echo "<li>Wrong port number (try 3306 instead of 3307)</li>";
        echo "<li>Wrong username/password</li>";
        echo "</ul>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ <strong>MySQL Connection Successful!</strong></p>";
    
    // Check if mabote_db exists
    $result = $mysqli->query("SHOW DATABASES LIKE 'mabote_db'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ <strong>Database 'mabote_db' EXISTS!</strong></p>";
        
        // Connect to the database and check tables
        $mysqli->select_db('mabote_db');
        $tables_result = $mysqli->query("SHOW TABLES");
        
        echo "<p><strong>📋 Tables in mabote_db:</strong></p>";
        echo "<ul>";
        while ($row = $tables_result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        
        // Check if tables have data
        $users_count = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        $transactions_count = $mysqli->query("SELECT COUNT(*) as count FROM transactions")->fetch_assoc()['count'];
        
        echo "<p><strong>📊 Data Count:</strong></p>";
        echo "<ul>";
        echo "<li>Users: " . $users_count . "</li>";
        echo "<li>Transactions: " . $transactions_count . "</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Database 'mabote_db' DOES NOT EXIST!</strong></p>";
        echo "<p><strong>🔧 Solution:</strong></p>";
        echo "<ol>";
        echo "<li>Go to <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
        echo "<li>Click 'Import' tab</li>";
        echo "<li>Import the file: <code>php_endpoints/complete_database_setup.sql</code></li>";
        echo "<li>Click 'Go' to execute</li>";
        echo "</ol>";
    }
    
    // Show all databases
    echo "<p><strong>🗄️ All Available Databases:</strong></p>";
    $all_dbs = $mysqli->query("SHOW DATABASES");
    echo "<ul>";
    while ($row = $all_dbs->fetch_array()) {
        $db_name = $row[0];
        $style = ($db_name === 'mabote_db') ? "color: green; font-weight: bold;" : "";
        echo "<li style='$style'>" . $db_name . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

$mysqli->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
p { margin: 10px 0; }
ul { margin: 10px 0; padding-left: 20px; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
