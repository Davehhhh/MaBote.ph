<?php
require_once 'config.php';
requireAdminLogin();

// Handle machine actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $machine_id = $_POST['machine_id'] ?? '';
        $location = $_POST['location'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if ($machine_id && $location) {
            $stmt = $mysqli->prepare("INSERT INTO machines (machine_id, location, status) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $machine_id, $location, $status);
            $stmt->execute();
            $success = "Machine added successfully";
        } else {
            $error = "Please fill in all required fields";
        }
    } elseif ($action === 'edit') {
        $machine_id = $_POST['machine_id'] ?? '';
        $location = $_POST['location'] ?? '';
        $status = $_POST['status'] ?? 'active';
        
        if ($machine_id && $location) {
            $stmt = $mysqli->prepare("UPDATE machines SET location = ?, status = ? WHERE machine_id = ?");
            $stmt->bind_param('sss', $location, $status, $machine_id);
            $stmt->execute();
            $success = "Machine updated successfully";
        }
    } elseif ($action === 'delete') {
        $machine_id = $_POST['machine_id'] ?? '';
        
        if ($machine_id) {
            $stmt = $mysqli->prepare("DELETE FROM machines WHERE machine_id = ?");
            $stmt->bind_param('s', $machine_id);
            $stmt->execute();
            $success = "Machine deleted successfully";
        }
    }
}

// Check if machine_id column exists in transactions table
$check_column = $mysqli->query("SHOW COLUMNS FROM transactions LIKE 'machine_id'");
$has_machine_id = $check_column->num_rows > 0;

if ($has_machine_id) {
    // Get machines with transaction data
    $query = "SELECT m.*, 
                     COALESCE(COUNT(t.transaction_id), 0) as total_transactions,
                     COALESCE(SUM(t.bottle_deposited), 0) as total_bottles,
                     COALESCE(SUM(t.points_earned), 0) as total_points
              FROM machines m
              LEFT JOIN transactions t ON m.machine_id = t.machine_id AND t.points_earned > 0
              GROUP BY m.machine_id
              ORDER BY m.machine_id DESC";
} else {
    // Get machines without transaction data (machine_id column doesn't exist yet)
    $query = "SELECT m.*, 
                     0 as total_transactions,
                     0 as total_bottles,
                     0 as total_points
              FROM machines m
              ORDER BY m.machine_id DESC";
}
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
    <title>Machine Management - MaBote.ph Admin</title>
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
        .stat-card.danger { border-left-color: #e74c3c; }
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
            <a class="nav-link active" href="machines.php">
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
            <h2><i class="fas fa-cogs"></i> Machine Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                <i class="fas fa-plus"></i> Add Machine
            </button>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-primary"><?php echo $stats['total_machines']; ?></div>
                            <div class="text-muted">Total Machines</div>
                        </div>
                        <i class="fas fa-cogs fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-success"><?php echo $stats['active_machines']; ?></div>
                            <div class="text-muted">Active</div>
                        </div>
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-warning"><?php echo $stats['maintenance_machines']; ?></div>
                            <div class="text-muted">Maintenance</div>
                        </div>
                        <i class="fas fa-tools fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 text-danger"><?php echo $stats['offline_machines']; ?></div>
                            <div class="text-muted">Offline</div>
                        </div>
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Machines Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Machine ID</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Transactions</th>
                                <th>Bottles</th>
                                <th>Points</th>
                                <th>Fill Level</th>
                                <th>Battery</th>
                                <th>Last Seen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($machines as $machine): ?>
                            <tr>
                                <td><strong><?php echo $machine['machine_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($machine['location']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = $machine['status'] === 'active' ? 'bg-success' : 
                                                  ($machine['status'] === 'maintenance' ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($machine['status']); ?></span>
                                </td>
                                <td><?php echo $machine['total_transactions']; ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo number_format($machine['total_bottles']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo number_format($machine['total_points']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $machine['fill_level'] ?? 0; ?>%</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $machine['battery_level'] ?? 100; ?>%</span>
                                </td>
                                <td><?php echo isset($machine['last_seen']) && $machine['last_seen'] ? date('M j, Y H:i', strtotime($machine['last_seen'])) : 'Never'; ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" data-bs-target="#editMachineModal<?php echo $machine['machine_id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                    onclick="return confirm('Delete this machine?')">
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
            </div>
        </div>
    </div>

    <!-- Add Machine Modal -->
    <div class="modal fade" id="addMachineModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Machine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Machine ID</label>
                            <input type="text" class="form-control" name="machine_id" placeholder="e.g., BIN001" required>
                            <div class="form-text">Unique identifier for the machine</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" placeholder="e.g., Mall Entrance" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Machine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Machine Modals -->
    <?php foreach ($machines as $machine): ?>
    <div class="modal fade" id="editMachineModal<?php echo $machine['machine_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Machine: <?php echo $machine['machine_id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Machine ID</label>
                            <input type="text" class="form-control" value="<?php echo $machine['machine_id']; ?>" readonly>
                            <div class="form-text">Machine ID cannot be changed</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($machine['location']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?php echo $machine['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo $machine['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="offline" <?php echo $machine['status'] === 'offline' ? 'selected' : ''; ?>>Offline</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Machine</button>
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
