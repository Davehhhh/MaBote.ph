<?php
require_once 'config.php';
requireAdminLogin();

// Handle user actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action === 'suspend' && $user_id) {
        $stmt = $mysqli->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $success = "User suspended successfully";
    } elseif ($action === 'activate' && $user_id) {
        $stmt = $mysqli->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $success = "User activated successfully";
    } elseif ($action === 'add_points' && $user_id) {
        $points = (int)($_POST['points'] ?? 0);
        $reason = $_POST['reason'] ?? 'Admin adjustment';
        
        if ($points > 0) {
            // Ensure wallet exists for user
            $stmt = $mysqli->prepare("INSERT IGNORE INTO wallet (user_id, current_balance, is_active, wallet_status) VALUES (?, 0, 1, 'active')");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            
            // Add points to wallet current_balance (this is what the API uses)
            $stmt = $mysqli->prepare("UPDATE wallet SET current_balance = current_balance + ? WHERE user_id = ?");
            $stmt->bind_param('ii', $points, $user_id);
            $stmt->execute();
            
            // Create a transaction record with unique QR code
            $unique_qr = 'ADMIN_ADD_' . $user_id . '_' . time();
            $stmt = $mysqli->prepare("INSERT INTO transactions (user_id, bottle_deposited, points_earned, transaction_date, qr_code_scanned) VALUES (?, 0, ?, NOW(), ?)");
            $stmt->bind_param('iis', $user_id, $points, $unique_qr);
            $stmt->execute();
            
            // Send notification to user
            $stmt = $mysqli->prepare("INSERT INTO notification (user_id, title, message, is_read, notification_type, priority, sent_at) VALUES (?, ?, ?, 0, 'points', 'normal', NOW())");
            $title = "Points Added by Admin";
            $message = "Admin has added $points points to your account. Reason: $reason";
            $stmt->bind_param('iss', $user_id, $title, $message);
            $stmt->execute();
            
            $success = "Successfully added $points points to user account";
        } else {
            $error = "Please enter a valid number of points";
        }
    } elseif ($action === 'deduct_points' && $user_id) {
        $points = (int)($_POST['points'] ?? 0);
        $reason = $_POST['reason'] ?? 'Admin adjustment';
        
        if ($points > 0) {
            // Check if user has enough points
            $stmt = $mysqli->prepare("SELECT current_balance FROM wallet WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result && $result['current_balance'] >= $points) {
                // Deduct points from wallet current_balance
                $stmt = $mysqli->prepare("UPDATE wallet SET current_balance = current_balance - ? WHERE user_id = ?");
                $stmt->bind_param('ii', $points, $user_id);
                $stmt->execute();
                
                // Create a redemption record
                $stmt = $mysqli->prepare("INSERT INTO redemption (user_id, points_used, redemption_date, reward_id) VALUES (?, ?, NOW(), NULL)");
                $stmt->bind_param('ii', $user_id, $points);
                $stmt->execute();
                
                // Send notification to user
                $stmt = $mysqli->prepare("INSERT INTO notification (user_id, title, message, is_read, notification_type, priority, sent_at) VALUES (?, ?, ?, 0, 'points', 'normal', NOW())");
                $title = "Points Deducted by Admin";
                $message = "Admin has deducted $points points from your account. Reason: $reason";
                $stmt->bind_param('iss', $user_id, $title, $message);
                $stmt->execute();
                
                $success = "Successfully deducted $points points from user account";
            } else {
                $error = "User does not have enough points (Current balance: " . ($result['current_balance'] ?? 0) . ")";
            }
        } else {
            $error = "Please enter a valid number of points";
        }
    } elseif ($action === 'delete' && $user_id) {
        // Disable foreign key checks temporarily
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
        
        try {
            // Delete from tables that reference user_id (in correct order)
            $tables_to_clean = [
                'points_history',
                'redemption', 
                'notification',
                'transactions',
                'wallet',
                'sessions',
                'password_reset_tokens'
            ];
            
            foreach ($tables_to_clean as $table) {
                $stmt = $mysqli->prepare("DELETE FROM $table WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Now delete the user
            $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
            }
            
            $success = "User and all related data deleted successfully";
            
        } catch (Exception $e) {
            $error = "Error deleting user: " . $e->getMessage();
        } finally {
            // Re-enable foreign key checks
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
        }
    }
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
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
$query = "SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.barangay, u.city, u.qr_id, u.is_active, u.total_points, u.user_profile, u.created_at,
                 COALESCE(w.current_balance, 0) as balance,
                 COALESCE(SUM(t.points_earned), 0) as total_earned,
                 COUNT(t.transaction_id) as total_transactions
          FROM users u 
          LEFT JOIN wallet w ON u.user_id = w.user_id
          LEFT JOIN transactions t ON u.user_id = t.user_id
          $where_clause
          GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.phone, u.address, u.barangay, u.city, u.qr_id, u.is_active, u.total_points, u.user_profile, u.created_at, w.current_balance
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
    <title>User Management - MaBote.ph Admin</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 10px 20px;
        }
        .search-box:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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
            <a class="nav-link active" href="users.php">
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
            <h2><i class="fas fa-users"></i> User Management</h2>
            <div class="text-muted">
                Total Users: <?php echo number_format($total_users); ?>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control search-box" name="search" 
                                   placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Balance</th>
                                <th>Earned</th>
                                <th>Transactions</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($user['user_profile']): ?>
                                            <img src="../mabote_api/<?php echo htmlspecialchars($user['user_profile']); ?>" 
                                                 class="user-avatar me-3" alt="Profile">
                                        <?php else: ?>
                                            <div class="user-avatar me-3 bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">ID: <?php echo $user['user_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo number_format($user['balance']); ?> pts</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo number_format($user['total_earned']); ?> pts</span>
                                </td>
                                <td><?php echo $user['total_transactions']; ?></td>
                                <td>
                                    <?php 
                                    $status_class = ($user['is_active'] ?? 1) ? 'bg-success' : 'bg-danger';
                                    $status_text = ($user['is_active'] ?? 1) ? 'Active' : 'Inactive';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td><?php echo isset($user['date_registered']) ? date('M d, Y', strtotime($user['date_registered'])) : 'N/A'; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="modal" data-bs-target="#addPointsModal<?php echo $user['user_id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" data-bs-target="#deductPointsModal<?php echo $user['user_id']; ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['user_id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (($user['is_active'] ?? 1) == 1): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                        onclick="return confirm('Suspend this user?')">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" 
                                                        onclick="return confirm('Activate this user?')">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Delete this user permanently?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <?php foreach ($users as $user): ?>
    <div class="modal fade" id="userModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="userModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel<?php echo $user['user_id']; ?>">
                        <i class="fas fa-user me-2"></i>User Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Personal Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Barangay:</strong></td>
                                    <td><?php echo htmlspecialchars($user['barangay'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>City:</strong></td>
                                    <td><?php echo htmlspecialchars($user['city'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Account Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>User ID:</strong></td>
                                    <td><?php echo $user['user_id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>QR ID:</strong></td>
                                    <td><code><?php echo htmlspecialchars($user['qr_id'] ?? 'N/A'); ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <?php 
                                        $status_class = ($user['is_active'] ?? 1) ? 'bg-success' : 'bg-danger';
                                        $status_text = ($user['is_active'] ?? 1) ? 'Active' : 'Inactive';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Joined:</strong></td>
                                    <td><?php echo isset($user['date_registered']) ? date('M d, Y', strtotime($user['date_registered'])) : 'N/A'; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">Wallet & Activity</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-success"><?php echo number_format($user['balance']); ?></h5>
                                            <small class="text-muted">Current Balance</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-primary"><?php echo number_format($user['total_earned']); ?></h5>
                                            <small class="text-muted">Total Earned</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="text-info"><?php echo $user['total_transactions']; ?></h5>
                                            <small class="text-muted">Transactions</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if (($user['is_active'] ?? 1) == 1): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="suspend">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Suspend this user?')">
                                <i class="fas fa-pause me-1"></i>Suspend User
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Activate this user?')">
                                <i class="fas fa-play me-1"></i>Activate User
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Points Modal -->
    <div class="modal fade" id="addPointsModal<?php echo $user['user_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Points</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_points">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Balance</label>
                            <input type="text" class="form-control" value="<?php echo number_format($user['balance']); ?> points" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Points to Add</label>
                            <input type="number" class="form-control" name="points" min="1" max="10000" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Reason for adding points...">Admin adjustment</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>Add Points
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Deduct Points Modal -->
    <div class="modal fade" id="deductPointsModal<?php echo $user['user_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Deduct Points</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="deduct_points">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Balance</label>
                            <input type="text" class="form-control" value="<?php echo number_format($user['balance']); ?> points" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Points to Deduct</label>
                            <input type="number" class="form-control" name="points" min="1" max="<?php echo $user['balance']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Reason for deducting points...">Admin adjustment</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-minus me-1"></i>Deduct Points
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    adminLogout();
}
?>
