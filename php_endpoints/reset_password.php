<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

// Check if token is provided
$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Reset token is required']);
    exit;
}

try {
    // Verify token exists and is not expired
    $stmt = $mysqli->prepare('
        SELECT prt.user_id, prt.expires_at, u.first_name, u.last_name, u.email 
        FROM password_reset_tokens prt 
        JOIN users u ON prt.user_id = u.user_id 
        WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used_at IS NULL
    ');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        exit;
    }

    // Check if this is a POST request (password reset form submission)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $body = json_body();
        $newPassword = $body['password'] ?? '';
        $confirmPassword = $body['confirm_password'] ?? '';

        if (!$newPassword || strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Update user password
            $stmt = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $stmt->bind_param('si', $hashedPassword, $result['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update password');
            }
            $stmt->close();

            // Mark token as used
            $stmt = $mysqli->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?');
            $stmt->bind_param('s', $token);
            if (!$stmt->execute()) {
                throw new Exception('Failed to mark token as used');
            }
            $stmt->close();

            $mysqli->commit();

            echo json_encode([
                'success' => true, 
                'message' => 'Password reset successfully! You can now login with your new password.',
                'redirect_url' => 'http://192.168.254.119/mabote_api/login_success.html'
            ]);
            exit;

        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to reset password: ' . $e->getMessage()]);
            exit;
        }
    }

    // If GET request, show password reset form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Password - MaBote.ph</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                position: relative;
                overflow: hidden;
            }
            
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
                opacity: 0.3;
                pointer-events: none;
            }
            
            .container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                padding: 40px;
                border-radius: 20;
                box-shadow: 
                    0 20px 40px rgba(0, 0, 0, 0.1),
                    0 8px 16px rgba(0, 0, 0, 0.06),
                    inset 0 1px 0 rgba(255, 255, 255, 0.5);
                width: 100%;
                max-width: 400px;
                position: relative;
                z-index: 1;
            }
            
            .logo {
                text-align: center;
                margin-bottom: 32px;
            }
            
            .logo-icon {
                display: flex;
                justify-content: center;
                margin-bottom: 16px;
            }
            
            .logo-circle {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 
                    0 8px 16px rgba(76, 175, 80, 0.3),
                    0 4px 8px rgba(76, 175, 80, 0.2);
                position: relative;
                overflow: hidden;
            }
            
            .logo-circle::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.2) 50%, transparent 70%);
                animation: logoShine 3s ease-in-out infinite;
            }
            
            .logo-text {
                color: white;
                font-size: 28px;
                font-weight: 800;
                letter-spacing: -0.5px;
                position: relative;
                z-index: 1;
            }
            
            @keyframes logoShine {
                0%, 100% { transform: translateX(-100%); }
                50% { transform: translateX(100%); }
            }
            
            .logo h1 {
                background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin: 0;
                font-size: 28px;
                font-weight: 700;
                letter-spacing: -0.5px;
            }
            
            .logo p {
                color: #64748b;
                margin: 8px 0 0 0;
                font-size: 14px;
                font-weight: 500;
            }
            
            .user-info {
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                padding: 20px;
                border-radius: 16;
                margin-bottom: 24px;
                text-align: center;
                border: 1px solid rgba(148, 163, 184, 0.2);
                position: relative;
                overflow: hidden;
            }
            
            .user-info::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: linear-gradient(90deg, #4CAF50 0%, #2E7D32 100%);
            }
            
            .user-info h3 {
                margin: 0 0 6px 0;
                color: #1e293b;
                font-weight: 600;
                font-size: 16px;
            }
            
            .user-info p {
                margin: 0;
                color: #64748b;
                font-size: 13px;
                font-weight: 500;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            label {
                display: block;
                margin-bottom: 8px;
                color: #374151;
                font-weight: 600;
                font-size: 14px;
                letter-spacing: 0.025em;
            }
            
            input[type="password"] {
                width: 100%;
                padding: 16px 20px;
                border: 2px solid #e5e7eb;
                border-radius: 16;
                font-size: 16px;
                font-weight: 500;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-sizing: border-box;
                background: #ffffff;
                color: #1f2937;
            }
            
            input[type="password"]:focus {
                outline: none;
                border-color: #4CAF50;
                box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
                transform: translateY(-1px);
            }
            
            input[type="password"]::placeholder {
                color: #9ca3af;
                font-weight: 400;
            }
            
            .btn {
                width: 100%;
                padding: 16px 24px;
                background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
                color: white;
                border: none;
                border-radius: 12;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 
                    0 8px 16px rgba(76, 175, 80, 0.25),
                    0 4px 8px rgba(76, 175, 80, 0.15);
                letter-spacing: 0.5px;
                position: relative;
                overflow: hidden;
            }
            
            .btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                transition: left 0.5s;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 
                    0 12px 24px rgba(76, 175, 80, 0.3),
                    0 6px 12px rgba(76, 175, 80, 0.2);
            }
            
            .btn:hover::before {
                left: 100%;
            }
            
            .btn:active {
                transform: translateY(0);
            }
            
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
            
            .message {
                padding: 16px 20px;
                border-radius: 12;
                margin-bottom: 20px;
                text-align: center;
                font-weight: 500;
                border: 1px solid;
            }
            
            .success {
                background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
                color: #065f46;
                border-color: #10b981;
            }
            
            .error {
                background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
                color: #991b1b;
                border-color: #ef4444;
            }
            
            @media (max-width: 480px) {
                .container {
                    padding: 24px 20px;
                    margin: 16px;
                }
                
                .logo h1 {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">
                <div class="logo-icon">
                    <div class="logo-circle">
                        <span class="logo-text">♻</span>
                    </div>
                </div>
                <h1>MaBote.ph</h1>
                <p>Reset Your Password</p>
            </div>

            <div class="user-info">
                <h3>Hello, <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>!</h3>
                <p><?php echo htmlspecialchars($result['email']); ?></p>
            </div>

            <form id="resetForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Enter new password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Confirm new password">
                </div>
                
                <button type="submit" class="btn" id="submitBtn">
                    Reset Password
                </button>
            </form>
        </div>

        <script>
            document.getElementById('resetForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const submitBtn = document.getElementById('submitBtn');
                
                if (password !== confirmPassword) {
                    showMessage('Passwords do not match', 'error');
                    return;
                }
                
                if (password.length < 6) {
                    showMessage('Password must be at least 6 characters', 'error');
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Resetting...';
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            password: password,
                            confirm_password: confirmPassword
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showMessage(data.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'http://192.168.254.119/mabote_api/login_success.html';
                        }, 2000);
                    } else {
                        showMessage(data.message, 'error');
                    }
                } catch (error) {
                    showMessage('An error occurred. Please try again.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset Password';
                }
            });
            
            function showMessage(text, type) {
                const existingMessage = document.querySelector('.message');
                if (existingMessage) {
                    existingMessage.remove();
                }
                
                const message = document.createElement('div');
                message.className = `message ${type}`;
                message.textContent = text;
                
                const form = document.getElementById('resetForm');
                form.parentNode.insertBefore(message, form);
            }
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
