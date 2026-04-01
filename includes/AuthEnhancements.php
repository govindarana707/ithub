<?php
require_once __DIR__ . '/../config/config.php';

class AuthEnhancements {
    private $db;
    
    public function __construct() {
        $this->db = connectDB();
    }
    
    /**
     * Generate secure verification token
     */
    public function generateVerificationToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $token, $fullName) {
        // For localhost, we'll use a simple mail function
        // In production, use PHPMailer or similar
        $verificationUrl = BASE_URL . "verify-email.php?token=" . urlencode($token);
        
        $subject = "Verify Your IT HUB Account";
        $message = "
        <html>
        <head>
            <title>Verify Your IT HUB Account</title>
        </head>
        <body>
            <h2>Welcome to IT HUB, $fullName!</h2>
            <p>Thank you for registering. Please click the link below to verify your email address:</p>
            <p><a href='$verificationUrl' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>Or copy and paste this link in your browser:</p>
            <p>$verificationUrl</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't create an account, please ignore this email.</p>
            <hr>
            <p>Best regards,<br>IT HUB Team</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: noreply@ithub.com" . "\r\n";
        
        // For localhost testing, we'll log instead of sending
        error_log("EMAIL VERIFICATION: Would send to $email with token: $token");
        error_log("Verification URL: $verificationUrl");
        
        // Uncomment for actual email sending (requires mail server setup)
        // return mail($email, $subject, $message, $headers);
        
        return true; // Simulate successful send for localhost
    }
    
    /**
     * Check rate limiting
     */
    public function checkRateLimit($ipAddress, $email = null, $type = 'login', $maxAttempts = 5, $timeWindow = 300) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND attempt_type = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            " . ($email ? "AND email = ?" : "")
        );
        
        if ($email) {
            $stmt->bind_param("sssi", $ipAddress, $type, $timeWindow, $email);
        } else {
            $stmt->bind_param("ssi", $ipAddress, $type, $timeWindow);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempts'] < $maxAttempts;
    }
    
    /**
     * Get remaining time for rate limit
     */
    public function getRateLimitRemainingTime($ipAddress, $email = null, $type = 'login', $timeWindow = 900) {
        $stmt = $this->db->prepare("
            SELECT created_at 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND attempt_type = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            " . ($email ? "AND email = ?" : "")
            . " ORDER BY created_at ASC
            LIMIT 1
        ");
        
        if ($email) {
            $stmt->bind_param("sssi", $ipAddress, $type, $timeWindow, $email);
        } else {
            $stmt->bind_param("ssi", $ipAddress, $type, $timeWindow);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $firstAttemptTime = strtotime($result['created_at']);
            $windowEndTime = $firstAttemptTime + $timeWindow;
            $remainingTime = $windowEndTime - time();
            return max(0, $remainingTime);
        }
        
        return 0;
    }
    
    /**
     * Log login attempt
     */
    public function logAttempt($ipAddress, $email = null, $type = 'login', $success = false) {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, email, attempt_type, success) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("sssi", $ipAddress, $email, $type, $success);
        return $stmt->execute();
    }
    
    /**
     * Check if account is locked
     */
    public function isAccountLocked($userId = null, $ipAddress = null) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as locked_count 
            FROM account_lockouts 
            WHERE locked_until > NOW()
            AND (user_id = ? OR ip_address = ?)
        ");
        $stmt->bind_param("is", $userId, $ipAddress);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['locked_count'] > 0;
    }
    
    /**
     * Lock account
     */
    public function lockAccount($userId = null, $ipAddress = null, $reason = 'Too many failed attempts') {
        $lockDuration = 900; // 15 minutes
        $lockedUntil = date('Y-m-d H:i:s', time() + $lockDuration);
        
        $stmt = $this->db->prepare("
            INSERT INTO account_lockouts (user_id, ip_address, lock_reason, locked_until) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $userId, $ipAddress, $reason, $lockedUntil);
        return $stmt->execute();
    }
    
    /**
     * Enhanced password validation
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>@]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Generate simple CAPTCHA
     */
    public function generateCaptcha() {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $captcha = '';
        for ($i = 0; $i < 6; $i++) {
            $captcha .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        $_SESSION['captcha_code'] = $captcha;
        $_SESSION['captcha_time'] = time();
        
        return $captcha;
    }
    
    /**
     * Verify CAPTCHA
     */
    public function verifyCaptcha($userInput) {
        if (!isset($_SESSION['captcha_code']) || !isset($_SESSION['captcha_time'])) {
            return false;
        }
        
        // CAPTCHA expires after 5 minutes
        if (time() - $_SESSION['captcha_time'] > 300) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
            return false;
        }
        
        $isValid = strtoupper($userInput) === strtoupper($_SESSION['captcha_code']);
        
        // Clear CAPTCHA after verification
        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
        
        return $isValid;
    }
    
    /**
     * Get recent failed attempts
     */
    public function getRecentFailedAttempts($ipAddress, $email = null, $timeWindow = 300) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = ? 
            AND attempt_type = 'login' 
            AND success = FALSE
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            " . ($email ? "AND email = ?" : "")
        );
        
        if ($email) {
            $stmt->bind_param("sis", $ipAddress, $timeWindow, $email);
        } else {
            $stmt->bind_param("si", $ipAddress, $timeWindow);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['attempts'] ?? 0;
    }
    
    /**
     * Create CAPTCHA image
     */
    public function createCaptchaImage($captcha) {
        $width = 120;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);
        
        // Colors
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        $textColor = imagecolorallocate($image, 50, 50, 50);
        $lineColor = imagecolorallocate($image, 200, 200, 200);
        
        // Background
        imagefill($image, 0, 0, $bgColor);
        
        // Add noise lines
        for ($i = 0; $i < 5; $i++) {
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }
        
        // Add text
        $fontSize = 16;
        $angle = 0;
        $x = 15;
        $y = 25;
        
        imagettftext($image, $fontSize, $angle, $x, $y, $textColor, __DIR__ . '/../assets/fonts/arial.ttf', $captcha);
        
        // Output image
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
}

// Simple CAPTCHA fallback (if TTF not available)
function createSimpleCaptcha($text) {
    $width = 120;
    $height = 40;
    $image = imagecreatetruecolor($width, $height);
    
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 50, 50, 50);
    
    imagefill($image, 0, 0, $bgColor);
    
    // Add text
    $fontSize = 5;
    $x = 20;
    $y = 15;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Add noise
    for ($i = 0; $i < 50; $i++) {
        $pixelColor = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));
        imagesetpixel($image, rand(0, $width), rand(0, $height), $pixelColor);
    }
    
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}
?>
