<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

// Get JSON input
$input = file_get_contents('php://input');
$body = json_decode($input, true);

if (!$body) {
    $response = [
        'success' => false,
        'message' => 'Invalid JSON input'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$userId = (int)($body['user_id'] ?? 0);

if ($userId <= 0) {
    // Return error at root level for Flutter compatibility
    $response = [
        'success' => false,
        'message' => 'Invalid user ID'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Delete all notifications for the user
$stmt = $mysqli->prepare('DELETE FROM notification WHERE user_id = ?');
$stmt->bind_param('i', $userId);

if ($stmt->execute()) {
    $deletedCount = $stmt->affected_rows;
    // Return success at root level for Flutter compatibility
    $response = [
        'success' => true,
        'message' => "Deleted $deletedCount notifications successfully"
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Return error at root level for Flutter compatibility
    $response = [
        'success' => false,
        'message' => 'Failed to delete notifications'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$stmt->close();
?>
