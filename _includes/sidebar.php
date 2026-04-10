<?php
/**
 * Sidebar Navigation Component
 * Role-Based Access Control (RBAC) - Clear & Simple
 * 
 * ROLES AND THEIR ACCESS:
 * ========================
 * 1. super_admin    - Full access to everything
 * 2. admin          - Almost all features (except delete users, edit settings)
 * 3. store_manager  - Store operations, sales, stock, customers
 * 4. sales_executive - Sales, invoices, quotations, customers (limited)
 * 5. accountant     - Finance, expenses, reports, GST
 * 6. warehouse_manager - Stock and warehouse operations
 * 7. hr             - HR: employees, attendance, leave
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$userName = Session::getUserName() ?? 'User';
$userRole = Session::getUserRole() ?? 'user';
$userRoleDisplay = ucfirst(str_replace('_', ' ', $userRole));
$userInitials = strtoupper(substr($userName, 0, 2));

// ===============================================
// ROLE-BASED MENU VISIBILITY CONFIGURATION
// ===============================================

// Get all permissions for the current user from database
$userPermissions = PermissionMiddleware::getUserPermissions();
// Extract just the permission names into a flat array for easy checking
$permissionNames = array_column($userPermissions, 'name');

/**
 * Check if user has a specific permission
 * @param string|array $permission Permission name or array of permissions (any one required)
 * @return bool
 */
function canSee($permission) {
    global $permissionNames, $userRole;
    
    // Super admin sees everything
    if ($userRole === 'super_admin') {
        return true;
    }
    
    // Check if any of the permissions exist
    if (is_array($permission)) {
        foreach ($permission as $p) {
            if (in_array($p, $permissionNames)) {
                return true;
            }
        }
        return false;
    }
    
    return in_array($permission, $permissionNames);
}

// Check if entire sections should be visible based on their children
$showProductSection = canSee(['view_products', 'view_categories', 'view_brands']) && $userRole !== 'sales_executive' && $userRole !== 'accountant';
$showStockSection = canSee(['view_stock', 'stock_in', 'stock_out', 'adjust_stock', 'transfer_stock']) && $userRole !== 'sales_executive' && $userRole !== 'accountant';
$showSalesSection = canSee(['create_invoice', 'view_all_invoices', 'view_own_invoices', 'view_quotations', 'process_returns']);
$showMarketingSection = (canSee('create_promotions') || $userRole === 'admin') && $userRole !== 'sales_executive' && $userRole !== 'accountant';
$showFinanceSection = canSee(['view_expenses', 'create_expenses']) || $userRole === 'accountant';
$showPeopleSection = canSee(['view_customers', 'view_suppliers', 'view_stores', 'view_warehouses']);
$showHRSection = ($userRole === 'super_admin' || $userRole === 'admin' || $userRole === 'hr')
    || (canSee(['view_employees', 'view_attendance', 'view_leave', 'manage_employees', 'view_hr_dashboard']) && $userRole !== 'sales_executive');
