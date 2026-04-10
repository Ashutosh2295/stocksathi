<?php
/**
 * Authentication Configuration
 * Contains constants and settings for authentication
 */

// Database connection - Include config
require_once __DIR__ . '/config.php';

// Authentication settings
define('AUTH_TOKEN_EXPIRY', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 7200); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// User roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');  
define('ROLE_MANAGER', 'manager');
define('ROLE_USER', 'user');

// Default redirect pages
define('DEFAULT_LOGIN_REDIRECT', BASE_PATH . '/index.php');
define('SUPER_ADMIN_REDIRECT', BASE_PATH . '/super-admin/dashboard.php');

/**
 * Get database connection
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $host = 'localhost';
        $dbname = 'stocksathi';
        $username = 'root';
        $password = '';
        
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}
