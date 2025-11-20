<?php
// Clean delete notification API - NO HTML output
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$mysqli->set_charset('utf8mb4');

// Get JSON input
$input = file_get_contents('php://input');
$body = json_decode($input, true);

if (!$body) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$userId = (int)($body['user_id'] ?? 0);
$notificationId = (int)($body['notification_id'] ?? 0);

if ($userId <= 0 || $notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Delete the notification
$stmt = $mysqli->prepare('DELETE FROM notification WHERE notification_id = ? AND user_id = ?');
$stmt->bind_param('ii', $notificationId, $userId);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
}

$stmt->close();
$mysqli->close();
exit;
?>
