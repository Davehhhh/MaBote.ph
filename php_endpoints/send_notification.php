<?php
require __DIR__ . '/db.php';

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$notificationType = trim($body['notification_type'] ?? '');
$title = trim($body['title'] ?? '');
$message = trim($body['message'] ?? '');
$payload = trim($body['payload'] ?? '');

if ($userId <= 0 || !$notificationType || !$title || !$message) {
    respond(false, 'user_id, notification_type, title, and message are required');
}

// Insert notification into database
$stmt = $mysqli->prepare('
    INSERT INTO notification (user_id, notification_type, title, message, sent_at, is_read, priority)
    VALUES (?, ?, ?, ?, NOW(), 0, ?)
');
$priority = 'medium'; // Default priority
$stmt->bind_param('issss', $userId, $notificationType, $title, $message, $priority);

if (!$stmt->execute()) {
    respond(false, 'Failed to save notification');
}
$stmt->close();

// Here you would typically send push notification via Firebase
// For now, we'll just save to database
// TODO: Integrate with Firebase Cloud Messaging (FCM)

respond(true, 'Notification sent successfully', [
    'notification_id' => $mysqli->insert_id,
    'sent_at' => date('Y-m-d H:i:s'),
]);
?>
