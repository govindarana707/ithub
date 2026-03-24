<?php
require_once '../config/config.php';
require_once '../services/PaymentService.php';

/**
 * eSewa Payment Request Handler
 * 
 * This file handles the creation of eSewa payment requests and redirects users
 * to the eSewa payment gateway with proper signature generation.
 */

// Get course ID from URL
$courseId = intval($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    sendJSON(['success' => false, 'error' => 'Invalid course ID'], 400);
}

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'student') {
    sendJSON(['success' => false, 'error' => 'Authentication required'], 401);
}

// Get user ID
$userId = $_SESSION['user_id'];

try {
    // Initialize payment service
    $paymentService = new PaymentService();
    
    // Get course details
    require_once '../models/Course.php';
    $courseModel = new Course();
    $courseDetails = $courseModel->getCourseById($courseId);
    
    if (!$courseDetails || $courseDetails['status'] !== 'published') {
        sendJSON(['success' => false, 'error' => 'Course not found or not available'], 404);
    }
    
    // Check if user is already enrolled
    require_once '../services/EnrollmentServiceNew.php';
    $enrollmentService = new EnrollmentServiceNew();
    
    if ($enrollmentService->isUserEnrolled($userId, $courseId)) {
        sendJSON(['success' => false, 'error' => 'Already enrolled in this course'], 409);
    }
    
    // Check for existing pending payment
    $existingPayment = $paymentService->getPendingPayment($userId, $courseId);
    if ($existingPayment) {
        // Return existing payment details
        $formData = $paymentService->signatureService->createPaymentFormData([
            'amount' => $existingPayment['amount'],
            'user_id' => $userId,
            'course_id' => $courseId,
            'transaction_uuid' => $existingPayment['transaction_uuid']
        ]);
        
        sendJSON([
            'success' => true,
            'payment_id' => $existingPayment['id'],
            'transaction_uuid' => $existingPayment['transaction_uuid'],
            'payment_form' => [
                'form_action' => $paymentService->signatureService->getPaymentUrl(),
                'form_data' => $formData
            ],
            'message' => 'Existing payment found. Please complete the payment.'
        ]);
    }
    
    // Create new payment
    $paymentData = [
        'user_id' => $userId,
        'course_id' => $courseId,
        'amount' => $courseDetails['price'],
        'payment_method' => 'esewa',
        'currency' => 'NPR',
        'status' => 'pending'
    ];
    
    $paymentResult = $paymentService->createPayment($paymentData);
    
    if (!$paymentResult['success']) {
        sendJSON(['success' => false, 'error' => $paymentResult['error']], 500);
    }
    
    // Store payment info in session for callback verification
    $_SESSION['esewa_payment'] = [
        'payment_id' => $paymentResult['payment_id'],
        'transaction_uuid' => $paymentResult['transaction_uuid'],
        'user_id' => $userId,
        'course_id' => $courseId,
        'course_title' => $courseDetails['title'],
        'amount' => $courseDetails['price'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Return payment form data
    sendJSON([
        'success' => true,
        'payment_id' => $paymentResult['payment_id'],
        'transaction_uuid' => $paymentResult['transaction_uuid'],
        'payment_form' => $paymentResult['payment_form'],
        'message' => 'Payment request created successfully'
    ]);
    
} catch (Exception $e) {
    error_log("eSewa payment request failed: " . $e->getMessage());
    sendJSON(['success' => false, 'error' => 'Payment request failed'], 500);
}
?>
