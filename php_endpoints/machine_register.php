<?php
// Machine Registration API
// File: php_endpoints/machine_register.php

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
$location = $input['location'] ?? '';
$status = $input['status'] ?? 'active';

if (empty($machine_id)) {
    echo json_encode(['success' => false, 'message' => 'Machine ID required']);
    exit;
}

try {
    // Check if machine already exists
    $stmt = $mysqli->prepare("SELECT machine_id FROM machines WHERE machine_id = ?");
    $stmt->bind_param('s', $machine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing machine
        $stmt = $mysqli->prepare("UPDATE machines SET location = ?, status = ?, last_seen = NOW() WHERE machine_id = ?");
        $stmt->bind_param('sss', $location, $status, $machine_id);
    } else {
        // Insert new machine
        $stmt = $mysqli->prepare("INSERT INTO machines (machine_id, location, status, last_seen) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('sss', $machine_id, $location, $status);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Machine registered successfully',
            'machine_id' => $machine_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to register machine']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>







