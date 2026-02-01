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

$recommendationSystem = new RecommendationSystem();

try {
    $learningPath = $recommendationSystem->getPersonalizedLearningPath($userId);
    
    echo json_encode([
        'success' => true,
        'learning_path' => $learningPath
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate learning path: ' . $e->getMessage()
    ]);
}
?>
