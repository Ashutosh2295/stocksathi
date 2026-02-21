<?php
/**
 * RBAC Setup Script
 * Run this to initialize the Role-Based Access Control system
 * Access via: http://localhost/stocksathi/migrations/run_setup.php
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>RBAC Setup - Stocksathi</title>";
echo "<style>
body { font-family: 'Segoe UI', system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
.container { background: w  hite; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
h1 { color: #0d9488; margin-bottom: 20px; }
.success { background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
.error { background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
.info { background: #e0f2fe; color: #0369a1; padding: 12px 16px; border-radius: 8px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
th { background: #f9fafb; font-weight: 600; 
}
.badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.badge-admin { background: #fee2e2; color: #991b1b; }
.badge-manager { background: #fef3c7; color: #92400e; }
.badge-sales { background: #dcfce7; color: #166534; }
.badge-accountant { background: #e0f2fe; color: #0369a1; }
a.btn { display: inline-block; background: #0d9488; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-top: 16px; }
a.btn:hover { background: #0f766e; }
</style></head><body><div class='container'>";

echo "<h1>🔐 RBAC Setup for Stocksathi</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'stocksathi';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Create permissions table
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='info'>📋 Permissions table ready</div>";
    
    // Create role_permissions table
    $pdo->exec("
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
    echo "<div class='info'>📋 Role Permissions table ready</div>";
    
    // Add user columns if not exist
    $columns = [
        'daily_sales_target' => 'DECIMAL(10,2) DEFAULT 0.00',
        'max_discount_percent' => 'DECIMAL(5,2) DEFAULT 0.00',
        'commission_percent' => 'DECIMAL(5,2) DEFAULT 0.00',
        'assigned_store_id' => 'INT(11) DEFAULT NULL',
        'assigned_warehouse_id' => 'INT(11) DEFAULT NULL'
    ];
    
    foreach ($columns as $column => $type) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $type");
        }
    }
    echo "<div class='info'>📋 User table columns updated</div>";
    
    // Insert/Update roles
    $roles = [
        ['super_admin', 'Super Administrator', 'Full system access'],
        ['admin', 'Administrator', 'Administrative access'],
        ['store_manager', 'Store Manager', 'Store operations'],
        ['sales_executive', 'Sales Executive', 'Sales and billing'],
        ['accountant', 'Accountant', 'Finance operations'],
        ['warehouse_manager', 'Warehouse Manager', 'Inventory operations']
    ];
    
    $roleStmt = $pdo->prepare("INSERT INTO roles (name, display_name, description) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE display_name = VALUES(display_name)");
    foreach ($roles as $role) {
        $roleStmt->execute($role);
    }
    echo "<div class='success'>✅ Roles created/updated</div>";
    
    // Insert permissions
    $permissions = [
        // Dashboard
        ['view_admin_dashboard', 'dashboard', 'view', 'View admin dashboard'],
        ['view_store_dashboard', 'dashboard', 'view', 'View store dashboard'],
        ['view_sales_dashboard', 'dashboard', 'view', 'View sales dashboard'],
        
        // Products
        ['view_products', 'products', 'view', 'View products'],
        ['create_products', 'products', 'create', 'Create products'],
        ['edit_products', 'products', 'edit', 'Edit products'],
        ['delete_products', 'products', 'delete', 'Delete products'],
        ['view_purchase_price', 'products', 'view', 'View purchase prices'],
        
        // Categories & Brands
        ['view_categories', 'categories', 'view', 'View categories'],
        ['manage_categories', 'categories', 'edit', 'Manage categories'],
        ['view_brands', 'brands', 'view', 'View brands'],
        ['manage_brands', 'brands', 'edit', 'Manage brands'],
        
        // Inventory
        ['view_stock', 'inventory', 'view', 'View stock'],
        ['adjust_stock', 'inventory', 'edit', 'Adjust stock'],
        ['transfer_stock', 'inventory', 'edit', 'Transfer stock'],
        ['stock_in', 'inventory', 'create', 'Stock in'],
        ['stock_out', 'inventory', 'create', 'Stock out'],
        
        // Sales
        ['create_invoice', 'sales', 'create', 'Create invoices'],
        ['edit_invoice', 'sales', 'edit', 'Edit invoices'],
        ['delete_invoice', 'sales', 'delete', 'Delete invoices'],
        ['view_all_invoices', 'sales', 'view', 'View all invoices'],
        ['view_own_invoices', 'sales', 'view', 'View own invoices'],
        ['give_discount', 'sales', 'edit', 'Give discounts'],
        ['process_returns', 'sales', 'edit', 'Process returns'],
        ['create_quotation', 'sales', 'create', 'Create quotations'],
        ['view_quotations', 'sales', 'view', 'View quotations'],
        
        // Customers
        ['view_customers', 'customers', 'view', 'View customers'],
        ['create_customers', 'customers', 'create', 'Create customers'],
        ['edit_customers', 'customers', 'edit', 'Edit customers'],
        ['delete_customers', 'customers', 'delete', 'Delete customers'],
        ['view_customer_balance', 'customers', 'view', 'View balances'],
        
        // Suppliers
        ['view_suppliers', 'suppliers', 'view', 'View suppliers'],
        ['create_suppliers', 'suppliers', 'create', 'Create suppliers'],
        ['edit_suppliers', 'suppliers', 'edit', 'Edit suppliers'],
        
        // Expenses
        ['view_expenses', 'expenses', 'view', 'View expenses'],
        ['create_expenses', 'expenses', 'create', 'Create expenses'],
        ['approve_expenses', 'expenses', 'approve', 'Approve expenses'],
        
        // Reports
        ['view_sales_reports', 'reports', 'view', 'View sales reports'],
        ['view_stock_reports', 'reports', 'view', 'View stock reports'],
        ['view_financial_reports', 'reports', 'view', 'View financial reports'],
        
        // Users
        ['view_users', 'users', 'view', 'View users'],
        ['create_users', 'users', 'create', 'Create users'],
        ['edit_users', 'users', 'edit', 'Edit users'],
        ['delete_users', 'users', 'delete', 'Delete users'],
        
        // Settings
        ['view_settings', 'settings', 'view', 'View settings'],
        ['edit_settings', 'settings', 'edit', 'Edit settings'],
        
        // Activity
        ['view_activity_logs', 'system', 'view', 'View activity logs']
    ];
    
    $permStmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, module, action, description) VALUES (?, ?, ?, ?)");
    foreach ($permissions as $perm) {
        $permStmt->execute($perm);
    }
    $permCount = $pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
    echo "<div class='success'>✅ $permCount permissions ready</div>";
    
    // Assign permissions to roles
    $pdo->exec("DELETE FROM role_permissions");
    
    // Get role IDs
    $roleIds = [];
    $rolesResult = $pdo->query("SELECT id, name FROM roles");
    while ($row = $rolesResult->fetch(PDO::FETCH_ASSOC)) {
        $roleIds[$row['name']] = $row['id'];
    }
    
    // Super admin gets all
    if (isset($roleIds['super_admin'])) {
        $pdo->exec("INSERT INTO role_permissions (role_id, permission_id) 
                    SELECT {$roleIds['super_admin']}, id FROM permissions");
    }
    
    // Admin gets most
    if (isset($roleIds['admin'])) {
        $pdo->exec("INSERT INTO role_permissions (role_id, permission_id) 
                    SELECT {$roleIds['admin']}, id FROM permissions 
                    WHERE name NOT IN ('delete_users', 'edit_settings')");
    }
    
    // Store manager permissions
    if (isset($roleIds['store_manager'])) {
        $storePerms = "'view_store_dashboard','view_sales_dashboard','view_products','view_purchase_price','view_categories','view_brands','view_stock','adjust_stock','stock_in','stock_out','create_invoice','edit_invoice','view_all_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','edit_customers','view_customer_balance','view_suppliers','view_expenses','create_expenses','view_sales_reports','view_stock_reports','view_activity_logs'";
        $pdo->exec("INSERT INTO role_permissions (role_id, permission_id) 
                    SELECT {$roleIds['store_manager']}, id FROM permissions WHERE name IN ($storePerms)");
    }
    
    // Sales executive permissions
    if (isset($roleIds['sales_executive'])) {
        $salesPerms = "'view_sales_dashboard','view_products','view_categories','view_brands','view_stock','create_invoice','view_own_invoices','give_discount','process_returns','create_quotation','view_quotations','view_customers','create_customers','view_customer_balance','view_sales_reports'";
        $pdo->exec("INSERT INTO role_permissions (role_id, permission_id) 
                    SELECT {$roleIds['sales_executive']}, id FROM permissions WHERE name IN ($salesPerms)");
    }
    
    $rpCount = $pdo->query("SELECT COUNT(*) FROM role_permissions")->fetchColumn();
    echo "<div class='success'>✅ $rpCount role-permission mappings created</div>";
    
    // Create sample users
    $hashedPassword = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password123
    
    $users = [
        ['superadmin', 'superadmin@stocksathi.com', $hashedPassword, 'Super Admin', 'super_admin', '9876543200', 0, 100, 0],
        ['admin', 'admin@stocksathi.com', $hashedPassword, 'Admin User', 'admin', '9876543201', 0, 50, 0],
        ['storemanager', 'store@stocksathi.com', $hashedPassword, 'Store Manager', 'store_manager', '9876543202', 50000, 20, 1],
        ['sales1', 'sales1@stocksathi.com', $hashedPassword, 'Rahul Sharma', 'sales_executive', '9876543203', 25000, 10, 2],
        ['sales2', 'sales2@stocksathi.com', $hashedPassword, 'Priya Patel', 'sales_executive', '9876543204', 25000, 10, 2],
        ['accountant', 'accounts@stocksathi.com', $hashedPassword, 'Finance Team', 'accountant', '9876543206', 0, 0, 0]
    ];
    
    $userStmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, phone, daily_sales_target, max_discount_percent, commission_percent, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                               ON DUPLICATE KEY UPDATE 
                                   full_name = VALUES(full_name),   
                                   role = VALUES(role),
                                   daily_sales_target = VALUES(daily_sales_target),
                                   max_discount_percent = VALUES(max_discount_percent),
                                   commission_percent = VALUES(commission_percent)");
    
    foreach ($users as $user) {
        $userStmt->execute($user);
    }
    echo "<div class='success'>✅ Sample users created</div>";
    
    // Display summary
    echo "<h2>📊 Setup Summary</h2>";
    
    echo "<h3>User Accounts (Password: <code>password123</code>)</h3>";
    echo "<table>";
    echo "<tr><th>Username</th><th>Email</th><th>Role</th><th>Daily Target</th><th>Max Discount</th></tr>";
    
    $usersResult = $pdo->query("SELECT username, email, role, daily_sales_target, max_discount_percent FROM users WHERE status = 'active' ORDER BY role, username");
    while ($user = $usersResult->fetch(PDO::FETCH_ASSOC)) {
        $badgeClass = 'badge-sales';
        if ($user['role'] == 'super_admin' || $user['role'] == 'admin') $badgeClass = 'badge-admin';
        elseif ($user['role'] == 'store_manager') $badgeClass = 'badge-manager';
        elseif ($user['role'] == 'accountant') $badgeClass = 'badge-accountant';
        
        echo "<tr>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td>{$user['email']}</td>";
        echo "<td><span class='badge $badgeClass'>" . ucwords(str_replace('_', ' ', $user['role'])) . "</span></td>";
        echo "<td>₹" . number_format($user['daily_sales_target']) . "</td>";
        echo "<td>{$user['max_discount_percent']}%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success' style='margin-top: 20px;'><strong>🎉 RBAC Setup Complete!</strong><br>You can now login with any of the above accounts.</div>";
    
    echo "<a href='../pages/login.php' class='btn'>Go to Login Page →</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Make sure MySQL is running and the 'stocksathi' database exists.</div>";
}

echo "</div></body></html>";
?>
