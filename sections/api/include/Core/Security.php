<?php
namespace Core;

/**
 * Security Class - CSRF, XSS, SQL Injection Prevention
 */
class Security
{
    private static $csrfTokens = [];
    
    /**
     * Generate CSRF Token
     */
    public static function generateCsrfToken($sessionId = null)
    {
        $sessionId = $sessionId ?? session_id();
        if (empty($sessionId)) {
            session_start();
            $sessionId = session_id();
        }
        
        $token = bin2hex(random_bytes(32));
        self::$csrfTokens[$sessionId] = $token;
        
        // Store in session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Validate CSRF Token
     */
    public static function validateCsrfToken($token, $sessionId = null)
    {
        $sessionId = $sessionId ?? session_id();
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        // Check token exists
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Check token matches
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        
        // Check token age (max 1 hour)
        if (isset($_SESSION['csrf_token_time'])) {
            $age = time() - $_SESSION['csrf_token_time'];
            if ($age > 3600) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitizeInput($input, $type = 'string')
    {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'int':
                return (int) $input;
            
            case 'float':
                return (float) $input;
            
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            case 'string':
            default:
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL format
     */
    public static function validateUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Prevent SQL Injection (use with PDO prepared statements)
     */
    public static function escapeSql($input)
    {
        // Note: Always use PDO prepared statements instead
        // This is a backup sanitization
        return addslashes($input);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password hash
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash($hash)
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Validate password strength
     * Returns array with 'valid' boolean and 'errors' array
     */
    public static function validatePasswordStrength($password, $minLength = 8)
    {
        $errors = [];
        
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters";
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
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Prevent directory traversal attacks
     */
    public static function sanitizePath($path)
    {
        // Remove any ../ or ..\\ sequences
        $path = str_replace(['../', '..\\'], '', $path);
        
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        return $path;
    }
    
    /**
     * Check if request is from same origin
     */
    public static function checkOrigin($allowedOrigins = [])
    {
        if (empty($allowedOrigins)) {
            return true;
        }
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        return in_array($origin, $allowedOrigins);
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders()
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict Transport Security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Rate limiting check (basic implementation)
     * Returns true if rate limit exceeded
     */
    public static function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 60)
    {
        $key = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $now = time();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $now
            ];
            return false;
        }
        
        $data = $_SESSION[$key];
        $elapsed = $now - $data['start_time'];
        
        // Reset if time window passed
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => $now
            ];
            return false;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        
        // Check if limit exceeded
        return $_SESSION[$key]['count'] > $maxRequests;
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIp()
    {
        $ipAddress = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '0.0.0.0';
    }
}
