<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) respond(false, 'user_id required');

$stmt = $mysqli->prepare('
  SELECT user_id, first_name, last_name, email, phone, address, barangay, city, user_profile, qr_id
  FROM users
  WHERE user_id = ? AND is_active = 1
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Return profile data at root level for Flutter compatibility
$response = array_merge([
    'success' => true,
    'message' => 'OK',
], $user);

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
