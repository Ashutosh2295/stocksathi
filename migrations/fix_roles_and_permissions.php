<?php
/**
 * Fix Roles and Permissions
 * Ensures all required roles exist and have basic permissions.
 */

require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/config.php';

$db = Database::getInstance();

echo "Starting role cleanup and permission sync...<br>";

try {
    // 1. Roles to ensure
    $requiredRoles = [
        ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'Full system access'],
        ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'General administration'],
        ['name' => 'manager', 'display_name' => 'General Manager', 'description' => 'Branch/Area management'],
        ['name' => 'hr', 'display_name' => 'HR Manager', 'description' => 'Human Resources and Payroll'],
        ['name' => 'accountant', 'display_name' => 'Accountant', 'description' => 'Financial records and GST'],
        ['name' => 'auditor', 'display_name' => 'Auditor', 'description' => 'Read-only financial audit'],
        ['name' => 'store_manager', 'display_name' => 'Store Manager', 'description' => 'Inventory and local operations'],
        ['name' => 'warehouse_manager', 'display_name' => 'Warehouse Manager', 'description' => 'Bulk stock and warehouse logistics'],
        ['name' => 'purchase_manager', 'display_name' => 'Purchase Manager', 'description' => 'Suppliers and PO management'],
        ['name' => 'sales_executive', 'display_name' => 'Sales Executive', 'description' => 'Sales and billing'],
        ['name' => 'user', 'display_name' => 'Standard User', 'description' => 'Basic access']
    ];

    // Remove delivery_boy if exists
    $db->execute("DELETE FROM roles WHERE name = 'delivery_boy'");
    echo "Removed 'delivery_boy' role if it existed.<br>";

    // Ensure all roles exist
    foreach ($requiredRoles as $role) {
        $exists = $db->queryOne("SELECT id FROM roles WHERE name = ?", [$role['name']]);
        if (!$exists) {
            $db->execute(
                "INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?)",
                [$role['name'], $role['display_name'], $role['description']]
            );
            echo "Created role: {$role['name']}<br>";
        } else {
            $db->execute(
                "UPDATE roles SET display_name = ?, description = ? WHERE name = ?",
                [$role['display_name'], $role['description'], $role['name']]
            );
            echo "Updated role: {$role['name']}<br>";
        }
    }

    // 2. Permissions to ensure (HR focus as requested)
    $hrPermissions = [
        ['name' => 'view_hr_dashboard', 'module' => 'hrm', 'action' => 'view', 'description' => 'View HR dashboard'],
        ['name' => 'view_employees', 'module' => 'hrm', 'action' => 'view', 'description' => 'View employees list'],
        ['name' => 'manage_employees', 'module' => 'hrm', 'action' => 'edit', 'description' => 'Add/Edit employees'],
        ['name' => 'view_attendance', 'module' => 'hrm', 'action' => 'view', 'description' => 'View attendance logs'],
        ['name' => 'manage_attendance', 'module' => 'hrm', 'action' => 'edit', 'description' => 'Mark/Edit attendance'],
        ['name' => 'view_leave', 'module' => 'hrm', 'action' => 'view', 'description' => 'View leave requests'],
        ['name' => 'manage_leave', 'module' => 'hrm', 'action' => 'edit', 'description' => 'Request/Edit leave'],
        ['name' => 'approve_leave', 'module' => 'hrm', 'action' => 'approve', 'description' => 'Approve or reject leave'],
        ['name' => 'view_payroll', 'module' => 'hrm', 'action' => 'view', 'description' => 'View payroll records']
    ];

    foreach ($hrPermissions as $perm) {
        $exists = $db->queryOne("SELECT id FROM permissions WHERE name = ?", [$perm['name']]);
        if (!$exists) {
            $db->execute(
                "INSERT INTO permissions (name, module, action, description) VALUES (?, ?, ?, ?)",
                [$perm['name'], $perm['module'], $perm['action'], $perm['description']]
            );
        }
    }
    echo "Ensured HR permissions metadata.<br>";

    // Link permissions to HR role
    $hrRole = $db->queryOne("SELECT id FROM roles WHERE name = 'hr'");
    if ($hrRole) {
        foreach ($hrPermissions as $perm) {
            $p = $db->queryOne("SELECT id FROM permissions WHERE name = ?", [$perm['name']]);
            if ($p) {
                $db->execute(
                    "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$hrRole['id'], $p['id']]
                );
            }
        }
        echo "Linked permissions to HR role.<br>";
    }

    // Link basic dashboard permissions to others
    $dashboardPerms = [
        'store_manager' => 'view_store_dashboard',
        'warehouse_manager' => 'view_warehouse_dashboard',
        'purchase_manager' => 'view_purchase_dashboard',
        'accountant' => 'view_financial_dashboard',
        'auditor' => 'view_audit_dashboard',
        'manager' => 'view_admin_dashboard',
        'sales_executive' => 'view_sales_dashboard'
    ];

    foreach ($dashboardPerms as $roleName => $permName) {
        $role = $db->queryOne("SELECT id FROM roles WHERE name = ?", [$roleName]);
        if ($role) {
            // Ensure permission exists
            $pExists = $db->queryOne("SELECT id FROM permissions WHERE name = ?", [$permName]);
            if (!$pExists) {
                $db->execute("INSERT INTO permissions (name, module, action, description) VALUES (?, 'dashboard', 'view', 'View specific dashboard')", [$permName]);
                $pId = $db->lastInsertId();
            } else {
                $pId = $pExists['id'];
            }
            $db->execute("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)", [$role['id'], $pId]);
        }
    }
    echo "Linked basic dashboard permissions to respective roles.<br>";

    echo "<strong>Success! All roles and permissions updated.</strong>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
