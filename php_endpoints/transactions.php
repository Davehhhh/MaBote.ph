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

    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'user_id required']);
        exit;
    }

// Get transactions
$stmt = $mysqli->prepare('SELECT transaction_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $row['transaction_type'] = 'deposit';
    $transactions[] = $row;
}
$stmt->close();

// Get redemptions and add to transactions (using correct column names from your database)
$stmt = $mysqli->prepare('SELECT redemption_id as transaction_id, redemption_code as transaction_code, 0 as bottle_deposited, -points_used as points_earned, redemption_date as transaction_date, \'N/A\' as qr_code_scanned, redemption_status as transaction_status FROM redemption WHERE user_id = ? ORDER BY redemption_date DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['transaction_type'] = 'redemption';
    $transactions[] = $row; // Add redemptions to the same transactions array
}
$stmt->close();

// Sort all transactions by date
usort($transactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Limit to 50 results
$transactions = array_slice($transactions, 0, 50);

    // Return transactions data at root level for Flutter compatibility
    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>
