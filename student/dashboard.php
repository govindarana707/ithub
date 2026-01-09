<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireStudent();

require_once '../models/User.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';
require_once '../models/Discussion.php';

$user = new User();
$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();

$userId = $_SESSION['user_id'];

// Get dashboard data
$dashboardData = getStudentDashboardData($userId);

// Include header after all PHP logic
require_once '../includes/universal_header.php';

/**
 * Get comprehensive student dashboard data
 */
function getStudentDashboardData($userId) {
    $conn = connectDB();
    
    // Get enrolled courses with progress
    $enrolledCourses = getEnrolledCoursesWithProgress($conn, $userId);
    
    // Get quiz statistics
    $quizStats = getQuizStatistics($conn, $userId);
    
    // Get recent activity
    $recentActivity = getRecentActivity($conn, $userId);
    
    // Get learning streak
    $learningStreak = getLearningStreak($conn, $userId);
    
    // Get achievements
    $achievements = getStudentAchievements($conn, $userId);
    
    // Get recommended courses
    $recommendedCourses = getRecommendedCourses($conn, $userId, 4);
    
    $conn->close();
    
    return [
        'enrolledCourses' => $enrolledCourses,
        'quizStats' => $quizStats,
        'recentActivity' => $recentActivity,
        'learningStreak' => $learningStreak,
        'achievements' => $achievements,
        'recommendedCourses' => $recommendedCourses
    ];
}

/**
 * Get enrolled courses with progress information
 */
