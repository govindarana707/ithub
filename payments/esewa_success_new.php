<?php
require_once '../config/config.php';
require_once '../services/PaymentService.php';
require_once '../services/EnrollmentServiceNew.php';

/**
 * eSewa Success Callback Handler
 * 
 * This file handles the success callback from eSewa after payment completion.
 * It decodes the Base64 response, verifies the signature, and processes the payment.
 */

// Log incoming request for debugging
error_log("eSewa success callback received: " . json_encode($_POST));

// Get raw response and decode Base64 (as per eSewa specification)
$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $data = json_decode(base64_decode($raw), true);
} else {
    // Fallback to POST data if no raw input
    $data = $_POST;
}

error_log("eSewa decoded data: " . json_encode($data));

// Get response data from eSewa
$responseData = [
    'transaction_uuid' => $data['transaction_uuid'] ?? '',
    'total_amount' => $data['total_amount'] ?? '',
    'status' => $data['status'] ?? '',
    'signature' => $data['signature'] ?? '',
    'product_code' => $data['product_code'] ?? '',
    'transaction_code' => $data['transaction_code'] ?? '',
    'signed_field_names' => $data['signed_field_names'] ?? 'total_amount,transaction_uuid,product_code'
];

// Verify signature
$secretKey = '8gBm/:&EnhH.1/q';
$signedFields = explode(',', $responseData['signed_field_names']);

$signatureData = [];
foreach ($signedFields as $field) {
    if (isset($data[$field])) {
        $signatureData[] = $field . '=' . $data[$field];
    }
}

$message = implode(',', $signatureData);
$generatedSignature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

$signatureValid = ($generatedSignature === $responseData['signature']);

error_log("eSewa signature verification: " . ($signatureValid ? 'VALID' : 'INVALID'));
error_log("Expected signature: " . $generatedSignature);
error_log("Received signature: " . $responseData['signature']);

try {
    // Initialize services
    $paymentService = new PaymentService();
    $enrollmentService = new EnrollmentServiceNew();
    
    // Verify payment with eSewa
    $verificationResult = $paymentService->verifyEsewaPayment($responseData);
    
    if (!$verificationResult['success']) {
        error_log("eSewa payment verification failed: " . $verificationResult['error']);
        
        // Redirect to failure page with error
        header('Location: ../payments/esewa_failure_new.php?error=' . urlencode($verificationResult['error']));
        exit();
    }
    
    // Get payment details
    $payment = $paymentService->getPaymentByTransactionUuid($responseData['transaction_uuid']);
    
    if (!$payment) {
        error_log("Payment not found for transaction UUID: " . $responseData['transaction_uuid']);
        header('Location: ../payments/esewa_failure_new.php?error=' . urlencode('Payment record not found'));
        exit();
    }
    
    // Enroll user after successful payment
    $enrollmentResult = $enrollmentService->enrollUserAfterPayment(
        $payment['user_id'],
        $payment['course_id'],
        $payment['id']
    );
    
    if (!$enrollmentResult['success']) {
        error_log("Enrollment failed after payment: " . $enrollmentResult['error']);
        
        // Payment successful but enrollment failed - show warning page
        header('Location: ../payments/payment_warning.php?payment_success=true&enrollment_failed=true');
        exit();
    }
    
    // Clear session payment data
    unset($_SESSION['esewa_payment']);
    
    // Log successful enrollment
    logActivity($payment['user_id'], 'payment_success', "eSewa payment successful and enrollment completed for course ID: {$payment['course_id']}");
    
    // Redirect to success page
    header('Location: ../payments/payment_success.php?enrollment_id=' . $enrollmentResult['enrollment_id'] . '&course_id=' . $payment['course_id']);
    exit();
    
} catch (Exception $e) {
    error_log("eSewa success callback error: " . $e->getMessage());
    
    // Redirect to failure page
    header('Location: ../payments/esewa_failure_new.php?error=' . urlencode('Payment processing failed')));
    exit();
}
?>
