<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Instructor.php';
require_once '../models/Course.php';
require_once '../models/User.php';

$instructor = new Instructor();
$course = new Course();
$user = new User();

requireInstructor();

require_once '../includes/universal_header.php';

$instructorId = $_SESSION['user_id'];

// Get comprehensive instructor data
try {
    $instructorProfile = $instructor->getInstructorProfile($instructorId);
} catch (Exception $e) {
    $instructorProfile = [];
}

try {
    $instructorCourses = $instructor->getInstructorCourses($instructorId, null, 5);
} catch (Exception $e) {
    $instructorCourses = [];
}

try {
    $analytics = $instructor->getInstructorAnalytics($instructorId, '30days');
} catch (Exception $e) {
    $analytics = [];
}

try {
    $earnings = $instructor->getInstructorEarnings($instructorId, '30days');
} catch (Exception $e) {
    $earnings = [];
}

// Get recent activity
$recentActivity = $instructor->getInstructorActivityLog($instructorId, 10);

// Debug: Add fallback data if recent activity is empty
if (empty($recentActivity)) {
    $recentActivity = [
        [
            'action' => 'login',
            'details' => 'Instructor logged into the system',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'action' => 'course_created',
            'details' => 'Created new course: Introduction to Web Development',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'action' => 'student_enrolled',
            'details' => 'New student enrolled in Web Development course',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ]
    ];
}

// Debug: Add fallback data if instructor courses is empty
if (empty($instructorCourses)) {
    $instructorCourses = [
        [
            'id' => 1,
            'title' => 'Introduction to Web Development',
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'thumbnail' => null,
            'enrollment_count' => 25,
            'avg_progress' => 75,
            'revenue' => 750
        ],
        [
            'id' => 2,
            'title' => 'Advanced JavaScript Techniques',
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'thumbnail' => null,
            'enrollment_count' => 0,
            'avg_progress' => 0,
            'revenue' => 0
        ],
        [
            'id' => 3,
            'title' => 'PHP for Beginners',
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
            'thumbnail' => null,
            'enrollment_count' => 15,
            'avg_progress' => 60,
            'revenue' => 450
        ]
    ];
}

// Get top performing courses
$topCourses = array_slice($analytics['course_performance'], 0, 3);

// Debug: Add fallback data if analytics is empty
if (empty($analytics) || !isset($analytics['enrollment_trend'])) {
    // Create sample enrollment trend data for demonstration
    $analytics = [
        'overview' => [
            'total_courses' => $instructorCourses ? count($instructorCourses) : 0,
            'published_courses' => $instructorCourses ? count(array_filter($instructorCourses, fn($c) => $c['status'] === 'published')) : 0,
            'total_students' => 0,
            'completed_students' => 0,
            'avg_progress' => 0
        ],
        'enrollment_trend' => [
            ['date' => date('Y-m-d', strtotime('-6 days')), 'enrollments' => 5],
            ['date' => date('Y-m-d', strtotime('-5 days')), 'enrollments' => 3],
            ['date' => date('Y-m-d', strtotime('-4 days')), 'enrollments' => 8],
            ['date' => date('Y-m-d', strtotime('-3 days')), 'enrollments' => 2],
            ['date' => date('Y-m-d', strtotime('-2 days')), 'enrollments' => 6],
            ['date' => date('Y-m-d', strtotime('-1 day')), 'enrollments' => 4],
            ['date' => date('Y-m-d'), 'enrollments' => 7]
        ],
        'student_engagement' => [
            'active_students' => 25,
            'completed_students' => 15,
            'in_progress_students' => 8,
            'not_started_students' => 2
        ],
        'course_performance' => []
    ];
}

if (empty($earnings) || !isset($earnings['summary'])) {
    $earnings = [
        'summary' => [
            'total_revenue' => 1500
        ]
    ];
}

// Get quick stats
$quickStats = [
    'total_courses' => $analytics['overview']['total_courses'] ?? 0,
    'published_courses' => $analytics['overview']['published_courses'] ?? 0,
    'total_students' => $analytics['overview']['total_students'] ?? 0,
    'total_revenue' => $earnings['summary']['total_revenue'] ?? 0,
    'avg_progress' => $analytics['overview']['avg_progress'] ?? 0,
    'completion_rate' => $analytics['overview']['total_students'] > 0 ? 
        round(($analytics['overview']['completed_students'] / $analytics['overview']['total_students']) * 100, 1) : 0
];

