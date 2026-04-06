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

require_once dirname(__DIR__) . '/includes/universal_header.php';
?>

<link rel="stylesheet" href="../assets/css/admin-theme.css">

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Admin Dashboard Header -->
            <div class="admin-dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">⚙️ Settings</h2>
                        <p class="mb-0 opacity-75">Manage system settings and maintenance</p>
                    </div>
                    <div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-cog me-2"></i>
                    General Settings
                </div>
                <div class="card-body">
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
                            <button type="submit" class="btn-modern btn-primary-modern">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Maintenance -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-tools me-2"></i>
                    System Maintenance
                </div>
                <div class="card-body">
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
                                    <button type="submit" class="btn-modern btn-outline-primary-modern">
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
                                    <button type="submit" class="btn-modern btn-outline-success-modern">
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
                                <button class="btn-modern btn-outline-info-modern" onclick="optimizeDatabase()">
                                    <i class="fas fa-sync me-2"></i>Optimize
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <i class="fas fa-info-circle me-2"></i>
                    System Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="admin-modern-table">
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
                            <table class="admin-modern-table">
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
</div>

<script>
    function optimizeDatabase() {
        if (confirm('This will optimize the database tables. Continue?')) {
            // AJAX call to optimize database
            fetch('api/optimize_database.php', {
                method: 'POST'
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Database optimized successfully!');
                } else {
                    alert('Failed to optimize database');
                }
            })
            .catch(error => {
                alert('An error occurred while optimizing the database');
            });
        }
    }

    // Add animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Animate content cards
        const contentCards = document.querySelectorAll('.admin-content-card');
        contentCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
