<?php
// htdocs/mabote_api/start_session.php
require __DIR__ . '/db.php';

$body = json_body();
$qrCode = trim($body['qr_code'] ?? '');
$binId = (int)($body['bin_id'] ?? 0);
$expiresInSec = (int)($body['expires_in_sec'] ?? 300); // 5 minutes default

if (!$qrCode || $binId <= 0) {
  respond(false, 'qr_code and bin_id required');
}

// Find user by QR ID
$stmt = $mysqli->prepare('SELECT user_id, first_name, last_name FROM users WHERE qr_id = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('s', $qrCode);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) respond(false, 'Invalid QR code');

$userId = (int)$user['user_id'];

// Check for existing open session for this user
$stmt = $mysqli->prepare('SELECT session_id FROM deposit_session WHERE user_id = ? AND status = \'open\' AND expires_at > NOW() LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
  $stmt->close();
  respond(false, 'User already has an active session');
}
$stmt->close();

// Create new session
$sessionToken = bin2hex(random_bytes(16));
$expiresAt = date('Y-m-d H:i:s', time() + $expiresInSec);

$stmt = $mysqli->prepare('INSERT INTO deposit_session (user_id, bin_id, session_token, status, expires_at) VALUES (?,?,?,\'open\',?)');
$stmt->bind_param('iiss', $userId, $binId, $sessionToken, $expiresAt);
if (!$stmt->execute()) respond(false, 'Failed to create session');
$stmt->close();

respond(true, 'User verified - Machine unlocked', [
  'session_token' => $sessionToken,
  'user_name' => $user['first_name'] . ' ' . $user['last_name'],
  'user_id' => $userId,
  'expires_at' => $expiresAt,
  'machine_status' => 'unlocked',
  'message' => 'User verified successfully. Machine is now unlocked for bottle deposit.'
]);
