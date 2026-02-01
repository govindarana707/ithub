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
$limit = $_GET['limit'] ?? 5;
$type = $_GET['type'] ?? 'knn';

$recommendationSystem = new RecommendationSystem();

try {
    // Check for cached recommendations first
    $cachedRecommendations = $recommendationSystem->getCachedRecommendations($userId, $type);

    if (!empty($cachedRecommendations)) {
        echo json_encode([
            'success' => true,
            'recommendations' => array_slice($cachedRecommendations, 0, $limit),
            'cached' => true
        ]);
        exit;
    }

    // Generate new recommendations
    if ($type === 'knn') {
        $recommendations = $recommendationSystem->getKNNRecommendations($userId, $limit);
    } else {
        // Fallback to basic recommendations
        require_once '../models/Course.php';
        $course = new Course();
        $recommendations = $course->getRecommendedCourses($userId, $limit);
    }

    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'cached' => false
    ]);
} catch (Exception $e) {
    // Log error and return fallback recommendations
    error_log("Recommendation error: " . $e->getMessage());
    
    // Fallback to simple course recommendations
    try {
        require_once '../models/Course.php';
        $course = new Course();
        $recommendations = $course->getRecommendedCourses($userId, $limit);
        
        echo json_encode([
            'success' => true,
            'recommendations' => $recommendations,
            'cached' => false,
            'fallback' => true,
            'message' => 'Using fallback recommendations due to system error'
        ]);
    } catch (Exception $fallbackError) {
        // Ultimate fallback - return empty recommendations
        echo json_encode([
            'success' => false,
            'message' => 'Unable to generate recommendations: ' . $e->getMessage(),
            'recommendations' => []
        ]);
    }
}
?>
