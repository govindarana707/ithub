<?php
require_once '../config/config.php';
require_once '../services/EnrollmentService.php';

// Get payment response from eSewa
$transactionId = $_GET['oid'] ?? '';
$amount = $_GET['amt'] ?? '';
$productId = $_GET['refId'] ?? '';

if (empty($transactionId)) {
    // Redirect with error
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_cancelled');
    exit();
}

try {
    // Initialize enrollment service
    $enrollmentService = new EnrollmentService();
    
    // Process payment failure
    $result = $enrollmentService->processPaymentFailure($transactionId, [
        'amount' => $amount,
        'product_id' => $productId,
        'gateway_response' => $_GET
    ]);
    
    // Redirect to courses page with appropriate message
    if ($result['success']) {
        header('Location: ' . BASE_URL . 'courses.php?error=payment_cancelled&message=' . urlencode($result['message']));
    } else {
        header('Location: ' . BASE_URL . 'courses.php?error=payment_processing_failed');
    }
    exit();
    
} catch (Exception $e) {
    error_log('Payment failure processing error: ' . $e->getMessage());
    
    // Redirect with error
    header('Location: ' . BASE_URL . 'courses.php?error=payment_processing_failed');
    exit();
}
?>
