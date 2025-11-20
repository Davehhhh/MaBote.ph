<?php
require_once 'config.php';
requireLguLogin();

$stats = getLguDashboardStats();
$envStats = getLguEnvironmentalStats();
$userStats = getUserStats();
$recentTransactions = getRecentTransactions(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MaBote.ph LGU</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
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
            <a class="nav-link active" href="dashboard.php">
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
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        </div>

        <!-- Welcome Card -->
        <div class="card mb-4">
            <div class="card-body bg-primary text-white">
                <h4 class="card-title">Welcome back, LGU!</h4>
                <p class="card-text">Monitor and manage your recycling platform with real-time insights</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['bottles_this_month']; ?></div>
                    <div class="stat-label">Bottles This Month</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['co2_saved'], 1); ?>kg</div>
                    <div class="stat-label">CO₂ Saved</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_bins']; ?></div>
                    <div class="stat-label">Active Bins</div>
                </div>
            </div>
        </div>

        <!-- Environmental Impact -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-leaf me-2"></i>Environmental Impact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h3 class="text-success"><?php echo $envStats['total_bottles']; ?></h3>
                                <small class="text-muted">Total Bottles Recycled</small>
                            </div>
                            <div class="col-6">
                                <h3 class="text-warning"><?php echo number_format($envStats['waste_diverted'], 1); ?>kg</h3>
                                <small class="text-muted">Waste Diverted</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Contributors</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($envStats['top_contributors'])): ?>
                            <?php foreach ($envStats['top_contributors'] as $index => $contributor): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="badge bg-success me-2"><?php echo $index + 1; ?></span>
                                        <?php echo htmlspecialchars($contributor['username']); ?>
                                    </div>
                                    <small class="text-muted"><?php echo $contributor['bottles']; ?> bottles</small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No contributors yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentTransactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Points</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['type'] === 'deposit' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($transaction['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($transaction['points']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent transactions</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
