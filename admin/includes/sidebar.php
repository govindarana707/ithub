<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireAdmin();

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = pathinfo($current_page, PATHINFO_FILENAME);

// Get admin stats for badges
$conn = connectDB();
$adminId = $_SESSION['user_id'];

// Get user counts
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users_new WHERE role = 'student'");
$stmt->execute();
$studentCount = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE status = 'published'");
$stmt->execute();
$courseCount = $stmt->get_result()->fetch_assoc()['count'];

// Get pending approvals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];

$conn->close();
?>

<div class="list-group admin-sidebar">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
    <a href="users.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
        <i class="fas fa-users me-2"></i> Users
        <?php if ($studentCount > 0): ?>
            <span class="badge bg-primary float-end"><?php echo $studentCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="courses.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : ''; ?>">
        <i class="fas fa-book me-2"></i> Courses
        <?php if ($courseCount > 0): ?>
            <span class="badge bg-success float-end"><?php echo $courseCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="categories.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i class="fas fa-folder me-2"></i> Categories
    </a>
    <a href="analytics.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line me-2"></i> Analytics
    </a>
    <a href="reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar me-2"></i> Reports
    </a>
    <a href="logs.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
        <i class="fas fa-list-alt me-2"></i> Activity Logs
    </a>
    <a href="settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog me-2"></i> Settings
    </a>
</div>

<style>
.admin-sidebar {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    padding: 10px;
    background: white;
}

.admin-sidebar .list-group-item {
    border: none;
    padding: 12px 20px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
    margin-bottom: 4px;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
}

.admin-sidebar .list-group-item:hover {
    background: linear-gradient(135deg, rgba(65, 105, 225, 0.1) 0%, rgba(49, 87, 201, 0.1) 100%);
    transform: translateX(5px);
    color: #4169E1;
}

.admin-sidebar .list-group-item.active {
    background: linear-gradient(135deg, #4169E1 0%, #3157c9 100%);
    color: white;
    font-weight: 600;
}

.admin-sidebar .list-group-item.active:hover {
    transform: translateX(0);
    color: white;
}

.admin-sidebar .badge {
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
}

.pending-approvals {
    margin-top: 10px;
    border-radius: 8px;
}
</style>

<?php if ($pendingCount > 0): ?>
<div class="admin-sidebar pending-approvals mt-3">
    <a href="courses.php?filter=pending" class="list-group-item list-group-item-action text-warning">
        <i class="fas fa-exclamation-triangle me-2"></i> Pending Approvals
        <span class="badge bg-warning float-end"><?php echo $pendingCount; ?></span>
    </a>
</div>
<?php endif; ?>
