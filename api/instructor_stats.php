<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

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

try {
    $conn = connectDB();
    $stats = [];
    
    // Get courses count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE instructor_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['courses'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get students count (unique students across all courses)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.student_id) as count 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['students'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get quizzes count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['quizzes'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get discussions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM discussions d 
        JOIN courses c ON d.course_id = c.id 
        WHERE c.instructor_id = ? AND d.parent_id IS NULL
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['discussions'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get earnings
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(c.price), 0) as total 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.instructor_id = ? AND e.status = 'active'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['earnings'] = number_format($stmt->get_result()->fetch_assoc()['total'], 2);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'courses' => $stats['courses'],
        'students' => $stats['students'],
        'quizzes' => $stats['quizzes'],
        'discussions' => $stats['discussions'],
        'earnings' => $stats['earnings']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading stats: ' . $e->getMessage()
    ]);
}
?>
