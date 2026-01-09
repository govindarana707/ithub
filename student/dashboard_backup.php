<?php
require_once '../config/config.php';

// Authentication and authorization
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $Rs_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

// Load required models
require_once '../models/Course.php';
require_once '../models/User.php';
require_once '../models/Quiz.php';

// Initialize objects
$Rscourse = new Course();
$Rsuser = new User();
$Rsquiz = new Quiz();

$RsRsuserId = $Rs_SESSION['user_id'];

// Get dashboard data
$RsRsdashboardData = getStudentDashboardData($RsRsuserId);

/**
 * Get comprehensive student dashboard data
 */
function getStudentDashboardData($RsuserId) {
    $RsRsconn = connectDB();
    
    // Get enrolled courses with progress
    $RsRsenrolledCourses = getEnrolledCoursesWithProgress($RsRsconn, $RsuserId);
    
    // Get quiz statistics
    $RsRsquizStats = getQuizStatistics($RsRsconn, $RsRsuserId);
    
    // Get recent activity
    $RsRsrecentActivity = getRecentActivity($RsRsconn, $RsRsuserId);
    
    // Get learning streak
    $RsRslearningStreak = getLearningStreak($RsRsconn, $RsRsuserId);
    
    // Get achievements
    $RsRsachievements = getStudentAchievements($RsRsconn, $RsRsuserId);
    
    // Get recommended courses
    $RsRsrecommendedCourses = getRecommendedCourses($RsRsconn, $RsRsuserId, 4);
    
    $RsRsconn->close();
    
    return [
        'enrolledCourses' => $RsRsenrolledCourses,
        'quizStats' => $RsRsquizStats,
        'recentActivity' => $RsRsrecentActivity,
        'learningStreak' => $RsRslearningStreak,
        'achievements' => $RsRsachievements,
        'recommendedCourses' => $RsRsrecommendedCourses
    ];
}

/**
 * Get enrolled courses with progress information
 */
