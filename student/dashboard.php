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
        id Int AUTO_INCREMENT PRIMARY KEY,
        course_id Int NOT NULL,
        title VARCHAR(255) NOT NULL,
        duration_minutes Int DEFAULT 0,
        lesson_order Int DEFAULT 0,
        is_published BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS lesson_progress (
        id Int AUTO_INCREMENT PRIMARY KEY,
        student_id Int NOT NULL,
        lesson_id Int NOT NULL,
        course_id Int NOT NULL,
        completed BOOLEAN DEFAULT FALSE,
        time_spent_minutes Int DEFAULT 0,
        last_accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_progress (student_id, lesson_id),
        INDEX idx_student_id (student_id),
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS quizzes (
        id Int AUTO_INCREMENT PRIMARY KEY,
        course_id Int NOT NULL,
        title VARCHAR(255) NOT NULL,
        time_limit_minutes Int DEFAULT 30,
        passing_score Int DEFAULT 70,
        is_published BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course_id (course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS quiz_attempts (
        id Int AUTO_INCREMENT PRIMARY KEY,
        quiz_id Int NOT NULL,
        student_id Int NOT NULL,
        score Int DEFAULT 0,
        status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        INDEX idx_quiz_id (quiz_id),
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS notifications (
        id Int AUTO_INCREMENT PRIMARY KEY,
        user_id Int NOT NULL,
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

// Get enrolled courses with progress
$enrolledCourses = $course->getEnrolledCourses($userId);

// Filter to show only in-progress courses for Continue Learning section
$inProgressCourses = array_filter($enrolledCourses, function($course) {
    return ($course['progress_percentage'] ?? 0) < 100;
});

// Sort by last accessed date
usort($inProgressCourses, function($a, $b) {
    $dateA = strtotime($a['updated_at'] ?? $a['enrolled_at']);
    $dateB = strtotime($b['updated_at'] ?? $b['enrolled_at']);
    return $dateB - $dateA; // Descending order
});

// Update stats to match course listing data
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, function($course) {
    return ($course['progress_percentage'] ?? 0) >= 100;
}));
$inProgressCoursesCount = count($inProgressCourses);

// Add missing variables from original dashboard stats
$learningStreak = (int)($dashboardStats['learning_streak'] ?? 0);
$totalStudyHours = round((int)($dashboardStats['total_study_minutes'] ?? 0) / 60, 1);

// Get certificates count
$certificatesQuery = "SELECT COUNT(*) as count FROM certificates WHERE student_id = ?";
$stmt = $conn->prepare($certificatesQuery);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $certificatesResult = $stmt->get_result()->fetch_assoc();
    $certificatesCount = (int)$certificatesResult['count'];
    $stmt->close();
} else {
    $certificatesCount = 0;
}

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

<!-- Import global theme and layout styles with cache busting -->
<style>
@import url('../assets/css/theme.css?v=<?php echo time(); ?>');
@import url('css/student-theme.css?v=<?php echo time(); ?>');

/* Enhanced Dashboard Styles */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --card-shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
    --border-radius-modern: 20px;
    --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Modern Dashboard Header */
.dashboard-header {
    background: var(--primary-gradient);
    border-radius: var(--border-radius-modern);
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: var(--card-shadow);
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 60%;
    height: 200%;
    background: rgba(255, 255, 255, 0.05);
    transform: rotate(35deg);
    pointer-events: none;
}

.dashboard-header::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="50" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="30" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
    pointer-events: none;
}

.dashboard-header h1 {
    color: white !important;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    animation: fadeInUp 0.8s ease;
    position: relative;
    z-index: 1;
}

.dashboard-header p {
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 1.1rem;
    margin: 0;
    animation: fadeInUp 0.8s ease 0.2s both;
    position: relative;
    z-index: 1;
}

/* Enhanced Stats Cards */
.modern-stat-card {
    background: white;
    border-radius: var(--border-radius-modern);
    padding: 2rem;
    text-align: center;
    transition: var(--transition-smooth);
    border: none;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
    height: 100%;
}

.modern-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    transition: var(--transition-smooth);
}

.modern-stat-card.success::before {
    background: var(--success-gradient);
}

.modern-stat-card.warning::before {
    background: var(--warning-gradient);
}

.modern-stat-card.info::before {
    background: var(--info-gradient);
}

.modern-stat-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.modern-stat-card:hover::after {
    opacity: 1;
}

.modern-stat-card:hover {
    transform: translateY(-10px) scale(1.02);
    box-shadow: var(--card-shadow-hover);
}

.modern-stat-card:hover::before {
    height: 8px;
}

.modern-stat-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    margin: 0 auto 1.5rem;
    position: relative;
    animation: pulse 2s infinite;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    transition: var(--transition-smooth);
}

.modern-stat-card:hover .modern-stat-icon {
    transform: scale(1.1);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
}

.modern-stat-icon.primary {
    background: var(--primary-gradient);
}

.modern-stat-icon.success {
    background: var(--success-gradient);
}

