<?php
require_once 'config.php';
requireAdminLogin();

// Get filter parameters
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR t.transaction_code LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'ssss';
}

// Add transaction type filter
if ($type !== 'all') {
    // We'll handle this in the UNION query by filtering the results
    $filter_by_type = $type;
} else {
    $filter_by_type = null;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(t.transaction_date) >= ?";
    $params[] = $date_from;
    $param_types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(t.transaction_date) <= ?";
    $params[] = $date_to;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count based on filter
if ($filter_by_type === 'deposit') {
    $count_query = "SELECT COUNT(*) as total 
                    FROM transactions t 
                    JOIN users u ON t.user_id = u.user_id 
                    $where_clause
                    AND t.points_earned > 0";
} elseif ($filter_by_type === 'redemption') {
    $count_query = "SELECT COUNT(*) as total 
                    FROM redemption r
                    JOIN users u ON r.user_id = u.user_id
                    " . str_replace('t.', 'r.', $where_clause);
} else {
    $count_query = "SELECT COUNT(*) as total FROM (
                        SELECT t.transaction_id
                        FROM transactions t 
                        JOIN users u ON t.user_id = u.user_id 
                        $where_clause
                        AND t.points_earned > 0
                        
                        UNION ALL
                        
                        SELECT r.redemption_id as transaction_id
                        FROM redemption r
                        JOIN users u ON r.user_id = u.user_id
                        " . str_replace('t.', 'r.', $where_clause) . "
                    ) as combined_transactions";
}
$count_stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_transactions = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $limit);

// Build the UNION query with type filtering
$deposit_query = "SELECT 
            t.transaction_id,
            t.transaction_code,
            t.bottle_deposited,
            t.points_earned,
            t.transaction_date,
            t.qr_code_scanned,
            t.transaction_status,
            'deposit' as transaction_type,
            u.first_name, 
            u.last_name, 
            u.email, 
            u.phone
          FROM transactions t 
          JOIN users u ON t.user_id = u.user_id 
          $where_clause
          AND t.points_earned > 0";

$redemption_query = "SELECT 
            r.redemption_id as transaction_id,
            CONCAT('RED-', r.redemption_id) as transaction_code,
            0 as bottle_deposited,
            -r.points_used as points_earned,
            r.redemption_date as transaction_date,
            'N/A' as qr_code_scanned,
            r.status as transaction_status,
            'redemption' as transaction_type,
            u.first_name, 
            u.last_name, 
            u.email, 
            u.phone
          FROM redemption r
          JOIN users u ON r.user_id = u.user_id
          " . str_replace('t.', 'r.', $where_clause);

// Combine queries based on filter
if ($filter_by_type === 'deposit') {
    $query = $deposit_query . " ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
} elseif ($filter_by_type === 'redemption') {
    $query = $redemption_query . " ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
} else {
    $query = "($deposit_query) UNION ALL ($redemption_query) ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
}
          
$stmt = $mysqli->prepare($query);
$transaction_params = $params;
$transaction_params[] = $limit;
$transaction_params[] = $offset;
$transaction_param_types = $param_types . 'ii';

if (!empty($transaction_params)) {
    $stmt->bind_param($transaction_param_types, ...$transaction_params);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get summary statistics based on filter
if ($filter_by_type === 'deposit') {
    $summary_query = "SELECT 
                        COUNT(*) as total_transactions,
                        COALESCE(SUM(bottle_deposited), 0) as total_bottles,
                        COALESCE(SUM(points_earned), 0) as total_points,
                        COALESCE(AVG(bottle_deposited), 0) as avg_bottles
                      FROM transactions t 
                      JOIN users u ON t.user_id = u.user_id 
                      $where_clause
                      AND t.points_earned > 0";
} elseif ($filter_by_type === 'redemption') {
    $summary_query = "SELECT 
                        COUNT(*) as total_transactions,
                        0 as total_bottles,
                        COALESCE(SUM(-points_used), 0) as total_points,
                        0 as avg_bottles
                      FROM redemption r
                      JOIN users u ON r.user_id = u.user_id
                      " . str_replace('t.', 'r.', $where_clause);
} else {
    $summary_query = "SELECT 
                        COUNT(*) as total_transactions,
                        COALESCE(SUM(bottle_deposited), 0) as total_bottles,
                        COALESCE(SUM(points_earned), 0) as total_points,
                        COALESCE(AVG(bottle_deposited), 0) as avg_bottles
                      FROM transactions t 
                      JOIN users u ON t.user_id = u.user_id 
                      $where_clause
                      AND t.points_earned > 0";
}
$summary_stmt = $mysqli->prepare($summary_query);
if (!empty($params)) {
    $summary_stmt->bind_param($param_types, ...$params);
}
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - MaBote.ph Admin</title>
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
        .search-box {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 10px 20px;
        }
        .search-box:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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
            <a class="nav-link active" href="transactions.php">
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
            <h2><i class="fas fa-exchange-alt"></i> Transaction Management</h2>
            <div class="text-muted">
                Total Transactions: <?php echo number_format($total_transactions); ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-primary"><?php echo number_format($summary['total_transactions']); ?></div>
                            <div class="text-muted">Total Transactions</div>
                        </div>
                        <i class="fas fa-exchange-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-success"><?php echo number_format($summary['total_bottles']); ?></div>
                            <div class="text-muted">Bottles Collected</div>
                        </div>
                        <i class="fas fa-recycle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-warning"><?php echo number_format($summary['total_points']); ?></div>
                            <div class="text-muted">Points Distributed</div>
                        </div>
                        <i class="fas fa-coins fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-info"><?php echo number_format($summary['avg_bottles'], 1); ?></div>
                            <div class="text-muted">Avg Bottles/Transaction</div>
                        </div>
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control search-box" name="search" 
                                   placeholder="Search by name, email, or transaction code..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="type">
                            <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="deposit" <?php echo $type === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                            <option value="redemption" <?php echo $type === 'redemption' ? 'selected' : ''; ?>>Redemption</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Bottles</th>
                                <th>Points</th>
                                <th>QR Code</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['transaction_code']); ?></strong>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($transaction['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $transaction_type = $transaction['transaction_type'] ?? 'deposit';
                                    if ($transaction_type === 'redemption') {
                                        $type_class = 'bg-warning';
                                        $type_text = 'Redemption';
                                    } else {
                                        $type_class = 'bg-success';
                                        $type_text = 'Deposit';
                                    }
                                    ?>
                                    <span class="badge <?php echo $type_class; ?>"><?php echo $type_text; ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $transaction['bottle_deposited']; ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $points = $transaction['points_earned'];
                                    if ($points > 0) {
                                        echo '<span class="badge bg-success">+' . number_format($points) . '</span>';
                                    } elseif ($points < 0) {
                                        echo '<span class="badge bg-danger">' . number_format($points) . '</span>';
                                    } else {
                                        echo '<span class="text-muted">0</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($transaction['qr_code_scanned'] ?? 'N/A'); ?></code>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                <td>
                                    <span class="badge bg-success">Completed</span>
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo $type; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
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
