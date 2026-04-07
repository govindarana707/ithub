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
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses_new WHERE instructor_id = ?");
    if ($stmt === false) {
        $stats['courses'] = 0;
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['courses'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }
    
    // Get students count (unique students across all courses)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT e.user_id) as count 
        FROM enrollments_new e 
        JOIN courses_new c ON e.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    if ($stmt === false) {
        $stats['students'] = 0;
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['students'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }
    
    // Get quizzes count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM quizzes q 
        JOIN courses_new c ON q.course_id = c.id 
        WHERE c.instructor_id = ?
    ");
    if ($stmt === false) {
        $stats['quizzes'] = 0;
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['quizzes'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }
    
    // Get discussions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM discussions d 
        JOIN courses_new c ON d.course_id = c.id 
        WHERE c.instructor_id = ? AND d.parent_id IS NULL
    ");
    if ($stmt === false) {
        $stats['discussions'] = 0;
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['discussions'] = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
    }
    
    // Get earnings
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(c.price), 0) as total 
        FROM enrollments_new e 
        JOIN courses_new c ON e.course_id = c.id 
        WHERE c.instructor_id = ? AND e.status = 'active'
    ");
    if ($stmt === false) {
        $stats['earnings'] = 0;
    } else {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['earnings'] = number_format($stmt->get_result()->fetch_assoc()['total'], 2);
        $stmt->close();
    }
    
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
