<?php
require_once '../config/config.php';
require_once '../models/Course.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$studentId = $_SESSION['user_id'];
$lessonId = intval($_POST['lesson_id']);

if (!$lessonId) {
    sendJSON(['success' => false, 'message' => 'Invalid lesson ID']);
}

$course = new Course();

// Verify student is enrolled in the course containing this lesson
$lesson = $course->getLessonById($lessonId);
if (!$lesson) {
    sendJSON(['success' => false, 'message' => 'Lesson not found']);
}

// Check if student is enrolled in this course
$enrollment = $course->getEnrollment($studentId, $lesson['course_id']);
if (!$enrollment) {
    sendJSON(['success' => false, 'message' => 'You are not enrolled in this course']);
}

// Mark lesson as complete
$result = $course->markLessonComplete($studentId, $lessonId);

if ($result) {
    // Update overall course progress
    $course->updateCourseProgress($studentId, $lesson['course_id']);
    
    // Log activity
    logActivity($studentId, 'lesson_completed', "Completed lesson: {$lesson['title']}");
    
    sendJSON(['success' => true, 'message' => 'Lesson marked as complete']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to mark lesson as complete']);
}
?>
