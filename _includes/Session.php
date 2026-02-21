<?php
/**
 * Session Helper Class
 * Manages user sessions and authentication state
 */
class Session {
    /**
     * Start session if not already started
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::start();
        session_destroy();
        $_SESSION = [];
    }
    
    /**
     * Get user ID
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Get user role
     */
    public static function getUserRole() {
        return self::get('role', 'user');
    }
    
    /**
     * Get user name
     */
    public static function getUserName() {
        return self::get('username');
    }
    
    /**
     * Get organization ID
     */
    public static function getOrganizationId() {
        return self::get('organization_id');
    }
    
    /**
     * Set user session data
     */
    public static function setUser($userId, $username, $role, $organizationId = null) {
        self::set('user_id', $userId);
        self::set('username', $username);
        self::set('role', $role);
        self::set('organization_id', $organizationId);
        self::set('login_time', time());
    }
    
    /**
     * Clear user session data
     */
    public static function clearUser() {
        self::remove('user_id');
        self::remove('username');
        self::remove('role');
        self::remove('organization_id');
        self::remove('login_time');
    }
    
    /**
     * Get complete user data as array
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => self::getUserId(),
            'username' => self::getUserName(),
            'name' => self::getUserName(), // Alias for compatibility
            'role' => self::getUserRole(),
            'organization_id' => self::getOrganizationId()
        ];
    }
    
    /**
     * Set a flash message
     * Flash messages are one-time messages that are shown once and then cleared
     * Supports: setFlash('message', 'type') or setFlash('key', 'message', 'type')
     */
    public static function setFlash($messageOrKey, $typeOrMessage = 'success', $type = null) {
        self::start();
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        // Simple 2-param format: setFlash('message', 'type')
        if ($type === null) {
            $_SESSION['flash_messages']['_default'] = [
                'message' => $messageOrKey,
                'type' => $typeOrMessage
            ];
        } else {
            // Full 3-param format: setFlash('key', 'message', 'type')
            $_SESSION['flash_messages'][$messageOrKey] = [
                'message' => $typeOrMessage,
                'type' => $type
            ];
        }
    }
    
    /**
     * Get a flash message and remove it from session
     * Returns array with 'message' and 'type' keys, or null
     */
    public static function getFlash($key = null) {
        self::start();
        
        // If no key specified, try to get the default flash message first
        if ($key === null) {
            // First try the simple format
            if (isset($_SESSION['flash_messages']['_default'])) {
                $message = $_SESSION['flash_messages']['_default'];
                unset($_SESSION['flash_messages']['_default']);
                return $message;
            }
            // Return first available message if any
            if (!empty($_SESSION['flash_messages'])) {
                $key = array_key_first($_SESSION['flash_messages']);
                $message = $_SESSION['flash_messages'][$key];
                unset($_SESSION['flash_messages'][$key]);
                return $message;
            }
            return null;
        }
        
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if a flash message exists
     */
    public static function hasFlash($key = null) {
        self::start();
        
        if ($key === null) {
            return !empty($_SESSION['flash_messages']);
        }
        
        return isset($_SESSION['flash_messages'][$key]);
    }
    
    /**
     * Clear all flash messages
     */
    public static function clearFlash() {
        self::start();
        unset($_SESSION['flash_messages']);
    }
}
