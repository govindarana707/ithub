<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';
require_once '../models/Quiz.php';
require_once '../models/Discussion.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Allow students, admins, and instructors (for preview) to access
$role = getUserRole();
if ($role !== 'student' && $role !== 'admin' && $role !== 'instructor') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

// Get course ID from URL
$courseId = $_GET['id'] ?? 0;
if (!$courseId || !is_numeric($courseId)) {
    $_SESSION['error_message'] = 'Invalid course ID.';
    redirect('my-courses.php');
}

$course = new Course();
$quiz = new Quiz();
$discussion = new Discussion();
$userId = $_SESSION['user_id'];

// Get course details
$courseDetails = $course->getCourseById($courseId);
if (!$courseDetails) {
    $_SESSION['error_message'] = 'Course not found.';
    redirect('my-courses.php');
}

// Check if user is enrolled in this course
$enrollment = $course->getEnrollment($userId, $courseId);
$isEnrolled = !empty($enrollment);

// Allow instructors to preview their own courses
$isInstructorPreview = false;
if (!$isEnrolled && getUserRole() === 'instructor') {
    // Check if the instructor owns this course
    $instructorId = $courseDetails['instructor_id'] ?? 0;
    if ((int)$instructorId === (int)$userId) {
        $isInstructorPreview = true;
        $_SESSION['info_message'] = 'Previewing your own course (Instructor Preview Mode)';
    }
}

// If not enrolled and not instructor preview, redirect to course details page
if (!$isEnrolled && !$isInstructorPreview) {
    $_SESSION['error_message'] = 'You must be enrolled in this course to view it.';
    redirect('../course-details.php?id=' . $courseId);
}

require_once '../includes/universal_header.php';

// Get course lessons
$lessons = $course->getCourseLessons($courseId);

// Get course progress from enrollment
$progress = $isInstructorPreview ? 0 : ($enrollment['progress_percentage'] ?? 0);

// Get course quizzes
$quizzes = $quiz->getCourseQuizzes($courseId);

// Get course discussions
$discussions = $discussion->getCourseDiscussions($courseId);

// Get next lesson (first incomplete lesson)
$nextLesson = null;
$database = new Database();
$conn = $database->getConnection();
foreach ($lessons as $lesson) {
    // Check if lesson is completed (this is a simplified check)
    $stmt = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ? AND completed = 1");
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $lesson['id']);
        $stmt->execute();
        $isCompleted = $stmt->get_result()->num_rows > 0;
        if (!$isCompleted) {
            $nextLesson = $lesson;
            break;
        }
    }
}

// Get completed lessons count
$completedLessons = 0;
foreach ($lessons as $lesson) {
    $stmt = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ? AND completed = 1");
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $lesson['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $completedLessons++;
        }
    }
}
$totalLessons = count($lessons);
$progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
?>

