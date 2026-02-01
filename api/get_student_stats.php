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

$database = new Database();
$conn = $database->getConnection();
$studentId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($conn, $studentId);
        break;
    case 'POST':
        handlePostRequest($conn, $studentId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetRequest($conn, $studentId) {
    // Get student statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as enrolled_courses,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
            COALESCE(AVG(progress_percentage), 0) as avg_progress
        FROM enrollments 
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $enrollmentStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get quiz statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_attempts,
            COALESCE(AVG(percentage), 0) as avg_score,
            MAX(percentage) as highest_score
        FROM quiz_attempts 
        WHERE student_id = ? AND status = 'completed'
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $quizStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get certificate count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as certificates
        FROM certificates 
        WHERE student_id = ? AND status = 'issued'
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $certificateStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get study time
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(time_spent_minutes), 0) as total_minutes
        FROM course_progress
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $studyTimeStats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => [
            'enrolled_courses' => (int)$enrollmentStats['enrolled_courses'],
            'completed_courses' => (int)$enrollmentStats['completed_courses'],
            'avg_progress' => round($enrollmentStats['avg_progress'], 2),
            'total_attempts' => (int)$quizStats['total_attempts'],
            'avg_score' => round($quizStats['avg_score'], 2),
            'highest_score' => round($quizStats['highest_score'], 2),
            'certificates' => (int)$certificateStats['certificates'],
            'study_time_hours' => round($studyTimeStats['total_minutes'] / 60, 1)
        ]
    ]);
}

function handlePostRequest($conn, $studentId) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_stats':
            // Refresh statistics (same as GET)
            handleGetRequest($conn, $studentId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

$conn->close();
?>
