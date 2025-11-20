<?php
require __DIR__ . '/db.php';

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);

if ($userId <= 0) {
    respond(false, 'user_id required');
}

// Mark all notifications as read for this user
$stmt = $mysqli->prepare('UPDATE notification SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    $affectedRows = $mysqli->affected_rows;
    respond(true, 'Notifications marked as read', ['affected_rows' => $affectedRows]);
} else {
    respond(false, 'Failed to mark notifications as read');
}

$stmt->close();
?>
