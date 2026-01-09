<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireAdmin();

// Get activity logs with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$db = new Database();
$conn = $db->getConnection();

// Filter options
$action = $_GET['action'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT al.*, u.full_name, u.email, u.role as user_role
    FROM admin_logs al
    JOIN users u ON al.user_id = u.id
    WHERE 1=1
";
$params = [];
$types = "";

if ($action) {
    $sql .= " AND al.action = ?";
    $params[] = $action;
    $types .= "s";
}

if ($user_id) {
    $sql .= " AND al.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
}

if ($date_from) {
    $sql .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $sql .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM admin_logs WHERE 1=1";
$countParams = [];
$countTypes = "";

if ($action) {
    $countSql .= " AND action = ?";
    $countParams[] = $action;
    $countTypes .= "s";
}

if ($user_id) {
    $countSql .= " AND user_id = ?";
    $countParams[] = $user_id;
    $countTypes .= "i";
}

if ($date_from) {
    $countSql .= " AND DATE(created_at) >= ?";
    $countParams[] = $date_from;
    $countTypes .= "s";
}

if ($date_to) {
    $countSql .= " AND DATE(created_at) <= ?";
    $countParams[] = $date_to;
    $countTypes .= "s";
}

$countStmt = $conn->prepare($countSql);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalLogs / $limit);

// Get unique actions for filter dropdown
$actions = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - IT HUB</title>
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
                    <a href="logs.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Activity Logs</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="dashboard-card mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo $act['action']; ?>" <?php echo $action === $act['action'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($act['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="logs.php" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-warning w-100" onclick="exportLogs()">
                                <i class="fas fa-download me-2"></i>Export Logs
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Logs Table -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Activity Logs (<?php echo $totalLogs; ?> total)</h3>
                        <button class="btn btn-sm btn-outline-danger" onclick="clearLogs()">
                            <i class="fas fa-trash me-1"></i>Clear Old Logs
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Date/Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-list-alt fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No activity logs found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                         style="width: 24px; height: 24px; font-size: 10px;">
                                                        <?php echo strtoupper(substr($log['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div><?php echo htmlspecialchars($log['full_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($log['user_role']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo getActionBadgeColor($log['action']); ?>">
                                                    <?php echo ucfirst($log['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                                            <td><?php echo $log['ip_address']; ?></td>
                                            <td><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function exportLogs() {
            window.location.href = 'api/export_logs.php';
        }
        
        function clearLogs() {
            if (confirm('Are you sure you want to clear logs older than 30 days? This action cannot be undone.')) {
                window.location.href = 'api/clear_logs.php';
            }
        }
    </script>
</body>
</html>

<?php
function getActionBadgeColor($action) {
    $colors = [
        'login' => 'success',
        'register' => 'info',
        'course_created' => 'primary',
        'enrollment' => 'success',
        'quiz_completed' => 'warning',
        'profile_updated' => 'secondary',
        'password_changed' => 'danger',
        'user_created' => 'primary',
        'user_updated' => 'info',
        'user_deleted' => 'danger'
    ];
    return $colors[$action] ?? 'secondary';
}
?>
