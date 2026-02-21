<?php
/**
 * Permission Middleware
 * Checks if current user has required permission to access a resource
 */

class PermissionMiddleware {
    /**
     * Check if user has permission
     */
    public static function hasPermission($permissionName) {
        Session::start();
        
        if (!Session::isLoggedIn()) {
            return false;
        }
        
        $userId = Session::getUserId();
        $userRole = Session::getUserRole();
        
        // Super admin has all permissions
        if ($userRole === 'super_admin') {
            return true;
        }
        
        try {
            $db = Database::getInstance();
            
            // Get user's role ID
            $roleQuery = "SELECT id FROM roles WHERE name = ? LIMIT 1";
            $role = $db->queryOne($roleQuery, [$userRole]);
            
            if (!$role) {
                return false;
            }
            
            // Check if role has this permission
            $permQuery = "SELECT COUNT(*) as has_permission 
                         FROM role_permissions rp
                         INNER JOIN permissions p ON rp.permission_id = p.id
                         WHERE rp.role_id = ? AND p.name = ?";
            
            $result = $db->queryOne($permQuery, [$role['id'], $permissionName]);
            
            return (int)$result['has_permission'] > 0;
            
        } catch (Exception $e) {
            error_log("Permission check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Require permission or redirect to access denied page
     */
    public static function requirePermission($permissionName, $redirectUrl = null) {
        if (!self::hasPermission($permissionName)) {
            if ($redirectUrl) {
                header('Location: ' . $redirectUrl);
            } else {
                http_response_code(403);
                die('Access Denied: You do not have permission to access this resource.');
            }
            exit;
        }
    }
    
    /**
     * Check if user has any of the given permissions
     */
    public static function hasAnyPermission($permissions = []) {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user has all of the given permissions
     */
    public static function hasAllPermissions($permissions = []) {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all permissions for current user
     */
    public static function getUserPermissions() {
        Session::start();
        
        if (!Session::isLoggedIn()) {
            return [];
        }
        
        $userRole = Session::getUserRole();
        
        // Super admin has all permissions
        if ($userRole === 'super_admin') {
            try {
                $db = Database::getInstance();
                return $db->query("SELECT name FROM permissions");
            } catch (Exception $e) {
                return [];
            }
        }
        
        try {
            $db = Database::getInstance();
            
            $query = "SELECT p.name, p.module, p.action, p.description
                     FROM permissions p
                     INNER JOIN role_permissions rp ON p.id = rp.permission_id
                     INNER JOIN roles r ON rp.role_id = r.id
                     WHERE r.name = ?
                     ORDER BY p.module, p.name";
            
            return $db->query($query, [$userRole]);
            
        } catch (Exception $e) {
            error_log("Get permissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get permissions grouped by module
     */
    public static function getUserPermissionsByModule() {
        $permissions = self::getUserPermissions();
        $grouped = [];
        
        foreach ($permissions as $perm) {
            $module = $perm['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $perm;
        }
        
        return $grouped;
    }
}
