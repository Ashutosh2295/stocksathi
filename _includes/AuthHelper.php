<?php
/**
 * Auth Helper Class
 * Provides authentication utilities
 */
class AuthHelper {
    /**
     * Get database connection
     */
    private static function getDB() {
        try {
            $db = Database::getInstance();
            return $db->getConnection();
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Login user
     */
    public static function login($username, $password) {
        $db = self::getDB();
        if (!$db) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            if (!self::verifyPassword($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Set session with organization_id
            Session::setUser($user['id'], $user['username'], $user['role'], $user['organization_id']);
            
            // Update last login
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name'],
                    'organization_id' => $user['organization_id']
                ]
            ];
        } catch (Exception $e) {
            $logFile = __DIR__ . '/../login_debug.txt';
            $errorDetails = "Exception Message: " . $e->getMessage() . "\n";
            $errorDetails .= "Exception File: " . $e->getFile() . "\n";
            $errorDetails .= "Exception Line: " . $e->getLine() . "\n";
            $errorDetails .= "Stack Trace: " . $e->getTraceAsString() . "\n";
            file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $errorDetails, FILE_APPEND);
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
    
    /**
     * Register new user
     */
    public static function register($data) {
        $db = self::getDB();
        if (!$db) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        try {
            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Insert new user
            $hashedPassword = self::hashPassword($data['password']);
            $insertStmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, role, phone, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $insertStmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['full_name'] ?? $data['username'],
                $data['role'] ?? 'user',
                $data['phone'] ?? null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred'];
        }
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        Session::clearUser();
        Session::destroy();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        return Session::isLoggedIn();
    }
    
    /**
     * Get current user
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => Session::getUserId(),
            'username' => Session::getUserName(),
            'role' => Session::getUserRole()
        ];
    }
}
