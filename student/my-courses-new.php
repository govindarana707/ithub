<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';
requireStudent();

$course = new Course();
$studentId = $_SESSION['user_id'];

$data = [
    'stats' => [],
    'allCourses' => [],
    'ongoingCourses' => [],
    'completedCourses' => [],
    'recommendations' => []
];

try {
    $data['stats'] = $course->getEnrollmentStats($studentId);
    $data['allCourses'] = $course->getEnrolledCourses($studentId);
    
    foreach ($data['allCourses'] as &$courseData) {
        $courseData['progress_percentage'] = $course->calculateCourseProgress($studentId, $courseData['id']);
        $courseData['has_certificate'] = $course->hasCertificate($studentId, $courseData['id']);
        $courseData['study_hours'] = $course->getStudyTime($studentId, $courseData['id']) ?: 0;
        $courseData['rating'] = $courseData['rating'] ?? 4.5;
        
        $enrollment = $course->getEnrollment($studentId, $courseData['id']);
        $courseData['last_accessed'] = $enrollment['last_accessed'] ?? $enrollment['enrolled_at'] ?? date('Y-m-d H:i:s');
        
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
    
    $data['ongoingCourses'] = array_values(array_filter($data['allCourses'], fn($c) => ($c['progress_percentage'] ?? 0) < 100));
    $data['completedCourses'] = array_values(array_filter($data['allCourses'], fn($c) => ($c['progress_percentage'] ?? 0) >= 100));
    $data['recommendations'] = $course->getRecommendedCourses($studentId, 4);
    
} catch (Exception $e) {
    error_log("Error loading courses: " . $e->getMessage());
    $data['stats'] = ['total_enrollments' => 0, 'in_progress' => 0, 'completed_courses' => 0, 'total_study_hours' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --gradient-primary: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        body { background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); min-height: 100vh; }
        .stat-card { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 12px 40px rgba(99, 102, 241, 0.15); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .stat-icon.primary { background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .stat-icon.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-icon.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .stat-icon.info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #1e293b; }
        .stat-label { color: #64748b; font-size: 0.875rem; }
        .course-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: all 0.3s ease; height: 100%; }
        .course-card:hover { transform: translateY(-8px); box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15); }
        .course-thumbnail { position: relative; height: 160px; overflow: hidden; }
        .course-thumbnail img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .course-card:hover .course-thumbnail img { transform: scale(1.1); }
        .course-badge { position: absolute; top: 12px; right: 12px; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .course-badge.completed { background: var(--gradient-success); color: white; }
        .course-badge.in-progress { background: white; color: var(--primary); }
        .course-content { padding: 1.5rem; }
        .course-category { display: inline-block; padding: 4px 10px; background: rgba(99, 102, 241, 0.1); color: var(--primary); border-radius: 20px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.75rem; }
        .course-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; }
        .course-instructor { color: #64748b; font-size: 0.875rem; margin-bottom: 1rem; }
        .progress { height: 8px; border-radius: 4px; background: rgba(0,0,0,0.05); }
        .progress-bar { background: var(--gradient-primary); border-radius: 4px; }
        .progress-bar.completed { background: var(--gradient-success); }
        .btn-primary-gradient { background: var(--gradient-primary); border: none; color: white; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; }
        .btn-primary-gradient:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3); color: white; }
        .search-box { position: relative; }
        .search-box input { padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; border: 2px solid rgba(0,0,0,0.05); }
        .search-box i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .nav-tabs-custom { border: none; gap: 0.5rem; margin-bottom: 1.5rem; }
        .nav-tabs-custom .nav-link { border: none; border-radius: 10px; padding: 0.75rem 1.25rem; color: #64748b; font-weight: 600; background: rgba(255,255,255,0.5); }
        .nav-tabs-custom .nav-link.active { background: var(--gradient-primary); color: white; }
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state i { font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem; }
        @media (max-width: 768px) { .stat-value { font-size: 1.5rem; } }
    </style>
</head>
<body>
    <!-- Universal Header -->
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
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item active" href="my-courses.php">My Courses</a></li>
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
            <!-- Universal Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-book-open me-2"></i> My Courses
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
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="fw-bold mb-1">My Learning Journey</h1>
                        <p class="text-muted mb-0">Track your progress and continue where you left off</p>
                    </div>
                    <button class="btn btn-primary-gradient" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Refresh
                    </button>
                </div>

                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon primary"><i class="fas fa-book-open"></i></div>
                            <div class="stat-value" id="statTotal"><?php echo $data['stats']['total_enrollments'] ?? 0; ?></div>
                            <div class="stat-label">Courses Enrolled</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-value" id="statCompleted"><?php echo $data['stats']['completed_courses'] ?? 0; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon warning"><i class="fas fa-play-circle"></i></div>
                            <div class="stat-value" id="statActive"><?php echo $data['stats']['in_progress'] ?? 0; ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon info"><i class="fas fa-clock"></i></div>
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
                            <input type="text" id="searchInput" class="form-control" placeholder="Search courses...">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <select id="sortSelect" class="form-select">
                            <option value="recent">Recently Accessed</option>
                            <option value="progress">Progress (High)</option>
                            <option value="progress-asc">Progress (Low)</option>
                            <option value="title">Title (A-Z)</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <select id="categorySelect" class="form-select">
                            <option value="">All Categories</option>
                            <?php
                            $categories = array_unique(array_column($data['allCourses'], 'category_name'));
                            foreach ($categories as $category) {
                                if ($category) echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs-custom" id="courseTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-courses">
                            All <span class="badge bg-light text-dark ms-1" id="countAll"><?php echo count($data['allCourses']); ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-courses">
                            In Progress <span class="badge bg-light text-dark ms-1" id="countActive"><?php echo count($data['ongoingCourses']); ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-courses">
                            Completed <span class="badge bg-light text-dark ms-1" id="countCompleted"><?php echo count($data['completedCourses']); ?></span>
                        </button>
                    </li>
                </ul>

                <!-- Course Content -->
                <div class="tab-content" id="courseTabsContent">
                    <!-- All Courses -->
                    <div class="tab-pane fade show active" id="all-courses">
                        <div class="row" id="allCoursesGrid">
                            <?php if (!empty($data['allCourses'])): ?>
                                <?php foreach ($data['allCourses'] as $course): ?>
                                    <div class="col-lg-4 col-md-6 mb-4 course-item" 
                                         data-title="<?php echo htmlspecialchars(strtolower($course['title'])); ?>"
                                         data-instructor="<?php echo htmlspecialchars(strtolower($course['instructor_name'])); ?>"
                                         data-category="<?php echo htmlspecialchars(strtolower($course['category_name'] ?? '')); ?>"
                                         data-progress="<?php echo $course['progress_percentage']; ?>"
                                         data-date="<?php echo $course['last_accessed']; ?>">
                                        <div class="course-card">
                                            <div class="course-thumbnail">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light"><i class="fas fa-image fa-3x text-muted"></i></div>
                                                <?php endif; ?>
                                                <?php if ($course['progress_percentage'] >= 100): ?>
                                                    <span class="course-badge completed"><i class="fas fa-check me-1"></i>Done</span>
                                                <?php else: ?>
                                                    <span class="course-badge in-progress"><?php echo round($course['progress_percentage']); ?>%</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="course-content">
                                                <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <div class="course-instructor"><i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($course['instructor_name']); ?></div>
                                                <div class="progress mb-2">
                                                    <div class="progress-bar <?php echo $course['progress_percentage'] >= 100 ? 'completed' : ''; ?>" style="width: <?php echo $course['progress_percentage']; ?>"></div>
                                                </div>
                                                <small class="text-muted d-block mb-3"><?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?> lessons</small>
                                                <div class="d-grid gap-2">
                                                    <?php if ($course['progress_percentage'] < 100): ?>
                                                        <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary-gradient btn-sm" onclick="trackStudyStart(<?php echo $course['id']; ?>)"><i class="fas fa-play me-1"></i>Continue</a>
                                                    <?php elseif ($course['has_certificate']): ?>
                                                        <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-download me-1"></i>Certificate</a>
                                                    <?php endif; ?>
                                                    <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-info-circle me-1"></i>Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 empty-state">
                                    <i class="fas fa-book-open"></i>
                                    <h4>No Courses Yet</h4>
                                    <p class="text-muted">Start learning by enrolling in a course</p>
                                    <a href="courses.php" class="btn btn-primary-gradient">Browse Courses</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Active Courses -->
                    <div class="tab-pane fade" id="active-courses">
                        <div class="row" id="activeCoursesGrid">
                            <?php if (!empty($data['ongoingCourses'])): ?>
                                <?php foreach ($data['ongoingCourses'] as $course): ?>
                                    <div class="col-lg-4 col-md-6 mb-4 course-item">
                                        <div class="course-card">
                                            <div class="course-thumbnail">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light"><i class="fas fa-image fa-3x text-muted"></i></div>
                                                <?php endif; ?>
                                                <span class="course-badge in-progress"><?php echo round($course['progress_percentage']); ?>%</span>
                                            </div>
                                            <div class="course-content">
                                                <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <?php if ($course['next_lesson']): ?>
                                                    <div class="alert alert-info py-2 mb-2"><small><i class="fas fa-forward me-1"></i><?php echo htmlspecialchars($course['next_lesson']['title']); ?></small></div>
                                                <?php endif; ?>
                                                <div class="progress mb-2"><div class="progress-bar" style="width: <?php echo $course['progress_percentage']; ?>"></div></div>
                                                <a href="lesson.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary-gradient w-100 btn-sm" onclick="trackStudyStart(<?php echo $course['id']; ?>)"><i class="fas fa-play me-1"></i>Continue Learning</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 empty-state">
                                    <i class="fas fa-play-circle"></i>
                                    <h4>No Active Courses</h4>
                                    <p class="text-muted">All courses completed! Start a new one.</p>
                                    <a href="courses.php" class="btn btn-primary-gradient">Browse Courses</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Completed Courses -->
                    <div class="tab-pane fade" id="completed-courses">
                        <div class="row" id="completedCoursesGrid">
                            <?php if (!empty($data['completedCourses'])): ?>
                                <?php foreach ($data['completedCourses'] as $course): ?>
                                    <div class="col-lg-4 col-md-6 mb-4 course-item">
                                        <div class="course-card">
                                            <div class="course-thumbnail">
                                                <?php if ($course['thumbnail']): ?>
                                                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="filter: grayscale(20%);">
                                                <?php else: ?>
                                                    <div class="d-flex align-items-center justify-content-center h-100 bg-light"><i class="fas fa-image fa-3x text-muted"></i></div>
                                                <?php endif; ?>
                                                <span class="course-badge completed"><i class="fas fa-trophy me-1"></i>Done</span>
                                            </div>
                                            <div class="course-content">
                                                <span class="course-category"><?php echo htmlspecialchars($course['category_name'] ?? 'General'); ?></span>
                                                <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <div class="alert alert-success py-2 mb-2"><small><i class="fas fa-check-circle me-1"></i>Completed in <?php echo $course['study_hours']; ?>h</small></div>
                                                <div class="progress mb-2"><div class="progress-bar completed" style="width: 100%"></div></div>
                                                <div class="d-grid gap-2">
                                                    <?php if ($course['has_certificate']): ?>
                                                        <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-download me-1"></i>Certificate</a>
                                                    <?php endif; ?>
                                                    <a href="course-details.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-secondary btn-sm">Review</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 empty-state">
                                    <i class="fas fa-trophy"></i>
                                    <h4>No Completed Courses</h4>
                                    <p class="text-muted">Keep learning! Achievements appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <?php if (!empty($data['recommendations'])): ?>
                <div class="mt-5">
                    <h4 class="fw-bold mb-3">Recommended for You</h4>
                    <div class="row">
                        <?php foreach ($data['recommendations'] as $rec): ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="course-card">
                                    <div class="course-thumbnail" style="height: 120px;">
                                        <?php if ($rec['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($rec['thumbnail'])); ?>" alt="<?php echo htmlspecialchars($rec['title']); ?>">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100 bg-light"><i class="fas fa-image fa-2x text-muted"></i></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-content" style="padding: 1rem;">
                                        <h6 class="course-title" style="font-size: 0.95rem;"><?php echo htmlspecialchars($rec['title']); ?></h6>
                                        <a href="course-details.php?id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-primary w-100">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        const filterCourses = debounce(function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categorySelect').value.toLowerCase();
            document.querySelectorAll('.course-item').forEach(item => {
                const title = item.dataset.title;
                const instructor = item.dataset.instructor;
                const itemCategory = item.dataset.category;
                const matchesSearch = title.includes(searchTerm) || instructor.includes(searchTerm);
                const matchesCategory = !category || itemCategory === category;
                item.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
            updateCounts();
        }, 300);
        
        function sortCourses(sortBy) {
            const container = document.querySelector('.tab-pane.active .row');
            const items = Array.from(container.querySelectorAll('.course-item:not([style*="display: none"])'));
            items.sort((a, b) => {
                switch(sortBy) {
                    case 'progress': return parseFloat(b.dataset.progress) - parseFloat(a.dataset.progress);
                    case 'progress-asc': return parseFloat(a.dataset.progress) - parseFloat(b.dataset.progress);
                    case 'title': return a.dataset.title.localeCompare(b.dataset.title);
                    default: return new Date(b.dataset.date) - new Date(a.dataset.date);
                }
            });
            items.forEach(item => container.appendChild(item));
        }
        
        function updateCounts() {
            ['all-courses', 'active-courses', 'completed-courses'].forEach((tab, index) => {
                const container = document.getElementById(tab);
                const count = container ? container.querySelectorAll('.course-item:not([style*="display: none"])').length : 0;
                const badge = document.getElementById(['countAll', 'countActive', 'countCompleted'][index]);
                if (badge) badge.textContent = count;
            });
        }
        
        function trackStudyStart(courseId) {
            fetch('../api/track_study_time.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `course_id=${courseId}&action=start`
            });
        }
        
        function refreshData() { location.reload(); }
        
        document.getElementById('searchInput').addEventListener('input', filterCourses);
        document.getElementById('categorySelect').addEventListener('change', filterCourses);
        document.getElementById('sortSelect').addEventListener('change', (e) => sortCourses(e.target.value));
        document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', filterCourses);
        });
        
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
