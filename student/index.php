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
$conn = (new Database())->getConnection();

// --- 1. DATA FETCHING ---

// Enrolled Courses
$enrolledCourses = $course->getEnrolledCourses($userId);

// Statistics
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
$inProgressCourses = $totalEnrolled - $completedCourses;

// Quiz Stats
$quizStmt = $conn->prepare("
    SELECT COUNT(*) as total_quizzes, 
           AVG(score) as avg_score,
           SUM(CASE WHEN score >= 70 THEN 1 ELSE 0 END) as passed_quizzes
    FROM quiz_attempts 
    WHERE student_id = ?
");
if ($quizStmt) {
    $quizStmt->bind_param('i', $userId);
    $quizStmt->execute();
    $quizStats = $quizStmt->get_result()->fetch_assoc();
} else {
    $quizStats = ['total_quizzes' => 0, 'avg_score' => 0, 'passed_quizzes' => 0];
}

// Learning Streak
$streakStmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(last_accessed_at)) as streak_days
    FROM lesson_progress
    WHERE student_id = ? 
    AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
if ($streakStmt) {
    $streakStmt->bind_param('i', $userId);
    $streakStmt->execute();
    $streakData = $streakStmt->get_result()->fetch_assoc();
    $learningStreak = $streakData['streak_days'] ?? 0;
} else {
    $learningStreak = 0;
}

