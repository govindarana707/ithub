<?php
require_once '../config/config.php';
require_once '../controllers/CourseController.php';

// Set headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize controller
$controller = new CourseController();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Parse input data
$data = [];
if ($method === 'GET') {
    $data = $_GET;
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
}

try {
    // Route the request
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                case '':
                    $result = $controller->index();
                    break;
                    
                case 'search':
                    $query = $data['query'] ?? '';
                    $filters = $data;
                    unset($filters['query']);
                    $result = $controller->search($query, $filters);
                    break;
                    
                case 'details':
                    $courseId = intval($data['course_id'] ?? 0);
                    $result = $controller->show($courseId);
                    break;
                    
                case 'categories':
                    $service = new CourseService();
                    $result = [
                        'success' => true,
                        'data' => $service->getCategories()
                    ];
                    break;
                    
                case 'popular':
                    $limit = intval($data['limit'] ?? 10);
                    $service = new CourseService();
                    $result = [
                        'success' => true,
                        'data' => $service->getPopularCourses($limit)
                    ];
                    break;
                    
                default:
                    $result = [
                        'success' => false,
                        'error' => 'INVALID_ACTION',
                        'message' => 'Unknown action: ' . $action
                    ];
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'enroll':
                    $courseId = intval($data['course_id'] ?? 0);
                    $paymentMethod = $data['payment_method'] ?? 'trial';
                    $result = $controller->enroll($courseId, $paymentMethod);
                    break;
                    
                default:
                    $result = [
                        'success' => false,
                        'error' => 'INVALID_ACTION',
                        'message' => 'Unknown action: ' . $action
                    ];
            }
            break;
            
        default:
            $result = [
                'success' => false,
                'error' => 'INVALID_METHOD',
                'message' => 'Method not allowed: ' . $method
            ];
    }
    
    // Send response
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
    
} catch (Exception $e) {
    // Handle unexpected errors
    error_log('API Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
