<?php
require_once 'config.php';
requireAdminLogin();

// Handle notification actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send') {
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $notification_type = $_POST['notification_type'] ?? 'admin';
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
$query = "SELECT n.*, u.first_name, u.last_name, u.email
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
$users_query = "SELECT user_id, first_name, last_name, email FROM users ORDER BY first_name";
$users = $mysqli->query($users_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - MaBote.ph Admin</title>
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
            border-left: 4px solid;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            background: white;
        }
        .notification-item.info { border-left-color: #17a2b8; }
        .notification-item.success { border-left-color: #28a745; }
        .notification-item.warning { border-left-color: #ffc107; }
        .notification-item.error { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4">
            <h4 class="text-white">
                <i class="fas fa-recycle"></i> MaBote.ph
            </h4>
            <small class="text-light">Admin Panel</small>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a class="nav-link" href="users.php">
                <i class="fas fa-users"></i> Users
            </a>
            <a class="nav-link" href="machines.php">
                <i class="fas fa-cogs"></i> Machines
            </a>
            <a class="nav-link" href="transactions.php">
                <i class="fas fa-exchange-alt"></i> Transactions
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a class="nav-link active" href="notifications.php">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="?logout=1">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bell"></i> Notification Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
                <i class="fas fa-paper-plane"></i> Send Notification
            </button>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Notification sent successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Notification deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-primary"><?php echo $stats['total_notifications']; ?></div>
                            <div class="text-muted">Total Notifications</div>
                        </div>
                        <i class="fas fa-bell fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-warning"><?php echo $stats['unread_notifications']; ?></div>
                            <div class="text-muted">Unread</div>
                        </div>
                        <i class="fas fa-envelope fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-success"><?php echo $stats['success_notifications']; ?></div>
                            <div class="text-muted">Success</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-danger"><?php echo $stats['error_notifications']; ?></div>
                            <div class="text-muted">Errors</div>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="card">
            <div class="card-body">
                <h5><i class="fas fa-history"></i> Recent Notifications</h5>
                <div class="row">
                    <?php foreach ($notifications as $notification): ?>
                    <div class="col-md-6 mb-3">
                        <div class="notification-item info">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-warning">Unread</span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> 
                                        <?php echo $notification['first_name'] ? 
                                            htmlspecialchars($notification['first_name'] . ' ' . $notification['last_name']) : 
                                            'System'; ?>
                                        <br>
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('M j, Y g:i A', strtotime($notification['notification_date'] ?? 'now')); ?>
                                    </small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                                <button type="submit" class="dropdown-item text-danger" 
                                                        onclick="return confirm('Delete this notification?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Notification Modal -->
    <div class="modal fade" id="sendNotificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="send">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select class="form-select" name="notification_type" required>
                                        <option value="system">System (Cannot be deleted by users)</option>
                                        <option value="admin">Admin (Cannot be deleted by users)</option>
                                        <option value="reward">Personal Reward (Can be deleted)</option>
                                        <option value="transaction">Personal Transaction (Can be deleted)</option>
                                        <option value="points">Personal Points (Can be deleted)</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <strong>System/Admin:</strong> Important announcements users cannot delete<br>
                                        <strong>Personal:</strong> User-specific notifications that can be deleted
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target</label>
                            <select class="form-select" name="target" id="targetSelect">
                                <option value="all">All Users</option>
                                <option value="specific">Specific User</option>
                            </select>
                        </div>
                        <div class="mb-3" id="userSelectDiv" style="display: none;">
                            <label class="form-label">Select User</label>
                            <select class="form-select" name="user_id">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" required placeholder="Enter notification message..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Notification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('targetSelect').addEventListener('change', function() {
            const userSelectDiv = document.getElementById('userSelectDiv');
            if (this.value === 'specific') {
                userSelectDiv.style.display = 'block';
            } else {
                userSelectDiv.style.display = 'none';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const message = document.querySelector('textarea[name="message"]').value.trim();
            const target = document.querySelector('select[name="target"]').value;
            const userId = document.querySelector('select[name="user_id"]').value;

            if (!title) {
                e.preventDefault();
                alert('Please enter a notification title.');
                document.querySelector('input[name="title"]').focus();
                return false;
            }

            if (!message) {
                e.preventDefault();
                alert('Please enter a notification message.');
                document.querySelector('textarea[name="message"]').focus();
                return false;
            }

            if (target === 'specific' && !userId) {
                e.preventDefault();
                alert('Please select a user when sending to specific user.');
                document.querySelector('select[name="user_id"]').focus();
                return false;
            }

            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
}
?>
