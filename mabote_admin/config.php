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

// Admin session management
session_start();

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Redirect to login if not authenticated
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Admin login function
function adminLogin($username, $password) {
    global $mysqli;
    
    // For now, using simple hardcoded admin credentials
    // In production, you should store admin credentials in database
    $admin_username = 'admin';
    $admin_password = 'admin123'; // Change this in production
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Logout function
function adminLogout() {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Get dashboard statistics
function getDashboardStats() {
    global $mysqli;
    
    $stats = [];
    
    // Total users
    $result = $mysqli->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result->fetch_assoc()['total'];
    
    // Total bottles collected
    $result = $mysqli->query("SELECT COALESCE(SUM(bottle_deposited), 0) as total FROM transactions");
    $stats['total_bottles'] = $result->fetch_assoc()['total'];
    
    // Total points distributed (only positive points from deposits)
    $result = $mysqli->query("SELECT COALESCE(SUM(points_earned), 0) as total FROM transactions WHERE points_earned > 0");
    $stats['total_points'] = $result->fetch_assoc()['total'];
    
    // Total transactions (deposits + redemptions)
    $result = $mysqli->query("SELECT 
        (SELECT COUNT(*) FROM transactions WHERE points_earned > 0) + 
        (SELECT COUNT(*) FROM redemption) as total");
    $stats['total_transactions'] = $result->fetch_assoc()['total'];
    
    // Total redemptions
    $result = $mysqli->query("SELECT COALESCE(SUM(points_used), 0) as total FROM redemption");
    $stats['total_redemptions'] = $result->fetch_assoc()['total'];
    
    // Active machines (placeholder - you can add machines table later)
    $stats['active_machines'] = 5; // Placeholder
    
    return $stats;
}

// Get recent transactions (deposits + redemptions)
function getRecentTransactions($limit = 10) {
    global $mysqli;
    
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
                u.first_name, 
                u.last_name
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
                u.first_name, 
                u.last_name
              FROM redemption r
              JOIN users u ON r.user_id = u.user_id
              
              ORDER BY transaction_date DESC 
              LIMIT ?";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get user statistics
function getUserStats() {
    global $mysqli;
    
    $stats = [];
    
    // Users registered this month (simplified - just count all users)
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    $stats['new_this_month'] = $result->fetch_assoc()['count'];
    
    // Active users (users with transactions in last 30 days)
    $result = $mysqli->query("SELECT COUNT(DISTINCT user_id) as count FROM transactions WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $result->fetch_assoc()['count'];
    
    return $stats;
}
?>
