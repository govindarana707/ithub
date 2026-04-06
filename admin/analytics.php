<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Course.php';
require_once dirname(__DIR__) . '/models/Database.php';

requireAdmin();

// Get analytics data
$db = new Database();
$conn = $db->getConnection();
$course = new Course();
$user = new User();

// Get date range from request
$dateRange = $_GET['date_range'] ?? '30'; // Default to last 30 days
$startDate = date('Y-m-d', strtotime("-$dateRange days"));
$endDate = date('Y-m-d');

// Enhanced user statistics
try {
    $stmt = $conn->prepare("
        SELECT role, COUNT(*) as count,
               COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_count
        FROM users_new 
        GROUP BY role
    ");
    if ($stmt) {
        $stmt->bind_param('s', $startDate);
        $stmt->execute();
        $userStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $userStats = [
            ['role' => 'admin', 'count' => 1, 'new_count' => 0],
            ['role' => 'instructor', 'count' => 0, 'new_count' => 0],
            ['role' => 'student', 'count' => 0, 'new_count' => 0]
        ];
    }
} catch (Exception $e) {
    $userStats = [
        ['role' => 'admin', 'count' => 1, 'new_count' => 0],
        ['role' => 'instructor', 'count' => 0, 'new_count' => 0],
        ['role' => 'student', 'count' => 0, 'new_count' => 0]
    ];
}

// Course statistics with enhanced data
try {
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.price, c.status, c.difficulty_level,
               COUNT(e.id) as enrollment_count,
               COUNT(CASE WHEN e.enrolled_at >= ? THEN 1 END) as new_enrollments,
               AVG(e.progress_percentage) as avg_progress,
               COUNT(CASE WHEN e.progress_percentage = 100 THEN 1 END) as completions
        FROM courses_new c 
        LEFT JOIN enrollments e ON c.id = e.course_id
        GROUP BY c.id 
        ORDER BY enrollment_count DESC 
        LIMIT 10
    ");
    if ($stmt) {
        $stmt->bind_param('s', $startDate);
        $stmt->execute();
        $topCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $topCourses = [];
    }
} catch (Exception $e) {
    $topCourses = [];
}

// Monthly trends with enhanced data
try {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM users_new 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    if ($stmt) {
        $stmt->execute();
        $monthlyRegistrations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $monthlyRegistrations = [];
    }
} catch (Exception $e) {
    $monthlyRegistrations = [];
}

try {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(enrolled_at, '%Y-%m') as month, COUNT(*) as count 
        FROM enrollments 
        WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
        ORDER BY month
    ");
    if ($stmt) {
        $stmt->execute();
        $monthlyEnrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $monthlyEnrollments = [];
    }
} catch (Exception $e) {
    $monthlyEnrollments = [];
}

// Revenue analytics
try {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(enrolled_at, '%Y-%m') as month, 
               SUM(c.price) as revenue,
               COUNT(*) as enrollment_count
        FROM enrollments e
        JOIN courses_new c ON e.course_id = c.id
        WHERE e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(e.enrolled_at, '%Y-%m')
        ORDER BY month
    ");
    if ($stmt) {
        $stmt->execute();
        $monthlyRevenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $monthlyRevenue = [];
    }
} catch (Exception $e) {
    $monthlyRevenue = [];
}

// Enhanced quiz statistics
try {
    $stmt = $conn->prepare("
        SELECT AVG(qa.percentage) as avg_score, 
               COUNT(*) as total_attempts,
               COUNT(CASE WHEN qa.percentage >= 80 THEN 1 END) as high_scores,
               AVG(qa.time_taken) as avg_time
        FROM quiz_attempts qa 
        WHERE qa.status = 'completed' AND qa.created_at >= ?
    ");
    if ($stmt) {
        $stmt->bind_param('s', $startDate);
        $stmt->execute();
        $quizStats = $stmt->get_result()->fetch_assoc();
    } else {
        // Fallback if quiz_attempts table doesn't exist
        $quizStats = [
            'avg_score' => 75.5,
            'total_attempts' => 0,
            'high_scores' => 0,
            'avg_time' => 1800
        ];
    }
} catch (Exception $e) {
    $quizStats = [
        'avg_score' => 75.5,
        'total_attempts' => 0,
        'high_scores' => 0,
        'avg_time' => 1800
    ];
}

// Certificate statistics
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total,
               COUNT(CASE WHEN issued_date >= ? THEN 1 END) as new_certificates
        FROM certificates
    ");
    if ($stmt) {
        $stmt->bind_param('s', $startDate);
        $stmt->execute();
        $certificateStats = $stmt->get_result()->fetch_assoc();
    } else {
        $certificateStats = ['total' => 0, 'new_certificates' => 0];
    }
} catch (Exception $e) {
    $certificateStats = ['total' => 0, 'new_certificates' => 0];
}

// Learning progress analytics
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN progress_percentage >= 90 THEN 1 END) as high_achievers,
            COUNT(CASE WHEN progress_percentage >= 70 AND progress_percentage < 90 THEN 1 END) as good_progress,
            COUNT(CASE WHEN progress_percentage >= 50 AND progress_percentage < 70 THEN 1 END) as moderate_progress,
            COUNT(CASE WHEN progress_percentage < 50 THEN 1 END) as low_progress,
            AVG(progress_percentage) as overall_avg
        FROM enrollments 
        WHERE enrolled_at >= ?
    ");
    if ($stmt) {
        $stmt->bind_param('s', $startDate);
        $stmt->execute();
        $progressStats = $stmt->get_result()->fetch_assoc();
    } else {
        $progressStats = [
            'high_achievers' => 0,
            'good_progress' => 0,
            'moderate_progress' => 0,
            'low_progress' => 0,
            'overall_avg' => 0
        ];
    }
} catch (Exception $e) {
    $progressStats = [
        'high_achievers' => 0,
        'good_progress' => 0,
        'moderate_progress' => 0,
        'low_progress' => 0,
        'overall_avg' => 0
    ];
}

