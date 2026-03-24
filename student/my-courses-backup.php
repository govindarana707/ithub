<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
requireStudent();

$course = new Course();
$studentId = $_SESSION['user_id'];

try {
    // Get dynamic enrollment statistics
    $stats = $course->getEnrollmentStats($studentId);

    // Get all enrolled courses with real-time progress
    $allCourses = $course->getEnrolledCourses($studentId);

    // Update progress for each course dynamically with real data
    foreach ($allCourses as &$courseData) {
        $courseData['progress_percentage'] = $course->calculateCourseProgress($studentId, $courseData['id']);
        $courseData['has_certificate'] = $course->hasCertificate($studentId, $courseData['id']);
        $courseData['study_hours'] = $course->getStudyTime($studentId, $courseData['id']) ?: 0;

        // Get real rating data (fallback to default if not available)
        $courseData['rating'] = $courseData['rating'] ?? 4.5;
        $courseData['reviews_count'] = $courseData['reviews_count'] ?? 0;

        // Get real last accessed time from enrollment
        $enrollment = $course->getEnrollment($studentId, $courseData['id']);
        $courseData['last_accessed'] = $enrollment['last_accessed'] ?? $enrollment['enrollment_date'] ?? date('Y-m-d H:i:s');
        $courseData['enrollment_date'] = $enrollment['enrollment_date'] ?? date('Y-m-d H:i:s');
    }

    // Filter courses based on real-time progress
    $ongoingCourses = array_filter($allCourses, fn($c) => ($c['progress_percentage'] ?? 0) < 100);
    $completedCourses = array_filter($allCourses, fn($c) => ($c['progress_percentage'] ?? 0) >= 100);

    // Get user data for profile
    require_once dirname(__DIR__) . '/models/User.php';
    $user = new User();
    $userData = $user->getUserById($studentId);

} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Error loading courses: " . $e->getMessage());
    $stats = [
        'total_enrollments' => 0,
        'in_progress' => 0,
        'completed_courses' => 0,
        'total_study_hours' => 0
    ];
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
        /* Consistent with dashboard theme */
        .dashboard-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            margin-left: 1rem;
        }

        .page-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .nav-tabs-custom {
            border: none;
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            color: var(--dark-color);
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .nav-tabs-custom .nav-link:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .nav-tabs-custom .nav-link.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .dashboard-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card.primary {
            background: var(--gradient-primary);
            color: white;
        }

        .stat-card.info {
            background: var(--gradient-info);
            color: white;
        }

        .stat-card.success {
            background: var(--gradient-success);
            color: white;
        }

        .stat-card.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card small {
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .course-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .course-thumbnail {
            position: relative;
            height: 180px;
            overflow: hidden;
            background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%, #f0f0f0),
                linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%, #f0f0f0);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }

        .course-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .course-card:hover .course-thumbnail img {
            transform: scale(1.05);
        }

        .course-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.95);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .course-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .course-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .course-instructor {
            color: #6a6f73;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .course-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #6a6f73;
        }

        .course-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #f4c150;
        }

        .progress-section {
            margin-top: auto;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            background: var(--gradient-primary);
            transition: width 0.3s ease;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .progress-bar.bg-gradient-primary {
            background: var(--gradient-primary) !important;
        }

        .course-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-continue {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            flex: 1;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary-custom {
            background: white;
            color: var(--dark-color);
            border: 1px solid var(--light-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-secondary-custom:hover {
            background: var(--light-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .empty-state i {
            font-size: 4rem;
            color: #6a6f73;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .last-accessed {
            font-size: 0.75rem;
            color: #6a6f73;
            margin-bottom: 1rem;
        }

        .certificate-badge {
            background: var(--gradient-success);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .input-group {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .input-group-text {
            border: none;
            padding: 0.75rem 1rem;
            color: var(--primary-color);
        }

        .form-control,
        .form-select {
            border: none;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            border-color: transparent;
        }

        .input-group .form-control {
            box-shadow: none;
        }

        .input-group .form-control:focus {
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1rem;
            }

            .course-card {
                margin-bottom: 1.5rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .stat-card h3 {
                font-size: 2rem;
            }
        }
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
                        <a class="nav-link dropdown-toggle" href="#" id="studentDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> Student
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item active" href="my-courses.php">My Courses</a></li>
                            <li><a class="dropdown-item" href="courses.php">Course Catalog</a></li>
                            <li><a class="dropdown-item" href="certificates.php">Certificates</a></li>
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
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
                        <a href="quiz-results.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> Quiz Results
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </div>
                </div>

                <div class="col-md-9">
                    <div class="main-content">
                        <!-- Page Header -->
                        <div class="page-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h1 class="display-6 fw-bold mb-2">My Courses</h1>
                                    <p class="lead mb-0 opacity-75">Track your learning progress and continue where you
                                        left off</p>
                                </div>
                                <button class="btn btn-light btn-lg" onclick="refreshStats()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Search and Filter Section -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="courseSearch" class="form-control"
                                        placeholder="Search courses by title, instructor, or category...">
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <select id="sortBy" class="form-select">
                                    <option value="recent">Recently Accessed</option>
                                    <option value="progress">Progress (High to Low)</option>
                                    <option value="progress-asc">Progress (Low to High)</option>
                                    <option value="title">Title (A-Z)</option>
                                    <option value="title-desc">Title (Z-A)</option>
                                    <option value="rating">Rating (High to Low)</option>
                                    <option value="enrollment">Enrollment Date</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <select id="filterCategory" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php
                                    $categories = array_unique(array_column($allCourses, 'category_name'));
                                    foreach ($categories as $category) {
                                        if ($category) {
                                            echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Tabs -->
                        <ul class="nav nav-tabs-custom" id="courseTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab"
                                    data-bs-target="#all-courses" type="button" role="tab">
                                    All Courses
                                    <span class="badge bg-light text-dark ms-2"
                                        id="all-count"><?php echo count($allCourses); ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="active-tab" data-bs-toggle="tab"
                                    data-bs-target="#active-courses" type="button" role="tab">
                                    Active
                                    <span class="badge bg-light text-dark ms-2"
                                        id="active-count"><?php echo count($ongoingCourses); ?></span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="completed-tab" data-bs-toggle="tab"
                                    data-bs-target="#completed-courses" type="button" role="tab">
                                    Completed
                                    <span class="badge bg-light text-dark ms-2"
                                        id="completed-count"><?php echo count($completedCourses); ?></span>
                                </button>
                            </li>
                        </ul>

                        <!-- Quick Stats Row -->
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

                        <!-- Tab Content -->
                        <div class="tab-content" id="courseTabsContent">
                            <!-- All Courses Tab -->
                            <div class="tab-pane fade show active" id="all-courses" role="tabpanel">
                                <?php if (!empty($allCourses)): ?>
                                    <div class="row">
                                        <?php foreach ($allCourses as $course): ?>
                                            <div class="col-lg-4 col-md-6 mb-4"
                                                data-last-accessed="<?php echo htmlspecialchars($course['last_accessed']); ?>"
                                                data-enrollment="<?php echo htmlspecialchars($course['enrollment_date']); ?>"
                                                data-progress="<?php echo $course['progress_percentage'] ?? 0; ?>">
                                                <div class="course-card">
                                                    <div class="course-thumbnail">
                                                        <?php if ($course['thumbnail']): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>"
                                                                alt="<?php echo htmlspecialchars($course['title']); ?>">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                                <i class="fas fa-image fa-3x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (($course['progress_percentage'] ?? 0) >= 100): ?>
                                                            <div class="course-badge bg-success text-white">
                                                                <i class="fas fa-check me-1"></i>Completed
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="course-badge">
                                                                <i
                                                                    class="fas fa-clock me-1"></i><?php echo $course['duration_hours'] ?? 0; ?>h
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="course-content">
                                                        <?php if (($course['progress_percentage'] ?? 0) >= 100): ?>
                                                            <div class="certificate-badge">
                                                                <i class="fas fa-trophy"></i>
                                                                Certificate Earned
                                                            </div>
                                                        <?php endif; ?>

                                                        <h3 class="course-title">
                                                            <?php echo htmlspecialchars($course['title']); ?>
                                                        </h3>
                                                        <div class="course-instructor">
                                                            <i
                                                                class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                                        </div>

                                                        <div class="last-accessed">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Last accessed
                                                            <?php echo date('M j, Y', strtotime($course['last_accessed'])); ?>
                                                        </div>

                                                        <div class="course-meta">
                                                            <span><i
                                                                    class="fas fa-tag me-1"></i><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                            <span><i
                                                                    class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level'] ?? 'beginner'); ?></span>
                                                        </div>

                                                        <div class="course-rating">
                                                            <div class="stars">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i
                                                                        class="fas fa-star <?php echo $i <= ($course['rating'] ?? 4.5) ? '' : 'text-muted'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <span
                                                                class="text-muted small"><?php echo $course['rating'] ?? 4.5; ?>
                                                                (<?php echo $course['reviews_count']; ?>)</span>
                                                        </div>

                                                        <div class="progress-section">
                                                            <div class="progress-label">
                                                                <span>Progress</span>
                                                                <span><?php echo round($course['progress_percentage'] ?? 0); ?>%</span>
                                                            </div>
                                                            <div class="progress">
                                                                <div class="progress-bar bg-gradient-primary"
                                                                    style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%">
                                                                    <?php echo round($course['progress_percentage'] ?? 0); ?>%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="course-actions">
                                                            <?php if (($course['progress_percentage'] ?? 0) < 100): ?>
                                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>"
                                                                    class="btn btn-continue"
                                                                    onclick="trackStudyTime(<?php echo $course['id']; ?>)">
                                                                    <i class="fas fa-play me-1"></i>Continue
                                                                </a>
                                                            <?php else: ?>
                                                                <?php if ($course['has_certificate']): ?>
                                                                    <a href="certificate.php?course_id=<?php echo $course['id']; ?>"
                                                                        class="btn btn-success flex-fill">
                                                                        <i class="fas fa-download me-1"></i>Certificate
                                                                    </a>
                                                                <?php else: ?>
                                                                    <button class="btn btn-warning flex-fill" disabled>
                                                                        <i class="fas fa-clock me-1"></i>Processing
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <a href="course-details.php?id=<?php echo $course['id']; ?>"
                                                                class="btn btn-secondary-custom">
                                                                <i class="fas fa-info-circle"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-book-open"></i>
                                        <h3>No courses enrolled yet</h3>
                                        <p class="text-muted">You haven't enrolled in any courses yet. Start your learning
                                            journey today!</p>
                                        <a href="courses.php" class="btn btn-primary btn-lg mt-3">
                                            <i class="fas fa-search me-2"></i>Browse Courses
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Active Courses Tab -->
                            <div class="tab-pane fade" id="active-courses" role="tabpanel">
                                <?php if (!empty($ongoingCourses)): ?>
                                    <div class="row">
                                        <?php foreach ($ongoingCourses as $course): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="course-card">
                                                    <div class="course-thumbnail">
                                                        <?php if ($course['thumbnail']): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>"
                                                                alt="<?php echo htmlspecialchars($course['title']); ?>">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                                <i class="fas fa-image fa-3x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="course-badge">
                                                            <i
                                                                class="fas fa-clock me-1"></i><?php echo $course['duration_hours'] ?? 0; ?>h
                                                        </div>
                                                    </div>

                                                    <div class="course-content">
                                                        <h3 class="course-title">
                                                            <?php echo htmlspecialchars($course['title']); ?>
                                                        </h3>
                                                        <div class="course-instructor">
                                                            <i
                                                                class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                                        </div>

                                                        <div class="last-accessed">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Last accessed
                                                            <?php echo date('M j, Y', strtotime($course['last_accessed'])); ?>
                                                        </div>

                                                        <div class="course-meta">
                                                            <span><i
                                                                    class="fas fa-tag me-1"></i><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                            <span><i
                                                                    class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level'] ?? 'beginner'); ?></span>
                                                        </div>

                                                        <div class="course-rating">
                                                            <div class="stars">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i
                                                                        class="fas fa-star <?php echo $i <= ($course['rating'] ?? 4.5) ? '' : 'text-muted'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <span
                                                                class="text-muted small"><?php echo $course['rating'] ?? 4.5; ?>
                                                                (<?php echo $course['reviews_count']; ?>)</span>
                                                        </div>

                                                        <div class="progress-section">
                                                            <div class="progress-label">
                                                                <span>Progress</span>
                                                                <span><?php echo round($course['progress_percentage'] ?? 0); ?>%</span>
                                                            </div>
                                                            <div class="progress">
                                                                <div class="progress-bar bg-gradient-primary"
                                                                    style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%">
                                                                    <?php echo round($course['progress_percentage'] ?? 0); ?>%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="course-actions">
                                                            <a href="lesson.php?course_id=<?php echo $course['id']; ?>"
                                                                class="btn btn-continue"
                                                                onclick="trackStudyTime(<?php echo $course['id']; ?>)">
                                                                <i class="fas fa-play me-1"></i>Continue
                                                            </a>
                                                            <a href="course-details.php?id=<?php echo $course['id']; ?>"
                                                                class="btn btn-secondary-custom">
                                                                <i class="fas fa-info-circle"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-play-circle"></i>
                                        <h3>No active courses</h3>
                                        <p class="text-muted">You don't have any courses in progress. Enroll in a course to
                                            start learning!</p>
                                        <a href="courses.php" class="btn btn-primary btn-lg mt-3">
                                            <i class="fas fa-search me-2"></i>Browse Courses
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Completed Courses Tab -->
                            <div class="tab-pane fade" id="completed-courses" role="tabpanel">
                                <?php if (!empty($completedCourses)): ?>
                                    <div class="row">
                                        <?php foreach ($completedCourses as $course): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="course-card">
                                                    <div class="course-thumbnail">
                                                        <?php if ($course['thumbnail']): ?>
                                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>"
                                                                alt="<?php echo htmlspecialchars($course['title']); ?>"
                                                                style="filter: grayscale(20%);">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                                <i class="fas fa-image fa-3x text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="course-badge bg-success text-white">
                                                            <i class="fas fa-check me-1"></i>Completed
                                                        </div>
                                                    </div>

                                                    <div class="course-content">
                                                        <div class="certificate-badge">
                                                            <i class="fas fa-trophy"></i>
                                                            Certificate Earned
                                                        </div>

                                                        <h3 class="course-title">
                                                            <?php echo htmlspecialchars($course['title']); ?>
                                                        </h3>
                                                        <div class="course-instructor">
                                                            <i
                                                                class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                                        </div>

                                                        <div class="last-accessed">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Completed
                                                            <?php echo date('M j, Y', strtotime($course['last_accessed'])); ?>
                                                        </div>

                                                        <div class="course-meta">
                                                            <span><i
                                                                    class="fas fa-tag me-1"></i><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                            <span><i
                                                                    class="fas fa-clock me-1"></i><?php echo $course['study_hours'] ?? 0; ?>h
                                                                studied</span>
                                                        </div>

                                                        <div class="course-rating">
                                                            <div class="stars">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i
                                                                        class="fas fa-star <?php echo $i <= ($course['rating'] ?? 4.5) ? '' : 'text-muted'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                            <span
                                                                class="text-muted small"><?php echo $course['rating'] ?? 4.5; ?>
                                                                (<?php echo $course['reviews_count']; ?>)</span>
                                                        </div>

                                                        <div class="progress-section">
                                                            <div class="progress-label">
                                                                <span>Progress</span>
                                                                <span>100%</span>
                                                            </div>
                                                            <div class="progress">
                                                                <div class="progress-bar bg-success" style="width: 100%">
                                                                    100%
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="course-actions">
                                                            <?php if ($course['has_certificate']): ?>
                                                                <a href="certificate.php?course_id=<?php echo $course['id']; ?>"
                                                                    class="btn btn-success flex-fill">
                                                                    <i class="fas fa-download me-1"></i>Certificate
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-warning flex-fill" disabled>
                                                                    <i class="fas fa-clock me-1"></i>Processing
                                                                </button>
                                                            <?php endif; ?>
                                                            <a href="course-details.php?id=<?php echo $course['id']; ?>"
                                                                class="btn btn-secondary-custom">
                                                                <i class="fas fa-info-circle"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-trophy"></i>
                                        <h3>No completed courses yet</h3>
                                        <p class="text-muted">Keep learning! Your achievements will appear here when you
                                            complete courses.</p>
                                        <a href="courses.php" class="btn btn-primary btn-lg mt-3">
                                            <i class="fas fa-compass me-2"></i>Explore Courses
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Store all courses data for filtering
        let allCoursesData = <?php echo json_encode($allCourses); ?>;
        let activeCoursesData = <?php echo json_encode(array_values($ongoingCourses)); ?>;
        let completedCoursesData = <?php echo json_encode(array_values($completedCourses)); ?>;

        // Debounce function for search
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Enhanced search function with category and instructor filtering
        function searchCourses(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            $('.course-card').each(function () {
                const title = $(this).find('.course-title').text().toLowerCase();
                const instructor = $(this).find('.course-instructor').text().toLowerCase();
                const category = $(this).find('.course-meta span:first').text().toLowerCase();

                if (title.includes(searchTerm) || instructor.includes(searchTerm) || category.includes(searchTerm)) {
                    $(this).parent().show();
                } else {
                    $(this).parent().hide();
                }
            });
            updateCounts();
        }

        // Filter by category
        function filterByCategory(category) {
            if (!category) {
                $('.course-card').parent().show();
            } else {
                $('.course-card').each(function () {
                    const courseCategory = $(this).find('.course-meta span:first').text().toLowerCase();
                    if (courseCategory.includes(category.toLowerCase())) {
                        $(this).parent().show();
                    } else {
                        $(this).parent().hide();
                    }
                });
            }
            updateCounts();
        }

        // Enhanced sort courses with additional options
        function sortCourses(sortBy) {
            const activeTab = $('.tab-pane.active').attr('id');
            const container = $('#' + activeTab + ' .row').first();
            const cards = container.children('.col-lg-4');

            cards.sort(function (a, b) {
                const cardA = $(a);
                const cardB = $(b);

                switch (sortBy) {
                    case 'title':
                        return cardA.find('.course-title').text().localeCompare(cardB.find('.course-title').text());
                    case 'title-desc':
                        return cardB.find('.course-title').text().localeCompare(cardA.find('.course-title').text());
                    case 'progress':
                        const progressA = parseInt(cardA.find('.progress-bar').text()) || 0;
                        const progressB = parseInt(cardB.find('.progress-bar').text()) || 0;
                        return progressB - progressA;
                    case 'progress-asc':
                        const progressAscA = parseInt(cardA.find('.progress-bar').text()) || 0;
                        const progressAscB = parseInt(cardB.find('.progress-bar').text()) || 0;
                        return progressAscA - progressAscB;
                    case 'enrollment':
                        const dateA = cardA.data('enrollment') || '';
                        const dateB = cardB.data('enrollment') || '';
                        return dateB.localeCompare(dateA);
                    case 'rating':
                        const ratingA = parseFloat(cardA.find('.course-rating .text-muted').text()) || 0;
                        const ratingB = parseFloat(cardB.find('.course-rating .text-muted').text()) || 0;
                        return ratingB - ratingA;
                    case 'recent':
                    default:
                        const lastA = cardA.data('last-accessed') || '';
                        const lastB = cardB.data('last-accessed') || '';
                        return lastB.localeCompare(lastA);
                }
            });

            cards.detach().appendTo(container);
        }

        // Update visible counts
        function updateCounts() {
            $('#all-count').text($('#all-courses .course-card:visible').length);
            $('#active-count').text($('#active-courses .course-card:visible').length);
            $('#completed-count').text($('#completed-courses .course-card:visible').length);
        }

        // Refresh statistics dynamically
        function refreshStats() {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            btn.disabled = true;

            $.ajax({
                url: '../api/get_student_stats.php',
                method: 'GET',
                data: { student_id: <?php echo $studentId; ?> },
                success: function (response) {
                    if (response.success) {
                        // Update stat cards with animation
                        const statNumbers = document.querySelectorAll('.stat-card h3');
                        animateValue(statNumbers[0], parseInt(statNumbers[0].textContent), response.data.total_enrollments, 500);
                        animateValue(statNumbers[1], parseInt(statNumbers[1].textContent), response.data.in_progress, 500);
                        animateValue(statNumbers[2], parseInt(statNumbers[2].textContent), response.data.completed_courses, 500);

                        const studyHours = response.data.total_study_hours;
                        animateValue(statNumbers[3], parseInt(statNumbers[3].textContent), studyHours, 500, 'h');

                        // Show success toast
                        showToast('Stats updated successfully!', 'success');
                    }
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                },
                error: function (xhr) {
                    console.error('Failed to refresh stats:', xhr);
                    showToast('Failed to refresh stats', 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        // Animate number changes
        function animateValue(element, start, end, duration, suffix = '') {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const value = Math.floor(progress * (end - start) + start);
                element.textContent = value + suffix;
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Track study time when user starts a lesson
        let studySessionActive = false;
        let currentCourseId = null;

        function trackStudyTime(courseId) {
            currentCourseId = courseId;
            studySessionActive = true;

            // Update last accessed time
            updateLastAccessed(courseId);

            // Start tracking session
            $.ajax({
                url: '../api/track_study_time.php',
                method: 'POST',
                data: {
                    course_id: courseId,
                    student_id: <?php echo $studentId; ?>,
                    action: 'start'
                },
                success: function (response) {
                    if (response.success) {
                        // Store start time in localStorage
                        localStorage.setItem('study_start_' + courseId, Date.now());
                    }
                },
                error: function (xhr) {
                    console.error('Failed to start study session:', xhr);
                }
            });
        }

        // Update last accessed time
        function updateLastAccessed(courseId) {
            $.ajax({
                url: '../api/update_last_accessed.php',
                method: 'POST',
                data: {
                    course_id: courseId,
                    student_id: <?php echo $studentId; ?>
                }
            });
        }

        // End study session when leaving page
        window.addEventListener('beforeunload', function () {
            if (studySessionActive && currentCourseId) {
                navigator.sendBeacon('../api/track_study_time.php', JSON.stringify({
                    course_id: currentCourseId,
                    student_id: <?php echo $studentId; ?>,
                    action: 'end'
                }));
            }
        });

        // Show toast notification
        function showToast(message, type = 'info') {
            const toastContainer = $('#toastContainer');
            if (toastContainer.length === 0) {
                $('body').append('<div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
            }

            const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
            const toast = $(`
                <div class="toast align-items-center text-white ${bgColor} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `);

            $('#toastContainer').append(toast);
            const toastInstance = new bootstrap.Toast(toast[0], { delay: 3000 });
            toastInstance.show();

            toast.on('hidden.bs.toast', function () {
                toast.remove();
            });
        }

        // Initialize on document ready
        $(document).ready(function () {
            // Add data attributes for sorting
            $('.course-card').each(function () {
                const parent = $(this).parent();
                const lastAccessed = $(this).find('.last-accessed').text();
                parent.attr('data-last-accessed', lastAccessed);
                parent.attr('data-enrollment', '<?php echo date("Y-m-d"); ?>'); // Add real enrollment date if available
            });

            // Search event listener
            const debouncedSearch = debounce(function (e) {
                searchCourses(e.target.value);
            }, 300);

            $('#courseSearch').on('input', debouncedSearch);

            // Sort event listener
            $('#sortBy').on('change', function () {
                sortCourses($(this).val());
            });

            // Filter event listener
            $('#filterCategory').on('change', function () {
                filterByCategory($(this).val());
            });

            // Tab change event
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
                // Reapply filters and search when switching tabs
                const searchTerm = $('#courseSearch').val();
                const category = $('#filterCategory').val();
                const sortBy = $('#sortBy').val();

                if (searchTerm) searchCourses(searchTerm);
                if (category) filterByCategory(category);
                if (sortBy) sortCourses(sortBy);
            });

            // Auto-refresh progress every 2 minutes (more reasonable than 30 seconds)
            setInterval(function () {
                refreshStats();
            }, 120000);

            // Add smooth scroll animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            document.querySelectorAll('.course-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Add hover effect to stat cards
            $('.stat-card').hover(
                function () {
                    $(this).css('transform', 'scale(1.05)');
                },
                function () {
                    $(this).css('transform', 'scale(1)');
                }
            );
        });
    </script>
</body>

</html>