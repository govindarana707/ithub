<?php
/**
 * Comprehensive Error Handling and Logging System
 * Production-ready error management for IT HUB LMS
 */

class ErrorHandler {
    private static $logFile;
    private static $emailAdmin = true;
    private static $debugMode = false;
    
    public static function initialize($config = []) {
        self::$logFile = $config['log_file'] ?? __DIR__ . '/../logs/system.log';
        self::$emailAdmin = $config['email_admin'] ?? true;
        self::$debugMode = $config['debug_mode'] ?? false;
        
        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Set custom error handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];
        
        $errorType = $errorTypes[$errno] ?? 'Unknown Error';
        
        $message = sprintf(
            "[%s] %s in %s on line %d",
            $errorType,
            $errstr,
            $errfile,
            $errline
        );
        
        self::log($message, 'ERROR');
        
        // Email critical errors
        if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::emailAdmin($message, 'Critical System Error');
        }
        
        return true;
    }
    
    public static function handleException($exception) {
        $message = sprintf(
            "Uncaught Exception: %s in %s on line %d\nStack Trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        self::log($message, 'CRITICAL');
        self::emailAdmin($message, 'Critical Exception');
        
        if (self::$debugMode) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb;'>";
            echo "<h3>Uncaught Exception</h3>";
            echo "<pre>" . htmlspecialchars($message) . "</pre>";
            echo "</div>";
        } else {
            self::showUserFriendlyError();
        }
        
        exit(1);
    }
    
    public static function handleShutdown() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleError(
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
    
    public static function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        $url = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? 'unknown';
        
        $logEntry = sprintf(
            "[%s] [%s] [IP: %s] [User: %s] [URL: %s] %s\n",
            $timestamp,
            $level,
            $ip,
            $user,
            $url,
            $message
        );
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Rotate logs if they get too large
        if (filesize(self::$logFile) > 10 * 1024 * 1024) { // 10MB
            self::rotateLogs();
        }
    }
    
    private static function rotateLogs() {
        $backupFile = str_replace('.log', '_' . date('Y-m-d_H-i-s') . '.log', self::$logFile);
        rename(self::$logFile, $backupFile);
        
        // Keep only last 7 days of logs
        $logDir = dirname(self::$logFile);
        $files = glob($logDir . '/*.log');
        foreach ($files as $file) {
            if (filemtime($file) < time() - 7 * 24 * 60 * 60) {
                unlink($file);
            }
        }
    }
    
    private static function emailAdmin($message, $subject) {
        if (!self::$emailAdmin) {
            return;
        }
        
        $to = 'admin@ithub.com'; // Configure this
        $headers = [
            'From: noreply@ithub.com',
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        $emailBody = sprintf(
            "IT HUB LMS - %s\n\nTime: %s\nIP: %s\nUser: %s\nURL: %s\n\n%s",
            $subject,
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            $message
        );
        
        mail($to, $subject, $emailBody, implode("\r\n", $headers));
    }
    
    private static function showUserFriendlyError() {
        http_response_code(500);
        include __DIR__ . '/../views/errors/500.php';
    }
    
    public static function logUserAction($action, $details = '', $userId = null) {
        $userId = $userId ?? ($_SESSION['user_id'] ?? null);
        if ($userId) {
            $message = sprintf(
                "User Action: %s | Details: %s | User ID: %s",
                $action,
                $details,
                $userId
            );
            self::log($message, 'USER_ACTION');
        }
    }
    
    public static function logSecurity($event, $details = '') {
        $message = sprintf(
            "Security Event: %s | Details: %s | IP: %s | User Agent: %s",
            $event,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        self::log($message, 'SECURITY');
        
        // Email security events immediately
        self::emailAdmin($message, 'Security Event: ' . $event);
    }
    
    public static function logPerformance($script, $duration, $memory = null) {
        $message = sprintf(
            "Performance: %s | Duration: %s seconds | Memory: %s MB",
            $script,
            number_format($duration, 4),
            $memory ? number_format($memory / 1024 / 1024, 2) : 'N/A'
        );
        self::log($message, 'PERFORMANCE');
    }
}

// Initialize error handler
ErrorHandler::initialize([
    'log_file' => __DIR__ . '/../logs/system.log',
    'debug_mode' => false, // Set to true in development
    'email_admin' => true
]);
?>
