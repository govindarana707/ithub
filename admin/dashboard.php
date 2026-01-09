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

// Get recent activities
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT al.action, al.details, al.created_at, u.full_name, u.email
    FROM admin_logs al
    JOIN users u ON u.id = al.user_id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get system overview data
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$studentCount = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'instructor'");
$instructorCount = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM enrollments");
$enrollmentCount = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM quiz_attempts");
$quizAttempts = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM courses WHERE status = 'published'");
$publishedCourses = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM certificates");
$certificatesCount = $stmt->fetch_assoc()['total'];

// Get recent users
$stmt = $conn->prepare("SELECT id, full_name, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent courses
$stmt = $conn->prepare("
    SELECT c.id, c.title, c.status, u.full_name as instructor_name, c.created_at, COALESCE(cat.name, 'N/A') as category_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IT HUB</title>
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
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
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Admin Dashboard</h1>
                    <div>
                        <span class="badge bg-danger">Administrator</span>
                    </div>
                </div>

                <!-- System Overview -->
                <div class="dashboard-card mb-4">
                    <h3>System Overview</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <h3><?php echo $studentCount; ?></h3>
                                <p>Total Students</p>
                                <small><i class="fas fa-user-graduate"></i> Active Learners</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <h3><?php echo $instructorCount; ?></h3>
                                <p>Instructors</p>
                                <small><i class="fas fa-chalkboard-teacher"></i> Course Creators</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <h3><?php echo $publishedCourses; ?></h3>
                                <p>Published Courses</p>
                                <small><i class="fas fa-book"></i> Available Content</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <h3><?php echo $certificatesCount; ?></h3>
                                <p>Certificates</p>
                                <small><i class="fas fa-certificate"></i> Achievements</small>
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Recent Users</h3>
                        <a href="users.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
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
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : ($user['status'] === 'blocked' ? 'danger' : 'warning'); ?>">
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

                <!-- Recent Courses -->
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Recent Courses</h3>
                        <a href="courses.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
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
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
