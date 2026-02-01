<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/config.php';
require_once '../models/Database.php';
require_once '../models/Progress.php';
require_once '../includes/auth.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize authentication
$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$progress = new Progress();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($progress, $action, $user);
            break;
        case 'POST':
            handlePostRequest($progress, $action, $user);
            break;
        case 'PUT':
            handlePutRequest($progress, $action, $user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

function handleGetRequest($progress, $action, $user) {
    $studentId = $user['id'];
    
    switch ($action) {
        case 'course_progress':
            $courseId = $_GET['course_id'] ?? null;
            if (!$courseId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID required']);
                return;
            }
            
            $courseProgress = $progress->getCourseProgress($studentId, $courseId);
            $lessonsProgress = $progress->getCourseLessonsProgress($studentId, $courseId);
            
            echo json_encode([
                'success' => true,
                'course_progress' => $courseProgress,
                'lessons_progress' => $lessonsProgress
            ]);
            break;
            
        case 'overall_progress':
            $overallProgress = $progress->getStudentOverallProgress($studentId);
            echo json_encode([
                'success' => true,
                'progress' => $overallProgress
            ]);
            break;
            
        case 'statistics':
            $stats = $progress->getProgressStatistics($studentId);
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            break;
            
        case 'study_sessions':
            $courseId = $_GET['course_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            $sessions = $progress->getStudySessions($studentId, $courseId, $limit);
            
            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            break;
            
        case 'leaderboard':
            $courseId = $_GET['course_id'] ?? null;
            $limit = $_GET['limit'] ?? 10;
            $leaderboard = $progress->getLeaderboard($courseId, $limit);
            
            echo json_encode([
                'success' => true,
                'leaderboard' => $leaderboard
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function handlePostRequest($progress, $action, $user) {
    $studentId = $user['id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_lesson_progress':
            $courseId = $input['course_id'] ?? null;
            $lessonId = $input['lesson_id'] ?? null;
            
            if (!$courseId || !$lessonId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID and Lesson ID required']);
                return;
            }
            
            $result = $progress->updateLessonProgress($studentId, $courseId, $lessonId, $input);
            echo json_encode($result);
            break;
            
        case 'mark_lesson_completed':
            $courseId = $input['course_id'] ?? null;
            $lessonId = $input['lesson_id'] ?? null;
            
            if (!$courseId || !$lessonId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID and Lesson ID required']);
                return;
            }
            
            $result = $progress->markLessonCompleted($studentId, $courseId, $lessonId);
            echo json_encode($result);
            break;
            
        case 'start_study_session':
            $courseId = $input['course_id'] ?? null;
            $lessonId = $input['lesson_id'] ?? null;
            $activityType = $input['activity_type'] ?? 'reading';
            
            if (!$courseId || !$lessonId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID and Lesson ID required']);
                return;
            }
            
            // Start session tracking
            $sessionId = session_id();
            $_SESSION['study_session'] = [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'lesson_id' => $lessonId,
                'activity_type' => $activityType,
                'start_time' => time()
            ];
            
            echo json_encode([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Study session started'
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}

function handlePutRequest($progress, $action, $user) {
    $studentId = $user['id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'end_study_session':
            if (!isset($_SESSION['study_session'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No active study session']);
                return;
            }
            
            $session = $_SESSION['study_session'];
            $endTime = time();
            $duration = ($endTime - $session['start_time']) / 60; // Convert to minutes
            
            $progressData = [
                'session_start' => date('Y-m-d H:i:s', $session['start_time']),
                'duration_minutes' => round($duration),
                'activity_type' => $session['activity_type'],
                'time_spent_minutes' => round($duration),
                'watch_percentage' => $input['watch_percentage'] ?? 0
            ];
            
            $result = $progress->updateLessonProgress(
                $session['student_id'],
                $session['course_id'],
                $session['lesson_id'],
                $progressData
            );
            
            unset($_SESSION['study_session']);
            
            echo json_encode([
                'success' => true,
                'duration_minutes' => round($duration),
                'message' => 'Study session ended and progress updated'
            ]);
            break;
            
        case 'update_video_progress':
            $courseId = $input['course_id'] ?? null;
            $lessonId = $input['lesson_id'] ?? null;
            $currentTime = $input['current_time'] ?? 0;
            $duration = $input['duration'] ?? 0;
            
            if (!$courseId || !$lessonId) {
                http_response_code(400);
                echo json_encode(['error' => 'Course ID and Lesson ID required']);
                return;
            }
            
            $watchPercentage = $duration > 0 ? ($currentTime / $duration) * 100 : 0;
            
            $progressData = [
                'last_position_seconds' => $currentTime,
                'watch_percentage' => min($watchPercentage, 100),
                'time_spent_minutes' => $input['time_spent_minutes'] ?? 0,
                'detailed_progress' => [
                    'video_watch_time_seconds' => $currentTime,
                    'video_completion_percentage' => min($watchPercentage, 100),
                    'time_spent_minutes' => $input['time_spent_minutes'] ?? 0
                ]
            ];
            
            $result = $progress->updateLessonProgress($studentId, $courseId, $lessonId, $progressData);
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
    }
}
?>
