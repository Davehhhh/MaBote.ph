<?php
require_once 'config.php';
requireLguLogin();

// Handle machine actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $location = isset($_POST['location']) ? trim($_POST['location']) : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';
            
            // Validate location
            if (empty($location)) {
                $error = "Location is required.";
            } else {
                // Generate a unique machine_id (BIN001, BIN002, etc.)
                // First, get all existing machine_ids that match the BIN### pattern
                $all_ids_query = $mysqli->query("SELECT machine_id FROM machines WHERE machine_id LIKE 'BIN%'");
                $next_number = 1;
                
                if ($all_ids_query && $all_ids_query->num_rows > 0) {
                    $max_number = 0;
                    while ($row = $all_ids_query->fetch_assoc()) {
                        $id = $row['machine_id'];
                        // Extract number from ID (e.g., "BIN005" -> 5)
                        if (preg_match('/BIN(\d+)/', $id, $matches)) {
                            $num = (int)$matches[1];
                            if ($num > $max_number) {
                                $max_number = $num;
                            }
                        }
                    }
                    $next_number = $max_number + 1;
                }
                
                // Format as BIN001, BIN002, etc. (3 digits)
                $machine_id = 'BIN' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                
                // Check if this ID already exists (safety check)
                $check_stmt = $mysqli->prepare("SELECT machine_id FROM machines WHERE machine_id = ? LIMIT 1");
                $check_stmt->bind_param("s", $machine_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // If exists, try next number
                    $next_number++;
                    $machine_id = 'BIN' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
                }
                $check_stmt->close();
                
                // Insert with machine_id
                $stmt = $mysqli->prepare("INSERT INTO machines (machine_id, location, status) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $machine_id, $location, $status);
                if ($stmt->execute()) {
                    $success = "Machine added successfully!";
                } else {
                    $error = "Failed to add machine: " . $mysqli->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'update') {
            $machine_id = isset($_POST['machine_id']) ? trim($_POST['machine_id']) : '';
            $location = isset($_POST['location']) ? trim($_POST['location']) : '';
            $status = isset($_POST['status']) ? $_POST['status'] : 'active';
            
            if (empty($machine_id) || empty($location)) {
                $error = "Machine ID and location are required.";
            } else {
                $stmt = $mysqli->prepare("UPDATE machines SET location = ?, status = ? WHERE machine_id = ?");
                $stmt->bind_param("sss", $location, $status, $machine_id);
                if ($stmt->execute()) {
                    $success = "Machine updated successfully!";
                } else {
                    $error = "Failed to update machine: " . $mysqli->error;
                }
                $stmt->close();
            }
        } elseif ($action === 'delete') {
            $machine_id = isset($_POST['machine_id']) ? trim($_POST['machine_id']) : '';
            
            if (empty($machine_id)) {
                $error = "Machine ID is required.";
            } else {
                $stmt = $mysqli->prepare("DELETE FROM machines WHERE machine_id = ?");
                $stmt->bind_param("s", $machine_id);
                if ($stmt->execute()) {
                    $success = "Machine deleted successfully!";
                } else {
                    $error = "Failed to delete machine: " . $mysqli->error;
                }
                $stmt->close();
            }
        }
    }
}

// Get machines
$query = "SELECT m.*, 
                 0 as total_transactions,
                 0 as total_points
          FROM machines m
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
        .machine-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .machine-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
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
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i>Overview
            </a>
            <a class="nav-link active" href="bins.php">
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
            <h2><i class="fas fa-trash-alt"></i> Bin Management</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMachineModal">
                <i class="fas fa-plus me-2"></i>Add New Bin
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_machines']; ?></div>
                    <div class="stat-label">Total Bins</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_machines']; ?></div>
                    <div class="stat-label">Active Bins</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['maintenance_machines']; ?></div>
                    <div class="stat-label">Maintenance</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['offline_machines']; ?></div>
                    <div class="stat-label">Offline</div>
                </div>
            </div>
        </div>

        <!-- Machines List -->
        <div class="row">
            <?php foreach ($machines as $machine): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="machine-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0">Bin #<?php echo $machine['machine_id']; ?></h5>
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
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($machine['location']); ?></p>
                        
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="fw-bold text-primary"><?php echo $machine['total_transactions']; ?></div>
                                <small class="text-muted">Transactions</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold text-success"><?php echo number_format($machine['total_points']); ?></div>
                                <small class="text-muted">Points Generated</small>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
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
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($machines)): ?>
            <div class="text-center py-5">
                <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No bins available</h4>
                <p class="text-muted">Add your first bin to get started!</p>
            </div>
        <?php endif; ?>
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
