<?php
// Ultimate clean delete notification API - absolutely NO HTML output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear all output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Database connection with explicit settings
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
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
        echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $userId = isset($body['user_id']) ? (int)$body['user_id'] : 0;
    $notificationId = isset($body['notification_id']) ? (int)$body['notification_id'] : 0;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user_id: ' . $userId]);
        exit;
    }
    
    if ($notificationId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification_id: ' . $notificationId]);
        exit;
    }
    
    // Check if notification exists and belongs to user
    $checkStmt = $mysqli->prepare('SELECT notification_id FROM notification WHERE notification_id = ? AND user_id = ?');
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $mysqli->error]);
        exit;
    }
    
    $checkStmt->bind_param('ii', $notificationId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        echo json_encode(['success' => false, 'message' => 'Notification not found or access denied']);
        exit;
    }
    $checkStmt->close();
    
    // Delete the notification
    $deleteStmt = $mysqli->prepare('DELETE FROM notification WHERE notification_id = ? AND user_id = ?');
    if (!$deleteStmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $mysqli->error]);
        exit;
    }
    
    $deleteStmt->bind_param('ii', $notificationId, $userId);
    
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Notification deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No notification was deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete execution failed: ' . $deleteStmt->error]);
    }
    
    $deleteStmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

exit;
?>

