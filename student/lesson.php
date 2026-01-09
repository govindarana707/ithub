<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';
require_once '../models/LessonContent.php';
require_once '../includes/VideoProcessor.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

if (getUserRole() !== 'student' && getUserRole() !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Student privileges required.';
    redirect('../dashboard.php');
}

require_once '../includes/universal_header.php';

$course = new Course();
$lessonContent = new LessonContent();
$userId = $_SESSION['user_id'];
$courseId = intval($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    $_SESSION['error_message'] = 'Invalid course ID';
    redirect('courses.php');
}

// Check if student is enrolled
$enrollment = $course->getEnrollment($userId, $courseId);
if (!$enrollment) {
    $_SESSION['error_message'] = 'You must enroll in this course first';
    redirect('courses.php');
}

// Get course details
$courseData = $course->getCourseById($courseId);
if (!$courseData) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('courses.php');
}

// Get lessons for this course
$lessons = $course->getCourseLessons($courseId, $userId);

// Get current lesson with comprehensive content
$currentLesson = null;
$currentLessonIndex = 0;

foreach ($lessons as $index => $lesson) {
    if (!$lesson['is_completed']) {
        $currentLesson = $lessonContent->getLessonContent($lesson['id'], $userId);
        $currentLessonIndex = $index;
        break;
    }
}