.modern-stat-icon.warning {
    background: var(--warning-gradient);
}

.modern-stat-icon.info {
    background: var(--info-gradient);
}

.modern-stat-card h3 {
    color: #2d3748;
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 0.5rem;
    transition: var(--transition-smooth);
}

.modern-stat-card:hover h3 {
    transform: scale(1.05);
}

.modern-stat-card p {
    color: #718096;
    font-weight: 500;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
}

/* Modern Content Cards */
.modern-card {
    background: white;
    border-radius: var(--border-radius-modern);
    border: none;
    box-shadow: var(--card-shadow);
    transition: var(--transition-smooth);
    overflow: hidden;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow-hover);
}

.modern-card .card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 1px solid #e2e8f0;
    padding: 1.5rem;
    border-radius: var(--border-radius-modern) var(--border-radius-modern) 0 0;
}

.modern-card .card-title {
    color: #2d3748;
    font-weight: 700;
    font-size: 1.3rem;
    margin: 0;
    display: flex;
    align-items: center;
}

.modern-card .card-body {
    padding: 2rem;
}

/* Enhanced Table Styles */
.modern-table {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.modern-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.modern-table th {
    color: #4a5568;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border: none;
    padding: 1rem;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
    border-color: #f7fafc;
}

.modern-table tbody tr {
    transition: var(--transition-smooth);
}

.modern-table tbody tr:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transform: scale(1.01);
}

/* Enhanced Progress Bars */
.progress-modern {
    height: 8px;
    border-radius: 10px;
    background: #e2e8f0;
    overflow: hidden;
    position: relative;
}

.progress-modern .progress-bar {
    background: var(--primary-gradient);
    border-radius: 10px;
    transition: width 1s ease;
    position: relative;
    overflow: hidden;
}

.progress-modern .progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: shimmer 2s infinite;
}

