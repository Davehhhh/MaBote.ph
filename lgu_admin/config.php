<?php
// Database configuration
$host = 'localhost';
$port = 3306;  // XAMPP MySQL port
$username = 'root';
$password = '';  // Empty password for XAMPP default
$database = 'mabote_db';

// Create connection
$mysqli = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($mysqli->connect_error) {
    // Try to create database if it doesn't exist
    $mysqli = new mysqli($host, $username, $password, '', $port);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    // Create database
    $mysqli->query("CREATE DATABASE IF NOT EXISTS $database");
    $mysqli->select_db($database);
    
    // Reconnect to the specific database
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
}

// Set charset
$mysqli->set_charset("utf8");

// LGU session management
session_start();

// Check if user is logged in as LGU
function isLguLoggedIn() {
    return isset($_SESSION['lgu_logged_in']) && $_SESSION['lgu_logged_in'] === true;
}

// Redirect to login if not authenticated
function requireLguLogin() {
    if (!isLguLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// LGU login function
function adminLogin($username, $password) {
    global $mysqli;
    
    // For now, using simple hardcoded LGU credentials
    // In production, you should store LGU credentials in database
    $lgu_username = 'lgu';
    $lgu_password = 'lgu123'; // Change this in production

    if ($username === $lgu_username && $password === $lgu_password) {
        $_SESSION['lgu_logged_in'] = true;
        $_SESSION['lgu_username'] = $username;
        return true;
    }
    return false;
}

// Logout function
function lguLogout() {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Get LGU-specific dashboard statistics
function getLguDashboardStats() {
    global $mysqli;
    
    $stats = [];
    
    try {
        // Total users in LGU area
        $result = $mysqli->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total bottles collected this month
        $result = $mysqli->query("SELECT COALESCE(SUM(bottle_deposited), 0) as total FROM transactions WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())");
        $stats['bottles_this_month'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total bottles collected all time
        $result = $mysqli->query("SELECT COALESCE(SUM(bottle_deposited), 0) as total FROM transactions");
        $stats['total_bottles'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total points distributed
        $result = $mysqli->query("SELECT COALESCE(SUM(points_earned), 0) as total FROM transactions");
        $stats['total_points'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total redemptions (points used)
        $result = $mysqli->query("SELECT COALESCE(SUM(points_used), 0) as total FROM redemption");
        $stats['total_redemptions'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Active bins/machines
        $result = $mysqli->query("SELECT COUNT(*) as total FROM machines");
        $stats['active_bins'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Environmental impact (estimated CO2 saved)
        $stats['co2_saved'] = $stats['total_bottles'] * 0.05; // 0.05 kg CO2 per bottle
        
        // Waste diverted from landfill (kg)
        $stats['waste_diverted'] = $stats['total_bottles'] * 0.025; // 25g per bottle
        
    } catch (Exception $e) {
        // Set default values if there's an error
        $stats = [
            'total_users' => 0,
            'bottles_this_month' => 0,
            'total_bottles' => 0,
            'total_points' => 0,
            'total_redemptions' => 0,
            'active_bins' => 0,
            'co2_saved' => 0,
            'waste_diverted' => 0
        ];
    }
    
    return $stats;
}

// Get LGU environmental impact metrics
function getLguEnvironmentalStats() {
    global $mysqli;
    
    $stats = [];
    
    try {
        // Monthly collection trends
        $result = $mysqli->query("SELECT 
            MONTH(transaction_date) as month,
            YEAR(transaction_date) as year,
            SUM(bottle_deposited) as bottles,
            COUNT(*) as transactions
            FROM transactions 
            WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY YEAR(transaction_date), MONTH(transaction_date)
            ORDER BY year DESC, month DESC");
        
        $stats['monthly_trends'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Top contributors
        $result = $mysqli->query("SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as username,
            SUM(t.bottle_deposited) as bottles,
            SUM(t.points_earned) as points
            FROM users u
            JOIN transactions t ON u.user_id = t.user_id
            GROUP BY u.user_id
            ORDER BY bottles DESC
            LIMIT 5");
        
        $stats['top_contributors'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Add total bottles and waste diverted
        $result = $mysqli->query("SELECT COALESCE(SUM(bottle_deposited), 0) as total FROM transactions");
        $stats['total_bottles'] = $result ? $result->fetch_assoc()['total'] : 0;
        $stats['waste_diverted'] = $stats['total_bottles'] * 0.025; // 25g per bottle
        
    } catch (Exception $e) {
        // Set default values if there's an error
        $stats = [
            'monthly_trends' => [],
            'top_contributors' => []
        ];
    }
    
    return $stats;
}

// Get recent transactions (deposits + redemptions)
function getRecentTransactions($limit = 10) {
    global $mysqli;
    
    try {
        $query = "SELECT 
                    t.transaction_id,
                    t.transaction_code,
                    t.bottle_deposited,
                    t.points_earned,
                    t.transaction_date,
                    t.qr_code_scanned,
                    t.transaction_status,
                    'deposit' as transaction_type,
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as username,
                    t.points_earned as points,
                    t.transaction_date as created_at
                  FROM transactions t 
                  JOIN users u ON t.user_id = u.user_id 
                  
                  UNION ALL
                  
                  SELECT 
                    r.redemption_id as transaction_id,
                    CONCAT('RED-', r.redemption_id) as transaction_code,
                    0 as bottle_deposited,
                    -r.points_used as points_earned,
                    r.redemption_date as transaction_date,
                    'N/A' as qr_code_scanned,
                    r.status as transaction_status,
                    'redemption' as transaction_type,
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as username,
                    -r.points_used as points,
                    r.redemption_date as created_at
                  FROM redemption r
                  JOIN users u ON r.user_id = u.user_id
                  
                  ORDER BY transaction_date DESC 
                  LIMIT ?";
        
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        // Return empty array if there's an error
        return [];
    }
    
    return [];
}

// Get user statistics
function getUserStats() {
    global $mysqli;
    
    $stats = [];
    
    try {
        // Users registered this month (simplified - just count all users)
        $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
        $stats['new_this_month'] = $result ? $result->fetch_assoc()['count'] : 0;
        
        // Active users (users with transactions in last 30 days)
        $result = $mysqli->query("SELECT COUNT(DISTINCT user_id) as count FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['active_users'] = $result ? $result->fetch_assoc()['count'] : 0;
        
    } catch (Exception $e) {
        // Set default values if there's an error
        $stats = [
            'new_this_month' => 0,
            'active_users' => 0
        ];
    }
    
    return $stats;
}
?>
