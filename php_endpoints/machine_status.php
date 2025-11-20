<?php
// Machine Status Update API
// File: php_endpoints/machine_status.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$machine_id = $input['machine_id'] ?? '';
$fill_level = $input['fill_level'] ?? 0;
$status = $input['status'] ?? 'active';
$battery_level = $input['battery_level'] ?? 100;
$temperature = $input['temperature'] ?? 25;
$last_maintenance = $input['last_maintenance'] ?? null;

if (empty($machine_id)) {
    echo json_encode(['success' => false, 'message' => 'Machine ID required']);
    exit;
}

try {
    // Update machine status
    $stmt = $mysqli->prepare("UPDATE machines SET fill_level = ?, status = ?, battery_level = ?, temperature = ?, last_maintenance = ?, last_seen = NOW() WHERE machine_id = ?");
    $stmt->bind_param('isssss', $fill_level, $status, $battery_level, $temperature, $last_maintenance, $machine_id);
    
    if ($stmt->execute()) {
        // Check if machine needs maintenance (fill level > 80%)
        if ($fill_level > 80) {
            // Create maintenance alert notification
            $stmt = $mysqli->prepare("INSERT INTO notification (user_id, title, message, notification_type, priority) VALUES (0, 'Maintenance Alert', 'Machine {$machine_id} needs maintenance - Fill level: {$fill_level}%', 'maintenance', 'high')");
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'machine_id' => $machine_id,
            'fill_level' => $fill_level,
            'status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>