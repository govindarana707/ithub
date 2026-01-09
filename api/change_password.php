<?php
require_once '../config/config.php';
require_once '../models/User.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Please login to continue']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method']);
}

$studentId = $_SESSION['user_id'];
$user = new User();

$currentPassword = $_POST['current_password'];
$newPassword = $_POST['new_password'];
$confirmPassword = $_POST['confirm_password'];

// Validate input
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    sendJSON(['success' => false, 'message' => 'All fields are required']);
}

if ($newPassword !== $confirmPassword) {
    sendJSON(['success' => false, 'message' => 'New passwords do not match']);
}

if (strlen($newPassword) < 6) {
    sendJSON(['success' => false, 'message' => 'Password must be at least 6 characters long']);
}

// Verify current password
$currentUser = $user->getUserById($studentId);
if (!password_verify($currentPassword, $currentUser['password'])) {
    sendJSON(['success' => false, 'message' => 'Current password is incorrect']);
}

// Update password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$result = $user->updatePassword($studentId, $hashedPassword);

if ($result) {
    // Log activity
    logActivity($studentId, 'password_changed', 'Password changed successfully');
    
    sendJSON(['success' => true, 'message' => 'Password changed successfully']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to change password']);
}
?>
