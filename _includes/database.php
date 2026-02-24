<?php
/**
 * Database Connection Class
 * Singleton pattern for managing database connections
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    private function loadCredentials() {
        $envFile = __DIR__ . '/../db_config.php';
        if (file_exists($envFile)) {
            $cfg = require $envFile;
            $this->host = $cfg['host'] ?? 'localhost';
            $this->dbname = $cfg['dbname'] ?? 'stocksathi';
            $this->username = $cfg['username'] ?? 'root';
            $this->password = $cfg['password'] ?? '';
        } else {
            $this->host = 'localhost';
            $this->dbname = 'stocksathi';
            $this->username = 'root';
            $this->password = '';
        }
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->loadCredentials();
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Set organization_id in MySQL connection for multi-tenancy triggers
            try {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $orgId = $_SESSION['organization_id'] ?? null;
                if (!$orgId && class_exists('Session')) {
                    $orgId = Session::getOrganizationId();
                }
                if ($orgId) {
                    $this->conn->exec("SET @current_org_id = " . intval($orgId));
                }
            } catch (Exception $e) {
                // Ignore session errors during DB init
            }
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
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
     * Get PDO connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a query and return all results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a query and return single result
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute an insert/update/delete query
     * Returns last insert ID for INSERT, affected rows for UPDATE/DELETE
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            // For INSERT queries, return last insert ID
            if (stripos(trim($sql), 'INSERT') === 0) {
                return $this->conn->lastInsertId();
            }
            
            // For UPDATE/DELETE, return affected rows
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Execute error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
}
