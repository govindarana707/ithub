<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Discussion.php';

// Authentication check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userRole = getUserRole();
$userId = $_SESSION['user_id'];

// Role-based access control
if ($userRole !== 'student' && $userRole !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$discussionId = intval($_POST['discussion_id'] ?? 0);

if ($discussionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid discussion ID']);
    exit;
}

$discussion = new Discussion();

try {
    // Check if user has access to this discussion
    $discussionDetails = $discussion->getDiscussionById($discussionId);
    if (!$discussionDetails) {
        echo json_encode(['success' => false, 'message' => 'Discussion not found']);
        exit;
    }
    
    // Check access based on user role
    $conn = connectDB();
    $hasAccess = false;
    
    if ($userRole === 'student') {
        // Check if student is enrolled in the course
        $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'");
        $stmt->bind_param("ii", $userId, $discussionDetails['course_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $hasAccess = $result['enrolled'] > 0;
    } elseif ($userRole === 'instructor') {
        // Check if instructor owns the course
        $stmt = $conn->prepare("SELECT COUNT(*) as owns FROM courses_new WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param("ii", $discussionDetails['course_id'], $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $hasAccess = $result['owns'] > 0;
    }
    
    if (!$hasAccess) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Increment view count
    $result = $discussion->incrementViewCount($discussionId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'View count updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update view count']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
