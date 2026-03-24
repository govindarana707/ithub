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

// Verify user can only access their own courses
if ($studentId != $_SESSION['user_id'] && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $course = new Course();
    $enrolledCourses = $course->getEnrolledCourses($studentId);
    
    // Enhance course data with additional information
    foreach ($enrolledCourses as &$courseData) {
        $courseData['progress_percentage'] = $course->calculateCourseProgress($studentId, $courseData['id']);
        $courseData['has_certificate'] = $course->hasCertificate($studentId, $courseData['id']);
        $courseData['study_hours'] = $course->getStudyTime($studentId, $courseData['id']) ?: 0;
        
        // Get enrollment details
        $enrollment = $course->getEnrollment($studentId, $courseData['id']);
        $courseData['last_accessed'] = $enrollment['last_accessed'] ?? $enrollment['enrolled_at'] ?? date('Y-m-d H:i:s');
        $courseData['enrollment_date'] = $enrollment['enrolled_at'] ?? date('Y-m-d H:i:s');
        
        // Add rating data (fallback to default if not available)
        $courseData['rating'] = $courseData['rating'] ?? 4.5;
        $courseData['reviews_count'] = $courseData['reviews_count'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $enrolledCourses,
        'count' => count($enrolledCourses)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching enrolled courses: ' . $e->getMessage()
    ]);
}
?>
