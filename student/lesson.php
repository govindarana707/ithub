<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/session_helper.php';
require_once '../models/Course.php';
require_once '../models/LessonContent.php';
require_once '../includes/VideoProcessor.php';
require_once '../models/Database.php';

// Initialize session
initializeSession();

// Allow students, admins, and instructors (for preview) to access
$role = getUserRole();
if ($role !== 'student' && $role !== 'admin' && $role !== 'instructor') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

$course = new Course();
$lessonContent = new LessonContent();
$userId = $_SESSION['user_id'];
$courseId = intval($_GET['course_id'] ?? 0);
$isPreview = isset($_GET['preview']);

// Establish database connection
$database = new Database();
$conn = $database->getConnection();

if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course ID';
    redirect('courses.php');
}

// Check if student is enrolled or instructor preview
$enrollment = $course->getEnrollment($userId, $courseId);
$isInstructorPreview = false;

// Allow instructors to preview their own courses
if (!$enrollment && $role === 'instructor') {
    $courseData = $course->getCourseById($courseId);
    if ($courseData && (int)$courseData['instructor_id'] === (int)$userId) {
        $isInstructorPreview = true;
    }
}

if (!$enrollment && !$isInstructorPreview) {
    $_SESSION['error_message'] = 'You must enroll in this course first';
    redirect('courses.php');
}

// Get course details
$courseData = $course->getCourseById($courseId);

// Get lessons for this course
$lessons = $course->getCourseLessons($courseId, $userId);

// Get current lesson
$currentLesson = null;
$currentLessonIndex = 0;
$requestedLessonId = intval($_GET['lesson_id'] ?? 0);

if ($requestedLessonId > 0) {
    foreach ($lessons as $index => $lesson) {
        if ($lesson['id'] == $requestedLessonId) {
            $currentLesson = $lessonContent->getLessonContent($lesson['id'], $userId);
            // Fallback to simple lesson data if getLessonContent returns null
            if (!$currentLesson) {
                $currentLesson = $course->getLessonById($requestedLessonId);
                if ($currentLesson) {
                    $currentLesson['is_completed'] = $lesson['is_completed'] ?? 0;
                    $currentLesson['notes'] = [];
                    $currentLesson['assignments'] = [];
                    $currentLesson['resources'] = [];
                }
            }
            $currentLessonIndex = $index;
            break;
        }
    }
}

// Default to first incomplete if no specific lesson requested
if (!$currentLesson) {
    foreach ($lessons as $index => $lesson) {
        if (!$lesson['is_completed']) {
            $currentLesson = $lessonContent->getLessonContent($lesson['id'], $userId);
            // Fallback to simple lesson data if getLessonContent returns null
            if (!$currentLesson) {
                $currentLesson = $course->getLessonById($lesson['id']);
                if ($currentLesson) {
                    $currentLesson['is_completed'] = $lesson['is_completed'] ?? 0;
                    $currentLesson['notes'] = [];
                    $currentLesson['assignments'] = [];
                    $currentLesson['resources'] = [];
                }
            }
            $currentLessonIndex = $index;
            break;
        }
    }
}

// If all completed, show last
if (!$currentLesson && !empty($lessons)) {
    $lastLesson = end($lessons);
    $currentLesson = $lessonContent->getLessonContent($lastLesson['id'], $userId);
    // Fallback to simple lesson data if getLessonContent returns null
    if (!$currentLesson) {
        $currentLesson = $course->getLessonById($lastLesson['id']);
        if ($currentLesson) {
            $currentLesson['is_completed'] = $lastLesson['is_completed'] ?? 0;
            $currentLesson['notes'] = [];
            $currentLesson['assignments'] = [];
            $currentLesson['resources'] = [];
        }
    }
    $currentLessonIndex = count($lessons) - 1;
}

