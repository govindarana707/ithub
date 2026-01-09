<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'];

if ($userRole !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$discussion = new Discussion();

try {
    // Get last check time from session
    $lastCheck = $_SESSION['last_discussion_check'] ?? 0;
    $currentTime = time();
    
    // Get instructor's latest discussions
    $allDiscussions = $discussion->getInstructorDiscussions($userId, 1, 100);
    
    $newCount = 0;
    foreach ($allDiscussions as $disc) {
        if (strtotime($disc['created_at']) > $lastCheck) {
            $newCount++;
        }
    }
    
    // Update last check time
    $_SESSION['last_discussion_check'] = $currentTime;
    
    echo json_encode([
        'success' => true,
        'new_count' => $newCount,
        'timestamp' => $currentTime
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking for new discussions: ' . $e->getMessage()
    ]);
}
?>
