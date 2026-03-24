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
    <title>Khalti Payment - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 100px auto;
            text-align: center;
        }
        .khalti-logo {
            width: 120px;
            height: 60px;
            margin: 20px 0;
            background: linear-gradient(135deg, #4A2B8C, #7B5AA6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
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
        <div class="khalti-logo">
            K
        </div>
        <h3>Khalti Payment Integration</h3>
        <p>Coming Soon!</p>
        
        <div class="coming-soon">
            <h4>🚧 Under Development</h4>
            <p>We're working on integrating Khalti payment gateway to provide you with more payment options.</p>
            <p>In the meantime, you can use eSewa or start a free trial.</p>
            
            <div class="mt-4">
                <a href="../courses.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Courses
                </a>
            </div>
        </div>
    </div>
</body>
</html>