// Handle completion logic (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_lesson') {
    $lessonId = intval($_POST['lesson_id']);
    
    error_log("Mark lesson complete attempt: student_id=$userId, lesson_id=$lessonId, course_id=$courseId");
    
    if ($lessonId > 0) {
        try {
            // Mark lesson as complete
            $result = $course->markLessonComplete($userId, $lessonId);
            
            if ($result) {
                error_log("Lesson marked as complete successfully");
                
                // Update course progress
                $progressResult = $course->updateCourseProgress($userId, $courseId);
                error_log("Course progress update result: " . ($progressResult ? 'success' : 'failed'));
                
                // Set success message
                $_SESSION['success_message'] = 'Lesson marked as complete successfully!';
                
                // Redirect to next lesson or course completion
                $nextIdx = $currentLessonIndex + 1;
                if (isset($lessons[$nextIdx])) {
                    $nextLessonId = $lessons[$nextIdx]['id'];
                    $redirectUrl = "lesson.php?course_id=$courseId&lesson_id=$nextLessonId" . ($isInstructorPreview ? '&preview=1' : '');
                    error_log("Redirecting to next lesson: $redirectUrl");
                    header("Location: $redirectUrl");
                } else {
                    $redirectUrl = "course-complete.php?course_id=$courseId";
                    error_log("Redirecting to course completion: $redirectUrl");
                    header("Location: $redirectUrl");
                }
                exit;
            } else {
                error_log("Failed to mark lesson as complete");
                $_SESSION['error_message'] = 'Failed to mark lesson as complete. Please try again.';
                
                // If this is an AJAX request, return JSON response
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to mark lesson as complete']);
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log("Exception in lesson completion: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while marking the lesson as complete.';
            
            // If this is an AJAX request, return JSON response
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred while marking the lesson as complete']);
                exit;
            }
        }
    } else {
        error_log("Invalid lesson ID: $lessonId");
        $_SESSION['error_message'] = 'Invalid lesson ID.';
        
        // If this is an AJAX request, return JSON response
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
            exit;
        }
    }
}

// Calculate progress
$completedCount = 0;
foreach ($lessons as $l) {
    if (!empty($l['is_completed'])) $completedCount++;
}
$progressPercent = count($lessons) > 0 ? round(($completedCount / count($lessons)) * 100) : 0;

require_once '../includes/universal_header.php';
?>

<style>
    .video-container {
        position: relative;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }
    .video-container video, .video-container iframe {
        width: 100%;
        height: 450px;
        object-fit: contain;
    }
    .lesson-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        color: white;
    }
    .lesson-nav-btn {
        padding: 12px 24px;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .lesson-nav-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .progress-ring {
        width: 60px;
        height: 60px;
    }
    .progress-ring circle {
        transition: stroke-dashoffset 0.35s;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }
    .sidebar-lesson {
        border-radius: 10px;
        padding: 12px 16px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .sidebar-lesson:hover {
        background: rgba(102, 126, 234, 0.1);
    }
    .sidebar-lesson.active {
        background: rgba(102, 126, 234, 0.2);
        border-left: 3px solid #667eea;
    }
    .sidebar-lesson.completed {
        opacity: 0.7;
    }
    .content-tabs {
        border-bottom: 2px solid #f0f0f0;
    }
    .content-tabs .nav-link {
        border: none;
        color: #666;
        font-weight: 500;
        padding: 12px 20px;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    .content-tabs .nav-link:hover {
        color: #667eea;
        border-color: transparent;
    }
    .content-tabs .nav-link.active {
        color: #667eea;
        border-bottom-color: #667eea;
        background: transparent;
    }
    .completion-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 14px 32px;
        border-radius: 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .completion-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }
    .resource-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.2s;
    }
    .resource-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
    }
</style>
<link rel="stylesheet" href="../assets/css/theme-colors.css">

<?php if ($isInstructorPreview): ?>
<div class="alert alert-warning alert-dismissible fade show mx-3 mt-3" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-eye me-2 fs-4"></i>
        <div>
            <strong>Instructor Preview Mode:</strong> You are viewing your own course as a student would see it.
        </div>
        <a href="../instructor/course_builder.php?id=<?php echo $courseId; ?>" class="btn btn-sm btn-warning ms-3">
            <i class="fas fa-edit me-1"></i>Back to Editor
        </a>
        <a href="view-course.php?id=<?php echo $courseId; ?>" class="btn btn-sm btn-outline-warning ms-2">
            <i class="fas fa-list me-1"></i>Back to Course
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['error_message']); endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
    <div class="d-flex align-items-center">
        <i class="fas fa-check-circle me-2"></i>
        <div><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['success_message']); endif; ?>

