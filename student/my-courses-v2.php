<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
requireStudent();

$course = new Course();
$studentId = $_SESSION['user_id'];

// Initialize data array
$data = [
    'stats' => [],
    'allCourses' => [],
    'ongoingCourses' => [],
    'completedCourses' => [],
    'recommendations' => [],
    'learningStreak' => 0,
    'weeklyProgress' => [],
    'achievements' => []
];

try {
    // Get enrollment statistics
    $data['stats'] = $course->getEnrollmentStats($studentId);
    
    // Get all enrolled courses with real-time progress
    $data['allCourses'] = $course->getEnrolledCourses($studentId);
    
    // Enhance course data
    foreach ($data['allCourses'] as &$courseData) {
        $courseData['progress_percentage'] = $course->calculateCourseProgress($studentId, $courseData['id']);
        $courseData['has_certificate'] = $course->hasCertificate($studentId, $courseData['id']);
        $courseData['study_hours'] = $course->getStudyTime($studentId, $courseData['id']) ?: 0;
        $courseData['rating'] = $courseData['rating'] ?? 4.5;
        $courseData['reviews_count'] = $courseData['reviews_count'] ?? 0;
        
        $enrollment = $course->getEnrollment($studentId, $courseData['id']);
        $courseData['last_accessed'] = $enrollment['last_accessed'] ?? $enrollment['enrolled_at'] ?? date('Y-m-d H:i:s');
        $courseData['enrollment_date'] = $enrollment['enrolled_at'] ?? date('Y-m-d H:i:s');
        
        // Calculate days since last accessed
        $lastAccessed = strtotime($courseData['last_accessed']);
        $courseData['days_inactive'] = floor((time() - $lastAccessed) / 86400);
        
        // Get lessons info
        $lessons = $course->getCourseLessons($courseData['id'], $studentId);
        $nextLesson = null;
        foreach ($lessons as $lesson) {
            if (empty($lesson['is_completed'])) {
                $nextLesson = $lesson;
                break;
            }
        }
        $courseData['next_lesson'] = $nextLesson;
        $courseData['total_lessons'] = count($lessons);
        $courseData['completed_lessons'] = count(array_filter($lessons, fn($l) => !empty($l['is_completed'])));
    }
    
    // Filter courses
    $data['ongoingCourses'] = array_values(array_filter($data['allCourses'], fn($c) => ($c['progress_percentage'] ?? 0) < 100));
    $data['completedCourses'] = array_values(array_filter($data['allCourses'], fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
    
    // Get recommendations
    $data['recommendations'] = $course->getRecommendedCourses($studentId, 4);
    
} catch (Exception $e) {
    error_log("Error loading courses: " . $e->getMessage());
    $data['stats'] = [
        'total_enrollments' => 0,
        'in_progress' => 0,
        'completed_courses' => 0,
        'total_study_hours' => 0
    ];
}

$jsonData = json_encode($data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - IT HUB Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #ec4899;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1e293b;
            --light: #f8fafc;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        * { font-family: 'Inter', sans-serif; }
        
        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.15);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.primary { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .stat-icon.success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-icon.info { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Course Cards */
        .course-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .course-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
        }
        
        .course-thumbnail {
            position: relative;
            height: 180px;
            overflow: hidden;
        }
        
        .course-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .course-card:hover .course-thumbnail img {
            transform: scale(1.1);
        }
        
        .course-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .course-badge.completed {
            background: var(--gradient-success);
            color: white;
        }
        
        .course-badge.in-progress {
            background: rgba(255,255,255,0.95);
            color: var(--primary);
        }
        
        .course-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .course-category {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .course-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .course-instructor {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .course-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        /* Progress Section */
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
            background: rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .progress-bar {
            background: var(--gradient-primary);
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        .progress-bar.completed {
            background: var(--gradient-success);
        }
        
        /* Buttons */
        .btn-primary-gradient {
            background: var(--gradient-primary);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }
        
        /* Search & Filter */
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 15px;
            border: 2px solid rgba(0,0,0,0.05);
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .filter-select {
            padding: 1rem;
            border-radius: 15px;
            border: 2px solid rgba(0,0,0,0.05);
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        /* Tabs */
        .nav-tabs-custom {
            border: none;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            color: #64748b;
            font-weight: 600;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.5);
        }
        
        .nav-tabs-custom .nav-link:hover {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .nav-tabs-custom .nav-link.active {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stat-value { font-size: 1.5rem; }
            .course-title { font-size: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>IT HUB
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2 fa-lg"></i>
                            <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Student'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1 class="display-6 fw-bold mb-2">My Learning Journey</h1>
                        <p class="text-muted mb-0">Track your progress and continue where you left off</p>
                    </div>
                    <button class="btn btn-primary-gradient mt-2 mt-md-0" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card animate-fade-in" style="animation-delay: 0.1s">
                    <div class="stat-icon primary">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-value" id="statTotal"><?php echo $data['stats']['total_enrollments'] ?? 0; ?></div>
                    <div class="stat-label">Courses Enrolled</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card animate-fade-in" style="animation-delay: 0.2s">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value" id="statCompleted"><?php echo $data['stats']['completed_courses'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card animate-fade-in" style="animation-delay: 0.3s">
                    <div class="stat-icon warning">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-value" id="statActive"><?php echo $data['stats']['in_progress'] ?? 0; ?></div>
                    <div class="stat-label">In Progress</div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card animate-fade-in" style="animation-delay: 0.4s">
                    <div class="stat-icon info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value" id="statHours"><?php echo $data['stats']['total_study_hours'] ?? 0; ?>h</div>
                    <div class="stat-label">Study Time</div>
                </div>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search courses by title, instructor, or category...">
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <select id="sortSelect" class="form-select filter-select">
                    <option value="recent">Recently Accessed</option>
                    <option value="progress">Progress (High to Low)</option>
                    <option value="progress-asc">Progress (Low to High)</option>
                    <option value="title">Title (A-Z)</option>
                    <option value="rating">Highest Rated</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <select id="categorySelect" class="form-select filter-select">
                    <option value="">All Categories</option>
                    <?php
                    $categories = array_unique(array_column($data['allCourses'], 'category_name'));
                    foreach ($categories as $category) {
                        if ($category) {
                            echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs-custom" id="courseTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-courses" type="button">
                    All Courses <span class="badge bg-light text-dark ms-2" id="countAll"><?php echo count($data['allCourses']); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-courses" type="button">
                    In Progress <span class="badge bg-light text-dark ms-2" id="countActive"><?php echo count($data['ongoingCourses']); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-courses" type="button">
                    Completed <span class="badge bg-light text-dark ms-2" id="countCompleted"><?php echo count($data['completedCourses']); ?></span>
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="courseTabsContent">
            <!-- All Courses -->
            <div class="tab-pane fade show active" id="all-courses" role="tabpanel">
                <div class="row" id="allCoursesGrid">
                    <?php if (!empty($data['allCourses'])): ?>
                        <?php foreach ($data['allCourses'] as $index => $course): ?>
                            <div class="col-lg-4 col-md-6 mb-4 course-item animate-fade-in" 
                                 data-title="<?php echo htmlspecialchars(strtolower($course['title'])); ?>"
                                 data-instructor="<?php echo htmlspecialchars(strtolower($course['instructor_name'])); ?>"
                                 data-category="<?php echo htmlspecialchars(strtolower($course['category_name'] ?? '')); ?>"
                                 data-progress="<?php echo $course['progress_percentage']; ?>"
                                 data-rating="<?php echo $course['rating']; ?>"
                                 data-date="<?php echo $course['last_accessed']; ?>"
                                 style="animation-delay: <?php echo $index * 0.05; ?>s">
                                <div class="course-card">
                                    <div class="course-thumbnail">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($course['progress_percentage'] >= 100): ?>
                                            <span class="course-badge completed"><i class="fas fa-check me-1"></i>Completed</span>
                                        <?php else: ?>
                                            <span class="course-badge in-progress"><?php echo round($course['progress_percentage']); ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-content">
                                        <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                        <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <div class="course-instructor">
                                            <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                        </div>
                                        <div class="course-meta">
                                            <span><i class="fas fa-clock me-1"></i><?php echo $course['duration_hours'] ?? 0; ?>h</span>
                                            <span><i class="fas fa-signal me-1"></i><?php echo ucfirst($course['difficulty_level'] ?? 'beginner'); ?></span>
                                            <span><i class="fas fa-star me-1 text-warning"></i><?php echo $course['rating']; ?></span>
                                        </div>
                                        <div class="progress-section">
                                            <div class="progress-label">
                                                <span>Progress</span>
                                                <span><?php echo round($course['progress_percentage']); ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar <?php echo $course['progress_percentage'] >= 100 ? 'completed' : ''; ?>" 
                                                     style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                            </div>
                                            <small class="text-muted mt-1 d-block">
                                                <?php echo $course['completed_lessons']; ?> of <?php echo $course['total_lessons']; ?> lessons completed
                                            </small>
                                        </div>
                                        <div class="mt-3 d-grid gap-2">
                                            <?php if ($course['progress_percentage'] < 100): ?>
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary-gradient" onclick="trackStudyStart(<?php echo $course['id']; ?>)">
                                                    <i class="fas fa-play me-2"></i>Continue Learning
                                                </a>
                                            <?php else: ?>
                                                <?php if ($course['has_certificate']): ?>
                                                    <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">
                                                        <i class="fas fa-download me-2"></i>Download Certificate
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-info-circle me-2"></i>Course Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 empty-state">
                            <i class="fas fa-book-open"></i>
                            <h3>No Courses Yet</h3>
                            <p class="text-muted">Start your learning journey by enrolling in a course</p>
                            <a href="courses.php" class="btn btn-primary-gradient mt-3">
                                <i class="fas fa-compass me-2"></i>Browse Courses
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Courses -->
            <div class="tab-pane fade" id="active-courses" role="tabpanel">
                <div class="row" id="activeCoursesGrid">
                    <?php if (!empty($data['ongoingCourses'])): ?>
                        <?php foreach ($data['ongoingCourses'] as $index => $course): ?>
                            <div class="col-lg-4 col-md-6 mb-4 course-item animate-fade-in"
                                 style="animation-delay: <?php echo $index * 0.05; ?>s">
                                <div class="course-card">
                                    <div class="course-thumbnail">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="course-badge in-progress"><?php echo round($course['progress_percentage']); ?>%</span>
                                    </div>
                                    <div class="course-content">
                                        <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                        <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <div class="course-instructor">
                                            <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                        </div>
                                        <?php if ($course['next_lesson']): ?>
                                            <div class="alert alert-info py-2 mb-3">
                                                <small><i class="fas fa-forward me-1"></i>Next: <?php echo htmlspecialchars($course['next_lesson']['title']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <div class="progress-section">
                                            <div class="progress-label">
                                                <span>Progress</span>
                                                <span><?php echo round($course['progress_percentage']); ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $course['progress_percentage']; ?>%"></div>
                                            </div>
                                        </div>
                                        <div class="mt-3 d-grid">
                                            <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary-gradient" onclick="trackStudyStart(<?php echo $course['id']; ?>)">
                                                <i class="fas fa-play me-2"></i>Continue Learning
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 empty-state">
                            <i class="fas fa-play-circle"></i>
                            <h3>No Active Courses</h3>
                            <p class="text-muted">All your courses are completed! Start a new one.</p>
                            <a href="courses.php" class="btn btn-primary-gradient mt-3">
                                <i class="fas fa-plus me-2"></i>Enroll in New Course
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Courses -->
            <div class="tab-pane fade" id="completed-courses" role="tabpanel">
                <div class="row" id="completedCoursesGrid">
                    <?php if (!empty($data['completedCourses'])): ?>
                        <?php foreach ($data['completedCourses'] as $index => $course): ?>
                            <div class="col-lg-4 col-md-6 mb-4 course-item animate-fade-in"
                                 style="animation-delay: <?php echo $index * 0.05; ?>s">
                                <div class="course-card">
                                    <div class="course-thumbnail">
                                        <?php if ($course['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="filter: grayscale(20%);">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                                <i class="fas fa-image fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="course-badge completed"><i class="fas fa-trophy me-1"></i>Completed</span>
                                    </div>
                                    <div class="course-content">
                                        <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                        <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <div class="course-instructor">
                                            <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?>
                                        </div>
                                        <div class="alert alert-success py-2 mb-3">
                                            <small><i class="fas fa-check-circle me-1"></i>Completed in <?php echo $course['study_hours']; ?> hours</small>
                                        </div>
                                        <div class="progress-section">
                                            <div class="progress-label">
                                                <span>Progress</span>
                                                <span>100%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar completed" style="width: 100%"></div>
                                            </div>
                                        </div>
                                        <div class="mt-3 d-grid gap-2">
                                            <?php if ($course['has_certificate']): ?>
                                                <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">
                                                    <i class="fas fa-download me-2"></i>Certificate
                                                </a>
                                            <?php endif; ?>
                                            <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary">
                                                <i class="fas fa-redo me-2"></i>Review Course
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 empty-state">
                            <i class="fas fa-trophy"></i>
                            <h3>No Completed Courses</h3>
                            <p class="text-muted">Keep learning! Your achievements will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recommendations Section -->
        <?php if (!empty($data['recommendations'])): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="fw-bold mb-4">Recommended for You</h3>
                <div class="row">
                    <?php foreach ($data['recommendations'] as $rec): ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="course-card" style="transform: scale(0.95);">
                                <div class="course-thumbnail" style="height: 140px;">
                                    <?php if ($rec['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars(resolveUploadUrl($rec['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($rec['title']); ?>">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 bg-light">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="course-content" style="padding: 1rem;">
                                    <h6 class="course-title" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rec['title']); ?></h6>
                                    <div class="course-instructor" style="font-size: 0.8rem;">
                                        <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($rec['instructor_name']); ?>
                                    </div>
                                    <a href="course-details.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Store course data
        const coursesData = <?php echo $jsonData; ?>;
        
        // Debounce function
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
        
        // Search functionality
        const debouncedSearch = debounce(function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categorySelect').value.toLowerCase();
            const items = document.querySelectorAll('.course-item');
            
            let visibleCount = 0;
            items.forEach(item => {
                const title = item.dataset.title;
                const instructor = item.dataset.instructor;
                const itemCategory = item.dataset.category;
                
                const matchesSearch = title.includes(searchTerm) || instructor.includes(searchTerm);
                const matchesCategory = !category || itemCategory === category;
                
                if (matchesSearch && matchesCategory) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            updateCounts();
        }, 300);
        
        // Sort functionality
        function sortCourses(sortBy) {
            const container = document.querySelector('.tab-pane.active .row');
            const items = Array.from(container.querySelectorAll('.course-item'));
            
            items.sort((a, b) => {
                switch(sortBy) {
                    case 'progress':
                        return parseFloat(b.dataset.progress) - parseFloat(a.dataset.progress);
                    case 'progress-asc':
                        return parseFloat(a.dataset.progress) - parseFloat(b.dataset.progress);
                    case 'title':
                        return a.dataset.title.localeCompare(b.dataset.title);
                    case 'rating':
                        return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                    case 'recent':
                    default:
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                }
            });
            
            items.forEach(item => container.appendChild(item));
        }
        
        // Update counts
        function updateCounts() {
            const tabs = ['all-courses', 'active-courses', 'completed-courses'];
            const counts = ['countAll', 'countActive', 'countCompleted'];
            
            tabs.forEach((tab, index) => {
                const container = document.getElementById(tab);
                if (container) {
                    const visibleItems = container.querySelectorAll('.course-item:not([style*="display: none"])').length;
                    document.getElementById(counts[index]).textContent = visibleItems;
                }
            });
        }
        
        // Track study start
        function trackStudyStart(courseId) {
            fetch('../api/track_study_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `course_id=${courseId}&action=start`
            });
        }
        
        // Refresh data
        function refreshData() {
            showToast('Refreshing data...', 'info');
            location.reload();
        }
        
        // Show toast
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            container.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }
        
        // Event listeners
        document.getElementById('searchInput').addEventListener('input', debouncedSearch);
        document.getElementById('categorySelect').addEventListener('change', debouncedSearch);
        document.getElementById('sortSelect').addEventListener('change', (e) => sortCourses(e.target.value));
        
        // Tab change - reapply filters
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                debouncedSearch();
            });
        });
        
        // Auto-refresh stats every 2 minutes
        setInterval(() => {
            fetch('../api/get_student_stats.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('statTotal').textContent = data.data.total_enrollments;
                        document.getElementById('statCompleted').textContent = data.data.completed_courses;
                        document.getElementById('statActive').textContent = data.data.in_progress;
                        document.getElementById('statHours').textContent = data.data.total_study_hours + 'h';
                    }
                });
        }, 120000);
    </script>
</body>
</html>
