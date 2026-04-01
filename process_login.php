<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

require_once 'config/config.php';
require_once 'models/User.php';
require_once 'includes/AuthEnhancements.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth = new AuthEnhancements();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        // Check rate limiting (increased to 10 attempts in 15 minutes for better user experience)
        if (!$auth->checkRateLimit($ipAddress, $email, 'login', 10, 900)) {
            // Show remaining time if locked
            $remainingTime = $auth->getRateLimitRemainingTime($ipAddress, $email, 'login');
            $minutes = ceil($remainingTime / 60);
            sendJSON([
                'success' => false, 
                'message' => "Too many login attempts. Please try again in {$minutes} minutes.",
                'locked' => true,
                'remaining_minutes' => $minutes
            ]);
        }
        
        // Check if IP is locked
        if ($auth->isAccountLocked(null, $ipAddress)) {
            sendJSON(['success' => false, 'message' => 'Your IP has been temporarily locked due to suspicious activity.']);
        }
        
        if (empty($email) || empty($password)) {
            $auth->logAttempt($ipAddress, $email, 'login', false);
            sendJSON(['success' => false, 'message' => 'Please fill in all fields']);
        }
        
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            $userId = $result['user']['id'];
            
            // Check if user account is locked
            if ($auth->isAccountLocked($userId, null)) {
                $auth->logAttempt($ipAddress, $email, 'login', false);
                sendJSON(['success' => false, 'message' => 'Account temporarily locked. Please try again later.']);
            }
            
            // Check if email is verified (handle missing field)
            $emailVerified = 1; // Default to verified since field doesn't exist
            if (!$emailVerified) {
                $auth->logAttempt($ipAddress, $email, 'login', false);
                sendJSON([
                    'success' => false, 
                    'message' => 'Please verify your email address before logging in.',
                    'requires_verification' => true
                ]);
            }
            
            // Log successful login
            $auth->logAttempt($ipAddress, $email, 'login', true);
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['email'] = $result['user']['email'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            $_SESSION['user_role'] = $result['user']['role'];
            $_SESSION['email_verified'] = $emailVerified;
            $_SESSION['logged_in'] = true;
            
            // Generate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Set session timeout (30 minutes)
            $_SESSION['last_activity'] = time();
            $_SESSION['expires_on'] = time() + 1800; // 30 minutes
            
            // Log activity (optional - won't break login if fails)
            logActivity($result['user']['id'], 'login', 'User logged in from IP: ' . $ipAddress);
            
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
            // Log failed login attempt
            $auth->logAttempt($ipAddress, $email, 'login', false);
            
            // Check if we should lock the account/IP (increased threshold for better UX)
            $failedAttempts = $auth->getRecentFailedAttempts($ipAddress, $email, 900); // Last 15 minutes
            
            if ($failedAttempts >= 10) {
                // Lock for 10 minutes instead of 15
                $auth->lockAccount(null, $ipAddress, 'Too many failed login attempts');
                sendJSON(['success' => false, 'message' => 'Too many failed attempts. Account locked for 10 minutes.']);
            }
            
            sendJSON(['success' => false, 'message' => $result['error']]);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendJSON(['success' => false, 'message' => 'Server error occurred. Please try again.']);
}
?>
