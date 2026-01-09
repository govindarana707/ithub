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
$enrollmentStats = $stmt->fetch_assoc();

// Certificate statistics
$stmt = $conn->query("SELECT COUNT(*) as total, COUNT(DISTINCT student_id) as unique_recipients FROM certificates");
$certificateStats = $stmt->fetch_assoc();

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
$recentActivity = $stmt->fetch_all(MYSQLI_ASSOC);

// Popular courses
$stmt = $conn->query("
    SELECT c.title, COUNT(e.id) as enrollments
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollments DESC
    LIMIT 10
");
$popularCourses = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="reports.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-file-alt me-2"></i> Reports
                    </a>
                    <a href="logs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Reports</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="dashboard-card mb-4">
                    <h3>System Summary</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <h3><?php echo $userStats['total']; ?></h3>
                                <p>Total Users</p>
                                <small><i class="fas fa-users"></i></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <h3><?php echo $courseStats['total']; ?></h3>
                                <p>Total Courses</p>
                                <small><i class="fas fa-book"></i></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <h3><?php echo $enrollmentStats['total']; ?></h3>
                                <p>Total Enrollments</p>
                                <small><i class="fas fa-user-plus"></i></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <h3><?php echo $certificateStats['total']; ?></h3>
                                <p>Certificates Issued</p>
                                <small><i class="fas fa-certificate"></i></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Breakdown -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>User Breakdown</h5>
                            <canvas id="userChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>Course Status</h5>
                            <canvas id="courseChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Popular Courses -->
                <div class="dashboard-card mb-4">
                    <h5>Popular Courses</h5>
                    <div class="table-responsive">
                        <table class="table">
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

                <!-- Export Options -->
                <div class="dashboard-card">
                    <h5>Export Reports</h5>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-primary w-100" onclick="exportUsers()">
                                <i class="fas fa-download me-2"></i>Export Users
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-success w-100" onclick="exportCourses()">
                                <i class="fas fa-download me-2"></i>Export Courses
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-info w-100" onclick="exportEnrollments()">
                                <i class="fas fa-download me-2"></i>Export Enrollments
                            </button>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-warning w-100" onclick="exportCertificates()">
                                <i class="fas fa-download me-2"></i>Export Certificates
                            </button>
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
        // User Breakdown Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Instructors', 'Admins'],
                datasets: [{
                    data: [<?php echo $userStats['students']; ?>, <?php echo $userStats['instructors']; ?>, <?php echo $userStats['admins']; ?>],
                    backgroundColor: ['#28a745', '#007bff', '#dc3545']
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
                    backgroundColor: ['#28a745', '#ffc107']
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
    </script>
</body>
</html>
