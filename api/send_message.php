<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to send messages']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$userId = $_SESSION['user_id'];
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$receiverId = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;

if (empty($message)) {
    sendJSON(['success' => false, 'message' => 'Message cannot be empty']);
}

if ($receiverId <= 0) {
    sendJSON(['success' => false, 'message' => 'Invalid receiver']);
}

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

// Insert the message
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, course_id, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $userId, $receiverId, $courseId, $message);

if ($stmt->execute()) {
    sendJSON(['success' => true, 'message' => 'Message sent successfully']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to send message: ' . $conn->error]);
}
?>