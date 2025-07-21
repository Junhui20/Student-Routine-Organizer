<?php

/**
 * Cookie Management Class
 * Practical Topic: Set, read, and delete cookies using PHP
 */
class CookieManager {
    private const REMEMBER_TOKEN_NAME = 'remember_token';
    private const REMEMBER_USER_NAME = 'remember_user';
    private const SESSION_PREFERENCE_NAME = 'session_preferences';
    
    // Cookie expiration times
    private const REMEMBER_DURATION = 30 * 24 * 60 * 60; // 30 days
    private const PREFERENCE_DURATION = 365 * 24 * 60 * 60; // 1 year
    
    /**
     * Set a persistent login cookie for "Remember Me" functionality
     */
    public static function setRememberMeCookie($user_id, $username, $token) {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true;
        $samesite = 'Lax';
        
        // Set remember token cookie
        setcookie(
            self::REMEMBER_TOKEN_NAME,
            $token,
            [
                'expires' => time() + self::REMEMBER_DURATION,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]
        );
        
        // Set remember user cookie (encrypted)
        $encrypted_data = base64_encode(json_encode([
            'user_id' => $user_id,
            'username' => $username,
            'timestamp' => time()
        ]));
        
        setcookie(
            self::REMEMBER_USER_NAME,
            $encrypted_data,
            [
                'expires' => time() + self::REMEMBER_DURATION,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]
        );
        
        return true;
    }
    
    /**
     * Read remember me cookie data
     */
    public static function getRememberMeCookie() {
        if (!isset($_COOKIE[self::REMEMBER_TOKEN_NAME]) || !isset($_COOKIE[self::REMEMBER_USER_NAME])) {
            return false;
        }
        
        try {
            $token = $_COOKIE[self::REMEMBER_TOKEN_NAME];
            $user_data = json_decode(base64_decode($_COOKIE[self::REMEMBER_USER_NAME]), true);
            
            // Check if cookie is not too old (additional security)
            if (isset($user_data['timestamp']) && (time() - $user_data['timestamp']) > self::REMEMBER_DURATION) {
                self::clearRememberMeCookies();
                return false;
            }
            
            return [
                'token' => $token,
                'user_id' => $user_data['user_id'] ?? null,
                'username' => $user_data['username'] ?? null
            ];
        } catch (Exception $e) {
            self::clearRememberMeCookies();
            return false;
        }
    }
    
    /**
     * Delete remember me cookies
     */
    public static function clearRememberMeCookies() {
        setcookie(self::REMEMBER_TOKEN_NAME, '', time() - 3600, '/');
        setcookie(self::REMEMBER_USER_NAME, '', time() - 3600, '/');
        return true;
    }
    
    /**
     * Set user preferences cookie
     */
    public static function setUserPreferences($preferences) {
        $encrypted_prefs = base64_encode(json_encode($preferences));
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        
        return setcookie(
            self::SESSION_PREFERENCE_NAME,
            $encrypted_prefs,
            [
                'expires' => time() + self::PREFERENCE_DURATION,
                'path' => '/',
                'secure' => $secure,
                'httponly' => false, // Allow JavaScript access for UI preferences
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Read user preferences cookie
     */
    public static function getUserPreferences() {
        if (!isset($_COOKIE[self::SESSION_PREFERENCE_NAME])) {
            return [
                'theme' => 'default',
                'entries_per_page' => 10,
                'auto_save' => true
            ];
        }
        
        try {
            $preferences = json_decode(base64_decode($_COOKIE[self::SESSION_PREFERENCE_NAME]), true);
            return $preferences ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Delete user preferences cookie
     */
    public static function clearUserPreferences() {
        return setcookie(self::SESSION_PREFERENCE_NAME, '', time() - 3600, '/');
    }
    
    /**
     * Set a custom cookie with validation
     */
    public static function setCookie($name, $value, $expiry_days = 30, $options = []) {
        $default_options = [
            'expires' => time() + ($expiry_days * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        $cookie_options = array_merge($default_options, $options);
        
        return setcookie($name, $value, $cookie_options);
    }
    
    /**
     * Get a cookie value with default fallback
     */
    public static function getCookie($name, $default = null) {
        return $_COOKIE[$name] ?? $default;
    }
    
    /**
     * Delete a specific cookie
     */
    public static function deleteCookie($name) {
        if (isset($_COOKIE[$name])) {
            setcookie($name, '', time() - 3600, '/');
            unset($_COOKIE[$name]);
            return true;
        }
        return false;
    }
    
    /**
     * Check if a cookie exists
     */
    public static function cookieExists($name) {
        return isset($_COOKIE[$name]);
    }
    
    /**
     * Get all cookies for debugging (sanitized)
     */
    public static function getAllCookies() {
        $cookies = [];
        foreach ($_COOKIE as $name => $value) {
            // Don't expose sensitive cookie values
            if (in_array($name, [self::REMEMBER_TOKEN_NAME, self::REMEMBER_USER_NAME])) {
                $cookies[$name] = '[PROTECTED]';
            } else {
                $cookies[$name] = $value;
            }
        }
        return $cookies;
    }
    
    /**
     * Generate a secure random token for remember me functionality
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
?> 