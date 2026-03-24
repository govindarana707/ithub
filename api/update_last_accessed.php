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

// Verify user can only update their own last accessed time
if ($studentId != $_SESSION['user_id'] && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!$courseId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Update last accessed time in enrollments table
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET last_accessed = NOW(),
            updated_at = NOW()
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Last accessed time updated',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No enrollment found or no update needed'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating last accessed time: ' . $e->getMessage()
    ]);
}
?>