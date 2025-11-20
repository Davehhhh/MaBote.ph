<?php
require_once 'config.php';
requireLguLogin();

// Handle machine actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $location = $_POST['location'];
            $status = $_POST['status'] ?? 'active';
            
            $stmt = $mysqli->prepare("INSERT INTO machines (location, status) VALUES (?, ?)");
            $stmt->bind_param("ss", $location, $status);
            if ($stmt->execute()) {
                $success = "Machine added successfully!";
            } else {
                $error = "Failed to add machine.";
            }
        } elseif ($action === 'update') {
            $machine_id = $_POST['machine_id'];
            $location = $_POST['location'];
            $status = $_POST['status'];
            
            $stmt = $mysqli->prepare("UPDATE machines SET location = ?, status = ? WHERE machine_id = ?");
            $stmt->bind_param("ssi", $location, $status, $machine_id);
            if ($stmt->execute()) {
                $success = "Machine updated successfully!";
            } else {
                $error = "Failed to update machine.";
            }
        } elseif ($action === 'delete') {
            $machine_id = $_POST['machine_id'];
            
            $stmt = $mysqli->prepare("DELETE FROM machines WHERE machine_id = ?");
            $stmt->bind_param("i", $machine_id);
            if ($stmt->execute()) {
                $success = "Machine deleted successfully!";
            } else {
                $error = "Failed to delete machine.";
            }
        }
    }
}

// Get machines
$query = "SELECT m.*, 
                 COALESCE(COUNT(t.transaction_id), 0) as total_transactions,
                 COALESCE(SUM(t.points_earned), 0) as total_points
          FROM machines m
          LEFT JOIN transactions t ON m.machine_id = t.machine_id
          GROUP BY m.machine_id
          ORDER BY m.machine_id DESC";
$machines = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);

// Get machine statistics
$stats_query = "SELECT 
                    COUNT(*) as total_machines,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_machines,
                    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_machines,
                    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_machines
                FROM machines";
$stats = $mysqli->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bin Management - MaBote.ph LGU</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            border: 1px solid var(--border-color);
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.success .stat-icon { background: var(--gradient-primary); }
        .stat-card.warning .stat-icon { background: linear-gradient(135deg, var(--warning-color) 0%, #FFB74D 100%); }
        .stat-card.info .stat-icon { background: linear-gradient(135deg, var(--info-color) 0%, #42A5F5 100%); }

        .machine-card {
            background: var(--surface-color);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .machine-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-2px);
        }

        .machine-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .machine-info h5 {
            margin: 0;
            font-weight: 600;
            color: var(--text-primary);
        }

        .machine-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .machine-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .machine-stat {
            text-align: center;
            padding: 1rem;
            background: rgba(46, 125, 50, 0.05);
            border-radius: 12px;
        }

        .machine-stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .machine-stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .machine-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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

        .btn-warning {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-danger {
            background: linear-gradient(135deg, #F44336 0%, #D32F2F 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .machine-stats {
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
                    <a class="nav-link active" href="bins.php">
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
            <h1 class="page-title">Bin Management</h1>
            <p class="page-subtitle">Manage recycling bins and their status</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['total_machines']; ?></div>
                <div class="stat-label">Total Bins</div>
                <div class="stat-icon">
                    <i class="fas fa-trash-alt"></i>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo $stats['active_machines']; ?></div>
                <div class="stat-label">Active Bins</div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo $stats['maintenance_machines']; ?></div>
                <div class="stat-label">Maintenance</div>
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-number"><?php echo $stats['offline_machines']; ?></div>
                <div class="stat-label">Offline</div>
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>

        <!-- Add Machine Button -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Bin List</h3>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                    <i class="fas fa-plus me-2"></i>Add New Bin
                </button>
            </div>
        </div>

        <!-- Machines List -->
        <div class="content-card">
            <?php if (empty($machines)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No bins available</h4>
                    <p class="text-muted">Add your first bin to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($machines as $machine): ?>
                    <div class="machine-card">
                        <div class="machine-header">
                            <div class="machine-info">
                                <h5>Bin #<?php echo $machine['machine_id']; ?></h5>
                                <p><?php echo htmlspecialchars($machine['location']); ?></p>
                            </div>
                            <div>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch ($machine['status']) {
                                    case 'active':
                                        $status_class = 'bg-success';
                                        $status_text = 'Active';
                                        break;
                                    case 'maintenance':
                                        $status_class = 'bg-warning';
                                        $status_text = 'Maintenance';
                                        break;
                                    case 'offline':
                                        $status_class = 'bg-danger';
                                        $status_text = 'Offline';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                        </div>
                        
                        <div class="machine-stats">
                            <div class="machine-stat">
                                <div class="machine-stat-number"><?php echo $machine['total_transactions']; ?></div>
                                <div class="machine-stat-label">Transactions</div>
                            </div>
                            <div class="machine-stat">
                                <div class="machine-stat-number"><?php echo number_format($machine['total_points']); ?></div>
                                <div class="machine-stat-label">Points Generated</div>
                            </div>
                        </div>
                        
                        <div class="machine-actions">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editMachineModal<?php echo $machine['machine_id']; ?>">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteMachine(<?php echo $machine['machine_id']; ?>)">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>

                    <!-- Edit Machine Modal -->
                    <div class="modal fade" id="editMachineModal<?php echo $machine['machine_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Bin #<?php echo $machine['machine_id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Location</label>
                                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($machine['location']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status" required>
                                                <option value="active" <?php echo $machine['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="maintenance" <?php echo $machine['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                <option value="offline" <?php echo $machine['status'] === 'offline' ? 'selected' : ''; ?>>Offline</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Bin</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Machine Modal -->
    <div class="modal fade" id="addMachineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Bin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Bin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this bin? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="machine_id" id="deleteMachineId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteMachine(machineId) {
            document.getElementById('deleteMachineId').value = machineId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>


