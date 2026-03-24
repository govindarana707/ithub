<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../models/Database.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$studentId = $_POST['student_id'] ?? $_SESSION['user_id'];
$courseId = $_POST['course_id'] ?? 0;
$action = $_POST['action'] ?? 'start';

// Verify user can only track their own study time
if ($studentId != $_SESSION['user_id'] && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($action === 'start') {
        // Start a new study session
        $stmt = $conn->prepare("
            INSERT INTO study_sessions (student_id, course_id, start_time, created_at) 
            VALUES (?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            start_time = NOW(), 
            end_time = NULL, 
            study_time = 0
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        
    } elseif ($action === 'end') {
        // End current study session
        $stmt = $conn->prepare("
            UPDATE study_sessions 
            SET end_time = NOW(),
                study_time = TIMESTAMPDIFF(MINUTE, start_time, NOW())
            WHERE student_id = ? AND course_id = ? AND end_time IS NULL
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Study time tracked successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error tracking study time: ' . $e->getMessage()
    ]);
}
?>
