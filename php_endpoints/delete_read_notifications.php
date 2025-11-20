<?php
// Delete all read notifications for a user
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    $mysqli->set_charset('utf8mb4');
    
    // Get JSON input
    $input = file_get_contents('php://input');
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No input data received']);
        exit;
    }
    
    $body = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $userId = isset($body['user_id']) ? (int)$body['user_id'] : 0;
    $personalOnly = isset($body['personal_only']) ? (bool)$body['personal_only'] : false;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
        exit;
    }
    
    // Delete all read notifications for this user (personal only if specified)
    if ($personalOnly) {
        $stmt = $mysqli->prepare('DELETE FROM notification WHERE user_id = ? AND is_read = 1 AND notification_type != "system"');
    } else {
        $stmt = $mysqli->prepare('DELETE FROM notification WHERE user_id = ? AND is_read = 1');
    }
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error']);
        exit;
    }
    
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        $deletedCount = $mysqli->affected_rows;
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted $deletedCount read notifications",
            'deleted_count' => $deletedCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete read notifications']);
    }
    
    $stmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}

exit;
?>