/* Modern Buttons */
.btn-modern {
    border-radius: 25px;
    padding: 0.6rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: var(--transition-smooth);
    border: none;
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-modern:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary-modern {
    background: var(--primary-gradient);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

    .modern-stat-card {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        margin: 0 auto 1.5rem;
        position: relative;
        animation: pulse 2s infinite;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        transition: var(--transition-smooth);
    }

    .modern-stat-card:hover .modern-stat-icon {
        transform: scale(1.1);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .modern-stat-icon.primary {
        background: var(--primary-gradient);
    }

    .modern-stat-icon.success {
        background: var(--success-gradient);
    }

    .modern-stat-icon.warning {
        background: var(--warning-gradient);
    }

    .modern-stat-icon.info {
        background: var(--info-gradient);
    }

    .modern-stat-card h3 {
        color: #2d3748;
        font-weight: 700;
        font-size: 2.2rem;
        margin-bottom: 0.5rem;
        transition: var(--transition-smooth);
    }

    .modern-stat-card:hover h3 {
        transform: scale(1.05);
    }

    .modern-stat-card p {
        color: #718096;
        font-weight: 500;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.9rem;
    }

    /* Modern Content Cards */
    .modern-card {
        background: white;
        border-radius: var(--border-radius-modern);
        border: none;
        box-shadow: var(--card-shadow);
        transition: var(--transition-smooth);
        overflow: hidden;
    }

    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-shadow-hover);
    }

    .modern-card .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem;
        border-radius: var(--border-radius-modern) var(--border-radius-modern) 0 0;
    }

    .modern-card .card-title {
        color: #2d3748;
        font-weight: 700;
        font-size: 1.3rem;
        margin: 0;
        display: flex;
        align-items: center;
    }

    .modern-card .card-body {
        padding: 2rem;
    }

    /* Enhanced Table Styles */
    .modern-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .modern-table thead {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .modern-table th {
        color: #4a5568;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 1rem;
    }

    .modern-table td {
        padding: 1rem;
        vertical-align: middle;
        border-color: #f7fafc;
    }

    .modern-table tbody tr {
        transition: var(--transition-smooth);
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        transform: scale(1.01);
    }

    /* Enhanced Progress Bars */
    .progress-modern {
        height: 8px;
        border-radius: 10px;
        background: #e2e8f0;
        overflow: hidden;
        position: relative;
    }

    .progress-modern .progress-bar {
        background: var(--primary-gradient);
        border-radius: 10px;
        transition: width 1s ease;
        position: relative;
        overflow: hidden;
    }

    .progress-modern .progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255, 255, 255, 0.3),
            transparent
        );
        animation: shimmer 2s infinite;
    }

    /* Modern Buttons */
    .btn-modern {
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: var(--transition-smooth);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .btn-primary-modern {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* Enhanced Profile Widget */
    .profile-widget {
        background: var(--primary-gradient);
        border-radius: var(--border-radius-modern);
        padding: 2rem;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .profile-widget::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 60%;
        height: 200%;
        background: rgba(255, 255, 255, 0.05);
        transform: rotate(35deg);
        pointer-events: none;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 4px solid rgba(255, 255, 255, 0.2);
        margin: 0 auto 1rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        transition: var(--transition-smooth);
    }

    .profile-avatar:hover {
        transform: scale(1.05);
        border-color: rgba(255, 255, 255, 0.4);
    }

    /* Daily Focus Card */
    .daily-focus-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
        border-radius: var(--border-radius-modern);
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    .daily-focus-card::before {
        content: '';
        position: absolute;
        top: -20%;
        right: -20%;
        width: 40%;
        height: 40%;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
    }

    /* Floating Action Button */
    .fab-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
    }

    .fab {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--primary-gradient);
        border: none;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        transition: var(--transition-smooth);
        position: relative;
        overflow: hidden;
    }

    .fab:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    }

    .fab::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s ease, height 0.6s ease;
    }

    .fab:hover::before {
        width: 100px;
        height: 100px;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    @keyframes glow {
        0%, 100% {
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
        }
        50% {
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.8);
        }
    }

    /* Staggered Animation for Stats */
    .stat-card-1 { animation: fadeInUp 0.6s ease 0.1s both; }
    .stat-card-2 { animation: fadeInUp 0.6s ease 0.2s both; }
    .stat-card-3 { animation: fadeInUp 0.6s ease 0.3s both; }
    .stat-card-4 { animation: fadeInUp 0.6s ease 0.4s both; }

    /* Parallax Scroll Effect */
    .parallax-element {
        transition: transform 0.1s ease-out;
    }

    /* Loading Animation */
    .loading-skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }

    @keyframes loading {
        0% {
            background-position: 200% 0;
        }
        100% {
            background-position: -200% 0;
        }
    }

    /* Enhanced Responsive Design */
    @media (max-width: 768px) {
        .dashboard-header {
            padding: 2rem 1.5rem;
        }
        
        .dashboard-header h1 {
            font-size: 2rem;
        }
        
        .modern-stat-card {
            padding: 1.5rem;
        }
        
        .modern-stat-icon {
            width: 60px;
            height: 60px;
            font-size: 1.4rem;
        }
        
        .modern-stat-card h3 {
            font-size: 1.8rem;
        }
        
        .fab-container {
            bottom: 1rem;
            right: 1rem;
        }
        
        .fab {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }
    }

    .activity-timeline {
        position: relative;
        padding-left: 2rem;
    }
    
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, var(--primary-gradient), transparent);
    }
    
    .activity-item {
        position: relative;
        padding-left: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .activity-item::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 0;
        width: 20px;
        height: 20px;
        background: white;
        border: 2px solid var(--primary-gradient);
        border-radius: 50%;
        z-index: 1;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        position: absolute;
        left: -20px;
        top: 0;
        z-index: 2;
    }
    
    .activity-content {
        margin-left: 1rem;
    }
    
    .activity-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }
    
    .activity-time {
        font-size: 0.875rem;
        color: #718096;
    }

    /* Accessibility Enhancements */
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus Management */
    .btn-modern:focus,
    .modern-stat-card:focus,
    .fab:focus {
        outline: 3px solid var(--primary-gradient);
        outline-offset: 2px;
    }

    .modern-stat-card:focus-within,
    .btn-modern:focus-within,
    .fab:focus-within {
        outline: 2px solid rgba(102, 126, 234, 0.5);
    }

    /* Screen Reader Only Content */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Enhanced Table Accessibility */
    .modern-table caption {
        caption-side: bottom;
        text-align: center;
        font-size: 0.875rem;
        color: #6c757d;
        padding: 0.5rem;
        margin-top: 1rem;
    }

    .modern-table th[scope="col"] {
        position: relative;
    }

    .modern-table th[scope="col"]::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: var(--primary-gradient);
    }

    /* Progress Bar Accessibility */
    .progress-modern .progress-bar {
        background: var(--primary-gradient);
        position: relative;
    }

    .progress-modern .progress-bar::after {
        content: attr(aria-label);
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.9);
        color: #2d3748;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .progress-modern .progress-bar:hover::after {
        opacity: 1;
    }

    /* List Group Accessibility */
    .list-group-item {
        position: relative;
    }

    .list-group-item:focus {
        outline: 2px solid var(--primary-gradient);
        outline-offset: -2px;
    }

    /* Button Group Accessibility */
    .d-grid {
        display: grid;
        gap: 0.5rem;
    }

    .d-grid .btn-modern {
        position: relative;
    }

    .d-grid .btn-modern:focus {
        z-index: 1;
    }

    /* Enhanced Skip Links */
    .skip-link {
        position: absolute;
        top: -40px;
        left: 0;
        background: var(--primary-gradient);
        color: white;
        padding: 0.5rem 1rem;
        text-decoration: none;
        border-radius: 4px;
        font-weight: 600;
        transition: top 0.3s ease;
        z-index: 9999;
    }

    .skip-link:focus {
        top: 0;
    }

    /* Enhanced Print Styles */
    @media print {
        .fab-container,
        .activity-timeline::before {
            display: none;
        }
        
        .dashboard-header {
            background: var(--primary-gradient) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .modern-stat-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        
        .modern-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        
        .activity-item {
            break-inside: avoid;
        }
        
        .activity-icon {
            background: var(--primary-gradient) !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<!-- Main Content -->
<div class="container-fluid py-4" role="main">
    <div class="row">
        <!-- Sidebar Navigation -->
        <aside class="col-md-3" role="navigation" aria-label="Student Navigation">
            <?php require_once 'includes/sidebar.php'; ?>
        </aside>
        
        <!-- Main Dashboard Content -->
        <main class="col-md-9" role="main">
            <!-- Dashboard Header -->
            <header class="dashboard-header" role="banner">
                <div class="position-relative">
                    <h1 class="mb-3"><?php echo $greeting; ?>, <?php echo htmlspecialchars($userData['full_name']); ?>! 👋</h1>
                    <p class="mb-0">Ready to continue your learning journey? You've got this!</p>
                </div>
            </header>

            <!-- Statistics Overview -->
            <section class="statistics-overview" aria-labelledby="stats-heading">
                <h2 id="stats-heading" class="visually-hidden">Your Learning Statistics</h2>
                <div class="row g-4 mb-4" role="list">
                    <!-- Active Courses Stat -->
                    <article class="col-md-3" role="listitem">
                        <div class="modern-stat-card stat-card-1" role="group" aria-labelledby="active-courses-stat">
                            <div class="modern-stat-icon primary" aria-hidden="true">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="active-courses-stat"><?php echo $inProgressCoursesCount; ?></h3>
                            <p class="mb-0">Active Courses</p>
                        </div>
                    </article>
                    
                    <!-- Completed Courses Stat -->
                    <article class="col-md-3" role="listitem">
                        <div class="modern-stat-card success stat-card-2" role="group" aria-labelledby="completed-courses-stat">
                            <div class="modern-stat-icon success" aria-hidden="true">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="completed-courses-stat"><?php echo $completedCourses; ?></h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </article>
                    
                    <!-- Learning Streak Stat -->
                    <article class="col-md-3" role="listitem">
                        <div class="modern-stat-card info stat-card-3" role="group" aria-labelledby="learning-streak-stat">
                            <div class="modern-stat-icon info" aria-hidden="true">
                                <i class="fas fa-fire"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="learning-streak-stat"><?php echo $learningStreak; ?></h3>
                            <p class="mb-0">Day Streak 🔥</p>
                        </div>
                    </article>
                    
                    <!-- Study Hours Stat -->
                    <article class="col-md-3" role="listitem">
                        <div class="modern-stat-card warning stat-card-4" role="group" aria-labelledby="study-hours-stat">
                            <div class="modern-stat-icon warning" aria-hidden="true">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3 class="fw-bold mb-1" id="study-hours-stat"><?php echo $totalStudyHours; ?>h</h3>
                            <p class="mb-0">Study Hours</p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Main Content Grid -->
            <div class="row">
                <!-- Left Column: Learning Content -->
                <section class="col-lg-8" aria-labelledby="learning-content-heading">
                    <h2 id="learning-content-heading" class="visually-hidden">Learning Content</h2>
                    
                    <!-- Continue Learning Section -->
                    <section class="modern-card mb-4" aria-labelledby="continue-learning-heading">
                        <header class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0" id="continue-learning-heading">
                                    <i class="fas fa-play-circle me-2 text-primary" aria-hidden="true"></i>
                                    Continue Learning
                                </h3>
                                <a href="my-courses.php" class="btn btn-outline-primary btn-modern btn-sm" aria-label="View all courses">
                                    View All
                                </a>
                            </div>
                        </header>
                        <div class="card-body">
                            <?php if (!empty($inProgressCourses)): ?>
                                <div class="table-responsive modern-table">
                                    <table class="table table-hover" role="table" aria-label="In-progress courses">
                                        <thead>
                                            <tr>
                                                <th scope="col">Course</th>
                                                <th scope="col">Instructor</th>
                                                <th scope="col">Progress</th>
                                                <th scope="col">Last Accessed</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($inProgressCourses, 0, 5) as $course): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($course['thumbnail']): ?>
                                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" 
                                                                     class="rounded me-3" width="60" height="40" 
                                                                     style="object-fit: cover;" 
                                                                     alt="<?php echo htmlspecialchars($course['title']); ?> thumbnail">
                                                            <?php else: ?>
                                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                     style="width: 60px; height: 40px;" 
                                                                     aria-hidden="true">
                                                                    <i class="fas fa-image text-muted"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                                <small class="text-muted">
                                                                    <?php echo $course['completed_lessons'] ?? 0; ?> of <?php echo $course['total_lessons'] ?? 0; ?> lessons
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($course['instructor_name']); ?></td>
                                                    <td>
                                                        <div class="progress progress-modern" role="progressbar" 
                                                             aria-valuenow="<?php echo round($course['progress_percentage'] ?? 0); ?>" 
                                                             aria-valuemin="0" aria-valuemax="100"
                                                             aria-label="Course progress: <?php echo round($course['progress_percentage'] ?? 0); ?>%">
                                                            <div class="progress-bar" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                                        </div>
                                                        <small class="text-muted fw-bold"><?php echo round($course['progress_percentage'] ?? 0); ?>%</small>
                                                    </td>
                                                    <td>
                                                        <time class="text-muted" datetime="<?php echo date('c', strtotime($course['updated_at'] ?? $course['enrolled_at'])); ?>">
                                                            <?php 
                                                            $lastAccessed = $course['updated_at'] ?? $course['enrolled_at'];
                                                            echo date('M j, Y', strtotime($lastAccessed));
                                                            ?>
                                                        </time>
                                                    </td>
                                                    <td>
                                                        <?php if ($course['next_lesson']): ?>
                                                            <a href="lesson.php?course_id=<?php echo $course['id']; ?>&lesson_id=<?php echo $course['next_lesson']['id']; ?>" 
                                                               class="btn btn-primary-modern btn-modern btn-sm"
                                                               onclick="return trackStudyStartAndRedirect(<?php echo $course['id']; ?>)"
                                                               aria-label="Resume <?php echo htmlspecialchars($course['title']); ?>">
                                                                <i class="fas fa-play me-1" aria-hidden="true"></i>Resume
                                                            </a>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="lesson.php?course_id=<?php echo $course['id']; ?>" 
                                                               class="btn btn-primary-modern btn-modern btn-sm"
                                                               onclick="return trackStudyStartAndRedirect(<?php echo $course['id']; ?>)">
                                                                <i class="fas fa-play me-1"></i>Start
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5" role="status" aria-live="polite">
                                    <div class="mb-4">
                                        <i class="fas fa-graduation-cap fa-4x text-muted" style="opacity: 0.5;" aria-hidden="true"></i>
                                    </div>
                                    <h5 class="fw-bold text-muted mb-3">All Courses Completed! 🎉</h5>
                                    <p class="text-muted mb-4">Great job! You've completed all your courses. Ready for more?</p>
                                    <a href="courses.php" class="btn btn-primary-modern btn-modern" aria-label="Browse more courses">
                                        <i class="fas fa-search me-2" aria-hidden="true"></i>Browse Courses
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Recommended Courses Section -->
                    <section class="modern-card mb-4" aria-labelledby="recommended-courses-heading">
                        <header class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0" id="recommended-courses-heading">
                                    <i class="fas fa-star me-2 text-warning" aria-hidden="true"></i>
                                    Recommended for You
                                </h3>
                                <small class="text-muted">AI-powered suggestions</small>
                            </div>
                        </header>
                        <div class="card-body">
                            <?php if (!empty($recommendedCourses)): ?>
                                <div class="table-responsive modern-table">
                                    <table class="table table-hover" role="table" aria-label="Recommended courses">
                                        <thead>
                                            <tr>
                                                <th scope="col">Course</th>
                                                <th scope="col">Category</th>
                                                <th scope="col">Level</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($recommendedCourses as $rec): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if($rec['thumbnail']): ?>
                                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($rec['thumbnail'])); ?>" 
                                                                     class="rounded me-3" width="60" height="40" 
                                                                     style="object-fit: cover;" 
                                                                     alt="<?php echo htmlspecialchars($rec['title']); ?> thumbnail">
                                                            <?php else: ?>
                                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                                     style="width: 60px; height: 40px;" 
                                                                     aria-hidden="true">
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
                                                           class="btn btn-outline-primary btn-modern btn-sm"
                                                           aria-label="View <?php echo htmlspecialchars($rec['title']); ?> course details">
                                                            View Course
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5" role="status" aria-live="polite">
                                    <div class="mb-4">
                                        <i class="fas fa-star text-warning fa-3x" style="opacity: 0.6;" aria-hidden="true"></i>
                                    </div>
                                    <h6 class="fw-bold text-muted mb-3">Explore Popular Courses</h6>
                                    <p class="text-muted mb-4">Complete more courses to get personalized recommendations.</p>
                                    <a href="courses.php" class="btn btn-outline-primary btn-modern" aria-label="Explore course catalog">
                                        <i class="fas fa-compass me-2" aria-hidden="true"></i>Explore Catalog
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </section>

                <!-- Right Column: Sidebar Widgets -->
                <aside class="col-lg-4" role="complementary" aria-labelledby="widgets-heading">
                    <h2 id="widgets-heading" class="visually-hidden">Dashboard Widgets</h2>
                    
                    <!-- Pending Tasks Widget -->
                    <section class="modern-card mb-4" aria-labelledby="pending-tasks-heading">
                        <header class="card-header">
                            <h3 class="card-title mb-0" id="pending-tasks-heading">
                                <i class="fas fa-tasks me-2 text-warning" aria-hidden="true"></i>
                                Pending Tasks
                            </h3>
                        </header>
                        <div class="card-body">
                            <?php if (!empty($pendingTasks)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pendingTasks as $index => $task): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($task['course']); ?></small>
                                            </div>
                                            <a href="<?php echo htmlspecialchars($task['url']); ?>" 
                                               class="btn btn-sm btn-outline-primary btn-modern"
                                               aria-label="Complete <?php echo htmlspecialchars($task['title']); ?>">
                                                <i class="fas fa-play" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-3" role="status" aria-live="polite">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2" aria-hidden="true"></i>
                                    <p class="text-muted mb-0">No pending tasks!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Learning Activity Widget -->
                    <section class="modern-card mb-4" aria-labelledby="activity-heading">
                        <header class="card-header">
                            <h3 class="card-title mb-0" id="activity-heading">
                                <i class="fas fa-chart-line me-2 text-info" aria-hidden="true"></i>
                                Recent Activity
                            </h3>
                        </header>
                        <div class="card-body">
                            <div class="activity-timeline">
                                <div class="activity-item">
                                    <div class="activity-icon bg-primary">
                                        <i class="fas fa-book-open" aria-hidden="true"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Started new course</div>
                                        <div class="activity-time">2 hours ago</div>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon bg-success">
                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Completed lesson</div>
                                        <div class="activity-time">5 hours ago</div>
                                    </div>
                                </div>
                                <div class="activity-item">
                                    <div class="activity-icon bg-info">
                                        <i class="fas fa-award" aria-hidden="true"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Earned certificate</div>
                                        <div class="activity-time">Yesterday</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Quick Actions Widget -->
                    <section class="modern-card mb-4" aria-labelledby="quick-actions-heading">
                        <header class="card-header">
                            <h3 class="card-title mb-0" id="quick-actions-heading">
                                <i class="fas fa-bolt me-2 text-warning" aria-hidden="true"></i>
                                Quick Actions
                            </h3>
                        </header>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="courses.php" class="btn btn-outline-primary btn-modern w-100" aria-label="Browse all courses">
                                    <i class="fas fa-search me-2" aria-hidden="true"></i>Browse Courses
                                </a>
                                <a href="certificates.php" class="btn btn-outline-success btn-modern w-100" aria-label="View certificates">
                                    <i class="fas fa-certificate me-2" aria-hidden="true"></i>My Certificates
                                </a>
                                <a href="profile.php" class="btn btn-outline-info btn-modern w-100" aria-label="View profile">
                                    <i class="fas fa-user me-2" aria-hidden="true"></i>Profile
                                </a>
                                <a href="settings.php" class="btn btn-outline-secondary btn-modern w-100" aria-label="Settings">
                                    <i class="fas fa-cog me-2" aria-hidden="true"></i>Settings
                                </a>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </main>
    </div>
</div>
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks me-2 text-primary"></i>
                                Pending Tasks
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php if (!empty($pendingTasks)): ?>
                                    <?php foreach ($pendingTasks as $task): ?>
                                        <div class="list-group-item px-0 border-0">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px; box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);">
                                                        <i class="fas fa-clipboard-list small"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-truncate"><?php echo htmlspecialchars($task['title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($task['course']); ?></small>
                                                </div>
                                                <a href="<?php echo $task['url']; ?>" class="btn btn-primary-modern btn-modern btn-sm rounded-circle">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle fa-3x text-success" style="opacity: 0.4;"></i>
                                        </div>
                                        <p class="mb-0 fw-bold">All caught up!</p>
                                        <small>No pending tasks</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Profile Summary -->
                    <div class="profile-widget mb-4">
                        <div class="position-relative">
                            <h5 class="text-white mb-4">Profile Summary</h5>
                            <div class="mb-4">
                                <?php if ($userData['profile_image']): ?>
                                    <img src="../<?php echo htmlspecialchars($userData['profile_image']); ?>" 
                                         class="profile-avatar" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="profile-avatar d-flex align-items-center justify-content-center" 
                                         style="font-size: 2rem; font-weight: bold;">
                                        <?php echo strtoupper(substr($userData['full_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h6 class="fw-bold mb-2 text-white"><?php echo htmlspecialchars($userData['full_name']); ?></h6>
                            <p class="mb-4" style="opacity: 0.9;">Student</p>
                            
                            <div class="d-grid gap-2">
                                <a href="profile.php" class="btn btn-light btn-modern">
                                    <i class="fas fa-user-edit me-1"></i>Edit Profile
                                </a>
                                <a href="certificates.php" class="btn btn-outline-light btn-modern">
                                    <i class="fas fa-certificate me-1"></i>My Certificates
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Daily Focus -->
                    <?php if ($dailyFocus): ?>
                        <div class="daily-focus-card mb-4">
                            <div class="position-relative">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-bullseye me-2" style="color: #d97706;"></i>
                                    Daily Focus
                                </h5>
                                <div class="text-center">
                                    <?php if ($dailyFocus['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($dailyFocus['thumbnail'])); ?>" 
                                             class="rounded mb-3" width="100%" height="120" style="object-fit: cover; border-radius: 12px;">
                                    <?php else: ?>
                                        <div class="bg-white rounded mb-3 d-flex align-items-center justify-content-center" 
                                             style="width: 100%; height: 120px; border-radius: 12px;">
                                            <i class="fas fa-book text-muted fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($dailyFocus['course_title']); ?></h6>
                                    <p class="mb-3" style="color: #92400e;"><?php echo htmlspecialchars($dailyFocus['lesson_title']); ?></p>
                                    <a href="lesson.php?course_id=<?php echo $dailyFocus['course_id']; ?>" 
                                       class="btn btn-dark btn-modern w-100" 
                                       style="background: linear-gradient(135deg, #d97706 0%, #92400e 100%);">
                                        <i class="fas fa-play me-1"></i>Continue Learning
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Enhanced Quick Stats -->
                    <div class="modern-card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2 text-info"></i>
                                Quick Stats
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="p-3 bg-light rounded" style="border-radius: 15px; transition: var(--transition-smooth);"
                                         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'"
                                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        <i class="fas fa-fire text-danger fa-2x mb-2"></i>
                                        <div class="h5 mb-0 fw-bold"><?php echo $learningStreak; ?></div>
                                        <small class="text-muted">Day Streak</small>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="p-3 bg-light rounded" style="border-radius: 15px; transition: var(--transition-smooth);"
                                         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'"
                                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                        <i class="fas fa-clock text-primary fa-2x mb-2"></i>
                                        <div class="h5 mb-0 fw-bold"><?php echo $totalStudyHours; ?>h</div>
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
</div>

<!-- Floating Action Button -->
<div class="fab-container">
    <button class="fab" title="Quick Actions">
        <i class="fas fa-plus"></i>
    </button>
</div>

</body>
</html>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>

<!-- Enhanced Dashboard JavaScript -->
<script>
$(document).ready(function() {
    // Enhanced animations for modern stat cards
    $('.modern-stat-card').on('mouseenter', function() {
        $(this).find('.modern-stat-icon').addClass('pulse');
        $(this).css('transform', 'translateY(-10px) scale(1.02)');
    }).on('mouseleave', function() {
        $(this).find('.modern-stat-icon').removeClass('pulse');
        $(this).css('transform', 'translateY(0) scale(1)');
    });
    
    // Staggered fade-in animation for stat cards
    $('.modern-stat-card').each(function(index) {
        $(this).css('opacity', '0');
        $(this).css('transform', 'translateY(30px)');
        setTimeout(() => {
            $(this).animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, 600, 'easeOutCubic');
        }, 100 * index);
    });
    
    // Number counting animation for stats
    $('.modern-stat-card h3').each(function() {
        const $this = $(this);
        const countTo = parseInt($this.text().replace(/[^\d.]/g, ''));
        
        if (!isNaN(countTo)) {
            const originalText = $this.text();
            $this.text('0');
            
            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 1500,
                easing: 'easeOutCubic',
                step: function() {
                    if (originalText.includes('h')) {
                        $this.text(Math.floor(this.countNum) + 'h');
                    } else {
                        $this.text(Math.floor(this.countNum));
                    }
                },
                complete: function() {
                    $this.text(originalText);
                }
            });
        }
    });
    
    // Parallax effect for dashboard header
    $(window).on('scroll', function() {
        const scrolled = $(window).scrollTop();
        $('.dashboard-header').css('transform', `translateY(${scrolled * 0.5}px)`);
        $('.dashboard-header h1, .dashboard-header p').css('transform', `translateY(${scrolled * 0.3}px)`);
    });
    
    // Enhanced button ripple effects
    $('.btn-modern').on('click', function(e) {
        const button = $(this);
        const ripple = $('<span class="ripple"></span>');
        
        const x = e.pageX - button.offset().left;
        const y = e.pageY - button.offset().top;
        
        ripple.css({
            left: x + 'px',
            top: y + 'px'
        });
        
        button.append(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
    
    // Floating action button functionality
    $('.fab').on('click', function() {
        $(this).toggleClass('active');
        
        // Toggle quick actions menu
        const quickActions = `
            <div class="fab-menu">
                <a href="my-courses.php" class="fab-menu-item">
                    <i class="fas fa-book"></i>
                    <span>My Courses</span>
                </a>
                <a href="courses.php" class="fab-menu-item">
                    <i class="fas fa-search"></i>
                    <span>Browse Courses</span>
                </a>
                <a href="certificates.php" class="fab-menu-item">
                    <i class="fas fa-certificate"></i>
                    <span>Certificates</span>
                </a>
                <a href="profile.php" class="fab-menu-item">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
        `;
        
        if ($(this).hasClass('active')) {
            $('body').append(quickActions);
            setTimeout(() => {
                $('.fab-menu').addClass('show');
            }, 100);
        } else {
            $('.fab-menu').removeClass('show');
            setTimeout(() => {
                $('.fab-menu').remove();
            }, 300);
        }
    });
    
    // Close FAB menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.fab, .fab-menu').length) {
            $('.fab').removeClass('active');
            $('.fab-menu').removeClass('show');
            setTimeout(() => {
                $('.fab-menu').remove();
            }, 300);
        }
    });
    
    // Enhanced table row hover effects
    $('.modern-table tbody tr').on('mouseenter', function() {
        $(this).find('td').css('transform', 'scale(1.01)');
    }).on('mouseleave', function() {
        $(this).find('td').css('transform', 'scale(1)');
    });
    
    // Auto-refresh functionality for real-time updates
    let autoRefreshInterval;
    
    function startAutoRefresh() {
        autoRefreshInterval = setInterval(() => {
            // Refresh dashboard data without full page reload
            $.ajax({
                url: 'api/dashboard_stats.php',
                method: 'GET',
                success: function(data) {
                    if (data.success) {
                        // Update stats with animation
                        updateStatsWithAnimation(data.stats);
                    }
                },
                error: function() {
                    console.log('Auto-refresh failed');
                }
            });
        }, 30000); // Refresh every 30 seconds
    }
    
    function updateStatsWithAnimation(stats) {
        // Animate stat updates
        $('.modern-stat-card h3').each(function(index) {
            const $this = $(this);
            const oldValue = parseInt($this.text().replace(/[^\d.]/g, ''));
            const newValue = Object.values(stats)[index];
            
            if (oldValue !== newValue) {
                $this.addClass('highlight');
                setTimeout(() => {
                    $this.text(newValue);
                    $this.removeClass('highlight');
                }, 300);
            }
        });
    }
    
    // Start auto-refresh when page is visible
    if (!document.hidden) {
        startAutoRefresh();
    }
    
    // Pause auto-refresh when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(autoRefreshInterval);
        } else {
            startAutoRefresh();
        }
    });
    
    // Enhanced loading states
    function showLoadingState(element) {
        element.addClass('loading');
        const originalContent = element.html();
        element.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        element.data('original-content', originalContent);
    }
    
    function hideLoadingState(element) {
        element.removeClass('loading');
        element.html(element.data('original-content'));
    }
    
    // Apply loading states to all action buttons
    $('.btn-modern').on('click', function() {
        if (!$(this).hasClass('no-loading')) {
            showLoadingState($(this));
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + K for quick search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            // Focus on search input or show search modal
            $('#searchInput').focus();
        }
        
        // Ctrl/Cmd + N for new course
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = 'courses.php';
        }
        
        // ESC to close FAB menu
        if (e.key === 'Escape') {
            $('.fab').removeClass('active');
            $('.fab-menu').removeClass('show');
            setTimeout(() => {
                $('.fab-menu').remove();
            }, 300);
        }
    });
    
    // Performance monitoring
    const performanceData = {
        pageLoadTime: performance.now(),
        interactions: 0,
        lastInteraction: Date.now()
    };
    
    // Track user interactions
    $(document).on('click', 'button, a, .modern-stat-card', function() {
        performanceData.interactions++;
        performanceData.lastInteraction = Date.now();
        
        // Log performance data every 10 interactions
        if (performanceData.interactions % 10 === 0) {
            console.log('Performance Data:', performanceData);
        }
    });
    
    // Enhanced tooltips
    $('.modern-stat-card').each(function() {
        const $card = $(this);
        const title = $card.find('p').text();
        const value = $card.find('h3').text();
        
        $card.attr('title', `${title}: ${value}`).attr('data-bs-toggle', 'tooltip');
    });
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Welcome animation on page load
    setTimeout(() => {
        $('.dashboard-header').addClass('loaded');
    }, 500);
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800, 'easeOutCubic');
        }
    });
});
</script>

