<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/ProgressTracking.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$courseId = $_GET['course_id'] ?? null;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$progressTracking = new ProgressTracking();
$progressReport = $progressTracking->getProgressReport($userId, $courseId);

echo json_encode([
    'success' => true,
    'progress' => $progressReport
]);
?>
