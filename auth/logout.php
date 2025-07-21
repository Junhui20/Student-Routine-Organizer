<?php
require_once '../includes/SessionManager.php';

try {
    SessionManager::initializeSession();
    SessionManager::logoutUser();
} catch (Exception $e) {
    // Fallback logout if advanced session fails
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Clear remember me cookies
    require_once '../includes/CookieManager.php';
    CookieManager::clearRememberMeCookies();
}

// Redirect to home page with logout message
header("Location: ../index.php?logout=1");
exit();
?> 