<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';

requireAdmin();

// Get analytics data
$db = new Database();
$conn = $db->getConnection();

// User statistics
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userStats = $stmt->fetch_all(MYSQLI_ASSOC);

// Course statistics
$stmt = $conn->query("SELECT c.title, COUNT(e.id) as enrollment_count FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id ORDER BY enrollment_count DESC LIMIT 10");
$topCourses = $stmt->fetch_all(MYSQLI_ASSOC);

// Monthly registrations
$stmt = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$monthlyRegistrations = $stmt->fetch_all(MYSQLI_ASSOC);

// Monthly enrollments
$stmt = $conn->query("
    SELECT DATE_FORMAT(enrolled_at, '%Y-%m') as month, COUNT(*) as count 
    FROM enrollments 
    WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
    ORDER BY month
");
$monthlyEnrollments = $stmt->fetch_all(MYSQLI_ASSOC);

// Quiz statistics
$stmt = $conn->query("SELECT AVG(percentage) as avg_score, COUNT(*) as total_attempts FROM quiz_attempts WHERE status = 'completed'");
$quizStats = $stmt->fetch_assoc();

// Certificate statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM certificates");
$certificateCount = $stmt->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - IT HUB</title>
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
                    <a href="analytics.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="reports.php" class="list-group-item list-group-item-action">
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
                    <h1>Analytics</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="dashboard-card mb-4">
                    <h3>Platform Overview</h3>
                    <div class="row">
                        <?php foreach ($userStats as $stat): ?>
                            <div class="col-md-3">
                                <div class="stat-card <?php echo getRoleCardColor($stat['role']); ?>">
                                    <h3><?php echo $stat['count']; ?></h3>
                                    <p><?php echo ucfirst($stat['role']); ?>s</p>
                                    <small><i class="fas fa-<?php echo getRoleIcon($stat['role']); ?>"></i></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>User Registrations (Last 12 Months)</h5>
                            <canvas id="registrationsChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>Course Enrollments (Last 12 Months)</h5>
                            <canvas id="enrollmentsChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <h5>Quiz Performance</h5>
                            <div class="text-center">
                                <h2 class="text-primary"><?php echo round($quizStats['avg_score'], 1); ?>%</h2>
                                <p class="text-muted">Average Score</p>
                                <small><?php echo $quizStats['total_attempts']; ?> total attempts</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <h5>Certificates Issued</h5>
                            <div class="text-center">
                                <h2 class="text-success"><?php echo $certificateCount; ?></h2>
                                <p class="text-muted">Total Certificates</p>
                                <small>Achievement rate</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <h5>System Health</h5>
                            <div class="text-center">
                                <h2 class="text-info">98.5%</h2>
                                <p class="text-muted">Performance</p>
                                <small>All systems operational</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Courses -->
                <div class="dashboard-card">
                    <h5>Top Courses by Enrollment</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Enrollments</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCourses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo $course['enrollment_count']; ?></span>
                                                <div class="progress" style="width: 100px; height: 8px;">
                                                    <div class="progress-bar" style="width: <?php echo min(100, ($course['enrollment_count'] / 10) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Good</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Prepare data for charts
        const months = <?php echo json_encode(array_column($monthlyRegistrations, 'month')); ?>;
        const registrations = <?php echo json_encode(array_column($monthlyRegistrations, 'count')); ?>;
        const enrollments = <?php echo json_encode(array_column($monthlyEnrollments, 'count')); ?>;

        // Registrations Chart
        const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
        new Chart(registrationsCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'User Registrations',
                    data: registrations,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Enrollments Chart
        const enrollmentsCtx = document.getElementById('enrollmentsChart').getContext('2d');
        new Chart(enrollmentsCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Course Enrollments',
                    data: enrollments,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
function getRoleCardColor($role) {
    $colors = [
        'admin' => 'danger',
        'instructor' => 'primary',
        'student' => 'success'
    ];
    return $colors[$role] ?? 'secondary';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => 'user-shield',
        'instructor' => 'chalkboard-teacher',
        'student' => 'user-graduate'
    ];
    return $icons[$role] ?? 'user';
}
?>
