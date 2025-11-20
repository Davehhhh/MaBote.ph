<?php
require_once 'config.php';
requireAdminLogin();

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get analytics data
function getAnalyticsData($mysqli, $date_from, $date_to) {
    $data = [];
    
    // Daily transactions for chart (deposits + redemptions)
    $query = "SELECT DATE(transaction_date) as date, 
                     COUNT(*) as transactions,
                     SUM(bottle_deposited) as bottles,
                     SUM(points_earned) as points
              FROM (
                SELECT transaction_date, bottle_deposited, points_earned
                FROM transactions 
                WHERE DATE(transaction_date) BETWEEN ? AND ?
                
                UNION ALL
                
                SELECT redemption_date as transaction_date, 0 as bottle_deposited, -points_used as points_earned
                FROM redemption
                WHERE DATE(redemption_date) BETWEEN ? AND ?
              ) as combined_transactions
              GROUP BY DATE(transaction_date)
              ORDER BY date";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssss', $date_from, $date_to, $date_from, $date_to);
    $stmt->execute();
    $data['daily'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Top users by activity (deposits + redemptions)
    $query = "SELECT u.first_name, u.last_name, u.email,
                     COALESCE(SUM(t.bottle_deposited), 0) as total_bottles,
                     COALESCE(SUM(t.points_earned), 0) as total_points_earned,
                     COALESCE(SUM(r.points_used), 0) as total_points_redeemed,
                     COALESCE(COUNT(t.transaction_id), 0) + COALESCE(COUNT(r.redemption_id), 0) as total_transactions
              FROM users u
              LEFT JOIN transactions t ON u.user_id = t.user_id AND DATE(t.transaction_date) BETWEEN ? AND ?
              LEFT JOIN redemption r ON u.user_id = r.user_id AND DATE(r.redemption_date) BETWEEN ? AND ?
              GROUP BY u.user_id
              HAVING total_transactions > 0
              ORDER BY total_transactions DESC
              LIMIT 10";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssss', $date_from, $date_to, $date_from, $date_to);
    $stmt->execute();
    $data['top_users'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Monthly comparison (deposits + redemptions)
    $query = "SELECT 
                MONTH(transaction_date) as month,
                YEAR(transaction_date) as year,
                COUNT(*) as transactions,
                SUM(bottle_deposited) as bottles,
                SUM(points_earned) as points
              FROM (
                SELECT transaction_date, bottle_deposited, points_earned
                FROM transactions 
                WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                
                UNION ALL
                
                SELECT redemption_date as transaction_date, 0 as bottle_deposited, -points_used as points_earned
                FROM redemption
                WHERE redemption_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              ) as combined_transactions
              GROUP BY YEAR(transaction_date), MONTH(transaction_date)
              ORDER BY year, month";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $data['monthly'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

$analytics = getAnalyticsData($mysqli, $date_from, $date_to);

// Get summary stats (deposits only - positive points)
$summary_query = "SELECT 
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(bottle_deposited), 0) as total_bottles,
                    COALESCE(SUM(points_earned), 0) as total_points,
                    COALESCE(AVG(bottle_deposited), 0) as avg_bottles
                  FROM transactions 
                  WHERE DATE(transaction_date) BETWEEN ? AND ?
                  AND points_earned > 0";
$summary_stmt = $mysqli->prepare($summary_query);
$summary_stmt->bind_param('ss', $date_from, $date_to);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Get total transactions including redemptions
$total_transactions_query = "SELECT 
    (SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date) BETWEEN ? AND ? AND points_earned > 0) + 
    (SELECT COUNT(*) FROM redemption WHERE DATE(redemption_date) BETWEEN ? AND ?) as total";
$total_transactions_stmt = $mysqli->prepare($total_transactions_query);
$total_transactions_stmt->bind_param('ssss', $date_from, $date_to, $date_from, $date_to);
$total_transactions_stmt->execute();
$total_transactions_result = $total_transactions_stmt->get_result()->fetch_assoc();
$summary['total_transactions'] = $total_transactions_result['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - MaBote.ph Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-brand i {
            font-size: 2rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem;
            border-radius: 12px;
        }

        .sidebar-subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
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
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #3498db;
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
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-4px);
        }

        .stat-card.primary::before { background: var(--gradient-primary); }
        .stat-card.success::before { background: linear-gradient(135deg, var(--success-color) 0%, #66BB6A 100%); }
        .stat-card.warning::before { background: linear-gradient(135deg, var(--warning-color) 0%, #FFB74D 100%); }
        .stat-card.info::before { background: linear-gradient(135deg, var(--info-color) 0%, #42A5F5 100%); }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.primary .stat-icon { background: var(--gradient-primary); }
        .stat-card.success .stat-icon { background: linear-gradient(135deg, var(--success-color) 0%, #66BB6A 100%); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, var(--warning-color) 0%, #FFB74D 100%); }
        .stat-card.info .stat-icon { background: linear-gradient(135deg, var(--info-color) 0%, #42A5F5 100%); }

        .card {
            background: var(--surface-color);
            border: none;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table-container {
            background: var(--surface-color);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            overflow: hidden;
        }

        .table thead th {
            background: var(--background-color);
            border: none;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(46, 125, 50, 0.05);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge.bg-success {
            background: var(--success-color) !important;
        }

        .badge.bg-primary {
            background: var(--primary-color) !important;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.25);
        }

        .btn {
            border-radius: 12px;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                    <div class="sidebar-subtitle">Admin Panel</div>
                </div>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="machines.php">
                        <i class="fas fa-cogs"></i>
                        Machines
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="transactions.php">
                        <i class="fas fa-exchange-alt"></i>
                        Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog"></i>
                        Settings
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
            <h1 class="page-title">Reports & Analytics</h1>
            <p class="page-subtitle">Comprehensive insights and data analysis for MaBote.ph</p>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i>
                            Update Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo $summary['total_transactions']; ?></div>
                <div class="stat-label">Total Transactions</div>
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-number"><?php echo $summary['total_bottles']; ?></div>
                <div class="stat-label">Bottles Collected</div>
                <div class="stat-icon">
                    <i class="fas fa-recycle"></i>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $summary['total_points']; ?></div>
                <div class="stat-label">Points Distributed</div>
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-number"><?php echo number_format($summary['avg_bottles'], 1); ?></div>
                <div class="stat-label">Avg Bottles/Transaction</div>
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Daily Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Monthly Comparison
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2"></i>
                    Top Users by Activity
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>User</th>
                                <th>Bottles</th>
                                <th>Points Earned</th>
                                <th>Points Redeemed</th>
                                <th>Total Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($analytics['top_users'])): ?>
                                <?php foreach ($analytics['top_users'] as $index => $user): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $user['total_bottles']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">+<?php echo $user['total_points_earned']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">-<?php echo $user['total_points_redeemed']; ?></span>
                                    </td>
                                    <td><?php echo $user['total_transactions']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-info-circle text-muted me-2"></i>
                                        No data available for the selected date range
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Activity Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyData = <?php echo json_encode($analytics['daily']); ?>;
        
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.date),
                datasets: [{
                    label: 'Transactions',
                    data: dailyData.map(item => item.transactions),
                    borderColor: '#2E7D32',
                    backgroundColor: 'rgba(46, 125, 50, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Bottles',
                    data: dailyData.map(item => item.bottles),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Comparison Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($analytics['monthly']); ?>;
        
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => `${item.year}-${item.month.toString().padStart(2, '0')}`),
                datasets: [{
                    label: 'Bottles',
                    data: monthlyData.map(item => item.bottles),
                    backgroundColor: '#4CAF50',
                    borderColor: '#2E7D32',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
