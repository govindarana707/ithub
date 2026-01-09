<?php
require_once 'config/config.php';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        sendJSON(['success' => false, 'message' => 'Please enter your email address']);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['success' => false, 'message' => 'Invalid email format']);
    }
    
    $user = new User();
    
    // Check if email exists
    $userData = $user->getUserByEmail($email);
    if (!$userData) {
        // Don't reveal if email exists or not for security
        sendJSON(['success' => true, 'message' => 'If an account with this email exists, a reset link has been sent.']);
    }
    
    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save reset token to database (we'll need to add this column to users table)
    $conn = connectDB();
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
    $stmt->bind_param("sss", $resetToken, $resetTokenExpiry, $email);
    
    if ($stmt->execute()) {
        // Send reset email (for now, just log it - in production, use actual email sending)
        $resetLink = BASE_URL . "reset-password.php?token=" . $resetToken;
        
        // Log the password reset request
        logActivity($userData['id'], 'password_reset_requested', 'Password reset requested for email: ' . $email);
        
        // For development, show the reset link (remove this in production)
        error_log("Password reset link for $email: $resetLink");
        
        sendJSON(['success' => true, 'message' => 'If an account with this email exists, a reset link has been sent.']);
    } else {
        sendJSON(['success' => false, 'message' => 'Failed to process password reset request']);
    }
} else {
    redirect('forgot-password.php');
}
?>
