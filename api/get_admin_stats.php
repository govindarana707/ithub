<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$user = new User();

// Get statistics
$stats = $user->getUserStats();

// Get recent users
$recentUsers = $user->getAllUsers(null, null, 10, 0);

// Get enrollment stats
$conn = connectDB();
$stmt = $conn->query("SELECT COUNT(*) as total FROM enrollments");
$enrollmentCount = $stmt->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->query("SELECT COUNT(*) as total FROM quiz_attempts");
$quizAttempts = $stmt->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->query("SELECT COUNT(*) as total FROM certificates");
$certificateCount = $stmt->fetch_assoc()['total'];
$stmt->close();

$conn->close();

sendJSON([
    'success' => true,
    'stats' => $stats,
    'recent_users' => $recentUsers,
    'enrollment_count' => $enrollmentCount,
    'quiz_attempts' => $quizAttempts,
    'certificate_count' => $certificateCount
]);
?>
