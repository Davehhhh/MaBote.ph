<?php
require __DIR__ . '/db.php';

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$oldPassword = $body['old_password'] ?? '';
$newPassword = $body['new_password'] ?? '';

if ($userId <= 0 || !$oldPassword || !$newPassword) {
    respond(false, 'user_id, old_password, and new_password are required');
}

if (strlen($newPassword) < 6) {
    respond(false, 'New password must be at least 6 characters');
}

// Verify old password
$stmt = $mysqli->prepare('SELECT password_hash FROM users WHERE user_id = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
    respond(false, 'Current password is incorrect');
}

// Update password
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE user_id = ? AND is_active = 1');
$stmt->bind_param('si', $newHash, $userId);

if (!$stmt->execute()) {
    respond(false, 'Failed to update password');
}
$stmt->close();

respond(true, 'Password changed successfully');
?>
