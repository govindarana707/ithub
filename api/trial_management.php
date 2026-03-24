<?php
require_once '../config/config.php';
require_once '../services/TrialService.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Initialize trial service
$trialService = new TrialService();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get action from URL or POST data
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check authentication
if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Authentication required', 'code' => 'AUTH_REQUIRED']);
}

$userId = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequests($action, $userId, $trialService);
            break;
        case 'POST':
            handlePostRequests($action, $userId, $trialService);
            break;
        case 'PUT':
            handlePutRequests($action, $userId, $trialService);
            break;
        default:
            sendJSON(['success' => false, 'message' => 'Method not allowed', 'code' => 'METHOD_NOT_ALLOWED']);
    }
} catch (Exception $e) {
    error_log("Trial API Error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Internal server error', 'code' => 'INTERNAL_ERROR']);
}

/**
 * Handle GET requests
 */
function handleGetRequests($action, $userId, $trialService) {
    switch ($action) {
        case 'my_trials':
            $trials = $trialService->getUserActiveTrials($userId);
            sendJSON(['success' => true, 'trials' => $trials]);
            break;
            
        case 'statistics':
            // Only admin can view statistics
            if (getUserRole() !== 'admin') {
                sendJSON(['success' => false, 'message' => 'Access denied', 'code' => 'ACCESS_DENIED']);
            }
            
            $dateFrom = $_GET['date_from'] ?? null;
            $dateTo = $_GET['date_to'] ?? null;
            $stats = $trialService->getTrialStatistics($dateFrom, $dateTo);
            sendJSON(['success' => true, 'statistics' => $stats]);
            break;
            
        case 'check_trial':
            $courseId = intval($_GET['course_id'] ?? 0);
            if ($courseId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
            }
            
            $hasTrial = $trialService->hasActiveTrial($userId, $courseId);
            sendJSON(['success' => true, 'has_trial' => $hasTrial]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action', 'code' => 'INVALID_ACTION']);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequests($action, $userId, $trialService) {
    switch ($action) {
        case 'enroll_trial':
            $courseId = intval($_POST['course_id'] ?? 0);
            if ($courseId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
            }
            
            $result = $trialService->enrollInTrial($userId, $courseId);
            sendJSON($result);
            break;
            
        case 'convert_trial':
            $courseId = intval($_POST['course_id'] ?? 0);
            $paymentId = intval($_POST['payment_id'] ?? 0);
            
            if ($courseId <= 0 || $paymentId <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid course or payment ID', 'code' => 'INVALID_IDS']);
            }
            
            $result = $trialService->convertTrialToPaid($userId, $courseId, $paymentId);
            sendJSON($result);
            break;
            
        case 'extend_trial':
            // Only admin can extend trials
            if (getUserRole() !== 'admin') {
                sendJSON(['success' => false, 'message' => 'Access denied', 'code' => 'ACCESS_DENIED']);
            }
            
            $enrollmentId = intval($_POST['enrollment_id'] ?? 0);
            $days = intval($_POST['days'] ?? 0);
            
            if ($enrollmentId <= 0 || $days <= 0) {
                sendJSON(['success' => false, 'message' => 'Invalid enrollment ID or days', 'code' => 'INVALID_INPUT']);
            }
            
            $result = $trialService->extendTrial($enrollmentId, $days);
            sendJSON($result);
            break;
            
        case 'process_expirations':
            // Only admin or system can process expirations
            if (getUserRole() !== 'admin') {
                sendJSON(['success' => false, 'message' => 'Access denied', 'code' => 'ACCESS_DENIED']);
            }
            
            $result = $trialService->processTrialExpirations();
            sendJSON(['success' => true, 'result' => $result]);
            break;
            
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action', 'code' => 'INVALID_ACTION']);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequests($action, $userId, $trialService) {
    // Parse PUT data
    parse_str(file_get_contents('php://input'), $putData);
    
    switch ($action) {
        default:
            sendJSON(['success' => false, 'message' => 'Invalid action', 'code' => 'INVALID_ACTION']);
    }
}
?>
