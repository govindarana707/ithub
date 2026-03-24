<?php
require_once '../config/config.php';
require_once '../services/TrialService.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set request method for testing compatibility
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'POST'; // Default to POST for testing
}

error_log("Enrollment API called - Method: " . $_SERVER['REQUEST_METHOD'] . ", POST data: " . json_encode($_POST));

if (!isLoggedIn()) {
    error_log("Enrollment failed: User not logged in");
    sendJSON(['success' => false, 'message' => 'Please login to continue', 'code' => 'AUTH_REQUIRED']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Enrollment failed: Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    sendJSON(['success' => false, 'message' => 'Invalid request method', 'code' => 'INVALID_METHOD']);
}

$courseId = intval($_POST['course_id'] ?? 0);
$paymentMethod = sanitize($_POST['payment_method'] ?? 'free_trial');
$studentId = $_SESSION['user_id'];

error_log("Enrollment request - Course ID: $courseId, Payment Method: $paymentMethod, Student ID: $studentId");

if (getUserRole() !== 'student') {
    error_log("Enrollment failed: User role is not student: " . getUserRole());
    sendJSON(['success' => false, 'message' => 'Only students can enroll in courses', 'code' => 'ACCESS_DENIED']);
}

if ($courseId <= 0) {
    error_log("Enrollment failed: Invalid course ID: $courseId");
    sendJSON(['success' => false, 'message' => 'Invalid course ID', 'code' => 'INVALID_COURSE']);
}

try {
    require_once '../models/Course.php';
    $course = new Course();

    // Check if course exists and is published
    $courseDetails = $course->getCourseById($courseId);
    if (!$courseDetails) {
        error_log("Enrollment failed: Course not found with ID: $courseId");
        sendJSON(['success' => false, 'message' => 'Course not found', 'code' => 'COURSE_NOT_FOUND']);
    }

    if ($courseDetails['status'] !== 'published') {
        error_log("Enrollment failed: Course not published, status: " . $courseDetails['status']);
        sendJSON(['success' => false, 'message' => 'This course is not available for enrollment', 'code' => 'COURSE_UNAVAILABLE']);
    }

    // Initialize trial service
    $trialService = new TrialService();
    
    // Check if already enrolled
    if ($trialService->hasActiveTrial($studentId, $courseId)) {
        error_log("Enrollment failed: Already enrolled - Student: $studentId, Course: $courseId");
        sendJSON(['success' => false, 'message' => 'You are already enrolled in this course', 'code' => 'ALREADY_ENROLLED']);
    }

    // Process enrollment based on payment method
    if ($paymentMethod === 'free' || $paymentMethod === 'free_trial' || $courseDetails['price'] <= 0) {
        // Free trial enrollment
        $result = $trialService->enrollInTrial($studentId, $courseId);
        
        if ($result['success']) {
            // Log activity
            logActivity($studentId, 'free_enrollment', "Free enrollment in course: {$courseDetails['title']} (ID: {$courseId})");
            
            sendJSON([
                'success' => true, 
                'message' => $result['message'],
                'enrollment_id' => $result['enrollment_id'],
                'course_title' => $courseDetails['title'],
                'enrollment_type' => 'free_trial',
                'trial_duration' => $result['trial_duration'],
                'expires_at' => $result['expires_at'],
                'next_steps' => [
                    'Start with the first lesson',
                    'Track your progress in your dashboard',
                    'Upgrade before trial expires to keep access'
                ]
            ]);
        } else {
            error_log("Free enrollment failed - Student: $studentId, Course: $courseId, Error: " . $result['error']);
            sendJSON(['success' => false, 'message' => 'Enrollment failed: ' . $result['error'], 'code' => 'ENROLLMENT_FAILED']);
        }
        
    } else {
        // Paid enrollment - should go through payment flow
        error_log("Paid enrollment attempted without payment - Student: $studentId, Course: $courseId, Method: $paymentMethod");
        sendJSON([
            'success' => false, 
            'message' => 'Please complete payment through the payment gateway to enroll in this course.',
            'code' => 'PAYMENT_REQUIRED',
            'course_price' => $courseDetails['price'],
            'redirect_to_payment' => true
        ]);
    }

} catch (Exception $e) {
    error_log("Enrollment exception: " . $e->getMessage());
    sendJSON([
        'success' => false, 
        'message' => 'An unexpected error occurred. Please try again.',
        'error_code' => 'EXCEPTION',
        'technical_details' => $e->getMessage()
    ]);
}
?>
