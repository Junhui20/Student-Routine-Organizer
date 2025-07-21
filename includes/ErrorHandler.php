<?php

/**
 * Comprehensive Error Handler Class
 * Practical Topic: PHP Error Handling
 */
class ErrorHandler {
    
    private static $instance = null;
    private $log_errors_to_db = true;
    private $log_errors_to_file = true;
    private $display_errors = false;
    private $error_log_file;
    
    private function __construct() {
        $this->error_log_file = __DIR__ . '/../logs/error.log';
        $this->createLogDirectory();
        $this->setupErrorHandling();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize error handling system
     */
    public static function initialize($display_errors = false) {
        $handler = self::getInstance();
        $handler->display_errors = $display_errors;
        
        // Set error reporting level
        error_reporting(E_ALL);
        ini_set('display_errors', $display_errors ? 1 : 0);
        ini_set('log_errors', 1);
        
        return $handler;
    }
    
    /**
     * Set up error handling
     */
    private function setupErrorHandling() {
        // Set custom error handler
        set_error_handler([$this, 'handleError']);
        
        // Set custom exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Set shutdown function for fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Create log directory
     */
    private function createLogDirectory() {
        $log_dir = dirname($this->error_log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
            
            // Create .htaccess for security
            $htaccess = $log_dir . '/.htaccess';
            file_put_contents($htaccess, "Deny from all");
        }
    }
    
    /**
     * Handle PHP errors
     */
    public function handleError($severity, $message, $file, $line) {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error_data = [
            'type' => 'PHP_ERROR',
            'severity' => $this->getErrorSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $this->getUserIP()
        ];
        
        $this->logError($error_data);
        
        // Display error if enabled
        if ($this->display_errors) {
            $this->displayError($error_data);
        }
        
        // For fatal errors, stop execution
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $this->showErrorPage('A fatal error occurred');
            exit();
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public function handleException($exception) {
        $error_data = [
            'type' => 'EXCEPTION',
            'severity' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $this->getUserIP()
        ];
        
        $this->logError($error_data);
        
        if ($this->display_errors) {
            $this->displayError($error_data);
        } else {
            $this->showErrorPage('An unexpected error occurred');
        }
    }
    
    /**
     * Handle fatal errors during shutdown
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $error_data = [
                'type' => 'FATAL_ERROR',
                'severity' => $this->getErrorSeverityName($error['type']),
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'trace' => [],
                'timestamp' => date('Y-m-d H:i:s'),
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $this->getUserIP()
            ];
            
            $this->logError($error_data);
            
            if (!$this->display_errors) {
                $this->showErrorPage('A fatal error occurred');
            }
        }
    }
    
    /**
     * Log application-specific errors
     */
    public static function logApplicationError($message, $type = 'APPLICATION', $context = []) {
        $handler = self::getInstance();
        
        $error_data = [
            'type' => $type,
            'severity' => 'ERROR',
            'message' => $message,
            'file' => $context['file'] ?? '',
            'line' => $context['line'] ?? 0,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip' => $handler->getUserIP(),
            'context' => $context
        ];
        
        $handler->logError($error_data);
    }
    
    /**
     * Log database errors
     */
    public static function logDatabaseError($message, $query = '', $params = []) {
        self::logApplicationError($message, 'DATABASE', [
            'query' => $query,
            'params' => $params
        ]);
    }
    
    /**
     * Log authentication errors
     */
    public static function logAuthError($message, $username = '', $context = []) {
        self::logApplicationError($message, 'AUTHENTICATION', array_merge($context, [
            'username' => $username
        ]));
    }
    

    
    /**
     * Log error to file and database
     */
    private function logError($error_data) {
        try {
            // Log to file
            if ($this->log_errors_to_file) {
                $this->logToFile($error_data);
            }
            
            // Log to database
            if ($this->log_errors_to_db) {
                $this->logToDatabase($error_data);
            }
        } catch (Exception $e) {
            // Fallback logging if primary logging fails
            error_log("Error Handler Failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log error to file
     */
    private function logToFile($error_data) {
        $log_entry = sprintf(
            "[%s] %s: %s in %s:%d\n",
            $error_data['timestamp'],
            $error_data['type'],
            $error_data['message'],
            basename($error_data['file']),
            $error_data['line']
        );
        
        // Add trace for serious errors
        if (in_array($error_data['type'], ['EXCEPTION', 'FATAL_ERROR'])) {
            $log_entry .= "Stack trace:\n";
            foreach ($error_data['trace'] as $index => $trace) {
                $log_entry .= sprintf(
                    "#%d %s(%d): %s\n",
                    $index,
                    $trace['file'] ?? '[internal]',
                    $trace['line'] ?? 0,
                    ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? '')
                );
            }
        }
        
        $log_entry .= "URL: " . $error_data['url'] . "\n";
        $log_entry .= "IP: " . $error_data['ip'] . "\n\n";
        
        file_put_contents($this->error_log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log error to database
     */
    private function logToDatabase($error_data) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            // Get current user if session exists
            $user_id = null;
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
            }
            
            $stmt = $conn->prepare("
                INSERT INTO error_logs 
                (user_id, error_type, error_message, error_file, error_line, stack_trace, request_uri, user_agent, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $error_data['type'] . ':' . $error_data['severity'],
                $error_data['message'],
                $error_data['file'],
                $error_data['line'],
                json_encode($error_data['trace']),
                $error_data['url'],
                $error_data['user_agent'],
                $error_data['ip']
            ]);
        } catch (Exception $e) {
            // Don't let database logging errors break the application
            error_log("Database error logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Display error for debugging
     */
    private function displayError($error_data) {
        echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 10px; border-radius: 4px;'>";
        echo "<h4 style='color: #d32f2f; margin: 0 0 10px 0;'>{$error_data['type']}: {$error_data['severity']}</h4>";
        echo "<p style='margin: 5px 0;'><strong>Message:</strong> " . htmlspecialchars($error_data['message']) . "</p>";
        echo "<p style='margin: 5px 0;'><strong>File:</strong> " . htmlspecialchars($error_data['file']) . " (Line: {$error_data['line']})</p>";
        echo "<p style='margin: 5px 0;'><strong>Time:</strong> {$error_data['timestamp']}</p>";
        echo "</div>";
    }
    
    /**
     * Show user-friendly error page
     */
    private function showErrorPage($message) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Student Routine Organizer</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .error-container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .error-icon { font-size: 64px; color: #f44336; margin-bottom: 20px; }
        h1 { color: #333; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #5a6fd8; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Oops! Something went wrong</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <p>We apologize for the inconvenience. The error has been logged and will be reviewed.</p>
        <a href="/" class="btn">Return to Home</a>
    </div>
</body>
</html>';
    }
    
    /**
     * Get error severity name
     */
    private function getErrorSeverityName($severity) {
        $severities = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $severities[$severity] ?? 'UNKNOWN';
    }
    
    /**
     * Get user IP address
     */
    private function getUserIP() {
        $ip_fields = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = $_SERVER[$field];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get recent errors from database
     */
    public static function getRecentErrors($limit = 50, $error_type = null) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $sql = "SELECT * FROM error_logs";
            $params = [];
            
            if ($error_type) {
                $sql .= " WHERE error_type LIKE ?";
                $params[] = "%{$error_type}%";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear old error logs
     */
    public static function clearOldLogs($days_old = 30) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$days_old]);
            
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get error statistics
     */
    public static function getErrorStats() {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = new Database();
            $conn = $db->getConnection();
            
            $stats = [];
            
            // Total errors
            $stmt = $conn->query("SELECT COUNT(*) as total FROM error_logs");
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Errors by type
            $stmt = $conn->query("SELECT error_type, COUNT(*) as count FROM error_logs GROUP BY error_type ORDER BY count DESC LIMIT 10");
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent errors (last 24 hours)
            $stmt = $conn->query("SELECT COUNT(*) as recent FROM error_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
            
            return $stats;
        } catch (Exception $e) {
            return ['total' => 0, 'by_type' => [], 'recent' => 0];
        }
    }
}

// Initialize error handling when this file is included
if (!defined('ERROR_HANDLER_INITIALIZED')) {
    define('ERROR_HANDLER_INITIALIZED', true);
    ErrorHandler::initialize(false); // Set to true for development
}
?> 