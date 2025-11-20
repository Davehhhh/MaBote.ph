<?php
// Test database connection
$host = 'localhost';
$port = 3306;  // XAMPP MySQL port
$username = 'root';
$password = '';

echo "<h2>Database Connection Test</h2>";

// Test 1: Connect without database
echo "<h3>Test 1: Connect to MySQL server</h3>";
$mysqli = new mysqli($host, $username, $password, '', $port);

if ($mysqli->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Connected to MySQL server successfully</p>";
    
    // Test 2: List databases
    echo "<h3>Test 2: List databases</h3>";
    $result = $mysqli->query("SHOW DATABASES");
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Could not list databases: " . $mysqli->error . "</p>";
    }
    
    // Test 3: Check if mabote_db exists
    echo "<h3>Test 3: Check if mabote_db exists</h3>";
    $result = $mysqli->query("SHOW DATABASES LIKE 'mabote_db'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Database 'mabote_db' exists</p>";
        
        // Test 4: Connect to mabote_db
        echo "<h3>Test 4: Connect to mabote_db</h3>";
        $mysqli->select_db('mabote_db');
        if ($mysqli->error) {
            echo "<p style='color: red;'>❌ Could not select database: " . $mysqli->error . "</p>";
        } else {
            echo "<p style='color: green;'>✅ Successfully connected to mabote_db</p>";
            
            // Test 5: List tables
            echo "<h3>Test 5: List tables in mabote_db</h3>";
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                echo "<ul>";
                while ($row = $result->fetch_array()) {
                    echo "<li>" . $row[0] . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: red;'>❌ Could not list tables: " . $mysqli->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Database 'mabote_db' does not exist</p>";
        
        // Test 5: Create database
        echo "<h3>Test 5: Create mabote_db</h3>";
        if ($mysqli->query("CREATE DATABASE mabote_db")) {
            echo "<p style='color: green;'>✅ Database 'mabote_db' created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Could not create database: " . $mysqli->error . "</p>";
        }
    }
}

$mysqli->close();
?>
