<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$body = json_body();
$userId = (int)($body['user_id'] ?? 0);
$firstName = trim($body['first_name'] ?? '');
$lastName = trim($body['last_name'] ?? '');
$phone = trim($body['phone'] ?? '');
$address = trim($body['address'] ?? '');
$barangay = trim($body['barangay'] ?? '');
$city = trim($body['city'] ?? '');

if ($userId <= 0 || !$firstName || !$lastName) {
    respond(false, 'user_id, first_name, last_name required');
}

$stmt = $mysqli->prepare('
  UPDATE users
  SET first_name = ?, last_name = ?, phone = ?, address = ?, barangay = ?, city = ?
  WHERE user_id = ? AND is_active = 1
');
$stmt->bind_param('ssssssi', $firstName, $lastName, $phone, $address, $barangay, $city, $userId);
if (!$stmt->execute()) respond(false, 'Update failed');
$stmt->close();

respond(true, 'Profile updated');
?>
