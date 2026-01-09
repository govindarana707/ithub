<?php
require_once '../config/config.php';
require_once '../includes/csrf.php';

error_log("Enrollment API called - Method: " . $_SERVER['REQUEST_METHOD'] . ", POST data: " . json_encode($_POST));

if (!isLoggedIn()) {
    error_log("Enrollment failed: User not logged in");
    sendJSON(['success' => false, 'message' => 'Please login to continue', 'code' => 'AUTH_REQUIRED']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method', 'code' => 'INVALID_METHOD']);
}

// Validate CSRF token - temporarily disabled for debugging
// $csrfToken = $_POST['csrf_token'] ?? '';
// error_log("CSRF token received: " . ($csrfToken ? 'present' : 'missing'));
// if (!validateCSRFToken($csrfToken)) {
//     error_log("Enrollment failed: CSRF validation failed");
//     sendJSON(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.', 'code' => 'CSRF_INVALID']);
// }

$courseId = intval($_POST['course_id'] ?? 0);
$paymentMethod = sanitize($_POST['payment_method'] ?? 'trial');
$studentId = $_SESSION['user_id'];

if (getUserRole() !== 'student') {
    sendJSON(['success' => false, 'message' => 'Only students can enroll in courses', 'code' => 'ACCESS_DENIED']);
}

if ($courseId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
}

require_once '../models/Course.php';
$course = new Course();

// Check if course exists and is published
$courseDetails = $course->getCourseById($courseId);
if (!$courseDetails) {
    sendJSON(['success' => false, 'message' => 'Course not found', 'code' => 'COURSE_NOT_FOUND']);
}

if ($courseDetails['status'] !== 'published') {
    error_log("Enrollment failed: Course not published, status: " . $courseDetails['status']);
    sendJSON(['success' => false, 'message' => 'This course is not available for enrollment', 'code' => 'COURSE_UNAVAILABLE']);
}

// Check if student has any enrollment restrictions
$conn = connectDB();
$stmt = $conn->prepare("SELECT COUNT(*) as enrolled_count FROM enrollments WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$enrollmentCount = $stmt->get_result()->fetch_assoc()['enrolled_count'];

// Optional: Limit maximum concurrent enrollments (e.g., 10 courses)
$maxConcurrentEnrollments = 10;
if ($enrollmentCount >= $maxConcurrentEnrollments) {
    sendJSON(['success' => false, 'message' => "You have reached the maximum limit of {$maxConcurrentEnrollments} concurrent enrollments", 'code' => 'MAX_ENROLLMENTS']);
}

// Check if already enrolled
$stmt = $conn->prepare("SELECT id, enrolled_at FROM enrollments WHERE student_id = ? AND course_id = ?");
$stmt->bind_param("ii", $studentId, $courseId);
$stmt->execute();
$existingEnrollment = $stmt->get_result()->fetch_assoc();

if ($existingEnrollment) {
    sendJSON(['success' => false, 'message' => 'You are already enrolled in this course', 'code' => 'ALREADY_ENROLLED', 'enrolled_at' => $existingEnrollment['enrolled_at']]);
}

// Check course capacity (if applicable)
if ($courseDetails['max_students'] > 0) {
    $stmt = $conn->prepare("SELECT COUNT(*) as current_enrollments FROM enrollments WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $currentEnrollments = $stmt->get_result()->fetch_assoc()['current_enrollments'];
    
    if ($currentEnrollments >= $courseDetails['max_students']) {
        sendJSON(['success' => false, 'message' => 'This course is full. No more seats available.', 'code' => 'COURSE_FULL']);
    }
}

// Check prerequisites (if any)
if (!empty($courseDetails['prerequisites'])) {
    $prerequisites = json_decode($courseDetails['prerequisites'], true) ?: [];
    foreach ($prerequisites as $prereqCourseId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'completed'");
        $stmt->bind_param("ii", $studentId, $prereqCourseId);
        $stmt->execute();
        $isCompleted = $stmt->get_result()->fetch_assoc()['completed'];
        
        if (!$isCompleted) {
            $prereqCourse = $course->getCourseById($prereqCourseId);
            sendJSON(['success' => false, 'message' => "You must complete '{$prereqCourse['title']}' before enrolling in this course", 'code' => 'PREREQUISITE_MISSING']);
        }
    }
}

// Enroll student with enhanced tracking
error_log("Calling enrollStudent for student $studentId, course $courseId");
$result = $course->enrollStudent($studentId, $courseId);
error_log("enrollStudent result: " . json_encode($result));

if ($result['success']) {
    // Get enrollment ID for tracking
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? ORDER BY enrolled_at DESC LIMIT 1");
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $enrollmentId = $stmt->get_result()->fetch_assoc()['id'];
    
    // Log detailed activity
    logActivity($studentId, 'enroll_course', "Enrolled in course: {$courseDetails['title']} (ID: {$courseId}) via {$paymentMethod}");
    
    // Create comprehensive notification
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type) VALUES (?, ?, ?, 'success', ?, 'course')");
    $title = "🎉 Course Enrollment Successful!";
    $message = "Congratulations! You have successfully enrolled in '{$courseDetails['title']}' via " . ucfirst($paymentMethod) . ". Start your learning journey now!";
    $stmt->bind_param("issii", $studentId, $title, $message, $courseId);
    $stmt->execute();
    
    // Send welcome email (if email system is configured)
    // sendWelcomeEmail($studentId, $courseDetails);
    
    // Update course enrollment statistics
    $stmt = $conn->prepare("UPDATE courses SET enrollment_count = enrollment_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    
    // Create enrollment record in analytics table (if exists)
    try {
        $stmt = $conn->prepare("INSERT INTO enrollment_analytics (student_id, course_id, enrolled_at, source, payment_method) VALUES (?, ?, NOW(), 'web', ?) ON DUPLICATE KEY UPDATE enrolled_at = NOW(), payment_method = VALUES(payment_method)");
        if ($stmt) {
            $stmt->bind_param("iis", $studentId, $courseId, $paymentMethod);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Analytics table may not exist, continue
        error_log("Analytics insert failed: " . $e->getMessage());
    }
    
    sendJSON([
        'success' => true, 
        'message' => '🎉 Enrollment successful! Welcome to your new course!',
        'enrollment_id' => $enrollmentId,
        'course_title' => $courseDetails['title'],
        'course_price' => $courseDetails['price'],
        'payment_method' => $paymentMethod,
        'enrolled_at' => date('Y-m-d H:i:s'),
        'next_steps' => [
            'Start with the first lesson',
            'Join the course discussion forum',
            'Track your progress in your dashboard'
        ]
    ]);
} else {
    // Log enrollment failure
    logActivity($studentId, 'enroll_course_failed', "Failed to enroll in course: {$courseDetails['title']} (ID: {$courseId}) - Error: {$result['error']}");
    
    sendJSON([
        'success' => false, 
        'message' => 'Enrollment failed. Please try again or contact support.',
        'error_code' => 'ENROLLMENT_FAILED',
        'technical_details' => $result['error'] ?? 'Unknown error'
    ]);
}

$conn->close();
?>
