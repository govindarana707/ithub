<?php
require_once '../config/config.php';
require_once '../models/Course.php';

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    redirect('courses.php');
}

// Validate user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Validate user role
if (getUserRole() !== 'student') {
    redirect('courses.php');
}

$courseId = intval($_GET['course_id']);
$userId = $_SESSION['user_id'];

// Get enrollment data from session or database
$enrollmentData = null;
$isTrial = isset($_GET['trial']) && $_GET['trial'] === '1';
$paymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : 'trial';

// Try to get from session storage via JavaScript or from database
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT e.*, c.title as course_title, c.thumbnail as course_thumbnail, 
           c.price as course_price, c.description as course_description,
           i.full_name as instructor_name
    FROM enrollments_new e
    JOIN courses_new c ON e.course_id = c.id
    LEFT JOIN users_new i ON c.instructor_id = i.id
    WHERE e.user_id = ? AND e.course_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 1
");
$stmt->bind_param("ii", $userId, $courseId);
$stmt->execute();
$result = $stmt->get_result();
$enrollmentData = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$enrollmentData) {
    $_SESSION['error_message'] = 'Enrollment not found';
    redirect('student/my-courses.php');
}

// Get course details if not already loaded
if (!isset($enrollmentData['course_title'])) {
    $course = new Course();
    $courseDetails = $course->getCourseById($courseId);
} else {
    $courseDetails = $enrollmentData;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Successful - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .success-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .success-icon i {
            font-size: 50px;
            color: #27ae60;
        }
        
        .course-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .next-steps {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        
        .step-item {
            display: flex;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .btn-start-learning {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 10px;
        }
        
        .btn-start-learning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .enrollment-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .badge-trial {
            background: #3498db;
            color: white;
        }
        
        .badge-paid {
            background: #27ae60;
            color: white;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f00;
            animation: fall linear forwards;
        }
        
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(720deg);
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/header.php'; ?>
    
    <div class="success-container">
        <div class="success-card">
            <!-- Success Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="mb-2">Enrollment Successful!</h2>
                <p class="mb-0">Welcome to your learning journey</p>
            </div>
            
            <!-- Success Body -->
            <div class="p-4">
                <!-- Enrollment Type Badge -->
                <?php if ($enrollmentData['enrollment_type'] === 'free_trial'): ?>
                    <span class="enrollment-badge badge-trial">
                        <i class="fas fa-gift me-1"></i> Free Trial
                    </span>
                    <p class="text-muted">
                        Your free trial ends on <strong><?php echo date('F j, Y', strtotime($enrollmentData['expires_at'])); ?></strong>
                    </p>
                <?php else: ?>
                    <span class="enrollment-badge badge-paid">
                        <i class="fas fa-crown me-1"></i> Premium Access
                    </span>
                <?php endif; ?>
                
                <!-- Course Details -->
                <div class="course-card">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <?php if ($enrollmentData['course_thumbnail']): ?>
                                <img src="<?php echo htmlspecialchars(resolveUploadUrl($enrollmentData['course_thumbnail'])); ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($enrollmentData['course_title']); ?>">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 100px;">
                                    <i class="fas fa-book fa-2x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-2"><?php echo htmlspecialchars($enrollmentData['course_title']); ?></h4>
                            <div class="text-muted">
                                <span class="me-3"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($enrollmentData['instructor_name']); ?></span>
                                <span><i class="fas fa-calendar me-1"></i> Enrolled on <?php echo date('F j, Y', strtotime($enrollmentData['enrolled_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Next Steps -->
                <div class="next-steps">
                    <h5 class="mb-3"><i class="fas fa-rocket me-2"></i>What's Next?</h5>
                    
                    <div class="step-item">
                        <div class="step-icon">1</div>
                        <div>
                            <strong>Start Learning</strong>
                            <p class="text-muted mb-0">Access all course content and begin your first lesson</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-icon">2</div>
                        <div>
                            <strong>Track Your Progress</strong>
                            <p class="text-muted mb-0">Monitor your learning journey in your dashboard</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-icon">3</div>
                        <div>
                            <strong>Complete the Course</strong>
                            <p class="text-muted mb-0">Finish all lessons and earn your certificate</p>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                    <a href="view-course.php?id=<?php echo $courseId; ?>" class="btn btn-start-learning text-white">
                        <i class="fas fa-play me-2"></i>Start Learning
                    </a>
                    <a href="my-courses.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-book-open me-2"></i>My Courses
                    </a>
                </div>
                
                <!-- Trial Expiry Warning -->
                <?php if ($enrollmentData['enrollment_type'] === 'free_trial'): ?>
                    <div class="alert alert-warning mt-4">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Important:</strong> Your free trial will expire on <?php echo date('F j, Y', strtotime($enrollmentData['expires_at'])); ?>. 
                        <a href="../billing.php?course_id=<?php echo $courseId; ?>">Upgrade to premium</a> to continue learning after your trial ends.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Additional Info -->
        <div class="text-center mt-4 text-muted">
            <p>Need help? <a href="../contact.php">Contact Support</a></p>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    
    <script>
        // Simple confetti effect
        function createConfetti() {
            const colors = ['#667eea', '#764ba2', '#27ae60', '#f39c12', '#e74c3c'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                confetti.style.opacity = Math.random();
                document.body.appendChild(confetti);
                
                // Remove after animation
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Run confetti on page load
        createConfetti();
    </script>
</body>
</html>