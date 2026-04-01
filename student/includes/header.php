<?php
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

requireStudent();

// Get notification count for the universal header
$notificationCount = 0;
if (isLoggedIn()) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        if ($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $notificationCount = $result['count'];
        }
        $stmt->close();
    }
    $conn->close();
}

// Load universal header
require_once dirname(dirname(__DIR__)) . '/includes/universal_header.php';
?>
