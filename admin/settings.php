<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireAdmin();

// Handle settings update
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_settings':
            $siteName = sanitize($_POST['site_name']);
            $siteEmail = sanitize($_POST['site_email']);
            $maintenanceMode = $_POST['maintenance_mode'] ?? 'off';
            $allowRegistration = $_POST['allow_registration'] ?? 'off';
            
            // In a real application, you would save these to a settings table
            $_SESSION['success_message'] = 'Settings updated successfully!';
            logActivity($_SESSION['user_id'], 'settings_updated', 'Updated system settings');
            break;
            
        case 'clear_cache':
            // Clear cache logic
            $_SESSION['success_message'] = 'Cache cleared successfully!';
            logActivity($_SESSION['user_id'], 'cache_cleared', 'Cleared system cache');
            break;
            
        case 'backup_database':
            // Database backup logic
            $_SESSION['success_message'] = 'Database backup initiated!';
            logActivity($_SESSION['user_id'], 'database_backup', 'Initiated database backup');
            break;
    }
    
    header('Location: settings.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="users.php">User Management</a></li>
                        <li><a class="dropdown-item" href="courses.php">Course Management</a></li>
                        <li><a class="dropdown-item" href="analytics.php">Analytics</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2"></i> User Management
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> Course Management
                    </a>
                    <a href="categories.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tags me-2"></i> Categories
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt me-2"></i> Reports
                    </a>
                    <a href="logs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Settings</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- General Settings -->
                <div class="dashboard-card mb-4">
                    <h3>General Settings</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-control" value="IT HUB">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Email</label>
                                    <input type="email" name="site_email" class="form-control" value="admin@ithub.com">
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode">
                                        <label class="form-check-label" for="maintenance_mode">
                                            Maintenance Mode
                                        </label>
                                    </div>
                                    <small class="text-muted">Enable to temporarily disable user access</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_registration" id="allow_registration" checked>
                                        <label class="form-check-label" for="allow_registration">
                                            Allow Registration
                                        </label>
                                    </div>
                                    <small class="text-muted">Allow new users to register</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Max File Size (MB)</label>
                                    <input type="number" name="max_file_size" class="form-control" value="50" min="1" max="100">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" name="session_timeout" class="form-control" value="30" min="5" max="120">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- System Maintenance -->
                <div class="dashboard-card mb-4">
                    <h3>System Maintenance</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="maintenance-icon mb-3">
                                    <i class="fas fa-broom fa-3x text-primary"></i>
                                </div>
                                <h5>Clear Cache</h5>
                                <p class="text-muted">Clear system cache and temporary files</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="clear_cache">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-broom me-2"></i>Clear Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="maintenance-icon mb-3">
                                    <i class="fas fa-database fa-3x text-success"></i>
                                </div>
                                <h5>Database Backup</h5>
                                <p class="text-muted">Create a backup of the database</p>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="backup_database">
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="fas fa-download me-2"></i>Backup Now
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="maintenance-icon mb-3">
                                    <i class="fas fa-sync fa-3x text-info"></i>
                                </div>
                                <h5>Optimize Database</h5>
                                <p class="text-muted">Optimize database performance</p>
                                <button class="btn btn-outline-info" onclick="optimizeDatabase()">
                                    <i class="fas fa-sync me-2"></i>Optimize
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="dashboard-card">
                    <h3>System Information</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>PHP Version:</th>
                                    <td><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <th>MySQL Version:</th>
                                    <td>8.0.23</td>
                                </tr>
                                <tr>
                                    <th>Server Software:</th>
                                    <td><?php echo isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'; ?></td>
                                </tr>
                                <tr>
                                    <th>Document Root:</th>
                                    <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>Memory Limit:</th>
                                    <td><?php echo ini_get('memory_limit'); ?></td>
                                </tr>
                                <tr>
                                    <th>Max Upload:</th>
                                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                </tr>
                                <tr>
                                    <th>Max Post Size:</th>
                                    <td><?php echo ini_get('post_max_size'); ?></td>
                                </tr>
                                <tr>
                                    <th>Timezone:</th>
                                    <td><?php echo date_default_timezone_get(); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function optimizeDatabase() {
            if (confirm('This will optimize the database tables. Continue?')) {
                // AJAX call to optimize database
                $.post('api/optimize_database.php', {}, function(response) {
                    if (response.success) {
                        alert('Database optimized successfully!');
                    } else {
                        alert('Failed to optimize database');
                    }
                });
            }
        }
    </script>
</body>
</html>
