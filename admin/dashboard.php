<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';

$user = new User();
$course = new Course();

// Get statistics
$stats = [
    'users' => $user->getUserStats(),
    'courses' => $course->getCourseStats()
];

// Get system overview data
$conn = connectDB();

// Get all stats in optimized queries
$stmt = $conn->query("SELECT COUNT(*) as total FROM users_new WHERE role = 'student'");
$studentCount = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM users_new WHERE role = 'instructor'");
$instructorCount = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM enrollments_new");
$enrollmentCount = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM quiz_attempts");
$quizAttempts = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM courses_new WHERE status = 'published'");
$publishedCourses = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM courses_new WHERE status = 'pending'");
$pendingCourses = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM certificates");
$certificatesCount = $stmt && ($row = $stmt->fetch_assoc()) ? $row['total'] : 0;

// Get recent activities
$stmt = $conn->prepare("
    SELECT al.action, al.details, al.created_at, u.full_name, u.email
    FROM admin_logs al
    JOIN users_new u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent users
$stmt = $conn->prepare("SELECT id, full_name, email, role, status, created_at FROM users_new ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent courses
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.status, u.full_name as instructor_name, c.created_at, COALESCE(cat.name, 'N/A') as category_name
    FROM courses_new c
    JOIN users_new u ON c.instructor_id = u.id
    LEFT JOIN categories_new cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Get admin info for welcome message
$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Quick stats for header
$quickStats = [
    'total_users' => $studentCount + $instructorCount,
    'total_courses' => $publishedCourses,
    'total_enrollments' => $enrollmentCount,
    'pending_approvals' => $pendingCourses
];

// Load universal header
require_once dirname(__DIR__) . '/includes/universal_header.php';
?>

<style>
    /* Modern Admin Dashboard Styles */
    .admin-dashboard {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .dashboard-header {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }

    .header-left {
        flex: 1;
    }

    .header-title {
        font-size: 2.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 0.5rem;
    }

    .header-subtitle {
        color: #64748b;
        font-size: 1.1rem;
        margin: 0;
    }

    .header-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .btn-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: white;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-icon:hover {
        background: #f8fafc;
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-2px);
    }

    /* Quick Stats Bar */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quick-stat {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }

    .quick-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #667eea;
    }

    .quick-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .quick-stat-icon.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .quick-stat-icon.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .quick-stat-icon.info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .quick-stat-icon.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .quick-stat-content {
        flex: 1;
    }

    .quick-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
        line-height: 1;
    }

    .quick-stat-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
    }

    /* Overview Stats Grid */
    .overview-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .overview-stat-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .overview-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .overview-stat-card.primary::before {
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .overview-stat-card.success::before {
        background: linear-gradient(90deg, #10b981, #059669);
    }

    .overview-stat-card.info::before {
        background: linear-gradient(90deg, #3b82f6, #2563eb);
    }

    .overview-stat-card.warning::before {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }

    .overview-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }

    .stat-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
        margin: 0 auto 1.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .stat-icon::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
        border-radius: 50%;
    }

    .stat-icon.primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-icon.info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .stat-icon.warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.5rem;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Content Cards */
    .admin-content-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .admin-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .admin-card-header h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }

    .admin-card-header h3 i {
        color: #667eea;
    }

    /* Modern Table */
    .admin-table {
        margin: 0;
    }

    .admin-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem 1.5rem;
        border: none;
    }

    .admin-table tbody td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #e5e7eb;
    }

    .admin-table tbody tr:hover {
        background: #f8fafc;
    }

    .admin-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Avatar Placeholder */
    .avatar-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
    }

    /* Badges */
    .badge {
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    }

    .badge.bg-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    }

    .badge.bg-info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    }

    .badge.bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    /* Buttons */
    .admin-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .admin-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .admin-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 1rem;
        }

        .header-title {
            font-size: 1.75rem;
        }

        .header-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .quick-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .overview-stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .dashboard-header {
            padding: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .overview-stats-grid {
            grid-template-columns: 1fr;
        }

        .quick-stats {
            grid-template-columns: 1fr;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .overview-stat-card {
        animation: fadeInUp 0.6s ease-out;
    }

    .overview-stat-card:nth-child(1) { animation-delay: 0.1s; }
    .overview-stat-card:nth-child(2) { animation-delay: 0.2s; }
    .overview-stat-card:nth-child(3) { animation-delay: 0.3s; }
    .overview-stat-card:nth-child(4) { animation-delay: 0.4s; }

    .admin-content-card {
        animation: fadeInUp 0.6s ease-out;
        animation-delay: 0.5s;
        animation-fill-mode: both;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>

            <div class="col-md-9">
                <!-- Modern Dashboard Header -->
                <div class="dashboard-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1 class="header-title">Admin Dashboard</h1>
                            <p class="header-subtitle">Welcome back, <?php echo htmlspecialchars($adminName); ?>! Here's your system overview.</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-primary" onclick="window.location.href='users.php'">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </button>
                            <button class="btn-icon" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Stats Bar -->
                    <div class="quick-stats">
                        <div class="quick-stat">
                            <div class="quick-stat-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="quick-stat-content">
                                <div class="quick-stat-value"><?php echo $quickStats['total_users']; ?></div>
                                <div class="quick-stat-label">Total Users</div>
                            </div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-icon success">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="quick-stat-content">
                                <div class="quick-stat-value"><?php echo $quickStats['total_courses']; ?></div>
                                <div class="quick-stat-label">Published Courses</div>
                            </div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-icon info">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="quick-stat-content">
                                <div class="quick-stat-value"><?php echo $quickStats['total_enrollments']; ?></div>
                                <div class="quick-stat-label">Total Enrollments</div>
                            </div>
                        </div>
                        <div class="quick-stat">
                            <div class="quick-stat-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="quick-stat-content">
                                <div class="quick-stat-value"><?php echo $quickStats['pending_approvals']; ?></div>
                                <div class="quick-stat-label">Pending Approvals</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview Stats Cards -->
                <div class="overview-stats-grid">
                    <div class="overview-stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-value"><?php echo $studentCount; ?></div>
                        <div class="stat-label">Total Students</div>
                        <small class="text-muted mt-2 d-block">Active Learners</small>
                    </div>
                    <div class="overview-stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-value"><?php echo $instructorCount; ?></div>
                        <div class="stat-label">Total Instructors</div>
                        <small class="text-muted mt-2 d-block">Content Creators</small>
                    </div>
                    <div class="overview-stat-card info">
                        <div class="stat-icon info">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-value"><?php echo $publishedCourses; ?></div>
                        <div class="stat-label">Published Courses</div>
                        <small class="text-muted mt-2 d-block">Live Content</small>
                    </div>
                    <div class="overview-stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="stat-value"><?php echo $certificatesCount; ?></div>
                        <div class="stat-label">Certificates Issued</div>
                        <small class="text-muted mt-2 d-block">Achievements</small>
                    </div>
                </div>

                <!-- Recent Users Card -->
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-users me-2"></i>Recent Users</h3>
                            <a href="users.php" class="admin-btn admin-btn-primary">
                                <i class="fas fa-arrow-right"></i>
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-placeholder">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'blocked' ? 'primary' : 'warning'); ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Courses Card -->
                <div class="admin-content-card">
                    <div class="admin-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3><i class="fas fa-book me-2"></i>Recent Courses</h3>
                            <a href="courses.php" class="admin-btn admin-btn-primary">
                                <i class="fas fa-arrow-right"></i>
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Category</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCourses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-book text-primary" style="color: #667eea !important;"></i>
                                                <span><?php echo htmlspecialchars($course['title']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($course['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function refreshDashboard() {
            location.reload();
        }

        // Add hover effects to stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.overview-stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
<?php
// End of admin dashboard - universal_header.php will close body and html
