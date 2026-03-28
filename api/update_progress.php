<?php
/**
 * API Endpoint for Progress Updates
 * Handles AJAX requests for updating lesson and course progress
 */

require_once '../includes/session_helper.php';
require_once '../config/config.php';
require_once '../includes/progress_manager.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isUserLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

// Get and validate input data
$action = $_POST['action'] ?? '';
$studentId = getCurrentUserId();

try {
    $progressManager = new ProgressManager();
    
    switch ($action) {
        case 'update_lesson_progress':
            $lessonId = intval($_POST['lesson_id'] ?? 0);
            $progressData = [];
            
            // Collect progress data
            if (isset($_POST['video_watch_time'])) {
                $progressData['video_watch_time_seconds'] = intval($_POST['video_watch_time']);
            }
            
            if (isset($_POST['video_completion'])) {
                $progressData['video_completion_percentage'] = floatval($_POST['video_completion']);
            }
            
            if (isset($_POST['notes_viewed'])) {
                $progressData['notes_viewed'] = intval($_POST['notes_viewed']);
            }
            
            if (isset($_POST['assignments_completed'])) {
                $progressData['assignments_completed'] = intval($_POST['assignments_completed']);
            }
            
            if (isset($_POST['assignments_total'])) {
                $progressData['assignments_total'] = intval($_POST['assignments_total']);
            }
            
            if (isset($_POST['resources_viewed'])) {
                $progressData['resources_viewed'] = intval($_POST['resources_viewed']);
            }
            
            if (isset($_POST['resources_total'])) {
                $progressData['resources_total'] = intval($_POST['resources_total']);
            }
            
            if (isset($_POST['time_spent'])) {
                $progressData['time_spent_minutes'] = intval($_POST['time_spent']);
            }
            
            // Validate lesson ID
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            // Update progress
            $progressManager->updateLessonProgress($studentId, $lessonId, $progressData);
            
            // Get updated course progress
            $courseId = $progressManager->getCourseIdByLesson($lessonId);
            $courseProgress = $courseId ? $progressManager->updateCourseProgress($studentId, $courseId) : 0;
            
            sendJSON([
                'success' => true,
                'message' => 'Progress updated successfully',
                'course_progress' => $courseProgress
            ]);
            break;
            
        case 'mark_lesson_complete':
            $lessonId = intval($_POST['lesson_id'] ?? 0);
            
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            $progressManager->markLessonComplete($studentId, $lessonId);
            
            // Get updated course progress
            $courseId = $progressManager->getCourseIdByLesson($lessonId);
            $courseProgress = $courseId ? $progressManager->updateCourseProgress($studentId, $courseId) : 0;
            
            sendJSON([
                'success' => true,
                'message' => 'Lesson marked as complete',
                'course_progress' => $courseProgress
            ]);
            break;
            
        case 'update_video_progress':
            $lessonId = intval($_POST['lesson_id'] ?? 0);
            $watchTime = intval($_POST['watch_time'] ?? 0);
            $completion = floatval($_POST['completion'] ?? 0);
            
            if ($lessonId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
            }
            
            $progressManager->updateVideoProgress($studentId, $lessonId, $watchTime, $completion);
            
            sendJSON([
                'success' => true,
                'message' => 'Video progress updated'
            ]);
            break;
            
        case 'update_study_time':
            $lessonId = intval($_POST['lesson_id'] ?? 0);
            $additionalMinutes = intval($_POST['additional_minutes'] ?? 0);
            
            if ($lessonId <= 0 || $additionalMinutes <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid parameters']);
            }
            
            $progressManager->updateStudyTime($studentId, $lessonId, $additionalMinutes);
            
            sendJSON([
                'success' => true,
                'message' => 'Study time updated'
            ]);
            break;
            
        case 'get_course_progress':
            $courseId = intval($_POST['course_id'] ?? 0);
            
            if ($courseId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid course ID']);
            }
            
            $progressDetails = $progressManager->getCourseProgressDetails($studentId, $courseId);
            $overallProgress = $progressManager->updateCourseProgress($studentId, $courseId);
            
            sendJSON([
                'success' => true,
                'progress_details' => $progressDetails,
                'overall_progress' => $overallProgress
            ]);
            break;
            
        case 'get_student_stats':
            $stats = $progressManager->getStudentProgressStats($studentId);
            $learningStreak = $progressManager->getLearningStreak($studentId);
            
            sendJSON([
                'success' => true,
                'stats' => $stats,
                'learning_streak' => $learningStreak
            ]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Progress API Error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'An error occurred while updating progress']);
}

/**
 * Send JSON response
 */
function sendJSON($data) {
    echo json_encode($data);
    exit();
}
?>