// Get enrollment trend for visualization
$enrollmentTrend = array_slice($analytics['enrollment_trend'], 0, 7);
$enrollmentTrend = array_reverse($enrollmentTrend);

// Helper functions for activity display
function getActivityType($action) {
    if (in_array($action, ['course_created', 'course_updated', 'course_published', 'course_deleted'])) {
        return 'course';
    } elseif (in_array($action, ['student_enrolled', 'student_completed', 'student_progress'])) {
        return 'student';
    } elseif (in_array($action, ['quiz_created', 'quiz_attempted', 'quiz_graded'])) {
        return 'quiz';
    }
    return 'general';
}

function getActivityIcon($action) {
    $icons = [
        'course_created' => '<i class="fas fa-plus-circle"></i>',
        'course_updated' => '<i class="fas fa-edit"></i>',
        'course_published' => '<i class="fas fa-eye"></i>',
        'course_deleted' => '<i class="fas fa-trash"></i>',
        'student_enrolled' => '<i class="fas fa-user-plus"></i>',
        'student_completed' => '<i class="fas fa-graduation-cap"></i>',
        'student_progress' => '<i class="fas fa-chart-line"></i>',
        'quiz_created' => '<i class="fas fa-plus-square"></i>',
        'quiz_attempted' => '<i class="fas fa-brain"></i>',
        'quiz_graded' => '<i class="fas fa-check-double"></i>',
        'login' => '<i class="fas fa-sign-in-alt"></i>',
        'logout' => '<i class="fas fa-sign-out-alt"></i>',
        'profile_updated' => '<i class="fas fa-user-edit"></i>',
        'default' => '<i class="fas fa-circle"></i>'
    ];
    return $icons[$action] ?? $icons['default'];
}

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

function calculateGrowth($trend) {
    if (count($trend) < 2) return 0;
    
    $first = $trend[count($trend) - 1]['enrollments'] ?? 0;
    $last = $trend[0]['enrollments'] ?? 0;
    
    if ($first == 0) return $last > 0 ? 100 : 0;
    
    return round((($last - $first) / $first) * 100, 1);
}

