<?php
/**
 * Reusable Header Component
 * Include this in every protected page
 */



$userName = Session::getUserName() ?? 'User';
$userRole = Session::getUserRole() ?? 'user';
$userRole = ucfirst(str_replace('_', ' ', $userRole));
$userInitials = strtoupper(substr($userName, 0, 2));

// Helper functions for permission checking
if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        return PermissionMiddleware::hasPermission($permission);
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission($permissions) {
        return PermissionMiddleware::hasAnyPermission($permissions);
    }
}

// Get notifications count (you can make this dynamic later)
$notificationCount = 3;
?>
<header class="header">
    <div class="header-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">☰</button>
    </div>
    <div class="header-right">
        <!-- Quick Add Button -->
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button id="quickAddBtn" style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 14px; background: linear-gradient(135deg, #4f82d5 0%, #3a63a5 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 2px 8px rgba(79, 130, 213, 0.3); transition: all 0.3s ease;">
                <span style="font-size: 18px; color: white;">➕</span>
                <span style="color: white;">Quick Add</span>
                <span style="font-size: 12px; color: white;">▼</span>
            </button>
            <div id="quickAddDropdown" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; background: white; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); width: 720px; z-index: 1000;">
                <!-- Header -->
                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; background: linear-gradient(135deg, #4f82d5 0%, #3a63a5 100%); border-radius: 12px 12px 0 0;">
                    <h3 style="margin: 0; color: white; font-size: 16px; font-weight: 600;">⚡ Quick Add - Create New Items</h3>
                </div>
                
                <!-- Grid Layout -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; padding: 12px;">
                    <?php
                    // Safely check permissions based on sidebar's canSee() or fallback to hasPermission()
                    $checkQA = function($perm) {
                        global $userRole;
                        if ($userRole === 'super_admin') return true;
                        if (function_exists('canSee')) return canSee($perm);
                        if (function_exists('hasPermission')) {
                            if (is_array($perm)) {
                                foreach($perm as $p) { if(hasPermission($p)) return true; }
                                return false;
                            }
                            return hasPermission($perm);
                        }
                        return false;
                    };

                    $qaProd = $checkQA(['view_products']);
                    $qaCat = $checkQA(['view_categories']);
                    $qaBrand = $checkQA(['view_brands']);
                    $hasProdSec = $qaProd || $qaCat || $qaBrand;

                    $qaStockIn = $checkQA(['stock_in']);
                    $qaStockOut = $checkQA(['stock_out']);
                    $qaStockAdj = $checkQA(['adjust_stock']);
                    $qaStockTrans = $checkQA(['transfer_stock']);
                    $hasStockSec = $qaStockIn || $qaStockOut || $qaStockAdj || $qaStockTrans;

                    $qaInv = $checkQA(['create_invoice', 'view_all_invoices']);
                    $qaQuot = $checkQA(['view_quotations']);
                    $qaSR = $checkQA(['process_returns']);
                    $hasSalesSec = $qaInv || $qaQuot || $qaSR;

                    $qaCust = $checkQA(['view_customers']);
                    $qaSupp = $checkQA(['view_suppliers']);
                    $qaEmp = $checkQA(['view_employees']);
                    $hasPeopleSec = $qaCust || $qaSupp || $qaEmp;

                    $qaStore = $checkQA(['view_stores']);
                    $qaWh = $checkQA(['view_warehouses']);
                    $hasLocSec = $qaStore || $qaWh;

                    $qaExp = $checkQA(['view_expenses']) || (isset($userRole) && $userRole === 'accountant');
                    $qaProm = $checkQA(['create_promotions']) || (isset($userRole) && $userRole === 'admin');
                    $hasFinSec = $qaExp || $qaProm;

                    $qaDept = $checkQA(['view_departments']) || (isset($userRole) && $userRole === 'hr');
                    $qaAtt = $checkQA(['view_attendance']);
                    $qaLeave = $checkQA(['view_leave']);
                    $qaUser = $checkQA(['view_users', 'assign_roles']);
                    $hasHRSec = $qaDept || $qaAtt || $qaLeave || $qaUser;
                    ?>
                    
                    <!-- Column 1: Products & Inventory -->
                    <div style="padding: 8px; border-right: 1px solid #f3f4f6;">
                        <?php if ($hasProdSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">📦 Products & Inventory</div>
                        <?php if ($qaProd): ?>
                        <a href="<?= BASE_PATH ?>/pages/products.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">📦</span>
                            <span style="font-size: 14px; font-weight: 500;">Product</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaCat): ?>
                        <a href="<?= BASE_PATH ?>/pages/categories.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏷️</span>
                            <span style="font-size: 14px; font-weight: 500;">Category</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaBrand): ?>
                        <a href="<?= BASE_PATH ?>/pages/brands.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏢</span>
                            <span style="font-size: 14px; font-weight: 500;">Brand</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($hasProdSec && $hasStockSec): ?>
                        <div style="height: 1px; background: #f3f4f6; margin: 8px 0;"></div>
                        <?php endif; ?>
                        
                        <?php if ($hasStockSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">📊 Stock</div>
                        <?php if ($qaStockIn): ?>
                        <a href="<?= BASE_PATH ?>/pages/stock-in.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">📥</span>
                            <span style="font-size: 14px; font-weight: 500;">Stock In</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaStockOut): ?>
                        <a href="<?= BASE_PATH ?>/pages/stock-out.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">📤</span>
                            <span style="font-size: 14px; font-weight: 500;">Stock Out</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaStockAdj): ?>
                        <a href="<?= BASE_PATH ?>/pages/stock-adjustments.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">⚖️</span>
                            <span style="font-size: 14px; font-weight: 500;">Adjustment</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaStockTrans): ?>
                        <a href="<?= BASE_PATH ?>/pages/stock-transfers.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🔄</span>
                            <span style="font-size: 14px; font-weight: 500;">Transfer</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Column 2: Sales & People -->
                    <div style="padding: 8px; border-right: 1px solid #f3f4f6;">
                        <?php if ($hasSalesSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">💰 Sales & Billing</div>
                        <?php if ($qaInv): ?>
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🧾</span>
                            <span style="font-size: 14px; font-weight: 500;">Invoice</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaQuot): ?>
                        <a href="<?= BASE_PATH ?>/pages/quotations.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">📋</span>
                            <span style="font-size: 14px; font-weight: 500;">Quotation</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaSR): ?>
                        <a href="<?= BASE_PATH ?>/pages/sales-returns.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">↩️</span>
                            <span style="font-size: 14px; font-weight: 500;">Sales Return</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($hasSalesSec && $hasPeopleSec): ?>
                        <div style="height: 1px; background: #f3f4f6; margin: 8px 0;"></div>
                        <?php endif; ?>
                        
                        <?php if ($hasPeopleSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">👥 People</div>
                        <?php if ($qaCust): ?>
                        <a href="<?= BASE_PATH ?>/pages/customers.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">👥</span>
                            <span style="font-size: 14px; font-weight: 500;">Customer</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaSupp): ?>
                        <a href="<?= BASE_PATH ?>/pages/suppliers.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏭</span>
                            <span style="font-size: 14px; font-weight: 500;">Supplier</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaEmp): ?>
                        <a href="<?= BASE_PATH ?>/pages/employees.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">👤</span>
                            <span style="font-size: 14px; font-weight: 500;">Employee</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (($hasSalesSec || $hasPeopleSec) && $hasLocSec): ?>
                        <div style="height: 1px; background: #f3f4f6; margin: 8px 0;"></div>
                        <?php endif; ?>
                        
                        <?php if ($hasLocSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">🏢 Locations</div>
                        <?php if ($qaStore): ?>
                        <a href="<?= BASE_PATH ?>/pages/stores.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏪</span>
                            <span style="font-size: 14px; font-weight: 500;">Store</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaWh): ?>
                        <a href="<?= BASE_PATH ?>/pages/warehouses.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏭</span>
                            <span style="font-size: 14px; font-weight: 500;">Warehouse</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Column 3: Finance & Others -->
                    <div style="padding: 8px;">
                        <?php if ($hasFinSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">💸 Finance</div>
                        <?php if ($qaExp): ?>
                        <a href="<?= BASE_PATH ?>/pages/expenses.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">💰</span>
                            <span style="font-size: 14px; font-weight: 500;">Expense</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaProm): ?>
                        <a href="<?= BASE_PATH ?>/pages/promotions.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🎁</span>
                            <span style="font-size: 14px; font-weight: 500;">Promotion</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($hasFinSec && $hasHRSec): ?>
                        <div style="height: 1px; background: #f3f4f6; margin: 8px 0;"></div>
                        <?php endif; ?>
                        
                        <?php if ($hasHRSec): ?>
                        <div style="padding: 8px 12px; font-weight: 700; color: #4f82d5; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">👔 HR & Admin</div>
                        <?php if ($qaDept): ?>
                        <a href="<?= BASE_PATH ?>/pages/departments.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏢</span>
                            <span style="font-size: 14px; font-weight: 500;">Department</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaAtt): ?>
                        <a href="<?= BASE_PATH ?>/pages/attendance.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">✅</span>
                            <span style="font-size: 14px; font-weight: 500;">Attendance</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaLeave): ?>
                        <a href="<?= BASE_PATH ?>/pages/leave-management.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">🏖️</span>
                            <span style="font-size: 14px; font-weight: 500;">Leave Request</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($qaUser): ?>
                        <a href="<?= BASE_PATH ?>/pages/users.php" class="dropdown-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #374151; text-decoration: none; border-radius: 6px; transition: all 0.2s; margin: 2px 0;" onmouseover="this.style.background='#f0fdfa'; this.style.transform='translateX(4px)'" onmouseout="this.style.background='transparent'; this.style.transform='translateX(0)'">
                            <span style="font-size: 20px;">👤</span>
                            <span style="font-size: 14px; font-weight: 500;">User</span>
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php
$userIdForNotif = Session::getUserId();
// Get unread notifications count
try {
    $dbNotif = Database::getInstance();
    $notifResult = $dbNotif->queryOne("SELECT count(*) as cnt FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = FALSE", [$userIdForNotif]);
    $notificationCount = $notifResult['cnt'] ?? 0;
    
    // Get top 5 notifications
    $headerNotifs = $dbNotif->query("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC LIMIT 5", [$userIdForNotif]);
} catch (Exception $e) {
    $notificationCount = 0;
    $headerNotifs = [];
}
?>
        <!-- Notifications -->
        <div class="dropdown" style="position: relative; display: inline-block;">
            <button class="header-icon-btn" id="notificationBtn" title="Notifications" style="position: relative;">
                <span style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">
                    <?php include __DIR__ . '/../assets/icons/utility/notification.svg'; ?>
                </span>
                <?php if ($notificationCount > 0): ?>
                    <span class="badge" style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; border-radius: 10px; padding: 2px 6px; font-size: 11px; font-weight: 600;"><?= $notificationCount ?></span>
                <?php endif; ?>
            </button>
            <div id="notificationDropdown" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; margin-top: 8px; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 320px; max-height: 400px; overflow-y: auto; z-index: 1000;">
                <div style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 600; color: #111827; display: flex; justify-content: space-between;">
                    <span>Notifications (<?= $notificationCount ?>)</span>
                    <?php if($notificationCount > 0): ?>
                        <a href="<?= BASE_PATH ?>/pages/api/mark_all_read.php" style="font-size: 12px; color: #4A6FA5; font-weight: normal; text-decoration: none;">Mark all read</a>
                    <?php endif; ?>
                </div>
                <div style="padding: 8px 0;">
                    <?php if (empty($headerNotifs)): ?>
                        <div style="padding: 16px; text-align: center; color: #6b7280; font-size: 13px;">No notifications yet.</div>
                    <?php else: ?>
                        <?php foreach($headerNotifs as $notif): ?>
                            <?php 
                                $dotColor = '#3b82f6'; // info / blue
                                if ($notif['type'] == 'success') $dotColor = '#10b981';
                                if ($notif['type'] == 'warning') $dotColor = '#f59e0b';
                                if ($notif['type'] == 'danger') $dotColor = '#ef4444';
                                
                                $bg = $notif['is_read'] ? 'white' : '#f0f9ff';
                            ?>
                            <div class="notification-item" style="padding: 12px 16px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background 0.2s; background: <?= $bg ?>;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='<?= $bg ?>'">
                                <div style="display: flex; gap: 12px;">
                                    <div style="width: 8px; height: 8px; background: <?= $dotColor ?>; border-radius: 50%; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 500; color: #111827; margin-bottom: 4px;"><?= htmlspecialchars($notif['title']) ?></div>
                                        <div style="font-size: 13px; color: #6b7280;"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                                            <?php 
                                            // Crude relative time
                                            $elapsed = time() - strtotime($notif['created_at']);
                                            if ($elapsed < 60) echo "Just now";
                                            elseif ($elapsed < 3600) echo floor($elapsed/60)." mins ago";
                                            elseif ($elapsed < 86400) echo floor($elapsed/3600)." hrs ago";
                                            else echo floor($elapsed/86400)." days ago";
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div style="padding: 12px 16px; border-top: 1px solid #e5e7eb; text-align: center;">
                    <a href="<?= BASE_PATH ?>/pages/notifications.php" style="color: #4A6FA5; text-decoration: none; font-size: 14px; font-weight: 500;">View All Notifications</a>
                </div>
            </div>
        </div>

        <!-- User Profile -->
        <div class="header-user dropdown">
            <div class="header-user-avatar"><?= $userInitials ?></div>
            <div class="header-user-info">
                <div class="header-user-name" id="headerUserName"><?= htmlspecialchars($userName) ?></div>
                <div class="header-user-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>
    </div>
</header>
<!-- ApexCharts loaded early (outside main) so it's ready before dashboards -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>

<script>
// Quick Add Dropdown
document.addEventListener('DOMContentLoaded', function() {
    const quickAddBtn = document.getElementById('quickAddBtn');
    const quickAddDropdown = document.getElementById('quickAddDropdown');
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    // Quick Add Toggle
    if (quickAddBtn && quickAddDropdown) {
        quickAddBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            quickAddDropdown.style.display = quickAddDropdown.style.display === 'none' ? 'block' : 'none';
            if (notificationDropdown) notificationDropdown.style.display = 'none';
        });
        
        // Allow clicks inside dropdown to propagate naturally (for link navigation)
        quickAddDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Notification Toggle
    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.style.display = notificationDropdown.style.display === 'none' ? 'block' : 'none';
            if (quickAddDropdown) quickAddDropdown.style.display = 'none';
        });
        
        // Allow clicks inside dropdown to propagate naturally (for link navigation)
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        if (quickAddDropdown) quickAddDropdown.style.display = 'none';
        if (notificationDropdown) notificationDropdown.style.display = 'none';
    });
});
</script>

