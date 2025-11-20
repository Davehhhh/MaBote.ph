<?php
// Start output buffering to prevent any output before redirect
ob_start();

// Prevent caching to avoid stale form submissions
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';
requireLguLogin();

// Generate form token to prevent duplicate submissions
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Handle reward actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CRITICAL: Check if this POST is a duplicate BEFORE processing anything
    // This prevents the browser warning from causing duplicates
    $submission_id = isset($_POST['submission_id']) ? trim($_POST['submission_id']) : '';
    if (!empty($submission_id) && isset($_SESSION['processed_submissions']) && is_array($_SESSION['processed_submissions'])) {
        if (in_array($submission_id, $_SESSION['processed_submissions'])) {
            // This is a duplicate POST (likely from browser refresh warning)
            // Immediately redirect without processing
            ob_end_clean();
            header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
            exit();
        }
    }
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $form_token = isset($_POST['form_token']) ? $_POST['form_token'] : '';
        
        // ALWAYS generate submission_id if not provided (critical for duplicate prevention)
        if (empty($submission_id)) {
            // Create a hash based on form data to identify duplicate submissions
            $form_data = [
                'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
                'points' => isset($_POST['points']) ? (int)$_POST['points'] : 0,
                'action' => $action,
                'token' => $form_token
            ];
            if (isset($_POST['reward_id'])) {
                $form_data['reward_id'] = (int)$_POST['reward_id'];
            }
            $form_hash = md5(serialize($form_data));
            $submission_id = 'auto_' . $action . '_' . $form_hash . '_' . time() . '_' . bin2hex(random_bytes(4));
        }
        
        // Initialize processed submissions array if not exists
        if (!isset($_SESSION['processed_submissions'])) {
            $_SESSION['processed_submissions'] = [];
        }
        
        // Process the action
        if ($action === 'add') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
            $category = isset($_POST['category']) ? trim($_POST['category']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 100;
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            // Validate required fields
            if (empty($name)) {
                $_SESSION['error_message'] = "Reward name is required.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            if ($points <= 0) {
                $_SESSION['error_message'] = "Points required must be greater than 0.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            // CRITICAL: Ensure quantity > 0 for Flutter app visibility (default is 0, which hides rewards)
            if ($quantity <= 0) {
                $quantity = 100; // Default to 100 if not set or 0
            }
            // Ensure is_active is 1 (not 0, not NULL) for Flutter app visibility
            if ($is_active != 1) {
                $is_active = 1; // Default to active
            }
            
            // CRITICAL: Check for duplicates BEFORE starting transaction
            // This prevents race conditions where two requests check simultaneously
            
            // Check 1: Exact duplicate by name (case-insensitive, trimmed) - MOST IMPORTANT CHECK
            // This should ALWAYS prevent duplicates with the same name
            $normalized_name = strtolower(trim($name));
            $name_check = $mysqli->prepare("SELECT reward_id, reward_name FROM reward WHERE LOWER(TRIM(reward_name)) = ? LIMIT 1");
            $name_check->bind_param("s", $normalized_name);
            $name_check->execute();
            $name_result = $name_check->get_result();
            if ($name_result->num_rows > 0) {
                $name_check->close();
                // Mark as processed to prevent retry
                if (!empty($submission_id)) {
                    $_SESSION['processed_submissions'][] = $submission_id;
                    if (count($_SESSION['processed_submissions']) > 50) {
                        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
                    }
                }
                $_SESSION['error_message'] = "Reward with this name already exists.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            $name_check->close();
            
            // Check 2: Recent duplicate (name + points) in last 10 minutes - catch refresh duplicates
            $recent_check = $mysqli->prepare("
                SELECT reward_id FROM reward 
                WHERE LOWER(TRIM(reward_name)) = LOWER(TRIM(?)) 
                AND points_required = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                LIMIT 1
            ");
            $recent_check->bind_param("si", $name, $points);
            $recent_check->execute();
            $recent_result = $recent_check->get_result();
            if ($recent_result->num_rows > 0) {
                $recent_check->close();
                // Mark as processed to prevent further attempts
                if (!empty($submission_id)) {
                    $_SESSION['processed_submissions'][] = $submission_id;
                    if (count($_SESSION['processed_submissions']) > 50) {
                        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
                    }
                }
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            $recent_check->close();
            
            // Check 3: Form token validation
            if (!isset($_SESSION['form_token']) || $form_token !== $_SESSION['form_token']) {
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            // All checks passed - NOW start transaction and insert
            $mysqli->begin_transaction();
            
            try {
                // Double-check for duplicate WITHIN transaction using FOR UPDATE lock
                $lock_check = $mysqli->prepare("SELECT reward_id FROM reward WHERE LOWER(TRIM(reward_name)) = LOWER(TRIM(?)) LIMIT 1 FOR UPDATE");
                $lock_check->bind_param("s", $name);
                $lock_check->execute();
                $lock_result = $lock_check->get_result();
                
                if ($lock_result->num_rows > 0) {
                    $lock_check->close();
                    $mysqli->rollback();
                    $_SESSION['error_message'] = "Reward with this name already exists.";
                    $_SESSION['form_token'] = bin2hex(random_bytes(32));
                    ob_end_clean();
                    header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                    exit();
                }
                $lock_check->close();
                
                // Regenerate token BEFORE insert
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                
                // Mark submission as processed BEFORE insert (in case of failure, we'll handle it)
                if (!empty($submission_id)) {
                    $_SESSION['processed_submissions'][] = $submission_id;
                    if (count($_SESSION['processed_submissions']) > 50) {
                        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
                    }
                }
                
                // Insert the reward - ensure values are explicitly set (no NULLs)
                // Force is_active to be exactly 1 (not 0, not NULL)
                $is_active_value = ($is_active == 1) ? 1 : 0;
                $quantity_value = max(1, (int)$quantity); // Ensure at least 1
                
                // Validate name is not empty before insert
                if (empty($name)) {
                    $mysqli->rollback();
                    $_SESSION['error_message'] = "Reward name cannot be empty.";
                    $_SESSION['form_token'] = bin2hex(random_bytes(32));
                    ob_end_clean();
                    header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                    exit();
                }
                
                $stmt = $mysqli->prepare("INSERT INTO reward (reward_name, points_required, category, description, quantity_available, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $mysqli->rollback();
                    $_SESSION['error_message'] = "Failed to prepare statement: " . $mysqli->error;
                    $_SESSION['form_token'] = bin2hex(random_bytes(32));
                    ob_end_clean();
                    header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                    exit();
                }
                
                $stmt->bind_param("sissii", $name, $points, $category, $description, $quantity_value, $is_active_value);
                
                if ($stmt->execute()) {
                    // Commit transaction
                    $mysqli->commit();
                    $_SESSION['success_message'] = "Reward added successfully!";
                } else {
                    // If insert failed, remove from processed submissions so it can be retried
                    if (!empty($submission_id) && isset($_SESSION['processed_submissions'])) {
                        $key = array_search($submission_id, $_SESSION['processed_submissions']);
                        if ($key !== false) {
                            unset($_SESSION['processed_submissions'][$key]);
                            $_SESSION['processed_submissions'] = array_values($_SESSION['processed_submissions']);
                        }
                    }
                    $mysqli->rollback();
                    $error_msg = "Failed to add reward: " . $stmt->error . " (MySQL Error: " . $mysqli->error . ")";
                    $_SESSION['error_message'] = $error_msg;
                }
                $stmt->close();
                
            } catch (Exception $e) {
                // Rollback on any error
                $mysqli->rollback();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
            }
        } elseif ($action === 'update') {
            // Check submission ID to prevent duplicate updates
            if (!empty($submission_id) && isset($_SESSION['processed_submissions']) && in_array($submission_id, $_SESSION['processed_submissions'])) {
                ob_end_clean();
                header("Location: rewards.php?t=" . time(), true, 303);
                exit();
            }
            
            // Check form token
            if (!isset($_SESSION['form_token']) || $form_token !== $_SESSION['form_token']) {
                ob_end_clean();
                header("Location: rewards.php?t=" . time(), true, 303);
                exit();
            }
            
            // Regenerate token before processing
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            
            $reward_id = isset($_POST['reward_id']) ? (int)$_POST['reward_id'] : 0;
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $points = isset($_POST['points']) ? (int)$_POST['points'] : 0;
            $category = isset($_POST['category']) ? trim($_POST['category']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
            $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
            
            // Debug: Log received data (remove in production)
            error_log("UPDATE REWARD - Received data: name='" . $name . "', reward_id=" . $reward_id . ", points=" . $points);
            error_log("UPDATE REWARD - POST data: " . print_r($_POST, true));
            
            // Validate required fields
            if (empty($name)) {
                $debug_info = "Name is empty. POST data: " . print_r($_POST, true);
                error_log("UPDATE REWARD ERROR: " . $debug_info);
                $_SESSION['error_message'] = "Reward name cannot be empty. Please enter a name.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            if ($reward_id <= 0) {
                $_SESSION['error_message'] = "Invalid reward ID.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            if ($points <= 0) {
                $_SESSION['error_message'] = "Points required must be greater than 0.";
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            // CRITICAL: Ensure quantity > 0 for Flutter app visibility
            // If quantity is 0 or less, set to 100 to make it visible
            if ($quantity <= 0) {
                $quantity = 100;
            }
            
            // Ensure values are explicitly set (no NULLs)
            $is_active_value = ($is_active == 1) ? 1 : 0;
            $quantity_value = max(1, (int)$quantity); // Ensure at least 1 (not 0)
            
            $stmt = $mysqli->prepare("UPDATE reward SET reward_name = ?, points_required = ?, category = ?, description = ?, quantity_available = ?, is_active = ? WHERE reward_id = ?");
            if (!$stmt) {
                $_SESSION['error_message'] = "Failed to prepare statement: " . $mysqli->error;
                $_SESSION['form_token'] = bin2hex(random_bytes(32));
                ob_end_clean();
                header("Location: rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4)), true, 303);
                exit();
            }
            
            $stmt->bind_param("sissiii", $name, $points, $category, $description, $quantity_value, $is_active_value, $reward_id);
            if ($stmt->execute()) {
                // Mark submission as processed
                if (!empty($submission_id)) {
                    if (!isset($_SESSION['processed_submissions'])) {
                        $_SESSION['processed_submissions'] = [];
                    }
                    $_SESSION['processed_submissions'][] = $submission_id;
                    if (count($_SESSION['processed_submissions']) > 50) {
                        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
                    }
                }
                $_SESSION['success_message'] = "Reward updated successfully!";
            } else {
                $error_msg = "Failed to update reward: " . $stmt->error . " (MySQL Error: " . $mysqli->error . ")";
                $_SESSION['error_message'] = $error_msg;
            }
            $stmt->close();
        } elseif ($action === 'fix_all') {
            // Fix all rewards: set is_active=1 and quantity_available=100 for rewards that are hidden
            // Based on schema: is_active defaults to 1, quantity_available defaults to 0
            // We need quantity_available > 0 for rewards to show in app
            $fix_query = "UPDATE reward 
                         SET is_active = 1,
                             quantity_available = CASE 
                                 WHEN quantity_available IS NULL OR quantity_available <= 0 THEN 100
                                 ELSE quantity_available
                             END
                         WHERE is_active IS NULL 
                            OR is_active = 0 
                            OR quantity_available IS NULL 
                            OR quantity_available <= 0";
            
            if ($mysqli->query($fix_query)) {
                $affected = $mysqli->affected_rows;
                $_SESSION['success_message'] = "Fixed $affected reward(s)! Set is_active=1 and quantity_available=100 for all hidden rewards.";
            } else {
                $_SESSION['error_message'] = "Failed to fix rewards: " . $mysqli->error;
            }
        } elseif ($action === 'delete') {
            // Check submission ID to prevent duplicate deletes
            if (!empty($submission_id) && isset($_SESSION['processed_submissions']) && in_array($submission_id, $_SESSION['processed_submissions'])) {
                ob_end_clean();
                header("Location: rewards.php?t=" . time(), true, 303);
                exit();
            }
            
            // Check form token
            if (!isset($_SESSION['form_token']) || $form_token !== $_SESSION['form_token']) {
                ob_end_clean();
                header("Location: rewards.php?t=" . time(), true, 303);
                exit();
            }
            
            // Regenerate token before processing
            $_SESSION['form_token'] = bin2hex(random_bytes(32));
            
            $reward_id = (int)$_POST['reward_id'];
            
            $stmt = $mysqli->prepare("DELETE FROM reward WHERE reward_id = ?");
            $stmt->bind_param("i", $reward_id);
            if ($stmt->execute()) {
                // Mark submission as processed
                if (!empty($submission_id)) {
                    if (!isset($_SESSION['processed_submissions'])) {
                        $_SESSION['processed_submissions'] = [];
                    }
                    $_SESSION['processed_submissions'][] = $submission_id;
                    if (count($_SESSION['processed_submissions']) > 50) {
                        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
                    }
                }
                $_SESSION['success_message'] = "Reward deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete reward: " . $mysqli->error;
            }
            $stmt->close();
        }
        
        // ALWAYS redirect after POST to prevent resubmission on refresh
        // Use 303 See Other to ensure GET request on redirect
        // Add unique timestamp to prevent browser from caching the redirect
        // Clear output buffer and redirect
        ob_end_clean();
        $redirect_url = "rewards.php?t=" . time() . "&r=" . bin2hex(random_bytes(4));
        header("Location: " . $redirect_url, true, 303);
        exit();
    }
}

// End output buffering for normal page display
ob_end_flush();

// Clean up old processed submissions (older than 1 hour)
if (isset($_SESSION['processed_submissions']) && is_array($_SESSION['processed_submissions'])) {
    // Keep only last 50 to prevent session bloat
    if (count($_SESSION['processed_submissions']) > 50) {
        $_SESSION['processed_submissions'] = array_slice($_SESSION['processed_submissions'], -50);
    }
}

// Get success/error/info messages from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
$info_message = isset($_SESSION['info_message']) ? $_SESSION['info_message'] : null;
unset($_SESSION['success_message'], $_SESSION['error_message'], $_SESSION['info_message']);

// Get rewards
$query = "SELECT * FROM reward ORDER BY points_required ASC";
$rewards = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Rewards Management - LGU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 15px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #3498db;
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .reward-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .reward-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h4 class="text-white mb-0">
                <i class="fas fa-recycle me-2"></i>MaBote.ph
            </h4>
            <small class="text-light">LGU Panel</small>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Overview
            </a>
            <a class="nav-link" href="bins.php">
                <i class="fas fa-trash-alt me-2"></i>Bins
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users me-2"></i>User Management
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>Analytics & Reporting
            </a>
            <a class="nav-link active" href="rewards.php">
                <i class="fas fa-gift me-2"></i>Rewards
            </a>
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-bell me-2"></i>Notifications
            </a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-gift"></i> Rewards Management</h2>
            <div class="d-flex gap-2">
                <?php 
                // Count hidden rewards
                $hidden_count = 0;
                foreach ($rewards as $r) {
                    $visible = (isset($r['is_active']) && $r['is_active'] == 1) && 
                              (isset($r['quantity_available']) && $r['quantity_available'] > 0);
                    if (!$visible) $hidden_count++;
                }
                if ($hidden_count > 0): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('This will set all hidden rewards to Active status with quantity 100. Continue?');">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                        <input type="hidden" name="action" value="fix_all">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-wrench me-2"></i>Fix All Hidden Rewards (<?php echo $hidden_count; ?>)
                        </button>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRewardModal">
                    <i class="fas fa-plus me-2"></i>Add New Reward
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($info_message)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($info_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Info about Flutter app visibility -->
        <div class="alert alert-info" role="alert">
            <i class="fas fa-mobile-alt me-2"></i>
            <strong>Flutter App Visibility:</strong> Rewards will only appear in the mobile app if they have <strong>Status = Active</strong> AND <strong>Quantity Available > 0</strong>.
            <?php 
            $hidden_count = 0;
            foreach ($rewards as $r) {
                $visible = (isset($r['is_active']) && $r['is_active'] == 1) && 
                          (isset($r['quantity_available']) && $r['quantity_available'] > 0);
                if (!$visible) $hidden_count++;
            }
            if ($hidden_count > 0): ?>
                <br><small><i class="fas fa-exclamation-triangle me-1"></i><?php echo $hidden_count; ?> reward(s) are currently hidden from the app. Edit them to make them visible.</small>
            <?php endif; ?>
        </div>

        <!-- Rewards List -->
        <div class="row">
            <?php foreach ($rewards as $reward): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="reward-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($reward['reward_name']); ?></h5>
                            <span class="badge bg-primary"><?php echo number_format($reward['points_required']); ?> PTS</span>
                        </div>
                        <?php if ($reward['description']): ?>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($reward['description']); ?></p>
                        <?php endif; ?>
                        <?php if ($reward['category']): ?>
                            <span class="badge bg-secondary mb-3"><?php echo htmlspecialchars($reward['category']); ?></span>
                        <?php endif; ?>
                        <div class="mb-2">
                            <?php 
                            $is_visible = (isset($reward['is_active']) && $reward['is_active'] == 1) && 
                                         (isset($reward['quantity_available']) && $reward['quantity_available'] > 0);
                            ?>
                            <?php if (!$is_visible): ?>
                                <div class="alert alert-warning alert-sm py-1 px-2 mb-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>Not visible in app</strong> - Check status and quantity
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success alert-sm py-1 px-2 mb-2" style="font-size: 0.85rem;">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <strong>Visible in app</strong>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">
                                <i class="fas fa-box me-1"></i>Quantity: <?php echo isset($reward['quantity_available']) ? $reward['quantity_available'] : 0; ?>
                                | 
                                <i class="fas fa-toggle-<?php echo (isset($reward['is_active']) && $reward['is_active']) ? 'on' : 'off'; ?> me-1"></i>
                                Status: <?php echo (isset($reward['is_active']) && $reward['is_active']) ? 'Active' : 'Inactive'; ?>
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editRewardModal<?php echo $reward['reward_id']; ?>">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteReward(<?php echo $reward['reward_id']; ?>)">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Edit Reward Modal -->
                <div class="modal fade" id="editRewardModal<?php echo $reward['reward_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Reward</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" onsubmit="return handleFormSubmit(this);">
                                <div class="modal-body">
                                    <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="reward_id" value="<?php echo $reward['reward_id']; ?>">
                                    <input type="hidden" name="submission_id" class="edit-submission-id" value="">
                                    <div class="mb-3">
                                        <label class="form-label">Reward Name</label>
                                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($reward['reward_name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Points Required</label>
                                        <input type="number" class="form-control" name="points" value="<?php echo $reward['points_required']; ?>" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select class="form-control" name="category">
                                            <option value="">Select Category</option>
                                            <option value="Food" <?php echo $reward['category'] === 'Food' ? 'selected' : ''; ?>>Food</option>
                                            <option value="Electronics" <?php echo $reward['category'] === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                                            <option value="Vouchers" <?php echo $reward['category'] === 'Vouchers' ? 'selected' : ''; ?>>Vouchers</option>
                                            <option value="Services" <?php echo $reward['category'] === 'Services' ? 'selected' : ''; ?>>Services</option>
                                            <option value="Other" <?php echo $reward['category'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($reward['description']); ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Quantity Available</label>
                                        <input type="number" class="form-control" name="quantity" value="<?php echo isset($reward['quantity_available']) ? $reward['quantity_available'] : 0; ?>" min="0" required>
                                        <small class="text-muted">Rewards with quantity > 0 will appear in the user app</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="is_active">
                                            <option value="1" <?php echo (isset($reward['is_active']) && $reward['is_active']) ? 'selected' : ''; ?>>Active</option>
                                            <option value="0" <?php echo (!isset($reward['is_active']) || !$reward['is_active']) ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <small class="text-muted">Only active rewards will appear in the user app</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Reward</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($rewards)): ?>
            <div class="text-center py-5">
                <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No rewards available</h4>
                <p class="text-muted">Add your first reward to get started!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Reward Modal -->
    <div class="modal fade" id="addRewardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="addRewardForm" onsubmit="return handleFormSubmit(this);">
                    <div class="modal-body">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                        <input type="hidden" name="submission_id" id="addRewardSubmissionId" value="">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Reward Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Points Required</label>
                            <input type="number" class="form-control" name="points" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="category">
                                <option value="">Select Category</option>
                                <option value="Food">Food</option>
                                <option value="Electronics">Electronics</option>
                                <option value="Vouchers">Vouchers</option>
                                <option value="Services">Services</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity Available</label>
                            <input type="number" class="form-control" name="quantity" value="100" min="0" required>
                            <small class="text-muted">Rewards with quantity > 0 will appear in the user app</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="is_active">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <small class="text-muted">Only active rewards will appear in the user app</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Reward</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this reward? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" onsubmit="return handleFormSubmit(this);">
                        <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="reward_id" id="deleteRewardId">
                        <input type="hidden" name="submission_id" id="deleteSubmissionId" value="">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Track form submissions to prevent duplicates (persist across page loads using sessionStorage)
        var submittedForms = new Set();
        
        // Load previously submitted forms from sessionStorage
        try {
            var stored = sessionStorage.getItem('submittedForms');
            if (stored) {
                submittedForms = new Set(JSON.parse(stored));
            }
        } catch(e) {
            console.log('Could not load submitted forms from sessionStorage');
        }
        
        // Save submitted forms to sessionStorage
        function saveSubmittedForms() {
            try {
                sessionStorage.setItem('submittedForms', JSON.stringify(Array.from(submittedForms)));
            } catch(e) {
                console.log('Could not save submitted forms to sessionStorage');
            }
        }
        
        function handleFormSubmit(form) {
            var submissionId = form.querySelector('[name="submission_id"]');
            
            // Ensure submission ID exists
            if (!submissionId || !submissionId.value) {
                // Generate one if missing
                if (!submissionId) {
                    submissionId = document.createElement('input');
                    submissionId.type = 'hidden';
                    submissionId.name = 'submission_id';
                    form.appendChild(submissionId);
                }
                submissionId.value = 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            
            // Check if this form was already submitted
            if (submittedForms.has(submissionId.value)) {
                console.log('Form already submitted, preventing duplicate submission');
                alert('This form has already been submitted. Please wait for the page to reload.');
                return false;
            }
            
            // Mark as submitted
            submittedForms.add(submissionId.value);
            saveSubmittedForms();
            
            // Mark form as submitting (for beforeunload handler)
            formSubmitting = true;
            
            // Disable submit button immediately
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                submitBtn.setAttribute('data-original-text', originalText);
            }
            
            // DO NOT disable form inputs - disabled inputs don't submit their values!
            // Instead, we'll rely on the duplicate submission tracking to prevent resubmission
            // The form will submit normally with all values intact
            
            // Allow form to submit - the page will redirect after successful submission
            // Set a timeout to clear the flag in case submission fails (shouldn't happen, but safety)
            setTimeout(function() {
                formSubmitting = false;
            }, 5000);
            
            return true;
        }
        
        function deleteReward(rewardId) {
            document.getElementById('deleteRewardId').value = rewardId;
            // Generate unique submission ID for delete
            var deleteSubmissionId = document.getElementById('deleteSubmissionId');
            if (deleteSubmissionId) {
                deleteSubmissionId.value = 'delete_' + rewardId + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Prevent browsers from keeping the page in POST state (extra safety alongside the PHP redirect)
        // This must run immediately on page load, not just on DOMContentLoaded
        (function() {
            // Remove any query parameters that were added for redirect (t=timestamp, r=random)
            var url = new URL(window.location);
            if (url.searchParams.has('t') || url.searchParams.has('r')) {
                // This is a redirect after POST - clean up the URL
                url.searchParams.delete('t');
                url.searchParams.delete('r');
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, url.pathname + (url.search || ''));
                }
            }
            
            if (window.history && window.history.replaceState) {
                // Replace current history entry to remove POST data immediately
                var cleanUrl = window.location.pathname + window.location.search.replace(/[?&]t=\d+/, '').replace(/[?&]r=[^&]*/, '');
                if (cleanUrl.includes('?')) {
                    cleanUrl = cleanUrl.replace(/\?$/, ''); // Remove trailing ?
                }
                window.history.replaceState({}, document.title, cleanUrl);
            }
            
            // Clear submitted forms from sessionStorage if we're on a fresh GET request (not a redirect)
            // Check if we have a success/error message (indicates redirect after POST)
            <?php if (!isset($success_message) && !isset($error_message) && !isset($info_message)): ?>
                // Fresh page load - clear old submission tracking
                try {
                    sessionStorage.removeItem('submittedForms');
                    submittedForms.clear();
                } catch(e) {}
            <?php endif; ?>
        })();
        
        // Track if form submission is in progress
        var formSubmitting = false;
        
        // Prevent page unload during form submission (but allow after submission completes)
        window.addEventListener('beforeunload', function(e) {
            // Only show warning if form is actively submitting
            if (formSubmitting) {
                e.preventDefault();
                e.returnValue = 'A form is being submitted. Please wait...';
                return e.returnValue;
            }
        });

        // Generate unique submission ID when modal opens
        function generateSubmissionId() {
            return 'sub_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        // Reset form and close modal after successful submission
        document.addEventListener('DOMContentLoaded', function() {
            // Generate unique submission ID when add modal is shown
            var addModal = document.getElementById('addRewardModal');
            if (addModal) {
                addModal.addEventListener('show.bs.modal', function() {
                    var submissionIdField = document.getElementById('addRewardSubmissionId');
                    if (submissionIdField) {
                        submissionIdField.value = generateSubmissionId();
                    }
                });
            }
            
            // Generate unique submission ID when edit modals are shown
            document.querySelectorAll('[id^="editRewardModal"]').forEach(function(modal) {
                modal.addEventListener('show.bs.modal', function() {
                    var submissionIdField = this.querySelector('.edit-submission-id');
                    if (submissionIdField) {
                        var rewardId = this.querySelector('[name="reward_id"]').value;
                        submissionIdField.value = 'edit_' + rewardId + '_' + generateSubmissionId();
                    }
                });
            });

            // Close modals if there's a success/error message (indicating redirect after POST)
            <?php if (isset($success_message) || isset($error_message) || isset($info_message)): ?>
                // Clear form submitting flag since we've received a response
                formSubmitting = false;
                
                var addModalInstance = bootstrap.Modal.getInstance(document.getElementById('addRewardModal'));
                if (addModalInstance) {
                    addModalInstance.hide();
                }
                // Reset add form and clear submission tracking
                var addForm = document.querySelector('#addRewardModal form');
                if (addForm) {
                    addForm.reset();
                    // Clear the submission ID so a new one can be generated
                    var submissionIdField = document.getElementById('addRewardSubmissionId');
                    if (submissionIdField) {
                        submissionIdField.value = '';
                    }
                    // Re-enable submit button
                    var submitBtn = addForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        var originalText = submitBtn.getAttribute('data-original-text');
                        if (originalText) {
                            submitBtn.innerHTML = originalText;
                        }
                    }
                }
            <?php endif; ?>

            // Additional form submission protection (backup to handleFormSubmit)
            var forms = document.querySelectorAll('form[method="POST"]');
            forms.forEach(function(form) {
                // Remove any existing onsubmit handler to avoid conflicts
                form.onsubmit = null;
                
                // Add our handler
                form.addEventListener('submit', function(e) {
                    // Call handleFormSubmit first
                    if (!handleFormSubmit(form)) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                    // If handleFormSubmit returns true, allow submission to proceed
                }, true); // Use capture phase to run before other handlers
            });
            
            // Store original button text for restore
            document.querySelectorAll('form[method="POST"] button[type="submit"]').forEach(function(btn) {
                if (!btn.getAttribute('data-original-text')) {
                    btn.setAttribute('data-original-text', btn.innerHTML);
                }
            });
        });
    </script>
</body>
</html>
