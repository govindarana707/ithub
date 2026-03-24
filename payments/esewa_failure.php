<?php
require_once '../config/config.php';
require_once '../includes/EsewaGateway.php';

// Get response from eSewa
$transactionId = $_GET['oid'] ?? '';
$amount = $_GET['amt'] ?? '';
$productId = $_GET['refId'] ?? '';

if (empty($transactionId)) {
    header('Location: ../courses.php?error=payment_cancelled');
    exit();
}

// Initialize eSewa gateway
$esewa = new EsewaGateway();

// Update payment status to failed
$esewa->updatePaymentStatus($transactionId, 'failed', [
    'error' => 'Payment cancelled or failed by user',
    'amount' => $amount,
    'product_id' => $productId
]);

// Get payment details for logging
$payment = $esewa->getPaymentByTransactionId($transactionId);

if ($payment) {
    // Log activity
    logActivity($payment['student_id'], 'payment_failed', "eSewa payment failed for course ID: {$payment['course_id']}");
}

// Clear session
unset($_SESSION['esewa_payment']);

// Redirect with error message
header('Location: ../courses.php?error=payment_cancelled&course_id=' . ($payment['course_id'] ?? ''));
exit();
?>
