<?php
require_once 'config/config.php';
require_once 'models/Course.php';
require_once 'includes/csrf.php';

if (!isset($_GET['id'])) {
    redirect('courses.php');
}

$courseId = intval($_GET['id']);
$course = new Course();
$courseDetails = $course->getCourseById($courseId);

if (!$courseDetails) {
    $_SESSION['error_message'] = 'Course not found';
    redirect('courses.php');
}

// Get course lessons
$conn = connectDB();
$stmt = $conn->prepare("SELECT * FROM lessons WHERE course_id = ? ORDER BY lesson_order");
$stmt->bind_param("i", $courseId);
$stmt->execute();
$lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get enrolled students count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
$stmt->bind_param("i", $courseId);
$stmt->execute();
$enrolledCount = $stmt->get_result()->fetch_assoc()['count'];

// Get instructor details
$instructorId = $courseDetails['instructor_id'];
$stmt = $conn->prepare("SELECT id, username, email, full_name, bio, profile_image FROM users WHERE id = ? AND role = 'instructor'");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$instructor = $stmt->get_result()->fetch_assoc();

// Get instructor's courses count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE instructor_id = ? AND status = 'published'");
$stmt->bind_param("i", $instructorId);
$stmt->execute();
$instructorCourseCount = $stmt->get_result()->fetch_assoc()['count'];

