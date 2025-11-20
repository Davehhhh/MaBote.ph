<?php
// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo "Database connection failed: " . $mysqli->connect_error;
        exit;
    }
    
    echo "<h2>Database Structure Check</h2>";
    
    // Check redemption table structure
    echo "<h3>Redemption Table Structure:</h3>";
    $result = $mysqli->query("DESCRIBE redemption");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing redemption table: " . $mysqli->error;
    }
    
    // Check if redemption table has any data
    echo "<h3>Redemption Table Data:</h3>";
    $result = $mysqli->query("SELECT COUNT(*) as count FROM redemption");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total redemption records: " . $row['count'] . "<br>";
    }
    
    // Show sample redemption data if any exists
    $result = $mysqli->query("SELECT * FROM redemption LIMIT 3");
    if ($result && $result->num_rows > 0) {
        echo "<h4>Sample Redemption Data:</h4>";
        echo "<table border='1'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
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
    } else {
        echo "No redemption data found.<br>";
    }
    
    // Check transactions table structure too
    echo "<h3>Transactions Table Structure:</h3>";
    $result = $mysqli->query("DESCRIBE transactions");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error describing transactions table: " . $mysqli->error;
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>








