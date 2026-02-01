<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
requireStudent();

require_once '../models/Database.php';
require_once '../models/User.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';

$user = new User();
$course = new Course();
$quiz = new Quiz();
$userId = $_SESSION['user_id'];
$userData = $user->getUserById($userId);

// Get dashboard data
$enrolledCourses = $course->getEnrolledCourses($userId);
$conn = (new Database())->getConnection();

// Calculate statistics
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
$inProgressCourses = $totalEnrolled - $completedCourses;

// Get quiz statistics
$quizStmt = $conn->prepare("
    SELECT COUNT(*) as total_quizzes, 
           AVG(score) as avg_score,
           SUM(CASE WHEN score >= 70 THEN 1 ELSE 0 END) as passed_quizzes
    FROM quiz_attempts 
    WHERE student_id = ?
");
$quizStmt->bind_param('i', $userId);
$quizStmt->execute();
$quizStats = $quizStmt->get_result()->fetch_assoc();

// Calculate learning streak
$streakStmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(last_accessed_at)) as streak_days
    FROM lesson_progress
    WHERE student_id = ? 
    AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$streakStmt->bind_param('i', $userId);
$streakStmt->execute();
$streakData = $streakStmt->get_result()->fetch_assoc();
$learningStreak = $streakData['streak_days'] ?? 0;

