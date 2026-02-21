-- =====================================================
-- COMPLETE DATABASE RBAC SETUP
-- Adds Sales Executive and Admin roles with permissions
-- =====================================================

USE stocksathi;

-- =====================================================
-- 1. ADD PERMISSIONS TABLE IF NOT EXISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_module (module),
    KEY idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. ADD ROLE_PERMISSIONS TABLE IF NOT EXISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    role_id INT(11) NOT NULL,
    permission_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    KEY idx_role_id (role_id),
    KEY idx_permission_id (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ADD USER COLUMNS FOR RBAC (IF NOT EXISTS)
-- =====================================================

-- Add columns if they don't exist (wrapped in procedure to avoid errors)
DELIMITER //
CREATE PROCEDURE AddUserColumns()
BEGIN
    -- Check and add assigned_store_id
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'assigned_store_id') THEN
        ALTER TABLE users ADD COLUMN assigned_store_id INT(11) DEFAULT NULL;
    END IF;
    
    -- Check and add assigned_warehouse_id
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'assigned_warehouse_id') THEN
        ALTER TABLE users ADD COLUMN assigned_warehouse_id INT(11) DEFAULT NULL;
    END IF;
    
    -- Check and add can_give_discount
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'can_give_discount') THEN
        ALTER TABLE users ADD COLUMN can_give_discount DECIMAL(5,2) DEFAULT 0.00;
    END IF;
    
    -- Check and add max_discount_percent
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'max_discount_percent') THEN
        ALTER TABLE users ADD COLUMN max_discount_percent DECIMAL(5,2) DEFAULT 0.00;
    END IF;
    
    -- Check and add daily_sales_target
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'daily_sales_target') THEN
        ALTER TABLE users ADD COLUMN daily_sales_target DECIMAL(10,2) DEFAULT 0.00;
    END IF;
    
    -- Check and add commission_percent
    IF NOT EXISTS (SELECT * FROM information_schema.columns 
                   WHERE table_schema = 'stocksathi' AND table_name = 'users' 
                   AND column_name = 'commission_percent') THEN
        ALTER TABLE users ADD COLUMN commission_percent DECIMAL(5,2) DEFAULT 0.00;
    END IF;
END //
DELIMITER ;

CALL AddUserColumns();
DROP PROCEDURE IF EXISTS AddUserColumns;

-- =====================================================
-- 4. INSERT/UPDATE ROLES
-- =====================================================

INSERT INTO roles (name, display_name, description, permissions) VALUES
('super_admin', 'Super Administrator', 'Full system access with all permissions', '{"all": true}'),
('admin', 'Administrator', 'Administrative access to most features', '{"admin": true}'),
('store_manager', 'Store Manager', 'Manage store operations and daily sales', '{"store_ops": true}'),
('sales_executive', 'Sales Executive', 'Sales and billing operations', '{"sales": true}'),
('accountant', 'Accountant', 'Finance and GST compliance', '{"finance": true}'),
('warehouse_manager', 'Warehouse Manager', 'Inventory and warehouse operations', '{"inventory": true}')
ON DUPLICATE KEY UPDATE 
    display_name = VALUES(display_name),
    description = VALUES(description);

-- =====================================================
-- 5. INSERT ALL PERMISSIONS
-- =====================================================

INSERT IGNORE INTO permissions (name, module, action, description) VALUES
-- Dashboard Permissions
('view_admin_dashboard', 'dashboard', 'view', 'View admin dashboard with financial data'),
('view_store_dashboard', 'dashboard', 'view', 'View store manager dashboard'),
('view_sales_dashboard', 'dashboard', 'view', 'View sales executive dashboard'),
('view_accountant_dashboard', 'dashboard', 'view', 'View accountant dashboard'),

-- Product Permissions
('view_products', 'products', 'view', 'View products list and details'),
('create_products', 'products', 'create', 'Create new products'),
('edit_products', 'products', 'edit', 'Edit existing products'),
('delete_products', 'products', 'delete', 'Delete products'),
('view_purchase_price', 'products', 'view', 'View product purchase prices'),
('edit_selling_price', 'products', 'edit', 'Edit product selling prices'),

-- Category & Brand Permissions
('view_categories', 'categories', 'view', 'View categories'),
('manage_categories', 'categories', 'edit', 'Manage categories'),
('view_brands', 'brands', 'view', 'View brands'),
('manage_brands', 'brands', 'edit', 'Manage brands'),

