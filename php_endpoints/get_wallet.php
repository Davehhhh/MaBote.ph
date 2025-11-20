<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) respond(false, 'user_id required');

// Check if user exists
$stmt = $mysqli->prepare('SELECT user_id FROM users WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    respond(false, 'User not found');
}

// Check if wallet exists, create if not
$stmt = $mysqli->prepare('SELECT wallet_id FROM wallet WHERE user_id = ? AND is_active = 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$walletExists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$walletExists) {
    // Create wallet for new user
    $stmt = $mysqli->prepare('INSERT INTO wallet (user_id, current_balance, is_active, wallet_status) VALUES (?, 0, 1, "active")');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

// Get wallet with comprehensive stats
$stmt = $mysqli->prepare('
  SELECT 
    w.current_balance,
    u.total_points as total_earned,
    COALESCE((SELECT SUM(points_used) FROM redemption WHERE user_id = ?), 0) as total_redeemed,
    COUNT(DISTINCT t.transaction_id) as total_deposits,
    COUNT(DISTINCT r.redemption_id) as total_redemptions,
    w.last_transaction_date,
    w.wallet_status
  FROM wallet w
  LEFT JOIN users u ON w.user_id = u.user_id
  LEFT JOIN transactions t ON w.user_id = t.user_id
  LEFT JOIN redemption r ON w.user_id = r.user_id
  WHERE w.user_id = ? AND w.is_active = 1
  GROUP BY w.current_balance, u.total_points, w.last_transaction_date, w.wallet_status
');
$stmt->bind_param('ii', $userId, $userId);
$stmt->execute();
$wallet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$wallet) {
  // This shouldn't happen since we create wallet above, but just in case
  $wallet = [
    'current_balance' => 0,
    'total_earned' => 0,
    'total_redeemed' => 0,
    'total_deposits' => 0,
    'total_redemptions' => 0,
    'last_transaction_date' => null,
    'wallet_status' => 'active'
  ];
} else {
  // Format the data
  $wallet = [
    'current_balance' => (int)$wallet['current_balance'],
    'total_earned' => (int)$wallet['total_earned'],
    'total_redeemed' => (int)$wallet['total_redeemed'],
    'total_deposits' => (int)$wallet['total_deposits'],
    'total_redemptions' => (int)$wallet['total_redemptions'],
    'last_transaction_date' => $wallet['last_transaction_date'],
    'wallet_status' => $wallet['wallet_status']
  ];
}

// Return wallet data at root level for Flutter compatibility
$response = array_merge([
    'success' => true,
    'message' => 'OK',
], $wallet);

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
