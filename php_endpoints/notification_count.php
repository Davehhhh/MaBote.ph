<?php
require __DIR__ . '/db.php';

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    respond(false, 'user_id required');
}

// Get unread notification count
$stmt = $mysqli->prepare('
    SELECT COUNT(*) as unread_count
    FROM notification
    WHERE user_id = ? AND is_read = 0
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

$unreadCount = (int)$result['unread_count'];

respond(true, 'OK', ['unread_count' => $unreadCount]);
?>
