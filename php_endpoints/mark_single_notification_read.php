<?php
require __DIR__ . '/db.php';

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$notificationId = (int)($body['notification_id'] ?? 0);

if ($userId <= 0) {
    respond(false, 'user_id required');
}

if ($notificationId <= 0) {
    respond(false, 'notification_id required');
}

// Mark specific notification as read for this user
$stmt = $mysqli->prepare('UPDATE notification SET is_read = 1 WHERE user_id = ? AND notification_id = ? AND is_read = 0');
$stmt->bind_param('ii', $userId, $notificationId);

if ($stmt->execute()) {
    $affectedRows = $mysqli->affected_rows;
    if ($affectedRows > 0) {
        respond(true, 'Notification marked as read', ['affected_rows' => $affectedRows]);
    } else {
        respond(true, 'Notification already read or not found', ['affected_rows' => 0]);
    }
} else {
    respond(false, 'Failed to mark notification as read');
}

$stmt->close();
?>