<div class="container-fluid py-4">
    <div class="row g-4">
        
        <!-- Main Content -->
        <div class="col-lg-8">
            
            <?php if ($currentLesson): ?>
            
            <!-- Video Player -->
            <div class="video-container mb-4">
                <?php if ($currentLesson['lesson_type'] === 'video'): ?>
                    <?php
                    $videoDisplayed = false;
                    if ($currentLesson['video_source'] === 'upload' && $currentLesson['video_file_path']) {
                        $videoUrl = '../' . $currentLesson['video_file_path'];
                        echo "<video id='lessonVideo' controls class='w-100' controlsList='nodownload' playsinline>
                                <source src='$videoUrl' type='{$currentLesson['video_mime_type']}'>
                              </video>";
                        $videoDisplayed = true;
                    } elseif ($currentLesson['video_source'] === 'external_url' && $currentLesson['video_url']) {
                        $videoUrl = $currentLesson['video_url'];
                        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $videoUrl, $matches);
                            $videoId = $matches[1] ?? '';
                            echo "<div class='ratio ratio-16x9'><iframe src='https://www.youtube.com/embed/$videoId?rel=0&modestbranding=1' allowfullscreen></iframe></div>";
                            $videoDisplayed = true;
                        } elseif (strpos($videoUrl, 'drive.google.com') !== false) {
                            preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $videoUrl, $matches);
                            $videoId = $matches[1] ?? '';
                            if ($videoId) {
                                echo "<div class='ratio ratio-16x9'><iframe src='https://drive.google.com/file/d/$videoId/preview' allowfullscreen></iframe></div>";
                                $videoDisplayed = true;
                            }
                        } else {
                            echo "<video id='lessonVideo' controls class='w-100' controlsList='nodownload' playsinline>
                                    <source src='$videoUrl'>
                                  </video>";
                            $videoDisplayed = true;
                        }
                    } elseif (($currentLesson['video_source'] === 'none' || empty($currentLesson['video_source'])) && !empty($currentLesson['video_url'])) {
                        $videoUrl = $currentLesson['video_url'];
                        if (strpos($videoUrl, 'http') !== 0 && strpos($videoUrl, '../') !== 0 && strpos($videoUrl, '/') !== 0) {
                            $videoUrl = '../' . $videoUrl;
                        }
                        echo "<video id='lessonVideo' controls class='w-100' controlsList='nodownload' playsinline>
                                <source src='$videoUrl'>
                              </video>";
                        $videoDisplayed = true;
                    }

                    if (!$videoDisplayed):
                    ?>
                        <div class="d-flex justify-content-center align-items-center" style="height: 450px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
                            <div class="text-center text-white">
                                <i class="fas fa-film fa-5x mb-4 opacity-50"></i>
                                <h4>Video content unavailable</h4>
                                <p class="opacity-75">The video for this lesson is not yet available.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="d-flex justify-content-center align-items-center" style="height: 300px; background: linear-gradient(135deg, #f5f7fa 0%, #e4e8eb 100%);">
                        <div class="text-center">
                            <i class="fas fa-file-alt fa-5x mb-4 text-muted"></i>
                            <h4 class="text-muted">Reading Material</h4>
                            <p class="text-muted">Content is available in the tabs below</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lesson Info Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h2 class="mb-2 fw-bold text-dark"><?php echo htmlspecialchars($currentLesson['title']); ?></h2>
                    <div class="d-flex gap-3 text-muted">
                        <?php if (!empty($currentLesson['duration'])): ?>
                            <span><i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($currentLesson['duration']); ?></span>
                        <?php elseif (!empty($currentLesson['duration_minutes'])): ?>
                            <span><i class="fas fa-clock me-1"></i><?php echo $currentLesson['duration_minutes']; ?> min</span>
                        <?php endif; ?>
                        <span><i class="fas fa-signal me-1"></i>Lesson <?php echo $currentLessonIndex + 1; ?> of <?php echo count($lessons); ?></span>
                        <?php if ($currentLesson['is_completed']): ?>
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Completed</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="d-flex gap-2">
                    <?php if ($currentLessonIndex > 0): ?>
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lessons[$currentLessonIndex - 1]['id']; ?><?php echo $isInstructorPreview ? '&preview=1' : ''; ?>" 
                           class="btn btn-light lesson-nav-btn">
                            <i class="fas fa-arrow-left me-2"></i>Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($currentLessonIndex < count($lessons) - 1): ?>
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lessons[$currentLessonIndex + 1]['id']; ?><?php echo $isInstructorPreview ? '&preview=1' : ''; ?>" 
                           class="btn btn-primary lesson-nav-btn">
                            Next<i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completion Button -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?php echo $currentLesson['is_completed'] ? 'Lesson Completed!' : 'Mark as Complete'; ?></h5>
                            <small class="text-muted">
                                <?php if ($currentLesson['is_completed']): ?>
                                    Great job! You've completed this lesson.
                                <?php else: ?>
                                    Click to mark this lesson as completed and continue to the next one.
                                <?php endif; ?>
                            </small>
                        </div>
                        <form method="POST" id="completionForm">
                            <input type="hidden" name="action" value="complete_lesson">
                            <input type="hidden" name="lesson_id" value="<?php echo $currentLesson['id']; ?>">
                            <?php if (!$currentLesson['is_completed']): ?>
                                <button type="submit" class="btn completion-btn text-white" id="completeBtn">
                                    <i class="fas fa-check-circle me-2"></i>Mark Complete
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-success" disabled>
                                    <i class="fas fa-check-double me-2"></i>Completed
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Course Completion Certificate Section -->
            <?php if ($progressPercent == 100): ?>
            <div class="card border-0 shadow-sm mb-4 bg-success bg-opacity-10">
                <div class="card-body">
                    <div class="text-center">
                        <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                        <h4 class="text-success mb-3">🎉 Congratulations! Course Completed!</h4>
                        <p class="text-muted mb-4">You have successfully completed all lessons in this course. You can still review any lesson below and access your certificate.</p>
                        
                        <!-- Check if certificate already exists -->
                        <?php
                        try {
                            // Create a new database connection for certificate check
                            $certDatabase = new Database();
                            $certConn = $certDatabase->getConnection();
                            
                            if ($certConn) {
                                $stmt = $certConn->prepare("SELECT COUNT(*) as count FROM certificates WHERE student_id = ? AND course_id = ? AND status = 'issued'");
                                if ($stmt) {
                                    $stmt->bind_param("ii", $userId, $courseId);
                                    $stmt->execute();
                                    $certCount = $stmt->get_result()->fetch_assoc()['count'];
                                    $stmt->close();
                                } else {
                                    $certCount = 0;
                                }
                                $certConn->close();
                            } else {
                                $certCount = 0;
                            }
                        } catch (Exception $e) {
                            $certCount = 0;
                            error_log('Certificate check error: ' . $e->getMessage());
                        }
                        ?>
                        
                        <?php if ($certCount > 0): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-certificate me-2"></i>
                                <strong>Certificate Available!</strong><br>
                                <small class="text-muted">Your certificate has been generated. You can view and download it from the Certificates page.</small>
                            </div>
                            <a href="certificates.php" class="btn btn-success btn-lg me-2">
                                <i class="fas fa-certificate me-2"></i>View My Certificate
                            </a>
                            <button type="button" class="btn btn-primary btn-lg" onclick="showLessonSelector()">
                                <i class="fas fa-list me-2"></i>Review Lessons
                            </button>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Generate Your Certificate</strong><br>
                                <small class="text-muted">Click the button below to generate your completion certificate.</small>
                            </div>
                            <button type="button" class="btn btn-success btn-lg me-2" onclick="generateCertificate()">
                                <i class="fas fa-magic me-2"></i>Generate Certificate
                            </button>
                            <button type="button" class="btn btn-primary btn-lg" onclick="showLessonSelector()">
                                <i class="fas fa-list me-2"></i>Review Lessons
                            </button>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>All course content remains accessible below.</strong> You can review any lesson, access resources, and download materials anytime.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content Tabs -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <ul class="nav content-tabs" id="lessonTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" role="tab">
                                    <i class="fas fa-align-left me-2"></i>Content
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes" role="tab">
                                    <i class="fas fa-pen me-2"></i>My Notes
                                </button>
                            </li>
                            <?php if (!empty($currentLesson['resources'])): ?>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#resources" role="tab">
                                    <i class="fas fa-download me-2"></i>Resources
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if (!empty($currentLesson['assignments'])): ?>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#assignments" role="tab">
                                    <i class="fas fa-tasks me-2"></i>Assignments
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <?php if ($progressPercent == 100): ?>
                            <small class="text-success"><i class="fas fa-info-circle me-1"></i>All content available for review</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Content Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <?php if (!empty($currentLesson['content'])): ?>
                                <div class="lesson-content">
                                    <?php echo nl2br(htmlspecialchars($currentLesson['content'])); ?>
                                </div>
                            <?php elseif (!empty($currentLesson['description'])): ?>
                                <div class="lesson-content">
                                    <?php echo nl2br(htmlspecialchars($currentLesson['description'])); ?>
                                </div>
                            <?php elseif (!empty($currentLesson['notes'])): ?>
                                <?php foreach ($currentLesson['notes'] as $note): ?>
                                    <div class="mb-4">
                                        <h5><?php echo htmlspecialchars($note['title'] ?? 'Lesson Notes'); ?></h5>
                                        <div class="lesson-content">
                                            <?php echo nl2br(htmlspecialchars($note['content'] ?? '')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                                    <p class="text-muted">No content available for this lesson.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Notes Tab -->
                        <div class="tab-pane fade" id="notes" role="tabpanel">
                            <h5 class="mb-3">Your Notes</h5>
                            <textarea id="studentNotesContent" class="form-control" rows="8" 
                                placeholder="Take notes while you learn. Your notes are saved automatically."
                                <?php echo $isInstructorPreview ? 'readonly' : ''; ?>
                            ><?php echo htmlspecialchars($currentLesson['student_notes']['content'] ?? ''); ?></textarea>
                            <small class="text-muted mt-2 d-block">Notes are saved automatically as you type.</small>
                        </div>
                        
                        <!-- Resources Tab -->
                        <?php if (!empty($currentLesson['resources'])): ?>
                        <div class="tab-pane fade" id="resources" role="tabpanel">
                            <h5 class="mb-3">Downloadable Resources</h5>
                            <div class="row g-3">
                                <?php foreach ($currentLesson['resources'] as $resource): ?>
                                    <div class="col-md-6">
                                        <div class="resource-card h-100">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php
                                                    $icon = 'fa-file';
                                                    $color = 'secondary';
                                                    if (strpos($resource['resource_type'] ?? '', 'pdf') !== false) {
                                                        $icon = 'fa-file-pdf';
                                                        $color = 'danger';
                                                    } elseif (strpos($resource['resource_type'] ?? '', 'video') !== false) {
                                                        $icon = 'fa-video';
                                                        $color = 'primary';
                                                    } elseif (strpos($resource['resource_type'] ?? '', 'link') !== false) {
                                                        $icon = 'fa-link';
                                                        $color = 'info';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?> fa-2x text-<?php echo $color; ?>"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($resource['title'] ?? 'Resource'); ?></h6>
                                                    <?php if (!empty($resource['description'])): ?>
                                                        <small class="text-muted"><?php echo htmlspecialchars($resource['description']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($resource['file_path']) || !empty($resource['external_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($resource['file_path'] ?? $resource['external_url']); ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Assignments Tab -->
                        <?php if (!empty($currentLesson['assignments'])): ?>
                        <div class="tab-pane fade" id="assignments" role="tabpanel">
                            <h5 class="mb-3">Assignments</h5>
                            <?php foreach ($currentLesson['assignments'] as $assignment): ?>
                                <div class="card mb-3 border">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                        <?php if (!empty($assignment['description'])): ?>
                                            <p class="card-text text-muted"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($assignment['due_date'])): ?>
                                            <div class="mb-2">
                                                <i class="fas fa-calendar me-1"></i>
                                                Due: <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($assignment['max_points'])): ?>
                                            <div class="mb-2">
                                                <i class="fas fa-star me-1"></i>
                                                Points: <?php echo $assignment['max_points']; ?>
                                            </div>
                                        <?php endif; ?>
                                        <button class="btn btn-primary mt-2">
                                            <i class="fas fa-upload me-2"></i>Submit Assignment
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <!-- No Lesson Found -->
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-graduation-cap fa-5x text-muted mb-4"></i>
                    <h3>Course Completed!</h3>
                    <p class="text-muted">Congratulations! You've completed all lessons in this course.</p>
                    <a href="view-course.php?id=<?php echo $courseId; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-certificate me-2"></i>View Certificate
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Course Progress Card -->
            <div class="card border-0 shadow-sm mb-4 lesson-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Your Progress</h5>
                        <span class="badge bg-light text-dark"><?php echo $progressPercent; ?>%</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $progressPercent; ?>%"></div>
                    </div>
                    <p class="mb-0 small opacity-75">
                        <?php echo $completedCount; ?> of <?php echo count($lessons); ?> lessons completed
                    </p>
                </div>
            </div>
            
            <!-- Course Content -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Course Content</h5>
                        <?php if ($progressPercent == 100): ?>
                            <small class="text-success"><i class="fas fa-check-circle me-1"></i>Course Completed - All Lessons Accessible</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($lessons as $index => $lesson): ?>
                        <?php
                        $isActive = ($currentLesson && $lesson['id'] == $currentLesson['id']);
                        $isCompleted = !empty($lesson['is_completed']);
                        ?>
                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lesson['id']; ?><?php echo $isInstructorPreview ? '&preview=1' : ''; ?>" 
                           class="sidebar-lesson d-block text-decoration-none <?php echo $isActive ? 'active' : ''; ?> <?php echo $isCompleted ? 'completed' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($isCompleted): ?>
                                        <i class="fas fa-check-circle text-success fa-lg"></i>
                                    <?php elseif ($isActive): ?>
                                        <i class="fas fa-play-circle text-primary fa-lg"></i>
                                    <?php else: ?>
                                        <i class="far fa-circle text-muted fa-lg"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="text-muted small">Lesson <?php echo $index + 1; ?></span>
                                            <h6 class="mb-0 <?php echo $isActive ? 'text-primary' : 'text-dark'; ?>">
                                                <?php echo htmlspecialchars($lesson['title']); ?>
                                            </h6>
                                        </div>
                                        <?php if (!empty($lesson['duration_minutes'])): ?>
                                            <small class="text-muted"><?php echo $lesson['duration_minutes']; ?>m</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Course Actions -->
            <div class="mt-4">
                <a href="view-course.php?id=<?php echo $courseId; ?>" class="btn btn-outline-secondary w-100 py-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Course
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle completion form submission
    const completionForm = document.getElementById('completionForm');
    if (completionForm) {
        completionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('completeBtn');
            const originalText = submitBtn.innerHTML;
            const originalDisabled = submitBtn.disabled;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            // Create form data
            const formData = new FormData(completionForm);
            
            // Submit via fetch for better error handling
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                // Check if response is ok (status 200-299)
                if (response.ok) {
                    // Check content type to determine response format
                    const contentType = response.headers.get('content-type');
                    
                    if (contentType && contentType.includes('application/json')) {
                        // Handle JSON response (for errors)
                        return response.json().then(data => {
                            if (data.success) {
                                // Success - reload page to show updated state
                                window.location.reload();
                            } else {
                                throw new Error(data.message || 'Unknown error');
                            }
                        });
                    } else if (response.redirected) {
                        // Handle redirect response
                        window.location.href = response.url;
                        return;
                    } else {
                        // Handle HTML response (success case)
                        return response.text().then(text => {
                            console.log('Response text:', text);
                            // If we get here, it means the form was processed but didn't redirect
                            // Try to reload the page to show updated state
                            window.location.reload();
                        });
                    }
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            })
            .catch(error => {
                console.error('Completion error:', error);
                
                // Show error state
                submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Try Again';
                submitBtn.classList.remove('completion-btn');
                submitBtn.classList.add('btn-warning');
                
                // Reset after 3 seconds
                setTimeout(() => {
                    submitBtn.disabled = originalDisabled;
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('completion-btn');
                }, 3000);
                
                // Show error message
                showAlert('Failed to mark lesson as complete. Please try again.', 'danger');
            });
        });
    }
    
    // Auto-save notes
    const notesArea = document.getElementById('studentNotesContent');
    if (notesArea && !notesArea.readOnly) {
        let saveTimeout;
        notesArea.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Save notes via AJAX
                const formData = new FormData();
                formData.append('lesson_id', '<?php echo $currentLesson['id'] ?? 0; ?>');
                formData.append('content', notesArea.value);
                
                fetch('../api/save_notes.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notesArea.classList.add('border-success');
                        setTimeout(() => notesArea.classList.remove('border-success'), 1000);
                    }
                })
                .catch(err => console.error('Error saving notes:', err));
            }, 1500);
        });
    }
    
    // Video ended - prompt to mark complete
    const video = document.getElementById('lessonVideo');
    if (video) {
        video.addEventListener('ended', function() {
            // Could auto-submit completion form here if desired
            const completeBtn = document.getElementById('completeBtn');
            if (completeBtn && !completeBtn.disabled) {
                completeBtn.classList.add('pulse-animation');
                setTimeout(() => {
                    completeBtn.classList.remove('pulse-animation');
                }, 3000);
            }
        });
    }
    
    // Certificate generation function
    window.generateCertificate = function() {
        const courseId = <?php echo $courseId; ?>;
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generating...';
        
        // Generate certificate via API
        fetch('../api/generate_certificates.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=generate_single&course_id=' + courseId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message with redirect indication
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Generated! Redirecting...';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-success');
                btn.disabled = true;
                
                // Add redirect message below the button
                const redirectMsg = document.createElement('div');
                redirectMsg.className = 'alert alert-success mt-3';
                redirectMsg.innerHTML = '<i class="fas fa-info-circle me-2"></i>Redirecting to your certificate...';
                btn.parentElement.appendChild(redirectMsg);
                
                // Redirect to certificates page after 1.5 seconds to show the generated certificate
                setTimeout(() => {
                    window.location.href = 'certificates.php';
                }, 1500);
            } else {
                // Show error
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Try Again';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-warning');
                
                alert('Error generating certificate: ' + (data.message || 'Unknown error'));
                
                // Reset button after 3 seconds
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-success');
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Try Again';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning');
            
            alert('Error generating certificate. Please try again.');
            
            // Reset button after 3 seconds
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-success');
            }, 3000);
        });
    };
    
    // Show lesson selector for completed courses
    function showLessonSelector() {
        const courseId = <?php echo $courseId; ?>;
        
        // Fetch all lessons for this course
        fetch(`../api/get_course_lessons.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(lessons => {
                if (lessons.success && lessons.data.length > 0) {
                    // Create lesson selector modal
                    const modalHtml = `
                        <div class="modal fade" id="lessonSelectorModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Select Lesson to Review</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="list-group">
                                            ${lessons.data.map((lesson, index) => `
                                                <a href="lesson.php?course_id=${courseId}&lesson_id=${lesson.id}" class="list-group-item list-group-item-action">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1">${lesson.title}</h6>
                                                            <small class="text-muted">Lesson ${index + 1} of ${lessons.data.length}</small>
                                                        </div>
                                                        <div class="text-end">
                                                            ${lesson.is_completed ? 
                                                                '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Completed</span>' : 
                                                                '<span class="badge bg-secondary"><i class="fas fa-play me-1"></i>Not Started</span>'
                                                            }
                                                        </div>
                                                    </div>
                                                </a>
                                            `).join('')}
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add modal to page
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('lessonSelectorModal'));
                    modal.show();
                    
                    // Remove modal from DOM when hidden
                    document.getElementById('lessonSelectorModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    showAlert('No lessons found for this course.', 'warning');
                }
            })
            .catch(error => {
                console.error('Error fetching lessons:', error);
                showAlert('Error loading lessons. Please try again.', 'danger');
            });
    }
    
    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 3000);
    }
});

// Add CSS for pulse animation
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .pulse-animation {
        animation: pulse 1.5s ease-in-out infinite;
        box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7);
    }
`;
document.head.appendChild(style);

</script>

<?php require_once '../includes/footer.php'; ?>
