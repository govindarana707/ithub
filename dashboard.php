<?php
// ==============================
// Dashboard Controller
// ==============================

require_once 'config/config.php';
require_once 'includes/auth.php';

// Authentication check
if (!isLoggedIn()) {
    redirect('login.php');
    exit;
}

// Session safety
$userId = $_SESSION['user_id'] ?? null;
$fullName = $_SESSION['full_name'] ?? 'User';

// Role & data
$role = getUserRole();
if (!$role) {
    redirect('login.php');
    exit;
}

// Redirect to role-specific dashboards
$role = strtolower((string)$role);
if ($role === 'admin') {
    redirect('admin/dashboard.php');
}
if ($role === 'instructor') {
    redirect('instructor/dashboard.php');
}
if ($role === 'student') {
    redirect('student/dashboard.php');
}

$dashboardData = getDashboardData($userId, $role);

// Load header AFTER auth
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1>Welcome to IT HUB</h1>
            <span class="badge bg-primary"><?php echo ucfirst($role); ?></span>
        </div>
    </div>

    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 mb-3">
            <div class="list-group shadow-sm">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>

                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> Profile
                </a>

                <a href="courses.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-book me-2"></i> Courses
                </a>

                <?php if ($role === 'student'): ?>
                    <a href="my-courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
                    </a>
                <?php endif; ?>

                <?php if ($role === 'instructor'): ?>
                    <a href="instructor/courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="instructor/students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                    <a href="admin/users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2"></i> User Management
                    </a>
                    <a href="admin/courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-book-open me-2"></i> Course Management
                    </a>
                    <a href="admin/logs.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list-alt me-2"></i> Activity Logs
                    </a>
                <?php endif; ?>

                <a href="settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">

            <div class="card shadow-sm p-4 mb-4">
                <h2>Dashboard Overview</h2>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($fullName); ?>!</p>
            </div>

            <!-- ================= STUDENT DASHBOARD ================= -->
            <?php if ($role === 'student'): ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['enrolled_courses'] ?? 0; ?></h3>
                                <p>Enrolled Courses</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['quiz_attempts'] ?? 0; ?></h3>
                                <p>Quiz Attempts</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['average_score'] ?? 0; ?>%</h3>
                                <p>Average Score</p>
                            </div>
                        </div>
                    </div>
                </div>

                <h4>Recent Courses</h4>
                <div class="row">
                    <?php foreach ($dashboardData['enrolled_courses_list'] ?? [] as $course): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo (int)$course['progress_percentage']; ?>%"></div>
                                    </div>
                                    <small><?php echo round($course['progress_percentage']); ?>% completed</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ================= INSTRUCTOR DASHBOARD ================= -->
            <?php if ($role === 'instructor'): ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_courses'] ?? 0; ?></h3>
                                <p>Total Courses</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_students'] ?? 0; ?></h3>
                                <p>Total Students</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ================= ADMIN DASHBOARD ================= -->
            <?php if ($role === 'admin'): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_users'] ?? 0; ?></h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_courses'] ?? 0; ?></h3>
                                <p>Total Courses</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_enrollments'] ?? 0; ?></h3>
                                <p>Enrollments</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card text-center shadow-sm">
                            <div class="card-body">
                                <h3><?php echo $dashboardData['total_attempts'] ?? 0; ?></h3>
                                <p>Quiz Attempts</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
