<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';

requireAdmin();

$user = new User();
$course = new Course();

// Get report data
$db = new Database();
$conn = $db->getConnection();

// User statistics
$userStats = $user->getUserStats();

// Course statistics
$courseStats = $course->getCourseStats();

// Enrollment statistics
$stmt = $conn->query("SELECT COUNT(*) as total, COUNT(DISTINCT student_id) as unique_students FROM enrollments");
$enrollmentStats = $stmt ? $stmt->fetch_assoc() : ['total' => 0, 'unique_students' => 0];

// Certificate statistics
$stmt = $conn->query("SELECT COUNT(*) as total, COUNT(DISTINCT student_id) as unique_recipients FROM certificates");
$certificateStats = $stmt ? $stmt->fetch_assoc() : ['total' => 0, 'unique_recipients' => 0];

// Recent activity
$stmt = $conn->query("
    SELECT 'login' as type, COUNT(*) as count, DATE(created_at) as date 
    FROM admin_logs 
    WHERE action = 'login' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 7
");
$recentActivity = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];

// Popular courses
$stmt = $conn->query("
    SELECT c.title, COUNT(e.id) as enrollments
    FROM courses_new c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollments DESC
    LIMIT 10
");
$popularCourses = $stmt ? $stmt->fetch_all(MYSQLI_ASSOC) : [];

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
                        <h2 class="mb-1">📊 Reports</h2>
                        <p class="mb-0 opacity-75">System overview and statistics</p>
                    </div>
                    <div>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-chart-pie me-2"></i>
                    System Summary
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="admin-stat-card primary">
                                <div class="admin-stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="admin-stat-value"><?php echo $userStats['total']; ?></div>
                                <div class="admin-stat-label">Total Users</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="admin-stat-card success">
                                <div class="admin-stat-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="admin-stat-value"><?php echo $courseStats['total']; ?></div>
                                <div class="admin-stat-label">Total Courses</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="admin-stat-card info">
                                <div class="admin-stat-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="admin-stat-value"><?php echo $enrollmentStats['total']; ?></div>
                                <div class="admin-stat-label">Total Enrollments</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="admin-stat-card warning">
                                <div class="admin-stat-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="admin-stat-value"><?php echo $certificateStats['total']; ?></div>
                                <div class="admin-stat-label">Certificates Issued</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Breakdown -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <i class="fas fa-users me-2"></i>
                            User Breakdown
                        </div>
                        <div class="card-body">
                            <canvas id="userChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="admin-content-card">
                        <div class="admin-card-header">
                            <i class="fas fa-book me-2"></i>
                            Course Status
                        </div>
                        <div class="card-body">
                            <canvas id="courseChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Courses -->
            <div class="admin-content-card mb-4">
                <div class="admin-card-header">
                    <i class="fas fa-star me-2"></i>
                    Popular Courses
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-modern-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Enrollments</th>
                                    <th>Popularity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popularCourses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo $course['enrollments']; ?></td>
                                        <td>
                                            <div class="progress" style="width: 100px; height: 8px;">
                                                <div class="progress-bar" style="width: <?php echo min(100, ($course['enrollments'] / 10) * 100); ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Export Options -->
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <i class="fas fa-download me-2"></i>
                    Export Reports
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn-modern btn-primary-modern w-100" onclick="exportUsers()">
                                <i class="fas fa-download me-2"></i>Export Users
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn-modern btn-success-modern w-100" onclick="exportCourses()">
                                <i class="fas fa-download me-2"></i>Export Courses
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn-modern btn-info-modern w-100" onclick="exportEnrollments()">
                                <i class="fas fa-download me-2"></i>Export Enrollments
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn-modern btn-warning-modern w-100" onclick="exportCertificates()">
                                <i class="fas fa-download me-2"></i>Export Certificates
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // User Breakdown Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Instructors', 'Admins'],
            datasets: [{
                data: [<?php echo $userStats['students']; ?>, <?php echo $userStats['instructors']; ?>, <?php echo $userStats['admins']; ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#4169E1']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Course Status Chart
    const courseCtx = document.getElementById('courseChart').getContext('2d');
    new Chart(courseCtx, {
        type: 'pie',
        data: {
            labels: ['Published', 'Draft'],
            datasets: [{
                data: [<?php echo $courseStats['published']; ?>, <?php echo $courseStats['draft']; ?>],
                backgroundColor: ['#10b981', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    function exportUsers() {
        window.location.href = 'api/export_users.php';
    }

    function exportCourses() {
        window.location.href = 'api/export_courses.php';
    }

    function exportEnrollments() {
        window.location.href = 'api/export_enrollments.php';
    }

    function exportCertificates() {
        window.location.href = 'api/export_certificates.php';
    }

    // Add animations on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stat cards
        const statCards = document.querySelectorAll('.admin-stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Animate content cards
        const contentCards = document.querySelectorAll('.admin-content-card');
        contentCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 400 + (index * 200));
        });
    });
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
