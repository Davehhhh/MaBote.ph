<?php
require __DIR__ . '/db.php';

$body = json_body();
$email = strtolower(trim($body['email'] ?? ''));
$password = $body['password'] ?? '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$password) {
    respond(false, 'Email and password are required');
}

// Find user by email
$stmt = $mysqli->prepare('SELECT user_id, first_name, last_name, email, password_hash, qr_id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond(false, 'Invalid email or password');
}

// Generate session token
$token = bin2hex(random_bytes(32));

// Save session data
$stmt = $mysqli->prepare('INSERT INTO sessions (user_id, token, created_at, expires_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))');
$stmt->bind_param('is', $user['user_id'], $token);
$stmt->execute();
$stmt->close();

// Return data at root level for Flutter compatibility
$response = [
    'success' => true,
    'message' => 'Login successful',
    'user_id' => (int)$user['user_id'],
    'name' => $user['first_name'] . ' ' . $user['last_name'],
    'email' => $user['email'],
    'token' => $token,
    'qr_id' => $user['qr_id']
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
