<?php
require_once '../config/config.php';
require_once '../includes/EsewaGateway.php';

// Get course ID from URL
$courseId = intval($_GET['course_id'] ?? 0);

if ($courseId <= 0) {
    header('Location: ../courses.php?error=invalid_course');
    exit();
}

// Check if user is logged in
if (!isLoggedIn() || getUserRole() !== 'student') {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Get course details
require_once '../models/Course.php';
$course = new Course();
$courseDetails = $course->getCourseById($courseId);

if (!$courseDetails || $courseDetails['status'] !== 'published') {
    header('Location: ../courses.php?error=course_not_found');
    exit();
}

// Initialize eSewa gateway
$esewa = new EsewaGateway();

// Generate payment form
$amount = $courseDetails['price'];
$productId = 'COURSE_' . $courseId . '_' . time();
$courseName = $courseDetails['title'];

$successUrl = BASE_URL . 'payments/esewa_success.php';
$failureUrl = BASE_URL . 'payments/esewa_failure.php';

$paymentForm = $esewa->generatePaymentForm($amount, $productId, $courseName, $successUrl, $failureUrl);

// Create payment record
$transactionId = $paymentForm['transaction_id'];
$studentId = $_SESSION['user_id'];

$paymentResult = $esewa->createPaymentRecord($studentId, $courseId, $amount, $transactionId);

if (!$paymentResult['success']) {
    header('Location: ../courses.php?error=payment_failed');
    exit();
}

// Store payment info in session for callback verification
$_SESSION['esewa_payment'] = [
    'transaction_id' => $transactionId,
    'student_id' => $studentId,
    'course_id' => $courseId,
    'course_title' => $courseName,
    'amount' => $amount,
    'created_at' => date('Y-m-d H:i:s')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing eSewa Payment - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
        }
        .esewa-logo {
            width: 120px;
            height: 60px;
            margin: 20px 0;
        }
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #00A651;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="loading-spinner"></div>
        <h3>Redirecting to eSewa...</h3>
        <p>Please wait while we redirect you to the secure payment gateway.</p>
        
        <form id="esewaForm" method="POST" action="<?php echo $paymentForm['form_action']; ?>">
            <?php foreach ($paymentForm['form_data'] as $key => $value): ?>
                <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        // Auto-submit form after a short delay
        setTimeout(function() {
            document.getElementById('esewaForm').submit();
        }, 2000);
    </script>
</body>
</html>
