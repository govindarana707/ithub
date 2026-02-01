<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'models/User.php';
require_once 'includes/AuthEnhancements.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth = new AuthEnhancements();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check rate limiting
        if (!$auth->checkRateLimit($ipAddress, $_POST['email'] ?? null, 'register', 3, 300)) {
            sendJSON(['success' => false, 'message' => 'Too many registration attempts. Please try again later.']);
        }
        
        $data = [
            'full_name' => sanitize($_POST['full_name']),
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone']),
            'password' => hashPassword($_POST['password']),
            'role' => sanitize($_POST['role'])
        ];
        
        // Enhanced validation
        if (empty($data['full_name']) || empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
            sendJSON(['success' => false, 'message' => 'Please fill in all required fields']);
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            sendJSON(['success' => false, 'message' => 'Invalid email format']);
        }
        
        // Enhanced password validation
        $passwordErrors = $auth->validatePassword($_POST['password']);
        if (!empty($passwordErrors)) {
            sendJSON(['success' => false, 'message' => implode(', ', $passwordErrors)]);
        }
        
        $user = new User();
        
        // Check if email already exists
        if ($user->emailExists($data['email'])) {
            $auth->logAttempt($ipAddress, $data['email'], 'register', false);
            sendJSON(['success' => false, 'message' => 'Email already registered']);
        }
        
        // Check if username already exists
        if ($user->usernameExists($data['username'])) {
            $auth->logAttempt($ipAddress, $data['email'], 'register', false);
            sendJSON(['success' => false, 'message' => 'Username already taken']);
        }
        
        // Register user
        $result = $user->register($data);
        
        if ($result['success']) {
            sendJSON([
                'success' => true, 
                'message' => 'Registration successful! You can now login.',
                'requires_verification' => false
            ]);
        } else {
            sendJSON(['success' => false, 'message' => 'Registration failed: ' . $result['error']]);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Server error occurred. Please try again.']);
}
?>
