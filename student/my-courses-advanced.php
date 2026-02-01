<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/Quiz.php';
require_once dirname(__DIR__) . '/models/Discussion.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once dirname(__DIR__) . '/includes/universal_header.php';

$studentId = $_SESSION['user_id'];
$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();

// Get enrolled courses with enhanced data
$enrolledCourses = $course->getEnrolledCourses($studentId);

// Enhanced course data
foreach ($enrolledCourses as &$enrolledCourse) {
    // Get detailed progress
    $enrolledCourse['progress_details'] = $course->getCourseStatistics($enrolledCourse['id']);
    
    // Get quiz statistics
    $enrolledCourse['quiz_stats'] = $quiz->getCourseQuizStats($studentId, $enrolledCourse['id']);
    
    // Get discussion count
    $enrolledCourse['discussion_count'] = $discussion->getCourseDiscussionCount($enrolledCourse['id']);
    
    // Get next lesson
    $enrolledCourse['next_lesson'] = $course->getNextLesson($studentId, $enrolledCourse['id']);
    
    // Calculate learning streak
    $enrolledCourse['learning_streak'] = calculateLearningStreak($studentId, $enrolledCourse['id']);
    
    // Get completion prediction
    $enrolledCourse['completion_prediction'] = predictCompletionTime($enrolledCourse);
}

// Handle filters and sorting
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'recent';
$search = $_GET['search'] ?? '';

// Filter courses
$filteredCourses = filterCourses($enrolledCourses, $filter, $search);

// Sort courses
$filteredCourses = sortCourses($filteredCourses, $sort);

// Advanced functions
function calculateLearningStreak($studentId, $courseId) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(completed_at)) as streak_days
        FROM lesson_progress lp
        JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.student_id = ? AND l.course_id = ? AND lp.completed = 1
        AND completed_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        ORDER BY completed_at DESC
    ");
    
    if ($stmt) {
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['streak_days'] ?? 0;
    }
    return 0;
}

function predictCompletionTime($course) {
    $progress = $course['enrollment_status'] ?? 'active';
    $progressPercentage = $course['progress_percentage'] ?? 0;
    
    if ($progressPercentage >= 100) {
        return ['status' => 'completed', 'days' => 0];
    }
    
    // Simple prediction based on current progress
    $daysSinceEnrollment = $course['enrolled_at'] ? 
        (time() - strtotime($course['enrolled_at'])) / 86400 : 1;
    
    if ($progressPercentage > 0) {
        $totalDays = $daysSinceEnrollment / ($progressPercentage / 100);
        $remainingDays = $totalDays - $daysSinceEnrollment;
        return [
            'status' => 'in_progress', 
            'days' => max(1, round($remainingDays))
        ];
    }
    
    return ['status' => 'not_started', 'days' => 30]; // Default estimate
}

function filterCourses($courses, $filter, $search) {
    $filtered = $courses;
    
    // Apply search filter
    if (!empty($search)) {
        $search = strtolower($search);
        $filtered = array_filter($filtered, function($course) use ($search) {
            return strpos(strtolower($course['title']), $search) !== false ||
                   strpos(strtolower($course['description'] ?? ''), $search) !== false ||
                   strpos(strtolower($course['category_name'] ?? ''), $search) !== false;
        });
    }
    
    // Apply status filter
    switch ($filter) {
        case 'active':
            $filtered = array_filter($filtered, function($course) {
                return ($course['progress_percentage'] ?? 0) < 100;
            });
            break;
        case 'completed':
            $filtered = array_filter($filtered, function($course) {
                return ($course['progress_percentage'] ?? 0) >= 100;
            });
            break;
        case 'in_progress':
            $filtered = array_filter($filtered, function($course) {
                $progress = $course['progress_percentage'] ?? 0;
                return $progress > 0 && $progress < 100;
            });
            break;
    }
    
    return array_values($filtered);
}