// If all lessons are completed, show last one
if (!$currentLesson && !empty($lessons)) {
    $lastLesson = end($lessons);
    $currentLesson = $lessonContent->getLessonContent($lastLesson['id'], $userId);
    $currentLessonIndex = count($lessons) - 1;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Handle lesson completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_lesson') {
    $lessonId = intval($_POST['lesson_id']);
    
    if ($lessonId > 0) {
        $course->markLessonComplete($userId, $lessonId);
        $course->updateCourseProgress($userId, $courseId);
        
        // Redirect to next lesson or course completion
        $nextLessonIndex = $currentLessonIndex + 1;
        if (isset($lessons[$nextLessonIndex])) {
            header("Location: lesson.php?course_id=$courseId&lesson_id=" . $lessons[$nextLessonIndex]['id']);
        } else {
            // Course completed
            header("Location: course-complete.php?course_id=$courseId");
        }
        exit;
    }
}
?>

    <style>
        .lesson-sidebar {
            max-height: 600px;
            overflow-y: auto;
        }
        .lesson-item {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .lesson-item:hover {
            background-color: #f8f9fa;
        }
        .lesson-item.active {
            background-color: #007bff;
            color: white;
        }
        .lesson-item.completed {
            color: #28a745;
        }
        .lesson-content {
            min-height: 400px;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Course Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="my-courses.php">My Courses</a></li>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($courseData['title']); ?></li>
                                    </ol>
                                </nav>
                                <h2 class="mb-2"><?php echo htmlspecialchars($courseData['title']); ?></h2>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($courseData['instructor_name']); ?>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-clock me-1"></i><?php echo $courseData['duration_hours']; ?> hours
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-signal me-1"></i><?php echo ucfirst($courseData['difficulty_level']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="progress-ring" style="width: 80px; height: 80px;">
                                    <svg width="80" height="80">
                                        <circle cx="40" cy="40" r="30" stroke="#e9ecef" stroke-width="8" fill="none"/>
                                        <circle cx="40" cy="40" r="30" stroke="#007bff" stroke-width="8" fill="none"
                                                stroke-dasharray="<?php echo ($enrollment['progress_percentage'] / 100) * 188; ?> 188"
                                                stroke-linecap="round"/>
                                    </svg>
                                    <div style="margin-top: -55px; font-size: 1rem; font-weight: bold;">
                                        <?php echo round($enrollment['progress_percentage']); ?>%
                                    </div>
                                </div>
                                <small class="text-muted d-block">Course Progress</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Lesson Sidebar -->
            <div class="col-md-3">
                <div class="card lesson-sidebar">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Course Content</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lessons as $index => $lesson): ?>
                            <div class="list-group-item lesson-item <?php echo $lesson['is_completed'] ? 'completed' : ''; ?> <?php echo ($currentLesson && $lesson['id'] == $currentLesson['id']) ? 'active' : ''; ?>"
                                 onclick="loadLesson(<?php echo $lesson['id']; ?>)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-<?php echo $lesson['is_completed'] ? 'check-circle' : ($currentLesson && $lesson['id'] == $currentLesson['id'] ? 'play-circle' : 'circle'); ?> me-2"></i>
                                        <span><?php echo htmlspecialchars($lesson['title']); ?></span>
                                    </div>
                                    <small class="text-muted"><?php echo $lesson['duration_minutes']; ?>m</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Lesson Content -->
            <div class="col-md-9">
                <?php if ($currentLesson): ?>
                    <div class="card lesson-content">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($currentLesson['title']); ?></h5>
                                <span class="badge bg-info"><?php echo ucfirst($currentLesson['lesson_type']); ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($currentLesson['lesson_type'] === 'video' && ($currentLesson['video_source'] !== 'none' || $currentLesson['video_url'])): ?>
                                <div class="video-container mb-4">
                                    <?php
                                    // Handle different video sources
                                    if ($currentLesson['video_source'] === 'upload' && $currentLesson['video_file_path']) {
                                        $videoUrl = '../' . $currentLesson['video_file_path'];
                                        $thumbnail = $currentLesson['video_thumbnail'] ? '../' . $currentLesson['video_thumbnail'] : '';
                                        $downloadable = $currentLesson['is_downloadable'] ? '' : 'controlsList="nodownload"';
                                        
                                        if ($currentLesson['video_processing_status'] === 'processing') {
                                            echo "<div class='alert alert-info text-center'>
                                                <i class='fas fa-spinner fa-spin me-2'></i>Video is being processed. This may take a few minutes.
                                                </div>";
                                        } elseif ($currentLesson['video_processing_status'] === 'failed') {
                                            echo "<div class='alert alert-warning text-center'>
                                                <i class='fas fa-exclamation-triangle me-2'></i>Video processing failed. Please contact the instructor.
                                                </div>";
                                        }
                                        
                                        echo "<video id='lessonVideo' controls preload='metadata' poster='$thumbnail' $downloadable style='width: 100%; height: 400px;' data-lesson-id='{$currentLesson['id']}' data-user-id='{$_SESSION['user_id']}'>
                                            <source src='$videoUrl' type='{$currentLesson['video_mime_type']}'>
                                            Your browser does not support the video tag.
                                        </video>";
                                        
                                        if ($currentLesson['video_duration'] || $currentLesson['video_file_size']) {
                                            echo "<div class='video-info mt-2'>
                                                <small class='text-muted'>";
                                            if ($currentLesson['video_duration']) {
                                                echo "<i class='fas fa-clock me-1'></i>Duration: {$currentLesson['video_duration']} ";
                                            }
                                            if ($currentLesson['video_file_size']) {
                                                echo "<span class='mx-2'>|</span><i class='fas fa-hdd me-1'></i>Size: " . formatBytes($currentLesson['video_file_size']) . " ";
                                            }
                                            echo "<span class='mx-2'>|</span><i class='fas fa-tv me-1'></i>Quality: {$currentLesson['video_quality']}</span>
                                            </small></div>";
                                        }
                                    } elseif ($currentLesson['video_source'] === 'google_drive' && $currentLesson['google_drive_url']) {
                                        $videoProcessor = new VideoProcessor();
                                        $embedUrl = $videoProcessor->getGoogleDriveEmbedUrl($currentLesson['google_drive_url']);
                                        if ($embedUrl) {
                                            echo "<iframe src='$embedUrl' style='width: 100%; height: 400px; border: none;' allowfullscreen data-lesson-id='{$currentLesson['id']}' data-user-id='{$_SESSION['user_id']}'></iframe>
                                            <div class='video-info mt-2'>
                                                <small class='text-muted'><i class='fab fa-google-drive me-1'></i>Google Drive Video</small>
                                            </div>";
                                        }
                                    } elseif ($currentLesson['video_source'] === 'external_url' && $currentLesson['video_url']) {
                                        $videoUrl = $currentLesson['video_url'];
                                        
                                        // Check if it's a YouTube video
                                        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $videoUrl, $matches);
                                            $videoId = $matches[1] ?? '';
                                            $embedUrl = "https://www.youtube.com/embed/$videoId";
                                            echo "<iframe src='$embedUrl' style='width: 100%; height: 400px; border: none;' allowfullscreen data-lesson-id='{$currentLesson['id']}' data-user-id='{$_SESSION['user_id']}'></iframe>";
                                        } elseif (strpos($videoUrl, 'vimeo.com') !== false) {
                                            preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                                            $videoId = $matches[1] ?? '';
                                            $embedUrl = "https://player.vimeo.com/video/$videoId";
                                            echo "<iframe src='$embedUrl' style='width: 100%; height: 400px; border: none;' allowfullscreen data-lesson-id='{$currentLesson['id']}' data-user-id='{$_SESSION['user_id']}'></iframe>";
                                        } else {
                                            // Direct video link
                                            echo "<video id='lessonVideo' controls preload='metadata' style='width: 100%; height: 400px;' data-lesson-id='{$currentLesson['id']}' data-user-id='{$_SESSION['user_id']}'>
                                                <source src='$videoUrl'>
                                                Your browser does not support the video tag.
                                            </video>";
                                        }
                                    } else {
                                        // Fallback to original video_url for backward compatibility
                                        $videoUrl = $currentLesson['video_url'];
                                        if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $videoUrl, $matches);
                                            $videoId = $matches[1] ?? '';
                                            echo "<iframe src='https://www.youtube.com/embed/$videoId' style='width: 100%; height: 400px; border: none;' allowfullscreen></iframe>";
                                        } elseif (strpos($videoUrl, 'vimeo.com') !== false) {
                                            preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                                            $videoId = $matches[1] ?? '';
                                            echo "<iframe src='https://player.vimeo.com/video/$videoId' style='width: 100%; height: 400px; border: none;' allowfullscreen></iframe>";
                                        } else {
                                            echo "<video controls style='width: 100%; height: 400px;'><source src='$videoUrl'>Your browser does not support video tag.</video>";
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($currentLesson['content']): ?>
                                <div class="lesson-text mb-4">
                                    <h6><i class="fas fa-book me-2"></i>Lesson Content</h6>
                                    <?php echo nl2br(htmlspecialchars($currentLesson['content'])); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Instructor Notes Section -->
                            <?php if (!empty($currentLesson['notes'])): ?>
                                <div class="lesson-notes mb-4">
                                    <h6><i class="fas fa-sticky-note me-2"></i>Instructor Notes</h6>
                                    <div class="accordion" id="notesAccordion">
                                        <?php foreach ($currentLesson['notes'] as $index => $note): ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="noteHeading<?php echo $index; ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#noteCollapse<?php echo $index; ?>">
                                                        <?php echo htmlspecialchars($note['title']); ?>
                                                    </button>
                                                </h2>
                                                <div id="noteCollapse<?php echo $index; ?>" class="accordion-collapse collapse" data-bs-parent="#notesAccordion">
                                                    <div class="accordion-body">
                                                        <?php echo nl2br(htmlspecialchars($note['content'])); ?>
                                                        <?php if ($note['is_downloadable'] && $note['file_path']): ?>
                                                            <div class="mt-2">
                                                                <a href="<?php echo '../' . $note['file_path']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                                    <i class="fas fa-download me-1"></i>Download Notes
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Assignments Section -->
                            <?php if (!empty($currentLesson['assignments'])): ?>
                                <div class="lesson-assignments mb-4">
                                    <h6><i class="fas fa-tasks me-2"></i>Assignments</h6>
                                    <div class="list-group">
                                        <?php foreach ($currentLesson['assignments'] as $assignment): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                        <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                                        
                                                        <?php if ($assignment['due_date']): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Due: <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                                                <?php if ($assignment['is_overdue']): ?>
                                                                    <span class="badge bg-danger ms-2">Overdue</span>
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($assignment['submission']): ?>
                                                            <div class="mt-2">
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check me-1"></i>Submitted
                                                                </span>
                                                                <?php if ($assignment['submission']['graded_at']): ?>
                                                                    <span class="badge bg-info ms-2">
                                                                        Score: <?php echo $assignment['submission']['percentage_score']; ?>%
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="mt-2">
                                                                <button class="btn btn-sm btn-primary" onclick="openAssignmentModal(<?php echo $assignment['id']; ?>)">
                                                                    <i class="fas fa-upload me-1"></i>Submit Assignment
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ms-3">
                                                        <span class="badge bg-secondary"><?php echo $assignment['max_points']; ?> pts</span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Resources Section -->
                            <?php if (!empty($currentLesson['resources'])): ?>
                                <div class="lesson-resources mb-4">
                                    <h6><i class="fas fa-folder-open me-2"></i>Additional Resources</h6>
                                    <div class="row">
                                        <?php foreach ($currentLesson['resources'] as $resource): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <?php
                                                            $icon = 'fa-file';
                                                            switch($resource['resource_type']) {
                                                                case 'document': $icon = 'fa-file-alt'; break;
                                                                case 'presentation': $icon = 'fa-file-powerpoint'; break;
                                                                case 'video': $icon = 'fa-video'; break;
                                                                case 'audio': $icon = 'fa-headphones'; break;
                                                                case 'link': $icon = 'fa-link'; break;
                                                                case 'image': $icon = 'fa-image'; break;
                                                            }
                                                            ?>
                                                            <i class="fas <?php echo $icon; ?> me-2"></i>
                                                            <?php echo htmlspecialchars($resource['title']); ?>
                                                        </h6>
                                                        <?php if ($resource['description']): ?>
                                                            <p class="card-text text-muted small"><?php echo htmlspecialchars($resource['description']); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($resource['external_url']): ?>
                                                            <a href="<?php echo htmlspecialchars($resource['external_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-external-link-alt me-1"></i>Open Resource
                                                            </a>
                                                        <?php elseif ($resource['file_path'] && $resource['is_downloadable']): ?>
                                                            <a href="<?php echo '../' . $resource['file_path']; ?>" class="btn btn-sm btn-outline-primary" download>
                                                                <i class="fas fa-download me-1"></i>Download
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Student Notes Section -->
                            <div class="student-notes mb-4">
                                <h6><i class="fas fa-pen me-2"></i>Your Notes</h6>
                                <div id="studentNotesEditor">
                                    <textarea class="form-control" id="studentNotesContent" rows="4" placeholder="Take your personal notes here..."><?php echo htmlspecialchars($currentLesson['student_notes']['content'] ?? ''); ?></textarea>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-success" onclick="saveStudentNotes()">
                                            <i class="fas fa-save me-1"></i>Save Notes
                                        </button>
                                        <span id="notesSaveStatus" class="ms-2"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Lesson Materials (if any) -->
                            <?php
                            $conn = connectDB();
                            $stmt = $conn->prepare("SELECT * FROM lesson_materials WHERE lesson_id = ?");
                            $stmt->bind_param("i", $currentLesson['id']);
                            $stmt->execute();
                            $materials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            if (!empty($materials)):
                            ?>
                                <div class="mt-4">
                                    <h6><i class="fas fa-download me-2"></i>Lesson Materials</h6>
                                    <div class="list-group">
                                        <?php foreach ($materials as $material): ?>
                                            <a href="<?php echo resolveUploadUrl($material['file_path']); ?>" class="list-group-item list-group-item-action" target="_blank">
                                                <i class="fas fa-file-<?php echo $material['file_type']; ?> me-2"></i>
                                                <?php echo htmlspecialchars($material['material_name']); ?>
                                                <small class="text-muted float-end"><?php echo round($material['file_size'] / 1024, 2); ?> KB</small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php $conn->close(); ?>
                        </div>
                        
                        <div class="card-footer">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php if ($currentLessonIndex > 0): ?>
                                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lessons[$currentLessonIndex - 1]['id']; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>Previous Lesson
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <?php if (!$currentLesson['is_completed']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="complete_lesson">
                                            <input type="hidden" name="lesson_id" value="<?php echo $currentLesson['id']; ?>">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-check me-1"></i>Mark as Complete
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Completed
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($currentLessonIndex < count($lessons) - 1): ?>
                                        <a href="lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lessons[$currentLessonIndex + 1]['id']; ?>" class="btn btn-primary ms-2">
                                            Next Lesson<i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                            <h4>Congratulations! 🎉</h4>
                            <p class="text-muted">You have completed all lessons in this course.</p>
                            <a href="my-courses.php" class="btn btn-primary">Back to My Courses</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadLesson(lessonId) {
            window.location.href = `lesson.php?course_id=<?php echo $courseId; ?>&lesson_id=${lessonId}`;
        }
        
        // Video analytics tracking
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('lessonVideo');
            if (video) {
                let lessonId = video.dataset.lessonId;
                let userId = video.dataset.userId;
                let watchStartTime = Date.now();
                let lastReportedTime = 0;
                
                // Track video progress
                video.addEventListener('timeupdate', function() {
                    const currentTime = Math.floor(video.currentTime);
                    const duration = Math.floor(video.duration);
                    const percentage = duration > 0 ? (currentTime / duration) * 100 : 0;
                    
                    // Report progress every 10 seconds or at 25%, 50%, 75%, 90%
                    if (currentTime - lastReportedTime >= 10 || 
                        Math.floor(percentage) === 25 || Math.floor(percentage) === 50 || 
                        Math.floor(percentage) === 75 || Math.floor(percentage) === 90) {
                        
                        reportVideoProgress(lessonId, userId, currentTime, duration, percentage);
                        lastReportedTime = currentTime;
                    }
                });
                
                // Track when video ends
                video.addEventListener('ended', function() {
                    reportVideoProgress(lessonId, userId, Math.floor(video.duration), Math.floor(video.duration), 100);
                });
            }
        });
        
        function reportVideoProgress(lessonId, userId, watchTime, duration, percentage) {
            fetch('../api/video_analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_progress',
                    lesson_id: lessonId,
                    user_id: userId,
                    watch_time_seconds: watchTime,
                    total_video_duration: duration,
                    completion_percentage: percentage,
                    last_watched_position: watchTime
                })
            }).catch(error => console.log('Analytics error:', error));
        }
        
        // Save student notes
        function saveStudentNotes() {
            const content = document.getElementById('studentNotesContent').value;
            const statusSpan = document.getElementById('notesSaveStatus');
            
            statusSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('../api/lesson_content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_student_notes',
                    lesson_id: <?php echo $currentLesson['id']; ?>,
                    title: 'Personal Notes',
                    content: content
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusSpan.innerHTML = '<i class="fas fa-check text-success"></i> Saved';
                    setTimeout(() => {
                        statusSpan.innerHTML = '';
                    }, 3000);
                } else {
                    statusSpan.innerHTML = '<i class="fas fa-times text-danger"></i> Error';
                }
            })
            .catch(error => {
                console.error('Error saving notes:', error);
                statusSpan.innerHTML = '<i class="fas fa-times text-danger"></i> Error';
            });
        }
        
        // Open assignment submission modal
        function openAssignmentModal(assignmentId) {
            // Create modal if it doesn't exist
            if (!document.getElementById('assignmentModal')) {
                const modalHtml = `
                    <div class="modal fade" id="assignmentModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Submit Assignment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="assignmentForm">
                                        <input type="hidden" id="assignmentId">
                                        <div class="mb-3">
                                            <label class="form-label">Submission Type</label>
                                            <select class="form-select" id="submissionType" onchange="toggleSubmissionType()">
                                                <option value="file_upload">Upload File</option>
                                                <option value="text_submission">Text Submission</option>
                                            </select>
                                        </div>
                                        <div id="fileUploadSection" class="mb-3">
                                            <label class="form-label">Upload File</label>
                                            <input type="file" class="form-control" id="submissionFile" accept=".pdf,.doc,.docx,.txt,.zip,.jpg,.png,.gif">
                                            <div class="form-text">Max file size: 10MB</div>
                                        </div>
                                        <div id="textSubmissionSection" class="mb-3" style="display: none;">
                                            <label class="form-label">Text Submission</label>
                                            <textarea class="form-control" id="textContent" rows="6"></textarea>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="submitAssignment()">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
            }
            
            // Set assignment ID and show modal
            document.getElementById('assignmentId').value = assignmentId;
            const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
            modal.show();
        }
        
        function toggleSubmissionType() {
            const type = document.getElementById('submissionType').value;
            const fileSection = document.getElementById('fileUploadSection');
            const textSection = document.getElementById('textSubmissionSection');
            
            if (type === 'file_upload') {
                fileSection.style.display = 'block';
                textSection.style.display = 'none';
            } else {
                fileSection.style.display = 'none';
                textSection.style.display = 'block';
            }
        }
        
        function submitAssignment() {
            const assignmentId = document.getElementById('assignmentId').value;
            const submissionType = document.getElementById('submissionType').value;
            const formData = new FormData();
            
            formData.append('action', 'submit_assignment');
            formData.append('assignment_id', assignmentId);
            formData.append('lesson_id', <?php echo $currentLesson['id']; ?>);
            formData.append('submission_type', submissionType);
            
            if (submissionType === 'file_upload') {
                const fileInput = document.getElementById('submissionFile');
                if (fileInput.files.length === 0) {
                    alert('Please select a file to upload');
                    return;
                }
                formData.append('submission_file', fileInput.files[0]);
            } else {
                const textContent = document.getElementById('textContent').value;
                if (!textContent.trim()) {
                    alert('Please enter your text submission');
                    return;
                }
                formData.append('text_content', textContent);
            }
            
            fetch('../api/lesson_content.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to submit assignment');
                }
            })
            .catch(error => {
                console.error('Error submitting assignment:', error);
                alert('Failed to submit assignment');
            });
        }
        
        // Auto-save notes every 30 seconds
        setInterval(() => {
            const content = document.getElementById('studentNotesContent').value;
            if (content.trim()) {
                saveStudentNotes();
            }
        }, 30000);
        
        // Auto-save progress (every 30 seconds)
        setInterval(() => {
            console.log('Auto-saving progress...');
        }, 30000);
    </script>
