<?php
// Clean any existing output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Get JSON input
$input = file_get_contents('php://input');
$body = json_decode($input, true);

if (!$body) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Database connection
$host = '127.0.0.1';
$port = 3307;
$username = 'root';
$password = '';
$database = 'mabote_db';

$mysqli = new mysqli($host, $username, $password, $database, $port);

if ($mysqli->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $mysqli->connect_error
    ]);
    exit;
}

$mysqli->set_charset('utf8mb4');

$email = strtolower(trim($body['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Valid email address is required'
    ]);
    exit;
}

// Find user by email
$stmt = $mysqli->prepare('SELECT user_id, first_name, last_name, email FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'User not found with email: ' . $email
    ]);
    exit;
}

// Return user data
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'User found',
    'user_id' => (int)$user['user_id'],
    'name' => $user['first_name'] . ' ' . $user['last_name'],
    'email' => $user['email']
]);
exit;
?>
