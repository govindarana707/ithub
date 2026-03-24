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

// --- 1. ADVANCED DATA FETCHING ---

// Enrolled Courses & Progress
$enrolledCourses = $course->getEnrolledCourses($userId);
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
$inProgressCourses = $totalEnrolled - $completedCourses;

// Learning Streak
$streakStmt = $conn->prepare("
    SELECT COUNT(DISTINCT DATE(last_accessed_at)) as streak_days
    FROM lesson_progress
    WHERE student_id = ? 
    AND last_accessed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
if (!$streakStmt) {
    error_log("Streak query prepare failed: " . $conn->error);
    $learningStreak = 0;
} else {
    $streakStmt->bind_param('i', $userId);
    $streakStmt->execute();
    $learningStreak = $streakStmt->get_result()->fetch_assoc()['streak_days'] ?? 0;
}

// Total Study Time
$timeStmt = $conn->prepare("SELECT SUM(time_spent_minutes) as total_minutes FROM lesson_progress WHERE student_id = ?");
if (!$timeStmt) {
    error_log("Time query prepare failed: " . $conn->error);
    $totalStudyHours = 0;
} else {
    $timeStmt->bind_param('i', $userId);
    $timeStmt->execute();
    $totalStudyHours = round(($timeStmt->get_result()->fetch_assoc()['total_minutes'] ?? 0) / 60, 1);
}

// --- 2. INTELLIGENT FEATURES ---

// Daily Focus: Find the most relevant lesson to continue
$dailyFocus = null;
// Try to find the last accessed unfinished lesson
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
if (!$focusStmt) {
    error_log("Prepare failed: " . $conn->error);
    $dailyFocus = null;
    $dailyFocusResult = null;
} else {
    $focusStmt->bind_param('i', $userId);
    $focusStmt->execute();
    $dailyFocusResult = $focusStmt->get_result();
}

if ($dailyFocusResult && $dailyFocusResult->num_rows > 0) {
    $dailyFocus = $dailyFocusResult->fetch_assoc();
} elseif (!empty($enrolledCourses)) {
    // Fallback: First unfinished course
    foreach ($enrolledCourses as $ec) {
        if (($ec['progress_percentage'] ?? 0) < 100) {
            $dailyFocus = [
                'course_id' => $ec['id'],
                'course_title' => $ec['title'],
                'lesson_title' => 'Continue your progress',
                'duration' => 'Active',
                'thumbnail' => $ec['thumbnail']
            ];
            break;
        }
    }
}

// AI Recommendations (KNN)
$recommender = new RecommendationSystem();
$recommendedCourses = $recommender->getKNNRecommendations($userId, 2);

// Pending Tasks (Quizzes due)
$pendingTasks = [];
$pendingQuizzesStmt = $conn->prepare("
    SELECT q.id, q.title, c.title as course_title
    FROM quizzes q
    JOIN enrollments e ON q.course_id = e.course_id
    JOIN courses c ON q.course_id = c.id
    LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
    WHERE e.student_id = ? AND (qa.id IS NULL OR qa.score < 70)
    LIMIT 3
");
if (!$pendingQuizzesStmt) {
    error_log("Pending quizzes prepare failed: " . $conn->error);
    $pendingQuizzes = [];
} else {
    $pendingQuizzesStmt->bind_param('ii', $userId, $userId);
    $pendingQuizzesStmt->execute();
    $pendingQuizzes = $pendingQuizzesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

foreach ($pendingQuizzes as $pq) {
    $pendingTasks[] = [
        'title' => $pq['title'],
        'course' => $pq['course_title'],
        'url' => 'quiz.php?quiz_id=' . $pq['id']
    ];
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

// Greeting based on time
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");
?>

<div class="container-fluid py-4 px-4">

    <!-- Top Section: Welcome & Daily Focus -->
    <div class="row g-4 mb-4">
        <!-- Welcome & Stats -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <?php echo $greeting; ?>, <?php echo htmlspecialchars($userData['full_name']); ?>! 👋
                    </h2>
                    <p class="text-muted mb-0">You're on a <span class="fw-bold text-primary"><?php echo $learningStreak; ?> day streak</span>. Keep it up!</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card bg-white h-100 border-0 shadow-sm p-3 rounded-4 d-flex align-items-center">
                        <div class="icon-wrapper bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                            <i class="fas fa-book-open fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $inProgressCourses; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Active Courses</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-white h-100 border-0 shadow-sm p-3 rounded-4 d-flex align-items-center">
                        <div class="icon-wrapper bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                            <i class="fas fa-check-circle fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $completedCourses; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-white h-100 border-0 shadow-sm p-3 rounded-4 d-flex align-items-center">
                        <div class="icon-wrapper bg-warning bg-opacity-10 text-warning rounded-circle p-3 me-3">
                            <i class="fas fa-clock fa-lg"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $totalStudyHours; ?>h</h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Study Time</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Focus Card -->
        <div class="col-lg-4">
            <div class="daily-focus-card h-100 elevation-2 d-flex flex-column justify-content-center">
                <div class="position-relative z-2">
                    <div class="badge bg-white text-dark mb-3 px-3 py-2 rounded-pill fw-bold">
                        <i class="fas fa-bullseye text-danger me-2"></i>Daily Focus
                    </div>

                    <?php if ($dailyFocus): ?>
                        <h4 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($dailyFocus['lesson_title']); ?></h4>
                        <p class="text-white-50 mb-4"><?php echo htmlspecialchars($dailyFocus['course_title']); ?></p>

                        <div class="d-flex align-items-center justify-content-between mt-auto">
                            <div class="d-flex align-items-center text-white-50 small">
                                <i class="far fa-clock me-2"></i> <?php echo $dailyFocus['duration'] ?? 'Active'; ?>
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
            
            <!-- Continue Learning Section -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Continue Learning</h5>
                    <a href="my-courses.php" class="text-primary text-decoration-none small fw-bold">View All</a>
                </div>

                <?php if (!empty($enrolledCourses)): ?>
                    <div class="row g-3">
                        <?php foreach (array_slice($enrolledCourses, 0, 3) as $course): ?>
                            <?php if (($course['progress_percentage'] ?? 0) < 100): ?>
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-sm p-3 rounded-4 hover-lift transition-all">
                                        <div class="d-flex align-items-center">
                                            <!-- Thumbnail -->
                                            <div class="flex-shrink-0 position-relative rounded-3 overflow-hidden" style="width: 140px; height: 90px;">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" class="w-100 h-100 object-fit-cover" alt="Course">
                                                <?php else: ?>
                                                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-secondary">
                                                        <i class="fas fa-image fa-2x"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-75 text-white text-center small py-1">
                                                    <?php echo round($course['progress_percentage'] ?? 0); ?>% 
                                                </div>
                                            </div>

                                            <!-- Content -->
                                            <div class="flex-grow-1 ms-4">
                                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <div class="progress mt-2" style="height: 6px; width: 60%; background-color: #e9ecef;">
                                                    <div class="progress-bar bg-primary" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                                                </div>
                                                <div class="mt-2 text-muted small">
                                                    <i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </div>
                                            </div>

                                            <div class="ms-3">
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary rounded-circle btn-lg text-white shadow-sm">
                                                    <i class="fas fa-play fa-sm"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                     <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3 opacity-50"></i>
                        <h5 class="text-dark fw-bold">Start your journey</h5>
                        <p class="text-muted">Enroll in a course to start learning.</p>
                        <a href="courses.php" class="btn btn-primary rounded-pill px-4">Browse Catalog</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Smart Suggestions (AI Powered) -->
            <div class="mb-4">
                <h5 class="fw-bold text-dark mb-3">Recommended for You <i class="fas fa-magic text-primary ms-1"></i></h5>
                <div class="row g-3">
                    <?php if (!empty($recommendedCourses)): ?>
                        <?php foreach($recommendedCourses as $rec): ?>
                            <div class="col-md-6">
                                <div class="suggestion-card bg-white p-3 rounded-4 shadow-sm h-100 border-start border-4 border-primary">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            <?php if($rec['thumbnail']): ?>
                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($rec['thumbnail'])); ?>" class="rounded-3" width="60" height="60" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-lightbulb text-warning fa-lg"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1 line-clamp-1"><?php echo htmlspecialchars($rec['title']); ?></h6>
                                            <p class="text-muted small mb-2 line-clamp-2"><?php echo htmlspecialchars($rec['description'] ?? 'Based on your learning history.'); ?></p>
                                            <a href="course-details.php?id=<?php echo $rec['id']; ?>" class="text-primary small fw-bold text-decoration-none">View Course <i class="fas fa-arrow-right list-inline-item"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback Recommendation -->
                         <div class="col-md-12">
                            <div class="suggestion-card bg-white p-3 rounded-4 shadow-sm">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-star text-warning fa-2x me-3"></i>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Explore Popular Courses</h6>
                                        <p class="text-muted small mb-0">We need more data to give you personalized suggestions.</p>
                                    </div>
                                    <a href="courses.php" class="btn btn-outline-primary btn-sm rounded-pill ms-auto px-4">Explore</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar Widgets -->
        <div class="col-lg-4">
            
            <!-- Pending Tasks -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 rounded-top-4">
                    <h6 class="fw-bold text-dark m-0"><i class="fas fa-tasks me-2 text-primary"></i>Pending Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($pendingTasks)): ?>
                        <?php foreach ($pendingTasks as $task): ?>
                            <div class="task-item px-3 py-3 border-bottom d-flex align-items-center">
                                <div class="task-checkbox me-3">
                                    <i class="fas fa-check text-white small"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                     <a href="<?php echo $task['url']; ?>" class="d-block text-dark fw-bold text-decoration-none text-truncate">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                     </a>
                                     <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($task['course']); ?></small>
                                </div>
                                <a href="<?php echo $task['url']; ?>" class="btn btn-sm btn-light text-primary rounded-circle ms-2">
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
            <div class="card border-0 shadow-sm rounded-4 bg-white text-center p-4">
                <div class="position-relative d-inline-block mb-3 mx-auto">
                    <?php if ($userData['profile_image']): ?>
                        <img src="../<?php echo htmlspecialchars($userData['profile_image']); ?>" class="rounded-circle shadow-sm" width="90" height="90" style="object-fit: cover; border: 3px solid #f8f9fa;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm mx-auto" style="width: 90px; height: 90px; font-size: 2.5rem; color: #667eea; font-weight: bold;">
                            <?php echo strtoupper(substr($userData['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                     <div class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle p-2"></div>
                </div>
                
                <h5 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($userData['full_name']); ?></h5>
                <p class="text-muted small mb-3">Student</p>
                
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-outline-light text-dark border-1 shadow-sm rounded-pill hover-lift">
                        <i class="fas fa-user-edit me-2 text-primary"></i> Edit Profile
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Scoped overrides/additions for this dashboard specifically if needed, 
   though most should be in ux_additions.css */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
