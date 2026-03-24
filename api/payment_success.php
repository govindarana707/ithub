<?php
require_once '../config/config.php';
require_once '../services/EnrollmentService.php';

// Get payment response from eSewa
$transactionId = $_GET['oid'] ?? '';
$amount = $_GET['amt'] ?? '';
$productId = $_GET['refId'] ?? '';

if (empty($transactionId) || empty($amount)) {
    // Redirect with error
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_failed&reason=missing_params');
    exit();
}

try {
    // Initialize enrollment service
    $enrollmentService = new EnrollmentService();
    
    // Process payment success
    $result = $enrollmentService->processPaymentSuccess($transactionId, [
        'amount' => $amount,
        'product_id' => $productId,
        'gateway_response' => $_GET
    ]);
    
    if ($result['success']) {
        // Redirect to success page
        header('Location: ' . BASE_URL . 'student/courses.php?success=enrollment_completed&course_id=' . ($result['course_id'] ?? ''));
        exit();
    } else {
        // Payment successful but enrollment failed
        header('Location: ' . BASE_URL . 'student/courses.php?error=enrollment_failed&payment_success=true&message=' . urlencode($result['error']));
        exit();
    }
    
} catch (Exception $e) {
    error_log('Payment success processing error: ' . $e->getMessage());
    
    // Redirect with error
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_processing_failed');
    exit();
}
?>
