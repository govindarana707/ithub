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

// Update profile information
$data = [
    'full_name' => sanitize($_POST['full_name']),
    'email' => sanitize($_POST['email']),
    'phone' => sanitize($_POST['phone'] ?? ''),
    'bio' => sanitize($_POST['bio'] ?? '')
];

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    sendJSON(['success' => false, 'message' => 'Invalid email address']);
}

// Check if email is already taken by another user
$existingUser = $user->getUserByEmail($data['email']);
if ($existingUser && $existingUser['id'] != $studentId) {
    sendJSON(['success' => false, 'message' => 'Email already exists']);
}

$result = $user->updateUser($studentId, $data);

if ($result) {
    // Update session data
    $_SESSION['full_name'] = $data['full_name'];
    $_SESSION['email'] = $data['email'];
    
    // Log activity
    logActivity($studentId, 'profile_updated', 'Updated profile information');
    
    sendJSON(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    sendJSON(['success' => false, 'message' => 'Failed to update profile']);
}
?>
