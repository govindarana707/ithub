<?php
require_once __DIR__ . '/../config/config.php';

// Get response from payment gateway
$courseId = intval($_GET['course_id'] ?? 0);
$status = $_GET['status'] ?? 'failed';
$message = $_GET['message'] ?? '';

// Handle different failure scenarios
$errorTitle = 'Payment Failed';
$errorMessage = 'An error occurred during payment. Please try again.';

switch ($status) {
    case 'canceled':
        $errorTitle = 'Payment Canceled';
        $errorMessage = 'You canceled the payment. You can try again to complete your enrollment.';
        break;
    case 'failed':
        $errorTitle = 'Payment Failed';
        $errorMessage = 'The payment could not be processed. Please check your account and try again.';
        break;
    case 'expired':
        $errorTitle = 'Payment Expired';
        $errorMessage = 'Your payment session expired. Please try again to complete your enrollment.';
        break;
    default:
        if (!empty($message)) {
            $errorMessage = $message;
        }
}

// Set error message in session
$_SESSION['error_message'] = $errorMessage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorTitle; ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="css/student-theme.css" rel="stylesheet">
    <style>
        .failure-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .failure-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            text-align: center;
        }
        
        .failure-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 40px;
        }
        
        .failure-icon {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .failure-icon i {
            font-size: 50px;
            color: #e74c3c;
        }
        
        .btn-retry {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 10px;
        }
        
        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="failure-container">
        <div class="failure-card">
            <!-- Failure Header -->
            <div class="failure-header">
                <div class="failure-icon">
                    <i class="fas fa-times"></i>
                </div>
                <h2 class="mb-2"><?php echo $errorTitle; ?></h2>
                <p class="mb-0">We couldn't complete your payment</p>
            </div>
            
            <!-- Failure Body -->
            <div class="p-4">
                <p class="text-muted"><?php echo $errorMessage; ?></p>
                
                <div class="info-box">
                    <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>What to do next:</h6>
                    <ul class="mb-0">
                        <li>Check your account balance</li>
                        <li>Verify your payment method is valid</li>
                        <li>Ensure you have sufficient funds</li>
                        <li>Try a different payment method</li>
                    </ul>
                </div>
                
                <?php if ($courseId > 0): ?>
                    <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                        <a href="billing.php?course_id=<?php echo $courseId; ?>" class="btn btn-retry text-white">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </a>
                        <a href="course-details.php?id=<?php echo $courseId; ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back to Course
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                        <a href="courses.php" class="btn btn-retry text-white">
                            <i class="fas fa-search me-2"></i>Browse Courses
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-muted">
                    <p class="mb-1">Need help? <a href="../contact.php">Contact Support</a></p>
                    <small>If the problem persists, please reach out to our support team.</small>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>