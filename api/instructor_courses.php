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

if (getUserRole() !== 'instructor' && getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$instructorId = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($conn, $instructorId);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGetRequest($conn, $instructorId) {
    // Get instructor's courses
    $stmt = $conn->prepare("
        SELECT c.id, c.title, c.description, c.category, c.price, c.status, 
               c.created_at, c.thumbnail,
               COUNT(DISTINCT e.id) as enrollment_count,
               COUNT(DISTINCT l.id) as lesson_count
        FROM courses_new c
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN lessons l ON c.id = l.course_id
        WHERE c.instructor_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    
    if ($stmt === false) {
        // Try with courses table as fallback
        $stmt = $conn->prepare("
            SELECT c.id, c.title, c.description, c.category, c.price, c.status, 
                   c.created_at, c.thumbnail,
                   0 as enrollment_count,
                   0 as lesson_count
            FROM courses c
            WHERE c.instructor_id = ?
            ORDER BY c.created_at DESC
        ");
    }
    
    if ($stmt === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $conn->error,
            'courses' => []
        ]);
        return;
    }
    
    $stmt->bind_param("i", $instructorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'category' => $row['category'] ?? 'General',
            'price' => (float)($row['price'] ?? 0),
            'status' => $row['status'] ?? 'draft',
            'created_at' => $row['created_at'],
            'thumbnail' => $row['thumbnail'] ?? '',
            'enrollment_count' => (int)($row['enrollment_count'] ?? 0),
            'lesson_count' => (int)($row['lesson_count'] ?? 0)
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'total' => count($courses)
    ]);
}

$conn->close();
?>
