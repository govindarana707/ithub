<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PaymentService.php';

// Log incoming request for debugging
error_log("eSewa failure callback received: " . json_encode($_POST));

// Get response data from eSewa
$responseData = [
    'transaction_uuid' => $_POST['transaction_uuid'] ?? '',
    'total_amount' => $_POST['total_amount'] ?? '',
    'status' => $_POST['status'] ?? '',
    'signature' => $_POST['signature'] ?? '',
    'product_code' => $_POST['product_code'] ?? ''
];

if (empty($responseData['transaction_uuid'])) {
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_cancelled');
    exit();
}

try {
    // Initialize payment service
    $paymentService = new PaymentService();
    
    // Get payment details
    $payment = $paymentService->getPaymentByTransactionUuid($responseData['transaction_uuid']);
    
    if ($payment) {
        // Update payment status to failed
        $paymentService->updatePaymentStatus($payment['id'], 'failed', 'Payment cancelled or failed by user', [
            'gateway_response' => json_encode($responseData)
        ]);
        
        // Log activity
        logActivity($payment['user_id'], 'payment_failed', "eSewa payment failed for course ID: {$payment['course_id']}");
        
        $courseId = $payment['course_id'];
    } else {
        $courseId = '';
    }

    // Redirect with error message
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_cancelled&course_id=' . $courseId);
    exit();
    
} catch (Exception $e) {
    error_log("eSewa failure callback error: " . $e->getMessage());
    header('Location: ' . BASE_URL . 'student/courses.php?error=payment_processing_failed');
    exit();
}
?>
