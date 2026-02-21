<?php
/**
 * Organization Helper Class
 * Provides organization-based data isolation utilities
 */
class OrganizationHelper {
    /**
     * Get current user's organization ID from session
     */
    public static function getCurrentOrganizationId() {
        return Session::getOrganizationId();
    }
    
    /**
     * Check if current user belongs to an organization
     */
    public static function hasOrganization() {
        return !empty(self::getCurrentOrganizationId());
    }
    
    /**
     * Get organization details
     */
    public static function getOrganization($organizationId = null) {
        if ($organizationId === null) {
            $organizationId = self::getCurrentOrganizationId();
        }
        
        if (empty($organizationId)) {
            return null;
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("
                SELECT * FROM organizations WHERE id = ? LIMIT 1
            ");
            $stmt->execute([$organizationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching organization: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Add organization filter to WHERE clause
     * Usage: $where = OrganizationHelper::addOrgFilter("WHERE status = 'active'");
     */
    public static function addOrgFilter($whereClause = "", $tableAlias = "") {
        $orgId = self::getCurrentOrganizationId();
        
        if (empty($orgId)) {
            return $whereClause;
        }
        
        $prefix = empty($tableAlias) ? "" : $tableAlias . ".";
        $orgCondition = "{$prefix}organization_id = " . intval($orgId);
        
        if (empty($whereClause) || stripos($whereClause, 'WHERE') === false) {
            return "WHERE " . $orgCondition;
        }
        
        return $whereClause . " AND " . $orgCondition;
    }
    
    /**
     * Get organization-filtered query
     * Automatically adds organization_id filter to queries
     */
    public static function filterQuery($baseQuery, $params = []) {
        $orgId = self::getCurrentOrganizationId();
        
        if (empty($orgId)) {
            return ['query' => $baseQuery, 'params' => $params];
        }
        
        // Add organization_id to WHERE clause
        if (stripos($baseQuery, 'WHERE') !== false) {
            $baseQuery = str_replace('WHERE', "WHERE organization_id = ? AND", $baseQuery);
        } else {
            // Add WHERE clause before ORDER BY, LIMIT, etc.
            $keywords = ['ORDER BY', 'LIMIT', 'GROUP BY', 'HAVING'];
            $position = false;
            
            foreach ($keywords as $keyword) {
                $pos = stripos($baseQuery, $keyword);
                if ($pos !== false && ($position === false || $pos < $position)) {
                    $position = $pos;
                }
            }
            
            if ($position !== false) {
                $baseQuery = substr_replace($baseQuery, " WHERE organization_id = ? ", $position, 0);
            } else {
                $baseQuery .= " WHERE organization_id = ?";
            }
        }
        
        // Add organization_id as first parameter
        array_unshift($params, $orgId);
        
        return ['query' => $baseQuery, 'params' => $params];
    }
    
    /**
     * Validate that a record belongs to current organization
     */
    public static function validateOwnership($table, $recordId) {
        $orgId = self::getCurrentOrganizationId();
        
        if (empty($orgId)) {
            return false;
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("
                SELECT id FROM {$table} 
                WHERE id = ? AND organization_id = ? 
                LIMIT 1
            ");
            $stmt->execute([$recordId, $orgId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error validating ownership: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get organization users
     */
    public static function getOrganizationUsers($organizationId = null) {
        if ($organizationId === null) {
            $organizationId = self::getCurrentOrganizationId();
        }
        
        if (empty($organizationId)) {
            return [];
        }
        
        try {
            $db = Database::getInstance();
            $stmt = $db->getConnection()->prepare("
                SELECT id, username, email, full_name, role, status, created_at
                FROM users 
                WHERE organization_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$organizationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching organization users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user is super admin of their organization
     */
    public static function isSuperAdmin() {
        return Session::getUserRole() === 'super_admin';
    }
    
    /**
     * Get organization statistics
     */
    public static function getOrganizationStats($organizationId = null) {
        if ($organizationId === null) {
            $organizationId = self::getCurrentOrganizationId();
        }
        
        if (empty($organizationId)) {
            return null;
        }
        
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stats = [];
            
            // Count users
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE organization_id = ?");
            $stmt->execute([$organizationId]);
            $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count products
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE organization_id = ?");
            $stmt->execute([$organizationId]);
            $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count customers
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE organization_id = ?");
            $stmt->execute([$organizationId]);
            $stats['customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Count invoices
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM invoices WHERE organization_id = ?");
            $stmt->execute([$organizationId]);
            $stats['invoices'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error fetching organization stats: " . $e->getMessage());
            return null;
        }
    }
}