// Check if user is enrolled and get enrollment date
$isEnrolled = false;
$enrollmentDate = null;
if (isLoggedIn() && getUserRole() === 'student') {
    $studentId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, enrolled_at FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $enrollmentResult = $stmt->get_result();
    if ($enrollmentResult->num_rows > 0) {
        $isEnrolled = true;
        $enrollmentData = $enrollmentResult->fetch_assoc();
        $enrollmentDate = $enrollmentData['enrolled_at'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseDetails['title']); ?> - IT HUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <div class="container py-4">
        <!-- Course Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($courseDetails['title']); ?></li>
                    </ol>
                </nav>
                
                <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars($courseDetails['title']); ?></h1>
                
                <div class="mb-3">
                    <span class="badge bg-primary me-2"><?php echo htmlspecialchars($courseDetails['category_name']); ?></span>
                    <span class="badge bg-secondary me-2"><?php echo ucfirst($courseDetails['difficulty_level']); ?></span>
                    <span class="badge bg-info me-2"><?php echo $enrolledCount; ?> Students</span>
                </div>
                
                <div class="d-flex align-items-center text-muted mb-3">
                    <div class="me-4">
                        <i class="fas fa-user me-2"></i>
                        <strong>Instructor:</strong> <?php echo htmlspecialchars($courseDetails['instructor_name']); ?>
                    </div>
                    <div class="me-4">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Duration:</strong> <?php echo $courseDetails['duration_hours']; ?> hours
                    </div>
                    <div>
                        <i class="fas fa-tag me-2"></i>
                        <strong>Price:</strong> Rs<?php echo number_format($courseDetails['price'], 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <?php if ($courseDetails['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars(resolveUploadUrl($courseDetails['thumbnail'])); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($courseDetails['title']); ?>">
                <?php else: ?>
                    <img src="https://via.placeholder.com/400x300" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($courseDetails['title']); ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-md-8">
                <!-- Course Description -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Course Description</h4>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($courseDetails['description'])); ?></p>
                    </div>
                </div>

                <!-- Course Curriculum -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-list me-2"></i>Course Curriculum</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($lessons)): ?>
                            <p class="text-muted">No lessons available yet.</p>
                        <?php else: ?>
                            <div class="accordion" id="lessonAccordion">
                                <?php foreach ($lessons as $index => $lesson): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo $lesson['id']; ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $lesson['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                    <span>
                                                        <i class="fas fa-<?php echo $lesson['lesson_type'] === 'video' ? 'play-circle' : ($lesson['lesson_type'] === 'quiz' ? 'question-circle' : 'file-alt'); ?> me-2"></i>
                                                        Lesson <?php echo $index + 1; ?>: <?php echo htmlspecialchars($lesson['title']); ?>
                                                    </span>
                                                    <small class="text-muted"><?php echo $lesson['duration_minutes']; ?> min</small>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo $lesson['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#lessonAccordion">
                                            <div class="accordion-body">
                                                <?php if ($lesson['content']): ?>
                                                    <p><?php echo nl2br(htmlspecialchars($lesson['content'])); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if ($lesson['video_url']): ?>
                                                    <div class="ratio ratio-16x9 mb-3">
                                                        <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" title="Lesson Video" allowfullscreen></iframe>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($isEnrolled || getUserRole() === 'admin' || (getUserRole() === 'instructor' && $courseDetails['instructor_id'] == $_SESSION['user_id'])): ?>
                                                    <button class="btn btn-primary btn-sm">
                                                        <i class="fas fa-play me-1"></i>Start Lesson
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="fas fa-lock me-1"></i>Enroll to access
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requirements -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Requirements</h4>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li>Basic computer skills</li>
                            <li>Internet connection</li>
                            <li>Dedication to learn</li>
                            <li><?php echo ucfirst($courseDetails['difficulty_level']); ?> level knowledge recommended</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Instructor Info -->
                <?php if ($instructor): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Instructor</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($instructor['profile_image']): ?>
                            <img src="<?php echo htmlspecialchars(resolveUploadUrl($instructor['profile_image'])); ?>" class="rounded-circle mb-3" alt="<?php echo htmlspecialchars($instructor['full_name']); ?>" style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($instructor['full_name']); ?></h6>
                        <p class="text-muted small mb-2"><?php echo $instructorCourseCount; ?> Published Courses</p>
                        <?php if ($instructor['bio']): ?>
                            <p class="small text-muted"><?php echo nl2br(htmlspecialchars(substr($instructor['bio'], 0, 120))); ?>...</p>
                        <?php else: ?>
                            <p class="small text-muted">No bio available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Professional Enrollment Card -->
                <div class="card mb-4 sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <!-- Price Section -->
                        <div class="text-center mb-4">
                            <div class="price-display">
                                <span class="currency-symbol">Rs</span>
                                <span class="price-amount"><?php echo number_format($courseDetails['price'], 2); ?></span>
                            </div>
                            <?php if ($courseDetails['price'] > 0): ?>
                                <div class="payment-badges">
                                    <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i>Secure Payment</span>
                                    <span class="badge bg-info"><i class="fas fa-undo me-1"></i>30-Day Refund</span>
                                </div>
                            <?php else: ?>
                                <div class="free-badge">
                                    <span class="badge bg-warning text-dark"><i class="fas fa-gift me-1"></i>FREE Course</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Course Stats -->
                        <div class="course-stats mb-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="stat-item">
                                        <i class="fas fa-users text-primary"></i>
                                        <div class="stat-value"><?php echo $courseDetails['enrollment_count'] ?? 0; ?></div>
                                        <div class="stat-label">Students</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <i class="fas fa-clock text-warning"></i>
                                        <div class="stat-value"><?php echo $courseDetails['duration_hours']; ?>h</div>
                                        <div class="stat-label">Duration</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item">
                                        <i class="fas fa-signal text-success"></i>
                                        <div class="stat-value"><?php echo ucfirst($courseDetails['difficulty_level']); ?></div>
                                        <div class="stat-label">Level</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Enrollment Actions -->
                        <?php if (!isLoggedIn()): ?>
                            <div class="login-prompt mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Login Required</strong><br>
                                    <small>Please login or create an account to enroll in this course.</small>
                                </div>
                                <a href="login.php" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Enroll
                                </a>
                                <a href="register.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </a>
                            </div>
                        <?php elseif (getUserRole() === 'student'): ?>
                            <?php if ($isEnrolled): ?>
                                <div class="enrolled-status mb-3">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>You're Enrolled!</strong><br>
                                        <small>You enrolled on <?php echo $enrollmentDate ? date('M j, Y', strtotime($enrollmentDate)) : 'Recently'; ?></small>
                                    </div>
                                    <a href="student/view-course.php?id=<?php echo $courseId; ?>" class="btn btn-success btn-lg w-100 mb-2">
                                        <i class="fas fa-play me-2"></i>Continue Learning
                                    </a>
                                    <a href="student/dashboard.php" class="btn btn-outline-primary btn-lg w-100">
                                        <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                                    </a>
                                </div>
                            <?php else: ?>
                                <!-- Enrollment Requirements Check -->
                                <div class="enrollment-requirements mb-3">
                                    <?php
                                    $canEnroll = true;
                                    $requirements = [];
                                    
                                    // Check course capacity
                                    if (!empty($courseDetails['max_students']) && $courseDetails['max_students'] > 0) {
                                        $conn = connectDB();
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
                                        $stmt->bind_param("i", $courseId);
                                        $stmt->execute();
                                        $currentEnrollments = $stmt->get_result()->fetch_assoc()['count'];
                                        
                                        if ($currentEnrollments >= $courseDetails['max_students']) {
                                            $canEnroll = false;
                                            $requirements[] = '<i class="fas fa-exclamation-triangle text-warning"></i> Course is full';
                                        } else {
                                            $remaining = $courseDetails['max_students'] - $currentEnrollments;
                                            $requirements[] = '<i class="fas fa-check-circle text-success"></i> ' . $remaining . ' seats available';
                                        }
                                    }
                                    
                                    // Check prerequisites
                                    if (isset($courseDetails['prerequisites']) && !empty($courseDetails['prerequisites'])) {
                                        $prerequisites = json_decode($courseDetails['prerequisites'], true) ?: [];
                                        foreach ($prerequisites as $prereqId) {
                                            $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'completed'");
                                            $stmt->bind_param("ii", $studentId, $prereqId);
                                            $stmt->execute();
                                            $isCompleted = $stmt->get_result()->fetch_assoc()['completed'];
                                            
                                            if (!$isCompleted) {
                                                $canEnroll = false;
                                                $prereqCourse = $course->getCourseById($prereqId);
                                                $requirements[] = '<i class="fas fa-times-circle text-danger"></i> Requires: ' . htmlspecialchars($prereqCourse['title']);
                                            }
                                        }
                                    }
                                    
                                    if (empty($requirements)) {
                                        $requirements[] = '<i class="fas fa-check-circle text-success"></i> Ready to enroll';
                                    }
                                    ?>
                                    
                                    <div class="requirements-list">
                                        <?php foreach ($requirements as $requirement): ?>
                                            <div class="requirement-item mb-1"><?php echo $requirement; ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <button class="btn btn-primary btn-lg w-100 mb-2 enroll-course-btn" 
                                        data-course-id="<?php echo $courseId; ?>">
                                    <i class="fas fa-plus me-2"></i>Enroll Now
                                </button>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>" id="csrf_token">
                                
                                <?php if ($canEnroll && $courseDetails['price'] > 0): ?>
                                    <div class="payment-info mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-lock me-1"></i>Secure payment processing
                                            <i class="fas fa-credit-card me-1 ms-2"></i>Multiple payment methods
                                        </small>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif (getUserRole() === 'admin'): ?>
                            <div class="admin-actions mb-3">
                                <div class="alert alert-warning">
                                    <i class="fas fa-user-shield me-2"></i>
                                    <strong>Admin View</strong><br>
                                    <small>You're viewing this as an administrator.</small>
                                </div>
                                <a href="admin/edit-course.php?id=<?php echo $courseId; ?>" class="btn btn-warning btn-lg w-100 mb-2">
                                    <i class="fas fa-edit me-2"></i>Edit Course
                                </a>
                                <a href="admin/course-stats.php?id=<?php echo $courseId; ?>" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-chart-bar me-2"></i>View Statistics
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="access-denied mb-3">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Access Denied</strong><br>
                                    <small>Your account type cannot enroll in courses.</small>
                                </div>
                                <a href="../dashboard.php" class="btn btn-secondary btn-lg w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Go Back
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Options Modal -->
                <div class="modal fade" id="paymentOptionsModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-credit-card me-2"></i>Choose Payment Method
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="course-summary mb-4">
                                    <h6 class="text-muted mb-2">Course Summary</h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($courseDetails['title']); ?></strong>
                                            <br><small class="text-muted">Duration: <?php echo $courseDetails['duration_hours']; ?> hours</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="price-tag">Rs<?php echo number_format($courseDetails['price'], 2); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="payment-methods">
                                    <h6 class="mb-3">Select Payment Method</h6>
                                    
                                    <!-- Esewa Option -->
                                    <div class="payment-option mb-3" data-method="esewa">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="payment-icon me-3">
                                                        <img src="assets/images/esewa-logo.png" alt="Esewa" style="width: 60px; height: 40px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div style="display: none; width: 60px; height: 40px; background: linear-gradient(135deg, #00A651, #00D084); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">E</div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Esewa</h6>
                                                        <small class="text-muted">Pay with Nepal's most popular digital wallet</small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" value="esewa" id="esewa">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Khalti Option -->
                                    <div class="payment-option mb-3" data-method="khalti">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="payment-icon me-3">
                                                        <img src="assets/images/khalti-logo.png" alt="Khalti" style="width: 60px; height: 40px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                        <div style="display: none; width: 60px; height: 40px; background: linear-gradient(135deg, #4A2B8C, #7B5AA6); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">K</div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Khalti</h6>
                                                        <small class="text-muted">Fast and secure payments with Khalti</small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" value="khalti" id="khalti">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Trial Option -->
                                    <div class="payment-option mb-3" data-method="trial">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center">
                                                    <div class="payment-icon me-3">
                                                        <div style="width: 60px; height: 40px; background: linear-gradient(135deg, #FF6B6B, #FF8E53); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                            <i class="fas fa-play"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">Free Trial</h6>
                                                        <small class="text-muted">Start learning with 7-day free trial</small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method" value="trial" id="trial">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="selected-payment mt-3" id="selectedPaymentInfo" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <span id="paymentInfoText"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="proceedPayment" disabled>
                                    <i class="fas fa-arrow-right me-2"></i>Proceed to Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Course Features -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6><i class="fas fa-star me-2"></i>Course Features:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i><?php echo count($lessons); ?> Lessons</li>
                                    <li><i class="fas fa-check text-success me-2"></i><?php echo $courseDetails['duration_hours']; ?> Hours of content</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Certificate of completion</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Lifetime access</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>Mobile friendly</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Downloadable resources</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Discussion forum access</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Instructor support</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <style>
        .price-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .currency-symbol {
            font-size: 1.5rem;
            color: #7f8c8d;
            vertical-align: super;
        }
        
        .price-amount {
            color: #27ae60;
        }
        
        .payment-badges {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .free-badge {
            display: flex;
            justify-content: center;
        }
        
        .course-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
        }
        
        .stat-item {
            padding: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        
        .requirements-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.9rem;
        }
        
        .requirement-item {
            padding: 0.25rem 0;
        }
        
        .enrollment-success {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .enrollment-success:hover {
            background: linear-gradient(135deg, #229954, #27ae60);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .enrollment-loading {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
        }
        
        .enrollment-modal {
            max-width: 500px;
        }
        
        .success-animation {
            animation: successPulse 0.6s ease-in-out;
        }
        
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Payment Options Styles */
        .payment-option {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option:hover {
            transform: translateY(-2px);
        }
        
        .payment-option.selected {
            border: 2px solid #007bff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .payment-icon {
            width: 60px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .course-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .payment-methods .card {
            transition: all 0.3s ease;
        }
        
        .payment-methods .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
    </style>
    
    <script>
        $(document).ready(function() {
            // Handle enrollment - show payment options modal
            $('.enroll-course-btn').click(function() {
                var btn = $(this);
                var courseId = btn.data('course-id');
                
                // Store course ID for payment processing
                $('#proceedPayment').data('course-id', courseId);
                
                // Show payment options modal
                var modal = new bootstrap.Modal(document.getElementById('paymentOptionsModal'));
                modal.show();
            });
            
            // Handle payment method selection
            $('input[name="payment_method"]').change(function() {
                var selectedMethod = $(this).val();
                var proceedBtn = $('#proceedPayment');
                var paymentInfo = $('#selectedPaymentInfo');
                var paymentInfoText = $('#paymentInfoText');
                
                // Enable proceed button
                proceedBtn.prop('disabled', false);
                
                // Show payment-specific information
                paymentInfo.show();
                
                switch(selectedMethod) {
                    case 'esewa':
                        paymentInfoText.html('You will be redirected to Esewa to complete your payment securely.');
                        proceedBtn.html('<i class="fas fa-mobile-alt me-2"></i>Pay with Esewa');
                        break;
                    case 'khalti':
                        paymentInfoText.html('You will be redirected to Khalti to complete your payment securely.');
                        proceedBtn.html('<i class="fas fa-mobile-alt me-2"></i>Pay with Khalti');
                        break;
                    case 'trial':
                        paymentInfoText.html('Start your 7-day free trial. No payment required. You can upgrade anytime.');
                        proceedBtn.html('<i class="fas fa-play me-2"></i>Start Free Trial');
                        break;
                }
                
                // Highlight selected payment option
                $('.payment-option').removeClass('border-primary');
                $('.payment-option[data-method="' + selectedMethod + '"]').addClass('border-primary');
            });
            
            // Handle payment processing
            $('#proceedPayment').click(function() {
                var btn = $(this);
                var courseId = btn.data('course-id');
                var paymentMethod = $('input[name="payment_method"]:checked').val();
                var originalText = btn.html();
                
                // Show loading state
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                
                // Process based on payment method
                if (paymentMethod === 'trial') {
                    // Direct enrollment for trial
                    processEnrollment(courseId, 'trial', btn, originalText);
                } else {
                    // Redirect to payment gateway
                    processPaymentGateway(courseId, paymentMethod, btn, originalText);
                }
            });
            
            function processEnrollment(courseId, paymentMethod, btn, originalText) {
                $.ajax({
                    url: 'api/enroll_course.php',
                    type: 'POST',
                    data: { 
                        course_id: courseId,
                        payment_method: paymentMethod,
                        csrf_token: $('#csrf_token').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Close payment modal
                            bootstrap.Modal.getInstance(document.getElementById('paymentOptionsModal')).hide();
                            
                            // Show success modal
                            showEnrollmentSuccessModal(response);
                            
                            // Reload page after delay
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            handleEnrollmentError(btn, originalText, 'btn-primary', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Enrollment error:', xhr.responseText);
                        btn.prop('disabled', false)
                           .removeClass('btn-primary')
                           .addClass('btn-danger')
                           .html('<i class="fas fa-exclamation-triangle me-2"></i>Enrollment Failed');
                        
                        showAlert('Network error. Please check your connection and try again.', 'danger');
                        
                        setTimeout(function() {
                            btn.prop('disabled', false)
                               .removeClass('btn-danger')
                               .addClass('btn-primary')
                               .html(originalText);
                        }, 3000);
                    }
                });
            }
            
            function processPaymentGateway(courseId, paymentMethod, btn, originalText) {
                // Simulate payment gateway redirect
                setTimeout(function() {
                    // Show payment gateway redirect message
                    showAlert('Redirecting to ' + paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1) + '...', 'info');
                    
                    // Simulate successful payment (in real implementation, redirect to actual payment gateway)
                    setTimeout(function() {
                        processEnrollment(courseId, paymentMethod, btn, originalText);
                    }, 2000);
                }, 1000);
            }
            
            function showEnrollmentSuccessModal(response) {
                var modalHtml = `
                    <div class="modal fade" id="enrollmentSuccessModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered enrollment-modal">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-check-circle me-2"></i>Enrollment Successful!
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center py-4">
                                    <div class="success-icon mb-3">
                                        <i class="fas fa-graduation-cap fa-4x text-success"></i>
                                    </div>
                                    <h4 class="mb-3">Welcome to ${response.course_title}!</h4>
                                    <p class="text-muted mb-4">You've successfully enrolled in this course. Here's what you can do next:</p>
                                    <div class="next-steps text-start">
                                        ${response.next_steps.map(step => `<div class="mb-2"><i class="fas fa-arrow-right text-success me-2"></i>${step}</div>`).join('')}
                                    </div>
                                    <div class="enrollment-details mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>Enrollment ID:</strong> ${response.enrollment_id}<br>
                                            <strong>Enrolled:</strong> ${response.enrolled_at}<br>
                                            <strong>Price:</strong> Rs${parseFloat(response.course_price).toFixed(2)}
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="window.location.href='student/view-course.php?id=${courseId}'">
                                        <i class="fas fa-play me-2"></i>Start Learning
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if present
                $('#enrollmentSuccessModal').remove();
                
                // Add new modal to body
                $('body').append(modalHtml);
                
                // Show modal
                var modal = new bootstrap.Modal(document.getElementById('enrollmentSuccessModal'));
                modal.show();
                
                // Remove modal from DOM after hidden
                $('#enrollmentSuccessModal').on('hidden.bs.modal', function () {
                    $(this).remove();
                });
            }
            
            function handleEnrollmentError(btn, originalText, originalClass, response) {
                let errorMessage = response.message;
                let errorIcon = 'fa-exclamation-triangle';
                let buttonClass = 'btn-warning';
                
                // Handle specific error codes
                switch(response.code) {
                    case 'ALREADY_ENROLLED':
                        errorIcon = 'fa-info-circle';
                        buttonClass = 'btn-info';
                        break;
                    case 'COURSE_FULL':
                        errorIcon = 'fa-users';
                        buttonClass = 'btn-warning';
                        break;
                    case 'PREREQUISITE_MISSING':
                        errorIcon = 'fa-lock';
                        buttonClass = 'btn-warning';
                        break;
                    case 'MAX_ENROLLMENTS':
                        errorIcon = 'fa-exclamation-triangle';
                        buttonClass = 'btn-warning';
                        break;
                    case 'COURSE_UNAVAILABLE':
                        errorIcon = 'fa-ban';
                        buttonClass = 'btn-danger';
                        break;
                }
                
                btn.prop('disabled', false)
                   .removeClass('enrollment-loading')
                   .addClass(buttonClass)
                   .html(`<i class="fas ${errorIcon} me-2"></i>${errorMessage}`);
                
                showAlert(errorMessage, 'warning');
                
                // Reset button after delay
                setTimeout(function() {
                    btn.prop('disabled', false)
                       .removeClass(buttonClass)
                       .addClass(originalClass)
                       .html(originalText);
                }, 4000);
            }
            
            function showAlert(message, type) {
                // Remove existing alerts
                $('.alert').remove();
                
                var alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Insert alert at the top of the main content
                $('.sticky-top').first().before(alertHtml);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 5000);
            }
        });
    </script>
</body>
</html>