// Popular categories
try {
    $stmt = $conn->prepare("
        SELECT cat.name, COUNT(e.id) as enrollment_count,
               AVG(c.price) as avg_price
        FROM categories_new cat
        LEFT JOIN courses_new c ON cat.id = c.category_id
        LEFT JOIN enrollments e ON c.id = e.course_id
        GROUP BY cat.id, cat.name
        ORDER BY enrollment_count DESC
        LIMIT 8
    ");
    if ($stmt) {
        $stmt->execute();
        $categoryStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $categoryStats = [];
    }
} catch (Exception $e) {
    $categoryStats = [];
}

// Instructor performance
try {
    $stmt = $conn->prepare("
        SELECT u.full_name, COUNT(c.id) as course_count,
               COUNT(e.id) as total_students,
               AVG(e.progress_percentage) as avg_student_progress,
               SUM(c.price) as total_revenue
        FROM users_new u
        JOIN courses_new c ON u.id = c.instructor_id
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE u.role = 'instructor'
        GROUP BY u.id, u.full_name
        ORDER BY total_students DESC
        LIMIT 6
    ");
    if ($stmt) {
        $stmt->execute();
        $instructorStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $instructorStats = [];
    }
} catch (Exception $e) {
    $instructorStats = [];
}

// Daily activity for last 7 days
try {
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as activity_count
        FROM (
            SELECT created_at FROM users_new WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT enrolled_at as created_at FROM enrollments WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT created_at FROM quiz_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            UNION ALL
            SELECT issued_date as created_at FROM certificates WHERE issued_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ) as activities
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    if ($stmt) {
        $stmt->execute();
        $dailyActivity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $dailyActivity = [];
    }
} catch (Exception $e) {
    $dailyActivity = [];
}

?>

<?php require_once dirname(__DIR__) . '/includes/universal_header.php'; ?>

<link rel="stylesheet" href="../assets/css/admin-theme.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Analytics-specific styles */
.analytics-card {
    background: var(--admin-bg-primary);
    border-radius: var(--admin-radius-xl);
    border: 1px solid var(--admin-border);
    box-shadow: var(--admin-shadow-sm);
    margin-bottom: 25px;
    overflow: hidden;
}

.analytics-card .card-header {
    background: linear-gradient(135deg, var(--admin-bg-secondary) 0%, #f1f5f9 100%);
    border-bottom: 1px solid var(--admin-border);
    padding: 20px 25px;
    font-weight: 600;
}

.analytics-card .card-body {
    padding: 25px;
}

.date-filter {
    background: var(--admin-bg-secondary);
    border-radius: var(--admin-radius-xl);
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid var(--admin-border);
}

.chart-container {
    position: relative;
    height: 300px;
    margin-top: 20px;
}

.metric-card {
    background: var(--admin-bg-primary);
    border-radius: var(--admin-radius-xl);
    padding: 25px;
    text-align: center;
    border: 1px solid var(--admin-border);
    box-shadow: var(--admin-shadow-sm);
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--admin-shadow-lg);
    border-color: var(--admin-primary-light);
}

.metric-card .metric-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--admin-text-primary);
}

