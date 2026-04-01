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

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users_new WHERE role = 'instructor'");
$stmt->execute();
$instructorCount = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE status = 'published'");
$stmt->execute();
$courseCount = $stmt->get_result()->fetch_assoc()['count'];

// Get pending approvals
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE status = 'pending'");
$stmt->execute();
$pendingCount = $stmt->get_result()->fetch_assoc()['count'];

$conn->close();
?>

<div class="list-group sidebar-modern">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
    <a href="users.php" class="list-group-item list-group-item-action <?php echo $current_page === 'users' ? 'active' : ''; ?>">
        <i class="fas fa-users-cog me-2"></i> User Management
        <span class="badge bg-info float-end"><?php echo $studentCount + $instructorCount; ?></span>
    </a>
    <a href="courses.php" class="list-group-item list-group-item-action <?php echo $current_page === 'courses' ? 'active' : ''; ?>">
        <i class="fas fa-book-open me-2"></i> Course Management
        <span class="badge bg-primary float-end"><?php echo $courseCount; ?></span>
    </a>
    <a href="categories.php" class="list-group-item list-group-item-action <?php echo $current_page === 'categories' ? 'active' : ''; ?>">
        <i class="fas fa-tags me-2"></i> Categories
    </a>
    <a href="analytics.php" class="list-group-item list-group-item-action <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line me-2"></i> Analytics
    </a>
    <a href="reports.php" class="list-group-item list-group-item-action <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
        <i class="fas fa-file-alt me-2"></i> Reports
    </a>
    <a href="logs.php" class="list-group-item list-group-item-action <?php echo $current_page === 'logs' ? 'active' : ''; ?>">
        <i class="fas fa-list-alt me-2"></i> Activity Logs
    </a>
    <a href="trial-analytics.php" class="list-group-item list-group-item-action <?php echo $current_page === 'trial-analytics' ? 'active' : ''; ?>">
        <i class="fas fa-clock me-2"></i> Trial Analytics
    </a>
    <a href="settings.php" class="list-group-item list-group-item-action <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
        <i class="fas fa-cog me-2"></i> Settings
    </a>
    <?php if ($pendingCount > 0): ?>
        <a href="courses.php?filter=pending" class="list-group-item list-group-item-action text-warning">
            <i class="fas fa-exclamation-triangle me-2"></i> Pending Approvals
            <span class="badge bg-warning float-end"><?php echo $pendingCount; ?></span>
        </a>
    <?php endif; ?>
    <hr class="my-3">
    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
</div>

<style>
.sidebar-modern {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    overflow: hidden;
}

.sidebar-modern .list-group-item {
    border: none;
    padding: 15px 20px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}

.sidebar-modern .list-group-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateX(5px);
}

.sidebar-modern .list-group-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.sidebar-modern .list-group-item.active:hover {
    transform: translateX(0);
}

.sidebar-modern .badge {
    border-radius: 20px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>
