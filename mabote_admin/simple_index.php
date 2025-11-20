<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MaBote.ph - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
        }
        .admin-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .admin-body {
            padding: 2rem;
        }
        .module-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .module-card:hover {
            border-color: #4CAF50;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
        }
        .module-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-card">
            <div class="admin-header">
                <div class="mb-3">
                    <i class="fas fa-recycle fa-3x"></i>
                </div>
                <h2>MaBote.ph Admin Panel</h2>
                <p class="mb-0">Complete Management System</p>
            </div>
            
            <div class="admin-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="dashboard.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-tachometer-alt module-icon text-primary"></i>
                                </div>
                                <div>
                                    <h5>Dashboard</h5>
                                    <p class="text-muted mb-0">Overview and statistics</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="users.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-users module-icon text-info"></i>
                                </div>
                                <div>
                                    <h5>User Management</h5>
                                    <p class="text-muted mb-0">Manage users and accounts</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="machines.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-cogs module-icon text-warning"></i>
                                </div>
                                <div>
                                    <h5>Machine Management</h5>
                                    <p class="text-muted mb-0">Manage recycling machines</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="transactions.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-exchange-alt module-icon text-success"></i>
                                </div>
                                <div>
                                    <h5>Transactions</h5>
                                    <p class="text-muted mb-0">View and manage transactions</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="reports.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-chart-bar module-icon text-danger"></i>
                                </div>
                                <div>
                                    <h5>Reports & Analytics</h5>
                                    <p class="text-muted mb-0">Detailed reports and insights</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="notifications.php" class="module-card">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-bell module-icon text-secondary"></i>
                                </div>
                                <div>
                                    <h5>Notifications</h5>
                                    <p class="text-muted mb-0">Send notifications to users</p>
                                </div>
                            </div>
                            <span class="badge bg-success status-badge">Ready</span>
                        </a>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Database connection needs to be configured. 
                        Please check MySQL settings in XAMPP Control Panel.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


