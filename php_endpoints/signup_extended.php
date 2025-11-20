<?php
// htdocs/mabote_api/signup_extended.php
error_reporting(0); // Suppress errors to prevent output before JSON
ini_set('display_errors', 0);

require __DIR__ . '/db.php';

try {
    $body = json_body();
    $first = trim($body['first_name'] ?? '');
    $last  = trim($body['last_name'] ?? '');
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';
    $phone = trim($body['phone'] ?? '');
    $address = trim($body['address'] ?? '');
    $barangay = trim($body['barangay'] ?? '');
    $city = trim($body['city'] ?? '');

    if (!$first || !$last || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        respond(false, 'Invalid input. Please provide valid first name, last name, email, and password (minimum 6 characters).');
    }

    $stmt = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    if (!$stmt) {
        respond(false, 'Database error: ' . $mysqli->error);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        respond(false, 'Email already registered');
    }
    $stmt->close();

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $qrId = 'QR' . strtoupper(bin2hex(random_bytes(8))); // Generate unique QR ID

    $stmt = $mysqli->prepare('INSERT INTO users (first_name, last_name, email, password, password_hash, phone, address, barangay, city, qr_id, is_active, total_points, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,0,NOW())');
    if (!$stmt) {
        respond(false, 'Database error: ' . $mysqli->error);
    }
    $stmt->bind_param('ssssssssss', $first, $last, $email, $hash, $hash, $phone, $address, $barangay, $city, $qrId);
    if (!$stmt->execute()) {
        $error = $stmt->error ?: $mysqli->error;
        $stmt->close();
        respond(false, 'Failed to create user: ' . $error);
    }
    $userId = $stmt->insert_id;
    $stmt->close();

    // Create wallet row with 0 balance
    $stmt = $mysqli->prepare('INSERT INTO wallet (user_id, current_balance, is_active, wallet_status) VALUES (?,0,1,\'active\')');
    if (!$stmt) {
        respond(false, 'Database error: ' . $mysqli->error);
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $error = $stmt->error ?: $mysqli->error;
        $stmt->close();
        respond(false, 'Failed to create wallet: ' . $error);
    }
    $stmt->close();

    // Generate session token for auto-login
    $token = bin2hex(random_bytes(32));

    // Save session data
    $stmt = $mysqli->prepare('INSERT INTO sessions (user_id, token, created_at, expires_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))');
    if (!$stmt) {
        respond(false, 'Database error: ' . $mysqli->error);
    }
    $stmt->bind_param('is', $userId, $token);
    if (!$stmt->execute()) {
        $error = $stmt->error ?: $mysqli->error;
        $stmt->close();
        respond(false, 'Failed to create session: ' . $error);
    }
    $stmt->close();

    respond(true, 'Account created', [
        'user_id' => $userId, 
        'name' => $first . ' ' . $last,
        'email' => $email,
        'token' => $token,
        'qr_id' => $qrId
    ]);
} catch (Throwable $e) {
    respond(false, 'Server error: ' . $e->getMessage());
}
