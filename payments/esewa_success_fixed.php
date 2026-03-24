<?php
require_once '../config/config.php';
require_once '../services/PaymentService.php';
require_once '../services/EnrollmentServiceNew.php';

/**
 * eSewa Success Callback Handler (Fixed)
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
    
    // Check if signature is valid
    if (!$signatureValid) {
        throw new Exception("Invalid signature - payment verification failed");
    }
    
    // Check payment status
    if ($responseData['status'] !== 'COMPLETE') {
        throw new Exception("Payment not completed. Status: " . $responseData['status']);
    }
    
    // Verify payment with eSewa status API
    $statusResult = $paymentService->checkEsewaStatus($responseData['transaction_uuid'], $responseData['total_amount']);
    
    if (!$statusResult['success']) {
        throw new Exception("Payment verification failed: " . $statusResult['error']);
    }
    
    // Process payment completion
    $processResult = $paymentService->processEsewaSuccess($responseData);
    
    if (!$processResult['success']) {
        throw new Exception("Payment processing failed: " . $processResult['error']);
    }
    
    // Enroll user
    $enrollmentResult = $enrollmentService->enrollUserAfterPayment(
        $processResult['payment']['user_id'],
        $processResult['payment']['course_id'],
        'paid'
    );
    
    if (!$enrollmentResult['success']) {
        throw new Exception("Enrollment failed: " . $enrollmentResult['error']);
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment completed and user enrolled successfully',
        'transaction_uuid' => $responseData['transaction_uuid'],
        'enrollment_id' => $enrollmentResult['enrollment_id']
    ]);
    
} catch (Exception $e) {
    error_log("eSewa success callback error: " . $e->getMessage());
    
    // Error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'transaction_uuid' => $responseData['transaction_uuid']
    ]);
}
?>
