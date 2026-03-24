<?php
require_once '../config/config.php';
require_once '../services/EnrollmentService.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize service
$enrollmentService = new EnrollmentService();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'enroll':
                    // Process enrollment request
                    $courseId = intval($_POST['course_id'] ?? 0);
                    $paymentMethod = sanitize($_POST['payment_method'] ?? 'trial');
                    
                    // Authorization check
                    if (!isLoggedIn() || getUserRole() !== 'student') {
                        sendJSON([
                            'success' => false,
                            'error' => 'UNAUTHORIZED',
                            'message' => 'Only students can enroll in courses'
                        ]);
                    }
                    
                    if ($courseId <= 0) {
                        sendJSON([
                            'success' => false,
                            'error' => 'INVALID_COURSE',
                            'message' => 'Invalid course ID'
                        ]);
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $result = $enrollmentService->processEnrollment($userId, $courseId, $paymentMethod);
                    
                    sendJSON($result);
                    break;
                    
                case 'check_status':
                    // Check enrollment status
                    $courseId = intval($_POST['course_id'] ?? 0);
                    
                    if (!isLoggedIn()) {
                        sendJSON([
                            'success' => false,
                            'error' => 'NOT_LOGGED_IN',
                            'message' => 'Please login to check enrollment status'
                        ]);
                    }
                    
                    if ($courseId <= 0) {
                        sendJSON([
                            'success' => false,
                            'error' => 'INVALID_COURSE',
                            'message' => 'Invalid course ID'
                        ]);
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $status = $enrollmentService->getEnrollmentStatus($userId, $courseId);
                    
                    sendJSON([
                        'success' => true,
                        'data' => $status
                    ]);
                    break;
                    
                case 'cancel_payment':
                    // Cancel pending payment
                    if (!isLoggedIn()) {
                        sendJSON([
                            'success' => false,
                            'error' => 'NOT_LOGGED_IN',
                            'message' => 'Please login to cancel payment'
                        ]);
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $enrollmentService->cancelPendingPayment($userId);
                    
                    sendJSON([
                        'success' => true,
                        'message' => 'Payment cancelled successfully'
                    ]);
                    break;
                    
                default:
                    sendJSON([
                        'success' => false,
                        'error' => 'INVALID_ACTION',
                        'message' => 'Unknown action: ' . $action
                    ]);
            }
            break;
            
        case 'GET':
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'my_enrollments':
                    // Get user's enrollments
                    if (!isLoggedIn()) {
                        sendJSON([
                            'success' => false,
                            'error' => 'NOT_LOGGED_IN',
                            'message' => 'Please login to view enrollments'
                        ]);
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $enrollments = $enrollmentService->getUserEnrollments($userId);
                    
                    sendJSON([
                        'success' => true,
                        'data' => $enrollments
                    ]);
                    break;
                    
                case 'pending_payment':
                    // Check for pending payment
                    if (!isLoggedIn()) {
                        sendJSON([
                            'success' => false,
                            'error' => 'NOT_LOGGED_IN',
                            'message' => 'Please login to check payment status'
                        ]);
                    }
                    
                    $userId = $_SESSION['user_id'];
                    $pendingPayment = $enrollmentService->getPendingPayment($userId);
                    
                    sendJSON([
                        'success' => true,
                        'data' => $pendingPayment
                    ]);
                    break;
                    
                default:
                    sendJSON([
                        'success' => false,
                        'error' => 'INVALID_ACTION',
                        'message' => 'Unknown action: ' . $action
                    ]);
            }
            break;
            
        default:
            sendJSON([
                'success' => false,
                'error' => 'INVALID_METHOD',
                'message' => 'Method not allowed: ' . $method
            ]);
    }
    
} catch (Exception $e) {
    error_log('Enrollment API Error: ' . $e->getMessage());
    
    sendJSON([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'An unexpected error occurred'
    ]);
}
?>
