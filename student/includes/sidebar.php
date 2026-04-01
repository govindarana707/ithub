<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireStudent();

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = pathinfo($current_page, PATHINFO_FILENAME);

// Get student stats for badges
$studentId = $_SESSION['user_id'];
$conn = connectDB();

// Get enrolled courses count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT course_id) as count FROM enrollments_new WHERE user_id = ? AND status = 'active'");
if ($stmt === false) {
    $enrolledCount = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $enrolledCount = $stmt->get_result()->fetch_assoc()['count'];
}

// Get completed courses count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT course_id) as count FROM enrollments_new WHERE user_id = ? AND status = 'active' AND progress_percentage >= 100");
if ($stmt === false) {
    $completedCount = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $completedCount = $stmt->get_result()->fetch_assoc()['count'];
}

// Get certificates count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM certificates WHERE student_id = ?");
if ($stmt === false) {
    $certificateCount = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $certificateCount = $stmt->get_result()->fetch_assoc()['count'];
}

// Get pending quizzes count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM quizzes q
    JOIN enrollments_new e ON q.course_id = e.course_id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE e.user_id = ? AND e.status = 'active' 
    AND (qa.id IS NULL OR qa.score < q.passing_score)
");
if ($stmt === false) {
    $pendingQuizCount = 0;
} else {
    $stmt->bind_param("ii", $studentId, $studentId);
    $stmt->execute();
    $pendingQuizCount = $stmt->get_result()->fetch_assoc()['count'];
}

// Get completed quizzes count
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT qa.quiz_id) as count 
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    JOIN enrollments_new e ON q.course_id = e.course_id
    WHERE qa.student_id = ? AND qa.score >= q.passing_score AND e.status = 'active'
");
if ($stmt === false) {
    $completedQuizCount = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $completedQuizCount = $stmt->get_result()->fetch_assoc()['count'];
}

// Get unread notifications count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
if ($stmt === false) {
    $notificationCount = 0;
} else {
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $notificationCount = $stmt->get_result()->fetch_assoc()['count'];
}

$conn->close();
?>

<div class="list-group sidebar-modern">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
    </a>
    <a href="courses.php" class="list-group-item list-group-item-action <?php echo $current_page === 'courses' ? 'active' : ''; ?>">
        <i class="fas fa-book me-2"></i> Browse Courses
    </a>
    <a href="my-courses.php" class="list-group-item list-group-item-action <?php echo $current_page === 'my-courses' ? 'active' : ''; ?>">
        <i class="fas fa-book-open me-2"></i> My Courses
        <span class="badge bg-primary float-end"><?php echo $enrolledCount; ?></span>
    </a>
    
    <!-- Quiz Section -->
    <div class="list-group-item list-group-item-action quiz-section-header">
        <i class="fas fa-brain me-2"></i> Quizzes
        <small class="text-muted ms-auto">2 items</small>
    </div>
    <div class="quiz-submenu">
        <a href="quizzes.php" class="list-group-item list-group-item-action <?php echo $current_page === 'quizzes' ? 'active' : ''; ?>">
            <i class="fas fa-list me-2"></i> All Quizzes
            <?php if ($pendingQuizCount > 0): ?>
                <span class="badge bg-info float-end"><?php echo $pendingQuizCount; ?></span>
            <?php endif; ?>
        </a>
        <a href="quiz-results.php" class="list-group-item list-group-item-action <?php echo $current_page === 'quiz-results' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar me-2"></i> Results
            <?php if ($completedQuizCount > 0): ?>
                <span class="badge bg-success float-end"><?php echo $completedQuizCount; ?></span>
            <?php endif; ?>
        </a>
    </div>
    
    <a href="certificates.php" class="list-group-item list-group-item-action <?php echo $current_page === 'certificates' ? 'active' : ''; ?>">
        <i class="fas fa-certificate me-2"></i> Certificates
        <span class="badge bg-success float-end"><?php echo $certificateCount; ?></span>
    </a>
    <a href="discussions.php" class="list-group-item list-group-item-action <?php echo $current_page === 'discussions' ? 'active' : ''; ?>">
        <i class="fas fa-comments me-2"></i> Discussions
    </a>
    <a href="notifications.php" class="list-group-item list-group-item-action <?php echo $current_page === 'notifications' ? 'active' : ''; ?>">
        <i class="fas fa-bell me-2"></i> Notifications
        <?php if ($notificationCount > 0): ?>
            <span class="badge bg-warning float-end"><?php echo $notificationCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="profile.php" class="list-group-item list-group-item-action <?php echo $current_page === 'profile' ? 'active' : ''; ?>">
        <i class="fas fa-user me-2"></i> Profile
    </a>
    <a href="settings.php" class="list-group-item list-group-item-action <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
        <i class="fas fa-cog me-2"></i> Settings
    </a>
    <hr class="my-3">
    <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
</div>

<style>
.sidebar-modern {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-modern .list-group-item-action {
    border: none;
    padding: 12px 16px;
    margin-bottom: 2px;
    border-radius: 8px;
    padding: 15px 20px;
    transition: all 0.3s ease;
    position: relative;
    background: white;
}

.sidebar-modern .list-group-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    transform: translateX(5px);
}

.sidebar-modern .list-group-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
}

.sidebar-modern .list-group-item.active:hover {
    transform: translateX(0);
}

.sidebar-modern .badge {
    border-radius: 20px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Mobile sidebar adjustments */
@media (max-width: 768px) {
    .sidebar-modern {
        border-radius: 0;
        box-shadow: none;
        margin: 0;
    }
    
    .sidebar-modern .list-group-item {
        border-radius: 0;
        padding: 20px;
    }
}
</style>
