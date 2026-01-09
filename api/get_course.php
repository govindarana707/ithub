<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Course.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$courseId = intval($_GET['course_id']);

// Verify admin or instructor access
$role = getUserRole();
if ($role !== 'admin') {
    // If instructor, ensure they own the course
    if ($role === 'instructor') {
        $course = new Course();
        $c = $course->getCourseById($courseId);
        if (!$c || (int)$c['instructor_id'] !== (int)$_SESSION['user_id']) {
            sendJSON(['success' => false, 'message' => 'Access denied']);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Access denied']);
    }
}

$course = new Course();
$courseData = $course->getCourseById($courseId);

if ($courseData) {
    sendJSON([
        'success' => true,
        'course' => $courseData
    ]);
} else {
    sendJSON(['success' => false, 'message' => 'Course not found']);
}
?>
