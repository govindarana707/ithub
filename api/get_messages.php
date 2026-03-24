<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    echo '<div class="alert alert-danger">Please login to view messages</div>';
    exit;
}

$userId = $_SESSION['user_id'];
$receiverId = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Ensure messages table exists
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    course_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_course (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Get messages between current user and receiver
$html = '';

if ($receiverId > 0) {
    $stmt = $conn->prepare("
        SELECT m.*, 
               CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as message_type,
               u.username as sender_name
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiiii", $userId, $userId, $receiverId, $receiverId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $html .= '<div class="text-center text-muted p-3">No messages yet. Start a conversation!</div>';
    } else {
        while ($msg = $result->fetch_assoc()) {
            $isSent = $msg['message_type'] === 'sent';
            $time = date('h:i A', strtotime($msg['created_at']));
            $html .= '<div class="message ' . ($isSent ? 'sent' : 'received') . ' mb-2">';
            $html .= '<div class="message-content p-2 ' . ($isSent ? 'bg-primary text-white' : 'bg-light') . ' rounded">';
            $html .= '<small>' . htmlspecialchars($msg['message']) . '</small>';
            $html .= '</div>';
            $html .= '<small class="text-muted">' . $time . '</small>';
            $html .= '</div>';
        }
        
        // Mark messages as read
        $updateStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $updateStmt->bind_param("ii", $userId, $receiverId);
        $updateStmt->execute();
    }
} else {
    $html .= '<div class="text-center text-muted p-3">Select a conversation to view messages</div>';
}

echo $html;
?>