<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';

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

// Get enrolled courses
$enrolledCourses = $course->getEnrolledCourses($studentId);

// Handle course selection
$selectedCourseId = $_GET['course_id'] ?? null;
$selectedCourse = null;
$lessons = [];

if ($selectedCourseId && isset($enrolledCourses)) {
    foreach ($enrolledCourses as $enrolled) {
        if ($enrolled['id'] == $selectedCourseId) {
            $selectedCourse = $enrolled;
            $lessons = $course->getCourseLessons($selectedCourseId);
            break;
        }
    }
}

$pageTitle = $selectedCourse ? htmlspecialchars($selectedCourse['title']) : 'My Courses';
?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="my-courses.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-graduation-cap me-2"></i> My Courses
                        <span class="badge bg-primary float-end"><?php echo count($enrolledCourses ?? []); ?></span>
                    </a>
                    <a href="quizzes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-brain me-2"></i> Quizzes
                        <span class="badge bg-info float-end">0</span>
                    </a>
                    <a href="quiz-results.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Quiz Results
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
                    <a href="../logout.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>My Courses</h1>
                    <div>
                        <span class="badge bg-success">Student</span>
                    </div>
                </div>

                <?php if (!$selectedCourse): ?>
                    <!-- Course List View -->
                    <div class="dashboard-card">
                        <h3>Enrolled Courses</h3>
                        
                        <?php if (empty($enrolledCourses)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <h5>No courses enrolled yet</h5>
                                <p class="text-muted">Browse our catalog and enroll in courses to start learning!</p>
                                <a href="../courses.php" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Browse Courses
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($enrolledCourses as $course): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 course-card">
                                            <?php if ($course['thumbnail']): ?>
                                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($course['thumbnail'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                                            <?php else: ?>
                                                <div class="card-img-top bg-primary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
                                                    <i class="fas fa-graduation-cap fa-3x"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body d-flex flex-column">
                                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                <p class="card-text"><?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...</p>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($course['instructor_name']); ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar" style="width: <?php echo (int)$course['progress_percentage']; ?>%"></div>
                                                    </div>
                                                    <small class="text-muted">Progress: <?php echo round($course['progress_percentage']); ?>%</small>
                                                </div>
                                                
                                                <div class="mt-auto">
                                                    <a href="my-courses.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-play me-1"></i> Continue Learning
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Course Detail View -->
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3><?php echo htmlspecialchars($selectedCourse['title'] ?? 'Unknown Course'); ?></h3>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($selectedCourse['instructor_name'] ?? 'Unknown Instructor'); ?>
                                </p>
                            </div>
                            <a href="my-courses.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Courses
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Course Content -->
                                <div class="course-content">
                                    <?php if (!empty($lessons)): ?>
                                        <h4>Course Content</h4>
                                        <div class="accordion" id="lessonsAccordion">
                                            <?php foreach ($lessons as $index => $lesson): ?>
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading<?php echo $lesson['id']; ?>">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $lesson['id'] ?? 0; ?>">
                                                            <div class="d-flex align-items-center w-100">
                                                                <div class="me-3">
                                                                    <?php if (($lesson['lesson_type'] ?? 'text') === 'video'): ?>
                                                                        <i class="fas fa-video text-primary"></i>
                                                                    <?php elseif (($lesson['lesson_type'] ?? 'text') === 'text'): ?>
                                                                        <i class="fas fa-file-alt text-info"></i>
                                                                    <?php elseif (($lesson['lesson_type'] ?? 'text') === 'quiz'): ?>
                                                                        <i class="fas fa-question-circle text-warning"></i>
                                                                    <?php else: ?>
                                                                        <i class="fas fa-file text-secondary"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <?php echo htmlspecialchars($lesson['title'] ?? 'Untitled Lesson'); ?>
                                                                    <small class="text-muted d-block">
                                                                        Duration: <?php echo $lesson['duration'] ?? 'N/A'; ?>
                                                                    </small>
                                                                </div>
                                                                <div class="ms-3">
                                                                    <?php if ($lesson['is_completed'] ?? false): ?>
                                                                        <i class="fas fa-check-circle text-success"></i>
                                                                    <?php else: ?>
                                                                        <i class="far fa-circle text-muted"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </h2>
                                                    <div id="collapse<?php echo $lesson['id'] ?? 0; ?>" class="accordion-collapse collapse" data-bs-parent="#lessonsAccordion">
                                                        <div class="accordion-body">
                                                            <?php if (($lesson['lesson_type'] ?? 'text') === 'video' && ($lesson['video_url'] ?? null)): ?>
                                                                <div class="video-container mb-3">
                                                                    <video class="w-100" controls>
                                                                        <source src="<?php echo htmlspecialchars($lesson['video_url'] ?? ''); ?>" type="video/mp4">
                                                                        Your browser does not support the video tag.
                                                                    </video>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($lesson['content'] ?? null): ?>
                                                                <div class="lesson-content">
                                                                    <?php echo nl2br(htmlspecialchars($lesson['content'] ?? '')); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($lesson['file_path'] ?? null): ?>
                                                                <div class="mt-3">
                                                                    <a href="../uploads/<?php echo htmlspecialchars($lesson['file_path'] ?? ''); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                        <i class="fas fa-download me-1"></i> Download Material
                                                                    </a>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="mt-3">
                                                                <button class="btn btn-sm btn-success" onclick="markLessonComplete(<?php echo $lesson['id'] ?? 0; ?>)">>
                                                                    <i class="fas fa-check me-1"></i> Mark as Complete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                            <h5>No lessons available yet</h5>
                                            <p class="text-muted">The instructor hasn't added any content to this course yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Course Info Sidebar -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Course Progress</h5>
                                        <div class="mb-3">
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo (int)($selectedCourse['progress_percentage'] ?? 0); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo round($selectedCourse['progress_percentage'] ?? 0); ?>% Complete</small>
                                        </div>
                                        
                                        <h6 class="card-title mt-4">Course Details</h6>
                                        <ul class="list-unstyled">
                                            <li class="mb-2">
                                                <i class="fas fa-clock me-2 text-muted"></i>
                                                Duration: <?php echo $selectedCourse['duration'] ?? 'N/A'; ?>
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-signal me-2 text-muted"></i>
                                                Level: <?php echo ucfirst($selectedCourse['difficulty'] ?? 'Beginner'); ?>
                                            </li>
                                            <li class="mb-2">
                                                <i class="fas fa-certificate me-2 text-muted"></i>
                                                Certificate: <?php echo $selectedCourse['certificate_available'] ?? true ? 'Available' : 'Not Available'; ?>
                                            </li>
                                        </ul>
                                        
                                        <?php if (($selectedCourse['progress_percentage'] ?? 0) >= 100 && ($selectedCourse['certificate_available'] ?? true)): ?>
                                            <button class="btn btn-success w-100" onclick="generateCertificate(<?php echo $selectedCourse['id'] ?? 0; ?>)">
                                                <i class="fas fa-certificate me-2"></i>Generate Certificate
                                            </button>
                                        <?php endif; ?>
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
    <script src="../assets/js/main.js"></script>
    <script>
        function markLessonComplete(lessonId) {
            $.ajax({
                url: '../api/mark_lesson_complete.php',
                type: 'POST',
                data: {
                    lesson_id: lessonId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
        
        function generateCertificate(courseId) {
            $.ajax({
                url: '../api/generate_certificate.php',
                type: 'POST',
                data: {
                    course_id: courseId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.open(response.certificate_url, '_blank');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
    </script>
