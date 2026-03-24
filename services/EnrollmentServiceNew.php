<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/PaymentService.php';

/**
 * EnrollmentServiceNew - Enhanced enrollment management with payment integration
 * 
 * This service handles course enrollments with secure payment verification,
 * duplicate prevention, and comprehensive audit logging.
 */
class EnrollmentServiceNew {
    private $db;
    private $paymentService;
    private $enableLogging;
    
    public function __construct() {
        $this->db = new Database();
        $this->paymentService = new PaymentService();
        $this->loadSettings();
    }
    
    /**
     * Load service settings
     */
    private function loadSettings() {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key = 'enable_payment_logging'");
            $stmt->execute();
            $result = $stmt->get_result();
            
            $setting = $result->fetch_assoc();
            $this->enableLogging = ($setting['setting_value'] ?? 'true') === 'true';
            
        } catch (Exception $e) {
            $this->enableLogging = true;
            error_log("EnrollmentService: Failed to load settings - " . $e->getMessage());
        }
    }
    
    /**
     * Enroll user after successful payment verification
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param int $paymentId Payment ID
     * @return array Enrollment result
     */
    public function enrollUserAfterPayment($userId, $courseId, $paymentId) {
        try {
            $conn = $this->db->getConnection();
            
            // Start transaction
            $conn->begin_transaction();
            
            // Validate inputs
            $validation = $this->validateEnrollmentData($userId, $courseId, $paymentId);
            if (!$validation['valid']) {
                $conn->rollback();
                return ['success' => false, 'error' => implode(', ', $validation['errors'])];
            }
            
            // Check if already enrolled
            if ($this->isUserEnrolled($userId, $courseId)) {
                $conn->rollback();
                return ['success' => false, 'error' => 'User already enrolled in this course'];
            }
            
            // Get payment details
            $payment = $this->paymentService->getPaymentById($paymentId);
            if (!$payment || $payment['status'] !== 'completed') {
                $conn->rollback();
                return ['success' => false, 'error' => 'Invalid or incomplete payment'];
            }
            
            // Verify payment belongs to this user and course
            if ($payment['user_id'] != $userId || $payment['course_id'] != $courseId) {
                $conn->rollback();
                return ['success' => false, 'error' => 'Payment verification failed'];
            }
            
            // Create enrollment record
            $stmt = $conn->prepare("
                INSERT INTO enrollments_new (
                    user_id, course_id, payment_id, enrollment_type, status, 
                    progress_percentage, enrolled_at
                ) VALUES (?, ?, ?, 'paid', 'active', 0, NOW())
            ");
            
            $stmt->bind_param("iii", $userId, $courseId, $paymentId);
            
            if ($stmt->execute()) {
                $enrollmentId = $conn->insert_id;
                
                // Update course enrollment count (if courses table has enrollment_count field)
                $this->updateCourseEnrollmentCount($courseId);
                
                // Commit transaction
                $conn->commit();
                
                // Log enrollment
                $this->logEnrollmentActivity($enrollmentId, 'enrollment_created', 'User enrolled after payment verification', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'payment_id' => $paymentId,
                    'enrollment_type' => 'paid'
                ]);
                
                // Create notification
                $this->createEnrollmentNotification($userId, $courseId, 'paid');
                
                return [
                    'success' => true,
                    'enrollment_id' => $enrollmentId,
                    'message' => 'Successfully enrolled in course',
                    'enrollment_type' => 'paid'
                ];
                
            } else {
                $conn->rollback();
                throw new Exception("Failed to create enrollment: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            if (isset($conn) && $conn->ping()) {
                $conn->rollback();
            }
            error_log("EnrollmentService: Enrollment failed - " . $e->getMessage());
            return ['success' => false, 'error' => 'Enrollment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enroll user for free trial or complimentary access
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param string $enrollmentType Enrollment type (free_trial, complimentary)
     * @return array Enrollment result
     */
    public function enrollUserFree($userId, $courseId, $enrollmentType = 'free_trial') {
        try {
            $conn = $this->db->getConnection();
            
            // Validate inputs
            $validation = $this->validateFreeEnrollmentData($userId, $courseId);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => implode(', ', $validation['errors'])];
            }
            
            // Check if already enrolled
            if ($this->isUserEnrolled($userId, $courseId)) {
                return ['success' => false, 'error' => 'User already enrolled in this course'];
            }
            
            // Create enrollment record
            $stmt = $conn->prepare("
                INSERT INTO enrollments_new (
                    user_id, course_id, enrollment_type, status, 
                    progress_percentage, enrolled_at, expires_at
                ) VALUES (?, ?, ?, 'active', 0, NOW(), ?)
            ");
            
            // Set expiration for free trials (30 days)
            $expiresAt = null;
            if ($enrollmentType === 'free_trial') {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
            
            $stmt->bind_param("iiss", $userId, $courseId, $enrollmentType, $expiresAt);
            
            if ($stmt->execute()) {
                $enrollmentId = $conn->insert_id;
                
                // Update course enrollment count
                $this->updateCourseEnrollmentCount($courseId);
                
                // Log enrollment
                $this->logEnrollmentActivity($enrollmentId, 'free_enrollment_created', 'User enrolled for free', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'enrollment_type' => $enrollmentType
                ]);
                
                // Create notification
                $this->createEnrollmentNotification($userId, $courseId, $enrollmentType);
                
                return [
                    'success' => true,
                    'enrollment_id' => $enrollmentId,
                    'message' => 'Successfully enrolled in course',
                    'enrollment_type' => $enrollmentType
                ];
                
            } else {
                throw new Exception("Failed to create free enrollment: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Free enrollment failed - " . $e->getMessage());
            return ['success' => false, 'error' => 'Enrollment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if user is enrolled in course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if enrolled
     */
    public function isUserEnrolled($userId, $courseId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT id FROM enrollments_new 
                WHERE user_id = ? AND course_id = ? AND status = 'active'
                LIMIT 1
            ");
            $stmt->bind_param("ii", $userId, $courseId);
            $stmt->execute();
            
            return $stmt->get_result()->num_rows > 0;
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to check enrollment - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user enrollment details
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array|null Enrollment details or null
     */
    public function getUserEnrollment($userId, $courseId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT e.*, c.title as course_title, c.thumbnail as course_thumbnail,
                       p.amount as payment_amount, p.payment_method
                FROM enrollments_new e
                LEFT JOIN courses_new c ON e.course_id = c.id
                LEFT JOIN payments p ON e.payment_id = p.id
                WHERE e.user_id = ? AND e.course_id = ?
                ORDER BY e.enrolled_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("ii", $userId, $courseId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to get enrollment - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all enrollments for a user
     * 
     * @param int $userId User ID
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array User enrollments
     */
    public function getUserEnrollments($userId, $limit = 20, $offset = 0) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT e.*, c.title as course_title, c.description as course_description,
                       c.thumbnail as course_thumbnail, c.instructor_id,
                       u.full_name as instructor_name,
                       p.amount as payment_amount, p.payment_method
                FROM enrollments_new e
                JOIN courses_new c ON e.course_id = c.id
                LEFT JOIN users_new u ON c.instructor_id = u.id
                LEFT JOIN payments p ON e.payment_id = p.id
                WHERE e.user_id = ? AND e.status = 'active'
                ORDER BY e.enrolled_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("iii", $userId, $limit, $offset);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to get user enrollments - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update enrollment progress
     * 
     * @param int $enrollmentId Enrollment ID
     * @param float $progress Progress percentage (0-100)
     * @return bool Update success
     */
    public function updateProgress($enrollmentId, $progress) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE enrollments_new 
                SET progress_percentage = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("di", $progress, $enrollmentId);
            
            $success = $stmt->execute();
            
            if ($success && $progress >= 100) {
                // Mark as completed
                $this->markAsCompleted($enrollmentId);
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to update progress - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark enrollment as completed
     * 
     * @param int $enrollmentId Enrollment ID
     * @return bool Update success
     */
    public function markAsCompleted($enrollmentId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE enrollments_new 
                SET status = 'completed', completed_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $enrollmentId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to mark as completed - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate enrollment data
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param int $paymentId Payment ID
     * @return array Validation result
     */
    private function validateEnrollmentData($userId, $courseId, $paymentId) {
        $errors = [];
        
        if (!is_numeric($userId) || $userId <= 0) {
            $errors[] = "Invalid user ID";
        }
        
        if (!is_numeric($courseId) || $courseId <= 0) {
            $errors[] = "Invalid course ID";
        }
        
        if (!is_numeric($paymentId) || $paymentId <= 0) {
            $errors[] = "Invalid payment ID";
        }
        
        // Check if course exists and is published
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("SELECT id, status FROM courses_new WHERE id = ?");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $course = $stmt->get_result()->fetch_assoc();
            
            if (!$course) {
                $errors[] = "Course not found";
            } elseif ($course['status'] !== 'published') {
                $errors[] = "Course not available for enrollment";
            }
        } catch (Exception $e) {
            $errors[] = "Failed to validate course";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate free enrollment data
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Validation result
     */
    private function validateFreeEnrollmentData($userId, $courseId) {
        $errors = [];
        
        if (!is_numeric($userId) || $userId <= 0) {
            $errors[] = "Invalid user ID";
        }
        
        if (!is_numeric($courseId) || $courseId <= 0) {
            $errors[] = "Invalid course ID";
        }
        
        // Check if course exists and is published
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("SELECT id, status, price FROM courses_new WHERE id = ?");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $course = $stmt->get_result()->fetch_assoc();
            
            if (!$course) {
                $errors[] = "Course not found";
            } elseif ($course['status'] !== 'published') {
                $errors[] = "Course not available for enrollment";
            }
        } catch (Exception $e) {
            $errors[] = "Failed to validate course";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Update course enrollment count
     * 
     * @param int $courseId Course ID
     */
    private function updateCourseEnrollmentCount($courseId) {
        try {
            $conn = $this->db->getConnection();
            
            // This would work if courses table has enrollment_count field
            // For now, we'll skip this as it depends on the exact table structure
            // $stmt = $conn->prepare("UPDATE courses_new SET enrollment_count = enrollment_count + 1 WHERE id = ?");
            // $stmt->bind_param("i", $courseId);
            // $stmt->execute();
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to update enrollment count - " . $e->getMessage());
        }
    }
    
    /**
     * Create enrollment notification
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param string $enrollmentType Enrollment type
     */
    private function createEnrollmentNotification($userId, $courseId, $enrollmentType) {
        try {
            $conn = $this->db->getConnection();
            
            // Get course details
            $stmt = $conn->prepare("SELECT title FROM courses_new WHERE id = ?");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $course = $stmt->get_result()->fetch_assoc();
            
            if (!$course) {
                return;
            }
            
            // Create notification (if notifications table exists)
            $title = "🎉 Course Enrollment Successful!";
            $message = "Congratulations! You have successfully enrolled in '{$course['title']}'";
            
            if ($enrollmentType === 'paid') {
                $message .= " via paid enrollment";
            } elseif ($enrollmentType === 'free_trial') {
                $message .= " with a free trial (expires in 30 days)";
            } else {
                $message .= " with complimentary access";
            }
            
            $message .= ". Start your learning journey now!";
            
            // This assumes notifications table exists
            $notificationStmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type, created_at)
                VALUES (?, ?, ?, 'success', ?, 'course', NOW())
            ");
            
            if ($notificationStmt) {
                $notificationStmt->bind_param("issii", $userId, $title, $message, $courseId);
                $notificationStmt->execute();
            }
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Failed to create notification - " . $e->getMessage());
        }
    }
    
    /**
     * Log enrollment activity
     * 
     * @param int $enrollmentId Enrollment ID
     * @param string $activity Activity type
     * @param string $description Activity description
     * @param array $data Additional data
     */
    private function logEnrollmentActivity($enrollmentId, $activity, $description, $data = []) {
        if (!$this->enableLogging) {
            return;
        }
        
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO payment_verification_logs 
                (payment_id, verification_type, status, request_data, response_data, ip_address, user_agent)
                VALUES (?, ?, 'success', ?, ?, ?, ?)
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $requestData = json_encode(['activity' => $activity, 'description' => $description]);
            $responseData = json_encode($data);
            
            // We use payment_id field to store enrollment_id for logging purposes
            $stmt->bind_param("isssss", $enrollmentId, $activity, $requestData, $responseData, $ipAddress, $userAgent);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Activity logging failed - " . $e->getMessage());
        }
    }
    
    /**
     * Get enrollment statistics
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @return array Enrollment statistics
     */
    public function getEnrollmentStatistics($dateFrom = null, $dateTo = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "
                SELECT 
                    enrollment_type,
                    status,
                    COUNT(*) as count,
                    AVG(progress_percentage) as avg_progress,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
                FROM enrollments_new
                WHERE 1=1
            ";
            
            $params = [];
            $types = "";
            
            if ($dateFrom) {
                $sql .= " AND DATE(enrolled_at) >= ?";
                $params[] = $dateFrom;
                $types .= "s";
            }
            
            if ($dateTo) {
                $sql .= " AND DATE(enrolled_at) <= ?";
                $params[] = $dateTo;
                $types .= "s";
            }
            
            $sql .= " GROUP BY enrollment_type, status ORDER BY enrolled_at DESC";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("EnrollmentService: Statistics failed - " . $e->getMessage());
            return [];
        }
    }
}
?>
