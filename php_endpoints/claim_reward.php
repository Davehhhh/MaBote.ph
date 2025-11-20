<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$rewardId = (int)($body['reward_id'] ?? 0);
$pointsRequired = (int)($body['points_required'] ?? 0);

if ($userId <= 0 || $rewardId <= 0 || $pointsRequired <= 0) {
  respond(false, 'Invalid parameters');
}

$mysqli->begin_transaction();
try {
  // Check if user has enough points
  $stmt = $mysqli->prepare('SELECT current_balance FROM wallet WHERE user_id = ? LIMIT 1');
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $wallet = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$wallet || (int)$wallet['current_balance'] < $pointsRequired) {
    throw new Exception('Insufficient points');
  }

  // Check if reward is available
  // Handle NULL is_active (treat as active since default is 1)
  $stmt = $mysqli->prepare('SELECT reward_name, quantity_available FROM reward WHERE reward_id = ? AND (is_active IS NULL OR is_active = 1) LIMIT 1');
  $stmt->bind_param('i', $rewardId);
  $stmt->execute();
  $reward = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$reward || (int)$reward['quantity_available'] <= 0) {
    throw new Exception('Reward not available');
  }

  // Deduct points from wallet
  $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance - ?, total_spent = total_spent + ? WHERE user_id = ?');
  $stmt->bind_param('iii', $pointsRequired, $pointsRequired, $userId);
  if (!$stmt->execute()) throw new Exception('Failed to deduct points: ' . $mysqli->error);
  $stmt->close();
  
  // Get updated wallet balance
  $stmt = $mysqli->prepare('SELECT current_balance FROM wallet WHERE user_id = ? LIMIT 1');
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $updatedWallet = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $remainingPoints = (int)$updatedWallet['current_balance'];

  // DON'T subtract from total_points - total_points should be cumulative earned points
  // Only subtract from current wallet balance (already done above)

  // Create redemption record
  // Based on actual table structure: redemption_id, user_id, reward_id, points_used, redemption_date, status
  $stmt = $mysqli->prepare('INSERT INTO redemption (user_id, reward_id, points_used, status) VALUES (?, ?, ?, \'completed\')');
  $stmt->bind_param('iii', $userId, $rewardId, $pointsRequired);
  if (!$stmt->execute()) throw new Exception('Failed to create redemption: ' . $mysqli->error);
  $redemptionId = $mysqli->insert_id;
  $stmt->close();
  
  // Generate redemption code for display (not stored in DB, but can be used in notification)
  $redemptionCode = 'RED-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $redemptionId;

  // Decrease reward quantity
  $stmt = $mysqli->prepare('UPDATE reward SET quantity_available = quantity_available - 1 WHERE reward_id = ?');
  $stmt->bind_param('i', $rewardId);
  if (!$stmt->execute()) throw new Exception('Failed to update reward quantity');
  $stmt->close();

  // Note: Database notifications are now handled by Flutter app based on user preferences
  // This ensures notifications respect the user's notification settings

  $mysqli->commit();
  
  // Return data at root level for Flutter compatibility
  $response = [
    'success' => true,
    'message' => 'Reward claimed successfully',
    'redemption_code' => $redemptionCode,
    'redemption_id' => $redemptionId,
    'remaining_points' => $remainingPoints,
    'reward_name' => $reward['reward_name']
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
