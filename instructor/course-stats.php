<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

requireInstructor();

require_once '../models/Instructor.php';
require_once '../models/Course.php';

$instructor = new Instructor();
$course = new Course();

$instructorId = $_SESSION['user_id'];
$courseId = $_GET['id'] ?? null;

if (!$courseId) {
    $_SESSION['error_message'] = 'Course ID not provided';
    header('Location: courses.php');
    exit;
}

// Verify course ownership
$courseData = $course->getCourseById($courseId);
if (!$courseData || $courseData['instructor_id'] != $instructorId) {
    $_SESSION['error_message'] = 'Course not found or access denied';
    header('Location: courses.php');
    exit;
}

// Get detailed statistics
$courseStats = $course->getCourseStatistics($courseId);
$enrolledStudents = $course->getEnrolledStudents($courseId);
$courseLessons = $course->getCourseLessons($courseId);

// Get instructor analytics for this course
$analytics = $instructor->getInstructorAnalytics($instructorId);
$coursePerformance = array_filter($analytics['course_performance'], function($c) use ($courseId) {
    return $c['id'] == $courseId;
});
$coursePerformance = array_values($coursePerformance)[0] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Statistics - <?php echo htmlspecialchars($courseData['title']); ?></title>
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
        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        .student-progress {
            margin-bottom: 10px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
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
                    <a href="course-stats.php?id=<?php echo $courseId; ?>" class="list-group-item list-group-item-action active">
                        <i class="fas fa-chart-bar me-2"></i> Course Stats
                    </a>
                    <a href="course-students.php?id=<?php echo $courseId; ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Students
                    </a>
                    <a href="../admin/course_builder.php?id=<?php echo $courseId; ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-screwdriver-wrench me-2"></i> Course Builder
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1>Course Statistics</h1>
                        <p class="text-muted"><?php echo htmlspecialchars($courseData['title']); ?></p>
                    </div>
                    <a href="courses.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Courses
                    </a>
                </div>

                <!-- Overview Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $courseStats['total_enrollments']; ?></h3>
                            <p>Total Enrollments</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $courseStats['active_enrollments']; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo $courseStats['completed_enrollments']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h3><?php echo round($courseStats['avg_progress'], 1); ?>%</h3>
                            <p>Average Progress</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Distribution -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Progress Distribution</h5>
                                <div class="chart-container">
                                    <canvas id="progressChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Completion Rate</h5>
                                <div class="text-center">
                                    <canvas id="completionChart" class="progress-ring"></canvas>
                                    <h4 class="mt-3">
                                        <?php 
                                        $completionRate = $courseStats['total_enrollments'] > 0 ? 
                                            round(($courseStats['completed_enrollments'] / $courseStats['total_enrollments']) * 100, 1) : 0;
                                        echo $completionRate; ?>%
                                    </h4>
                                    <p class="text-muted">Students who completed the course</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Student Activity -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Student Activity</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Enrolled</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                        <th>Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentStudents = array_slice($enrolledStudents, 0, 10);
                                    foreach ($recentStudents as $student): 
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($student['profile_image']): ?>
                                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($student['profile_image'])); ?>" 
                                                             class="rounded-circle me-2" width="30" height="30">
                                                    <?php else: ?>
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 30px; height: 30px; font-size: 12px;">
                                                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($student['full_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?></td>
                                            <td>
                                                <div class="progress student-progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $student['progress_percentage']; ?>%"
                                                         aria-valuenow="<?php echo $student['progress_percentage']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo round($student['progress_percentage']); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $student['enrollment_status'] === 'active' ? 'success' : 
                                                         ($student['enrollment_status'] === 'completed' ? 'primary' : 'warning'); ?>">
                                                    <?php echo ucfirst($student['enrollment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($enrolledStudents) > 10): ?>
                            <div class="text-center">
                                <a href="course-students.php?id=<?php echo $courseId; ?>" class="btn btn-outline-primary">
                                    View All Students
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course Content Stats -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Course Content</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h4 class="text-primary"><?php echo count($courseLessons); ?></h4>
                                    <p class="text-muted">Total Lessons</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h4 class="text-info"><?php echo $courseData['duration_hours']; ?></h4>
                                    <p class="text-muted">Duration (Hours)</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <h4 class="text-success"><?php echo htmlspecialchars($courseData['difficulty_level']); ?></h4>
                                    <p class="text-muted">Difficulty Level</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Stats -->
                <?php if ($courseData['price'] > 0): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Revenue Overview</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h4 class="text-success">Rs<?php echo number_format($courseData['price'] * $courseStats['total_enrollments'], 2); ?></h4>
                                    <p class="text-muted">Total Revenue</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <h4 class="text-primary">Rs<?php echo number_format($courseData['price'], 2); ?></h4>
                                    <p class="text-muted">Price per Enrollment</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Progress Distribution Chart
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        const progressData = {
            labels: ['Not Started', '0-25%', '26-50%', '51-75%', '76-99%', 'Completed'],
            datasets: [{
                label: 'Number of Students',
                data: [
                    <?php 
                    $notStarted = count(array_filter($enrolledStudents, function($s) { return $s['progress_percentage'] == 0; }));
                    echo $notStarted;
                    ?>,
                    <?php 
                    $low = count(array_filter($enrolledStudents, function($s) { return $s['progress_percentage'] > 0 && $s['progress_percentage'] <= 25; }));
                    echo $low;
                    ?>,
                    <?php 
                    $medium = count(array_filter($enrolledStudents, function($s) { return $s['progress_percentage'] > 25 && $s['progress_percentage'] <= 50; }));
                    echo $medium;
                    ?>,
                    <?php 
                    $high = count(array_filter($enrolledStudents, function($s) { return $s['progress_percentage'] > 50 && $s['progress_percentage'] <= 75; }));
                    echo $high;
                    ?>,
                    <?php 
                    $veryHigh = count(array_filter($enrolledStudents, function($s) { return $s['progress_percentage'] > 75 && $s['progress_percentage'] < 100; }));
                    echo $veryHigh;
                    ?>,
                    <?php echo $courseStats['completed_enrollments']; ?>
                ],
                backgroundColor: [
                    '#dc3545',
                    '#fd7e14',
                    '#ffc107',
                    '#20c997',
                    '#0dcaf0',
                    '#198754'
                ]
            }]
        };

        new Chart(progressCtx, {
            type: 'bar',
            data: progressData,
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

        // Completion Rate Chart (Doughnut)
        const completionCtx = document.getElementById('completionChart').getContext('2d');
        const completionRate = <?php echo $completionRate; ?>;
        
        new Chart(completionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Not Completed'],
                datasets: [{
                    data: [completionRate, 100 - completionRate],
                    backgroundColor: ['#198754', '#e9ecef'],
                    borderWidth: 0
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
                        enabled: false
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
