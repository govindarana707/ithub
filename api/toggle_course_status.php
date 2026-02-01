<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';

requireAdmin();
header('Content-Type: application/json');

$courseId = intval($_POST['course_id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$courseId || !in_array($status, ['published', 'draft'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $course = new Course();
    $result = $course->updateCourse($courseId, ['status' => $status]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => "Course status updated to $status"
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update course status'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
