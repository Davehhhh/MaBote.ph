<?php
// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON headers immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $mysqli->set_charset('utf8mb4');

// Check if period parameter is provided
$period = $_GET['period'] ?? 'all';

if ($period === 'monthly') {
    // For monthly, get users who had activity this month
    // Only show active users (is_active = 1, not NULL, not 0)
    $query = "
      SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.total_points as total_earned,
        w.current_balance,
        COUNT(DISTINCT t.transaction_id) as total_deposits,
        COALESCE(SUM(t.bottle_deposited), 0) as total_bottles
      FROM users u
      LEFT JOIN wallet w ON u.user_id = w.user_id
      LEFT JOIN transactions t ON u.user_id = t.user_id AND t.transaction_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
      WHERE (u.is_active = 1 AND u.is_active IS NOT NULL)
        AND t.transaction_id IS NOT NULL
      GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance
      ORDER BY COALESCE(SUM(t.points_earned), 0) DESC, COUNT(DISTINCT t.transaction_id) DESC
      LIMIT 50
    ";
} else {
    // For all-time, show all active users only
    // Only show active users (is_active = 1, not NULL, not 0)
    $query = "
      SELECT 
        u.user_id, 
        u.first_name, 
        u.last_name, 
        u.email, 
        u.total_points as total_earned,
        w.current_balance,
        COUNT(DISTINCT t.transaction_id) as total_deposits,
        COALESCE(SUM(t.bottle_deposited), 0) as total_bottles
      FROM users u
      LEFT JOIN wallet w ON u.user_id = w.user_id
      LEFT JOIN transactions t ON u.user_id = t.user_id
      WHERE (u.is_active = 1 AND u.is_active IS NOT NULL)
      GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.total_points, w.current_balance
      ORDER BY u.total_points DESC
      LIMIT 50
    ";
}

$stmt = $mysqli->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($row = $result->fetch_assoc()) {
  $users[] = [
    'user_id' => (int)$row['user_id'],
    'first_name' => $row['first_name'],
    'last_name' => $row['last_name'],
    'full_name' => $row['first_name'] . ' ' . $row['last_name'],
    'email' => $row['email'],
    'total_earned' => (int)$row['total_earned'],
    'current_balance' => (int)$row['current_balance'],
    'total_deposits' => (int)$row['total_deposits'],
    'total_bottles' => (int)$row['total_bottles']
  ];
}
$stmt->close();

    // Return leaderboard data at root level for Flutter compatibility
    $response = [
        'success' => true,
        'message' => 'OK',
        'leaderboard' => $users
    ];

    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>
