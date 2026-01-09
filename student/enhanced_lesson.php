<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';
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
$videoProcessor = new VideoProcessor();
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

// Get enhanced lessons for this course
$lessons = getEnhancedCourseLessons($courseId, $userId);

// Get current lesson (first incomplete or first lesson)
$currentLesson = null;
$currentLessonIndex = 0;

foreach ($lessons as $index => $lesson) {
    if (!$lesson['is_completed']) {
        $currentLesson = $lesson;
        $currentLessonIndex = $index;
        break;
    }
}

// If all lessons are completed, show last one
if (!$currentLesson && !empty($lessons)) {
    $currentLesson = end($lessons);
    $currentLessonIndex = count($lessons) - 1;
}

// Handle lesson completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_lesson') {
    $lessonId = intval($_POST['lesson_id']);
    
    if ($lessonId > 0) {
        $course->markLessonComplete($userId, $lessonId);
        $course->updateCourseProgress($userId, $courseId);
        
        // Record video analytics if applicable
        recordVideoAnalytics($userId, $lessonId);
        
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

/**
 * Get enhanced lessons with video information
 */
function getEnhancedCourseLessons($courseId, $userId) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("
        SELECT l.*, 
               CASE WHEN cl.student_id IS NOT NULL THEN 1 ELSE 0 END as is_completed
        FROM lessons l
        LEFT JOIN completed_lessons cl ON l.id = cl.lesson_id AND cl.student_id = ?
        WHERE l.course_id = ? 
        ORDER BY l.lesson_order ASC, l.id ASC
    ");
    $stmt->bind_param('ii', $userId, $courseId);
    $stmt->execute();
    $lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $lessons;
}

/**
 * Record video analytics
 */
function recordVideoAnalytics($userId, $lessonId) {
    $conn = connectDB();
    
    // Check if analytics record exists
    $stmt = $conn->prepare("SELECT id FROM video_analytics WHERE lesson_id = ? AND student_id = ?");
    $stmt->bind_param('ii', $lessonId, $userId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE video_analytics SET completed_watching = 1, completion_percentage = 100.00, last_watched_at = NOW() WHERE lesson_id = ? AND student_id = ?");
        $stmt->bind_param('ii', $lessonId, $userId);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO video_analytics (lesson_id, student_id, completed_watching, completion_percentage, first_watched_at) VALUES (?, ?, 1, 100.00, NOW())");
        $stmt->bind_param('ii', $lessonId, $userId);
    }
    $stmt->execute();
}

/**
 * Get video embed HTML
 */
function getVideoEmbedHtml($lesson) {
    $html = '';
    
    switch ($lesson['video_source']) {
        case 'upload':
            if ($lesson['video_file_path']) {
                $videoUrl = '../' . $lesson['video_file_path'];
                $thumbnail = $lesson['video_thumbnail'] ? '../' . $lesson['video_thumbnail'] : '';
                $downloadable = $lesson['is_downloadable'] ? 'controlsList="nodownload"' : '';
                
                $html = "
                    <div class='video-container mb-4'>
                        <video 
                            id='lessonVideo' 
                            controls 
                            preload='metadata' 
                            poster='$thumbnail'
                            $downloadable
                            style='width: 100%; height: 400px; max-height: 400px;'
                            data-lesson-id='{$lesson['id']}'
                            data-user-id='{$_SESSION['user_id']}'>
                            <source src='$videoUrl' type='{$lesson['video_mime_type']}'>
                            Your browser does not support the video tag.
                        </video>
                        <div class='video-info mt-2'>
                            <small class='text-muted'>
                                <i class='fas fa-clock me-1'></i>Duration: {$lesson['video_duration']}
                                <span class='mx-2'>|</span>
                                <i class='fas fa-hdd me-1'></i>Size: " . formatBytes($lesson['video_file_size']) . "
                                <span class='mx-2'>|</span>
                                <i class='fas fa-tv me-1'></i>Quality: {$lesson['video_quality']}
                            </small>
                        </div>
                    </div>
                ";
            }
            break;
            
        case 'google_drive':
            if ($lesson['google_drive_url']) {
                $embedUrl = $videoProcessor->getGoogleDriveEmbedUrl($lesson['google_drive_url']);
                if ($embedUrl) {
                    $html = "
                        <div class='video-container mb-4'>
                            <iframe 
                                src='$embedUrl' 
                                style='width: 100%; height: 400px; border: none;'
                                allowfullscreen
                                data-lesson-id='{$lesson['id']}'
                                data-user-id='{$_SESSION['user_id']}'>
                            </iframe>
                            <div class='video-info mt-2'>
                                <small class='text-muted'>
                                    <i class='fab fa-google-drive me-1'></i>Google Drive Video
                                </small>
                            </div>
                        </div>
                    ";
                }
            }
            break;
            
        case 'external_url':
            if ($lesson['video_url']) {
                $videoUrl = $lesson['video_url'];
                
                // Check if it's a YouTube video
                if (strpos($videoUrl, 'youtube.com') !== false || strpos($videoUrl, 'youtu.be') !== false) {
                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $videoUrl, $matches);
                    $videoId = $matches[1] ?? '';
                    $embedUrl = "https://www.youtube.com/embed/$videoId";
                    $html = "
                        <div class='video-container mb-4'>
                            <iframe 
                                src='$embedUrl' 
                                style='width: 100%; height: 400px; border: none;'
                                allowfullscreen
                                data-lesson-id='{$lesson['id']}'
                                data-user-id='{$_SESSION['user_id']}'>
                            </iframe>
                        </div>
                    ";
                } elseif (strpos($videoUrl, 'vimeo.com') !== false) {
                    preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                    $videoId = $matches[1] ?? '';
                    $embedUrl = "https://player.vimeo.com/video/$videoId";
                    $html = "
                        <div class='video-container mb-4'>
                            <iframe 
                                src='$embedUrl' 
                                style='width: 100%; height: 400px; border: none;'
                                allowfullscreen
                                data-lesson-id='{$lesson['id']}'
                                data-user-id='{$_SESSION['user_id']}'>
                            </iframe>
                        </div>
                    ";
                } else {
                    // Direct video link
                    $html = "
                        <div class='video-container mb-4'>
                            <video 
                                controls 
                                preload='metadata'
                                style='width: 100%; height: 400px;'
                                data-lesson-id='{$lesson['id']}'
                                data-user-id='{$_SESSION['user_id']}'>
                                <source src='$videoUrl'>
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    ";
                }
            }
            break;
    }
    
    return $html;
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
        background: #000;
        border-radius: 8px;
    }
    .video-container video,
    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
    .progress-ring {
        transform: rotate(-90deg);
    }
    .video-info {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 0 0 8px 8px;
        margin-top: -4px;
    }
    .video-processing {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin-bottom: 20px;
    }
    .video-quality-badge {
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 4px;
        background: #6c757d;
        color: white;
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
                                    <?php if ($lesson['lesson_type'] === 'video' && $lesson['video_source'] !== 'none'): ?>
                                        <span class="video-quality-badge ms-1">
                                            <?php 
                                            switch($lesson['video_source']) {
                                                case 'upload': echo '📹'; break;
                                                case 'google_drive': echo '📁'; break;
                                                case 'external_url': echo '🔗'; break;
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
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
                            <div>
                                <span class="badge bg-info"><?php echo ucfirst($currentLesson['lesson_type']); ?></span>
                                <?php if ($currentLesson['lesson_type'] === 'video' && $currentLesson['video_source'] !== 'none'): ?>
                                    <span class="badge bg-secondary ms-1">
                                        <?php 
                                        switch($currentLesson['video_source']) {
                                            case 'upload': echo 'Uploaded Video'; break;
                                            case 'google_drive': echo 'Google Drive'; break;
                                            case 'external_url': echo 'External Link'; break;
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($currentLesson['lesson_type'] === 'video' && $currentLesson['video_source'] !== 'none'): ?>
                            <?php 
                            // Show processing status if video is being processed
                            if ($currentLesson['video_source'] === 'upload' && $currentLesson['video_processing_status'] === 'processing'): ?>
                                <div class="video-processing">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                    <h6>Video Processing</h6>
                                    <p class="text-muted mb-0">Your video is being processed. This may take a few minutes.</p>
                                </div>
                            <?php elseif ($currentLesson['video_source'] === 'upload' && $currentLesson['video_processing_status'] === 'failed'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Video processing failed. Please try uploading the video again.
                                </div>
                            <?php else: ?>
                                <?php echo getVideoEmbedHtml($currentLesson); ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($currentLesson['content']): ?>
                            <div class="lesson-text">
                                <?php echo nl2br(htmlspecialchars($currentLesson['content'])); ?>
                            </div>
                        <?php endif; ?>

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
    
    // Auto-save progress (every 30 seconds)
    setInterval(() => {
        console.log('Auto-saving progress...');
    }, 30000);
</script>
