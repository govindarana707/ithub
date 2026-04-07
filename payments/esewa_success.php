<?php
require_once '../config/config.php';
require_once '../includes/EsewaGateway.php';
require_once '../services/PaymentService.php';

// eSewa API v2 sends data as base64 encoded JSON in 'data' parameter
$dataParam = $_GET['data'] ?? '';

// If no data param, fall back to old format (oid, amt, refId)
if (empty($dataParam)) {
    $transactionId = $_GET['oid'] ?? '';
    $amount = $_GET['amt'] ?? '';
    $productId = $_GET['refId'] ?? '';
} else {
    // Decode base64 data from eSewa v2
    $decodedData = base64_decode($dataParam);
    $responseData = json_decode($decodedData, true);
    
    if (!$responseData) {
        header('Location: ../courses.php?error=payment_failed&reason=invalid_data');
        exit();
    }
    
    $transactionId = $responseData['transaction_uuid'] ?? '';
    $amount = $responseData['total_amount'] ?? '';
    $productId = $responseData['product_code'] ?? '';
}

if (empty($transactionId) || empty($amount)) {
    header('Location: ../courses.php?error=payment_failed&reason=missing_params');
    exit();
}

// Use PaymentService to verify and process
try {
    $paymentService = new PaymentService();
    
    // Get payment from database
    $payment = $paymentService->getPaymentByTransactionUuid($transactionId);
    
    if (!$payment) {
        header('Location: ../courses.php?error=payment_not_found');
        exit();
    }
    
    // Verify with eSewa API
    $verification = $paymentService->verifyEsewaPayment([
        'transaction_uuid' => $transactionId,
        'total_amount' => $amount
    ]);
    
    if ($verification['success']) {
        // Enroll student
        require_once '../models/Course.php';
        $course = new Course();
        
        $enrollmentResult = $course->enrollStudent($payment['user_id'], $payment['course_id']);
        
        if ($enrollmentResult['success']) {
            // Log activity
            logActivity($payment['user_id'], 'payment_completed', "eSewa payment completed for course ID: {$payment['course_id']}");
            
            header('Location: ../student/my-courses.php?success=payment_completed&course_id=' . $payment['course_id']);
            exit();
        } else {
            header('Location: ../student/my-courses.php?error=enrollment_failed&payment_success=true');
            exit();
        }
    } else {
        header('Location: ../courses.php?error=payment_verification_failed&reason=' . urlencode($verification['error']));
        exit();
    }
    
} catch (Exception $e) {
    error_log("eSewa success handling error: " . $e->getMessage());
    header('Location: ../courses.php?error=payment_processing_failed');
    exit();
}
?>
