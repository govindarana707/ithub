<?php
/**
 * Khalti Payment Success Handler
 * Official API: https://docs.khalti.com/khalti-epayment/
 * 
 * After Khalti payment, this handler:
 * 1. Receives the payment token from Khalti
 * 2. Verifies the payment with Khalti API
 * 3. Creates enrollment on successful verification
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../services/EnrollmentServiceNew.php';

// Get parameters from Khalti callback
$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
$transactionId = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';  // Payment token from Khalti

// Also check for other possible parameter names
if (empty($token)) {
    $token = isset($_GET['ConfirmationCode']) ? $_GET['ConfirmationCode'] : '';
}

if (empty($token)) {
    $token = isset($_POST['token']) ? $_POST['token'] : '';
}

// Validate required parameters
if (empty($paymentId) || empty($transactionId)) {
    $_SESSION['error_message'] = 'Invalid payment response';
    redirect('courses.php');
}

try {
    // Initialize services
    $paymentService = new PaymentService();
    $enrollmentService = new EnrollmentServiceNew();
    
    // Get payment details from database
    $payment = $paymentService->getPaymentById($paymentId);
    
    if (!$payment) {
        $_SESSION['error_message'] = 'Payment not found';
        redirect('courses.php');
    }
    
    // Verify payment with Khalti API
    $verificationResult = verifyKhaltiPayment($token, $payment['amount'], $transactionId);
    
    if (!$verificationResult['success']) {
        error_log("Khalti verification failed: " . $verificationResult['error']);
        $_SESSION['error_message'] = 'Payment verification failed. Please contact support.';
        redirect('student/payment-failed.php?course_id=' . $payment['course_id'] . '&status=verification_failed');
    }
    
    // Update payment status to completed
    $conn = connectDB();
    $stmt = $conn->prepare("UPDATE payments SET status = 'completed', gateway_transaction_id = ?, gateway_response = ?, updated_at = NOW() WHERE id = ?");
    $gatewayResponse = json_encode($verificationResult['data']);
    $stmt->bind_param("ssi", $token, $gatewayResponse, $paymentId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    // Create enrollment
    $enrollmentResult = $enrollmentService->enrollUserAfterPayment($payment['user_id'], $payment['course_id'], $paymentId);
    
    if ($enrollmentResult['success']) {
        // Log activity
        logActivity($payment['user_id'], 'paid_enrollment', "Enrolled in course ID: {$payment['course_id']} via Khalti");
        
        // Redirect to success page
        redirect('student/enrollment-success.php?course_id=' . $payment['course_id'] . '&payment=khalti');
    } else {
        $_SESSION['error_message'] = 'Enrollment failed: ' . $enrollmentResult['error'];
        redirect('student/payment-failed.php?course_id=' . $payment['course_id'] . '&status=enrollment_failed');
    }
    
} catch (Exception $e) {
    error_log("Khalti payment success handler error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while processing your payment. Please contact support.';
    redirect('courses.php');
}

/**
 * Verify payment with Khalti API
 * Official Documentation: https://docs.khalti.com/khalti-epayment/#verification
 */
function verifyKhaltiPayment($token, $amount, $transactionId) {
    // Get Khalti secret key from config
    $secretKey = getKhaltiSecretKey();
    
    if (empty($secretKey)) {
        // If no secret key configured, assume test mode with mock verification
        error_log("Khalti secret key not configured - using mock verification");
        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'amount' => $amount,
                'status' => 'Completed',
                'mock_verification' => true
            ]
        ];
    }
    
    // Call Khalti verification API
    $url = 'https://khalti.com/api/v2/payment/verify/';
    
    $postData = [
        'token' => $token,
        'amount' => $amount
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Key ' . $secretKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'cURL error: ' . $error];
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['success']) && $responseData['success'] === true) {
        return [
            'success' => true,
            'data' => $responseData
        ];
    }
    
    return [
        'success' => false,
        'error' => isset($responseData['message']) ? $responseData['message'] : 'Verification failed',
        'data' => $responseData
    ];
}

/**
 * Get Khalti secret key from configuration
 */
function getKhaltiSecretKey() {
    // Use live secret key from Khalti dashboard
    $secretKey = 'e7e919ef979c4c8cbcb0cf33f7e2f0db';
    
    // Allow override from database settings
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT setting_value FROM payment_settings WHERE setting_key = 'khalti_secret_key' LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['setting_value'])) {
                $secretKey = $row['setting_value'];
            }
        }
        $stmt->close();
    }
    $conn->close();
    
    return $secretKey;
}
?>