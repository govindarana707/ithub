<?php
require_once 'config/config.php';
require_once 'models/User.php';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = 'Invalid reset token';
    include 'reset-password.php';
    exit;
}

$token = sanitize($_GET['token']);

// Verify token
$conn = connectDB();
$stmt = $conn->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Invalid or expired reset token';
    include 'reset-password.php';
    exit;
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = sanitize($_POST['token']);
    
    if (empty($password) || empty($confirmPassword)) {
        sendJSON(['success' => false, 'message' => 'Please fill in all fields']);
    }
    
    if (strlen($password) < 8) {
        sendJSON(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    }
    
    if ($password !== $confirmPassword) {
        sendJSON(['success' => false, 'message' => 'Passwords do not match']);
    }
    
    // Hash new password
    $hashedPassword = hashPassword($password);
    
    // Update password and clear reset token
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $hashedPassword, $token);
    
    if ($stmt->execute()) {
        // Log password reset
        logActivity($user['id'], 'password_reset', 'Password successfully reset');
        
        sendJSON(['success' => true, 'message' => 'Password reset successful! Please login with your new password.']);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to reset password']);
    }
} else {
    redirect('reset-password.php?token=' . $token);
}
?>
