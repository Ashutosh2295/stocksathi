-- =====================================================
-- RBAC SYSTEM MIGRATION
-- Role-Based Access Control for Stocksathi
-- Version: 2.0
-- =====================================================

USE stocksathi;

-- =====================================================
-- 1. PERMISSIONS TABLE
-- =====================================================

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
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
-- 2. ROLE_PERMISSIONS TABLE
-- =====================================================

DROP TABLE IF EXISTS role_permissions;
CREATE TABLE role_permissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    role_id INT(11) NOT NULL,
    permission_id INT(11) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    KEY idx_role_id (role_id),
    KEY idx_permission_id (permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. UPDATE USERS TABLE
-- =====================================================

-- Add new columns for role-based features
ALTER TABLE users
ADD COLUMN assigned_store_id INT(11) DEFAULT NULL AFTER role,
ADD COLUMN assigned_warehouse_id INT(11) DEFAULT NULL AFTER assigned_store_id,
ADD COLUMN can_give_discount DECIMAL(5,2) DEFAULT 0.00 AFTER assigned_warehouse_id,
ADD COLUMN max_discount_percent DECIMAL(5,2) DEFAULT 0.00 AFTER can_give_discount,
ADD COLUMN daily_sales_target DECIMAL(10,2) DEFAULT 0.00 AFTER max_discount_percent,
ADD COLUMN commission_percent DECIMAL(5,2) DEFAULT 0.00 AFTER daily_sales_target;

-- Add foreign keys
ALTER TABLE users
ADD CONSTRAINT fk_users_store FOREIGN KEY (assigned_store_id) REFERENCES stores(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_users_warehouse FOREIGN KEY (assigned_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL;

-- =====================================================
-- 4. GST CONFIGURATION TABLE
-- =====================================================

DROP TABLE IF EXISTS gst_config;
CREATE TABLE gst_config (
    id INT(11) NOT NULL AUTO_INCREMENT,
    product_id INT(11) NOT NULL,
    hsn_code VARCHAR(20),
    sac_code VARCHAR(20),
    gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    cgst_percentage DECIMAL(5,2) DEFAULT 0.00,
    sgst_percentage DECIMAL(5,2) DEFAULT 0.00,
    igst_percentage DECIMAL(5,2) DEFAULT 0.00,
    cess_percentage DECIMAL(5,2) DEFAULT 0.00,
    is_reverse_charge TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_product_gst (product_id),
    KEY idx_hsn (hsn_code),
    CONSTRAINT fk_gst_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. PAYMENT MODES TABLE
-- =====================================================

DROP TABLE IF EXISTS payment_modes;
CREATE TABLE payment_modes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    type ENUM('cash', 'upi', 'card', 'netbanking', 'cheque', 'credit', 'wallet') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    account_number VARCHAR(50),
    bank_name VARCHAR(100),
    ifsc_code VARCHAR(20),
    upi_id VARCHAR(100),
    charges_percent DECIMAL(5,2) DEFAULT 0.00,
    icon VARCHAR(100),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_type (type),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. CUSTOMER CREDIT (KHATA) TABLE
-- =====================================================

DROP TABLE IF EXISTS customer_credits;
CREATE TABLE customer_credits (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) NOT NULL,
    invoice_id INT(11) DEFAULT NULL,
    transaction_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('credit', 'payment') NOT NULL,
    payment_mode_id INT(11) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    interest_rate DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT,
    created_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_customer (customer_id),
    KEY idx_invoice (invoice_id),
    KEY idx_date (transaction_date),
    CONSTRAINT fk_credit_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_credit_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
    CONSTRAINT fk_credit_payment_mode FOREIGN KEY (payment_mode_id) REFERENCES payment_modes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. INSERT DEFAULT PERMISSIONS
-- =====================================================

INSERT INTO permissions (name, module, action, description) VALUES
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

-- Inventory Permissions
('view_stock', 'inventory', 'view', 'View stock levels'),
('adjust_stock', 'inventory', 'edit', 'Adjust stock quantities'),
('transfer_stock', 'inventory', 'edit', 'Transfer stock between locations'),
('view_all_warehouses', 'inventory', 'view', 'View all warehouse stock'),

-- Sales Permissions
('create_invoice', 'sales', 'create', 'Create sales invoices'),
('edit_invoice', 'sales', 'edit', 'Edit sales invoices'),
('delete_invoice', 'sales', 'delete', 'Delete sales invoices'),
('view_all_invoices', 'sales', 'view', 'View all invoices'),
('view_own_invoices', 'sales', 'view', 'View own invoices only'),
('give_discount', 'sales', 'edit', 'Give discounts on sales'),
('process_returns', 'sales', 'edit', 'Process sales returns'),

-- Purchase Permissions
('view_purchases', 'purchase', 'view', 'View purchase orders'),
('create_purchase', 'purchase', 'create', 'Create purchase orders'),
('edit_purchase', 'purchase', 'edit', 'Edit purchase orders'),
('approve_purchase', 'purchase', 'approve', 'Approve purchase orders'),

-- Customer Permissions
('view_customers', 'customers', 'view', 'View customer list'),
('create_customers', 'customers', 'create', 'Create new customers'),
('edit_customers', 'customers', 'edit', 'Edit customer details'),
('view_customer_balance', 'customers', 'view', 'View customer outstanding balance'),

-- Supplier Permissions
('view_suppliers', 'suppliers', 'view', 'View supplier list'),
('create_suppliers', 'suppliers', 'create', 'Create new suppliers'),
('edit_suppliers', 'suppliers', 'edit', 'Edit supplier details'),

-- Expense Permissions
('view_expenses', 'expenses', 'view', 'View expense records'),
('create_expenses', 'expenses', 'create', 'Create new expenses'),
('approve_expenses', 'expenses', 'approve', 'Approve expenses'),

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
('manage_payment_modes', 'settings', 'edit', 'Manage payment modes'),

-- GST Permissions
('manage_gst_config', 'gst', 'edit', 'Manage GST configuration'),
('generate_gstr1', 'gst', 'create', 'Generate GSTR-1 report'),
('generate_gstr3b', 'gst', 'create', 'Generate GSTR-3B report'),

-- Khata (Credit) Permissions
('view_khata', 'khata', 'view', 'View customer credit book'),
('add_khata_entry', 'khata', 'create', 'Add khata entries'),
('collect_payment', 'khata', 'edit', 'Collect customer payments');

-- =====================================================
-- 8. ASSIGN PERMISSIONS TO ROLES
-- =====================================================

-- Super Admin (role_id = 1) - ALL PERMISSIONS
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Store Manager (role_id assumed as new role)
-- We'll need to add this role first, or update based on existing role IDs

-- =====================================================
-- 9. UPDATE ROLES TABLE
-- =====================================================

-- Add detailed roles if not exist
INSERT INTO roles (name, display_name, description, permissions) VALUES
('super_admin', 'Super Administrator', 'Full system access with all permissions', '{"all": true}'),
('store_manager', 'Store Manager', 'Manage store operations and daily sales', '{"store_ops": true}'),
('accountant', 'Accountant', 'Finance and GST compliance', '{"finance": true}'),
('purchase_manager', 'Purchase Manager', 'Procurement and vendor management', '{"purchase": true}'),
('sales_executive', 'Sales Executive', 'Sales and billing', '{"sales": true}'),
('warehouse_manager', 'Warehouse Manager', 'Inventory and warehouse operations', '{"inventory": true}'),
('delivery_boy', 'Delivery Boy', 'Delivery management', '{"delivery": true}'),
('auditor', 'Auditor', 'Read-only access for compliance', '{"read_only": true}')
ON DUPLICATE KEY UPDATE display_name=VALUES(display_name);

-- =====================================================
-- 10. INSERT DEFAULT PAYMENT MODES
-- =====================================================

INSERT INTO payment_modes (name, type, is_active, icon) VALUES
('Cash', 'cash', 1, '💵'),
('UPI', 'upi', 1, '📱'),
('Credit/Debit Card', 'card', 1, '💳'),
('Net Banking', 'netbanking', 1, '🏦'),
('Cheque', 'cheque', 1, '📝'),
('Credit (Khata)', 'credit', 1, '📒'),
('Paytm', 'wallet', 1, '💼'),
('PhonePe', 'wallet', 1, '💼'),
('Google Pay', 'wallet', 1, '💼');

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT '✅ RBAC Migration completed successfully!' AS message;
SELECT CONCAT('Total Permissions Created: ', COUNT(*)) AS status FROM permissions;
SELECT CONCAT('Total Roles: ', COUNT(*)) AS status FROM roles;
SELECT CONCAT('Total Payment Modes: ', COUNT(*)) AS status FROM payment_modes;
