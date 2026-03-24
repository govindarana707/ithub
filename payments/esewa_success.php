<?php
require_once '../config/config.php';
require_once '../includes/EsewaGateway.php';

// Get response from eSewa
$transactionId = $_GET['oid'] ?? '';
$amount = $_GET['amt'] ?? '';
$productId = $_GET['refId'] ?? '';

if (empty($transactionId) || empty($amount)) {
    header('Location: ../courses.php?error=payment_failed&reason=missing_params');
    exit();
}

// Initialize eSewa gateway
$esewa = new EsewaGateway();

// Verify payment
$verification = $esewa->verifyPayment($transactionId, $amount, $productId);

if ($verification['success']) {
    // Update payment status
    $esewa->updatePaymentStatus($transactionId, 'completed', $verification);
    
    // Get payment details
    $payment = $esewa->getPaymentByTransactionId($transactionId);
    
    if ($payment) {
        // Enroll student
        require_once '../models/Course.php';
        $course = new Course();
        
        $enrollmentResult = $course->enrollStudent($payment['student_id'], $payment['course_id']);
        
        if ($enrollmentResult['success']) {
            // Log activity
            logActivity($payment['student_id'], 'payment_completed', "eSewa payment completed for course ID: {$payment['course_id']}");
            
            // Create notification
            $conn = connectDB();
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type) VALUES (?, ?, ?, 'success', ?, 'course')");
            $title = "🎉 Payment Successful!";
            $message = "Your payment for '{$payment['course_title']}' has been successfully processed via eSewa. You are now enrolled!";
            $stmt->bind_param("issii", $payment['student_id'], $title, $message, $payment['course_id']);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            
            // Clear session
            unset($_SESSION['esewa_payment']);
            
            header('Location: ../student/courses.php?success=payment_completed&course_id=' . $payment['course_id']);
            exit();
        } else {
            // Payment successful but enrollment failed
            logActivity($payment['student_id'], 'enrollment_failed_after_payment', "Payment successful but enrollment failed for course ID: {$payment['course_id']}");
            header('Location: ../student/courses.php?error=enrollment_failed&payment_success=true');
            exit();
        }
    } else {
        header('Location: ../student/courses.php?error=payment_verification_failed');
        exit();
    }
} else {
    // Payment verification failed
    $esewa->updatePaymentStatus($transactionId, 'failed', $verification);
    
    header('Location: ../student/courses.php?error=payment_verification_failed&reason=' . urlencode($verification['error']));
    exit();
}
?>