-- Inventory Permissions
('view_stock', 'inventory', 'view', 'View stock levels'),
('adjust_stock', 'inventory', 'edit', 'Adjust stock quantities'),
('transfer_stock', 'inventory', 'edit', 'Transfer stock between locations'),
('view_all_warehouses', 'inventory', 'view', 'View all warehouse stock'),
('stock_in', 'inventory', 'create', 'Create stock in entries'),
('stock_out', 'inventory', 'create', 'Create stock out entries'),

-- Sales Permissions
('create_invoice', 'sales', 'create', 'Create sales invoices'),
('edit_invoice', 'sales', 'edit', 'Edit sales invoices'),
('delete_invoice', 'sales', 'delete', 'Delete sales invoices'),
('view_all_invoices', 'sales', 'view', 'View all invoices'),
('view_own_invoices', 'sales', 'view', 'View own invoices only'),
('give_discount', 'sales', 'edit', 'Give discounts on sales'),
('process_returns', 'sales', 'edit', 'Process sales returns'),
('create_quotation', 'sales', 'create', 'Create quotations'),
('view_quotations', 'sales', 'view', 'View quotations'),

-- Customer Permissions
('view_customers', 'customers', 'view', 'View customer list'),
('create_customers', 'customers', 'create', 'Create new customers'),
('edit_customers', 'customers', 'edit', 'Edit customer details'),
('delete_customers', 'customers', 'delete', 'Delete customers'),
('view_customer_balance', 'customers', 'view', 'View customer outstanding balance'),

-- Supplier Permissions
('view_suppliers', 'suppliers', 'view', 'View supplier list'),
('create_suppliers', 'suppliers', 'create', 'Create new suppliers'),
('edit_suppliers', 'suppliers', 'edit', 'Edit supplier details'),
('delete_suppliers', 'suppliers', 'delete', 'Delete suppliers'),

-- Expense Permissions
('view_expenses', 'expenses', 'view', 'View expense records'),
('create_expenses', 'expenses', 'create', 'Create new expenses'),
('approve_expenses', 'expenses', 'approve', 'Approve expenses'),
('delete_expenses', 'expenses', 'delete', 'Delete expenses'),

-- Report Permissions
('view_sales_reports', 'reports', 'view', 'View sales reports'),
('view_purchase_reports', 'reports', 'view', 'View purchase reports'),
('view_stock_reports', 'reports', 'view', 'View stock reports'),
('view_financial_reports', 'reports', 'view', 'View financial reports'),
('view_gst_reports', 'reports', 'view', 'View GST reports'),
('view_profit_loss', 'reports', 'view', 'View profit & loss reports'),

-- User Management Permissions
('view_users', 'users', 'view', 'View user list'),
('create_users', 'users', 'create', 'Create new users'),
('edit_users', 'users', 'edit', 'Edit user details'),
('delete_users', 'users', 'delete', 'Delete users'),
('assign_roles', 'users', 'edit', 'Assign roles to users'),

-- Settings Permissions
('view_settings', 'settings', 'view', 'View system settings'),
('edit_settings', 'settings', 'edit', 'Edit system settings'),

-- Store & Warehouse Permissions
('view_stores', 'stores', 'view', 'View stores'),
('manage_stores', 'stores', 'edit', 'Manage stores'),
('view_warehouses', 'warehouses', 'view', 'View warehouses'),
('manage_warehouses', 'warehouses', 'edit', 'Manage warehouses'),

-- HRM Permissions
('view_employees', 'hrm', 'view', 'View employees'),
('manage_employees', 'hrm', 'edit', 'Manage employees'),
('view_attendance', 'hrm', 'view', 'View attendance'),
('manage_attendance', 'hrm', 'edit', 'Manage attendance'),
('view_leave', 'hrm', 'view', 'View leave requests'),
('manage_leave', 'hrm', 'edit', 'Manage leave requests'),

-- Activity Log Permissions
('view_activity_logs', 'system', 'view', 'View activity logs');

-- =====================================================
-- 6. ASSIGN PERMISSIONS TO ROLES
-- =====================================================

-- Clear existing role permissions
DELETE FROM role_permissions;

