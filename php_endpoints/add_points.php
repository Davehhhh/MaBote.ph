<?php
// Clean any existing output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Get JSON input
$input = file_get_contents('php://input');
$body = json_decode($input, true);

if (!$body) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$mysqli->set_charset('utf8mb4');

$userId = (int)($body['user_id'] ?? 0);
$points = (int)($body['points'] ?? 0);
$reason = $body['reason'] ?? 'Manual points addition';

if ($userId <= 0 || $points <= 0) {
    // Return error at root level for Flutter compatibility
    $response = [
        'success' => false,
        'message' => 'Invalid parameters'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$mysqli->begin_transaction();
try {
    // Add points to wallet
    $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance + ? WHERE user_id = ?');
    $stmt->bind_param('ii', $points, $userId);
    if (!$stmt->execute()) throw new Exception('Failed to update wallet');
    $stmt->close();

    // Update user total points
    $stmt = $mysqli->prepare('UPDATE users SET total_points = total_points + ? WHERE user_id = ?');
    $stmt->bind_param('ii', $points, $userId);
    if (!$stmt->execute()) throw new Exception('Failed to update user points');
    $stmt->close();

    // Create transaction record
    $transactionCode = 'MAN-' . strtoupper(bin2hex(random_bytes(4)));
    $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES (?, ?, 0, ?, NOW(), ?, \'completed\')');
    $qrCode = 'MANUAL-' . $userId . '-' . time(); // Add timestamp to make it unique
    $stmt->bind_param('isis', $userId, $transactionCode, $points, $qrCode);
    if (!$stmt->execute()) throw new Exception('Failed to create transaction');
    $stmt->close();

    // Note: Database notifications are now handled by Flutter app based on user preferences
    // This ensures notifications respect the user's notification settings

    $mysqli->commit();
    
    // Return success at root level for Flutter compatibility
    $response = [
        'success' => true,
        'message' => "Successfully added $points points",
        'new_balance' => $points, // This would need a separate query to get actual balance
        'transaction_code' => $transactionCode
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} catch (Throwable $e) {
    $mysqli->rollback();
    
    // Return error at root level for Flutter compatibility
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