$showReportsSection = canSee(['view_sales_reports', 'view_purchase_reports', 'view_financial_reports', 'view_stock_reports']) && $userRole !== 'sales_executive' || $userRole === 'accountant';
$showAdminSection = canSee(['view_users', 'view_settings', 'view_activity_logs', 'assign_roles']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_PATH ?>/index.php" class="sidebar-logo">
            <img src="<?= BASE_PATH ?>/assets/images/logo.png" alt="Stocksathi" class="sidebar-logo-icon" style="width: 52px; height: 52px; object-fit: contain; background: white; border-radius: 10px; padding: 8px;">
            <span class="sidebar-logo-text">Stocksathi</span>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <!-- ============================================== -->
        <!-- DASHBOARD SECTION - Role Specific -->
        <!-- ============================================== -->
        <div class="nav-section">
            <div class="nav-section-title">Dashboard</div>
            
            <?php if ($userRole === 'super_admin'): ?>
                <!-- Super Admin: All Dashboards -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/super-admin.php" class="nav-item <?= $currentPage == 'super-admin.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Super Admin</span>
                </a>
                <a href="<?= BASE_PATH ?>/pages/dashboards/admin.php" class="nav-item <?= $currentPage == 'admin.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Admin Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/pages/dashboards/sales-executive.php" class="nav-item <?= $currentPage == 'sales-executive.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Sales Dashboard</span>
                </a>
            
            <?php elseif ($userRole === 'admin'): ?>
                <!-- Admin: Admin + Sales Dashboards -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/admin.php" class="nav-item <?= $currentPage == 'admin.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Admin Dashboard</span>
                </a>
                <a href="<?= BASE_PATH ?>/pages/dashboards/sales-executive.php" class="nav-item <?= $currentPage == 'sales-executive.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Sales Dashboard</span>
                </a>
            
            <?php elseif ($userRole === 'store_manager'): ?>
                <!-- Store Manager: Store Dashboard Only -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/store-manager.php" class="nav-item <?= $currentPage == 'store-manager.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Store Dashboard</span>
                </a>
            
            <?php elseif ($userRole === 'sales_executive'): ?>
                <!-- Sales Executive: Sales Dashboard Only -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/sales-executive.php" class="nav-item <?= $currentPage == 'sales-executive.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Sales Dashboard</span>
                </a>
            
            <?php elseif ($userRole === 'accountant'): ?>
                <!-- Accountant: Accountant Dashboard Only -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/accountant.php" class="nav-item <?= $currentPage == 'accountant.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Accountant Dashboard</span>
                </a>
            
            <?php elseif ($userRole === 'warehouse_manager'): ?>
                <!-- Warehouse Manager: Uses Store Manager Dashboard -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/store-manager.php" class="nav-item <?= $currentPage == 'store-manager.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Warehouse Dashboard</span>
                </a>

            <?php elseif ($userRole === 'hr'): ?>
                <!-- HR: HR Dashboard -->
                <a href="<?= BASE_PATH ?>/pages/dashboards/hr.php" class="nav-item <?= $currentPage == 'hr.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">HR Dashboard</span>
                </a>
            
            <?php else: ?>
                <!-- Default: General Dashboard -->
                <a href="<?= BASE_PATH ?>/index.php" class="nav-item <?= $currentPage == 'index.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/sales-dashboard.svg'; ?></span>
                    <span class="nav-item-text">Dashboard</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- ============================================== -->
        <!-- PRODUCT MANAGEMENT -->
        <!-- ============================================== -->
        <?php if ($showProductSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>Product Management</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <?php if (canSee('view_products')): ?>
                <a href="<?= BASE_PATH ?>/pages/products.php" class="nav-item nav-sub-item <?= $currentPage == 'products.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/products/products.svg'; ?></span>
                    <span class="nav-item-text">Products</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_categories')): ?>
                <a href="<?= BASE_PATH ?>/pages/categories.php" class="nav-item nav-sub-item <?= $currentPage == 'categories.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/products/categories.svg'; ?></span>
                    <span class="nav-item-text">Categories</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_brands')): ?>
                <a href="<?= BASE_PATH ?>/pages/brands.php" class="nav-item nav-sub-item <?= $currentPage == 'brands.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/products/brands.svg'; ?></span>
                    <span class="nav-item-text">Brands</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- STOCK MANAGEMENT -->
        <!-- ============================================== -->
        <?php if ($showStockSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>Stock Management</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <?php if (canSee('stock_in')): ?>
                <a href="<?= BASE_PATH ?>/pages/stock-in.php" class="nav-item nav-sub-item <?= $currentPage == 'stock-in.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/stock/stock-in.svg'; ?></span>
                    <span class="nav-item-text">Stock In</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('stock_out')): ?>
                <a href="<?= BASE_PATH ?>/pages/stock-out.php" class="nav-item nav-sub-item <?= $currentPage == 'stock-out.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/stock/stock-out.svg'; ?></span>
                    <span class="nav-item-text">Stock Out</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('adjust_stock')): ?>
                <a href="<?= BASE_PATH ?>/pages/stock-adjustments.php" class="nav-item nav-sub-item <?= $currentPage == 'stock-adjustments.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/stock/adjustments.svg'; ?></span>
                    <span class="nav-item-text">Adjustments</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('transfer_stock')): ?>
                <a href="<?= BASE_PATH ?>/pages/stock-transfers.php" class="nav-item nav-sub-item <?= $currentPage == 'stock-transfers.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/stock/transfers.svg'; ?></span>
                    <span class="nav-item-text">Transfers</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- SALES & BILLING -->
        <!-- ============================================== -->
        <?php if ($showSalesSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>Sales & Billing</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <?php if (canSee(['view_all_invoices', 'view_own_invoices', 'create_invoice'])): ?>
                <a href="<?= BASE_PATH ?>/pages/invoices.php" class="nav-item nav-sub-item <?= $currentPage == 'invoices.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/sales/invoices.svg'; ?></span>
                    <span class="nav-item-text">Invoices</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee(['view_quotations', 'create_quotation'])): ?>
                <a href="<?= BASE_PATH ?>/pages/quotations.php" class="nav-item nav-sub-item <?= $currentPage == 'quotations.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/sales/quotations.svg'; ?></span>
                    <span class="nav-item-text">Quotations</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('process_returns')): ?>
                <a href="<?= BASE_PATH ?>/pages/sales-returns.php" class="nav-item nav-sub-item <?= $currentPage == 'sales-returns.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/sales/sales-returns.svg'; ?></span>
                    <span class="nav-item-text">Sales Returns</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- MARKETING -->
        <!-- ============================================== -->
        <?php if ($showMarketingSection): ?>
        <div class="nav-section">
            <div class="nav-section-title">Marketing</div>
            <a href="<?= BASE_PATH ?>/pages/promotions.php" class="nav-item <?= $currentPage == 'promotions.php' ? 'active' : '' ?>">
                <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/marketing/promotions.svg'; ?></span>
                <span class="nav-item-text">Promotions</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- FINANCE -->
        <!-- ============================================== -->
        <?php if ($showFinanceSection): ?>
        <div class="nav-section">
            <div class="nav-section-title">Finance</div>
            <?php if (canSee(['view_expenses', 'create_expenses'])): ?>
            <a href="<?= BASE_PATH ?>/pages/expenses.php" class="nav-item <?= $currentPage == 'expenses.php' ? 'active' : '' ?>">
                <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/finance/expenses.svg'; ?></span>
                <span class="nav-item-text">Expenses</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- PEOPLE (Customers, Suppliers, Stores, Warehouses) -->
        <!-- ============================================== -->
        <?php if ($showPeopleSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>People</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <?php if (canSee('view_customers')): ?>
                <a href="<?= BASE_PATH ?>/pages/customers.php" class="nav-item nav-sub-item <?= $currentPage == 'customers.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/people/customers.svg'; ?></span>
                    <span class="nav-item-text">Customers</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_suppliers')): ?>
                <a href="<?= BASE_PATH ?>/pages/suppliers.php" class="nav-item nav-sub-item <?= $currentPage == 'suppliers.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/people/suppliers.svg'; ?></span>
                    <span class="nav-item-text">Suppliers</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_stores')): ?>
                <a href="<?= BASE_PATH ?>/pages/stores.php" class="nav-item nav-sub-item <?= $currentPage == 'stores.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/people/stores.svg'; ?></span>
                    <span class="nav-item-text">Stores</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_warehouses')): ?>
                <a href="<?= BASE_PATH ?>/pages/warehouses.php" class="nav-item nav-sub-item <?= $currentPage == 'warehouses.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/people/warehouses.svg'; ?></span>
                    <span class="nav-item-text">Warehouses</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- HUMAN RESOURCES -->
        <!-- ============================================== -->
        <?php if ($showHRSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>Human Resources</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <a href="<?= BASE_PATH ?>/pages/dashboards/hr.php" class="nav-item nav-sub-item <?= $currentPage == 'hr.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/dashboard/admin-dashboard.svg'; ?></span>
                    <span class="nav-item-text">HR Dashboard</span>
                </a>

                <?php if (canSee('view_employees') || $userRole === 'hr'): ?>
                <a href="<?= BASE_PATH ?>/pages/employees.php" class="nav-item nav-sub-item <?= $currentPage == 'employees.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/hr/employees.svg'; ?></span>
                    <span class="nav-item-text">Employees</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee(['view_employees']) || $userRole === 'hr'): // Using employees permission as proxy for departments ?>
                <a href="<?= BASE_PATH ?>/pages/departments.php" class="nav-item nav-sub-item <?= $currentPage == 'departments.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/hr/departments.svg'; ?></span>
                    <span class="nav-item-text">Departments</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_attendance') || $userRole === 'hr'): ?>
                <a href="<?= BASE_PATH ?>/pages/attendance.php" class="nav-item nav-sub-item <?= $currentPage == 'attendance.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/hr/attendance.svg'; ?></span>
                    <span class="nav-item-text">Attendance</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_leave') || $userRole === 'hr'): ?>
                <a href="<?= BASE_PATH ?>/pages/leave-management.php" class="nav-item nav-sub-item <?= $currentPage == 'leave-management.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/hr/leave-management.svg'; ?></span>
                    <span class="nav-item-text">Leave Management</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- ANALYTICS / REPORTS -->
        <!-- ============================================== -->
        <?php if ($showReportsSection): ?>
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <a href="<?= BASE_PATH ?>/pages/reports.php" class="nav-item <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
                <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/analytics/reports.svg'; ?></span>
                <span class="nav-item-text">Reports</span>
            </a>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- ADMINISTRATION -->
        <!-- ============================================== -->
        <?php if ($showAdminSection): ?>
        <div class="nav-section">
            <div class="nav-section-title nav-dropdown-toggle" onclick="toggleNavDropdown(this, event)">
                <span>Administration</span>
                <span class="nav-dropdown-icon">▼</span>
            </div>
            <div class="nav-dropdown-content">
                <?php if (canSee('view_users')): ?>
                <a href="<?= BASE_PATH ?>/pages/users.php" class="nav-item nav-sub-item <?= $currentPage == 'users.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/admin/users.svg'; ?></span>
                    <span class="nav-item-text">Users</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee(['assign_roles', 'view_users'])): ?>
                <a href="<?= BASE_PATH ?>/pages/roles.php" class="nav-item nav-sub-item <?= $currentPage == 'roles.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/admin/roles.svg'; ?></span>
                    <span class="nav-item-text">Roles & Permissions</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_activity_logs')): ?>
                <a href="<?= BASE_PATH ?>/pages/activity-logs.php" class="nav-item nav-sub-item <?= $currentPage == 'activity-logs.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/admin/activity-logs.svg'; ?></span>
                    <span class="nav-item-text">Activity Logs</span>
                </a>
                <?php endif; ?>
                
                <?php if (canSee('view_settings')): ?>
                <a href="<?= BASE_PATH ?>/pages/settings.php" class="nav-item nav-sub-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/admin/settings.svg'; ?></span>
                    <span class="nav-item-text">Settings</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================== -->
        <!-- LOGOUT -->
        <!-- ============================================== -->
        <div class="nav-section" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <form action="<?= BASE_PATH ?>/pages/logout.php" method="get" style="margin: 0; padding: 0; display: block;" onsubmit="return confirm('Are you sure you want to logout?');">
                <button type="submit" class="nav-item" style="width: 100%; background: none; border: none; cursor: pointer; text-align: left; font: inherit; color: var(--color-danger); display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px;">
                    <span class="nav-item-icon"><?php include __DIR__ . '/../assets/icons/utility/logout.svg'; ?></span>
                    <span class="nav-item-text">Logout</span>
                </button>
            </form>
        </div>
    </nav>
    
    <!-- User Info Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="sidebar-user-avatar"><?= $userInitials ?></div>
            <div class="sidebar-user-details">
                <div class="sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="sidebar-user-role"><?= htmlspecialchars($userRoleDisplay) ?></div>
            </div>
        </div>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
