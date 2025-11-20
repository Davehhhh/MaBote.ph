<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$qrCode = trim($body['qr_code'] ?? '');

if ($userId <= 0 || !$qrCode) {
    respond(false, 'user_id and qr_code required');
}

$mysqli->begin_transaction();
try {
    // Check if user exists and get current balance
    $stmt = $mysqli->prepare('SELECT current_balance FROM wallet WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$wallet) {
        throw new Exception('User wallet not found');
    }

    // Generate transaction code
    $transactionCode = 'TRX-' . strtoupper(bin2hex(random_bytes(4)));
    
    // Calculate points (5 points per bottle, assuming 1 bottle for now)
    $bottlesDeposited = 1;
    $pointsEarned = $bottlesDeposited * 5;

    // Create transaction record
    $stmt = $mysqli->prepare('
        INSERT INTO transactions (user_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status)
        VALUES (?, ?, ?, ?, NOW(), ?, \'completed\')
    ');
    $stmt->bind_param('isiis', $userId, $transactionCode, $bottlesDeposited, $pointsEarned, $qrCode);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create transaction');
    }
    $stmt->close();

    // Update wallet balance
    $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance + ?, total_earned = total_earned + ?, last_transaction_date = NOW() WHERE user_id = ?');
    $stmt->bind_param('iii', $pointsEarned, $pointsEarned, $userId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update wallet');
    }
    $stmt->close();

    // Update user total points
    $stmt = $mysqli->prepare('UPDATE users SET total_points = total_points + ? WHERE user_id = ?');
    $stmt->bind_param('ii', $pointsEarned, $userId);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update user points');
    }
    $stmt->close();

    // Note: Database notifications are now handled by Flutter app based on user preferences
    // This ensures notifications respect the user's notification settings

    $mysqli->commit();
    
    // Get updated balance
    $stmt = $mysqli->prepare('SELECT current_balance FROM wallet WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $newWallet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    respond(true, 'Deposit successful', [
        'points_added' => $pointsEarned,
        'new_total_points' => $newWallet['current_balance'],
        'transaction_code' => $transactionCode,
    ]);
} catch (Exception $e) {
    $mysqli->rollback();
    respond(false, $e->getMessage());
}
?>
