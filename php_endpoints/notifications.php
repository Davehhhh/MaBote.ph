<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) respond(false, 'user_id required');

$stmt = $mysqli->prepare('
  SELECT notification_id, notification_type, title, message, sent_at, is_read, priority
  FROM notification
  WHERE user_id = ?
  ORDER BY sent_at DESC
  LIMIT 50
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
  $notifications[] = $row;
}
$stmt->close();

// Return notifications data at root level for Flutter compatibility
$response = [
    'success' => true,
    'message' => 'OK',
    'notifications' => $notifications
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
