<?php
// htdocs/mabote_api/finalize_deposit.php
require __DIR__ . '/db.php';

$body = json_body();
$sessionToken = trim($body['session_token'] ?? '');
$bottleCount = (int)($body['bottle_count'] ?? 0);
$bottleWeight = (float)($body['bottle_weight'] ?? 0); // Weight in grams
$pointsPerBottle = (int)($body['points_per_bottle'] ?? 5); // Default 5 points per bottle

if (!$sessionToken) {
  respond(false, 'session_token required');
}

// If bottle_count is 0, calculate from weight (assuming average bottle weight)
if ($bottleCount <= 0 && $bottleWeight > 0) {
  $averageBottleWeight = 25; // Average plastic bottle weight in grams
  $bottleCount = max(1, round($bottleWeight / $averageBottleWeight));
}

if ($bottleCount <= 0) {
  respond(false, 'No bottle detected. Please insert a bottle.');
}

// Find and validate session
$stmt = $mysqli->prepare('SELECT session_id, user_id, bin_id FROM deposit_session WHERE session_token = ? AND status = \'open\' AND expires_at > NOW() LIMIT 1');
$stmt->bind_param('s', $sessionToken);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) respond(false, 'Invalid or expired session');

$userId = (int)$session['user_id'];
$binId = (int)$session['bin_id'];
$points = $bottleCount * $pointsPerBottle;
$transactionCode = 'TRX-' . strtoupper(bin2hex(random_bytes(4)));

$mysqli->begin_transaction();
try {
  // Insert transaction
  $stmt = $mysqli->prepare('INSERT INTO transactions (user_id, bin_id, transaction_code, bottle_deposited, points_earned, transaction_status) VALUES (?,?,?,?,?,\'completed\')');
  $stmt->bind_param('iisii', $userId, $binId, $transactionCode, $bottleCount, $points);
  if (!$stmt->execute()) throw new Exception('Failed to insert transaction');
  $stmt->close();

  // Update user total points
  $stmt = $mysqli->prepare('UPDATE users SET total_points = total_points + ? WHERE user_id = ?');
  $stmt->bind_param('ii', $points, $userId);
  if (!$stmt->execute()) throw new Exception('Failed to update user points');
  $stmt->close();

  // Update wallet
  $stmt = $mysqli->prepare('UPDATE wallet SET current_balance = current_balance + ?, total_earned = total_earned + ?, last_transaction_date = NOW() WHERE user_id = ?');
  $stmt->bind_param('iii', $points, $points, $userId);
  if (!$stmt->execute()) throw new Exception('Failed to update wallet');
  $stmt->close();

  // Close session
  $stmt = $mysqli->prepare('UPDATE deposit_session SET status = \'closed\' WHERE session_id = ?');
  $stmt->bind_param('i', $session['session_id']);
  $stmt->execute();
  $stmt->close();

  // Note: Database notifications are now handled by Flutter app based on user preferences
  // This ensures notifications respect the user's notification settings

  // Get new total points
  $res = $mysqli->query('SELECT total_points FROM users WHERE user_id = ' . $userId);
  $row = $res->fetch_assoc();
  $newTotal = (int)$row['total_points'];

  $mysqli->commit();
  respond(true, 'Bottle deposit successful', [
    'points_added' => $points,
    'new_total_points' => $newTotal,
    'transaction_code' => $transactionCode,
    'bottles_deposited' => $bottleCount,
    'bottle_weight' => $bottleWeight,
    'machine_status' => 'locked',
    'message' => "Successfully deposited $bottleCount bottle(s) and earned $points points!"
  ]);
} catch (Throwable $e) {
  $mysqli->rollback();
  respond(false, 'Failed to process deposit');
}
