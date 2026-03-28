<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireStudent();

require_once dirname(__DIR__) . '/models/Database.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/Quiz.php';
require_once dirname(__DIR__) . '/models/RecommendationSystem.php';
require_once dirname(__DIR__) . '/models/ProgressTracking.php';

$user = new User();
$course = new Course();
$updatedDb = new Database();
$conn = $updatedDb->getConnection();
$userId = $_SESSION['user_id'];
$userData = $user->getUserById($userId);

// Generate CSRF token for security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Ensure required tables exist for dashboard functionality
$requiredTables = [
    "CREATE TABLE IF NOT EXISTS lessons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        duration_minutes INT DEFAULT 0,
        lesson_order INT DEFAULT 0,
        is_published BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS lesson_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        lesson_id INT NOT NULL,
        course_id INT NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        time_spent_minutes INT DEFAULT 0,
        last_accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_progress (student_id, lesson_id),
        INDEX idx_student_id (student_id),
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS quizzes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        time_limit_minutes INT DEFAULT 30,
        passing_score INT DEFAULT 70,
        is_published BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        student_id INT NOT NULL,
        score INT DEFAULT 0,
        status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT,
        notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($requiredTables as $sql) {
    $conn->query($sql);
}

// --- OPTIMIZED DATA FETCHING ---

// Single query for comprehensive dashboard statistics
$dashboardStatsQuery = "
    SELECT 
        COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.course_id END) as total_enrolled,
        COUNT(DISTINCT CASE WHEN e.status = 'active' AND e.progress_percentage >= 100 THEN e.course_id END) as completed_courses,
        COUNT(DISTINCT DATE(lp.last_accessed_at)) as learning_streak,
        COALESCE(SUM(lp.time_spent_minutes), 0) as total_study_minutes,
        COUNT(DISTINCT CASE WHEN e.status = 'active' AND e.progress_percentage < 100 THEN e.course_id END) as in_progress_courses
    FROM enrollments_new e
    LEFT JOIN lesson_progress lp ON e.user_id = lp.student_id AND lp.last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE e.user_id = ?
";

$stmt = $conn->prepare($dashboardStatsQuery);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $dashboardStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $dashboardStats = [
        'total_enrolled' => 0,
        'completed_courses' => 0,
        'learning_streak' => 0,
        'total_study_minutes' => 0,
        'in_progress_courses' => 0
    ];
}

$totalEnrolled = (int)$dashboardStats['total_enrolled'];
$completedCourses = (int)$dashboardStats['completed_courses'];
$inProgressCourses = (int)$dashboardStats['in_progress_courses'];
$learningStreak = (int)$dashboardStats['learning_streak'];
$totalStudyHours = round((int)$dashboardStats['total_study_minutes'] / 60, 1);

// Get enrolled courses with progress
$enrolledCourses = $course->getEnrolledCourses($userId);

// Daily Focus: Find most relevant lesson to continue
$dailyFocusQuery = "
    SELECT c.id as course_id, c.title as course_title, c.thumbnail,
           l.id as lesson_id, l.title as lesson_title, l.duration_minutes
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses_new c ON l.course_id = c.id
    WHERE lp.student_id = ? AND lp.completed = 0
    ORDER BY lp.last_accessed_at DESC
    LIMIT 1
";

$stmt = $conn->prepare($dailyFocusQuery);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $dailyFocusResult = $stmt->get_result();
    $dailyFocus = $dailyFocusResult->num_rows > 0 ? $dailyFocusResult->fetch_assoc() : null;
    $stmt->close();
} else {
    $dailyFocus = null;
}

if (!$dailyFocus && !empty($enrolledCourses)) {
    // Fallback: First unfinished course
    foreach ($enrolledCourses as $ec) {
        if (($ec['progress_percentage'] ?? 0) < 100) {
            $dailyFocus = [
                'course_id' => $ec['id'],
                'course_title' => $ec['title'],
                'lesson_title' => 'Continue your progress',
                'duration_minutes' => 'Active',
                'thumbnail' => $ec['thumbnail']
            ];
            break;
        }
    }
}

// AI Recommendations (KNN)
$recommender = new RecommendationSystem();
$recommendedCourses = $recommender->getKNNRecommendations($userId, 2);

// Pending Tasks (Quizzes due) - Optimized query
$pendingQuizzesQuery = "
    SELECT q.id, q.title, c.title as course_title
    FROM quizzes q
    JOIN enrollments_new e ON q.course_id = e.course_id
    JOIN courses_new c ON q.course_id = c.id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE e.student_id = ? AND e.status = 'active' AND (qa.id IS NULL OR qa.score < 70)
    LIMIT 3
";

$stmt = $conn->prepare($pendingQuizzesQuery);
if ($stmt) {
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    $pendingQuizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $pendingQuizzes = [];
}

$pendingTasks = [];
foreach ($pendingQuizzes as $pq) {
    $pendingTasks[] = [
        'title' => $pq['title'],
        'course' => $pq['course_title'],
        'url' => 'quiz.php?quiz_id=' . $pq['id']
    ];
}