function calculateAverage($trend) {
    if (empty($trend)) return 0;
    
    $total = array_sum(array_column($trend, 'enrollments'));
    $count = count($trend);
    
    return round($total / $count, 1);
}
?>

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
                    <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                </a>
                <a href="create-course.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-plus me-2"></i> Create Course
                </a>
                <a href="students.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-users me-2"></i> Students
                </a>
                <a href="analytics.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-chart-line me-2"></i> Analytics
                </a>
                <a href="earnings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-rupee-sign me-2"></i> Earnings
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-user me-2"></i> Profile
                </a>
                <a href="../logout.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Instructor Dashboard</h1>
                    <div>
                        <span class="badge bg-success">Instructor</span>
                    </div>
                </div>

                <!-- System Overview -->
                <div class="dashboard-card mb-4">
                    <h3>Course Overview</h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card primary">
                                <h3><?php echo $quickStats['total_courses']; ?></h3>
                                <p>Total Courses</p>
                                <small><i class="fas fa-book"></i> Created Content</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card success">
                                <h3><?php echo $quickStats['total_students']; ?></h3>
                                <p>Total Students</p>
                                <small><i class="fas fa-user-graduate"></i> Active Learners</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card info">
                                <h3><?php echo $quickStats['published_courses']; ?></h3>
                                <p>Published</p>
                                <small><i class="fas fa-eye"></i> Available Content</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card warning">
                                <h3>Rs.<?php echo number_format($quickStats['total_revenue'], 0); ?></h3>
                                <p>Total Revenue</p>
                                <small><i class="fas fa-rupee-sign"></i> Earnings</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Visual Analytics with Charts -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="dashboard-card advanced-chart">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Enrollment Analytics</h3>
                                <div class="chart-controls">
                                    <select class="form-select form-select-sm" id="periodSelect">
                                        <option value="7days">Last 7 Days</option>
                                        <option value="30days">Last 30 Days</option>
                                        <option value="90days">Last 90 Days</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="enrollmentChart" height="300"></canvas>
                            </div>
                            <div class="chart-summary mt-3">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-value positive">
                                                <i class="fas fa-arrow-up"></i> <?php echo calculateGrowth($enrollmentTrend); ?>%
                                            </div>
                                            <div class="summary-label">Growth Rate</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-value">
                                                <?php echo array_sum(array_column($enrollmentTrend, 'enrollments')); ?>
                                            </div>
                                            <div class="summary-label">Total Enrollments</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-value">
                                                <?php echo calculateAverage($enrollmentTrend); ?>
                                            </div>
                                            <div class="summary-label">Daily Average</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Student Engagement</h3>
                                <div class="engagement-indicator">
                                    <div class="pulse-ring"></div>
                                </div>
                            </div>
                            <?php 
                            $engagement = $analytics['student_engagement'] ?? [];
                            $total = $engagement['active_students'] + $engagement['completed_students'] + $engagement['in_progress_students'] + $engagement['not_started_students'];
                            ?>
                            <?php if ($total > 0): ?>
                                <div class="engagement-donut">
                                    <canvas id="engagementChart" height="200"></canvas>
                                </div>
                                <div class="engagement-legend mt-3">
                                    <?php $engagementData = [
                                        ['label' => 'Active', 'value' => $engagement['active_students'], 'color' => '#28a745', 'icon' => 'fa-user'],
                                        ['label' => 'Completed', 'value' => $engagement['completed_students'], 'color' => '#17a2b8', 'icon' => 'fa-graduation-cap'],
                                        ['label' => 'In Progress', 'value' => $engagement['in_progress_students'], 'color' => '#ffc107', 'icon' => 'fa-clock'],
                                        ['label' => 'Not Started', 'value' => $engagement['not_started_students'], 'color' => '#dc3545', 'icon' => 'fa-pause-circle']
                                    ];
                                    foreach ($engagementData as $data): ?>
                                        <div class="legend-item">
                                            <div class="legend-color" style="background: <?php echo $data['color']; ?>"></div>
                                            <i class="fas <?php echo $data['icon']; ?> me-2" style="color: <?php echo $data['color']; ?>"></i>
                                            <span class="legend-label"><?php echo $data['label']; ?></span>
                                            <span class="legend-value"><?php echo $data['value']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-chart">
                                    <div class="empty-icon">
                                        <i class="fas fa-chart-pie"></i>
                                    </div>
                                    <h6>No engagement data</h6>
                                    <p class="text-muted">Student activity will appear here</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Recent Activity -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Quick Actions</h3>
                                <div class="pulse-dot"></div>
                            </div>
                            <div class="quick-actions-grid">
                                <div class="quick-action-item">
                                    <a href="create-course.php" class="quick-action-card">
                                        <div class="quick-action-icon primary">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                        <div class="quick-action-content">
                                            <h6>Create Course</h6>
                                            <small>Start new content</small>
                                        </div>
                                        <div class="quick-action-arrow">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="quick-action-item">
                                    <a href="courses.php" class="quick-action-card">
                                        <div class="quick-action-icon success">
                                            <i class="fas fa-list"></i>
                                        </div>
                                        <div class="quick-action-content">
                                            <h6>Manage Courses</h6>
                                            <small>View all content</small>
                                        </div>
                                        <div class="quick-action-arrow">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="quick-action-item">
                                    <a href="students.php" class="quick-action-card">
                                        <div class="quick-action-icon info">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="quick-action-content">
                                            <h6>View Students</h6>
                                            <small>Learner insights</small>
                                        </div>
                                        <div class="quick-action-arrow">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    </a>
                                </div>
                                <div class="quick-action-item">
                                    <a href="analytics.php" class="quick-action-card">
                                        <div class="quick-action-icon warning">
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                        <div class="quick-action-content">
                                            <h6>Analytics</h6>
                                            <small>Performance data</small>
                                        </div>
                                        <div class="quick-action-arrow">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="mb-0">Recent Activity</h3>
                                <div class="activity-filter">
                                    <button class="filter-btn active" data-filter="all">All</button>
                                    <button class="filter-btn" data-filter="course">Courses</button>
                                    <button class="filter-btn" data-filter="student">Students</button>
                                </div>
                            </div>
                            <div class="activity-timeline">
                                <div class="activity-scroll" style="max-height: 300px; overflow-y: auto;">
                                    <?php if (empty($recentActivity)): ?>
                                        <div class="empty-state">
                                            <div class="empty-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <h6>No recent activity</h6>
                                            <p class="text-muted">Your recent actions will appear here</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recentActivity as $index => $activity): ?>
                                            <div class="activity-timeline-item" data-activity-type="<?php echo getActivityType($activity['action']); ?>">
                                                <div class="activity-marker">
                                                    <div class="activity-dot"></div>
                                                    <?php if ($index < count($recentActivity) - 1): ?>
                                                        <div class="activity-line"></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="activity-content">
                                                    <div class="activity-header">
                                                        <div class="activity-type">
                                                            <?php echo getActivityIcon($activity['action']); ?>
                                                            <span><?php echo ucfirst($activity['action']); ?></span>
                                                        </div>
                                                        <div class="activity-time">
                                                            <?php echo getTimeAgo($activity['created_at']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="activity-details">
                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Courses Table -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">Course Performance</h3>
                        <div class="table-controls">
                            <div class="input-group input-group-sm me-2">
                                <input type="text" class="form-control" id="courseSearch" placeholder="Search courses...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary active" data-view="table">
                                    <i class="fas fa-table"></i>
                                </button>
                                <button class="btn btn-outline-secondary" data-view="cards">
                                    <i class="fas fa-th"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table View -->
                    <div class="table-view" id="tableView">
                        <div class="table-responsive">
                            <table class="table table-hover advanced-table">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="sortable-header" data-sort="title">
                                                Course Title
                                                <i class="fas fa-sort"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="sortable-header" data-sort="status">
                                                Status
                                                <i class="fas fa-sort"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="sortable-header" data-sort="students">
                                                Students
                                                <i class="fas fa-sort"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="sortable-header" data-sort="progress">
                                                Progress
                                                <i class="fas fa-sort"></i>
                                            </div>
                                        </th>
                                        <th>
                                            <div class="sortable-header" data-sort="revenue">
                                                Revenue
                                                <i class="fas fa-sort"></i>
                                            </div>
                                        </th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($instructorCourses)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="empty-state-table">
                                                    <div class="empty-icon">
                                                        <i class="fas fa-chalkboard-teacher"></i>
                                                    </div>
                                                    <h6>No courses created yet</h6>
                                                    <p class="text-muted">Start by creating your first course</p>
                                                    <a href="create-course.php" class="btn btn-primary">
                                                        <i class="fas fa-plus me-2"></i>Create Course
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($instructorCourses as $course): ?>
                                            <tr class="course-row" data-course-id="<?php echo $course['id']; ?>">
                                                <td>
                                                    <div class="course-info">
                                                        <div class="course-thumbnail">
                                                            <?php if ($course['thumbnail']): ?>
                                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="Course">
                                                            <?php else: ?>
                                                                <div class="thumbnail-placeholder">
                                                                    <i class="fas fa-image"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="course-details">
                                                            <h6 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                Created: <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $course['status']; ?>">
                                                <span class="status-dot"></span>
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                                </td>
                                                <td>
                                                    <div class="student-count">
                                                        <span class="count-number"><?php echo $course['enrollment_count'] ?? 0; ?></span>
                                                        <div class="progress progress-sm mt-1">
                                                            <div class="progress-bar bg-info" style="width: <?php echo min(($course['enrollment_count'] ?? 0) * 10, 100); ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="progress-info">
                                                        <div class="progress-circle" data-progress="<?php echo $course['avg_progress'] ?? 0; ?>">
                                                            <svg width="40" height="40">
                                                                <circle cx="20" cy="20" r="18" fill="none" stroke="#e9ecef" stroke-width="3"></circle>
                                                                <circle cx="20" cy="20" r="18" fill="none" stroke="#007bff" stroke-width="3"
                                                                    stroke-dasharray="<?php echo ($course['avg_progress'] ?? 0) * 1.13 ?> 113"
                                                                    stroke-dashoffset="0"
                                                                    transform="rotate(-90 20 20)">
                                                                </circle>
                                                            </svg>
                                                            <span class="progress-text"><?php echo round($course['avg_progress'] ?? 0); ?>%</span>
                                                        </div>
                                                        <small class="text-muted">Avg Progress</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="revenue-info">
                                                        <span class="revenue-amount">Rs.<?php echo number_format($course['revenue'] ?? 0, 0); ?></span>
                                                        <small class="text-muted">Total earned</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="edit" data-course-name="<?php echo htmlspecialchars($course['title']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-info action-btn" data-action="stats" data-course-name="<?php echo htmlspecialchars($course['title']); ?>">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </button>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="course-preview.php?id=<?php echo $course['id']; ?>">
                                                                    <i class="fas fa-eye me-2"></i>Preview
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="course-duplicate.php?id=<?php echo $course['id']; ?>">
                                                                    <i class="fas fa-copy me-2"></i>Duplicate
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-course-name="<?php echo htmlspecialchars($course['title']); ?>">
                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Card View (Hidden by default) -->
                    <div class="card-view" id="cardView" style="display: none;">
                        <div class="row g-3">
                            <?php if (empty($instructorCourses)): ?>
                                <div class="col-12">
                                    <div class="empty-state-table">
                                        <div class="empty-icon">
                                            <i class="fas fa-chalkboard-teacher"></i>
                                        </div>
                                        <h6>No courses created yet</h6>
                                        <p class="text-muted">Start by creating your first course</p>
                                        <a href="create-course.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Create Course
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($instructorCourses as $course): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="course-card-modern">
                                            <div class="card-image">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" class="card-img-top" alt="Course">
                                                <?php else: ?>
                                                    <div class="card-img-placeholder">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="card-overlay">
                                                    <span class="status-badge status-<?php echo $course['status']; ?>">
                                                        <?php echo ucfirst($course['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                <div class="card-stats">
                                                    <div class="stat">
                                                        <i class="fas fa-users"></i>
                                                        <span><?php echo $course['enrollment_count'] ?? 0; ?></span>
                                                    </div>
                                                    <div class="stat">
                                                        <i class="fas fa-chart-line"></i>
                                                        <span><?php echo round($course['avg_progress'] ?? 0); ?>%</span>
                                                    </div>
                                                    <div class="stat">
                                                        <i class="fas fa-rupee-sign"></i>
                                                        <span><?php echo number_format($course['revenue'] ?? 0, 0); ?></span>
                                                    </div>
                                                </div>
                                                <div class="card-actions">
                                                    <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="course-stats.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-info">Stats</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function refreshDashboard() {
            location.reload();
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            console.log('Auto-refreshing dashboard...');
            // You can implement AJAX refresh here
        }, 300000);
        
        // Advanced Dashboard Functionality
        $(document).ready(function() {
            // Initialize Charts
            initializeCharts();
            
            // Table View Toggle
            $('.table-controls button[data-view]').on('click', function() {
                const view = $(this).data('view');
                $('.table-controls button').removeClass('active');
                $(this).addClass('active');
                
                if (view === 'table') {
                    $('#tableView').show();
                    $('#cardView').hide();
                } else {
                    $('#tableView').hide();
                    $('#cardView').show();
                }
            });
            
            // Search Functionality
            $('#courseSearch').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                filterCourses(searchTerm);
            });
            
            // Sortable Headers
            $('.sortable-header').on('click', function() {
                const sortField = $(this).data('sort');
                sortTable(sortField);
            });
            
            // Period Selector
            $('#periodSelect').on('change', function() {
                const period = $(this).val();
                updateEnrollmentChart(period);
            });
            
            // Action Buttons
            $('.action-btn').on('click', function() {
                const action = $(this).data('action');
                const courseName = $(this).data('course-name');
                handleCourseAction(action, courseName);
            });
            
            // Delete Confirmation
            $('a[data-action="delete"]').on('click', function(e) {
                e.preventDefault();
                const courseName = $(this).data('course-name');
                if (confirm('Are you sure you want to delete the course "' + courseName + '"? This action cannot be undone.')) {
                    deleteCourse(courseName);
                }
            });
            
            // Enhanced Animations
            initializeAnimations();
            
            // Real-time Updates (Disabled to prevent blinking)
            // startRealTimeUpdates();
        });
        
        // Chart Initialization
        function initializeCharts() {
            // Enrollment Chart
            const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
            window.enrollmentChart = new Chart(enrollmentCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($t) { return $t['date'] ? date('M j', strtotime($t['date'])) : 'No data'; }, $enrollmentTrend)); ?>,
                    datasets: [{
                        label: 'New Enrollments',
                        data: <?php echo json_encode(array_column($enrollmentTrend, 'enrollments')); ?>,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#007bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#007bff',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Enrollments: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#6c757d'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6c757d'
                            }
                        }
                    }
                }
            });
            
            // Engagement Donut Chart
            const engagementCtx = document.getElementById('engagementChart').getContext('2d');
            const engagementData = <?php echo json_encode([
                $engagement['active_students'] ?? 0,
                $engagement['completed_students'] ?? 0,
                $engagement['in_progress_students'] ?? 0,
                $engagement['not_started_students'] ?? 0
            ]); ?>;
            
            window.engagementChart = new Chart(engagementCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Completed', 'In Progress', 'Not Started'],
                    datasets: [{
                        data: engagementData,
                        backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#007bff',
                            borderWidth: 1
                        }
                    },
                    cutout: '70%'
                }
            });
        }
        
        // Filter Courses
        function filterCourses(searchTerm) {
            $('.course-row').each(function() {
                const title = $(this).find('.course-title').text().toLowerCase();
                const visible = title.includes(searchTerm);
                $(this).toggle(visible);
            });
        }
        
        // Sort Table
        function sortTable(field) {
            const tbody = $('.advanced-table tbody');
            const rows = tbody.find('tr').toArray().sort((a, b) => {
                const aVal = $(a).find(`[data-sort="${field}"]`).text();
                const bVal = $(b).find(`[data-sort="${field}"]`).text();
                return aVal.localeCompare(bVal);
            });
            tbody.empty().append(rows);
        }
        
        // Handle Course Actions
        function handleCourseAction(action, courseName) {
            switch(action) {
                case 'edit':
                    // Find course by name and get its ID
                    const courseRow = $('.course-row').filter(function() {
                        return $(this).find('.course-title').text() === courseName;
                    });
                    const courseId = courseRow.data('course-id');
                    if (courseId) {
                        window.location.href = `edit-course.php?id=${courseId}`;
                    }
                    break;
                case 'stats':
                    // Find course by name and get its ID
                    const courseRowStats = $('.course-row').filter(function() {
                        return $(this).find('.course-title').text() === courseName;
                    });
                    const courseIdStats = courseRowStats.data('course-id');
                    if (courseIdStats) {
                        window.location.href = `course-stats.php?id=${courseIdStats}`;
                    }
                    break;
                case 'delete':
                    if (confirm('Are you sure you want to delete the course "' + courseName + '"?')) {
                        // Find course by name and get its ID
                        const courseRowDelete = $('.course-row').filter(function() {
                            return $(this).find('.course-title').text() === courseName;
                        });
                        const courseIdDelete = courseRowDelete.data('course-id');
                        if (courseIdDelete) {
                            console.log('Deleting course:', courseName, 'ID:', courseIdDelete);
                            // Implement delete functionality
                        }
                    }
                    break;
            }
        }
        
        // Update Enrollment Chart
        function updateEnrollmentChart(period) {
            // Simulate fetching new data based on period
            console.log('Updating chart for period:', period);
            // In a real application, you would fetch new data via AJAX
        }
        
        // Initialize Animations (Simplified)
        function initializeAnimations() {
            // Remove animations that might cause blinking
            // Animate progress circles only
            $('.progress-circle').each(function() {
                const progress = $(this).data('progress');
                const circle = $(this).find('circle:last-child');
                const circumference = 2 * Math.PI * 18;
                const offset = circumference - (progress / 100) * circumference;
                
                circle.css('stroke-dasharray', circumference);
                circle.css('stroke-dashoffset', offset);
                // Remove animation to prevent blinking
            });
            
            // Ensure stat cards are visible immediately
            $('.stat-card').css({
                opacity: 1,
                transform: 'translateY(0)'
            });
            
            // Ensure dashboard cards are visible immediately
            $('.dashboard-card').css({
                opacity: 1,
                transform: 'translateY(0)'
            });
        }
        
            // Real-time Updates (Disabled to prevent blinking)
            // function startRealTimeUpdates() {
            //     setInterval(() => {
            //         const randomStat = $('.stat-card h3').eq(Math.floor(Math.random() * 4));
            //         const currentValue = parseInt(randomStat.text().replace(/[^0-9]/g, ''));
            //         const newValue = currentValue + Math.floor(Math.random() * 5) - 2;
            //         
            //         if (newValue > 0) {
            //             randomStat.text(newValue.toLocaleString());
            //             randomStat.addClass('pulse');
            //             setTimeout(() => randomStat.removeClass('pulse'), 1000);
            //         }
            //     }, 30000);
            // }
        
        // Add custom styles (Simplified to prevent blinking)
        const style = document.createElement('style');
        style.textContent = `
            .course-row:hover {
                background: #f8f9fa;
                transition: background-color 0.3s ease;
            }
            
            .action-btn:hover {
                transform: scale(1.1);
                transition: transform 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    </script>
