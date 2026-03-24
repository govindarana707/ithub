<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = $_GET['student_id'] ?? $_SESSION['user_id'];
$courseId = $_GET['course_id'] ?? 0;

// Verify user can only access their own progress
if ($studentId != $_SESSION['user_id'] && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!$courseId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    $course = new Course();
    
    // Get course progress
    $progress = $course->calculateCourseProgress($studentId, $courseId);
    
    // Get course details
    $courseDetails = $course->getCourseById($courseId);
    
    // Get enrollment details
    $enrollment = $course->getEnrollment($studentId, $courseId);
    
    // Get lessons with completion status
    $lessons = $course->getCourseLessons($courseId, $studentId);
    
    // Calculate lesson statistics
    $totalLessons = count($lessons);
    $completedLessons = count(array_filter($lessons, fn($lesson) => $lesson['is_completed']));
    $inProgressLessons = $totalLessons - $completedLessons;
    
    // Get study time
    $studyHours = $course->getStudyTime($studentId, $courseId);
    
    // Check if certificate is available
    $hasCertificate = $course->hasCertificate($studentId, $courseId);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'progress_percentage' => round($progress, 2),
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedLessons,
            'in_progress_lessons' => $inProgressLessons,
            'study_hours' => $studyHours,
            'has_certificate' => $hasCertificate,
            'course_details' => $courseDetails,
            'enrollment' => $enrollment,
            'lessons' => $lessons
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching course progress: ' . $e->getMessage()
    ]);
}
?>