// Get recent activity
$activityStmt = $conn->prepare("
    SELECT 'lesson' as type, l.title as title, lp.last_accessed_at as activity_date, c.title as course_title
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE lp.student_id = ?
    UNION ALL
    SELECT 'quiz' as type, q.title as title, qa.completed_at as activity_date, c.title as course_title
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE qa.student_id = ?
    ORDER BY activity_date DESC
    LIMIT 5
");
$activityStmt->bind_param('ii', $userId, $userId);
$activityStmt->execute();
$recentActivity = $activityStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total study time (in hours)
$timeStmt = $conn->prepare("
    SELECT SUM(time_spent_minutes) as total_minutes
    FROM lesson_progress
    WHERE student_id = ?
");
$timeStmt->bind_param('i', $userId);
$timeStmt->execute();
$timeData = $timeStmt->get_result()->fetch_assoc();
$totalStudyHours = round(($timeData['total_minutes'] ?? 0) / 60, 1);

require_once '../includes/universal_header.php';
?>

<style>
    .dashboard-card {
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 20px;
    }

    .stat-card.success {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .stat-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .stat-card.info {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .progress-ring {
        width: 120px;
        height: 120px;
        margin: 0 auto;
    }

    .activity-item {
        padding: 15px;
        border-left: 4px solid #667eea;
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .activity-item:hover {
        background: #e9ecef;
        border-left-color: #764ba2;
    }

    .course-card {
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .course-card:hover {
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }

    .quick-action-btn {
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        transition: all 0.3s ease;
        background: white;
        border: 2px solid #e9ecef;
    }

    .quick-action-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: scale(1.05);
    }

    .badge-custom {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
    }

    .streak-badge {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        display: inline-block;
        font-weight: bold;
    }
</style>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-card card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome back,
                                <?php echo htmlspecialchars($userData['full_name']); ?>! 👋
                            </h2>
                            <p class="text-muted mb-0">Ready to continue your learning journey?</p>
                        </div>
                        <div class="text-end">
                            <div class="streak-badge">
                                <i class="fas fa-fire me-2"></i>
                                <?php echo $learningStreak; ?> Day Streak
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">
                            <?php echo $totalEnrolled; ?>
                        </h3>
                        <p class="mb-0 opacity-75">Total Courses</p>
                    </div>
                    <div>
                        <i class="fas fa-book fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">
                            <?php echo $completedCourses; ?>
                        </h3>
                        <p class="mb-0 opacity-75">Completed</p>
                    </div>
                    <div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">
                            <?php echo $quizStats['total_quizzes'] ?? 0; ?>
                        </h3>
                        <p class="mb-0 opacity-75">Quizzes Taken</p>
                    </div>
                    <div>
                        <i class="fas fa-brain fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">
                            <?php echo $totalStudyHours; ?>h
                        </h3>
                        <p class="mb-0 opacity-75">Study Time</p>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <!-- Continue Learning Section -->
            <div class="dashboard-card card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-play-circle me-2 text-primary"></i>Continue Learning</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($enrolledCourses)): ?>
                        <div class="row">
                            <?php foreach (array_slice($enrolledCourses, 0, 3) as $enrolledCourse): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="course-card card h-100">
                                        <?php if ($enrolledCourse['thumbnail']): ?>
                                            <img src="../<?php echo htmlspecialchars($enrolledCourse['thumbnail']); ?>"
                                                class="card-img-top" alt="Course thumbnail"
                                                style="height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="card-img-top bg-gradient"
                                                style="height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <?php echo htmlspecialchars($enrolledCourse['title']); ?>
                                            </h6>
                                            <div class="progress mb-2" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                    style="width: <?php echo $enrolledCourse['progress_percentage'] ?? 0; ?>%">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo round($enrolledCourse['progress_percentage'] ?? 0); ?>% Complete
                                            </small>
                                            <div class="mt-3">
                                                <a href="view-course.php?id=<?php echo $enrolledCourse['id']; ?>"
                                                    class="btn btn-sm btn-primary w-100">
                                                    <i class="fas fa-arrow-right me-1"></i>Continue
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't enrolled in any courses yet.</p>
                            <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-card card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-info"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <i
                                                class="fas fa-<?php echo $activity['type'] === 'lesson' ? 'book' : 'brain'; ?> me-2"></i>
                                            <?php echo htmlspecialchars($activity['title']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($activity['course_title']); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y', strtotime($activity['activity_date'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No recent activity</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Actions -->
            <div class="dashboard-card card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="courses.php" class="quick-action-btn text-decoration-none text-dark d-block">
                                <i class="fas fa-search fa-2x mb-2 text-primary"></i>
                                <p class="mb-0 small">Browse Courses</p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="my-courses.php" class="quick-action-btn text-decoration-none text-dark d-block">
                                <i class="fas fa-book fa-2x mb-2 text-success"></i>
                                <p class="mb-0 small">My Courses</p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="quizzes.php" class="quick-action-btn text-decoration-none text-dark d-block">
                                <i class="fas fa-brain fa-2x mb-2 text-info"></i>
                                <p class="mb-0 small">Take Quiz</p>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="certificates.php" class="quick-action-btn text-decoration-none text-dark d-block">
                                <i class="fas fa-certificate fa-2x mb-2 text-warning"></i>
                                <p class="mb-0 small">Certificates</p>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Insights -->
            <div class="dashboard-card card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-success"></i>Performance</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Average Quiz Score</span>
                            <strong>
                                <?php echo round($quizStats['avg_score'] ?? 0); ?>%
                            </strong>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success"
                                style="width: <?php echo round($quizStats['avg_score'] ?? 0); ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Course Completion</span>
                            <strong>
                                <?php echo $totalEnrolled > 0 ? round(($completedCourses / $totalEnrolled) * 100) : 0; ?>%
                            </strong>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary"
                                style="width: <?php echo $totalEnrolled > 0 ? round(($completedCourses / $totalEnrolled) * 100) : 0; ?>%">
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <div class="badge bg-success badge-custom">
                            <i class="fas fa-trophy me-1"></i>
                            <?php echo $quizStats['passed_quizzes'] ?? 0; ?> Quizzes Passed
                        </div>
                    </div>
                </div>
            </div>

            <!-- Learning Goals -->
            <div class="dashboard-card card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-target me-2 text-danger"></i>This Week's Goal</h5>
                </div>
                <div class="card-body text-center">
                    <div class="progress-ring mb-3">
                        <svg width="120" height="120">
                            <circle cx="60" cy="60" r="54" stroke="#e9ecef" stroke-width="8" fill="none" />
                            <circle cx="60" cy="60" r="54" stroke="#667eea" stroke-width="8" fill="none"
                                stroke-dasharray="339.292"
                                stroke-dashoffset="<?php echo 339.292 - (339.292 * min($learningStreak, 7) / 7); ?>"
                                transform="rotate(-90 60 60)" />
                            <text x="60" y="70" text-anchor="middle" font-size="24" font-weight="bold" fill="#667eea">
                                <?php echo min($learningStreak, 7); ?>/7
                            </text>
                        </svg>
                    </div>
                    <p class="mb-0">Days of Learning</p>
                    <small class="text-muted">Keep up the great work!</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>