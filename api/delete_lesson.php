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

$lessonId = intval($_POST['lesson_id']);

// Verify lesson ownership
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT l.course_id, c.instructor_id 
    FROM lessons l 
    JOIN courses_new c ON l.course_id = c.id 
    WHERE l.id = ?
");

if (!$stmt) {
    sendJSON(['success' => false, 'message' => 'Database error']);
}

$stmt->bind_param("i", $lessonId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    sendJSON(['success' => false, 'message' => 'Lesson not found']);
}

$courseId = $result['course_id'];
$instructorId = $result['instructor_id'];

// Check access permissions
$role = getUserRole();
if ($role !== 'admin' && ($role === 'instructor' && (int)$instructorId !== (int)$_SESSION['user_id'])) {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

// Delete the lesson
$stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
if (!$stmt) {
    sendJSON(['success' => false, 'message' => 'Database error']);
}

$stmt->bind_param("i", $lessonId);
if ($stmt->execute()) {
    sendJSON(['success' => true, 'message' => 'Lesson deleted successfully']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to delete lesson']);
}
?>
