<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];
$dateRange = $_GET['date_range'] ?? '30days';

// Get analytics data
$analytics = $instructor->getInstructorAnalytics($instructorId, $dateRange);
$earnings = $instructor->getInstructorEarnings($instructorId, $dateRange);

// Get instructor profile
$profile = $instructor->getInstructorProfile($instructorId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Instructor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .performance-table {
            font-size: 0.9rem;
        }
        .performance-table .progress {
            height: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a class="nav-link" href="courses.php">
                    <i class="fas fa-chalkboard-teacher me-1"></i> My Courses
                </a>
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users me-1"></i> Students
                </a>
                <a class="nav-link" href="analytics.php">
                    <i class="fas fa-chart-line me-1"></i> Analytics
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="courses.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chalkboard-teacher me-2"></i> My Courses
                    </a>
                    <a href="students.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="analytics.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chart-line me-2"></i> Analytics
                    </a>
                    <a href="earnings.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-rupee-sign me-2"></i> Earnings
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Profile
                    </a>
                </div>
                
                <!-- Date Range Filter -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Date Range</h6>
                        <form method="GET">
                            <select name="date_range" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="7days" <?php echo $dateRange === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30days" <?php echo $dateRange === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="90days" <?php echo $dateRange === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                                <option value="1year" <?php echo $dateRange === '1year' ? 'selected' : ''; ?>>Last Year</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Analytics Dashboard</h1>
                    <div>
                        <span class="badge bg-info">Instructor Analytics</span>
                    </div>
                </div>

                <!-- Overview Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $analytics['overview']['total_courses'] ?? 0; ?></h3>
                            <p>Total Courses</p>
                            <small><i class="fas fa-book"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $analytics['overview']['published_courses'] ?? 0; ?></h3>
                            <p>Published</p>
                            <small><i class="fas fa-check-circle"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $analytics['overview']['total_students'] ?? 0; ?></h3>
                            <p>Total Students</p>
                            <small><i class="fas fa-users"></i></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo round($analytics['overview']['avg_progress'] ?? 0, 1); ?>%</h3>
                            <p>Avg Progress</p>
                            <small><i class="fas fa-chart-line"></i></small>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Trend -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Enrollment Trend</h5>
                        <div class="chart-container">
                            <canvas id="enrollmentChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Student Engagement -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Student Engagement</h5>
                                <div class="chart-container">
                                    <canvas id="engagementChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Completion Status</h5>
                                <div class="chart-container">
                                    <canvas id="completionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Performance -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Course Performance</h5>
                        <div class="table-responsive">
                            <table class="table table-sm performance-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Students</th>
                                        <th>Avg Progress</th>
                                        <th>Completions</th>
                                        <th>Rating</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['course_performance'] as $course): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($course['status']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $course['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo $course['enrollments']; ?></span>
                                                    <div class="progress ms-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar" style="width: <?php echo min(100, ($course['enrollments'] / max(1, $analytics['overview']['total_students'])) * 100); ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span><?php echo round($course['avg_progress'], 1); ?>%</span>
                                                    <div class="progress ms-2" style="width: 50px; height: 8px;">
                                                        <div class="progress-bar" style="width: <?php echo $course['avg_progress']; ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $course['completions']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($course['avg_rating']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="text-warning">
                                                            <i class="fas fa-star"></i>
                                                            <?php echo number_format($course['avg_rating'], 1); ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No ratings</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="course-stats.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Stats">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <a href="../admin/course_builder.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-success" title="Edit Course">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Enrollment Trend Chart
        const enrollmentCtx = document.getElementById('enrollmentChart').getContext('2d');
        const enrollmentData = <?php echo json_encode(array_reverse($analytics['enrollment_trend'])); ?>;
        
        new Chart(enrollmentCtx, {
            type: 'line',
            data: {
                labels: enrollmentData.map(item => item.date),
                datasets: [{
                    label: 'Daily Enrollments',
                    data: enrollmentData.map(item => item.enrollments),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Student Engagement Chart
        const engagementCtx = document.getElementById('engagementChart').getContext('2d');
        const engagementData = <?php echo json_encode($analytics['student_engagement']); ?>;
        
        new Chart(engagementCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [
                        engagementData.active_students || 0,
                        engagementData.completed_students || 0,
                        engagementData.in_progress_students || 0,
                        engagementData.not_started_students || 0
                    ],
                    backgroundColor: [
                        '#198754',
                        '#0dcaf0',
                        '#ffc107',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Completion Status Chart
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        const completionData = <?php echo json_encode($analytics['overview']); ?>;
        
        new Chart(completionCtx, {
            type: 'pie',
            data: {
                labels: ['Completed Students', 'Total Students'],
                datasets: [{
                    data: [
                        completionData.completed_students || 0,
                        (completionData.total_students || 0) - (completionData.completed_students || 0)
                    ],
                    backgroundColor: ['#198754', '#e9ecef']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