<!-- FAB Menu Styles -->
<style>
.fab-menu {
    position: fixed;
    bottom: 100px;
    right: 2rem;
    z-index: 999;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s ease;
    pointer-events: none;
}

.fab-menu.show {
    opacity: 1;
    transform: scale(1);
    pointer-events: auto;
}

.fab-menu-item {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    margin-bottom: 0.5rem;
    background: white;
    border-radius: 50px;
    text-decoration: none;
    color: #2d3748;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    min-width: 200px;
}

.fab-menu-item:hover {
    background: var(--primary-gradient);
    color: white;
    transform: translateX(-10px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.fab-menu-item i {
    margin-right: 1rem;
    width: 20px;
    text-align: center;
}

.fab-menu-item span {
    font-weight: 600;
}

.highlight {
    animation: highlightPulse 0.6s ease;
}

@keyframes highlightPulse {
    0%, 100% {
        background: rgba(102, 126, 234, 0.1);
    }
    50% {
        background: rgba(102, 126, 234, 0.2);
    }
}

.loading {
    pointer-events: none;
    opacity: 0.7;
}

.dashboard-header.loaded {
    animation: headerGlow 2s ease;
}

@keyframes headerGlow {
    0%, 100% {
        box-shadow: var(--card-shadow);
    }
    50% {
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
    }
}

.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.6);
    transform: scale(0);
    animation: rippleAnimation 0.6s ease-out;
    pointer-events: none;
}

@keyframes rippleAnimation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
</style>

</body>
</html>
