<?php
/**
 * RBAC Seeder - Ensures default roles and permissions exist.
 * Called after new organization registration so new super admins see all roles & permissions.
 */
class RBACSeeder {
    /**
     * Ensure default roles, permissions, and role-permission mappings exist (global).
     * Safe to call multiple times (idempotent). Does not wipe existing data.
     */
    public static function seedIfNeeded() {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            self::ensureTables($conn);
            self::ensureRoles($conn);
            self::ensurePermissions($conn);
            self::ensureSuperAdminHasAllPermissions($conn);
            self::ensureOtherRolePermissions($conn);
            return true;
        } catch (Exception $e) {
            error_log("RBACSeeder: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create roles and permissions for a newly registered organization.
     * New orgs see only these roles (no shared/demo data).
     */
    public static function seedForOrganization($organizationId) {
        if (empty($organizationId) || !is_numeric($organizationId)) {
            return false;
        }
        $organizationId = (int) $organizationId;
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            self::ensureTables($conn);
            self::ensureRolesTableHasOrganizationId($conn);
            self::ensurePermissions($conn);
            $roleIds = self::createRolesForOrganization($conn, $organizationId);
            self::assignAllPermissionsForOrgRoles($conn, $roleIds);
            return true;
        } catch (Exception $e) {
            error_log("RBACSeeder::seedForOrganization: " . $e->getMessage());
            return false;
        }
    }

    private static function ensureRolesTableHasOrganizationId($conn) {
        try {
            $conn->exec("ALTER TABLE roles ADD COLUMN organization_id INT(11) NULL DEFAULT NULL AFTER id");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            }
        }
        try {
            $conn->exec("ALTER TABLE roles DROP INDEX name");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), '1091') === false && strpos($e->getMessage(), 'Can\'t DROP') === false) {
                // 1091 = Can't DROP; ignore if key doesn't exist
            }
        }
        try {
            $conn->exec("ALTER TABLE roles ADD UNIQUE KEY unique_org_role (organization_id, name)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false) {
                // ignore if key already exists
            }
        }
        try {
            $conn->exec("ALTER TABLE roles ADD INDEX idx_roles_organization_id (organization_id)");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                // ignore
            }
        }
    }

    /** @return array name => id for the org's roles */
    private static function createRolesForOrganization($conn, $organizationId) {
        $roles = [
            ['super_admin', 'Super Administrator', 'Full system access'],
            ['admin', 'Administrator', 'Administrative access'],
            ['store_manager', 'Store Manager', 'Store operations'],
            ['sales_executive', 'Sales Executive', 'Sales and billing'],
            ['accountant', 'Accountant', 'Finance operations'],
            ['warehouse_manager', 'Warehouse Manager', 'Inventory operations']
        ];
        $stmt = $conn->prepare("INSERT INTO roles (organization_id, name, display_name, description) VALUES (?, ?, ?, ?)");
        foreach ($roles as $r) {
            try {
                $stmt->execute([$organizationId, $r[0], $r[1], $r[2]]);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
        $roleIds = [];
        $stmt = $conn->prepare("SELECT id, name FROM roles WHERE organization_id = ?");
        $stmt->execute([$organizationId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roleIds[$row['name']] = (int) $row['id'];
        }
        return $roleIds;
    }

    private static function assignAllPermissionsForOrgRoles($conn, array $roleIds) {
        $allPermNames = array_column(self::getDefaultPermissions(), 0);
        if (isset($roleIds['super_admin'])) {
            self::assignPermissionsByName($conn, $roleIds['super_admin'], $allPermNames);
        }
        $adminPerms = ['view_admin_dashboard','view_store_dashboard','view_sales_dashboard','view_products','create_products','edit_products','delete_products','view_purchase_price','view_categories','manage_categories','view_brands','manage_brands','view_stock','adjust_stock','transfer_stock','stock_in','stock_out','create_invoice','edit_invoice','delete_invoice','view_all_invoices','view_own_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','edit_customers','delete_customers','view_customer_balance','view_suppliers','create_suppliers','edit_suppliers','view_expenses','create_expenses','approve_expenses','view_sales_reports','view_stock_reports','view_financial_reports','view_users','create_users','edit_users','view_settings','view_activity_logs','assign_roles'];
        self::assignPermissionsByName($conn, $roleIds['admin'] ?? 0, $adminPerms);
        self::assignPermissionsByName($conn, $roleIds['store_manager'] ?? 0, ['view_store_dashboard','view_sales_dashboard','view_products','view_purchase_price','view_categories','view_brands','view_stock','adjust_stock','stock_in','stock_out','create_invoice','edit_invoice','view_all_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','edit_customers','view_customer_balance','view_suppliers','view_expenses','create_expenses','view_sales_reports','view_stock_reports','view_activity_logs']);
        self::assignPermissionsByName($conn, $roleIds['sales_executive'] ?? 0, ['view_sales_dashboard','view_products','view_categories','view_brands','view_stock','create_invoice','view_own_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','view_customer_balance','view_sales_reports']);
        self::assignPermissionsByName($conn, $roleIds['accountant'] ?? 0, ['view_admin_dashboard','view_sales_reports','view_financial_reports','view_expenses','view_all_invoices','view_customers','view_customer_balance','view_suppliers','view_activity_logs']);
        self::assignPermissionsByName($conn, $roleIds['warehouse_manager'] ?? 0, ['view_store_dashboard','view_products','view_categories','view_brands','view_stock','adjust_stock','transfer_stock','stock_in','stock_out','view_suppliers','view_stock_reports','view_activity_logs']);
    }

    private static function ensureTables($conn) {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $conn->exec("
            CREATE TABLE IF NOT EXISTS permissions (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                module VARCHAR(50) NOT NULL,
                action VARCHAR(50) NOT NULL,
                description TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY name (name),
                KEY idx_module (module),
                KEY idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $conn->exec("
            CREATE TABLE IF NOT EXISTS role_permissions (
                id INT(11) NOT NULL AUTO_INCREMENT,
                role_id INT(11) NOT NULL,
                permission_id INT(11) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_role_permission (role_id, permission_id),
                KEY idx_role_id (role_id),
                KEY idx_permission_id (permission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private static function ensureRoles($conn) {
        $roles = [
            ['super_admin', 'Super Administrator', 'Full system access'],
            ['admin', 'Administrator', 'Administrative access'],
            ['store_manager', 'Store Manager', 'Store operations'],
            ['sales_executive', 'Sales Executive', 'Sales and billing'],
            ['accountant', 'Accountant', 'Finance operations'],
            ['warehouse_manager', 'Warehouse Manager', 'Inventory operations']
        ];
        $stmt = $conn->prepare("INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description)");
        foreach ($roles as $r) {
            try {
                $stmt->execute($r);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
    }

    private static function ensurePermissions($conn) {
        $list = self::getDefaultPermissions();
        $stmt = $conn->prepare("INSERT IGNORE INTO permissions (name, module, action, description) VALUES (?, ?, ?, ?)");
        foreach ($list as $p) {
            $stmt->execute($p);
        }
    }

    private static function getDefaultPermissions() {
        return [
            ['view_admin_dashboard', 'dashboard', 'view', 'View admin dashboard'],
            ['view_store_dashboard', 'dashboard', 'view', 'View store dashboard'],
            ['view_sales_dashboard', 'dashboard', 'view', 'View sales dashboard'],
            ['view_products', 'products', 'view', 'View products'],
            ['create_products', 'products', 'create', 'Create products'],
            ['edit_products', 'products', 'edit', 'Edit products'],
            ['delete_products', 'products', 'delete', 'Delete products'],
            ['view_purchase_price', 'products', 'view', 'View purchase prices'],
            ['view_categories', 'categories', 'view', 'View categories'],
            ['manage_categories', 'categories', 'edit', 'Manage categories'],
            ['view_brands', 'brands', 'view', 'View brands'],
            ['manage_brands', 'brands', 'edit', 'Manage brands'],
            ['view_stock', 'inventory', 'view', 'View stock'],
            ['adjust_stock', 'inventory', 'edit', 'Adjust stock'],
            ['transfer_stock', 'inventory', 'edit', 'Transfer stock'],
            ['stock_in', 'inventory', 'create', 'Stock in'],
            ['stock_out', 'inventory', 'create', 'Stock out'],
            ['create_invoice', 'sales', 'create', 'Create invoices'],
            ['edit_invoice', 'sales', 'edit', 'Edit invoices'],
            ['delete_invoice', 'sales', 'delete', 'Delete invoices'],
            ['view_all_invoices', 'sales', 'view', 'View all invoices'],
            ['view_own_invoices', 'sales', 'view', 'View own invoices'],
            ['give_discount', 'sales', 'edit', 'Give discounts'],
            ['process_returns', 'sales', 'edit', 'Process returns'],
            ['create_quotation', 'sales', 'create', 'Create quotations'],
            ['view_quotations', 'sales', 'view', 'View quotations'],
            ['view_customers', 'customers', 'view', 'View customers'],
            ['create_customers', 'customers', 'create', 'Create customers'],
            ['edit_customers', 'customers', 'edit', 'Edit customers'],
            ['delete_customers', 'customers', 'delete', 'Delete customers'],
            ['view_customer_balance', 'customers', 'view', 'View balances'],
            ['view_suppliers', 'suppliers', 'view', 'View suppliers'],
            ['create_suppliers', 'suppliers', 'create', 'Create suppliers'],
            ['edit_suppliers', 'suppliers', 'edit', 'Edit suppliers'],
            ['view_expenses', 'expenses', 'view', 'View expenses'],
            ['create_expenses', 'expenses', 'create', 'Create expenses'],
            ['approve_expenses', 'expenses', 'approve', 'Approve expenses'],
            ['view_sales_reports', 'reports', 'view', 'View sales reports'],
            ['view_stock_reports', 'reports', 'view', 'View stock reports'],
            ['view_financial_reports', 'reports', 'view', 'View financial reports'],
            ['view_users', 'users', 'view', 'View users'],
            ['create_users', 'users', 'create', 'Create users'],
            ['edit_users', 'users', 'edit', 'Edit users'],
            ['delete_users', 'users', 'delete', 'Delete users'],
            ['assign_roles', 'users', 'edit', 'Assign roles to users'],
            ['view_settings', 'settings', 'view', 'View settings'],
            ['edit_settings', 'settings', 'edit', 'Edit settings'],
            ['view_activity_logs', 'system', 'view', 'View activity logs']
        ];
    }

    /** Give super_admin role all permissions (insert missing only). */
    private static function ensureSuperAdminHasAllPermissions($conn) {
        $role = $conn->query("SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$role) {
            return;
        }
        $roleId = (int) $role['id'];
        $perms = $conn->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($perms)) {
            return;
        }
        $insert = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($perms as $permId) {
            $insert->execute([$roleId, $permId]);
        }
    }

    /** Ensure admin, store_manager, sales_executive, accountant, warehouse_manager have default permissions (insert missing only). */
    private static function ensureOtherRolePermissions($conn) {
        $roleIds = [];
        $r = $conn->query("SELECT id, name FROM roles");
        while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
            $roleIds[$row['name']] = (int) $row['id'];
        }
        self::assignPermissionsByName($conn, $roleIds['admin'] ?? 0, [
            'view_admin_dashboard','view_store_dashboard','view_sales_dashboard','view_products','create_products','edit_products','delete_products','view_purchase_price','view_categories','manage_categories','view_brands','manage_brands','view_stock','adjust_stock','transfer_stock','stock_in','stock_out','create_invoice','edit_invoice','delete_invoice','view_all_invoices','view_own_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','edit_customers','delete_customers','view_customer_balance','view_suppliers','create_suppliers','edit_suppliers','view_expenses','create_expenses','approve_expenses','view_sales_reports','view_stock_reports','view_financial_reports','view_users','create_users','edit_users','view_settings','view_activity_logs','assign_roles'
        ]);
        self::assignPermissionsByName($conn, $roleIds['store_manager'] ?? 0, [
            'view_store_dashboard','view_sales_dashboard','view_products','view_purchase_price','view_categories','view_brands','view_stock','adjust_stock','stock_in','stock_out','create_invoice','edit_invoice','view_all_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','edit_customers','view_customer_balance','view_suppliers','view_expenses','create_expenses','view_sales_reports','view_stock_reports','view_activity_logs'
        ]);
        self::assignPermissionsByName($conn, $roleIds['sales_executive'] ?? 0, [
            'view_sales_dashboard','view_products','view_categories','view_brands','view_stock','create_invoice','view_own_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','view_customer_balance','view_sales_reports'
        ]);
        self::assignPermissionsByName($conn, $roleIds['accountant'] ?? 0, [
            'view_admin_dashboard','view_sales_reports','view_financial_reports','view_expenses','view_all_invoices','view_customers','view_customer_balance','view_suppliers','view_activity_logs'
        ]);
        self::assignPermissionsByName($conn, $roleIds['warehouse_manager'] ?? 0, [
            'view_store_dashboard','view_products','view_categories','view_brands','view_stock','adjust_stock','transfer_stock','stock_in','stock_out','view_suppliers','view_stock_reports','view_activity_logs'
        ]);
        // Extra roles (from other migrations) - give read-only so they don't show 0 permissions
        $readOnlyPerms = ['view_admin_dashboard','view_store_dashboard','view_sales_dashboard','view_products','view_purchase_price','view_categories','view_brands','view_stock','view_all_invoices','view_quotations','view_customers','view_customer_balance','view_suppliers','view_expenses','view_sales_reports','view_stock_reports','view_financial_reports','view_users','view_settings','view_activity_logs'];
        self::assignPermissionsByName($conn, $roleIds['auditor'] ?? 0, $readOnlyPerms);
        self::assignPermissionsByName($conn, $roleIds['purchase_manager'] ?? 0, ['view_products','view_categories','view_brands','view_suppliers','view_stock','view_expenses','create_expenses','view_sales_reports','view_activity_logs']);
        self::assignPermissionsByName($conn, $roleIds['delivery_boy'] ?? 0, ['view_sales_dashboard','view_own_invoices','view_quotations','view_customers','view_activity_logs']);
    }

    private static function assignPermissionsByName($conn, $roleId, array $permissionNames) {
        if ($roleId <= 0 || empty($permissionNames)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($permissionNames), '?'));
        $stmt = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) SELECT ?, id FROM permissions WHERE name IN ($placeholders)");
        $stmt->execute(array_merge([$roleId], $permissionNames));
    }
}
