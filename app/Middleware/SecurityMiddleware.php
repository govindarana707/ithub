<?php

namespace App\Middleware;

use Core\Request;
use Core\Response;
use Core\Logging\Logger;
use Core\Security\RateLimiter;
use Core\Security\CsrfProtection;

/**
 * Security Middleware
 * 
 * Handles all security-related middleware functionality
 */
class SecurityMiddleware
{
    private Logger $logger;
    private RateLimiter $rateLimiter;
    private CsrfProtection $csrf;
    private array $config;

    public function __construct(
        Logger $logger,
        RateLimiter $rateLimiter,
        CsrfProtection $csrf,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->rateLimiter = $rateLimiter;
        $this->csrf = $csrf;
        $this->config = array_merge([
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'session_timeout' => 1800, // 30 minutes
            'require_https' => false,
            'allowed_origins' => ['*'],
            'max_request_size' => 10485760, // 10MB
        ], $config);
    }

    /**
     * Handle incoming request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Apply security headers
        $this->applySecurityHeaders($request);

        // Validate request size
        $this->validateRequestSize($request);

        // Check rate limiting
        $this->checkRateLimit($request);

        // Validate CSRF token for POST requests
        $this->validateCsrfToken($request);

        // Check session timeout
        $this->checkSessionTimeout($request);

        // Log security events
        $this->logSecurityEvent($request);

        return $next($request);
    }

    /**
     * Apply security headers
     */
    private function applySecurityHeaders(Request $request): void
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];

        // Content Security Policy
        $csp = $this->buildCSPHeader();
        $headers['Content-Security-Policy'] = $csp;

        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    /**
     * Build Content Security Policy header
     */
    private function buildCSPHeader(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://api.esewa.com.np",
            "media-src 'self' https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests"
        ];

        return implode('; ', $directives);
    }

    /**
     * Validate request size
     */
    private function validateRequestSize(Request $request): void
    {
        $contentLength = $request->header('Content-Length', 0);
        
        if ($contentLength > $this->config['max_request_size']) {
            $this->logger->warning('Request size exceeded', [
                'size' => $contentLength,
                'max_size' => $this->config['max_request_size'],
                'ip' => $request->ip()
            ]);

            Response::json([
                'error' => 'Request too large',
                'message' => 'Maximum request size exceeded'
            ], 413)->send();
            exit;
        }
    }

    /**
     * Check rate limiting
     */
    private function checkRateLimit(Request $request): void
    {
        $key = $this->getRateLimitKey($request);
        $limit = $this->getRateLimit($request);
        $window = $this->getRateLimitWindow($request);

        if (!$this->rateLimiter->allowed($key, $limit, $window)) {
            $this->logger->warning('Rate limit exceeded', [
                'key' => $key,
                'limit' => $limit,
                'window' => $window,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]);

            Response::json([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->rateLimiter->getRetryAfter($key, $window)
            ], 429)->send();
            exit;
        }
    }

    /**
     * Get rate limit key
     */
    private function getRateLimitKey(Request $request): string
    {
        $userId = $request->session()->get('user_id');
        
        if ($userId) {
            return "user:{$userId}:{$request->path()}";
        }

        return "ip:{$request->ip()}:{$request->path()}";
    }

    /**
     * Get rate limit based on request type
     */
    private function getRateLimit(Request $request): int
    {
        $path = $request->path();

        // Login attempts - stricter limit
        if (strpos($path, '/login') !== false || strpos($path, '/auth') !== false) {
            return $this->config['max_login_attempts'];
        }

        // API endpoints
        if (strpos($path, '/api/') !== false) {
            return 100; // 100 requests per window
        }

        // File uploads
        if (in_array($request->method(), ['POST', 'PUT']) && $request->hasFile()) {
            return 10; // 10 uploads per window
        }

        // Default limit
        return 60; // 60 requests per window
    }

    /**
     * Get rate limit window
     */
    private function getRateLimitWindow(Request $request): int
    {
        $path = $request->path();

        // Login attempts - shorter window
        if (strpos($path, '/login') !== false || strpos($path, '/auth') !== false) {
            return 300; // 5 minutes
        }

        // API endpoints
        if (strpos($path, '/api/') !== false) {
            return 60; // 1 minute
        }

        // Default window
        return 3600; // 1 hour
    }

    /**
     * Validate CSRF token
     */
    private function validateCsrfToken(Request $request): void
    {
        if (!$this->shouldValidateCsrf($request)) {
            return;
        }

        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
        
        if (!$this->csrf->validate($token)) {
            $this->logger->warning('CSRF token validation failed', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->header('User-Agent')
            ]);

            Response::json([
                'error' => 'Invalid CSRF token',
                'message' => 'Security validation failed'
            ], 419)->send();
            exit;
        }
    }

    /**
     * Check if CSRF validation is required
     */
    private function shouldValidateCsrf(Request $request): bool
    {
        // Only validate for state-changing requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        // Skip for API routes with their own authentication
        if (strpos($request->path(), '/api/') !== false) {
            return false;
        }

        // Skip for file uploads (handled separately)
        if ($request->hasFile()) {
            return false;
        }

        return true;
    }

    /**
     * Check session timeout
     */
    private function checkSessionTimeout(Request $request): void
    {
        if (!$request->session()->has('user_id')) {
            return;
        }

        $lastActivity = $request->session()->get('last_activity', 0);
        $timeout = $this->config['session_timeout'];

        if (time() - $lastActivity > $timeout) {
            $this->logger->info('Session timeout', [
                'user_id' => $request->session()->get('user_id'),
                'last_activity' => $lastActivity,
                'timeout' => $timeout
            ]);

            $request->session()->invalidate();
            
            Response::json([
                'error' => 'Session expired',
                'message' => 'Your session has expired. Please log in again.',
                'redirect' => '/login'
            ], 401)->send();
            exit;
        }

        // Update last activity
        $request->session()->set('last_activity', time());
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(Request $request): void
    {
        $events = [
            'suspicious_user_agent' => $this->isSuspiciousUserAgent($request),
            'suspicious_ip' => $this->isSuspiciousIP($request),
            'brute_force_attempt' => $this->isBruteForceAttempt($request),
            'sql_injection_attempt' => $this->isSQLInjectionAttempt($request),
            'xss_attempt' => $this->isXSSAttempt($request),
        ];

        foreach ($events as $event => $detected) {
            if ($detected) {
                $this->logger->warning("Security event detected: {$event}", [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id' => $request->session()->get('user_id')
                ]);
            }
        }
    }

    /**
     * Check for suspicious user agent
     */
    private function isSuspiciousUserAgent(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        $suspicious = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'perl', 'ruby', 'php', 'node'
        ];

        foreach ($suspicious as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious IP
     */
    private function isSuspiciousIP(Request $request): bool
    {
        $ip = $request->ip();
        
        // Check against known malicious IPs (would use a real database in production)
        $maliciousIPs = [
            '0.0.0.0', '127.0.0.1' // Example IPs
        ];

        return in_array($ip, $maliciousIPs);
    }

    /**
     * Check for brute force attempt
     */
    private function isBruteForceAttempt(Request $request): bool
    {
        if (!in_array($request->path(), ['/login', '/auth/login'])) {
            return false;
        }

        $key = "login_attempts:{$request->ip()}";
        $attempts = $this->rateLimiter->getAttempts($key);
        
        return $attempts > $this->config['max_login_attempts'];
    }

    /**
     * Check for SQL injection attempt
     */
    private function isSQLInjectionAttempt(Request $request): bool
    {
        $patterns = [
            '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
            '/(\s|^)(or|and)\s+\d+\s*=\s*\d+/i',
            '/(\s|^)(or|and)\s+["\'][^"\']*["\']\s*=\s*["\'][^"\']*["\']/i',
            '/(\s|^)(--|#|\/\*|\*\/)/i',
            '/(\s|^)(xp_|sp_)\w+/i'
        ];

        $inputs = array_merge(
            $request->all(),
            $request->headers->all()
        );

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check for XSS attempt
     */
    private function isXSSAttempt(Request $request): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<[^>]*on\w+\s*=.*?>/i'
        ];

        $inputs = $request->all();

        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get security configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update security configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}
