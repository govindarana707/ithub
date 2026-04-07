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
    JOIN users_new u ON al.user_id = u.id
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

require_once dirname(__DIR__) . '/includes/universal_header.php';

function getActionBadgeClass($action) {
    $classes = [
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
    return $classes[$action] ?? 'secondary';
}
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
                        <h2 class="mb-1">📋 Activity Logs</h2>
                        <p class="mb-0 opacity-75">Monitor system activity and user actions</p>
                    </div>
                    <div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-filter me-2"></i>
                    Filters
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
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
                        
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-modern btn-primary-modern">Filter</button>
                                <a href="logs.php" class="btn-modern btn-secondary-modern">Clear</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-3">
                        <button class="btn-modern btn-outline-warning-modern" onclick="exportLogs()">
                            <i class="fas fa-download me-2"></i>Export Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-list-alt me-2"></i>
                            Activity Logs
                            <span class="admin-badge primary ms-2"><?php echo $totalLogs; ?> total</span>
                        </div>
                        <button class="btn-modern btn-outline-danger-modern" onclick="clearLogs()">
                            <i class="fas fa-trash me-1"></i>Clear Old Logs
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="admin-modern-table">
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
                                        <td colspan="6" class="text-center py-5">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                                                <h5>No activity logs found</h5>
                                                <p class="text-muted">Try adjusting your filters</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="admin-avatar-placeholder me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                                        <?php echo strtoupper(substr($log['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($log['full_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($log['user_role']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="admin-badge <?php echo getActionBadgeClass($log['action']); ?>">
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
                        <div class="admin-pagination">
                            <?php if ($page > 1): ?>
                                <a class="admin-page-btn" href="?page=<?php echo $page - 1; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a class="admin-page-btn <?php echo $i == $page ? 'active' : ''; ?>" href="?page=<?php echo $i; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a class="admin-page-btn" href="?page=<?php echo $page + 1; ?>&action=<?php echo $action; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function exportLogs() {
        window.location.href = 'api/export_logs.php';
    }
    
    function clearLogs() {
        if (confirm('Are you sure you want to clear logs older than 30 days? This action cannot be undone.')) {
            window.location.href = 'api/clear_logs.php';
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

        // Animate table rows
        const tableRows = document.querySelectorAll('.admin-modern-table tbody tr');
        tableRows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            row.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                row.style.opacity = '1';
                row.style.transform = 'translateX(0)';
            }, 400 + (index * 50));
        });
    });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