-- Get role IDs
SET @super_admin_id = (SELECT id FROM roles WHERE name = 'super_admin');
SET @admin_id = (SELECT id FROM roles WHERE name = 'admin');
SET @store_manager_id = (SELECT id FROM roles WHERE name = 'store_manager');
SET @sales_executive_id = (SELECT id FROM roles WHERE name = 'sales_executive');
SET @accountant_id = (SELECT id FROM roles WHERE name = 'accountant');

-- Super Admin: ALL PERMISSIONS
INSERT INTO role_permissions (role_id, permission_id)
SELECT @super_admin_id, id FROM permissions;

-- Admin: Most permissions except some sensitive ones
INSERT INTO role_permissions (role_id, permission_id)
SELECT @admin_id, id FROM permissions 
WHERE name NOT IN ('delete_users', 'edit_settings');

-- Store Manager permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT @store_manager_id, id FROM permissions 
WHERE name IN (
    'view_store_dashboard', 'view_sales_dashboard',
    'view_products', 'view_purchase_price', 'view_categories', 'view_brands',
    'view_stock', 'adjust_stock', 'stock_in', 'stock_out',
    'create_invoice', 'edit_invoice', 'view_all_invoices', 'give_discount', 'process_returns',
    'create_quotation', 'view_quotations',
    'view_customers', 'create_customers', 'edit_customers', 'view_customer_balance',
    'view_suppliers',
    'view_expenses', 'create_expenses',
    'view_sales_reports', 'view_stock_reports',
    'view_employees', 'view_attendance', 'view_leave',
    'view_stores', 'view_warehouses',
    'view_activity_logs'
);

-- Sales Executive permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT @sales_executive_id, id FROM permissions 
WHERE name IN (
    'view_sales_dashboard',
    'view_products', 'view_categories', 'view_brands',
    'view_stock',
    'create_invoice', 'view_own_invoices', 'give_discount', 'process_returns',
    'create_quotation', 'view_quotations',
    'view_customers', 'create_customers', 'view_customer_balance',
    'view_sales_reports'
);

-- Accountant permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT @accountant_id, id FROM permissions 
WHERE name IN (
    'view_accountant_dashboard',
    'view_products', 'view_purchase_price',
    'view_all_invoices',
    'view_customers', 'view_customer_balance',
    'view_suppliers',
    'view_expenses', 'create_expenses', 'approve_expenses',
    'view_sales_reports', 'view_purchase_reports', 'view_financial_reports', 
    'view_gst_reports', 'view_profit_loss',
    'view_activity_logs'
);

-- =====================================================
-- 7. CREATE SAMPLE USERS (Password: password123)
-- =====================================================

-- First ensure we have a proper admin user
INSERT INTO users (username, email, password, full_name, role, phone, status, daily_sales_target, max_discount_percent, commission_percent)
VALUES 
('superadmin', 'superadmin@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin', '9876543200', 'active', 0, 100, 0),
('admin', 'admin@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', '9876543201', 'active', 0, 50, 0),
('storemanager', 'store@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Store Manager', 'store_manager', '9876543202', 'active', 50000, 20, 1),
('sales1', 'sales1@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rahul Sharma', 'sales_executive', '9876543203', 'active', 25000, 10, 2),
('sales2', 'sales2@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Priya Patel', 'sales_executive', '9876543204', 'active', 25000, 10, 2),
('sales3', 'sales3@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amit Kumar', 'sales_executive', '9876543205', 'active', 25000, 10, 2),
('accountant', 'accounts@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Finance Team', 'accountant', '9876543206', 'active', 0, 0, 0)
ON DUPLICATE KEY UPDATE 
    full_name = VALUES(full_name),
    role = VALUES(role),
    daily_sales_target = VALUES(daily_sales_target),
    max_discount_percent = VALUES(max_discount_percent),
    commission_percent = VALUES(commission_percent);

-- =====================================================
-- 8. SUMMARY
-- =====================================================

SELECT '✅ RBAC Setup Complete!' AS message;
SELECT CONCAT('Roles: ', COUNT(*)) AS status FROM roles;
SELECT CONCAT('Permissions: ', COUNT(*)) AS status FROM permissions;
SELECT CONCAT('Role-Permission Mappings: ', COUNT(*)) AS status FROM role_permissions;
SELECT CONCAT('Users: ', COUNT(*)) AS status FROM users;

-- Show users summary
SELECT username, email, role, daily_sales_target, max_discount_percent, commission_percent
FROM users WHERE status = 'active' ORDER BY role, username;
