<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/models/Course.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$courseId = intval($_POST['course_id']);

// Verify course ownership
$course = new Course();
$courseData = $course->getCourseById($courseId);

if (!$courseData) {
    sendJSON(['success' => false, 'message' => 'Course not found']);
}

$role = getUserRole();
if ($role !== 'admin' && ($role === 'instructor' && (int)$courseData['instructor_id'] !== (int)$_SESSION['user_id'])) {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

// Update course data
$updateData = [
    'title' => $_POST['title'] ?? '',
    'description' => $_POST['description'] ?? '',
    'category_id' => intval($_POST['category_id'] ?? 0),
    'price' => floatval($_POST['price'] ?? 0),
    'duration_hours' => intval($_POST['duration_hours'] ?? 0),
    'difficulty_level' => $_POST['difficulty_level'] ?? 'beginner',
    'status' => $_POST['status'] ?? 'draft'
];

$result = $course->updateCourse($courseId, $updateData);

if ($result['success']) {
    sendJSON(['success' => true, 'message' => 'Settings saved successfully']);
} else {
    sendJSON(['success' => false, 'message' => $result['error'] ?? 'Failed to save settings']);
}
?>
