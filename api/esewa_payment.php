<?php
require_once '../includes/session_helper.php';
require_once '../config/config.php';
require_once '../models/User.php';
require_once '../includes/AuthEnhancements.php';
require_once __DIR__ . '/../services/EnrollmentServiceNew.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue', 'code' => 'AUTH_REQUIRED']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method', 'code' => 'INVALID_METHOD']);
}

$courseId = intval($_POST['course_id'] ?? 0);
$studentId = $_SESSION['user_id'];

if ($courseId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
}

try {
    // Initialize services
    $paymentService = new PaymentService();
    $enrollmentService = new EnrollmentServiceNew();
    
    // Get course details
    require_once __DIR__ . '/../models/Course.php';
    $course = new Course();
    $courseDetails = $course->getCourseById($courseId);

    if (!$courseDetails) {
        sendJSON(['success' => false, 'message' => 'Course not found', 'code' => 'COURSE_NOT_FOUND']);
    }

    if ($courseDetails['status'] !== 'published') {
        sendJSON(['success' => false, 'message' => 'Course not available', 'code' => 'COURSE_UNAVAILABLE']);
    }

    // Check if already enrolled
    if ($enrollmentService->isUserEnrolled($studentId, $courseId)) {
        sendJSON(['success' => false, 'message' => 'Already enrolled', 'code' => 'ALREADY_ENROLLED']);
    }

    // Create payment
    $paymentData = [
        'user_id' => $studentId,
        'course_id' => $courseId,
        'amount' => $courseDetails['price'],
        'payment_method' => 'esewa',
        'currency' => 'NPR',
        'status' => 'pending'
    ];
    
    $paymentResult = $paymentService->createPayment($paymentData);

    if (!$paymentResult['success']) {
        sendJSON(['success' => false, 'message' => 'Failed to create payment record', 'error' => $paymentResult['error']]);
    }

    sendJSON([
        'success' => true,
        'payment_form' => $paymentResult['payment_form'],
        'course' => [
            'id' => $courseDetails['id'],
            'title' => $courseDetails['title'],
            'price' => $courseDetails['price']
        ],
        'payment_id' => $paymentResult['payment_id'],
        'transaction_uuid' => $paymentResult['transaction_uuid']
    ]);
    
} catch (Exception $e) {
    error_log("eSewa payment API error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Payment service error', 'error' => $e->getMessage()]);
}
?>
