<?php
/**
 * Student Module Security Middleware
 * Provides additional security checks for student routes
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Enhanced Student Security Check
 * Validates student access with additional security measures
 */
function requireStudentSecure(): void
{
    // Basic authentication check
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = 'Please login to continue';
        redirect('../login.php');
        exit;
    }
    
    // Role validation
    $role = getUserRole();
    if ($role !== 'student' && $role !== 'admin') {
        $_SESSION['error_message'] = 'Access denied. Student privileges required.';
        logActivity($_SESSION['user_id'] ?? 0, 'unauthorized_access', 'Attempted to access student area');
        redirect('../dashboard.php');
        exit;
    }
    
    // Session security
    if (!isset($_SESSION['student_session_verified'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['student_session_verified'] = true;
        $_SESSION['last_activity'] = time();
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
        session_destroy();
        $_SESSION['error_message'] = 'Session expired. Please login again.';
        redirect('../login.php');
        exit;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // CSRF token validation for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            $_SESSION['error_message'] = 'Invalid request token. Please refresh and try again.';
            logActivity($_SESSION['user_id'], 'csrf_failure', 'CSRF token validation failed');
            redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
            exit;
        }
    }
}

/**
 * Validate Course Access
 * Ensures student can only access enrolled courses
 */
function validateCourseAccess(int $courseId): bool
{
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $role = getUserRole();
    
    // Admins can access all courses
    if ($role === 'admin') {
        return true;
    }
    
    // Students must be enrolled
    if ($role === 'student') {
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->bind_param("ii", $userId, $courseId);
        $stmt->execute();
        $hasAccess = $stmt->get_result()->num_rows > 0;
        $conn->close();
        
        return $hasAccess;
    }
    
    return false;
}

/**
 * Rate Limiting for Sensitive Actions
 * Prevents brute force attacks
 */
function checkRateLimit(string $action, int $maxAttempts = 5, int $timeWindow = 300): bool
{
    $userId = $_SESSION['user_id'] ?? $_SERVER['REMOTE_ADDR'];
    $key = $action . '_' . $userId;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    $attempts = $_SESSION[$key];
    $now = time();
    
    // Clean old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $now;
    $_SESSION[$key] = $attempts;
    
    return true;
}

/**
 * Input Sanitization for Student Data
 */
function sanitizeStudentData(array $data): array
{
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            // Remove HTML tags and special characters
            $sanitized[$key] = sanitize($value);
            
            // Additional validation for specific fields
            switch ($key) {
                case 'email':
                    $sanitized[$key] = filter_var($value, FILTER_SANITIZE_EMAIL);
                    break;
                case 'phone':
                    $sanitized[$key] = preg_replace('/[^0-9+\-\s]/', '', $value);
                    break;
                case 'bio':
                    $sanitized[$key] = substr($sanitized[$key], 0, 500); // Limit bio length
                    break;
            }
        } elseif (is_array($value)) {
            $sanitized[$key] = sanitizeStudentData($value);
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

/**
 * Student Activity Logger
 * Enhanced logging with security context
 */
function logStudentActivity(string $action, string $details = '', array $context = []): void
{
    $userId = $_SESSION['user_id'] ?? 0;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Add security context
    $securityContext = [
        'session_id' => session_id(),
        'csrf_token' => substr($_SESSION['csrf_token'] ?? '', 0, 8) . '...', // Partial token for logging
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];
    
    $context = array_merge($context, $securityContext);
    
    // Log to database
    $conn = connectDB();
    $stmt = $conn->prepare("
        INSERT INTO student_activity_logs (user_id, action, details, ip_address, user_agent, context, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $contextJson = json_encode($context);
    $stmt->bind_param("isssss", $userId, $action, $details, $ipAddress, $userAgent, $contextJson);
    $stmt->execute();
    $conn->close();
    
    // Also log to system activity log
    logActivity($userId, $action, $details);
}

/**
 * Create student activity logs table if not exists
 */
function ensureStudentActivityLogs(): void
{
    $conn = connectDB();
    
    $sql = "CREATE TABLE IF NOT EXISTS student_activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        context JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_action (user_id, action),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $conn->query($sql);
    $conn->close();
}

// Initialize security features
ensureStudentActivityLogs();
?>
