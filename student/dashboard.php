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

// Ensure required tables exist for dashboard functionality
$conn->query("CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    duration_minutes INT DEFAULT 0,
    lesson_order INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS lesson_progress (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    time_limit_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 70,
    is_published BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_id INT NOT NULL,
    score INT DEFAULT 0,
    status ENUM('in_progress', 'completed', 'abandoned') DEFAULT 'in_progress',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_quiz_id (quiz_id),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    notification_type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- 1. ADVANCED DATA FETCHING ---

// Enrolled Courses & Progress
$enrolledCourses = $course->getEnrolledCourses($userId);
$totalEnrolled = count($enrolledCourses);
$completedCourses = count(array_filter($enrolledCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
$inProgressCourses = $totalEnrolled - $completedCourses;

// Debug: Log the values
error_log("Dashboard Stats - Total: $totalEnrolled, Completed: $completedCourses, Active: $inProgressCourses");
foreach ($enrolledCourses as $idx => $ec) {
    error_log("Course $idx: ID=" . ($ec['id'] ?? 'N/A') . ", Title=" . ($ec['title'] ?? 'N/A') . ", Progress=" . ($ec['progress_percentage'] ?? 0));
}

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

// Daily Focus: Find most relevant lesson to continue
$dailyFocus = null;
$focusStmt = $conn->prepare("
    SELECT c.id as course_id, c.title as course_title, c.thumbnail,
           l.id as lesson_id, l.title as lesson_title, l.duration_minutes
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

// Greeting based on time
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i> Student
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="courses.php">My Courses</a></li>
                        <li><a class="dropdown-item" href="certificates.php">Certificates</a></li>
                        <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </div>
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
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Student Dashboard</h1>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <!-- Learning Overview -->
                <div class="dashboard-card mb-4">
                    <h3>Learning Overview</h3>
                    <!-- Debug Info (remove in production) -->
                    <?php if (empty($enrolledCourses)): ?>
                        <div class="alert alert-warning">
                            <strong>Debug:</strong> No enrolled courses found. Total: <?php echo $totalEnrolled; ?>
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <h3><?php echo $inProgressCourses; ?></h3>
                                <p>Active Courses</p>
                                <small><i class="fas fa-book-open"></i> In Progress</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <h3><?php echo $completedCourses; ?></h3>
                                <p>Completed</p>
                                <small><i class="fas fa-check-circle"></i> Finished</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <h3><?php echo $learningStreak; ?></h3>
                                <p>Day Streak</p>
                                <small><i class="fas fa-fire"></i> Keep Going!</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <h3><?php echo $totalStudyHours; ?>h</h3>
                                <p>Study Hours</p>
                                <small><i class="fas fa-clock"></i> Total Time</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area -->
                <div class="row">
                    <!-- Left Column: Continue Learning & Recommendations -->
                    <div class="col-lg-8">
                        
                <!-- Continue Learning -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Continue Learning</h3>
                        <a href="courses.php" class="btn btn-primary btn-sm">View All</a>
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
                        <!-- Quick Actions -->
                        <div class="dashboard-card">
                            <h5 class="mb-3">Quick Actions</h5>
                            <div class="d-grid gap-2">
                                <a href="courses.php" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-book-open me-2"></i>Browse Courses
                                </a>
                                <a href="quiz-results.php" class="btn btn-outline-info btn-sm w-100">
                                    <i class="fas fa-question-circle me-2"></i>Quiz Results
                                </a>
                                <a href="certificates.php" class="btn btn-outline-success btn-sm w-100">
                                    <i class="fas fa-award me-2"></i>View Certificates
                                </a>
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
</body>
</html>
