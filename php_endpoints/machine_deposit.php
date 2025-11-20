<?php
// Machine Bottle Deposit API
// File: php_endpoints/machine_deposit.php

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
$user_qr = $input['user_qr'] ?? '';
$bottles_detected = $input['bottles_detected'] ?? 0;
$weight_grams = $input['weight_grams'] ?? 0;
$timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

if (empty($machine_id) || empty($user_qr)) {
    echo json_encode(['success' => false, 'message' => 'Machine ID and User QR required']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // Get user ID from QR code
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE qr_code = ?");
    $stmt->bind_param('s', $user_qr);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Invalid QR code');
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['user_id'];
    
    // Calculate points (assuming 1 point per bottle)
    $points_earned = $bottles_detected;
    
    // Generate transaction code
    $transaction_code = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
    // Insert transaction
    $stmt = $mysqli->prepare("INSERT INTO transactions (user_id, machine_id, transaction_code, bottle_deposited, points_earned, transaction_date, qr_code_scanned, transaction_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
    $stmt->bind_param('iisisss', $user_id, $machine_id, $transaction_code, $bottles_detected, $points_earned, $timestamp, $user_qr);
    $stmt->execute();
    
    $transaction_id = $mysqli->insert_id;
    
    // Update user wallet
    $stmt = $mysqli->prepare("INSERT INTO wallet (user_id, current_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE current_balance = current_balance + ?");
    $stmt->bind_param('iii', $user_id, $points_earned, $points_earned);
    $stmt->execute();
    
    // Update machine last activity
    $stmt = $mysqli->prepare("UPDATE machines SET last_seen = NOW() WHERE machine_id = ?");
    $stmt->bind_param('s', $machine_id);
    $stmt->execute();
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Bottle deposit successful',
        'transaction_id' => $transaction_id,
        'transaction_code' => $transaction_code,
        'bottles_deposited' => $bottles_detected,
        'points_earned' => $points_earned,
        'user_id' => $user_id
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>







