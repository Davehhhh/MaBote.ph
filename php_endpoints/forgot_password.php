<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

$body = json_body();
$email = strtolower(trim($body['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Valid email address is required');
}

// Check if user exists
$stmt = $mysqli->prepare('SELECT user_id, first_name, last_name FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // Don't reveal if email exists or not for security
    respond(true, 'If the email exists, a password reset link has been sent');
}

// Generate secure token
$token = bin2hex(random_bytes(32));
// Use MySQL's DATE_ADD to avoid timezone issues
$result = $mysqli->query("SELECT DATE_ADD(NOW(), INTERVAL 2 HOUR) as expires_at");
$row = $result->fetch_assoc();
$expiresAt = $row['expires_at'];

// Store token in database
$stmt = $mysqli->prepare('INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $user['user_id'], $token, $expiresAt);
if (!$stmt->execute()) {
    respond(false, 'Failed to generate reset token');
}
$stmt->close();

// Create reset link - use the same base URL as the Flutter app
$baseUrl = 'http://192.168.254.119/mabote_api';
$resetLink = "$baseUrl/reset_password.php?token=$token";

// Try to send email using different methods
$emailSent = false;
$subject = "MaBote.ph - Password Reset Request";

// Create HTML email content
$htmlMessage = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #79C765; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
        .button { display: inline-block; background: #79C765; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🌱 MaBote.ph</h1>
            <p>Eco-Friendly Bottle Collection</p>
        </div>
        <div class='content'>
            <h2>Password Reset Request</h2>
            <p>Hello {$user['first_name']},</p>
            <p>You requested a password reset for your MaBote.ph account.</p>
            <p>Click the button below to reset your password:</p>
            <p><a href='$resetLink' class='button'>Reset My Password</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p style='word-break: break-all; background: #eee; padding: 10px; border-radius: 4px;'>$resetLink</p>
            <p><strong>This link will expire in 1 hour.</strong></p>
            <p>If you didn't request this reset, please ignore this email.</p>
            <p>Best regards,<br>The MaBote.ph Team</p>
        </div>
        <div class='footer'>
            <p>© 2025 MaBote.ph - Sustainable Bottle Collection System</p>
        </div>
    </div>
</body>
</html>
";

// Set headers for HTML email
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: MaBote.ph <noreply@mabote.ph>\r\n";
$headers .= "Reply-To: support@mabote.ph\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Try to send email
if (mail($email, $subject, $htmlMessage, $headers)) {
    $emailSent = true;
}

// For development/testing - also return the reset link
if ($emailSent) {
    respond(true, 'Password reset link has been sent to your email', [
        'email_sent' => true,
        'reset_link' => $resetLink,
        'expires_in' => '1 hour',
        'user_name' => $user['first_name']
    ]);
} else {
    // If email fails, still show the link for testing
    respond(true, 'Password reset link generated', [
        'email_sent' => false,
        'reset_link' => $resetLink,
        'expires_in' => '1 hour',
        'user_name' => $user['first_name'],
        'instructions' => 'Email sending failed. Copy the reset link below and open it in your browser to reset your password'
    ]);
}
?>
