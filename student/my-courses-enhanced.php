<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
requireStudent();

$course = new Course();
$studentId = $_SESSION['user_id'];

try {
    $stats = $course->getEnrollmentStats($studentId);
    $allCourses = $course->getEnrolledCourses($studentId);
    
    foreach ($allCourses as &$courseData) {
        $courseData['progress_percentage'] = $course->calculateCourseProgress($studentId, $courseData['id']);
        $courseData['has_certificate'] = $course->hasCertificate($studentId, $courseData['id']);
        $courseData['study_hours'] = $course->getStudyTime($studentId, $courseData['id']) ?: 0;
        $courseData['rating'] = $courseData['rating'] ?? 4.5;
        $courseData['reviews_count'] = $courseData['reviews_count'] ?? 0;
        $enrollment = $course->getEnrollment($studentId, $courseData['id']);
        $courseData['last_accessed'] = $enrollment['last_accessed'] ?? $enrollment['enrollment_date'] ?? date('Y-m-d H:i:s');
        $courseData['enrollment_date'] = $enrollment['enrollment_date'] ?? date('Y-m-d H:i:s');
    }
    
    $ongoingCourses = array_filter($allCourses, fn($c) => ($c['progress_percentage'] ?? 0) < 100);
    $completedCourses = array_filter($allCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100);
    
} catch (Exception $e) {
    error_log("Error loading courses: " . $e->getMessage());
    $stats = ['total_enrollments' => 0, 'in_progress' => 0, 'completed_courses' => 0, 'total_study_hours' => 0];
    $allCourses = [];
    $ongoingCourses = [];
    $completedCourses = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-container { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .main-content { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); padding: 2rem; }
        .page-header { background: var(--gradient-primary); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; }
        .course-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; height: 100%; }
        .course-card:hover { transform: translateY(-8px); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15); }
        .course-thumbnail { position: relative; height: 180px; overflow: hidden; }
        .course-thumbnail img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .course-card:hover .course-thumbnail img { transform: scale(1.05); }
        .progress { height: 8px; border-radius: 4px; overflow: hidden; background: rgba(0, 0, 0, 0.1); }
        .progress-bar { background: var(--gradient-primary); transition: width 0.3s ease; }
        .btn-continue { background: var(--gradient-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; transition: all 0.3s ease; }
        .btn-continue:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
        .stat-card { text-align: center; padding: 1.5rem; border-radius: 12px; background: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12); }
        .stat-card.primary { background: var(--gradient-primary); color: white; }
        .stat-card.success { background: var(--gradient-success); color: white; }
        .stat-card.info { background: var(--gradient-info); color: white; }
        .stat-card.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
    </style>
</head>
<body>
    <div class="dashboard-container">
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
                            <li><a class="dropdown-item active" href="my-courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="courses.php">Course Catalog</a></li>
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
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="my-courses.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-book-open me-2"></i> My Courses
                        </a>
                        <a href="courses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i> Course Catalog
                        </a>
                        <a href="certificates.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-certificate me-2"></i> Certificates
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="main-content">
                        <div class="page-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1 class="display-6 fw-bold mb-2">My Courses</h1>
                                    <p class="lead mb-0 opacity-75">Track your learning progress and continue where you left off</p>
                                </div>
                                <button class="btn btn-light btn-lg" onclick="refreshStats()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh
                                </button>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card primary">
                                    <h3><?php echo $stats['total_enrollments']; ?></h3>
                                    <p>Total Enrolled</p>
                                    <small><i class="fas fa-book me-1"></i>Courses</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card info">
                                    <h3><?php echo $stats['in_progress']; ?></h3>
                                    <p>Active Courses</p>
                                    <small><i class="fas fa-play me-1"></i>In Progress</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card success">
                                    <h3><?php echo $stats['completed_courses']; ?></h3>
                                    <p>Completed</p>
                                    <small><i class="fas fa-check-circle me-1"></i>Finished</small>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card warning">
                                    <h3><?php echo $stats['total_study_hours']; ?>h</h3>
                                    <p>Study Time</p>
                                    <small><i class="fas fa-clock me-1"></i>Total Hours</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (!empty($allCourses)): ?>
                                <?php foreach ($allCourses as $course): ?>
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="course-card">
                                            <div class="course-thumbnail">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                        <i class="fas fa-image fa-3x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (($course['progress_percentage'] ?? 0) >= 100: ?>
                                                    <div class="position-absolute top-0 end-0 m-2">
                                                        <span class="badge bg-success">Completed</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                                </p>
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level'] ?? 'beginner'); ?>
                                                    </small>
                                                </div>
                                                <div class="progress mb-3">
                                                    <div class="progress-bar" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%">
                                                        <?php echo round($course['progress_percentage'] ?? 0); ?>%
                                                    </div>
                                                </div>
                                                <div class="d-grid gap-2">
                                                    <?php if (($course['progress_percentage'] ?? 0) < 100): ?>
                                                        <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-continue">
                                                            <i class="fas fa-play me-1"></i>Continue Learning
                                                        </a>
                                                    <?php else: ?>
                                                        <?php if ($course['has_certificate']): ?>
                                                            <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">
                                                                <i class="fas fa-download me-1"></i>Download Certificate
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary">
                                                        <i class="fas fa-info-circle me-1"></i>Course Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                                        <h3>No courses enrolled yet</h3>
                                        <p class="text-muted">You haven't enrolled in any courses yet. Start your learning journey today!</p>
                                        <a href="courses.php" class="btn btn-primary btn-lg">
                                            <i class="fas fa-search me-2"></i>Browse Courses
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function refreshStats() {
            location.reload();
        }
    </script>
</body>
</html>
