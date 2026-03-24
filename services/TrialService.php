<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/EnrollmentServiceNew.php';
require_once __DIR__ . '/NotificationService.php';

/**
 * TrialService - Comprehensive Free Trial Management System
 * 
 * Handles free trial enrollment, expiration tracking, notifications,
 * conversion analytics, and trial-to-paid conversion flows.
 */
class TrialService {
    private $db;
    private $enrollmentService;
    private $notificationService;
    private $trialDurationDays = 30;
    private $reminderDays = [7, 3, 1]; // Days before expiration to send reminders
    
    public function __construct() {
        $this->db = new Database();
        $this->enrollmentService = new EnrollmentServiceNew();
        $this->notificationService = new NotificationService();
        $this->loadSettings();
    }
    
    /**
     * Load trial settings from database
     */
    private function loadSettings() {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("SELECT setting_key, setting_value FROM payment_settings WHERE setting_key IN ('trial_duration_days', 'trial_reminder_days', 'enable_trial_notifications')");
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($setting = $result->fetch_assoc()) {
                switch ($setting['setting_key']) {
                    case 'trial_duration_days':
                        $this->trialDurationDays = intval($setting['setting_value']);
                        break;
                    case 'trial_reminder_days':
                        $this->reminderDays = json_decode($setting['setting_value'], true) ? json_decode($setting['setting_value'], true) : [7, 3, 1];
                        break;
                    case 'enable_trial_notifications':
                        $this->enableLogging = isset($setting['setting_value']) ? ($setting['setting_value'] == 'true') : true;
                        break;
                }
            }
            
        } catch (Exception $e) {
            error_log("TrialService: Failed to load settings - " . $e->getMessage());
        }
    }
    
    /**
     * Enroll user in free trial
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return array Trial enrollment result
     */
    public function enrollInTrial($userId, $courseId) {
        try {
            // Check if user is already enrolled
            if ($this->enrollmentService->isUserEnrolled($userId, $courseId)) {
                return ['success' => false, 'error' => 'User already enrolled in this course'];
            }
            
            // Check if user has active trial for this course
            if ($this->hasActiveTrial($userId, $courseId)) {
                return ['success' => false, 'error' => 'User already has an active trial for this course'];
            }
            
            // Check trial limits per user
            if (!$this->checkTrialLimits($userId)) {
                return ['success' => false, 'error' => 'Trial limit exceeded. You can only have 3 active trials at a time.'];
            }
            
            // Enroll in free trial
            $result = $this->enrollmentService->enrollUserFree($userId, $courseId, 'free_trial');
            
            if ($result['success']) {
                // Schedule trial expiration reminders
                $this->scheduleReminders($userId, $courseId, $result['enrollment_id']);
                
                // Log trial enrollment
                $this->logTrialActivity($userId, $courseId, 'trial_started', 'User enrolled in free trial');
                
                return [
                    'success' => true,
                    'enrollment_id' => $result['enrollment_id'],
                    'trial_duration' => $this->trialDurationDays,
                    'expires_at' => date('Y-m-d H:i:s', strtotime("+{$this->trialDurationDays} days")),
                    'message' => "🎉 Free trial activated! You have {$this->trialDurationDays} days of full access."
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("TrialService: Trial enrollment failed - " . $e->getMessage());
            return ['success' => false, 'error' => 'Trial enrollment failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check if user has active trial for course
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @return bool True if has active trial
     */
    public function hasActiveTrial($userId, $courseId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT id FROM enrollments_new 
                WHERE user_id = ? AND course_id = ? AND enrollment_type = 'free_trial' 
                AND status = 'active' AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->bind_param("ii", $userId, $courseId);
            $stmt->execute();
            
            return $stmt->get_result()->num_rows > 0;
            
        } catch (Exception $e) {
            error_log("TrialService: Failed to check active trial - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check user trial limits
     * 
     * @param int $userId User ID
     * @return bool True if within limits
     */
    private function checkTrialLimits($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT COUNT(*) as active_trials FROM enrollments_new 
                WHERE user_id = ? AND enrollment_type = 'free_trial' 
                AND status = 'active' AND expires_at > NOW()
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_assoc();
            return $result['active_trials'] < 3; // Max 3 active trials
            
        } catch (Exception $e) {
            error_log("TrialService: Failed to check trial limits - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's active trials
     * 
     * @param int $userId User ID
     * @return array Active trials
     */
    public function getUserActiveTrials($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT e.*, c.title as course_title, c.thumbnail as course_thumbnail,
                       c.price as course_price, i.full_name as instructor_name,
                       DATEDIFF(e.expires_at, NOW()) as days_remaining,
                       CASE 
                           WHEN DATEDIFF(e.expires_at, NOW()) <= 3 THEN 'expiring_soon'
                           WHEN DATEDIFF(e.expires_at, NOW()) <= 7 THEN 'expiring'
                           ELSE 'active'
                       END as trial_status
                FROM enrollments_new e
                JOIN courses_new c ON e.course_id = c.id
                LEFT JOIN users_new i ON c.instructor_id = i.id
                WHERE e.user_id = ? AND e.enrollment_type = 'free_trial' 
                AND e.status = 'active' AND e.expires_at > NOW()
                ORDER BY e.expires_at ASC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("TrialService: Failed to get user trials - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trial statistics
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @return array Trial statistics
     */
    public function getTrialStatistics($dateFrom = null, $dateTo = null) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "
                SELECT 
                    COUNT(*) as total_trials,
                    COUNT(CASE WHEN status = 'active' AND expires_at > NOW() THEN 1 END) as active_trials,
                    COUNT(CASE WHEN status = 'active' AND expires_at <= NOW() THEN 1 END) as expired_trials,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trials,
                    COUNT(CASE WHEN enrollment_type = 'paid' THEN 1 END) as converted_trials,
                    ROUND(COUNT(CASE WHEN enrollment_type = 'paid' THEN 1 END) * 100.0 / COUNT(*), 2) as conversion_rate,
                    AVG(DATEDIFF(expires_at, enrolled_at)) as avg_trial_duration,
                    AVG(progress_percentage) as avg_progress
                FROM enrollments_new 
                WHERE enrollment_type = 'free_trial'
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
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("TrialService: Statistics failed - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process trial expirations (cron job)
     * 
     * @return array Processing results
     */
    public function processTrialExpirations() {
        $processed = 0;
        $notifications = 0;
        
        try {
            $conn = $this->db->getConnection();
            
            // Get expired trials
            $stmt = $conn->prepare("
                SELECT id, user_id, course_id, enrolled_at 
                FROM enrollments_new 
                WHERE enrollment_type = 'free_trial' 
                AND status = 'active' 
                AND expires_at <= NOW()
                AND expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute();
            $expiredTrials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($expiredTrials as $trial) {
                // Update trial status
                $updateStmt = $conn->prepare("
                    UPDATE enrollments_new 
                    SET status = 'suspended', updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->bind_param("i", $trial['id']);
                $updateStmt->execute();
                
                // Send expiration notification
                $this->sendExpirationNotification($trial['user_id'], $trial['course_id']);
                
                // Log expiration
                $this->logTrialActivity($trial['user_id'], $trial['course_id'], 'trial_expired', 'Free trial expired');
                
                $processed++;
                $notifications++;
            }
            
            // Send reminder notifications
            $reminderSent = $this->sendTrialReminders();
            $notifications += $reminderSent;
            
        } catch (Exception $e) {
            error_log("TrialService: Expiration processing failed - " . $e->getMessage());
        }
        
        return [
            'processed' => $processed,
            'notifications_sent' => $notifications,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Send trial reminder notifications
     * 
     * @return int Number of reminders sent
     */
    private function sendTrialReminders() {
        $sent = 0;
        
        try {
            $conn = $this->db->getConnection();
            
            foreach ($this->reminderDays as $days) {
                $stmt = $conn->prepare("
                    SELECT e.user_id, e.course_id, e.expires_at, c.title as course_title
                    FROM enrollments_new e
                    JOIN courses_new c ON e.course_id = c.id
                    WHERE e.enrollment_type = 'free_trial' 
                    AND e.status = 'active'
                    AND DATEDIFF(e.expires_at, NOW()) = ?
                    AND e.expires_at > NOW()
                ");
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $trials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($trials as $trial) {
                    $this->sendReminderNotification($trial['user_id'], $trial['course_id'], $days, $trial['course_title']);
                    $sent++;
                }
            }
            
        } catch (Exception $e) {
            error_log("TrialService: Reminder sending failed - " . $e->getMessage());
        }
        
        return $sent;
    }
    
    /**
     * Convert trial to paid enrollment
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param int $paymentId Payment ID
     * @return array Conversion result
     */
    public function convertTrialToPaid($userId, $courseId, $paymentId) {
        try {
            $conn = $this->db->getConnection();
            
            // Start transaction
            $conn->begin_transaction();
            
            // Get existing trial enrollment
            $stmt = $conn->prepare("
                SELECT id, progress_percentage FROM enrollments_new 
                WHERE user_id = ? AND course_id = ? AND enrollment_type = 'free_trial'
                AND status = 'active'
                LIMIT 1
            ");
            $stmt->bind_param("ii", $userId, $courseId);
            $stmt->execute();
            $trial = $stmt->get_result()->fetch_assoc();
            
            if (!$trial) {
                $conn->rollback();
                return ['success' => false, 'error' => 'No active trial found for this course'];
            }
            
            // Create new paid enrollment
            $result = $this->enrollmentService->enrollUserAfterPayment($userId, $courseId, $paymentId);
            
            if ($result['success']) {
                // Transfer progress from trial
                if ($trial['progress_percentage'] > 0) {
                    $updateStmt = $conn->prepare("
                        UPDATE enrollments_new 
                        SET progress_percentage = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param("di", $trial['progress_percentage'], $result['enrollment_id']);
                    $updateStmt->execute();
                }
                
                // Cancel old trial
                $cancelStmt = $conn->prepare("
                    UPDATE enrollments_new 
                    SET status = 'cancelled', updated_at = NOW() 
                    WHERE id = ?
                ");
                $cancelStmt->bind_param("i", $trial['id']);
                $cancelStmt->execute();
                
                $conn->commit();
                
                // Log conversion
                $this->logTrialActivity($userId, $courseId, 'trial_converted', 'Trial converted to paid enrollment');
                
                // Send conversion notification
                $this->sendConversionNotification($userId, $courseId);
                
                return [
                    'success' => true,
                    'enrollment_id' => $result['enrollment_id'],
                    'message' => '🎉 Trial successfully converted to paid enrollment!'
                ];
            }
            
            $conn->rollback();
            return $result;
            
        } catch (Exception $e) {
            if (isset($conn) && $conn->ping()) {
                $conn->rollback();
            }
            error_log("TrialService: Trial conversion failed - " . $e->getMessage());
            return ['success' => false, 'error' => 'Trial conversion failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Schedule trial reminders
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param int $enrollmentId Enrollment ID
     */
    private function scheduleReminders($userId, $courseId, $enrollmentId) {
        // This could be implemented with a job queue system
        // For now, reminders are sent via the cron job process
        $this->logTrialActivity($userId, $courseId, 'reminders_scheduled', 'Trial reminders scheduled');
    }
    
    /**
     * Send expiration notification
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     */
    private function sendExpirationNotification($userId, $courseId) {
        $this->notificationService->createNotification(
            $userId,
            '⏰ Trial Expired',
            "Your free trial has expired. Upgrade to continue learning!",
            'warning',
            $courseId,
            'course'
        );
    }
    
    /**
     * Send reminder notification
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param int $days Days remaining
     * @param string $courseTitle Course title
     */
    private function sendReminderNotification($userId, $courseId, $days, $courseTitle) {
        $message = $days == 1 
            ? "Your free trial for '{$courseTitle}' expires tomorrow! Upgrade now to continue learning."
            : "Your free trial for '{$courseTitle}' expires in {$days} days. Upgrade to continue access.";
            
        $this->notificationService->createNotification(
            $userId,
            '⏰ Trial Expiring Soon',
            $message,
            'info',
            $courseId,
            'course'
        );
    }
    
    /**
     * Send conversion notification
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     */
    private function sendConversionNotification($userId, $courseId) {
        $this->notificationService->createNotification(
            $userId,
            '🎉 Trial Upgraded!',
            "Congratulations! Your trial has been upgraded to full access.",
            'success',
            $courseId,
            'course'
        );
    }
    
    /**
     * Log trial activity
     * 
     * @param int $userId User ID
     * @param int $courseId Course ID
     * @param string $activity Activity type
     * @param string $description Activity description
     */
    private function logTrialActivity($userId, $courseId, $activity, $description) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO admin_logs (user_id, action, details, created_at)
                VALUES (?, 'trial_activity', ?, NOW())
            ");
            
            $details = json_encode([
                'activity' => $activity,
                'course_id' => $courseId,
                'description' => $description
            ]);
            
            $stmt->bind_param("is", $userId, $details);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("TrialService: Activity logging failed - " . $e->getMessage());
        }
    }
    
    /**
     * Extend trial (admin function)
     * 
     * @param int $enrollmentId Enrollment ID
     * @param int $days Number of days to extend
     * @return array Extension result
     */
    public function extendTrial($enrollmentId, $days) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE enrollments_new 
                SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY),
                    updated_at = NOW()
                WHERE id = ? AND enrollment_type = 'free_trial'
            ");
            $stmt->bind_param("ii", $days, $enrollmentId);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => "Trial extended by {$days} days"];
            }
            
            return ['success' => false, 'error' => 'Failed to extend trial'];
            
        } catch (Exception $e) {
            error_log("TrialService: Trial extension failed - " . $e->getMessage());
            return ['success' => false, 'error' => 'Trial extension failed: ' . $e->getMessage()];
        }
    }
}
?>
