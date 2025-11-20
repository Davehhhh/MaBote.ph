<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) respond(false, 'user_id required');

if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'No image uploaded or upload error');
}

$file = $_FILES['profile_image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Check both MIME type and file extension
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$isValidType = in_array($file['type'], $allowedTypes) || in_array($fileExtension, $allowedExtensions);

if (!$isValidType) {
    respond(false, 'Invalid file type. Only JPEG, PNG, and GIF are allowed');
}

if ($file['size'] > $maxSize) {
    respond(false, 'File too large. Maximum size is 5MB');
}

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/profile_images/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = "user_{$userId}_" . time() . ".{$extension}";
$filepath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    respond(false, 'Failed to save image');
}

// Update user profile image path in database
$imageUrl = "uploads/profile_images/{$filename}";
$stmt = $mysqli->prepare('UPDATE users SET user_profile = ? WHERE user_id = ?');
$stmt->bind_param('si', $imageUrl, $userId);

if (!$stmt->execute()) {
    // Delete the uploaded file if database update fails
    unlink($filepath);
    respond(false, 'Failed to update profile image in database');
}
$stmt->close();

// Return data at root level for Flutter compatibility
$response = [
    'success' => true,
    'message' => 'Profile image updated successfully',
    'image_url' => $imageUrl
];

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
