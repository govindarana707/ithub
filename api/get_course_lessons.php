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

// Get lessons for the course
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT l.*, 
           (SELECT COUNT(*) FROM lesson_progress lc WHERE lc.lesson_id = l.id AND lc.student_id = ? AND lc.completed = TRUE) as completion_count
    FROM lessons l 
    WHERE l.course_id = ? 
    ORDER BY l.lesson_order ASC, l.id ASC
");

if (!$stmt) {
    sendJSON(['success' => false, 'message' => 'Database error']);
}

$stmt->bind_param("ii", $_SESSION['user_id'], $courseId);
$stmt->execute();
$lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendJSON([
    'success' => true,
    'lessons' => $lessons
]);
?>
