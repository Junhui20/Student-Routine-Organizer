<?php

/**
 * Advanced Session Management Class
 * Practical Topic: Advanced session management techniques
 */
class SessionManager {
    private const SESSION_TIMEOUT = 3600; // 1 hour
    private const SESSION_REGENERATE_INTERVAL = 1800; // 30 minutes
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_TIME = 900; // 15 minutes
    
    /**
     * Initialize secure session with advanced settings
     */
    public static function initializeSession() {
        // Configure session settings before starting
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize session security
        self::initializeSessionSecurity();
        
        // Check session validity
        self::validateSession();
        
        // Handle session regeneration
        self::handleSessionRegeneration();
        
        return true;
    }
    
    /**
     * Initialize session security measures
     */
    private static function initializeSessionSecurity() {
        // Set initial session data if not exists
        if (!isset($_SESSION['initiated'])) {
            $_SESSION['initiated'] = true;
            $_SESSION['created_time'] = time();
            $_SESSION['last_regeneration'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = self::getUserIP();
        }
    }
    
    /**
     * Validate session security and timeout
     */
    private static function validateSession() {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
                self::destroySession();
                throw new Exception('Session expired due to inactivity');
            }
        }
        
        // Check user agent consistency (security measure)
        if (isset($_SESSION['user_agent'])) {
            $current_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $current_agent) {
                self::destroySession();
                throw new Exception('Session hijacking attempt detected');
            }
        }
        
        // Check IP address consistency (optional, can be disabled for mobile users)
        if (isset($_SESSION['ip_address']) && isset($_SESSION['check_ip']) && $_SESSION['check_ip']) {
            $current_ip = self::getUserIP();
            if ($_SESSION['ip_address'] !== $current_ip) {
                self::destroySession();
                throw new Exception('IP address change detected');
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Handle session ID regeneration for security
     */
    private static function handleSessionRegeneration() {
        if (isset($_SESSION['last_regeneration'])) {
            if (time() - $_SESSION['last_regeneration'] > self::SESSION_REGENERATE_INTERVAL) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Login user with advanced security
     */
    public static function loginUser($user_id, $username, $remember_me = false) {
        // Check login attempts
        if (self::isLoginBlocked()) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
        
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        // Set user session data
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['last_regeneration'] = time();
        $_SESSION['is_authenticated'] = true;
        
        // Clear login attempts on successful login
        self::clearLoginAttempts();
        
        // Handle remember me functionality
        if ($remember_me) {
            require_once 'CookieManager.php';
            $token = CookieManager::generateSecureToken();
            
            // Store remember token in database (you'd implement this)
            self::storeRememberToken($user_id, $token);
            
            // Set remember me cookie
            CookieManager::setRememberMeCookie($user_id, $username, $token);
        }
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['is_authenticated']) && $_SESSION['is_authenticated'] === true;
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current username
     */
    public static function getCurrentUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    /**
     * Logout user and cleanup session
     */
    public static function logoutUser() {
        // Clear remember me cookies
        require_once 'CookieManager.php';
        CookieManager::clearRememberMeCookies();
        
        // Clear remember token from database
        if (isset($_SESSION['user_id'])) {
            self::clearRememberToken($_SESSION['user_id']);
        }
        
        // Destroy session
        self::destroySession();
        
        return true;
    }
    
    /**
     * Destroy session completely
     */
    public static function destroySession() {
        $_SESSION = [];
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Track login attempts for security
     */
    public static function recordLoginAttempt($username, $success = false) {
        $ip = self::getUserIP();
        $key = "login_attempts_{$ip}_{$username}";
        
        if ($success) {
            // Clear attempts on successful login
            if (isset($_SESSION[$key])) {
                unset($_SESSION[$key]);
            }
        } else {
            // Increment failed attempts
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = [
                    'count' => 0,
                    'last_attempt' => time()
                ];
            }
            
            $_SESSION[$key]['count']++;
            $_SESSION[$key]['last_attempt'] = time();
        }
    }
    
    /**
     * Check if login is blocked due to too many attempts
     */
    public static function isLoginBlocked($username = '') {
        $ip = self::getUserIP();
        $key = "login_attempts_{$ip}_{$username}";
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $attempts = $_SESSION[$key];
        
        // Check if lockout period has expired
        if (time() - $attempts['last_attempt'] > self::LOGIN_LOCKOUT_TIME) {
            unset($_SESSION[$key]);
            return false;
        }
        
        return $attempts['count'] >= self::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Clear login attempts
     */
    private static function clearLoginAttempts() {
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'login_attempts_') === 0) {
                unset($_SESSION[$key]);
            }
        }
    }
    
    /**
     * Get user IP address
     */
    private static function getUserIP() {
        $ip_fields = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = $_SERVER[$field];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get session information for debugging
     */
    public static function getSessionInfo() {
        return [
            'session_id' => session_id(),
            'created_time' => $_SESSION['created_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'last_regeneration' => $_SESSION['last_regeneration'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'ip_address' => $_SESSION['ip_address'] ?? null,
            'is_authenticated' => $_SESSION['is_authenticated'] ?? false
        ];
    }
    
    /**
     * Store remember token in database (placeholder implementation)
     */
    private static function storeRememberToken($user_id, $token) {
        // This would store the hashed token in the database
        // For now, we'll store it in session as a placeholder
        $_SESSION['remember_token'] = password_hash($token, PASSWORD_DEFAULT);
        return true;
    }
    
    /**
     * Clear remember token from database (placeholder implementation)
     */
    private static function clearRememberToken($user_id) {
        // This would clear the token from the database
        if (isset($_SESSION['remember_token'])) {
            unset($_SESSION['remember_token']);
        }
        return true;
    }
    
    /**
     * Extend session timeout for active users
     */
    public static function extendSession() {
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if session needs regeneration
     */
    public static function needsRegeneration() {
        if (!isset($_SESSION['last_regeneration'])) {
            return true;
        }
        
        return (time() - $_SESSION['last_regeneration']) > self::SESSION_REGENERATE_INTERVAL;
    }
}
?> 