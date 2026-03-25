<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/EnrollmentServiceNew.php';

// Log incoming request for debugging
error_log("eSewa success callback received: " . json_encode($_POST));
error_log("Response data: " . json_encode($responseData));

// Get response data from eSewa
// eSewa sends Base64 encoded response, need to decode first
$encodedResponse = $_POST['data'] ?? '';

if (empty($encodedResponse)) {
    // Fallback for direct POST data (for testing)
    $responseData = [
        'transaction_uuid' => $_POST['transaction_uuid'] ?? '',
        'total_amount' => $_POST['total_amount'] ?? '',
        'status' => $_POST['status'] ?? '',
        'signature' => $_POST['signature'] ?? '',
        'product_code' => $_POST['product_code'] ?? '',
        'transaction_code' => $_POST['transaction_code'] ?? '',
        'signed_field_names' => $_POST['signed_field_names'] ?? 'total_amount,transaction_uuid,product_code'
    ];
} else {
    // Decode Base64 response from eSewa
    $decodedResponse = base64_decode($encodedResponse);
    error_log("Decoded response: " . $decodedResponse);
    $responseData = json_decode($decodedResponse, true) ?: [];
    error_log("Parsed response data: " . json_encode($responseData));
}

if (empty($responseData['transaction_uuid']) || empty($responseData['total_amount'])) {
    header('Location: ' . BASE_URL . 'student/payment-failed.php?course_id=&status=payment_failed&reason=missing_params');
    exit();
}

try {
    // Initialize services
    $paymentService = new PaymentService();
    $enrollmentService = new EnrollmentServiceNew();
    
    // Verify payment with eSewa
    $verificationResult = $paymentService->verifyEsewaPayment($responseData);
    
    if (!$verificationResult['success']) {
        error_log("eSewa payment verification failed: " . $verificationResult['error']);
        header('Location: ' . BASE_URL . 'student/payment-failed.php?course_id=' . $payment['course_id'] . '&status=payment_verification_failed&reason=' . urlencode($verificationResult['error']));
        exit();
    }
    
    // Get payment details
    $payment = $paymentService->getPaymentByTransactionUuid($responseData['transaction_uuid']);
    
    if (!$payment) {
        error_log("Payment not found for transaction UUID: " . $responseData['transaction_uuid']);
        header('Location: ' . BASE_URL . 'student/payment-failed.php?course_id=&status=payment_not_found');
        exit();
    }
    
    // Enroll user after successful payment
    $enrollmentResult = $enrollmentService->enrollUserAfterPayment(
        $payment['user_id'],
        $payment['course_id'],
        $payment['id']
    );
    
    if ($enrollmentResult['success']) {
        // Log successful enrollment
        logActivity($payment['user_id'], 'payment_success', "eSewa payment successful and enrollment completed for course ID: {$payment['course_id']}");
        
        header('Location: ' . BASE_URL . 'student/enrollment-success.php?course_id=' . $payment['course_id'] . '&payment=esewa');
        exit();
    } else {
        // Payment successful but enrollment failed
        error_log("Enrollment failed after payment: " . $enrollmentResult['error']);
        logActivity($payment['user_id'], 'enrollment_failed_after_payment', "Payment successful but enrollment failed for course ID: {$payment['course_id']}");
        header('Location: ' . BASE_URL . 'student/payment-failed.php?course_id=' . $payment['course_id'] . '&status=enrollment_failed&payment_success=true&message=' . urlencode($enrollmentResult['error']));
        exit();
    }
    
} catch (Exception $e) {
    error_log("eSewa success callback error: " . $e->getMessage());
    header('Location: ' . BASE_URL . 'student/payment-failed.php?status=payment_processing_failed');
    exit();
}
?>
