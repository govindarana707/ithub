<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';
require_once '../models/User.php';

$instructor = new Instructor();
$course = new Course();
$user = new User();

$instructorId = $_SESSION['user_id'];

// Get comprehensive instructor data
$instructorProfile = $instructor->getInstructorProfile($instructorId);
$instructorCourses = $instructor->getInstructorCourses($instructorId, null, 5);
$analytics = $instructor->getInstructorAnalytics($instructorId, '30days');
$earnings = $instructor->getInstructorEarnings($instructorId, '30days');

// Get recent activity
$recentActivity = $instructor->getInstructorActivityLog($instructorId, 10);

// Get top performing courses
$topCourses = array_slice($analytics['course_performance'], 0, 3);

// Get quick stats
$quickStats = [
    'total_courses' => $analytics['overview']['total_courses'] ?? 0,
    'published_courses' => $analytics['overview']['published_courses'] ?? 0,
    'total_students' => $analytics['overview']['total_students'] ?? 0,
    'total_revenue' => $earnings['summary']['total_revenue'] ?? 0,
    'avg_progress' => $analytics['overview']['avg_progress'] ?? 0,
    'completion_rate' => $analytics['overview']['total_students'] > 0 ? 
        round(($analytics['overview']['completed_students'] / $analytics['overview']['total_students']) * 100, 1) : 0
];
$stmt->execute();
$draftCourses = $stmt->get_result()->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - IT HUB</title>
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
                <a class="nav-link" href="../dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i> Main Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
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
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="create-course.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i> Create Course
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i> Quizzes
                    </a>
                    <a href="discussions.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments me-2"></i> Discussions
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="../profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Instructor Dashboard</h1>
                    <div>
                        <span class="badge bg-warning">Instructor</span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card primary">
                            <h3><?php echo $totalCourses; ?></h3>
                            <p>Total Courses</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <h3><?php echo $totalStudents; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <h3><?php echo $publishedCourses; ?></h3>
                            <p>Published</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <h3><?php echo $draftCourses; ?></h3>
                            <p>Drafts</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card mb-4">
                    <h3>Quick Actions</h3>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="create-course.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-plus me-2"></i>Create New Course
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="courses.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-list me-2"></i>Manage Courses
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Courses -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>My Courses</h3>
                        <a href="courses.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    
                    <?php if (empty($instructorCourses)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                            <h5>No courses created yet</h5>
                            <p class="text-muted">Start creating your first course to share your knowledge.</p>
                            <a href="create-course.php" class="btn btn-primary">Create Course</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course Title</th>
                                        <th>Category</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($instructorCourses, 0, 5) as $course): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                            <td><?php echo htmlspecialchars($course['category_name']); ?></td>
                                            <td>
                                                <?php 
                                                $conn = connectDB();
                                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
                                                $stmt->bind_param("i", $course['id']);
                                                $stmt->execute();
                                                $studentCount = $stmt->get_result()->fetch_assoc()['count'];
                                                $conn->close();
                                                echo $studentCount;
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($course['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../admin/course_builder.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="view-course-stats.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Student Activity -->
                <div class="dashboard-card">
                    <h3>Recent Student Activity</h3>
                    <?php
                    $conn = connectDB();
                    $stmt = $conn->prepare("
                        SELECT u.full_name, c.title as course_title, e.enrolled_at, e.progress_percentage
                        FROM enrollments e
                        JOIN users u ON e.student_id = u.id
                        JOIN courses c ON e.course_id = c.id
                        WHERE c.instructor_id = ?
                        ORDER BY e.enrolled_at DESC
                        LIMIT 5
                    ");
                    $stmt->bind_param("i", $instructorId);
                    $stmt->execute();
                    $recentActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $conn->close();
                    ?>
                    
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted">No recent student activity.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                            enrolled in <em><?php echo htmlspecialchars($activity['course_title']); ?></em>
                                            <?php if ($activity['progress_percentage'] > 0): ?>
                                                <span class="badge bg-info ms-2"><?php echo round($activity['progress_percentage']); ?>% complete</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($activity['enrolled_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