// Total Study Time
$timeStmt = $conn->prepare("
    SELECT SUM(time_spent_minutes) as total_minutes
    FROM lesson_progress
    WHERE student_id = ?
");
if ($timeStmt) {
    $timeStmt->bind_param('i', $userId);
    $timeStmt->execute();
    $timeData = $timeStmt->get_result()->fetch_assoc();
    $totalStudyHours = round(($timeData['total_minutes'] ?? 0) / 60, 1);
} else {
    $totalStudyHours = 0;
}

// --- DAILY FOCUS LOGIC ---
$dailyFocus = null;
// Find the most recently accessed course that is NOT complete
$focusStmt = $conn->prepare("
    SELECT c.id as course_id, c.title as course_title, c.thumbnail,
           l.id as lesson_id, l.title as lesson_title, l.duration
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE lp.student_id = ? AND lp.completed = 0
    ORDER BY lp.last_accessed_at DESC
    LIMIT 1
");
if ($focusStmt) {
    $focusStmt->bind_param('i', $userId);
    $focusStmt->execute();
    $dailyFocusResult = $focusStmt->get_result();
    if ($dailyFocusResult && $dailyFocusResult->num_rows > 0) {
        $dailyFocus = $dailyFocusResult->fetch_assoc();
    }
} else {
    // Fallback: Get first lesson of any enrolled course that starts
    // This is simple fallback logic for demo
    if (!empty($enrolledCourses)) {
        foreach ($enrolledCourses as $ec) {
            if (($ec['progress_percentage'] ?? 0) < 100) {
                $dailyFocus = [
                    'course_id' => $ec['id'],
                    'course_title' => $ec['title'],
                    'thumbnail' => $ec['thumbnail'],
                    'lesson_title' => 'Continue your progress',
                    'duration' => 'Active'
                ];
                break;
            }
        }
    }
}

// --- PENDING TASKS ---
$pendingTasks = [];
// 1. Pending Quizzes (Simplification: Quizzes in enrolled courses not yet attempted)
// checking for quizzes in enrolled courses that user hasn't passed
$pendingQuizzesStmt = $conn->prepare("
    SELECT q.id, q.title, c.title as course_title
    FROM quizzes q
    JOIN enrollments e ON q.course_id = e.course_id
    JOIN courses c ON q.course_id = c.id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE e.student_id = ? AND (qa.id IS NULL OR qa.score < 70)
    LIMIT 3
");
if ($pendingQuizzesStmt) {
    $pendingQuizzesStmt->bind_param('ii', $userId, $userId);
    $pendingQuizzesStmt->execute();
    $pendingQuizzes = $pendingQuizzesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $pendingQuizzes = [];
}

foreach ($pendingQuizzes as $pq) {
    $pendingTasks[] = [
        'type' => 'quiz',
        'title' => $pq['title'],
        'course' => $pq['course_title'],
        'url' => 'quiz.php?quiz_id=' . $pq['id']
    ];
}

require_once '../includes/universal_header.php';

// Time Greeting Logic
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}
?>

<div class="container-fluid py-4">

    <!-- Top Section: Welcome & Daily Focus -->
    <div class="row g-4 mb-4">
        <!-- Welcome & Stats -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <?php echo $greeting; ?>, <?php echo htmlspecialchars($userData['full_name']); ?>! 👋
                    </h2>
                    <p class="text-muted mb-0">You're on a <span
                            class="fw-bold text-gradient-primary"><?php echo $learningStreak; ?> day streak</span>. Keep
                        it up!</p>
                </div>
                <div class="d-none d-md-block">
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-cog me-1"></i> Preferences
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="dashboard-card card border-0 h-100 elevation-1 hover-lift">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-wrapper bg-light text-primary rounded-circle p-2 me-3">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h6 class="text-muted mb-0 text-uppercase small fw-bold">Active Courses</h6>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $inProgressCourses; ?></h3>
                            <small class="text-muted">/ <?php echo $totalEnrolled; ?> Enrolled</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card card border-0 h-100 elevation-1 hover-lift">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-wrapper bg-light text-success rounded-circle p-2 me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h6 class="text-muted mb-0 text-uppercase small fw-bold">Completed</h6>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $completedCourses; ?></h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> Top 10%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card card border-0 h-100 elevation-1 hover-lift">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <div class="icon-wrapper bg-light text-warning rounded-circle p-2 me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h6 class="text-muted mb-0 text-uppercase small fw-bold">Study Time</h6>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $totalStudyHours; ?>h</h3>
                            <small class="text-muted">Lifetime</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Focus Card -->
        <div class="col-lg-4">
            <div class="daily-focus-card h-100 elevation-2">
                <div class="position-relative z-1">
                    <div class="badge bg-white text-dark mb-3 px-3 py-2 rounded-pill fw-bold">
                        <i class="fas fa-bullseye text-danger me-2"></i>Daily Focus
                    </div>

                    <?php if ($dailyFocus): ?>
                        <h4 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($dailyFocus['lesson_title']); ?>
                        </h4>
                        <p class="text-white-50 mb-4"><?php echo htmlspecialchars($dailyFocus['course_title']); ?></p>

                        <div class="d-flex align-items-center justify-content-between mt-auto">
                            <div class="d-flex align-items-center text-white-50 small">
                                <i class="far fa-clock me-2"></i> <?php echo $dailyFocus['duration'] ?? '15 min'; ?>
                            </div>
                            <a href="<?php echo isset($dailyFocus['lesson_id']) ? "lesson.php?course_id={$dailyFocus['course_id']}&lesson_id={$dailyFocus['lesson_id']}" : "lesson.php?course_id={$dailyFocus['course_id']}"; ?>"
                                class="btn btn-primary rounded-pill px-4 shadow-sm">
                                Start Now <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <h4 class="text-white fw-bold mb-2">All Caught Up!</h4>
                        <p class="text-white-50 mb-4">You have no active lessons. Browse new courses?</p>
                        <a href="courses.php" class="btn btn-light text-primary rounded-pill px-4">
                            Browse Courses
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: My Learning & Activity -->
        <div class="col-lg-8">
            <!-- Continue Learning -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Continue Learning</h5>
                    <a href="my-courses.php" class="text-primary text-decoration-none small fw-bold">View All</a>
                </div>

                <?php if (!empty($enrolledCourses)): ?>
                    <div class="row g-3">
                        <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
                            <?php if (($course['progress_percentage'] ?? 0) < 100): ?>
                                <div class="col-md-12">
                                    <div class="card border-0 elevation-1 hover-lift p-3 rounded-4">
                                        <div class="d-flex align-items-center">
                                            <!-- Thumbnail -->
                                            <div class="flex-shrink-0 position-relative rounded-3 overflow-hidden"
                                                style="width: 120px; height: 80px;">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="../<?php echo htmlspecialchars($course['thumbnail']); ?>"
                                                        class="w-100 h-100 object-fit-cover" alt="Course">
                                                <?php else: ?>
                                                    <div
                                                        class="w-100 h-100 bg-gradient d-flex align-items-center justify-content-center text-white">
                                                        <i class="fas fa-book"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-50 text-white text-center small py-1"
                                                    style="font-size: 0.7rem;">
                                                    <?php echo round($course['progress_percentage'] ?? 0); ?>%
                                                </div>
                                            </div>

                                            <!-- Content -->
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?>
                                                </h6>
                                                <div class="progress" style="height: 6px; width: 60%;">
                                                    <div class="progress-bar bg-success"
                                                        style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                                </div>
                                            </div>

                                            <!-- Action -->
                                            <div class="ms-3">
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>"
                                                    class="btn btn-icon btn-light rounded-circle text-primary">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 bg-white rounded-4 elevation-1">
                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3 opacity-50"></i>
                        <p class="text-muted">Start your journey today!</p>
                        <a href="courses.php" class="btn btn-primary rounded-pill">Explore Courses</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Smart Suggestions -->
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-3">Recommended for You</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="suggestion-card elevation-1 h-100">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-lightbulb text-warning fa-2x me-3"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Boost your PHP Skills</h6>
                                    <p class="text-muted small mb-2">Based on your recent activity in Web Development.
                                    </p>
                                    <a href="courses.php?category=php"
                                        class="text-primary small fw-bold text-decoration-none">Explore PHP Courses <i
                                            class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="suggestion-card elevation-1 h-100 border-start-0 border-top-0 border-bottom-0 border-end border-info"
                            style="border-left: 4px solid #17a2b8 !important;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-trophy text-info fa-2x me-3"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">Challenge Yourself</h6>
                                    <p class="text-muted small mb-2">Take a skill assessment to verify your knowledge.
                                    </p>
                                    <a href="quizzes.php" class="text-info small fw-bold text-decoration-none">Take a
                                        Quiz <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar Widgets -->
        <div class="col-lg-4">

            <!-- Pending Tasks -->
            <div class="card border-0 elevation-2 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark m-0"><i class="fas fa-tasks me-2 text-primary"></i>Pending Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($pendingTasks)): ?>
                        <?php foreach ($pendingTasks as $task): ?>
                            <div class="task-item">
                                <div class="task-checkbox" onclick="completeTask(this)">
                                    <i class="fas fa-check text-white small"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-dark small fw-bold"><?php echo htmlspecialchars($task['title']); ?></p>
                                    <small class="text-muted"
                                        style="font-size: 0.75rem;"><?php echo htmlspecialchars($task['course']); ?></small>
                                </div>
                                <a href="<?php echo $task['url']; ?>" class="btn btn-sm btn-light text-primary rounded-circle">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="fas fa-check-circle mb-2 text-success opacity-50 fa-2x"></i>
                            <p class="mb-0">All caught up!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Widget -->
            <div class="card border-0 elevation-1 bg-light">
                <div class="card-body text-center p-4">
                    <div class="position-relative d-inline-block mb-3">
                        <?php if ($userData && $userData['profile_image']): ?>
                            <img src="../<?php echo htmlspecialchars($userData['profile_image']); ?>"
                                class="rounded-circle shadow-sm" width="80" height="80" alt="Profile">
                        <?php else: ?>
                            <div class="rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm"
                                style="width: 80px; height: 80px; margin: 0 auto; font-size: 2rem; color: #667eea; font-weight: bold;">
                                <?php echo strtoupper(substr($userData['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1"
                            style="width: 20px; height: 20px;"></div>
                    </div>
                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($userData['full_name']); ?></h6>
                    <small class="text-muted">Student</small>

                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="profile.php" class="btn btn-sm btn-white elevation-1 text-dark">
                            <i class="fas fa-user-edit me-1"></i> Edit
                        </a>
                        <a href="../logout.php" class="btn btn-sm btn-white elevation-1 text-danger">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Simple UI interaction for task completion demo
    function completeTask(element) {
        element.classList.toggle('checked');
        // In a real app, you would make an AJAX call here
        if (element.classList.contains('checked')) {
            // Toast removed to avoid undefined error if not loaded yet, trusting header load
            if (typeof toastr !== 'undefined') {
                toastr.success('Task marked as done!');
            }
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>