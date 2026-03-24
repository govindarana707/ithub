<?php
require_once '../config/config.php';

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
        }
        .payment-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .payment-option {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 30px 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .payment-option:hover {
            border-color: #007bff;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        .payment-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .coming-soon {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 10px;
            margin: 50px 0;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h2>Choose Payment Method</h2>
        <p>Select your preferred payment method to enroll in this course.</p>
        
        <div class="course-summary mb-4 p-3 bg-light rounded">
            <h6>Course Details</h6>
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-start">
                    <strong><?php echo htmlspecialchars($courseDetails['title']); ?></strong>
                    <br><small class="text-muted">Duration: <?php echo $courseDetails['duration_hours'] ?? 0; ?> hours</small>
                </div>
                <div class="text-end">
                    <div class="price-tag">Rs<?php echo number_format($courseDetails['price'], 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="payment-options">
            <div class="payment-option" onclick="selectPayment('esewa')">
                <div class="payment-icon">🟢</div>
                <h5>eSewa</h5>
                <p>Nepal's most popular digital wallet</p>
            </div>
            
            <div class="payment-option" onclick="selectPayment('khalti')">
                <div class="payment-icon">🟣</div>
                <h5>Khalti</h5>
                <p>Fast and secure mobile payments</p>
            </div>
            
            <div class="payment-option" onclick="selectPayment('free')">
                <div class="payment-icon">🆓</div>
                <h5>Free Trial</h5>
                <p>Start with 7-day free trial</p>
            </div>
            
            <div class="payment-option" onclick="selectPayment('card')">
                <div class="payment-icon">💳</div>
                <h5>Credit/Debit Card</h5>
                <p>Pay with your bank card</p>
            </div>
            
            <div class="payment-option" onclick="selectPayment('bank')">
                <div class="payment-icon">🏦</div>
                <h5>Bank Transfer</h5>
                <p>Direct bank deposit</p>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="../courses.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Courses
            </a>
        </div>
    </div>

    <script>
        function selectPayment(method) {
            if (method === 'esewa') {
                window.location.href = 'esewa.php?course_id=<?php echo $courseId; ?>';
            } else if (method === 'khalti') {
                window.location.href = 'khalti.php?course_id=<?php echo $courseId; ?>';
            } else if (method === 'free') {
                // Redirect to free enrollment
                window.location.href = '../api/enroll_course.php?course_id=<?php echo $courseId; ?>&payment_type=free';
            } else {
                alert('This payment method is coming soon!');
            }
        }
    </script>
</body>
</html>
