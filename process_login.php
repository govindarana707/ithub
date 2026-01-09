<?php
require_once 'config/config.php';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        sendJSON(['success' => false, 'message' => 'Please fill in all fields']);
    }
    
    $user = new User();
    $result = $user->login($email, $password);
    
    if ($result['success']) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['email'] = $result['user']['email'];
        $_SESSION['full_name'] = $result['user']['full_name'];
        $_SESSION['user_role'] = $result['user']['role'];  // Fixed: use user_role
        
        // Generate CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Set session timeout (30 minutes)
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_on'] = time() + 1800; // 30 minutes
        
        // Log activity
        logActivity($result['user']['id'], 'login', 'User logged in from IP: ' . $_SERVER['REMOTE_ADDR']);
        
        // Redirect based on role
        $redirect = 'dashboard.php';
        if ($result['user']['role'] === 'admin') {
            $redirect = 'admin/dashboard.php';
        } elseif ($result['user']['role'] === 'instructor') {
            $redirect = 'instructor/dashboard.php';
        } elseif ($result['user']['role'] === 'student') {
            $redirect = 'student/dashboard.php';
        }
        
        sendJSON(['success' => true, 'message' => 'Login successful', 'redirect' => $redirect]);
    } else {
        sendJSON(['success' => false, 'message' => $result['error']]);
    }
} else {
    redirect('login.php');
}
?>
