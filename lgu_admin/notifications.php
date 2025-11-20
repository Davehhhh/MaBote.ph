<?php
require_once 'config.php';
requireLguLogin();

// Handle notification actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send') {
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $notification_type = $_POST['notification_type'] ?? 'lgu';
        $priority = $_POST['priority'] ?? 'normal';
        $target = $_POST['target'] ?? 'all';
        
        // Validate required fields
        if (empty($title)) {
            $error = "Please enter a notification title.";
        } elseif (empty($message)) {
            $error = "Please enter a notification message.";
        } elseif ($target === 'specific' && empty($_POST['user_id'])) {
            $error = "Please select a user when sending to specific user.";
        } else {
            // Send to all users
            if ($target === 'all') {
                $user_query = "SELECT user_id FROM users";
                $users = $mysqli->query($user_query)->fetch_all(MYSQLI_ASSOC);
                
                foreach ($users as $user) {
                    $stmt = $mysqli->prepare("INSERT INTO notification (user_id, title, message, is_read, notification_type, priority, sent_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
                    $stmt->bind_param('issss', $user['user_id'], $title, $message, $notification_type, $priority);
                    $stmt->execute();
                }
            } else {
                // Send to specific user
                $user_id = $_POST['user_id'] ?? '';
                if ($user_id) {
                    $stmt = $mysqli->prepare("INSERT INTO notification (user_id, title, message, is_read, notification_type, priority, sent_at) VALUES (?, ?, ?, 0, ?, ?, NOW())");
                    $stmt->bind_param('issss', $user_id, $title, $message, $notification_type, $priority);
                    $stmt->execute();
                }
            }
            $success = "Notification sent successfully";
            // Redirect to prevent form resubmission
            header("Location: notifications.php?success=1");
            exit;
        }
    } elseif ($action === 'delete') {
        $notification_id = $_POST['notification_id'] ?? '';
        
        if ($notification_id) {
            $stmt = $mysqli->prepare("DELETE FROM notification WHERE notification_id = ?");
            $stmt->bind_param('i', $notification_id);
            $stmt->execute();
            $success = "Notification deleted successfully";
            // Redirect to prevent form resubmission
            header("Location: notifications.php?deleted=1");
            exit;
        }
    }
}

// Get notifications
$query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as username, u.email
          FROM notification n 
          LEFT JOIN users u ON n.user_id = u.user_id 
          ORDER BY n.notification_id DESC 
          LIMIT 50";
$notifications = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// Get notification statistics
$stats_query = "SELECT 
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications,
                    0 as info_notifications,
                    0 as success_notifications,
                    0 as warning_notifications,
                    0 as error_notifications
                FROM notification";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Get users for targeting
$users_query = "SELECT user_id, CONCAT(first_name, ' ', last_name) as username, email FROM users ORDER BY first_name";
$users = $mysqli->query($users_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - MaBote.ph LGU</title>
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
        .table th {
            border: none;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid;
        }
        .stat-card.primary { border-left-color: #3498db; }
        .stat-card.success { border-left-color: #2ecc71; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-card.danger { border-left-color: #e74c3c; }
        .stat-card.info { border-left-color: #17a2b8; }
        .notification-item {
            border-left: 4px solid #3498db;
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .notification-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .notification-item.unread {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        .notification-item.success {
            border-left-color: #2ecc71;
        }
        .notification-item.warning {
            border-left-color: #f39c12;
        }
        .notification-item.info {
            border-left-color: #17a2b8;
        }
        .notification-priority {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .priority-high {
            background: #e74c3c;
            color: white;
        }
        .priority-normal {
            background: #3498db;
            color: white;
        }
        .priority-low {
            background: #95a5a6;
            color: white;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
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
            <a class="nav-link" href="rewards.php">
                <i class="fas fa-gift me-2"></i>Rewards
            </a>
            <a class="nav-link active" href="notifications.php">
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
            <h2><i class="fas fa-bell"></i> Notification Management</h2>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Notification sent successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-trash me-2"></i>Notification deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_notifications']; ?></h3>
                            <p class="mb-0 text-muted">Total Notifications</p>
                        </div>
                        <i class="fas fa-bell fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['unread_notifications']; ?></h3>
                            <p class="mb-0 text-muted">Unread</p>
                        </div>
                        <i class="fas fa-envelope fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_notifications'] - $stats['unread_notifications']; ?></h3>
                            <p class="mb-0 text-muted">Read</p>
                        </div>
                        <i class="fas fa-envelope-open fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo count($users); ?></h3>
                            <p class="mb-0 text-muted">Total Users</p>
                        </div>
                        <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Notification Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send New Notification</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="send">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="notification_type" class="form-label">Type</label>
                                <select class="form-select" id="notification_type" name="notification_type">
                                    <option value="lgu">LGU</option>
                                    <option value="system">System</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="announcement">Announcement</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="target" class="form-label">Target</label>
                                <select class="form-select" id="target" name="target" onchange="toggleUserSelect()">
                                    <option value="all">All Users</option>
                                    <option value="specific">Specific User</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="userSelectDiv" style="display: none;">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Send Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Notifications</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?> <?php echo $notification['notification_type']; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-3"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                        <span class="notification-priority priority-<?php echo $notification['priority']; ?>">
                                            <?php echo ucfirst($notification['priority']); ?>
                                        </span>
                                        <span class="badge bg-secondary ms-2"><?php echo ucfirst($notification['notification_type']); ?></span>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($notification['username'] ?? 'System'); ?>
                                        <i class="fas fa-clock me-1 ms-2"></i><?php echo date('M j, Y g:i A', strtotime($notification['sent_at'] ?? $notification['notification_id'])); ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-danger ms-2">Unread</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notification?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No notifications found</h5>
                        <p class="text-muted">Send your first notification using the form above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleUserSelect() {
            const target = document.getElementById('target').value;
            const userSelectDiv = document.getElementById('userSelectDiv');
            const userSelect = document.getElementById('user_id');
            
            if (target === 'specific') {
                userSelectDiv.style.display = 'block';
                userSelect.required = true;
            } else {
                userSelectDiv.style.display = 'none';
                userSelect.required = false;
            }
        }
    </script>
</body>
</html>
