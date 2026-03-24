<?php
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../services/CourseService.php';
require_once __DIR__ . '/../includes/EsewaGateway.php';

class EnrollmentService {
    private $courseService;
    private $course;
    private $esewaGateway;
    private $cache = [];

    public function __construct() {
        $this->courseService = new CourseService();
        $this->course = new Course();
        $this->esewaGateway = new EsewaGateway();
    }

    /**
     * Process enrollment based on payment method
     */
    public function processEnrollment(int $userId, int $courseId, string $paymentMethod): array {
        try {
            // Validate user and course
            $validation = $this->validateEnrollmentRequest($userId, $courseId);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            $course = $this->courseService->getCourseById($courseId);
            
            // Route based on payment method and course price
            if ($paymentMethod === 'trial' || $course['price'] <= 0) {
                return $this->processFreeEnrollment($userId, $courseId, $paymentMethod);
            } else {
                return $this->initiatePaidEnrollment($userId, $courseId, $paymentMethod);
            }

        } catch (Exception $e) {
            error_log("EnrollmentService error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Enrollment processing failed'];
        }
    }

    /**
     * Process free enrollment directly
     */
    private function processFreeEnrollment(int $userId, int $courseId, string $paymentMethod): array {
        // Check prerequisites
        $prereqCheck = $this->courseService->validatePrerequisites($userId, $courseId);
        if (!$prereqCheck['valid']) {
            return [
                'success' => false, 
                'error' => 'Prerequisites not met: ' . implode(', ', $prereqCheck['missing'])
            ];
        }

        // Perform enrollment
        $result = $this->courseService->enrollUser($userId, $courseId, $paymentMethod);
        
        if ($result['success']) {
            // Create notification
            $this->createEnrollmentNotification($userId, $courseId, $paymentMethod);
            
            // Log activity
            logActivity($userId, 'free_enrollment', "Free enrollment in course ID: {$courseId}");
            
            return [
                'success' => true,
                'message' => '🎉 Enrollment successful! Welcome to your course.',
                'redirect_url' => BASE_URL . 'student/courses.php',
                'enrollment_type' => 'free'
            ];
        }

        return $result;
    }

    /**
     * Initiate paid enrollment process
     */
    private function initiatePaidEnrollment(int $userId, int $courseId, string $paymentMethod): array {
        $course = $this->courseService->getCourseById($courseId);
        
        // Check prerequisites before payment
        $prereqCheck = $this->courseService->validatePrerequisites($userId, $courseId);
        if (!$prereqCheck['valid']) {
            return [
                'success' => false, 
                'error' => 'Prerequisites not met: ' . implode(', ', $prereqCheck['missing'])
            ];
        }

        switch ($paymentMethod) {
            case 'esewa':
                return $this->initiateEsewaPayment($userId, $courseId, $course);
                
            case 'khalti':
                return $this->initiateKhaltiPayment($userId, $courseId, $course);
                
            default:
                return ['success' => false, 'error' => 'Unsupported payment method'];
        }
    }

    /**
     * Initiate eSewa payment
     */
    private function initiateEsewaPayment(int $userId, int $courseId, array $course): array {
        // Create payment record
        $transactionId = uniqid('ESEWA_', true);
        $paymentResult = $this->esewaGateway->createPaymentRecord(
            $userId, 
            $courseId, 
            $course['price'], 
            $transactionId, 
            'pending'
        );

        if (!$paymentResult['success']) {
            return ['success' => false, 'error' => 'Failed to create payment record'];
        }

        // Generate payment form
        $productId = 'COURSE_' . $courseId . '_' . time();
        $successUrl = BASE_URL . 'api/payment_success.php';
        $failureUrl = BASE_URL . 'api/payment_failure.php';
        
        $paymentForm = $this->esewaGateway->generatePaymentForm(
            $course['price'],
            $productId,
            $course['title'],
            $successUrl,
            $failureUrl
        );

        // Store payment info in session for callback verification
        $_SESSION['pending_payment'] = [
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'course_id' => $courseId,
            'payment_method' => 'esewa',
            'amount' => $course['price']
        ];

        return [
            'success' => true,
            'action' => 'redirect',
            'payment_form' => $paymentForm,
            'redirect_url' => $paymentForm['form_action'],
            'form_data' => $paymentForm['form_data'],
            'enrollment_type' => 'paid',
            'payment_method' => 'esewa'
        ];
    }

    /**
     * Initiate Khalti payment (placeholder)
     */
    private function initiateKhaltiPayment(int $userId, int $courseId, array $course): array {
        // TODO: Implement Khalti integration
        return [
            'success' => false, 
            'error' => 'Khalti payment not yet implemented'
        ];
    }

    /**
     * Process payment success callback
     */
    public function processPaymentSuccess(string $transactionId, array $responseData): array {
        try {
            // Verify payment
            $payment = $this->esewaGateway->getPaymentByTransactionId($transactionId);
            if (!$payment) {
                return ['success' => false, 'error' => 'Payment record not found'];
            }

            // Update payment status
            $updateResult = $this->esewaGateway->updatePaymentStatus($transactionId, 'completed', $responseData);
            if (!$updateResult['success']) {
                return ['success' => false, 'error' => 'Failed to update payment status'];
            }

            // Auto-enroll user
            $enrollmentResult = $this->courseService->enrollUser(
                $payment['student_id'], 
                $payment['course_id'], 
                $payment['payment_method']
            );

            if ($enrollmentResult['success']) {
                // Create notification
                $this->createEnrollmentNotification(
                    $payment['student_id'], 
                    $payment['course_id'], 
                    $payment['payment_method']
                );
                
                // Log activity
                logActivity($payment['student_id'], 'paid_enrollment', "Paid enrollment completed for course ID: {$payment['course_id']}");
                
                // Clear session
                unset($_SESSION['pending_payment']);
                
                return [
                    'success' => true,
                    'message' => '🎉 Payment successful! You are now enrolled.',
                    'redirect_url' => BASE_URL . 'student/courses.php',
                    'enrollment_id' => $enrollmentResult['enrollment_id']
                ];
            } else {
                // Payment successful but enrollment failed
                return [
                    'success' => false,
                    'error' => 'Payment received but enrollment failed. Please contact support.',
                    'payment_success' => true
                ];
            }

        } catch (Exception $e) {
            error_log("Payment success processing error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment processing failed'];
        }
    }

    /**
     * Process payment failure callback
     */
    public function processPaymentFailure(string $transactionId, array $responseData): array {
        try {
            // Update payment status
            $this->esewaGateway->updatePaymentStatus($transactionId, 'failed', $responseData);
            
            // Get payment details for logging
            $payment = $this->esewaGateway->getPaymentByTransactionId($transactionId);
            
            if ($payment) {
                // Log activity
                logActivity($payment['student_id'], 'payment_failed', "Payment failed for course ID: {$payment['course_id']}");
            }
            
            // Clear session
            unset($_SESSION['pending_payment']);
            
            return [
                'success' => true,
                'message' => 'Payment was not completed. You can try again.',
                'redirect_url' => BASE_URL . 'courses.php'
            ];

        } catch (Exception $e) {
            error_log("Payment failure processing error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Payment failure processing failed'];
        }
    }

    /**
     * Validate enrollment request
     */
    private function validateEnrollmentRequest(int $userId, int $courseId): array {
        if ($userId <= 0) {
            return ['valid' => false, 'error' => 'Invalid user ID'];
        }

        if ($courseId <= 0) {
            return ['valid' => false, 'error' => 'Invalid course ID'];
        }

        $course = $this->courseService->getCourseById($courseId);
        if (!$course) {
            return ['valid' => false, 'error' => 'Course not found'];
        }

        if ($course['status'] !== 'published') {
            return ['valid' => false, 'error' => 'Course not available'];
        }

        // Check if already enrolled
        if ($this->courseService->isUserEnrolled($userId, $courseId)) {
            return ['valid' => false, 'error' => 'Already enrolled'];
        }

        return ['valid' => true];
    }

    /**
     * Create enrollment notification
     */
    private function createEnrollmentNotification(int $userId, int $courseId, string $paymentMethod): void {
        try {
            $course = $this->courseService->getCourseById($courseId);
            if (!$course) return;

            $conn = connectDB();
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type) 
                VALUES (?, ?, ?, 'success', ?, 'course')
            ");
            
            $title = "🎉 Course Enrollment Successful!";
            $message = "Congratulations! You have successfully enrolled in '{$course['title']}' via " . ucfirst($paymentMethod) . ". Start your learning journey now!";
            
            $stmt->bind_param("issii", $userId, $title, $message, $courseId);
            $stmt->execute();
            
            $conn->close();
        } catch (Exception $e) {
            error_log("Failed to create enrollment notification: " . $e->getMessage());
        }
    }

    /**
     * Get enrollment status for user
     */
    public function getEnrollmentStatus(int $userId, int $courseId): array {
        $isEnrolled = $this->courseService->isUserEnrolled($userId, $courseId);
        $course = $this->courseService->getCourseById($courseId);
        
        return [
            'enrolled' => $isEnrolled,
            'course' => $course,
            'can_enroll' => !$isEnrolled && $course && $course['status'] === 'published'
        ];
    }

    /**
     * Get user's enrollments with progress
     */
    public function getUserEnrollments(int $userId): array {
        return $this->course->getEnrolledCourses($userId);
    }

    /**
     * Check for pending payments
     */
    public function getPendingPayment(int $userId): ?array {
        if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['user_id'] === $userId) {
            return $_SESSION['pending_payment'];
        }
        return null;
    }

    /**
     * Cancel pending payment
     */
    public function cancelPendingPayment(int $userId): void {
        if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['user_id'] === $userId) {
            $payment = $_SESSION['pending_payment'];
            
            // Update payment status to cancelled
            $this->esewaGateway->updatePaymentStatus($payment['transaction_id'], 'cancelled', [
                'reason' => 'User cancelled'
            ]);
            
            // Log activity
            logActivity($userId, 'payment_cancelled', "Payment cancelled for course ID: {$payment['course_id']}");
            
            // Clear session
            unset($_SESSION['pending_payment']);
        }
    }
}
?>
