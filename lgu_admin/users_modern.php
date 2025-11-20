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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';

$where_conditions = [];
$params = [];
$param_types = '';

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

if ($status !== 'all') {
    $where_conditions[] = "is_active = ?";
    $params[] = ($status === 'active') ? 1 : 0;
    $param_types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$query = "SELECT u.*, 
                 COALESCE(w.current_balance, 0) as balance,
                 COALESCE(SUM(t.points_earned), 0) as total_earned,
                 COUNT(t.transaction_id) as total_transactions
          FROM users u 
          LEFT JOIN wallet w ON u.user_id = w.user_id
          LEFT JOIN transactions t ON u.user_id = t.user_id
          $where_clause
          GROUP BY u.user_id
          ORDER BY u.user_id DESC 
          LIMIT ? OFFSET ?";
          
$stmt = $mysqli->prepare($query);
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MaBote.ph LGU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
            --secondary-color: #FFC107;
            --accent-color: #FF5722;
            --background-color: #F5F5F5;
            --surface-color: #FFFFFF;
            --text-primary: #212121;
            --text-secondary: #757575;
            --success-color: #4CAF50;
            --warning-color: #FF9800;
            --error-color: #F44336;
            --info-color: #2196F3;
            --border-color: #E0E0E0;
            --shadow-light: 0 2px 8px rgba(0,0,0,0.1);
            --shadow-medium: 0 4px 16px rgba(0,0,0,0.15);
            --shadow-heavy: 0 8px 32px rgba(0,0,0,0.2);
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .sidebar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            z-index: 1000;
            box-shadow: var(--shadow-medium);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .sidebar-brand i {
            font-size: 2rem;
            color: var(--primary-light);
        }

        .sidebar-subtitle {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.7);
            font-weight: 400;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255,255,255,0.9);
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 1rem;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: var(--primary-light);
            color: white;
            transform: translateX(4px);
        }

        .nav-link i {
            font-size: 1.125rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: var(--background-color);
        }

        .page-header {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin: 0;
            font-weight: 400;
        }

        .content-card {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background: var(--surface-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: var(--text-secondary);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--text-primary);
            transform: translateY(-2px);
        }

        .user-card {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .user-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }

        .user-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .user-info h5 {
            margin: 0;
            font-weight: 600;
            color: var(--text-primary);
        }

        .user-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(46, 125, 50, 0.05);
            border-radius: 12px;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 8px;
        }

        .btn-success {
            background: var(--gradient-primary);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
            border: none;
        }

        .alert {
            border-radius: 16px;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-light);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-heavy);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            border-radius: 20px 20px 0 0;
            background: var(--surface-color);
        }

        .modal-body {
            background: var(--surface-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 20px 20px;
            background: var(--surface-color);
        }

        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }

        .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .page-link:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
            color: white;
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header {
                padding: 1rem;
            }

            .content-card {
                padding: 1rem;
            }

            .search-filters {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
            }

            .user-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-recycle"></i>
                <div>
                    <div>MaBote.ph</div>
                    <div class="sidebar-subtitle">LGU Panel</div>
                </div>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bins.php">
                        <i class="fas fa-trash-alt"></i>
                        Bins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="users.php">
                        <i class="fas fa-users"></i>
                        User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Analytics & Reporting
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="rewards.php">
                        <i class="fas fa-gift"></i>
                        Rewards
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">User Management</h1>
            <p class="page-subtitle">Manage community users and their accounts</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filters -->
        <div class="content-card">
            <form method="GET" class="search-filters">
                <div class="search-box">
                    <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="status" class="form-control" style="width: 150px;">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active Only</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </form>
        </div>

        <!-- Users List -->
        <div class="content-card">
            <h3 class="mb-3">Users (<?php echo $total_users; ?> total)</h3>
            
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No users found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-header">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="ms-auto">
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="user-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($user['balance']); ?></div>
                                <div class="stat-label">Balance</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo number_format($user['total_earned']); ?></div>
                                <div class="stat-label">Total Earned</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $user['total_transactions']; ?></div>
                                <div class="stat-label">Transactions</div>
                            </div>
                        </div>
                        
                        <div class="user-actions">
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#adjustPointsModal<?php echo $user['user_id']; ?>">
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
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="User pagination">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


