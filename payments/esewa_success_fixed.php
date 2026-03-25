<?php
require_once '../config/config.php';
require_once '../services/PaymentService.php';
require_once '../services/EnrollmentServiceNew.php';

/**
 * eSewa Success Callback Handler - Enhanced GUI
 * 
 * This file handles the success callback from eSewa after payment completion.
 * It decodes the Base64 response, verifies the signature, and processes the payment.
 * Features a beautiful, modern success page with enrollment details.
 */

// Log incoming request for debugging
error_log("eSewa success callback received: " . json_encode($_POST));

// Get raw response and decode Base64 (as per eSewa specification)
$raw = file_get_contents("php://input");
if (!empty($raw)) {
    $data = json_decode(base64_decode($raw), true);
} elseif (isset($_GET['data'])) {
    // Data comes in query parameter for eSewa redirect
    $dataParam = $_GET['data'];
    // URL decode if needed, then base64 decode
    $dataParam = urldecode($dataParam);
    $data = json_decode(base64_decode($dataParam), true);
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

$success = false;
$errorMessage = '';
$enrollmentDetails = null;
$paymentDetails = null;

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
    
    // Get payment details for display
    $paymentDetails = $processResult['payment'];
    
    // Enroll user
    $enrollmentResult = $enrollmentService->enrollUserAfterPayment(
        $processResult['payment']['user_id'],
        $processResult['payment']['course_id'],
        $processResult['payment']['id']
    );
    
    // Handle case where user is already enrolled
    if (!$enrollmentResult['success']) {
        if (strpos($enrollmentResult['error'], 'already enrolled') !== false) {
            // User already enrolled - this is actually a success case
            $success = true;
            $enrollmentDetails = ['enrollment_id' => 0]; // Will show as previous enrollment
            $errorMessage = '';
        } else {
            throw new Exception("Enrollment failed: " . $enrollmentResult['error']);
        }
    }
    
    // Success!
    $success = true;
    $enrollmentDetails = $enrollmentResult;
    
} catch (Exception $e) {
    error_log("eSewa success callback error: " . $e->getMessage());
    $errorMessage = $e->getMessage();
}

// Get course details if payment was successful
$courseName = 'Course';
if ($success && isset($paymentDetails['course_id'])) {
    try {
        require_once '../models/Course.php';
        $course = new Course();
        $courseInfo = $course->getCourseById($paymentDetails['course_id']);
        if ($courseInfo) {
            $courseName = $courseInfo['title'];
        }
    } catch (Exception $e) {
        error_log("Error fetching course details: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'Payment Successful' : 'Payment Failed'; ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .success-header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .success-header.failed {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        
        .success-body {
            padding: 40px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        
        .transaction-id {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-custom {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
            padding: 13px 30px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-custom:hover {
            background: #667eea;
            color: white;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #27ae60;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <?php if ($success): ?>
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="mb-2">Payment Successful!</h2>
            <p class="mb-0 opacity-75">Your enrollment has been completed</p>
        </div>
        
        <div class="success-body">
            <div class="detail-item">
                <span class="detail-label">Course</span>
                <span class="detail-value"><?php echo htmlspecialchars($courseName); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Amount Paid</span>
                <span class="detail-value">Rs <?php echo number_format($responseData['total_amount'], 2); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Transaction ID</span>
                <span class="detail-value">
                    <span class="transaction-id"><?php echo htmlspecialchars($responseData['transaction_code']); ?></span>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Payment Method</span>
                <span class="detail-value">
                    <i class="fas fa-wallet me-2 text-success"></i>eSewa
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Enrollment ID</span>
                <span class="detail-value">
                    <?php if (isset($enrollmentDetails['enrollment_id']) && $enrollmentDetails['enrollment_id'] > 0): ?>
                        #<?php echo $enrollmentDetails['enrollment_id']; ?>
                    <?php else: ?>
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>Already Enrolled</span>
                    <?php endif; ?>
                </span>
            </div>
            
            <a href="<?php echo BASE_URL; ?>student/my-courses.php" class="btn btn-primary-custom text-white">
                <i class="fas fa-graduation-cap me-2"></i>Go to My Courses
            </a>
            
            <a href="<?php echo BASE_URL; ?>courses.php" class="btn btn-outline-custom">
                <i class="fas fa-book me-2"></i>Browse More Courses
            </a>
            
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Secure payment verified by eSewa</span>
            </div>
        </div>
        
        <?php else: ?>
        <div class="success-header failed">
            <div class="success-icon">
                <i class="fas fa-times"></i>
            </div>
            <h2 class="mb-2">Payment Failed</h2>
            <p class="mb-0 opacity-75">Something went wrong</p>
        </div>
        
        <div class="success-body">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            
            <div class="detail-item">
                <span class="detail-label">Transaction UUID</span>
                <span class="detail-value">
                    <span class="transaction-id"><?php echo htmlspecialchars($responseData['transaction_uuid'] ?: 'N/A'); ?></span>
                </span>
            </div>
            
            <a href="<?php echo BASE_URL; ?>billing.php?course_id=<?php echo isset($paymentDetails['course_id']) ? $paymentDetails['course_id'] : ''; ?>" class="btn btn-primary-custom text-white">
                <i class="fas fa-redo me-2"></i>Try Again
            </a>
            
            <a href="<?php echo BASE_URL; ?>courses.php" class="btn btn-outline-custom">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