<div class="container-fluid py-4">
    <?php if ($isInstructorPreview): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-eye me-2"></i>
        <strong>Instructor Preview Mode:</strong> You are viewing your own course as a student would see it.
        <a href="../instructor/course_builder.php?id=<?php echo $courseId; ?>" class="btn btn-sm btn-warning ms-3">
            <i class="fas fa-edit me-1"></i>Back to Editor
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group">
                <a href="dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="my-courses.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-graduation-cap me-2"></i> My Courses
                </a>
                <a href="quizzes.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-brain me-2"></i> Quizzes
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
        
        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Course Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2><?php echo htmlspecialchars($courseDetails['title']); ?></h2>
                            <p class="text-muted"><?php echo htmlspecialchars($courseDetails['description'] ?? ''); ?></p>
                            <div class="mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($courseDetails['category_name'] ?? 'General'); ?></span>
                                <span class="badge bg-info"><?php echo htmlspecialchars($courseDetails['difficulty_level'] ?? 'Beginner'); ?></span>
                                <span class="badge bg-success"><?php echo htmlspecialchars($courseDetails['duration'] ?? 'Self-paced'); ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-3">
                                <h5>Progress</h5>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercentage; ?>%">
                                        <?php echo $progressPercentage; ?>%
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo $completedLessons; ?> of <?php echo $totalLessons; ?> lessons completed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Content Tabs -->
            <ul class="nav nav-tabs" id="courseTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="lessons-tab" data-bs-toggle="tab" data-bs-target="#lessons" type="button" role="tab">
                        <i class="fas fa-book me-2"></i>Lessons
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes" type="button" role="tab">
                        <i class="fas fa-brain me-2"></i>Quizzes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="discussions-tab" data-bs-toggle="tab" data-bs-target="#discussions" type="button" role="tab">
                        <i class="fas fa-comments me-2"></i>Discussions
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="courseTabContent">
                <!-- Lessons Tab -->
                <div class="tab-pane fade show active" id="lessons" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-book me-2"></i>Course Lessons</h5>
                            <?php if ($nextLesson): ?>
                                <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $nextLesson['id']; ?><?php echo $isInstructorPreview ? '&preview=1' : ''; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-play me-1"></i>Continue Learning
                                </a>
                            <?php elseif ($isInstructorPreview && !empty($lessons)): ?>
                                <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lessons[0]['id']; ?>&preview=1" class="btn btn-primary btn-sm">
                                    <i class="fas fa-play me-1"></i>Preview First Lesson
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($lessons)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <h5>No lessons available yet</h5>
                                    <p class="text-muted">This course doesn't have any lessons at the moment.</p>
                                </div>
                            <?php else: ?>
                                <div class="accordion" id="lessonsAccordion">
                                    <?php foreach ($lessons as $index => $lesson): ?>
                                        <?php 
                                        // Check if lesson is completed
                                        $stmt = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ? AND completed = 1");
                                        $isCompleted = false;
                                        if ($stmt) {
                                            $stmt->bind_param("ii", $userId, $lesson['id']);
                                            $stmt->execute();
                                            $isCompleted = $stmt->get_result()->num_rows > 0;
                                        }
                                        
                                        // Check if lesson is locked (previous lesson not completed)
                                        $isLocked = !$isCompleted && $index > 0;
                                        if ($isLocked && isset($lessons[$index - 1])) {
                                            $prevLessonId = $lessons[$index - 1]['id'];
                                            $stmt = $conn->prepare("SELECT id FROM lesson_progress WHERE student_id = ? AND lesson_id = ? AND completed = 1");
                                            if ($stmt) {
                                                $stmt->bind_param("ii", $userId, $prevLessonId);
                                                $stmt->execute();
                                                $prevCompleted = $stmt->get_result()->num_rows > 0;
                                                $isLocked = !$prevCompleted;
                                            }
                                        }
                                        ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="lesson<?php echo $lesson['id']; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $lesson['id']; ?>" 
                                                        <?php if ($isLocked): ?>disabled<?php endif; ?>>
                                                    <div class="d-flex justify-content-between align-items-center w-100">
                                                        <div>
                                                            <i class="fas fa-<?php echo $isCompleted ? 'check-circle text-success' : ($isLocked ? 'lock text-muted' : 'play-circle text-primary'); ?> me-2"></i>
                                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-<?php echo $isCompleted ? 'success' : ($isLocked ? 'secondary' : 'primary'); ?>">
                                                                <?php echo $isCompleted ? 'Completed' : ($isLocked ? 'Locked' : 'Available'); ?>
                                                            </span>
                                                            <?php if (!empty($lesson['duration_minutes'])): ?>
                                                                <small class="text-muted ms-2"><?php echo htmlspecialchars($lesson['duration_minutes'] . ' mins'); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $lesson['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#lessonsAccordion">
                                                <div class="accordion-body">
                                                    <p class="text-muted"><?php echo htmlspecialchars($lesson['description'] ?? ''); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <?php if ($lesson['video_url']): ?>
                                                                <small class="text-muted"><i class="fas fa-video me-1"></i>Video lesson</small>
                                                            <?php endif; ?>
                                                            <?php if (isset($lesson['lesson_type']) && $lesson['lesson_type'] === 'quiz'): ?>
                                                                <small class="text-muted"><i class="fas fa-brain me-1"></i>Quiz lesson</small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if (!$isLocked): ?>
                                                            <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lesson['id']; ?>" class="btn btn-primary btn-sm">
                                                                <?php echo $isCompleted ? 'Review' : 'Start Lesson'; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quizzes Tab -->
                <div class="tab-pane fade" id="quizzes" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-brain me-2"></i>Course Quizzes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($quizzes)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-brain fa-3x text-muted mb-3"></i>
                                    <h5>No quizzes available yet</h5>
                                    <p class="text-muted">This course doesn't have any quizzes at the moment.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <?php 
                                        $attempt = $quiz->getStudentAttempt($userId, $quiz['id']);
                                        $isPassed = $attempt && $attempt['passed'];
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></p>
                                                    <div class="mb-2">
                                                        <span class="badge bg-info"><?php echo $quiz['questions_count']; ?> questions</span>
                                                        <span class="badge bg-warning"><?php echo $quiz['time_limit']; ?> minutes</span>
                                                        <span class="badge bg-success"><?php echo $quiz['passing_score']; ?>% to pass</span>
                                                    </div>
                                                    <?php if ($attempt): ?>
                                                        <div class="mb-2">
                                                            <small class="text-muted">
                                                                Last attempt: <?php echo date('M j, Y H:i', strtotime($attempt['completed_at'])); ?>
                                                            </small>
                                                            <div>
                                                                <span class="badge bg-<?php echo $isPassed ? 'success' : 'danger'; ?>">
                                                                    <?php echo $isPassed ? 'Passed' : 'Failed'; ?>
                                                                </span>
                                                                <span class="badge bg-info"><?php echo $attempt['percentage']; ?>%</span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex gap-2">
                                                        <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">
                                                            <?php echo $attempt ? 'Retake Quiz' : 'Take Quiz'; ?>
                                                        </a>
                                                        <?php if ($attempt): ?>
                                                            <a href="quiz-result.php?attempt_id=<?php echo $attempt['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                                View Result
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Discussions Tab -->
                <div class="tab-pane fade" id="discussions" role="tabpanel">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-comments me-2"></i>Course Discussions</h5>
                            <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDiscussionModal">
                                <i class="fas fa-plus me-1"></i>New Discussion
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($discussions)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <h5>No discussions yet</h5>
                                    <p class="text-muted">Be the first to start a discussion about this course.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($discussions as $discussion): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6><a href="#" class="text-decoration-none"><?php echo htmlspecialchars($discussion['title']); ?></a></h6>
                                                <p class="text-muted small"><?php echo htmlspecialchars(substr($discussion['content'], 0, 150)) . '...'; ?></p>
                                                <div class="d-flex align-items-center gap-3 small text-muted">
                                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($discussion['author_name']); ?></span>
                                                    <span><i class="fas fa-clock me-1"></i><?php echo date('M j, Y H:i', strtotime($discussion['created_at'])); ?></span>
                                                    <span><i class="fas fa-comment me-1"></i><?php echo $discussion['replies_count']; ?> replies</span>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $discussion['is_pinned'] ? 'warning' : 'secondary'; ?>">
                                                    <?php echo $discussion['is_pinned'] ? 'Pinned' : 'Normal'; ?>
                                                </span>
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
</div>

<!-- New Discussion Modal -->
<div class="modal fade" id="newDiscussionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Discussion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newDiscussionForm">
                    <div class="mb-3">
                        <label for="discussionTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="discussionTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="discussionContent" class="form-label">Content</label>
                        <textarea class="form-control" id="discussionContent" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitDiscussion">Post Discussion</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Handle new discussion submission
    $('#submitDiscussion').click(function() {
        var title = $('#discussionTitle').val();
        var content = $('#discussionContent').val();
        
        if (!title || !content) {
            alert('Please fill in all fields');
            return;
        }
        
        $.ajax({
            url: '../api/create_discussion.php',
            method: 'POST',
            data: {
                course_id: <?php echo $courseId; ?>,
                title: title,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    $('#newDiscussionModal').modal('hide');
                    $('#newDiscussionForm')[0].reset();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
