<?php
// Add points to specific user by email
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
        exit;
    }
    
    $mysqli->set_charset('utf8mb4');
    
    // Get JSON input
    $input = file_get_contents('php://input');
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No input data received']);
        exit;
    }
    
    $body = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $email = $body['email'] ?? '';
    $points = (int)($body['points'] ?? 0);
    $reason = $body['reason'] ?? 'Manual addition';
    
    if (empty($email) || $points <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or points']);
        exit;
    }
    
    // Get user by email
    $stmt = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['user_id'];
    $stmt->close();
    
    // Start transaction
    $mysqli->begin_transaction();
    
    try {
        // Update wallet
        $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance + ? WHERE user_id = ?');
        $stmt->bind_param('ii', $points, $userId);
        $stmt->execute();
        $stmt->close();
        
        // Update total points
        $stmt = $mysqli->prepare('UPDATE users SET total_points = total_points + ? WHERE user_id = ?');
        $stmt->bind_param('ii', $points, $userId);
        $stmt->execute();
        $stmt->close();
        
        // Create transaction record
        $transactionCode = 'MANUAL-' . $userId . '-' . time();
        $qrCode = 'MANUAL-' . $userId . '-' . time();
        
        $stmt = $mysqli->prepare('
            INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status)
            VALUES (?, ?, 0, ?, NOW(), ?, \'completed\')
        ');
        $stmt->bind_param('isis', $userId, $transactionCode, $points, $qrCode);
        $stmt->execute();
        $stmt->close();
        
        // Note: Database notifications are now handled by Flutter app based on user preferences
        // This ensures notifications respect the user's notification settings
        
        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully added $points points to $email",
            'user_id' => $userId,
            'points_added' => $points
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

exit;
?>

