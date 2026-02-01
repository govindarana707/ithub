<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/RecommendationSystem.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$courseId = $_POST['course_id'] ?? null;
$interactionType = $_POST['interaction_type'] ?? null;
$interactionValue = $_POST['interaction_value'] ?? 1.0;

// Validate input
if (!$courseId || !$interactionType) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$validTypes = ['view', 'enroll', 'lesson_complete', 'quiz_attempt', 'discussion_post'];
if (!in_array($interactionType, $validTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid interaction type']);
    exit;
}

$recommendationSystem = new RecommendationSystem();
$result = $recommendationSystem->logInteraction($userId, $courseId, $interactionType, $interactionValue);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Interaction logged successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to log interaction']);
}
?>
