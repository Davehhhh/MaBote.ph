<?php
// Clean delete all notifications API - NO HTML output
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
$personalOnly = isset($body['personal_only']) ? (bool)$body['personal_only'] : false;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Delete all notifications for the user (personal only if specified)
if ($personalOnly) {
    $stmt = $mysqli->prepare('DELETE FROM notification WHERE user_id = ? AND notification_type != "system"');
} else {
    $stmt = $mysqli->prepare('DELETE FROM notification WHERE user_id = ?');
}
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    $deletedCount = $stmt->affected_rows;
    echo json_encode(['success' => true, 'message' => "Deleted $deletedCount notifications successfully"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete notifications']);
}

$stmt->close();
$mysqli->close();
exit;
?>

