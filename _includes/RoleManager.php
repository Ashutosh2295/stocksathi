<?php
/**
 * Role Manager Class
 * Manages user roles and permissions
 */

class RoleManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all roles
     */
    public function getAllRoles() {
        try {
            return $this->db->query("SELECT * FROM roles ORDER BY name");
        } catch (Exception $e) {
            error_log("Get roles error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get role by ID
     */
    public function getRoleById($roleId) {
        try {
            return $this->db->queryOne("SELECT * FROM roles WHERE id = ?", [$roleId]);
        } catch (Exception $e) {
            error_log("Get role error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get role by name
     */
    public function getRoleByName($roleName) {
        try {
            return $this->db->queryOne("SELECT * FROM roles WHERE name = ?", [$roleName]);
        } catch (Exception $e) {
            error_log("Get role error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get permissions for a role
     */
    public function getRolePermissions($roleId) {
        try {
            $query = "SELECT p.* 
                     FROM permissions p
                     INNER JOIN role_permissions rp ON p.id = rp.permission_id
                     WHERE rp.role_id = ?
                     ORDER BY p.module, p.name";
            
            return $this->db->query($query, [$roleId]);
        } catch (Exception $e) {
            error_log("Get role permissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Assign permission to role
     */
    public function assignPermissionToRole($roleId, $permissionId) {
        try {
            // Check if already exists
            $exists = $this->db->queryOne(
                "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permissionId]
            );
            
            if ($exists) {
                return true; // Already assigned
            }
            
            $this->db->execute(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                [$roleId, $permissionId]
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Assign permission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove permission from role
     */
    public function removePermissionFromRole($roleId, $permissionId) {
        try {
            $this->db->execute(
                "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?",
                [$roleId, $permissionId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Remove permission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign role to user
     */
    public function assignRoleToUser($userId, $roleName) {
        try {
            $this->db->execute(
                "UPDATE users SET role = ? WHERE id = ?",
                [$roleName, $userId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Assign role to user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($roleName) {
        try {
            return $this->db->query(
                "SELECT id, username, email, full_name, status FROM users WHERE role = ? ORDER BY username",
                [$roleName]
            );
        } catch (Exception $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new role
     */
    public function createRole($name, $displayName, $description = null) {
        try {
            $this->db->execute(
                "INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?)",
                [$name, $displayName, $description]
            );
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Create role error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update role
     */
    public function updateRole($roleId, $displayName, $description = null) {
        try {
            $this->db->execute(
                "UPDATE roles SET display_name = ?, description = ? WHERE id = ?",
                [$displayName, $description, $roleId]
            );
            return true;
        } catch (Exception $e) {
            error_log("Update role error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete role
     */
    public function deleteRole($roleId) {
        try {
            // Check if any users have this role
            $usersCount = $this->db->queryOne(
                "SELECT COUNT(*) as count FROM users u 
                 INNER JOIN roles r ON u.role = r.name 
                 WHERE r.id = ?",
                [$roleId]
            );
            
            if ((int)$usersCount['count'] > 0) {
                throw new Exception("Cannot delete role with assigned users");
            }
            
            $this->db->execute("DELETE FROM roles WHERE id = ?", [$roleId]);
            return true;
        } catch (Exception $e) {
            error_log("Delete role error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all permissions
     */
    public function getAllPermissions() {
        try {
            return $this->db->query("SELECT * FROM permissions ORDER BY module, name");
        } catch (Exception $e) {
            error_log("Get permissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get permissions grouped by module
     */
    public function getPermissionsByModule() {
        $permissions = $this->getAllPermissions();
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
    
    /**
     * Sync role permissions (replace all with new set)
     */
    public function syncRolePermissions($roleId, $permissionIds = []) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Remove all existing permissions
            $this->db->execute("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
            
            // Add new permissions
            if (!empty($permissionIds)) {
                foreach ($permissionIds as $permissionId) {
                    $this->db->execute(
                        "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                        [$roleId, $permissionId]
                    );
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Sync permissions error: " . $e->getMessage());
            return false;
        }
    }
}
