<?php
// Set JSON headers immediately
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any output buffers
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
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit;
}

// Simulate bottle deposit with automatic notifications
// This mimics what happens when a machine deposits bottles

$userId = (int)($_GET['user_id'] ?? 0);
$bottleCount = (int)($_GET['bottles'] ?? 1);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'user_id required']);
    exit;
}

$points = $bottleCount * 5; // 5 points per bottle

try {
    $mysqli->begin_transaction();
    
    // Create transaction record
    $transactionCode = 'SIM-' . strtoupper(bin2hex(random_bytes(4)));
    $qrCode = 'SIM-' . $userId . '-' . time();
    
    $stmt = $mysqli->prepare('
        INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status)
        VALUES (?, ?, ?, ?, NOW(), ?, \'completed\')
    ');
    $stmt->bind_param('isiss', $userId, $transactionCode, $bottleCount, $points, $qrCode);
    $stmt->execute();
    $stmt->close();
    
    // Update wallet
    $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance + ? WHERE user_id = ?');
    $stmt->bind_param('ii', $points, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Update user total points
    $stmt = $mysqli->prepare('UPDATE users SET total_points = total_points + ? WHERE user_id = ?');
    $stmt->bind_param('ii', $points, $userId);
    $stmt->execute();
    $stmt->close();
    
    // NO manual notification creation - let the app handle it automatically
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully deposited $bottleCount bottles and earned $points points",
        'bottles_deposited' => $bottleCount,
        'points_earned' => $points,
        'transaction_code' => $transactionCode
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
