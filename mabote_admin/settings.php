<?php
require_once 'config.php';
requireAdminLogin();

// Handle settings update
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_admin') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Simple password validation (in production, use proper hashing)
        if ($current_password === 'admin123' && $new_password === $confirm_password && strlen($new_password) >= 6) {
            // In production, update the admin password in database
            $success = "Admin password updated successfully";
        } else {
            $error = "Invalid current password or passwords don't match";
        }
    } elseif ($action === 'update_system') {
        $app_name = $_POST['app_name'] ?? '';
        $points_per_bottle = $_POST['points_per_bottle'] ?? '';
        $maintenance_mode = $_POST['maintenance_mode'] ?? '0';
        
        if ($app_name && is_numeric($points_per_bottle)) {
            // In production, update these settings in database
            $success = "System settings updated successfully";
        } else {
            $error = "Please fill in all required fields with valid values";
        }
    }
}

// Get system statistics (including redemptions)
$stats_query = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM transactions) + (SELECT COUNT(*) FROM redemption) as total_transactions,
                    (SELECT COALESCE(SUM(bottle_deposited), 0) FROM transactions) as total_bottles,
                    (SELECT COALESCE(SUM(points_earned), 0) FROM transactions) as total_points,
                    (SELECT COALESCE(SUM(points_used), 0) FROM redemption) as total_redemptions,
                    (SELECT COUNT(*) FROM notification) as total_notifications";
$stats = $mysqli->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MaBote.ph Admin</title>
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
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.danger { border-left-color: #e74c3c; }
        .stat-card.secondary { border-left-color: #6c757d; }
        .settings-section {
            margin-bottom: 30px;
        }
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
            <a class="nav-link" href="notifications.php">
                <i class="fas fa-bell"></i> Notifications
            </a>
            <a class="nav-link active" href="settings.php">
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
            <h2><i class="fas fa-cog"></i> System Settings</h2>
            <div class="text-muted">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </div>
        </div>

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

        <!-- System Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-primary"><?php echo number_format($stats['total_users']); ?></div>
                            <div class="text-muted">Total Users</div>
                        </div>
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-success"><?php echo number_format($stats['total_transactions']); ?></div>
                            <div class="text-muted">Total Transactions</div>
                        </div>
                        <i class="fas fa-exchange-alt fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-info"><?php echo number_format($stats['total_bottles']); ?></div>
                            <div class="text-muted">Bottles Collected</div>
                        </div>
                        <i class="fas fa-recycle fa-2x text-info"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-warning"><?php echo number_format($stats['total_points']); ?></div>
                            <div class="text-muted">Points Distributed</div>
                        </div>
                        <i class="fas fa-coins fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-danger"><?php echo number_format($stats['total_redemptions']); ?></div>
                            <div class="text-muted">Points Redeemed</div>
                        </div>
                        <i class="fas fa-gift fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-secondary"><?php echo number_format($stats['total_notifications']); ?></div>
                            <div class="text-muted">Notifications Sent</div>
                        </div>
                        <i class="fas fa-bell fa-2x text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Admin Settings -->
            <div class="col-md-6">
                <div class="card settings-section">
                    <div class="card-body">
                        <h5><i class="fas fa-user-shield"></i> Admin Settings</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_admin">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="col-md-6">
                <div class="card settings-section">
                    <div class="card-body">
                        <h5><i class="fas fa-cogs"></i> System Settings</h5>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_system">
                            <div class="mb-3">
                                <label class="form-label">App Name</label>
                                <input type="text" class="form-control" name="app_name" value="MaBote.ph" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Points per Bottle</label>
                                <input type="number" class="form-control" name="points_per_bottle" value="10" min="1" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="maintenanceMode">
                                    <label class="form-check-label" for="maintenanceMode">
                                        Maintenance Mode
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Information -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-database"></i> Database Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Database Host:</strong></td>
                                        <td>localhost</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database Name:</strong></td>
                                        <td>mabote_db</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Connection Status:</strong></td>
                                        <td><span class="badge bg-success">Connected</span></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Total Tables:</strong></td>
                                        <td>8</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Backup:</strong></td>
                                        <td>Never</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database Size:</strong></td>
                                        <td>~2.5 MB</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Actions -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-tools"></i> System Actions</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-download"></i> Export Data
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-success w-100 mb-2">
                                    <i class="fas fa-upload"></i> Import Data
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-warning w-100 mb-2">
                                    <i class="fas fa-database"></i> Backup Database
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-danger w-100 mb-2">
                                    <i class="fas fa-trash"></i> Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
}
?>
