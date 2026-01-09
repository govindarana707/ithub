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

$action = $_POST['action'] ?? '';
$discussion = new Discussion();

try {
    switch ($action) {
        case 'create_reply':
            $discussionId = intval($_POST['discussion_id'] ?? 0);
            $content = sanitize($_POST['content'] ?? '');
            
            if ($discussionId <= 0 || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                exit;
            }
            
            // Check if student has access to the discussion
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
                $stmt = $conn->prepare("SELECT COUNT(*) as enrolled FROM enrollments WHERE student_id = ? AND course_id = ?");
                $stmt->bind_param("ii", $userId, $discussionDetails['course_id']);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $hasAccess = $result['enrolled'] > 0;
            } elseif ($userRole === 'instructor') {
                // Check if instructor owns the course
                $stmt = $conn->prepare("SELECT COUNT(*) as owns FROM courses WHERE id = ? AND instructor_id = ?");
                $stmt->bind_param("ii", $discussionDetails['course_id'], $userId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $hasAccess = $result['owns'] > 0;
            }
            
            if (!$hasAccess) {
                echo json_encode(['success' => false, 'message' => 'You do not have access to this discussion']);
                exit;
            }
            
            $data = [
                'course_id' => $discussionDetails['course_id'],
                'student_id' => $userId,
                'title' => '', // Replies don't have titles
                'content' => $content,
                'parent_id' => $discussionId
            ];
            
            $result = $discussion->createDiscussion($data);
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Reply posted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to post reply: ' . $result['error']]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