// Navigation dropdown toggle function

// Navigation dropdown toggle function
function toggleNavDropdown(element, event) {
    event?.stopPropagation();
    const content = element.nextElementSibling;
    const icon = element.querySelector('.nav-dropdown-icon');
    
    if (content && content.classList.contains('nav-dropdown-content')) {
        const isOpen = content.classList.contains('show');
        
        if (isOpen) {
            content.classList.remove('show');
            icon.style.transform = 'rotate(0deg)';
        } else {
            content.classList.add('show');
            icon.style.transform = 'rotate(180deg)';
        }
    }
}

// Auto-open dropdown sections that contain the active page
document.addEventListener('DOMContentLoaded', function() {
    // Find all active nav items within dropdowns
    const activeItems = document.querySelectorAll('.nav-dropdown-content .nav-item.active, .nav-dropdown-content .nav-sub-item.active');
    
    activeItems.forEach(activeItem => {
        const dropdownContent = activeItem.closest('.nav-dropdown-content');
        if (dropdownContent) {
            dropdownContent.classList.add('show');
            
            const toggle = dropdownContent.previousElementSibling;
            if (toggle) {
                const icon = toggle.querySelector('.nav-dropdown-icon');
                if (icon) {
                    icon.style.transform = 'rotate(180deg)';
                }
            }
        }
    });
    
    // Also check if current page matches any link in dropdown
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    
    document.querySelectorAll('.nav-dropdown-content .nav-item, .nav-dropdown-content .nav-sub-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
            
            const dropdownContent = link.closest('.nav-dropdown-content');
            if (dropdownContent) {
                dropdownContent.classList.add('show');
                
                const toggle = dropdownContent.previousElementSibling;
                if (toggle) {
                    const icon = toggle.querySelector('.nav-dropdown-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                }
            }
        }
    });

    // Auto-scroll the sidebar to ensure the active menu item is visible on load
    setTimeout(() => {
        const activeItem = document.querySelector('.sidebar .nav-item.active, .sidebar .nav-sub-item.active');
        const sidebarNav = document.querySelector('.sidebar-nav');
        
        if (activeItem && sidebarNav) {
            const itemRect = activeItem.getBoundingClientRect();
            const navRect = sidebarNav.getBoundingClientRect();
            
            // If item is below or above the visible area of the sidebar
            if (itemRect.bottom > navRect.bottom || itemRect.top < navRect.top) {
                // Scroll the sidebar navigation pane specifically, so main window doesn't jump
                const scrollPos = activeItem.offsetTop - (navRect.height / 2) + (itemRect.height / 2);
                sidebarNav.scrollTo({
                    top: scrollPos > 0 ? scrollPos : 0,
                    behavior: 'smooth'
                });
            }
        }
    }, 150);
});

// Mobile menu toggle
document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('show');
    document.getElementById('sidebarOverlay')?.classList.toggle('show');
});

document.getElementById('sidebarOverlay')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.remove('show');
    document.getElementById('sidebarOverlay')?.classList.remove('show');
});

// Smooth loading transition for sidebar navigation has been disabled to ensure Modals and JS load correctly on first click.
</script>
    