function getEnrolledCoursesWithProgress($Rs$RsRsconn, $RsRsuserId) {
    $RsRsstmt = $RsRsconn->prepare("
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
    ");
    $RsRsstmt->bind_param("ii", $RsRsuserId, $RsRsuserId);
    $RsRsstmt->execute();
    return $RsRsstmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get comprehensive quiz statistics
 */
function getQuizStatistics($Rs$RsRsconn, $RsRsuserId) {
    // Basic stats
    $RsRsstmt = $RsRsconn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            AVG(percentage) as avg_score,
            MAX(percentage) as best_score,
            SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_quizzes,
            SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as failed_quizzes
        FROM quiz_attempts 
        WHERE student_id = ? AND status = 'completed'
    ");
    $RsRsstmt->bind_param("i", $RsRsuserId);
    $RsRsstmt->execute();
    $RsRsstats = $RsRsstmt->get_result()->fetch_assoc();
    
    // Recent quiz attempts
    $RsRsstmt = $RsRsconn->prepare("
        SELECT qa.*, q.title as quiz_title, c.title as course_title
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        JOIN courses c ON q.course_id = c.id
        WHERE qa.student_id = ? AND qa.status = 'completed'
        ORDER BY qa.completed_at DESC
        LIMIT 5
    ");
    $RsRsstmt->bind_param("i", $RsRsuserId);
    $RsRsstmt->execute();
    $RsRsrecentQuizzes = $RsRsstmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'total_attempts' => (int)$RsRsstats['total_attempts'],
        'avg_score' => round($RsRsstats['avg_score'] ?? 0, 1),
        'best_score' => round($RsRsstats['best_score'] ?? 0, 1),
        'passed_quizzes' => (int)$RsRsstats['passed_quizzes'],
        'failed_quizzes' => (int)$RsRsstats['failed_quizzes'],
        'recent_quizzes' => $RsRsrecentQuizzes
    ];
}

/**
 * Get recent student activity
 */
function getRecentActivity($Rs$Rsconn, $RsuserId) {
    $Rsstmt = $Rsconn->prepare("
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
    $Rsstmt->bind_param("iii", $RsuserId, $RsuserId, $RsuserId);
    $Rsstmt->execute();
    return $Rsstmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Calculate learning streak
 */
function getLearningStreak($Rs$Rsconn, $RsuserId) {
    $Rsstmt = $Rsconn->prepare("
        SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
        FROM lesson_progress 
        WHERE student_id = ? AND completed = 1
        AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
    ");
    $Rsstmt->bind_param("i", $RsuserId);
    $Rsstmt->execute();
    $Rsresult = $Rsstmt->get_result()->fetch_assoc();
    return (int)$Rsresult['streak_days'];
}

/**
 * Get student achievements
 */
function getStudentAchievements($Rs$Rsconn, $RsuserId) {
    $Rsachievements = [];
    
    // Quiz master achievement
    $Rsstmt = $Rsconn->prepare("SELECT COUNT(*) as count FROM quiz_attempts WHERE student_id = ? AND passed = 1");
    $Rsstmt->bind_param("i", $RsuserId);
    $Rsstmt->execute();
    $RspassedQuizzes = $Rsstmt->get_result()->fetch_assoc()['count'];
    
    if ($RspassedQuizzes >= 10) {
        $Rsachievements[] = ['icon' => 'trophy', 'title' => 'Quiz Master', 'description' => 'Passed 10 quizzes', 'color' => 'gold'];
    } elseif ($RspassedQuizzes >= 5) {
        $Rsachievements[] = ['icon' => 'medal', 'title' => 'Quiz Expert', 'description' => 'Passed 5 quizzes', 'color' => 'silver'];
    }
    
    // Course completion achievement
    $Rsstmt = $Rsconn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ? AND status = 'completed'");
    $Rsstmt->bind_param("i", $RsuserId);
    $Rsstmt->execute();
    $RscompletedCourses = $Rsstmt->get_result()->fetch_assoc()['count'];
    
    if ($RscompletedCourses >= 3) {
        $Rsachievements[] = ['icon' => 'graduation-cap', 'title' => 'Dedicated Learner', 'description' => 'Completed 3 courses', 'color' => 'blue'];
    }
    
    return $Rsachievements;
}

/**
 * Get recommended courses
 */
function getRecommendedCourses($Rs$Rsconn, $RsuserId, $Rslimit = 4) {
    $Rsstmt = $Rsconn->prepare("
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
    $Rsstmt->bind_param("ii", $RsuserId, $Rslimit);
    $Rsstmt->execute();
    return $Rsstmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Extract data for easier access
$RsenrolledCourses = $RsdashboardData['enrolledCourses'];
$RsquizStats = $RsdashboardData['quizStats'];
$RsrecentActivity = $RsdashboardData['recentActivity'];
$RslearningStreak = $RsdashboardData['learningStreak'];
$Rsachievements = $RsdashboardData['achievements'];
$RsrecommendedCourses = $RsdashboardData['recommendedCourses'];

// Calculate additional metrics
$RstotalEnrolled = count($RsenrolledCourses);
$RscompletedCourses = count(array_filter($RsenrolledCourses, fn($Rsc) => $Rsc['status'] === 'completed'));
$RsinProgressCourses = $RstotalEnrolled - $RscompletedCourses;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IT HUB</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .list-group-item {
            border: none;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            color: var(--dark-color);
        }
        
        .list-group-item:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .list-group-item.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3);
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, var(--info-color), #2563eb);
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }
        
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .dashboard-card h3 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .achievement-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: var(--dark-color);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.3);
        }
        
        .course-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .quiz-item {
            border-left: 4px solid var(--primary-color);
            background: rgba(79, 70, 229, 0.05);
            border-radius: 0 10px 10px 0;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .quiz-item:hover {
            background: rgba(79, 70, 229, 0.1);
            transform: translateX(5px);
        }
        
        .activity-item {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: var(--light-color);
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(79, 70, 229, 0.1);
        }
        
        .streak-badge {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 35px rgba(79, 70, 229, 0.4);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                top: 0;
                margin-bottom: 2rem;
            }
            
            .main-content {
                padding: 1rem;
            }
            
            .stat-card h3 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Enhanced Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto d-flex align-items-center">
                <div class="me-3">
                    <span class="badge bg-success">Student</span>
                </div>
                <a class="nav-link text-dark" href="../dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i> Main Dashboard
                </a>
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Enhanced Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <div class="text-center mb-4">
                        <div class="streak-badge mb-3">
                            <i class="fas fa-fire"></i>
                            <span><?php echo $RslearningStreak; ?> Day Streak</span>
                        </div>
                    </div>
                    
                    <div class="list-group">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="my-courses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-graduation-cap me-2"></i> My Courses
                            <span class="badge bg-primary float-end"><?php echo $RstotalEnrolled; ?></span>
                        </a>
                        <a href="quizzes.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-brain me-2"></i> Quizzes
                            <span class="badge bg-info float-end"><?php echo $RsquizStats['total_attempts']; ?></span>
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
                    </div>
                    
                    <!-- Achievements Section -->
                    <?php if (!empty($Rsachievements)): ?>
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Achievements</h6>
                        <div>
                            <?php foreach ($Rsachievements as $Rsachievement): ?>
                                <div class="achievement-badge">
                                    <i class="fas fa-<?php echo $Rsachievement['icon']; ?> me-1"></i>
                                    <span><?php echo $Rsachievement['title']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9">
                <div class="main-content">
                    <!-- Header Section -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="mb-2">Welcome back, Student!</h1>
                            <p class="text-muted mb-0">Continue your learning journey and track your progress</p>
                        </div>
                        <div class="text-end">
                            <div class="streak-badge">
                                <i class="fas fa-fire"></i>
                                <span><?php echo $RslearningStreak; ?> Days</span>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <i class="fas fa-book-open fa-2x mb-3"></i>
                                <h3><?php echo $RstotalEnrolled; ?></h3>
                                <p>Enrolled Courses</p>
                                <small><?php echo $RsinProgressCourses; ?> in progress</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card success">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <h3><?php echo $RscompletedCourses; ?></h3>
                                <p>Completed</p>
                                <small><?php echo $RstotalEnrolled > 0 ? round(($RscompletedCourses / $RstotalEnrolled) * 100) : 0; ?>% completion rate</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card info">
                                <i class="fas fa-brain fa-2x mb-3"></i>
                                <h3><?php echo $RsquizStats['total_attempts']; ?></h3>
                                <p>Quiz Attempts</p>
                                <small><?php echo $RsquizStats['passed_quizzes']; ?> passed</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card <?php echo $RsquizStats['avg_score'] >= 80 ? 'success' : 'warning'; ?>">
                                <i class="fas fa-percentage fa-2x mb-3"></i>
                                <h3><?php echo $RsquizStats['avg_score']; ?>%</h3>
                                <p>Average Score</p>
                                <small>Best: <?php echo $RsquizStats['best_score']; ?>%</small>
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
                            <?php if (empty($RsquizStats['recent_quizzes'])): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                                    <h5>No quiz attempts yet</h5>
                                    <p class="text-muted">Start taking quizzes to see your performance!</p>
                                    <a href="quizzes.php" class="btn btn-primary">Take Your First Quiz</a>
                                </div>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($RsquizStats['recent_quizzes'] as $Rsquiz): ?>
                                        <div class="quiz-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($Rsquiz['quiz_title']); ?></h6>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fas fa-book me-1"></i><?php echo htmlspecialchars($Rsquiz['course_title']); ?>
                                                    </p>
                                                    <div class="d-flex align-items-center flex-wrap">
                                                        <span class="badge bg-<?php echo $Rsquiz['passed'] ? 'success' : 'danger'; ?> me-2">
                                                            <i class="fas fa-<?php echo $Rsquiz['passed'] ? 'check' : 'times'; ?> me-1"></i>
                                                            <?php echo $Rsquiz['passed'] ? 'Passed' : 'Failed'; ?>
                                                        </span>
                                                        <span class="badge bg-info me-2">
                                                            <?php echo round($Rsquiz['percentage']); ?>%
                                                        </span>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($Rsquiz['completed_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div>
                                                    <a href="quiz-result.php?attempt_id=<?php echo $Rsquiz['id']; ?>" class="btn btn-sm btn-outline-primary">
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
                        
                        <?php if (empty($RsenrolledCourses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <h5>No courses enrolled yet</h5>
                                <p class="text-muted">Start your learning journey by enrolling in our courses.</p>
                                <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach (array_slice($RsenrolledCourses, 0, 3) as $Rscourse): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card course-card h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($Rscourse['title']); ?></h6>
                                                    <span class="badge bg-<?php echo $Rscourse['status'] === 'completed' ? 'success' : 'primary'; ?>">
                                                        <?php echo ucfirst($Rscourse['status']); ?>
                                                    </span>
                                                </div>
                                                <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars($Rscourse['category_name'] ?? 'General'); ?></p>
                                                
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">Progress</small>
                                                        <small class="text-muted"><?php echo round($Rscourse['progress_percentage']); ?>%</small>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar" style="width: <?php echo $Rscourse['progress_percentage']; ?>%"></div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo $Rscourse['completed_lessons'] ?? 0; ?>/<?php echo $Rscourse['total_lessons'] ?? 0; ?> lessons
                                                    </small>
                                                </div>
                                                
                                                <div class="mt-2">
                                                    <a href="view-course.php?id=<?php echo $Rscourse['id']; ?>" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-play me-1"></i><?php echo $Rscourse['status'] === 'completed' ? 'Review' : 'Continue'; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Enhanced Recommended Courses Section -->
                    <div class="dashboard-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><i class="fas fa-star me-2"></i>Recommended Courses</h3>
                            <a href="../courses.php" class="btn btn-outline-primary btn-sm">Browse All</a>
                        </div>
                        
                        <div class="row">
                            <?php foreach ($RsrecommendedCourses as $Rscourse): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card course-card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title"><?php echo htmlspecialchars($Rscourse['title']); ?></h6>
                                                <?php if ($Rscourse['avg_rating']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <small class="text-warning me-1">
                                                            <i class="fas fa-star"></i> <?php echo round($Rscourse['avg_rating'], 1); ?>
                                                        </small>
                                                        <small class="text-muted">(<?php echo $Rscourse['rating_count']; ?>)</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <p class="card-text small text-muted mb-2"><?php echo substr(htmlspecialchars($Rscourse['description']), 0, 80); ?>...</p>
                                            
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($Rscourse['instructor_name']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i><?php echo $Rscourse['enrollment_count']; ?> enrolled
                                                </small>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($Rscourse['category_name'] ?? 'General'); ?></span>
                                                <a href="../course-details.php?id=<?php echo $Rscourse['id']; ?>" class="btn btn-outline-primary btn-sm">
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
                        <?php if (empty($RsrecentActivity)): ?>
                            <p class="text-muted text-center py-3">No recent activity. Start learning to see your progress!</p>
                        <?php else: ?>
                            <div>
                                <?php foreach ($RsrecentActivity as $Rsactivity): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $Rsicon = 'circle';
                                                    $Rscolor = 'primary';
                                                    switch($Rsactivity['type']) {
                                                        case 'enrollment':
                                                            $Rsicon = 'graduation-cap';
                                                            $Rscolor = 'success';
                                                            break;
                                                        case 'quiz_attempt':
                                                            $Rsicon = 'brain';
                                                            $Rscolor = 'info';
                                                            break;
                                                        case 'lesson_completed':
                                                            $Rsicon = 'check-circle';
                                                            $Rscolor = 'success';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="fas fa-<?php echo $Rsicon; ?> text-<?php echo $Rscolor; ?> me-2"></i>
                                                    <span><?php echo htmlspecialchars($Rsactivity['description']); ?></span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo getTimeAgo($Rsactivity['created_at']); ?>
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
</body>
</html>

<?php
/**
 * Helper function to get time ago string
 */
function getTimeAgo($Rs$Rsdatetime) {
    $Rstime = strtotime($Rsdatetime);
    $Rsnow = time();
    $Rsdiff = $Rsnow - $Rstime;
    
    if ($Rsdiff < 60) return 'Just now';
    if ($Rsdiff < 3600) return floor($Rsdiff / 60) . ' minutes ago';
    if ($Rsdiff < 86400) return floor($Rsdiff / 3600) . ' hours ago';
    if ($Rsdiff < 604800) return floor($Rsdiff / 86400) . ' days ago';
    return date('M j, Y', $Rstime);
}
?>
