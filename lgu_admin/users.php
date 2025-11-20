<?php
require_once 'config.php';
requireLguLogin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $user_id = $_POST['user_id'] ?? null;
        
        if ($action === 'toggle_status' && $user_id) {
            $stmt = $mysqli->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_message = "User status updated successfully!";
            } else {
                $error_message = "Failed to update user status.";
            }
        } elseif ($action === 'adjust_points' && $user_id) {
            $points = $_POST['points'];
            $reason = $_POST['reason'] ?? 'LGU adjustment';
            
            // Add points to wallet
            $stmt = $mysqli->prepare("INSERT INTO wallet (user_id, current_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE current_balance = current_balance + ?");
            $stmt->bind_param("iii", $user_id, $points, $points);
            if ($stmt->execute()) {
                $success_message = "Points adjusted successfully!";
            } else {
                $error_message = "Failed to adjust points.";
            }
        }
    }
}

// Get users
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.barangay, u.city, u.qr_id, u.is_active, u.total_points, u.user_profile, u.created_at,
                 CONCAT(u.first_name, ' ', u.last_name) as username,
                 COALESCE(w.current_balance, 0) as balance,
                 COALESCE(SUM(t.points_earned), 0) as total_earned,
                 COUNT(t.transaction_id) as total_transactions
          FROM users u 
          LEFT JOIN wallet w ON u.user_id = w.user_id
          LEFT JOIN transactions t ON u.user_id = t.user_id
          GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.barangay, u.city, u.qr_id, u.is_active, u.total_points, u.user_profile, u.created_at, w.current_balance
          ORDER BY u.user_id DESC";
$users = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MaBote.ph LGU</title>
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
        .user-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .user-card:hover {
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
            <a class="nav-link active" href="users.php">
                <i class="fas fa-users me-2"></i>User Management
            </a>
            <a class="nav-link" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>Analytics & Reporting
            </a>
            <a class="nav-link" href="rewards.php">
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
            <h2><i class="fas fa-users"></i> User Management</h2>
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

        <!-- Users List -->
        <div class="row">
            <?php foreach ($users as $user): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="user-card">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                            </div>
                            <div class="ms-auto">
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="fw-bold text-primary"><?php echo number_format($user['balance']); ?></div>
                                <small class="text-muted">Balance</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success"><?php echo number_format($user['total_earned']); ?></div>
                                <small class="text-muted">Earned</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-info"><?php echo $user['total_transactions']; ?></div>
                                <small class="text-muted">Transactions</small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adjustPointsModal<?php echo $user['user_id']; ?>">
                                <i class="fas fa-coins me-1"></i>Adjust Points
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?> me-1"></i>
                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Adjust Points Modal -->
                    <div class="modal fade" id="adjustPointsModal<?php echo $user['user_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Adjust Points for <?php echo htmlspecialchars($user['username']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="adjust_points">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Points to Add</label>
                                            <input type="number" class="form-control" name="points" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Reason</label>
                                            <input type="text" class="form-control" name="reason" value="LGU adjustment">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Add Points</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No users found</h4>
                <p class="text-muted">Users will appear here once they register</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
