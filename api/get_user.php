<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$userId = intval($_GET['user_id']);
$currentUser = $_SESSION['user_id'];

// Verify admin access
if (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

// Admin can access any user (except themselves for some operations)
if ($userId == $currentUser && getUserRole() === 'admin') {
    // Admin can access their own data
} elseif (getUserRole() !== 'admin') {
    sendJSON(['success' => false, 'message' => 'Access denied']);
}

$user = new User();
$userData = $user->getUserById($userId);

error_log("DEBUG: API get_user for user_id: " . $userId . " - " . ($userData ? "found" : "not found"));

if ($userData) {
    sendJSON([
        'success' => true,
        'user' => $userData
    ]);
} else {
    sendJSON(['success' => false, 'message' => 'User not found']);
}
?>
