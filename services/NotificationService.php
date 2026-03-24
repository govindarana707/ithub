<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Database.php';

/**
 * NotificationService - Centralized notification management
 * 
 * Handles user notifications for trials, payments, course updates, etc.
 */
class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Create notification
     * 
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type (info, success, warning, error)
     * @param int $relatedId Related entity ID
     * @param string $relatedType Related entity type (course, payment, etc.)
     * @return bool Success status
     */
    public function createNotification($userId, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, title, message, notification_type, related_id, related_type, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("isssis", $userId, $title, $message, $type, $relatedId, $relatedType);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to create notification - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     * 
     * @param int $userId User ID
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @param bool $unreadOnly Get only unread notifications
     * @return array Notifications
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0, $unreadOnly = false) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "
                SELECT n.*, 
                       CASE 
                           WHEN n.related_type = 'course' THEN c.title
                           WHEN n.related_type = 'payment' THEN CONCAT('Payment for course ', c.title)
                           ELSE NULL
                       END as related_title
                FROM notifications n
                LEFT JOIN courses_new c ON n.related_id = c.id AND n.related_type = 'course'
                WHERE n.user_id = ?
            ";
            
            $params = [$userId];
            $types = "i";
            
            if ($unreadOnly) {
                $sql .= " AND n.is_read = 0";
            }
            
            $sql .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to get notifications - " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security)
     * @return bool Success status
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->bind_param("ii", $notificationId, $userId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to mark as read - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     * 
     * @param int $userId User ID
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to mark all as read - " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get unread notification count
     * 
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unread_count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            
            $result = $stmt->get_result()->fetch_assoc();
            return $result['unread_count'] ? $result['unread_count'] : 0;
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to get unread count - " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete notification
     * 
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security)
     * @return bool Success status
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->bind_param("ii", $notificationId, $userId);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to delete notification - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean old notifications (cron job)
     * 
     * @param int $daysOld Delete notifications older than this many days
     * @return int Number of notifications deleted
     */
    public function cleanOldNotifications($daysOld = 30) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->bind_param("i", $daysOld);
            $stmt->execute();
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            error_log("NotificationService: Failed to clean old notifications - " . $e->getMessage());
            return 0;
        }
    }
}
?>
