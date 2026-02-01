<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireStudent();

require_once '../models/Database.php';
require_once '../models/User.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';
require_once '../models/Discussion.php';
require_once '../models/Progress.php';
require_once '../models/RecommendationSystem.php';

/**
 * Get comprehensive student dashboard data
 */
function getStudentDashboardData($userId) {
    // Use Database class instead of connectDB()
    $database = new Database();
    $conn = $database->getConnection();
    
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
    
    $database->close();
    
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
        JOIN courses_new c ON e.course_id = c.id
        LEFT JOIN categories_new cat ON c.category_id = cat.id
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
            JOIN courses_new c ON e.course_id = c.id
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
        JOIN courses_new c ON q.course_id = c.id
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
        JOIN courses_new c ON e.course_id = c.id
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
        JOIN courses_new c ON l.course_id = c.id
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
 * Get recommended courses using the new recommendation system
 */
function getRecommendedCourses($conn, $userId, $limit = 4) {
    // Use the new recommendation system
    $recommendationSystem = new RecommendationSystem();
    
    // Get KNN recommendations
    $recommendations = $recommendationSystem->getKNNRecommendations($userId, $limit);
    
    // If no recommendations available, fallback to basic method
    if (empty($recommendations)) {
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name as instructor_name,
                   (SELECT AVG(rating) FROM course_ratings WHERE course_id = c.id) as avg_rating,
                   (SELECT COUNT(*) FROM course_ratings WHERE course_id = c.id) as rating_count
            FROM courses_new c
            JOIN users_new u ON c.instructor_id = u.id
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
        $recommendations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $recommendations;
}

// Initialize objects after function definitions
$user = new User();
$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();
$progress = new Progress();
$recommendationSystem = new RecommendationSystem();

$userId = $_SESSION['user_id'];

// Get dashboard data
$dashboardData = getStudentDashboardData($userId);

// Include header after all PHP logic
require_once '../includes/universal_header.php';
?>

<!-- Add enhanced dashboard styles and scripts -->
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/dashboard-advanced.css">
<link rel="stylesheet" href="../assets/css/progress-tracking.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/js/all.min.js"></script>

<!-- Add user ID meta tag for JavaScript -->
<meta name="user-id" content="<?php echo $userId; ?>">

<?php
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
$inProgressCourses = $totalEnrolled - $completedCourses;

// Get enhanced progress data for enrolled courses
$enhancedProgress = [];
foreach (array_slice($enrolledCourses, 0, 3) as $course) {
    try {
        $progressData = $progress->getCourseProgress($userId, $course['id']);
        $enhancedProgress[$course['id']] = $progressData;
    } catch (Exception $e) {
        // Fallback if progress tracking fails
        $enhancedProgress[$course['id']] = [
            'completion_percentage' => $course['progress_percentage'] ?? 0,
            'completion_probability' => 0.7,
            'estimated_completion_time' => -1,
            'alerts' => []
        ];
    }
}
?>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Advanced Dashboard Container -->
<div class="dashboard-advanced">
    <div class="row g-0">
        <!-- Enhanced Sidebar -->
        <div class="col-md-3 col-lg-2 p-0">
            <div class="sidebar-advanced">
                <div class="sidebar-header">
                    <h4><i class="fas fa-graduation-cap me-2"></i>IT HUB</h4>
                    <small>Student Portal</small>
                </div>
                
                <div class="sidebar-nav">
                    <a href="dashboard.php" class="sidebar-item active">
                        <span class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></span>
                        <span>Dashboard</span>
                    </a>
                    <a href="my-courses.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-book"></i></span>
                        <span>My Courses</span>
                    </a>
                    <a href="quizzes.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-brain"></i></span>
                        <span>Quizzes</span>
                    </a>
                    <a href="certificates.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-certificate"></i></span>
                        <span>Certificates</span>
                    </a>
                    <a href="discussions.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-comments"></i></span>
                        <span>Discussions</span>
                    </a>
                    <a href="profile.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-user"></i></span>
                        <span>Profile</span>
                    </a>
                    <a href="../logout.php" class="sidebar-item">
                        <span class="sidebar-icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content-advanced">
                <!-- Advanced Header -->
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="header-title">Student Dashboard</h1>
                            <p class="header-subtitle">Welcome back! Track your learning progress and discover new courses.</p>
                        </div>
                        <div class="header-actions">
                            <button class="btn btn-primary refresh-recommendations" data-original-text="<i class='fas fa-sync-alt'></i>">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <span class="badge bg-info">Student</span>
                        </div>
                    </div>
                </div>

                <!-- Advanced Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card-advanced primary">
                        <div class="stat-icon-advanced primary">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-value-advanced"><?php echo $totalEnrolled; ?></div>
                        <div class="stat-label-advanced">Enrolled Courses</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>Active Learning</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-advanced success">
                        <div class="stat-icon-advanced success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value-advanced"><?php echo $completedCourses; ?></div>
                        <div class="stat-label-advanced">Completed</div>
                        <div class="stat-change positive">
                            <i class="fas fa-trophy"></i>
                            <span>Achievements</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-advanced info">
                        <div class="stat-icon-advanced info">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="stat-value-advanced"><?php echo $quizStats['total_attempts']; ?></div>
                        <div class="stat-label-advanced">Quiz Attempts</div>
                        <div class="stat-change positive">
                            <i class="fas fa-chart-line"></i>
                            <span>Knowledge Tests</span>
                        </div>
                    </div>
                    
                    <div class="stat-card-advanced warning">
                        <div class="stat-icon-advanced warning">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-value-advanced"><?php echo $learningStreak; ?></div>
                        <div class="stat-label-advanced">Day Streak</div>
                        <div class="stat-change <?php echo $learningStreak > 7 ? 'positive' : 'negative'; ?>">
                            <i class="fas fa-<?php echo $learningStreak > 7 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                            <span>Consistency</span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Progress Tracking Section -->
                <div class="progress-section">
                    <div class="progress-header">
                        <h3 class="progress-title"><i class="fas fa-chart-line me-2"></i>Learning Progress</h3>
                        <button class="btn btn-outline-primary btn-sm" onclick="progressTracker.loadProgressStatistics()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh Stats
                        </button>
                    </div>
                    
                    <div id="progress-overview" class="progress-cards">
                        <!-- Progress cards will be loaded here by JavaScript -->
                        <div class="loading">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Loading progress data...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Quiz Performance Section -->
                <div class="progress-section">
                    <div class="progress-header">
                        <h3 class="progress-title"><i class="fas fa-brain me-2"></i>Quiz Performance</h3>
                        <div class="d-flex gap-2">
                            <span class="badge bg-<?php echo $quizStats['avg_score'] >= 70 ? 'success' : ($quizStats['avg_score'] >= 50 ? 'warning' : 'danger'); ?>">
                                Avg: <?php echo $quizStats['avg_score']; ?>%
                            </span>
                            <span class="badge bg-info">Best: <?php echo $quizStats['best_score']; ?>%</span>
                        </div>
                    </div>
                    
                    <div class="progress-container" data-percentage="<?php echo min($quizStats['avg_score'], 100); ?>">
                        <div class="progress-label">
                            <span>Overall Quiz Performance</span>
                            <span><?php echo $quizStats['avg_score']; ?>%</span>
                        </div>
                        <div class="progress-bar-advanced">
                            <div class="progress-fill" style="width: <?php echo min($quizStats['avg_score'], 100); ?>%"></div>
                        </div>
                        <div class="progress-stats">
                            <div class="progress-stat">
                                <div class="progress-stat-value"><?php echo $quizStats['passed_quizzes']; ?></div>
                                <div class="progress-stat-label">Passed</div>
                            </div>
                            <div class="progress-stat">
                                <div class="progress-stat-value"><?php echo $quizStats['failed_quizzes']; ?></div>
                                <div class="progress-stat-label">Failed</div>
                            </div>
                            <div class="progress-stat">
                                <div class="progress-stat-value"><?php echo $quizStats['total_attempts']; ?></div>
                                <div class="progress-stat-label">Total</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Course Grid -->
                <div class="course-grid">
                    <?php foreach (array_slice($enrolledCourses, 0, 6) as $course): ?>
                        <?php 
                        $progressData = $enhancedProgress[$course['id']] ?? [];
                        $completionProb = $progressData['completion_probability'] ?? 0.7;
                        $estimatedDays = $progressData['estimated_completion_time'] ?? -1;
                        $alerts = $progressData['alerts'] ?? [];
                        ?>
                        <div class="course-card-advanced" data-course-id="<?php echo $course['id']; ?>">
                            <div class="course-card-header">
                                <img src="../uploads/course_thumbnails/<?php echo $course['thumbnail'] ?? 'default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                     class="course-thumbnail">
                                <div class="course-badge">
                                    <?php echo ucfirst($course['status']); ?>
                                </div>
                            </div>
                            
                            <div class="course-card-body">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p class="course-description">
                                    <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)); ?>...
                                </p>
                                
                                <div class="progress-container" data-percentage="<?php echo $course['progress_percentage']; ?>">
                                    <div class="progress-label">
                                        <span>Progress</span>
                                        <span><?php echo round($course['progress_percentage']); ?>%</span>
                                    </div>
                                    <div class="progress-bar-advanced">
                                        <div class="progress-fill" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="course-meta">
                                    <div class="course-instructor">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo htmlspecialchars($course['instructor_name'] ?? 'Instructor'); ?></span>
                                    </div>
                                    <div class="course-stats">
                                        <div class="course-stat">
                                            <i class="fas fa-book-open"></i>
                                            <span><?php echo $course['total_lessons'] ?? 0; ?> lessons</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="completion-probability">
                                    <div class="probability-indicator">
                                        <span class="probability-dot <?php echo $completionProb > 0.7 ? 'high' : ($completionProb > 0.4 ? 'medium' : 'low'); ?>"></span>
                                        <span>Completion Probability</span>
                                    </div>
                                    <strong><?php echo round($completionProb * 100); ?>%</strong>
                                </div>
                                
                                <?php if ($estimatedDays > 0): ?>
                                    <div class="estimated-time">
                                        <i class="fas fa-clock"></i>
                                        <span>Est. <?php echo $estimatedDays; ?> days</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 mt-3">
                                    <a href="view-course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary flex-grow-1">
                                        <i class="fas fa-play me-1"></i><?php echo $course['status'] === 'completed' ? 'Review' : 'Continue'; ?>
                                    </a>
                                    <button class="btn btn-outline-primary progress-details-btn" data-course-id="<?php echo $course['id']; ?>">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Advanced Recommendations Section -->
                <div class="recommendations-advanced">
                    <div class="recommendation-header">
                        <h3><i class="fas fa-star me-2"></i>Recommended for You</h3>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary learning-path-btn">
                                <i class="fas fa-route me-1"></i>Generate Path
                            </button>
                            <button class="btn btn-primary refresh-recommendations" data-original-text="<i class='fas fa-sync-alt'></i>">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="recommendation-grid">
                        <?php foreach ($recommendedCourses as $course): ?>
                            <div class="recommendation-card-advanced" data-course-id="<?php echo $course['id']; ?>">
                                <?php if (isset($course['recommendation_score'])): ?>
                                    <div class="recommendation-score" data-bs-toggle="tooltip" title="Match score based on your preferences">
                                        <?php echo round($course['recommendation_score'] * 100); ?>%
                                    </div>
                                <?php endif; ?>
                                
                                <h4 class="recommendation-title"><?php echo htmlspecialchars($course['title']); ?></h4>
                                <p class="recommendation-description">
                                    <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 80)); ?>...
                                </p>
                                
                                <div class="course-meta">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                    </small>
                                </div>
                                
                                <?php if (isset($course['recommendation_reason'])): ?>
                                    <div class="recommendation-reason">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?php echo htmlspecialchars($course['recommendation_reason']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="recommendation-actions">
                                    <a href="../course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <button class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Progress Statistics Section -->
                <div class="progress-section">
                    <div class="progress-header">
                        <h3 class="progress-title"><i class="fas fa-chart-bar me-2"></i>Progress Statistics</h3>
                    </div>
                    
                    <div id="progress-statistics">
                        <!-- Statistics will be loaded here by JavaScript -->
                        <div class="loading">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Loading statistics...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Activity Timeline -->
                <div class="activity-timeline">
                    <h3 class="progress-title"><i class="fas fa-clock-rotate-left me-2"></i>Recent Activity</h3>
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activity. Start learning to see your progress!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo $activity['type']; ?>">
                                    <?php
                                    $icon = 'circle';
                                    switch($activity['type']) {
                                        case 'enrollment': $icon = 'graduation-cap'; break;
                                        case 'quiz_attempt': $icon = 'brain'; break;
                                        case 'lesson_completed': $icon = 'check-circle'; break;
                                    }
                                    ?>
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <div class="activity-time"><?php echo getTimeAgo($activity['created_at']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab">
    <i class="fas fa-plus"></i>
</button>

<!-- Advanced Dashboard JavaScript -->
<script src="../assets/js/dashboard-enhanced.js"></script>
<script src="../assets/js/dashboard-advanced.js"></script>
<script src="../assets/js/progress-tracking.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/main.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Helper function for time ago
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