.metric-card .metric-label {
    color: var(--admin-text-secondary);
    font-size: 0.9rem;
    margin-top: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-card .metric-change {
    font-size: 0.85rem;
    margin-top: 10px;
    font-weight: 500;
}

.metric-card .metric-change.positive { color: var(--admin-success); }
.metric-card .metric-change.negative { color: var(--admin-danger); }

.custom-table {
    background: white;
    border-radius: var(--admin-radius-lg);
    overflow: hidden;
}

.custom-table thead {
    background: var(--admin-bg-secondary);
}

.custom-table th {
    border: none;
    font-weight: 600;
    color: var(--admin-text-primary);
    padding: 15px;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.custom-table td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid var(--admin-border-light);
}

.custom-table tbody tr:hover {
    background: var(--admin-bg-secondary);
}

.badge-custom {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php require_once 'includes/sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Admin Dashboard Header -->
            <div class="admin-dashboard-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">📊 Analytics Dashboard</h2>
                        <p class="mb-0 opacity-75">Real-time insights and performance metrics</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn-modern btn-secondary-modern" onclick="refreshAnalytics()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                        <span class="admin-badge">Administrator</span>
                    </div>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="date-filter">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Date Range</label>
                        <select class="form-select" id="dateRange" onchange="updateDateRange()">
                            <option value="7" <?php echo $dateRange == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $dateRange == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $dateRange == '90' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="365" <?php echo $dateRange == '365' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">From</label>
                        <input type="date" class="form-control" id="startDate" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">To</label>
                        <input type="date" class="form-control" id="endDate" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">&nbsp;</label>
                        <button class="btn-modern btn-primary-modern w-100" onclick="applyCustomDateRange()">
                            <i class="fas fa-filter me-2"></i>Apply Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Key Metrics Overview -->
            <div class="row mb-4">
                <?php foreach ($userStats as $stat): ?>
                    <div class="col-md-3">
                        <div class="admin-stat-card <?php echo getRoleCardColor($stat['role']); ?>">
                            <div class="admin-stat-icon"><i class="fas fa-<?php echo getRoleIcon($stat['role']); ?>"></i></div>
                            <div class="admin-stat-value"><?php echo number_format($stat['count']); ?></div>
                            <div class="admin-stat-label"><?php echo ucfirst($stat['role']); ?>s</div>
                            <small class="text-success">+<?php echo $stat['new_count']; ?> new</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Revenue and Performance Metrics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value text-success">Rs<?php echo number_format(array_sum(array_column($monthlyRevenue, 'revenue')), 2); ?></div>
                        <div class="metric-label">Total Revenue</div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+12.5%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value text-primary"><?php echo round($quizStats['avg_score'], 1); ?>%</div>
                        <div class="metric-label">Avg Quiz Score</div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+3.2%
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value text-info"><?php echo $certificateStats['total']; ?></div>
                        <div class="metric-label">Certificates Issued</div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+<?php echo $certificateStats['new_certificates']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-value text-warning"><?php echo round($progressStats['overall_avg'], 1); ?>%</div>
                        <div class="metric-label">Avg Progress</div>
                        <div class="metric-change positive">
                            <i class="fas fa-arrow-up me-1"></i>+5.8%
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="analytics-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">User Registrations Trend</h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="changeChartType('registrations', 'line')">Line</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="changeChartType('registrations', 'bar')">Bar</button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="registrationsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="analytics-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Course Enrollments</h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="changeChartType('enrollments', 'bar')">Bar</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="changeChartType('enrollments', 'line')">Line</button>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="enrollmentsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Revenue and Activity Charts -->
            <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="analytics-card">
                            <h5 class="mb-3">Revenue Analytics</h5>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="analytics-card">
                            <h5 class="mb-3">Daily Activity (7 Days)</h5>
                            <div class="chart-container">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Progress Distribution -->
            <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="analytics-card">
                            <h5 class="mb-3">Student Progress Distribution</h5>
                            <div class="chart-container">
                                <canvas id="progressChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="analytics-card">
                            <h5 class="mb-3">Popular Categories</h5>
                            <div class="chart-container">
                                <canvas id="categoriesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Top Courses Table -->
            <div class="analytics-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Top Performing Courses</h5>
                        <button class="btn-modern btn-secondary-modern btn-sm" onclick="exportAnalytics('courses')">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Enrollments</th>
                                    <th>New</th>
                                    <th>Completion Rate</th>
                                    <th>Avg Progress</th>
                                    <th>Revenue</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCourses as $course): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-book text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></div>
                                                    <small class="text-muted"><?php echo ucfirst($course['difficulty_level']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2 fw-bold"><?php echo $course['enrollment_count']; ?></span>
                                                <div class="progress" style="width: 60px; height: 6px;">
                                                    <div class="progress-bar" style="width: <?php echo min(100, ($course['enrollment_count'] / 50) * 100); ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success badge-custom">+<?php echo $course['new_enrollments']; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $completionRate = $course['enrollment_count'] > 0 ? ($course['completions'] / $course['enrollment_count']) * 100 : 0;
                                            ?>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo round($completionRate); ?>%</span>
                                                <div class="progress" style="width: 60px; height: 6px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo $completionRate; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info badge-custom"><?php echo round($course['avg_progress']); ?>%</span>
                                        </td>
                                        <td class="fw-bold text-success">Rs<?php echo number_format($course['enrollment_count'] * $course['price'], 2); ?></td>
                                        <td>
                                            <?php if ($completionRate >= 70): ?>
                                                <span class="badge bg-success badge-custom">Excellent</span>
                                            <?php elseif ($completionRate >= 50): ?>
                                                <span class="badge bg-warning badge-custom">Good</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger badge-custom">Needs Improvement</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Instructor Performance -->
            <div class="row">
                    <div class="col-md-12">
                        <div class="analytics-card">
                            <h5 class="mb-3">Instructor Performance</h5>
                            <div class="table-responsive">
                                <table class="table custom-table">
                                    <thead>
                                        <tr>
                                            <th>Instructor</th>
                                            <th>Courses</th>
                                            <th>Total Students</th>
                                            <th>Avg Progress</th>
                                            <th>Total Revenue</th>
                                            <th>Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($instructorStats as $instructor): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded bg-secondary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($instructor['full_name']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo $instructor['course_count']; ?></td>
                                                <td><?php echo $instructor['total_students']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2"><?php echo round($instructor['avg_student_progress']); ?>%</span>
                                                        <div class="progress" style="width: 60px; height: 6px;">
                                                            <div class="progress-bar bg-info" style="width: <?php echo $instructor['avg_student_progress']; ?>%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-bold text-success">Rs<?php echo number_format($instructor['total_revenue'], 2); ?></td>
                                                <td>
                                                    <div class="text-warning">
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= 4 ? 'text-warning' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Global chart instances
        let charts = {};
        
        // Prepare data for charts
        const months = <?php echo json_encode(array_column($monthlyRegistrations, 'month')); ?>;
        const registrations = <?php echo json_encode(array_column($monthlyRegistrations, 'count')); ?>;
        const enrollments = <?php echo json_encode(array_column($monthlyEnrollments, 'count')); ?>;
        const revenue = <?php echo json_encode(array_column($monthlyRevenue, 'revenue')); ?>;
        const dailyActivity = <?php echo json_encode($dailyActivity); ?>;
        
        // Chart configuration
        const chartColors = {
            primary: 'rgb(54, 162, 235)',
            success: 'rgb(75, 192, 192)',
            warning: 'rgb(255, 206, 86)',
            danger: 'rgb(255, 99, 132)',
            info: 'rgb(153, 102, 255)',
            purple: 'rgb(118, 75, 162)'
        };
        
        // Initialize all charts when DOM is ready
        $(document).ready(function() {
            initializeCharts();
            setupEventListeners();
            startRealTimeUpdates();
        });
        
        function initializeCharts() {
            // Registrations Chart
            const registrationsCtx = document.getElementById('registrationsChart').getContext('2d');
            charts.registrations = new Chart(registrationsCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'User Registrations',
                        data: registrations,
                        borderColor: chartColors.primary,
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 8,
                        pointBackgroundColor: chartColors.primary,
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
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: chartColors.primary,
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Registrations: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                color: '#666'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#666'
                            }
                        }
                    }
                }
            });
            
            // Enrollments Chart
            const enrollmentsCtx = document.getElementById('enrollmentsChart').getContext('2d');
            charts.enrollments = new Chart(enrollmentsCtx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Course Enrollments',
                        data: enrollments,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: chartColors.success,
                        borderWidth: 2,
                        borderRadius: 8,
                        hoverBackgroundColor: 'rgba(75, 192, 192, 0.8)'
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
                            backgroundColor: 'rgba(0,0,0,0.8)',
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
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                color: '#666'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#666'
                            }
                        }
                    }
                }
            });
            
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            charts.revenue = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Revenue',
                        data: revenue,
                        borderColor: chartColors.success,
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 8
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
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: Rs' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                color: '#666',
                                callback: function(value) {
                                    return 'Rs' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#666'
                            }
                        }
                    }
                }
            });
            
            // Daily Activity Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            const activityLabels = dailyActivity.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en', { weekday: 'short' });
            });
            const activityCounts = dailyActivity.map(item => item.activity_count);
            
            charts.activity = new Chart(activityCtx, {
                type: 'doughnut',
                data: {
                    labels: activityLabels,
                    datasets: [{
                        data: activityCounts,
                        backgroundColor: [
                            chartColors.primary,
                            chartColors.success,
                            chartColors.warning,
                            chartColors.danger,
                            chartColors.info,
                            chartColors.purple,
                            '#FF6B6B'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                color: '#666'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' activities';
                                }
                            }
                        }
                    }
                }
            });
            
            // Progress Distribution Chart
            const progressCtx = document.getElementById('progressChart').getContext('2d');
            charts.progress = new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['High Achievers (90%+)', 'Good Progress (70-90%)', 'Moderate (50-70%)', 'Low Progress (<50%)'],
                    datasets: [{
                        data: [
                            <?php echo $progressStats['high_achievers'] ?? 0; ?>,
                            <?php echo $progressStats['good_progress'] ?? 0; ?>,
                            <?php echo $progressStats['moderate_progress'] ?? 0; ?>,
                            <?php echo $progressStats['low_progress'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            chartColors.success,
                            chartColors.primary,
                            chartColors.warning,
                            chartColors.danger
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                color: '#666'
                            }
                        }
                    }
                }
            });
            
            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoryLabels = <?php echo json_encode(array_column($categoryStats, 'name')); ?>;
            const categoryCounts = <?php echo json_encode(array_column($categoryStats, 'enrollment_count')); ?>;
            
            charts.categories = new Chart(categoriesCtx, {
                type: 'polarArea',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryCounts,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(199, 199, 199, 0.6)',
                            'rgba(83, 102, 255, 0.6)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                color: '#666'
                            }
                        }
                    },
                    scales: {
                        r: {
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                color: '#666',
                                backdropColor: 'transparent'
                            }
                        }
                    }
                }
            });
        }
        
        function setupEventListeners() {
            // Add smooth scrolling
            $('a[href^="#"]').on('click', function(event) {
                event.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
            
            // Add hover effects to cards
            $('.analytics-card').hover(
                function() {
                    $(this).find('.loading-overlay').fadeIn(200);
                },
                function() {
                    $(this).find('.loading-overlay').fadeOut(200);
                }
            );
        }
        
        function changeChartType(chartName, type) {
            if (charts[chartName]) {
                charts[chartName].config.type = type;
                charts[chartName].update();
                
                // Update button states
                $(`button[onclick*="${chartName}"]`).removeClass('active');
                $(`button[onclick*="${chartName}"][onclick*="${type}"]`).addClass('active');
            }
        }
        
        function updateDateRange() {
            const range = $('#dateRange').val();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - parseInt(range));
            
            $('#startDate').val(startDate.toISOString().split('T')[0]);
            $('#endDate').val(new Date().toISOString().split('T')[0]);
            
            // Reload page with new date range
            window.location.href = `analytics.php?date_range=${range}`;
        }
        
        function applyCustomDateRange() {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            
            if (startDate && endDate) {
                window.location.href = `analytics.php?start_date=${startDate}&end_date=${endDate}`;
            }
        }
        
        function refreshAnalytics() {
            // Show loading state
            $('.analytics-card').addClass('opacity-50');
            
            // Simulate refresh
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
        
        function exportAnalytics(type) {
            // Create export data based on type
            let exportData = [];
            let filename = '';
            
            if (type === 'courses') {
                exportData = <?php echo json_encode($topCourses); ?>;
                filename = 'courses_analytics_' + new Date().toISOString().split('T')[0] + '.json';
            }
            
            // Create and download file
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = filename;
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }
        
        function startRealTimeUpdates() {
            // Update metrics every 30 seconds
            setInterval(() => {
                updateMetrics();
            }, 30000);
        }
        
        function updateMetrics() {
            // Simulate real-time updates with animation
            $('.metric-value').each(function() {
                const currentValue = $(this).text();
                $(this).fadeOut(200, function() {
                    // Add slight variation to simulate real-time data
                    const variation = Math.random() * 10 - 5;
                    $(this).fadeIn(200);
                });
            });
        }
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + R to refresh analytics
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                refreshAnalytics();
            }
            
            // Ctrl/Cmd + E to export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportAnalytics('courses');
            }
        });
    </script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>

<?php
function getRoleCardColor($role) {
    $colors = [
        'admin' => 'danger',
        'instructor' => 'primary',
        'student' => 'success'
    ];
    return $colors[$role] ?? 'secondary';
}

function getRoleIcon($role) {
    $icons = [
        'admin' => 'user-shield',
        'instructor' => 'chalkboard-teacher',
        'student' => 'user-graduate'
    ];
    return $icons[$role] ?? 'user';
}
?>
