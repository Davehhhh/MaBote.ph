<?php
require_once 'config.php';
requireAdminLogin();

$stats = getDashboardStats();
$userStats = getUserStats();
$recentTransactions = getRecentTransactions(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MaBote.ph Admin</title>
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

        .welcome-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-medium);
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            opacity: 0.9;
            font-size: 1rem;
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
                    <a class="nav-link active" href="dashboard.php">
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
                    <a class="nav-link" href="reports.php">
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
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Welcome back, admin! Here's what's happening with MaBote.ph</p>
        </div>

        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-title">Welcome back, admin!</div>
            <div class="welcome-subtitle">Monitor and manage your recycling platform with real-time insights</div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo $userStats['new_this_month']; ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['total_bottles']; ?></div>
                <div class="stat-label">Bottles Collected</div>
                <div class="stat-icon">
                    <i class="fas fa-recycle"></i>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['total_points']; ?></div>
                <div class="stat-label">Points Distributed</div>
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-label">Total Transactions</div>
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Recent Transactions
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Bottles</th>
                                <th>Points</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="fw-medium"><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></div>
                                            <small class="text-muted">User ID: <?php echo $transaction['user_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $transaction_type = $transaction['transaction_type'] ?? 'deposit';
                                    if ($transaction_type === 'redemption') {
                                        echo '<span class="badge bg-warning">Redemption</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Deposit</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $transaction['bottle_deposited']; ?></td>
                                <td>
                                    <?php 
                                    $points = $transaction['points_earned'];
                                    if ($points > 0) {
                                        echo '<span class="badge bg-success">+' . $points . '</span>';
                                    } elseif ($points < 0) {
                                        echo '<span class="badge bg-danger">' . $points . '</span>';
                                    } else {
                                        echo '<span class="text-muted">0</span>';
                                    }
                                    ?>
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