function getEnrolledCoursesWithProgress($conn, $userId) {
    $sql = "
        SELECT c.*, e.enrolled_at, e.progress_percentage, e.status,
               cat.name as category_name,
               (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) as total_lessons,
               (SELECT COUNT(*) FROM lesson_progress lp 
                JOIN lessons l ON lp.lesson_id = l.id 
                WHERE l.course_id = c.id AND lp.student_id = ? AND lp.completed = 1) as completed_lessons
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        // Fallback query if complex query fails
        $stmt = $conn->prepare("
            SELECT c.*, e.enrolled_at, e.progress_percentage, e.status,
                   'General' as category_name,
                   0 as total_lessons,
                   0 as completed_lessons
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            WHERE e.student_id = ?
            ORDER BY e.enrolled_at DESC
        ");
        if ($stmt === false) {
            return []; // Return empty array if prepare fails
        }
        $stmt->bind_param("i", $userId);
    } else {
        $stmt->bind_param("ii", $userId, $userId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get comprehensive quiz statistics
 */
function getQuizStatistics($conn, $userId) {
    // Basic stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            AVG(percentage) as avg_score,
            MAX(percentage) as best_score,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_quizzes,
            SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed_quizzes
        FROM quiz_attempts 
        WHERE student_id = ? AND status = 'completed'
    ");
    if ($stmt === false) {
        return [
            'total_attempts' => 0,
            'avg_score' => 0,
            'best_score' => 0,
            'passed_quizzes' => 0,
            'failed_quizzes' => 0,
            'recent_quizzes' => []
        ];
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Recent quiz attempts
    $stmt = $conn->prepare("
        SELECT qa.*, q.title as quiz_title, c.title as course_title
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN courses c ON q.course_id = c.id
        WHERE qa.student_id = ? AND qa.status = 'completed'
        ORDER BY qa.completed_at DESC
        LIMIT 5
    ");
    if ($stmt === false) {
        $recentQuizzes = [];
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $recentQuizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return [
        'total_attempts' => (int)($stats['total_attempts'] ?? 0),
        'avg_score' => round($stats['avg_score'] ?? 0, 1),
        'best_score' => round($stats['best_score'] ?? 0, 1),
        'passed_quizzes' => (int)($stats['passed_quizzes'] ?? 0),
        'failed_quizzes' => (int)($stats['failed_quizzes'] ?? 0),
        'recent_quizzes' => $recentQuizzes
    ];
}

/**
 * Get recent student activity
 */
function getRecentActivity($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            'enrollment' as type, 
            c.title as description, 
            e.enrolled_at as created_at,
            c.id as related_id
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = ?
        UNION ALL
        SELECT 
            'quiz_attempt' as type, 
            q.title as description, 
            qa.completed_at as created_at,
            qa.id as related_id
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.student_id = ? AND qa.status = 'completed'
        UNION ALL
        SELECT 
            'lesson_completed' as type, 
            CONCAT(l.title, ' - ', c.title) as description, 
            lp.completed_at as created_at,
            l.id as related_id
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        JOIN courses c ON l.course_id = c.id
        WHERE lp.student_id = ? AND lp.completed = 1
        ORDER BY created_at DESC
        LIMIT 8
    ");
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param("iii", $userId, $userId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Calculate learning streak
 */
function getLearningStreak($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
        FROM lesson_progress 
        WHERE student_id = ? AND completed = 1
        AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['streak_days'] ?? 0);
}

/**
 * Get student achievements
 */
function getStudentAchievements($conn, $userId) {
    $achievements = [];
    
    // Quiz master achievement
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE student_id = ? AND passed = 1");
    if ($stmt !== false) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $passedQuizzes = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($passedQuizzes >= 10) {
            $achievements[] = ['icon' => 'trophy', 'title' => 'Quiz Master', 'description' => 'Passed 10 quizzes', 'color' => 'gold'];
        } elseif ($passedQuizzes >= 5) {
            $achievements[] = ['icon' => 'medal', 'title' => 'Quiz Expert', 'description' => 'Passed 5 quizzes', 'color' => 'silver'];
        }
    }
    
    // Course completion achievement
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'completed'");
    if ($stmt !== false) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $completedCourses = $stmt->get_result()->fetch_assoc()['count'];
        
        if ($completedCourses >= 3) {
            $achievements[] = ['icon' => 'graduation-cap', 'title' => 'Dedicated Learner', 'description' => 'Completed 3 courses', 'color' => 'blue'];
        }
    }
    
    return $achievements;
}

/**
 * Get recommended courses
 */
function getRecommendedCourses($conn, $userId, $limit = 4) {
    $stmt = $conn->prepare("
        SELECT c.*, u.name as instructor_name,
               (SELECT AVG(rating) FROM course_ratings WHERE course_id = c.id) as avg_rating,
               (SELECT COUNT(*) FROM course_ratings WHERE course_id = c.id) as rating_count
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        WHERE c.id NOT IN (SELECT course_id FROM enrollments WHERE student_id = ?)
        AND c.status = 'published'
        ORDER BY c.enrollment_count DESC, avg_rating DESC
        LIMIT ?
    ");
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Extract data for easier access with safe defaults
$enrolledCourses = $dashboardData['enrolledCourses'] ?? [];
$quizStats = $dashboardData['quizStats'] ?? ['total_attempts' => 0, 'avg_score' => 0, 'best_score' => 0, 'passed_quizzes' => 0, 'failed_quizzes' => 0, 'recent_quizzes' => []];
$recentActivity = $dashboardData['recentActivity'] ?? [];
$learningStreak = $dashboardData['learningStreak'] ?? 0;
$achievements = $dashboardData['achievements'] ?? [];
$recommendedCourses = $dashboardData['recommendedCourses'] ?? [];

// Calculate total enrolled courses
$totalEnrolled = count($enrolledCourses);

// Calculate additional metrics
$completedCourses = count(array_filter($enrolledCourses, fn($c) => $c['status'] === 'completed'));
$inProgressCourses = $totalEnrolled - $completedCourses;?>

<!-- Main Content -->
<div class="container-fluid py-4">
    <div class="row">
        <!-- Enhanced Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="my-courses.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-graduation-cap me-2"></i> My Courses
                    <span class="badge bg-primary float-end"><?php echo $totalEnrolled; ?></span>
                </a>
                <a href="quizzes.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-brain me-2"></i> Quizzes
                    <span class="badge bg-info float-end"><?php echo $quizStats['total_attempts']; ?></span>
                </a>
                <a href="quiz-results.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Quiz Results
                </a>
                <a href="discussions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-comments me-2"></i> Discussions
                </a>
                <a href="certificates.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-certificate me-2"></i> Certificates
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
                <a href="../logout.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Student Dashboard</h1>
                    <div>
                        <span class="badge bg-info">Student</span>
                    </div>
                </div>

                <!-- Learning Overview -->
                <div class="dashboard-card mb-4">
                    <h3>Learning Overview</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <h3><?php echo $totalEnrolled; ?></h3>
                                <p>Enrolled Courses</p>
                                <small><i class="fas fa-book-open"></i> Active Learning</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <h3><?php echo $completedCourses; ?></h3>
                                <p>Completed</p>
                                <small><i class="fas fa-check-circle"></i> Achievements</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <h3><?php echo $quizStats['total_attempts']; ?></h3>
                                <p>Quiz Attempts</p>
                                <small><i class="fas fa-brain"></i> Knowledge Tests</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <h3><?php echo $learningStreak; ?></h3>
                                <p>Day Streak</p>
                                <small><i class="fas fa-fire"></i> Consistency</small>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Enhanced Quiz Performance Section -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><i class="fas fa-brain me-2"></i>Quiz Performance</h3>
                            <div>
                                <a href="quizzes.php" class="btn btn-primary btn-sm me-2">
                                    <i class="fas fa-play me-1"></i>Take Quiz
                                </a>
                                <a href="quiz-results.php" class="btn btn-outline-primary btn-sm">View All Results</a>
                            </div>
                        </div>
                        
                        <!-- Recent Quiz Activity -->
                        <div class="mt-4">
                            <h5><i class="fas fa-clock-rotate-left me-2"></i>Recent Quiz Activity</h5>
                            <?php if (empty($quizStats['recent_quizzes'])): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                                    <h5>No quiz attempts yet</h5>
                                    <p class="text-muted">Start taking quizzes to see your performance!</p>
                                    <a href="quizzes.php" class="btn btn-primary">Take Your First Quiz</a>
                                </div>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($quizStats['recent_quizzes'] as $quiz): ?>
                                        <div class="quiz-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($quiz['course_title']); ?>
                                                    </p>
                                                    <div class="d-flex align-items-center flex-wrap">
                                                        <span class="badge bg-<?php echo $quiz['passed'] ? 'success' : 'danger'; ?> me-2">
                                                            <i class="fas fa-<?php echo $quiz['passed'] ? 'check' : 'times'; ?> me-1"></i>
                                                            <?php echo $quiz['passed'] ? 'Passed' : 'Failed'; ?>
                                                        </span>
                                                        <span class="badge bg-info me-2">
                                                            <?php echo round($quiz['percentage']); ?>%
                                                        </span>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($quiz['completed_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div>
                                                    <a href="quiz-result.php?attempt_id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Enhanced Enrolled Courses Section -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><i class="fas fa-graduation-cap me-2"></i>My Enrolled Courses</h3>
                            <a href="my-courses.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        
                        <?php if (empty($enrolledCourses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5>No courses enrolled yet</h5>
                                <p class="text-muted">Start your learning journey by enrolling in our courses.</p>
                                <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card course-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                    <span class="badge bg-<?php echo $course['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </div>
                                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></p>
                                                
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">Progress</small>
                                                        <small class="text-muted"><?php echo round($course['progress_percentage']); ?>%</small>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo $course['completed_lessons'] ?? 0; ?>/<?php echo $course['total_lessons'] ?? 0; ?> lessons
                                                    </small>
                                                </div>
                                                
                                                <div class="mt-2">
                                                    <a href="view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-play me-1"></i><?php echo $course['status'] === 'completed' ? 'Review' : 'Continue'; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Courses -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Recent Courses</h3>
                            <a href="my-courses.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course Title</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($enrolledCourses)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                                                <p>No courses enrolled yet</p>
                                                <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach (array_slice($enrolledCourses, 0, 5) as $course): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Category: <?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></small>
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%">
                                                            <?php echo round($course['progress_percentage'] ?? 0); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $course['status'] === 'completed' ? 'success' : ($course['status'] === 'in_progress' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($course['status'] ?? 'unknown'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="course-view.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="course-lessons.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-success">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Enhanced Recommended Courses Section -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><i class="fas fa-star me-2"></i>Recommended Courses</h3>
                            <a href="../courses.php" class="btn btn-outline-primary btn-sm">Browse All</a>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($recommendedCourses as $course): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card course-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <?php if ($course['avg_rating']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <small class="text-warning me-1">
                                                            <i class="fas fa-star"></i> <?php echo round($course['avg_rating'], 1); ?>
                                                        </small>
                                                        <small class="text-muted">(<?php echo $course['rating_count']; ?>)</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <p class="card-text small text-muted mb-2"><?php echo substr(htmlspecialchars($course['description']), 0, 80); ?>...</p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i><?php echo $course['enrollment_count']; ?> enrolled
                                                </small>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                <a href="../course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Enhanced Recent Activity Section -->
                    <div class="dashboard-card">
                        <h3><i class="fas fa-clock-rotate-left me-2"></i>Recent Activity</h3>
                        <?php if (empty($recentActivity)): ?>
                            <p class="text-muted text-center py-3">No recent activity. Start learning to see your progress!</p>
                        <?php else: ?>
                            <div>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $icon = 'circle';
                                                    $color = 'primary';
                                                    switch($activity['type']) {
                                                        case 'enrollment':
                                                            $icon = 'graduation-cap';
                                                            $color = 'success';
                                                            break;
                                                        case 'quiz_attempt':
                                                            $icon = 'brain';
                                                            $color = 'info';
                                                            break;
                                                        case 'lesson_completed':
                                                            $icon = 'check-circle';
                                                            $color = 'success';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="fas fa-<?php echo $icon; ?> text-<?php echo $color; ?> me-2"></i>
                                                    <span><?php echo htmlspecialchars($activity['description']); ?></span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo getTimeAgo($activity['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-btn" onclick="window.location.href='quizzes.php'" title="Take a Quiz">
        <i class="fas fa-brain"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add interactive animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            
            document.querySelectorAll('.stat-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
            
            // Add hover effects to cards
            document.querySelectorAll('.course-card, .quiz-item').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
        
        // Helper function for time ago (if not defined in main.js)
        function getTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
            return date.toLocaleDateString();
        }
    </script>

<?php
/**
 * Helper function to get time ago string
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}
?>