function sortCourses($courses, $sort) {
    switch ($sort) {
        case 'recent':
            usort($courses, function($a, $b) {
                return strtotime($b['enrolled_at'] ?? '1970-01-01') - strtotime($a['enrolled_at'] ?? '1970-01-01');
            });
            break;
        case 'progress':
            usort($courses, function($a, $b) {
                return ($b['progress_percentage'] ?? 0) - ($a['progress_percentage'] ?? 0);
            });
            break;
        case 'title':
            usort($courses, function($a, $b) {
                return strcmp($a['title'] ?? '', $b['title'] ?? '');
            });
            break;
        case 'streak':
            usort($courses, function($a, $b) {
                return ($b['learning_streak'] ?? 0) - ($a['learning_streak'] ?? 0);
            });
            break;
    }
    
    return $courses;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Advanced Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .advanced-course-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            border-radius: 15px;
        }
        .advanced-course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .progress-ring {
            position: relative;
            width: 60px;
            height: 60px;
        }
        .progress-ring circle {
            transition: stroke-dashoffset 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        .learning-streak {
            background: linear-gradient(45deg, #ff6b6b, #feca57);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        .course-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .course-stat {
            text-align: center;
            font-size: 0.8rem;
        }
        .course-stat i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 2px;
        }
        .filter-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .filter-bar .form-control,
        .filter-bar .form-select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        .filter-bar .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .analytics-chart {
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px 0;
        }
        .course-thumbnail {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .next-lesson-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .next-lesson-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .completion-prediction {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .course-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        .course-actions .btn {
            flex: 1;
            border-radius: 20px;
        }
        .sidebar-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-widget {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
        }
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .achievement-badge {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .course-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Enhanced Sidebar -->
            <div class="col-md-3">
                <div class="card sidebar-card mb-3">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-graduation-cap me-2"></i>Student Portal
                        </h5>
                        <div class="list-group list-group-flush">
                            <a href="dashboard.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                            <a href="my-courses-advanced.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-graduation-cap me-2"></i> My Courses
                                <span class="badge bg-primary float-end"><?php echo count($enrolledCourses ?? []); ?></span>
                            </a>
                            <a href="quizzes.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-brain me-2"></i> Quizzes
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
                            <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Learning Stats Widget -->
                <div class="card sidebar-card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-chart-line me-2"></i>Learning Stats
                        </h6>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-primary"><?php echo count($enrolledCourses ?? []); ?></h4>
                                <small class="text-muted">Courses</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success">
                                    <?php 
                                    $completed = array_filter($enrolledCourses ?? [], function($c) { 
                                        return ($c['progress_percentage'] ?? 0) >= 100; 
                                    });
                                    echo count($completed);
                                    ?>
                                </h4>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning">
                                    <?php 
                                    $inProgress = array_filter($enrolledCourses ?? [], function($c) { 
                                        $progress = $c['progress_percentage'] ?? 0;
                                        return $progress > 0 && $progress < 100;
                                    });
                                    echo count($inProgress);
                                    ?>
                                </h4>
                                <small class="text-muted">In Progress</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info">
                                    <?php 
                                    $totalStreak = array_sum(array_column($enrolledCourses ?? [], 'learning_streak'));
                                    echo $totalStreak;
                                    ?>
                                </h4>
                                <small class="text-muted">Day Streaks</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card sidebar-card mt-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-sm" onclick="window.location.href='../courses.php'">
                                <i class="fas fa-plus me-1"></i> Browse Courses
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="exportProgress()">
                                <i class="fas fa-download me-1"></i> Export Progress
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="showAchievements()">
                                <i class="fas fa-trophy me-1"></i> Achievements
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Enhanced Header -->
                <div class="header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="mb-2">My Courses</h1>
                            <p class="mb-0 opacity-75">Track your learning journey and achieve your goals</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-light" onclick="toggleFilters()">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                            <button class="btn btn-light" onclick="toggleView()">
                                <i class="fas fa-th"></i> View
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filter Bar -->
                <div class="filter-bar" id="filterBar" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search courses..." 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   onchange="applyFilters()">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="applyFilters()">
                                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Courses</option>
                                <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="in_progress" <?php echo $filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" onchange="applyFilters()">
                                <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Most Recent</option>
                                <option value="progress" <?php echo $sort === 'progress' ? 'selected' : ''; ?>>Highest Progress</option>
                                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Alphabetical</option>
                                <option value="streak" <?php echo $sort === 'streak' ? 'selected' : ''; ?>>Learning Streak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-light w-100" onclick="applyFilters()">
                                <i class="fas fa-search"></i> Apply
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Course Grid -->
                <?php if (empty($filteredCourses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h4>No courses found</h4>
                        <p class="text-muted">Try adjusting your filters or browse available courses.</p>
                        <a href="../courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($filteredCourses as $course): ?>
                            <div class="advanced-course-card">
                                <!-- Course Thumbnail -->
                                <div class="position-relative">
                                    <?php if ($course['thumbnail']): ?>
                                        <img src="../uploads/course_thumbnails/<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                                             class="course-thumbnail" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                    <?php else: ?>
                                        <div class="course-thumbnail d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-book fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Learning Streak Badge -->
                                    <?php if ($course['learning_streak'] > 0): ?>
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="learning-streak">
                                                <i class="fas fa-fire"></i> <?php echo $course['learning_streak']; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body">
                                    <!-- Course Title and Category -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                        </div>
                                        <div class="text-end">
                                            <?php if ($course['progress_percentage'] >= 100): ?>
                                                <span class="achievement-badge">
                                                    <i class="fas fa-trophy"></i> Completed
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Ring -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="progress-ring me-3">
                                            <svg width="60" height="60">
                                                <circle cx="30" cy="30" r="25" 
                                                        stroke="#e9ecef" 
                                                        stroke-width="5" 
                                                        fill="transparent"/>
                                                <circle cx="30" cy="30" r="25" 
                                                        stroke="#28a745" 
                                                        stroke-width="5" 
                                                        fill="transparent"
                                                        stroke-dasharray="<?php echo 2 * pi() * 25; ?>"
                                                        stroke-dashoffset="<?php echo 2 * pi() * 25 * (1 - ($course['progress_percentage'] ?? 0) / 100); ?>"/>
                                            </svg>
                                            <div class="position-absolute top-50 start-50 translate-middle">
                                                <small class="fw-bold"><?php echo round($course['progress_percentage'] ?? 0); ?>%</small>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold">Course Progress</div>
                                            <small class="text-muted">
                                                <?php 
                                                $completedLessons = ($course['progress_details']['completed_lessons'] ?? 0);
                                                $totalLessons = ($course['progress_details']['total_lessons'] ?? 1);
                                                echo "$completedLessons of $totalLessons lessons";
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Course Stats -->
                                    <div class="course-stats">
                                        <div class="course-stat">
                                            <i class="fas fa-book-open text-primary"></i>
                                            <div><?php echo $course['progress_details']['total_lessons'] ?? 0; ?></div>
                                            <small>Lessons</small>
                                        </div>
                                        <div class="course-stat">
                                            <i class="fas fa-brain text-info"></i>
                                            <div><?php echo $course['quiz_stats']['total_quizzes'] ?? 0; ?></div>
                                            <small>Quizzes</small>
                                        </div>
                                        <div class="course-stat">
                                            <i class="fas fa-comments text-warning"></i>
                                            <div><?php echo $course['discussion_count'] ?? 0; ?></div>
                                            <small>Discussions</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Next Lesson -->
                                    <?php if ($course['next_lesson']): ?>
                                        <div class="mb-3 p-2 bg-light rounded">
                                            <small class="text-muted">Next up:</small>
                                            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($course['next_lesson']['title']); ?></div>
                                            <button class="next-lesson-btn w-100 mt-2" onclick="openCourse(<?php echo $course['id']; ?>)">
                                                <i class="fas fa-play me-1"></i> Continue Learning
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Completion Prediction -->
                                    <div class="completion-prediction mb-3">
                                        <?php 
                                        $prediction = $course['completion_prediction'];
                                        if ($prediction['status'] === 'completed'): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Completed</span>
                                        <?php elseif ($prediction['status'] === 'in_progress'): ?>
                                            <span class="text-info"><i class="fas fa-clock"></i> Est. <?php echo $prediction['days']; ?> days to complete</span>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-hourglass-start"></i> Not started yet</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Course Actions -->
                                    <div class="course-actions">
                                        <button class="btn btn-primary btn-sm" onclick="openCourse(<?php echo $course['id']; ?>)">
                                            <i class="fas fa-play me-1"></i> Open
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm" onclick="viewAnalytics(<?php echo $course['id']; ?>)">
                                            <i class="fas fa-chart-line me-1"></i> Analytics
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Analytics Modal -->
    <div class="modal fade" id="analyticsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Course Analytics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="analyticsContent">
                        <!-- Analytics content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements Modal -->
    <div class="modal fade" id="achievementsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Your Achievements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                        <h5>Coming Soon!</h5>
                        <p class="text-muted">Achievements and badges will be available soon.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleFilters() {
            const filterBar = document.getElementById('filterBar');
            filterBar.style.display = filterBar.style.display === 'none' ? 'block' : 'none';
        }
        
        function toggleView() {
            // Toggle between grid and list view
            const courseGrid = document.querySelector('.course-grid');
            if (courseGrid.style.display === 'flex') {
                courseGrid.style.display = 'grid';
            } else {
                courseGrid.style.display = 'flex';
                courseGrid.style.flexDirection = 'column';
            }
        }
        
        function applyFilters() {
            const search = document.querySelector('input[placeholder="Search courses..."]').value;
            const filter = document.querySelector('select').value;
            const sort = document.querySelectorAll('select')[1].value;
            
            const params = new URLSearchParams();
            if (search) params.set('search', search);
            if (filter !== 'all') params.set('filter', filter);
            if (sort !== 'recent') params.set('sort', sort);
            
            window.location.href = '?' + params.toString();
        }
        
        function openCourse(courseId) {
            window.location.href = 'view-course.php?id=' + courseId;
        }
        
        function viewAnalytics(courseId) {
            // Load analytics data
            fetch(`../api/course_analytics.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    displayAnalytics(data);
                    const modal = new bootstrap.Modal(document.getElementById('analyticsModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error loading analytics:', error);
                    document.getElementById('analyticsContent').innerHTML = 
                        '<div class="text-center py-4"><i class="fas fa-exclamation-triangle text-warning"></i> Unable to load analytics</div>';
                });
        }
        
        function showAchievements() {
            const modal = new bootstrap.Modal(document.getElementById('achievementsModal'));
            modal.show();
        }
        
        function displayAnalytics(data) {
            const content = document.getElementById('analyticsContent');
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Learning Activity (Last 30 Days)</h6>
                        <div class="analytics-chart">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Category Performance</h6>
                        <div class="analytics-chart">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <h6>Course Statistics</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                </tr>
                                <tr>
                                    <td>Total Lessons</td>
                                    <td>${data.total_lessons || 0}</td>
                                </tr>
                                <tr>
                                    <td>Completed Lessons</td>
                                    <td>${data.completed_lessons || 0}</td>
                                </tr>
                                <tr>
                                    <td>Average Progress</td>
                                    <td>${data.avg_progress || 0}%</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
            
            // Initialize charts
            setTimeout(() => {
                if (data.activity_timeline) {
                    createActivityChart(data.activity_timeline);
                }
                if (data.category_performance) {
                    createCategoryChart(data.category_performance);
                }
            }, 100);
        }
        
        function createActivityChart(data) {
            const ctx = document.getElementById('activityChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Lessons Completed',
                        data: data.map(d => d.lessons_completed),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function createCategoryChart(data) {
            const ctx = document.getElementById('categoryChart');
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.category),
                    datasets: [{
                        data: data.map(d => d.avg_time),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#4BC0C8', '#FFC107', '#8BC34A'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        function exportProgress() {
            // Export functionality
            const courses = <?php echo json_encode($filteredCourses); ?>;
            const csvContent = generateCSV(courses);
            downloadCSV(csvContent, 'course-progress.csv');
        }
        
        function generateCSV(courses) {
            const headers = ['Course Title', 'Progress', 'Status', 'Enrolled Date', 'Category', 'Learning Streak'];
            const rows = courses.map(course => [
                course.title,
                course.progress_percentage + '%',
                course.progress_percentage >= 100 ? 'Completed' : 'In Progress',
                course.enrolled_at,
                course.category_name,
                course.learning_streak || 0
            ]);
            
            return [headers, ...rows].map(row => row.join(',')).join('\n');
        }
        
        function downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
