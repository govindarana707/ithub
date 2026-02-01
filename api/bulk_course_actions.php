<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/models/Course.php';

requireAdmin();
header('Content-Type: application/json');

$course = new Course();
$action = $_POST['action'] ?? '';
$courseIds = $_POST['course_ids'] ?? [];

if (empty($action) || empty($courseIds)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $successCount = 0;
    $totalCount = count($courseIds);
    
    foreach ($courseIds as $courseId) {
        $courseId = intval($courseId);
        
        switch ($action) {
            case 'publish':
                $result = $course->updateCourse($courseId, ['status' => 'published']);
                if ($result) $successCount++;
                break;
                
            case 'unpublish':
                $result = $course->updateCourse($courseId, ['status' => 'draft']);
                if ($result) $successCount++;
                break;
                
            case 'delete':
                $result = $course->deleteCourse($courseId);
                if ($result['success']) $successCount++;
                break;
        }
    }
    
    $message = "Successfully processed $successCount out of $totalCount courses";
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'processed' => $successCount,
        'total' => $totalCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