// Greeting based on time
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");
?>

<?php require_once dirname(__DIR__) . '/includes/universal_header.php'; ?>

<!-- Enhanced Student Dashboard Styles -->
<style>
/* Import enhanced dashboard layout styles */
@import url('../assets/css/dashboard-enhanced-layout.css');
@import url('css/student-theme.css');

/* Student Dashboard Specific Styles */
.dashboard-header {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
    border: 1px solid #e5e7eb;
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

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
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

.overview-stat-card.primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.overview-stat-card.success::before { background: linear-gradient(90deg, #10b981, #059669); }
.overview-stat-card.info::before { background: linear-gradient(90deg, #3b82f6, #1d4ed8); }
.overview-stat-card.warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }

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

.stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-icon.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-icon.info { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.stat-icon.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }

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

/* Enhanced animations */
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

/* Responsive Design */
@media (max-width: 768px) {
    .header-title {
        font-size: 1.75rem;
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
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .header-actions {
        width: 100%;
        justify-content: space-between;
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
</style>

<!-- Main Content -->
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="courses.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-book me-2"></i> Browse Courses
                </a>
                <a href="my-courses.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-book-open me-2"></i> My Courses
                </a>
                <a href="certificates.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-certificate me-2"></i> Certificates
                </a>
                <a href="quiz-results.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-bar me-2"></i> Quiz Results
                </a>
                <a href="discussions.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-comments me-2"></i> Discussions
                </a>
                <a href="notifications.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-bell me-2"></i> Notifications
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
                <div class="mt-3 p-2">
                    <a href="../logout.php" class="btn btn-outline-danger w-100">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Welcome Header -->
            <div class="dashboard-header mb-4">
                <div class="header-content">
                    <div class="header-left">
                        <h1 class="header-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($userData['full_name']); ?>!</h1>
                        <p class="header-subtitle">Ready to continue your learning journey?</p>
                    </div>
                    <div class="header-right">
                        <div class="header-actions">
                            <a href="courses.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Courses
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Bar -->
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-icon bg-primary">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="quick-stat-content">
                            <div class="quick-stat-value"><?php echo $totalEnrolled; ?></div>
                            <div class="quick-stat-label">Enrolled Courses</div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="quick-stat-content">
                            <div class="quick-stat-value"><?php echo $completedCourses; ?></div>
                            <div class="quick-stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon bg-info">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="quick-stat-content">
                            <div class="quick-stat-value"><?php echo $learningStreak; ?></div>
                            <div class="quick-stat-label">Day Streak</div>
                        </div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="quick-stat-content">
                            <div class="quick-stat-value"><?php echo $totalStudyHours; ?>h</div>
                            <div class="quick-stat-label">Study Time</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overview Stats Cards -->
            <div class="overview-stats-grid">
                <div class="overview-stat-card primary">
                    <div class="stat-icon primary">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-value"><?php echo $inProgressCourses; ?></div>
                    <div class="stat-label">Active Courses</div>
                    <small class="text-muted mt-2 d-block">In Progress</small>
                </div>
                <div class="overview-stat-card success">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $completedCourses; ?></div>
                    <div class="stat-label">Completed</div>
                    <small class="text-muted mt-2 d-block">Finished Courses</small>
                </div>
                <div class="overview-stat-card info">
                    <div class="stat-icon info">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-value"><?php echo $learningStreak; ?></div>
                    <div class="stat-label">Day Streak</div>
                    <small class="text-muted mt-2 d-block">Keep Going!</small>
                </div>
                <div class="overview-stat-card warning">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $totalStudyHours; ?>h</div>
                    <div class="stat-label">Study Hours</div>
                    <small class="text-muted mt-2 d-block">Total Time</small>
                </div>
            </div>

            <!-- Main Content Layout -->
            <div class="row">
                <!-- Left Column: Continue Learning & Recommendations -->
                <div class="col-lg-8">
                    <!-- Continue Learning -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Continue Learning</h3>
                            <a href="my-courses.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        
                        <?php if (!empty($enrolledCourses)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Instructor</th>
                                            <th>Progress</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($enrolledCourses, 0, 5) as $course): ?>
                                            <?php if (($course['progress_percentage'] ?? 0) < 100): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($course['thumbnail']): ?>
                                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" 
                                                                     class="rounded me-3" width="60" height="40" style="object-fit: cover;">
                                                            <?php else: ?>
                                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                     style="width: 60px; height: 40px;">
                                                                    <i class="fas fa-image text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                                <small class="text-muted"><?php echo $course['duration_minutes'] ?? 0; ?> minutes</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                                        </div>
                                                        <small><?php echo round($course['progress_percentage'] ?? 0); ?>%</small>
                                                    </td>
                                                    <td>
                                                        <a href="lesson.php?course_id=<?php echo $course['id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-play me-1"></i>Continue
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <h5>Start Your Learning Journey</h5>
                                <p class="text-muted">Enroll in your first course to begin learning.</p>
                                <a href="courses.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Browse Courses
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recommended Courses -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Recommended for You</h3>
                            <small class="text-muted">AI-powered suggestions</small>
                        </div>
                        
                        <?php if (!empty($recommendedCourses)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Category</th>
                                            <th>Level</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recommendedCourses as $rec): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if($rec['thumbnail']): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($rec['thumbnail'])); ?>" 
                                                                 class="rounded me-3" width="60" height="40" style="object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                 style="width: 60px; height: 40px;">
                                                                <i class="fas fa-lightbulb text-warning"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($rec['title']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($rec['description'] ?? 'Based on your learning history'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo ucfirst($rec['difficulty_level'] ?? 'Intermediate'); ?></span></td>
                                                <td><?php echo ucfirst($rec['difficulty_level'] ?? 'Intermediate'); ?></td>
                                                <td>
                                                    <a href="course-details.php?id=<?php echo $rec['id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        View Course
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-star text-warning fa-2x mb-3"></i>
                                <h6>Explore Popular Courses</h6>
                                <p class="text-muted">Complete more courses to get personalized recommendations.</p>
                                <a href="courses.php" class="btn btn-outline-primary">
                                    <i class="fas fa-compass me-2"></i>Explore Catalog
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                </div>

                <!-- Right Column: Sidebar Widgets -->
                <div class="col-lg-4">
                    <!-- Pending Tasks -->
                    <div class="dashboard-card mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-tasks me-2 text-primary"></i>
                            Pending Tasks
                        </h5>
                        <div class="list-group list-group-flush">
                            <?php if (!empty($pendingTasks)): ?>
                                <?php foreach ($pendingTasks as $task): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-primary text-white rounded-circle p-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-clipboard-list small"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-truncate"><?php echo htmlspecialchars($task['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($task['course']); ?></small>
                                            </div>
                                            <a href="<?php echo $task['url']; ?>" class="btn btn-outline-primary btn-sm rounded-circle">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                                    <p class="mb-0">All caught up!</p>
                                    <small>No pending tasks</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile Summary -->
                    <div class="dashboard-card mb-4 text-center">
                        <h5 class="mb-3">Profile Summary</h5>
                        <div class="mb-3">
                            <?php if ($userData['profile_image']): ?>
                                <img src="../<?php echo htmlspecialchars($userData['profile_image']); ?>" 
                                     class="rounded-circle shadow" width="80" height="80" style="object-fit: cover; border: 3px solid var(--primary-color);">
                            <?php else: ?>
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center shadow mx-auto" 
                                     style="width: 80px; height: 80px; font-size: 1.5rem; color: white; font-weight: bold;">
                                    <?php echo strtoupper(substr($userData['full_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($userData['full_name']); ?></h6>
                        <p class="text-muted small mb-3">Student</p>
                        
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user-edit me-1"></i>Edit Profile
                            </a>
                            <a href="certificates.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-certificate me-1"></i>My Certificates
                            </a>
                        </div>
                    </div>

                    <!-- Daily Focus -->
                    <?php if ($dailyFocus): ?>
                        <div class="dashboard-card mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-bullseye me-2 text-warning"></i>
                                Daily Focus
                            </h5>
                            <div class="text-center">
                                <?php if ($dailyFocus['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($dailyFocus['thumbnail'])); ?>" 
                                         class="rounded mb-3" width="100%" height="120" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center" 
                                         style="width: 100%; height: 120px;">
                                        <i class="fas fa-book text-muted fa-2x"></i>
                                    </div>
                                <?php endif; ?>
                                <h6 class="fw-bold"><?php echo htmlspecialchars($dailyFocus['course_title']); ?></h6>
                                <p class="text-muted small"><?php echo htmlspecialchars($dailyFocus['lesson_title']); ?></p>
                                <a href="lesson.php?course_id=<?php echo $dailyFocus['course_id']; ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-play me-1"></i>Continue Learning
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Quick Stats -->
                    <div class="dashboard-card">
                        <h5 class="mb-3">Quick Stats</h5>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="fas fa-fire text-danger fa-2x mb-2"></i>
                                    <div class="h5 mb-0"><?php echo $learningStreak; ?></div>
                                    <small class="text-muted">Day Streak</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <i class="fas fa-clock text-primary fa-2x mb-2"></i>
                                    <div class="h5 mb-0"><?php echo $totalStudyHours; ?>h</div>
                                    <small class="text-muted">Study Time</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/main.js"></script>

<!-- Enhanced Dashboard JavaScript -->
<script>
$(document).ready(function() {
    // Add smooth animations to stat cards
    $('.overview-stat-card').on('mouseenter', function() {
        $(this).find('.stat-icon').addClass('pulse');
    }).on('mouseleave', function() {
        $(this).find('.stat-icon').removeClass('pulse');
    });
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        // Silent refresh - you can implement AJAX refresh here
        console.log('Dashboard data refreshed');
    }, 300000);
});
</script>

</body>
</html>
