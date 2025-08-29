<?php
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "student_routine_db";
    public $connection;
    
    public function __construct() {
        $this->connection = null;
        
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Set MySQL timezone to match PHP timezone
            $phpTimezone = date_default_timezone_get();
            $this->connection->exec("SET time_zone = '" . date('P') . "'");
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Create global $pdo for backward compatibility
try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch(Exception $e) {
    // Handle connection error
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}
?> 