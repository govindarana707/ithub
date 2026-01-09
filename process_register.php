<?php
require_once 'config/config.php';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitize($_POST['full_name']),
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'phone' => sanitize($_POST['phone']),
        'password' => hashPassword($_POST['password']),
        'role' => sanitize($_POST['role'])
    ];
    
    // Validation
    if (empty($data['full_name']) || empty($data['username']) || empty($data['email']) || empty($data['password']) || empty($data['role'])) {
        sendJSON(['success' => false, 'message' => 'Please fill in all required fields']);
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendJSON(['success' => false, 'message' => 'Invalid email format']);
    }
    
    if (strlen($_POST['password']) < 8) {
        sendJSON(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    }
    
    $user = new User();
    
    // Check if email already exists
    if ($user->emailExists($data['email'])) {
        sendJSON(['success' => false, 'message' => 'Email already registered']);
    }
    
    // Check if username already exists
    if ($user->usernameExists($data['username'])) {
        sendJSON(['success' => false, 'message' => 'Username already taken']);
    }
    
    // Register user
    $result = $user->register($data);
    
    if ($result['success']) {
        sendJSON(['success' => true, 'message' => 'Registration successful! Please login to continue.']);
    } else {
        sendJSON(['success' => false, 'message' => 'Registration failed: ' . $result['error']]);
    }
} else {
    redirect('register.php');
}
?>
