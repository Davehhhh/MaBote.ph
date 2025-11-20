<?php
require_once 'config.php';
requireLguLogin();

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Get comprehensive LGU analytics data
function getLguAnalyticsData($mysqli, $date_from, $date_to) {
    $data = [];
    
    // Daily transactions for chart (deposits + redemptions)
    $query = "SELECT DATE(transaction_date) as date, 
                     COUNT(*) as transactions,
                     SUM(bottle_deposited) as bottles,
                     SUM(points_earned) as points,
                     COUNT(DISTINCT user_id) as active_users
              FROM transactions 
              WHERE DATE(transaction_date) BETWEEN ? AND ?
              GROUP BY DATE(transaction_date)
              ORDER BY date";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $data['daily'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Top users by activity
    $query = "SELECT u.user_id, u.first_name, u.last_name, u.email,
                     COALESCE(SUM(t.bottle_deposited), 0) as total_bottles,
                     COALESCE(SUM(t.points_earned), 0) as total_points_earned,
                     COALESCE(SUM(r.points_used), 0) as total_points_redeemed,
                     COALESCE(COUNT(t.transaction_id), 0) as deposit_count,
                     COALESCE(COUNT(r.redemption_id), 0) as redemption_count,
                     COALESCE(w.current_balance, 0) as current_balance
              FROM users u
              LEFT JOIN transactions t ON u.user_id = t.user_id AND DATE(t.transaction_date) BETWEEN ? AND ?
              LEFT JOIN redemption r ON u.user_id = r.user_id AND DATE(r.redemption_date) BETWEEN ? AND ?
              LEFT JOIN wallet w ON u.user_id = w.user_id
              GROUP BY u.user_id, u.first_name, u.last_name, u.email, w.current_balance
              HAVING (total_bottles > 0 OR total_points_redeemed > 0)
              ORDER BY total_bottles DESC
              LIMIT 15";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ssss', $date_from, $date_to, $date_from, $date_to);
    $stmt->execute();
    $data['top_users'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Monthly comparison
    $query = "SELECT 
                MONTH(transaction_date) as month,
                YEAR(transaction_date) as year,
                COUNT(*) as transactions,
                SUM(bottle_deposited) as bottles,
                SUM(points_earned) as points,
                COUNT(DISTINCT user_id) as active_users
              FROM transactions 
              WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY YEAR(transaction_date), MONTH(transaction_date)
              ORDER BY year, month";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $data['monthly'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Environmental impact trends
    $query = "SELECT 
                DATE_FORMAT(transaction_date, '%Y-%m') as month,
                SUM(bottle_deposited) as bottles,
                SUM(bottle_deposited) * 0.025 as waste_diverted_kg,
                SUM(bottle_deposited) * 0.05 as co2_saved_kg,
                SUM(bottle_deposited) * 0.1 as water_saved_liters
              FROM transactions 
              WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
              ORDER BY month";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $data['env_trends'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Machine performance
    $query = "SELECT 
                m.machine_id,
                m.location,
                m.status,
                0 as total_transactions,
                0 as bottles_collected,
                0 as points_distributed,
                0 as avg_bottles_per_transaction
              FROM machines m
              ORDER BY m.machine_id DESC";
    $stmt = $mysqli->prepare($query);
    $stmt->execute();
    $data['machine_performance'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

$analytics = getLguAnalyticsData($mysqli, $date_from, $date_to);

// Get summary stats
$summary_query = "SELECT 
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(bottle_deposited), 0) as total_bottles,
                    COALESCE(SUM(points_earned), 0) as total_points,
                    COALESCE(AVG(bottle_deposited), 0) as avg_bottles,
                    COUNT(DISTINCT user_id) as active_users,
                    COALESCE(SUM(bottle_deposited) * 0.025, 0) as waste_diverted_kg,
                    COALESCE(SUM(bottle_deposited) * 0.05, 0) as co2_saved_kg,
                    COALESCE(SUM(bottle_deposited) * 0.1, 0) as water_saved_liters
                  FROM transactions 
                  WHERE DATE(transaction_date) BETWEEN ? AND ?";
$summary_stmt = $mysqli->prepare($summary_query);
$summary_stmt->bind_param('ss', $date_from, $date_to);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();

// Get redemption stats
$redemption_query = "SELECT 
                       COUNT(*) as total_redemptions,
                       COALESCE(SUM(points_used), 0) as total_points_redeemed,
                       COALESCE(AVG(points_used), 0) as avg_points_per_redemption
                     FROM redemption 
                     WHERE DATE(redemption_date) BETWEEN ? AND ?";
$redemption_stmt = $mysqli->prepare($redemption_query);
$redemption_stmt->bind_param('ss', $date_from, $date_to);
$redemption_stmt->execute();
$redemption_stats = $redemption_stmt->get_result()->fetch_assoc();

// Get user engagement metrics
$engagement_query = "SELECT 
                       COUNT(DISTINCT u.user_id) as total_users,
                       COUNT(DISTINCT CASE WHEN t.user_id IS NOT NULL THEN u.user_id END) as active_users,
                       COUNT(DISTINCT CASE WHEN r.user_id IS NOT NULL THEN u.user_id END) as users_with_redemptions,
                       ROUND((COUNT(DISTINCT CASE WHEN t.user_id IS NOT NULL THEN u.user_id END) / COUNT(DISTINCT u.user_id)) * 100, 2) as engagement_rate
                     FROM users u
                     LEFT JOIN transactions t ON u.user_id = t.user_id AND DATE(t.transaction_date) BETWEEN ? AND ?
                     LEFT JOIN redemption r ON u.user_id = r.user_id AND DATE(r.redemption_date) BETWEEN ? AND ?";
$engagement_stmt = $mysqli->prepare($engagement_query);
$engagement_stmt->bind_param('ssss', $date_from, $date_to, $date_from, $date_to);
$engagement_stmt->execute();
$engagement_stats = $engagement_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reporting - MaBote.ph LGU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #1976D2;
            --primary-light: #42A5F5;
            --primary-dark: #0D47A1;
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
            --gradient-success: linear-gradient(135deg, var(--success-color) 0%, #66BB6A 100%);
            --gradient-warning: linear-gradient(135deg, var(--warning-color) 0%, #FFB74D 100%);
            --gradient-info: linear-gradient(135deg, var(--info-color) 0%, #42A5F5 100%);
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
        .stat-card.success::before { background: var(--gradient-success); }
        .stat-card.warning::before { background: var(--gradient-warning); }
        .stat-card.info::before { background: var(--gradient-info); }

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
        .stat-card.success .stat-icon { background: var(--gradient-success); }
        .stat-card.warning .stat-icon { background: var(--gradient-warning); }
        .stat-card.info .stat-icon { background: var(--gradient-info); }

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
            background: rgba(25, 118, 210, 0.05);
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

        .badge.bg-warning {
            background: var(--warning-color) !important;
        }

        .badge.bg-info {
            background: var(--info-color) !important;
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
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
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

        .environmental-impact {
            background: var(--gradient-success);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .impact-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .impact-title i {
            margin-right: 1rem;
            font-size: 2rem;
        }

        .impact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .impact-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .impact-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .impact-label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .export-btn {
            background: var(--gradient-info);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
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
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i>
                        User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reports.php">
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
            <h1 class="page-title">Analytics & Reporting</h1>
            <p class="page-subtitle">Comprehensive LGU insights and environmental impact analysis</p>
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

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="export-btn" onclick="exportToPDF()">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </button>
            <button class="export-btn" onclick="exportToExcel()">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
            <button class="export-btn" onclick="printReport()">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
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
                <div class="stat-label">Bottles Recycled</div>
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
                <div class="stat-number"><?php echo $engagement_stats['engagement_rate']; ?>%</div>
                <div class="stat-label">User Engagement</div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <!-- Environmental Impact -->
        <div class="environmental-impact">
            <h2 class="impact-title">
                <i class="fas fa-leaf"></i>Environmental Impact
            </h2>
            <div class="impact-grid">
                <div class="impact-item">
                    <div class="impact-number"><?php echo number_format($summary['waste_diverted_kg'], 1); ?>kg</div>
                    <div class="impact-label">Waste Diverted</div>
                </div>
                <div class="impact-item">
                    <div class="impact-number"><?php echo number_format($summary['co2_saved_kg'], 1); ?>kg</div>
                    <div class="impact-label">CO₂ Saved</div>
                </div>
                <div class="impact-item">
                    <div class="impact-number"><?php echo number_format($summary['water_saved_liters'], 1); ?>L</div>
                    <div class="impact-label">Water Saved</div>
                </div>
                <div class="impact-item">
                    <div class="impact-number"><?php echo $redemption_stats['total_redemptions']; ?></div>
                    <div class="impact-label">Rewards Claimed</div>
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
                            Daily Activity Trends
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

        <!-- Environmental Trends Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-leaf me-2"></i>
                            Environmental Impact Trends
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="envChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-trophy me-2"></i>
                    Top Contributors
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
                                <th>Current Balance</th>
                                <th>Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($analytics['top_users'])): ?>
                                <?php foreach ($analytics['top_users'] as $index => $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($index < 3): ?>
                                            <span class="badge bg-<?php echo $index == 0 ? 'warning' : ($index == 1 ? 'info' : 'success'); ?>">#<?php echo $index + 1; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">#<?php echo $index + 1; ?></span>
                                        <?php endif; ?>
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
                                    <td>
                                        <span class="badge bg-primary"><?php echo $user['current_balance']; ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $user['deposit_count']; ?> deposits, <?php echo $user['redemption_count']; ?> redemptions
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
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

        <!-- Machine Performance -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-server me-2"></i>
                    Machine Performance
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Machine ID</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Transactions</th>
                                <th>Bottles Collected</th>
                                <th>Points Distributed</th>
                                <th>Avg Bottles/Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($analytics['machine_performance'])): ?>
                                <?php foreach ($analytics['machine_performance'] as $machine): ?>
                                <tr>
                                    <td><strong>Bin #<?php echo $machine['machine_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($machine['location']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $machine['status'] == 'active' ? 'success' : ($machine['status'] == 'maintenance' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($machine['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $machine['total_transactions']; ?></td>
                                    <td><strong><?php echo $machine['bottles_collected']; ?></strong></td>
                                    <td><?php echo $machine['points_distributed']; ?></td>
                                    <td><?php echo number_format($machine['avg_bottles_per_transaction'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-server text-muted me-2"></i>
                                        No machine data available
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
                    borderColor: '#1976D2',
                    backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Bottles',
                    data: dailyData.map(item => item.bottles),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Active Users',
                    data: dailyData.map(item => item.active_users),
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
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
                }, {
                    label: 'Active Users',
                    data: monthlyData.map(item => item.active_users),
                    backgroundColor: '#FF9800',
                    borderColor: '#F57C00',
                    borderWidth: 1
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

        // Environmental Impact Chart
        const envCtx = document.getElementById('envChart').getContext('2d');
        const envData = <?php echo json_encode($analytics['env_trends']); ?>;
        
        new Chart(envCtx, {
            type: 'line',
            data: {
                labels: envData.map(item => item.month),
                datasets: [{
                    label: 'Waste Diverted (kg)',
                    data: envData.map(item => parseFloat(item.waste_diverted_kg)),
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'CO₂ Saved (kg)',
                    data: envData.map(item => parseFloat(item.co2_saved_kg)),
                    borderColor: '#2196F3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Water Saved (L)',
                    data: envData.map(item => parseFloat(item.water_saved_liters)),
                    borderColor: '#FF9800',
                    backgroundColor: 'rgba(255, 152, 0, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
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
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Export functions
        function exportToPDF() {
            window.print();
        }

        function exportToExcel() {
            // Simple CSV export
            const table = document.querySelector('.table');
            const rows = Array.from(table.querySelectorAll('tr'));
            const csv = rows.map(row => 
                Array.from(row.querySelectorAll('td, th'))
                    .map(cell => `"${cell.textContent.trim()}"`)
                    .join(',')
            ).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'lgu_analytics_report.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
