<?php
/**
 * Sales Executive Dashboard
 * Complete billing interface with performance tracking
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Require sales executive or higher role
$allowedRoles = ['super_admin', 'admin', 'store_manager', 'sales_executive'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Prevent caching so dashboard always shows latest numbers on refresh
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$userId = Session::getUserId();
$userName = Session::getUserName();

// One-click: insert a test invoice so dashboard shows real data (admin/super_admin only)
if (isset($_GET['seed']) && $_GET['seed'] === '1' && in_array(Session::getUserRole(), ['super_admin', 'admin'])) {
    try {
        $today = date('Y-m-d');
        $invNum = 'INV-TEST-' . time();
        $db->execute(
            "INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_status, status, notes, created_by) 
             VALUES (?, NULL, ?, ?, 500.00, 0, 0, 500.00, 0, 'pending', 'draft', 'Test invoice - delete if not needed', ?)",
            [$invNum, $today, $today, $userId]
        );
        Session::setFlash('Test invoice created. Dashboard should update in a few seconds (or refresh the page).', 'success');
    } catch (Exception $e) {
        Session::setFlash('Could not create test invoice: ' . $e->getMessage(), 'error');
    }
    header('Location: ' . BASE_PATH . '/pages/dashboards/sales-executive.php');
    exit;
}

// Defaults (used if a section fails or no data)
$todaysSales = $todaysInvoices = $todaysAverage = 0;
$targetProgress = $targetRemaining = 0;
$myWeeklySales = ['total' => 0, 'count' => 0];
$myMonthlySales = ['total' => 0, 'count' => 0];
$commissionEarned = $customersToday = 0;
$trendLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$trendValues = [0, 0, 0, 0, 0, 0, 0];
$availableProducts = $myRecentSales = $lowStockProducts = [];
$topProductsToday = $paymentModes = $teamStats = [];
$myRank = 1;
$last7DaysSales = null;
$last7DaysCount = null;
$totalInvoicesCount = null;

// Get user's profile and sales target (safe: users table always exists for logged-in user)
try {
    $userInfo = $db->queryOne("SELECT * FROM users WHERE {$orgFilter} id = ?", [$userId]);
} catch (Exception $e) {
    $userInfo = [];
}
$salesTarget = (!empty($userInfo['daily_sales_target'])) 
    ? (float)$userInfo['daily_sales_target'] : 10000;
$maxDiscountPercent = $userInfo['max_discount_percent'] ?? 10;
$commissionPercent = $userInfo['commission_percent'] ?? 0;

// === SIMPLE: jo DB me hai wahi dikhao (direct queries, no complex filters) ===
$totalInvoicesCount = 0;
$todaysSales = 0;
$todaysInvoices = 0;
$todaysAverage = 0;
$customersToday = 0;
$last7DaysSales = 0;
$last7DaysCount = 0;
$customersLast7 = 0;
$customersAllTime = 0;
$targetProgress = 0;
$targetRemaining = $salesTarget;

try {
    $totalInvoicesCount = (int)$db->queryOne("SELECT COUNT(*) as c FROM invoices WHERE created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId])['c'];

    $rowToday = $db->queryOne("SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND created_by = ?", [$userId]);
    $todaysInvoices = (int)($rowToday['c'] ?? 0);
    $todaysSales = (float)($rowToday['total'] ?? 0);
    $todaysAverage = $todaysInvoices > 0 ? ($todaysSales / $todaysInvoices) : 0;

    $row7 = $db->queryOne("SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId]);
    $last7DaysCount = (int)($row7['c'] ?? 0);
    $last7DaysSales = (float)($row7['total'] ?? 0);

    // Customers served: distinct customer_id (NULL = walk-in, count as unique per invoice via id)
    $rowCust = $db->queryOne("SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND created_by = ?", [$userId]);
    $customersToday = (int)($rowCust['c'] ?? 0);
    $rowCust7 = $db->queryOne("SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId]);
    $customersLast7 = (int)($rowCust7['c'] ?? 0);
    $rowCustAll = $db->queryOne("SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId]);
    $customersAllTime = (int)($rowCustAll['c'] ?? 0);

    $targetProgress = $salesTarget > 0 ? min(100, ($todaysSales / $salesTarget) * 100) : 0;
    $targetRemaining = max(0, $salesTarget - $todaysSales);
    
} catch (Exception $e) {
    error_log("Sales Dashboard (invoices): " . $e->getMessage());
}

// Agar aaj + last7 dono 0 hai par DB me invoices hain to "all time" dikhao
$allTimeSales = 0;
$allTimeCount = 0;
if ($totalInvoicesCount > 0 && $todaysInvoices == 0 && $last7DaysCount == 0) {
    try {
        $rowAll = $db->queryOne("SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId]);
        $allTimeCount = (int)($rowAll['c'] ?? 0);
        $allTimeSales = (float)($rowAll['total'] ?? 0);
    } catch (Exception $e) {}
}

try {
    $myWeeklySales = $db->queryOne("SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE YEARWEEK(invoice_date) = YEARWEEK(CURDATE()) AND created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$userId]);
    $myWeeklySales = $myWeeklySales ?: ['total' => 0, 'count' => 0];
    $myWeeklySales['total'] = (float)$myWeeklySales['total'];
} catch (Exception $e) { $myWeeklySales = ['total' => 0, 'count' => 0]; }
try {
    $myMonthlySales = $db->queryOne("SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE()) AND created_by = ?", [$userId]);
    $myMonthlySales = $myMonthlySales ?: ['total' => 0, 'count' => 0];
    $myMonthlySales['total'] = (float)$myMonthlySales['total'];
} catch (Exception $e) { $myMonthlySales = ['total' => 0, 'count' => 0]; }
$commissionEarned = $commissionPercent > 0 ? ($myMonthlySales['total'] * $commissionPercent / 100) : 0;

try {
    $dailyTrend = $db->query("SELECT DATE(invoice_date) as dt, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND created_by = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . " GROUP BY DATE(invoice_date) ORDER BY dt", [$userId]);
} catch (Exception $e) { $dailyTrend = []; }

$trendLabels = [];
$trendValues = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('d M', strtotime($d));
    $val = 0;
    foreach ($dailyTrend as $r) {
        if (isset($r['dt']) && $r['dt'] == $d) { $val = (float)$r['total']; break; }
    }
    $trendValues[] = $val;
}

try {
    $myRecentSales = $db->query("SELECT i.*, c.name as customer_name, c.phone as customer_phone FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id" . ($orgIdPatch ? " WHERE i.organization_id = " . intval($orgIdPatch) : "") . " ORDER BY i.id DESC LIMIT 10");
} catch (Exception $e) { $myRecentSales = []; }

// === PRODUCTS (quick lookup, low stock) ===
try {
    $availableProducts = $db->query("
        SELECT p.id, p.name, p.sku, p.barcode, p.selling_price, p.stock_quantity, 
               p.min_stock_level, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE " . ($orgIdPatch ? " p.organization_id = " . intval($orgIdPatch) . " AND " : "") . " p.status = 'active' AND p.stock_quantity > 0
        ORDER BY p.name ASC
        LIMIT 50
    ");
    $lowStockProducts = $db->query("
        SELECT name, stock_quantity, min_stock_level
        FROM products
        WHERE {$orgFilter} stock_quantity > 0 AND stock_quantity <= min_stock_level
        AND status = 'active'
        ORDER BY stock_quantity ASC
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Sales Dashboard (products): " . $e->getMessage());
}

// === INVOICE ITEMS (top products today, payment modes) ===
try {
    $topProductsToday = $db->query("
        SELECT p.name, SUM(ii.quantity) as qty_sold, SUM(ii.line_total) as total_value
        FROM invoice_items ii
        INNER JOIN invoices i ON ii.invoice_id = i.id
        INNER JOIN products p ON ii.product_id = p.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " DATE(i.invoice_date) = CURDATE() 
        AND i.created_by = ?
        AND i.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY qty_sold DESC
        LIMIT 5
    ", [$userId]);
    $paymentModes = $db->query("
        SELECT payment_method, COUNT(*) as count, SUM(total_amount) as total
        FROM invoices
        WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND created_by = ?
        GROUP BY payment_method
    ", [$userId]);
} catch (Exception $e) {
    error_log("Sales Dashboard (invoice items): " . $e->getMessage());
}

// === TEAM STATS (leaderboard) ===
try {
    $teamStats = $db->query("
        SELECT u.full_name, u.username, COALESCE(SUM(i.total_amount), 0) as total_sales
        FROM users u
        LEFT JOIN invoices i ON u.id = i.created_by 
            AND MONTH(i.invoice_date) = MONTH(CURDATE())
            AND YEAR(i.invoice_date) = YEAR(CURDATE())
            AND i.status != 'cancelled'
        WHERE " . ($orgIdPatch ? "u.organization_id = " . intval($orgIdPatch) . " AND " : "") . "u.role IN ('sales_executive', 'store_manager', 'admin', 'super_admin') AND u.status = 'active'
        GROUP BY u.id, u.full_name, u.username
        ORDER BY total_sales DESC
        LIMIT 5
    ");
    foreach ($teamStats as $index => $member) {
        if (isset($member['username']) && $member['username'] == $userName) {
            $myRank = $index + 1;
            break;
        }
    }
} catch (Exception $e) {
    error_log("Sales Dashboard (team): " . $e->getMessage());
}

function formatCurrency($amount) {
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . 'L';
    } elseif ($amount >= 1000) {
        return '₹' . number_format($amount / 1000, 2) . 'K';
    }
    return '₹' . number_format($amount, 2);
}

function getTimeGreeting() {
    $hour = date('H');
    if ($hour < 12) return 'Good Morning';
    if ($hour < 17) return 'Good Afternoon';
    return 'Good Evening';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Stocksathi</title>
    <meta name="description" content="Sales Executive Dashboard for quick billing and performance tracking">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../../_includes/header.php'; ?>
            
            <main class="content">
    <!-- ApexCharts must be inside main for PJAX -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<style>
        .sales-header {
            background: linear-gradient(135deg, #3a63a5 0%, #4f82d5 50%, #4f82d5 100%);
            padding: 32px; border-radius: 10px; color: white; margin-bottom: 24px;
            position: relative; overflow: hidden;
            border: none; box-shadow: 0 8px 32px rgba(79, 130, 213, 0.3);
        }
        .sales-header::before {
            content: ''; position: absolute; top: -50%; right: -10%;
            width: 250px; height: 250px; background: rgba(255,255,255,0.1);
            border-radius: 50%; pointer-events: none;
        }
        
        .target-section {
            background: rgba(255,255,255,0.1); border-radius: 8px; padding: 20px;
            margin-top: 20px; backdrop-filter: blur(10px);
        }
        .target-bar {
            background: rgba(255,255,255,0.2); height: 20px; border-radius: 10px;
            overflow: hidden; margin-top: 12px;
        }
        .target-fill {
            height: 100%; border-radius: 10px;
            transition: width 0.5s ease-out;
        }
        .target-fill.good { background: linear-gradient(90deg, #4ade80, #22c55e); }
        .target-fill.warning { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
        .target-fill.danger { background: linear-gradient(90deg, #f87171, #ef4444); }
        
        .quick-stats {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
            margin-bottom: 24px;
        }
        .quick-stat {
            background: white; border-radius: 8px; padding: 24px;
            border: 1px solid #E5E7EB; text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            min-height: 140px; display: flex; flex-direction: column; justify-content: center;
        }
        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(91, 141, 184, 0.12);
            border-color: #7BA3C7;
        }
        .quick-stat-icon { 
            font-size: 36px; margin-bottom: 12px; 
            display: block; line-height: 1;
        }
        .quick-stat-value { 
            font-size: 28px; font-weight: 700; color: #0F172A; 
            margin: 8px 0; line-height: 1.2;
        }
        .quick-stat-label { 
            font-size: 12px; color: #64748B; margin-top: 8px; 
            font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .new-sale-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important; 
            border: none; padding: 16px 32px; border-radius: 12px;
            font-size: 18px; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none !important;
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
            letter-spacing: 0.5px;
        }
        .new-sale-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.5);
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff !important;
        }
        
        .product-search {
            position: relative; margin-bottom: 16px;
        }
        .product-search input {
            padding-right: 100px;
        }
        .barcode-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: var(--color-primary); color: white; border: none;
            padding: 8px 12px; border-radius: 6px; cursor: pointer;
            font-size: 12px;
        }
        
        .product-list {
            max-height: 300px; overflow-y: auto;
        }
        .product-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px; border-radius: 8px; margin-bottom: 8px;
            background: var(--bg-secondary); cursor: pointer;
            transition: background 0.2s ease;
        }
        .product-item:hover { background: var(--bg-tertiary); }
        .product-item.low-stock { border-left: 3px solid #f59e0b; }
        .product-item.out-of-stock { opacity: 0.5; cursor: not-allowed; }
        
        .recent-sale {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 0; border-bottom: 1px solid var(--border-light);
        }
        .recent-sale:last-child { border-bottom: none; }
        
        .rank-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .rank-badge.gold { background: #fef3c7; color: #92400e; }
        .rank-badge.silver { background: #e5e7eb; color: #374151; }
        .rank-badge.bronze { background: #fed7aa; color: #9a3412; }
        .rank-badge.default { background: #e0f2fe; color: #0369a1; }
        
        .action-buttons {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;
        }
        .action-btn {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 16px; background: white; border: 1px solid var(--border-light);
            border-radius: 8px; text-decoration: none; color: var(--text-primary);
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            border-color: #4f82d5;
            background: #E8EDF5;
            color: #3a63a5;
        }
        .action-btn-icon { font-size: 28px; }
        .action-btn-text { font-size: 13px; font-weight: 500; }
        
        .chart-container { position: relative; height: 200px; }
        
        .alert-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px; background: #fef3c7; border-radius: 8px;
            margin-bottom: 8px; border-left: 4px solid #f59e0b;
        }
        .alert-item.info { background: #e0f2fe; border-left-color: #0ea5e9; }
        
        @media (max-width: 1200px) {
            .quick-stats { grid-template-columns: repeat(2, 1fr); }
            .action-buttons { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .quick-stats { grid-template-columns: 1fr; }
        }
    </style>
                <!-- Sales Header with Target -->
                <div class="sales-header">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1;">
                        <div>
                            <p style="margin: 0 0 4px 0; opacity: 0.9; font-size: 14px; color: white;"><?= getTimeGreeting() ?>,</p>
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700;"><?= htmlspecialchars($userInfo['full_name'] ?? $userName) ?> 🛍️</h1>
                            <p style="margin: 8px 0 0 0; opacity: 0.85; color: white;"><?= date('l, F j, Y') ?> • <?= date('h:i A') ?></p>
                        </div>
                        <div>
                            <?php if (hasPermission('create_invoice')): ?>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php?action=new" class="new-sale-btn">
                                ➕ New Sale
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Daily Target Progress (live-updated via JS) -->
                    <div class="target-section">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 14px; opacity: 0.9;">🎯 Today's Target</div>
                                <div style="font-size: 20px; font-weight: 700; margin-top: 4px;" id="targetText">
                                    <?= formatCurrency($todaysSales) ?> / <?= formatCurrency($salesTarget) ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 36px; font-weight: 800;" id="targetPercent"><?= number_format($targetProgress, 0) ?>%</div>
                                <div style="font-size: 12px; opacity: 0.8;" id="targetRemaining"><?= $targetRemaining > 0 ? formatCurrency($targetRemaining) . ' to go!' : '🎉 Target achieved!' ?></div>
                            </div>
                        </div>
                        <div class="target-bar">
                            <div class="target-fill <?= $targetProgress >= 100 ? 'good' : ($targetProgress >= 50 ? 'warning' : 'danger') ?>" 
                                 id="targetBar" style="width: <?= min(100, $targetProgress) ?>%"></div>
                        </div>
                    </div>
                </div>

                <?php
                $flash = Session::getFlash();
                if ($flash && !empty($flash['message'])):
                    $flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'danger';
                ?>
                <div class="alert alert-<?= $flashType ?>" style="margin-bottom: 20px;"><?= htmlspecialchars($flash['message']) ?></div>
                <?php endif; ?>

                <?php if ($totalInvoicesCount !== null && $totalInvoicesCount === 0): ?>
                <div class="card mb-6" id="noSalesYetCard" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px dashed #0ea5e9;">
                    <div class="card-body" style="text-align: center; padding: 40px 24px;">
                        <div style="font-size: 48px; margin-bottom: 16px;">🛒</div>
                        <h3 style="margin: 0 0 8px 0; font-size: 20px; color: #0c4a6e;">No sales yet</h3>
                        <p style="margin: 0 0 20px 0; color: #0369a1;">Create your first invoice to see sales and targets here. Data refreshes every 15 sec.</p>
                        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                            <a href="<?= BASE_PATH ?>/pages/invoice-form.php" class="new-sale-btn" style="display: inline-flex;">➕ Create your first sale</a>
                            <?php if (in_array(Session::getUserRole(), ['super_admin', 'admin'])): ?>
                            <a href="<?= BASE_PATH ?>/pages/dashboards/sales-executive.php?seed=1" class="btn" style="display: inline-flex; background: #0ea5e9; color: #ffffff !important; padding: 16px 32px; border-radius: 12px; text-decoration: none; font-weight: 700; box-shadow: 0 10px 20px -5px rgba(14, 165, 233, 0.4); transition: all 0.3s ease;">🔧 Insert test invoice</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Stats: sirf DB ka data (today / last 7 / all time) -->
                <?php
                $showSales = $todaysSales; $showCount = $todaysInvoices; $showLabel = "Today's Sales"; $showInvLabel = "Invoices Today"; $showCustomers = $customersToday;
                if ($todaysInvoices == 0 && $todaysSales == 0) {
                    if ($last7DaysCount > 0 || $last7DaysSales > 0) {
                        $showSales = $last7DaysSales; $showCount = $last7DaysCount; $showLabel = "Sales (Last 7 days)"; $showInvLabel = "Invoices (Last 7 days)"; $showCustomers = $customersLast7;
                    } elseif ($allTimeCount > 0) {
                        $showSales = $allTimeSales; $showCount = $allTimeCount; $showLabel = "Total Sales"; $showInvLabel = "Total Invoices"; $showCustomers = $customersAllTime;
                    }
                }
                $showAvg = ($showCount > 0) ? ($showSales / $showCount) : 0;
                ?>
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-icon">💰</div>
                        <div class="quick-stat-value" id="statSales"><?= formatCurrency($showSales) ?></div>
                        <div class="quick-stat-label" id="statSalesLabel"><?= $showLabel ?></div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon">🧾</div>
                        <div class="quick-stat-value" id="statInvoices"><?= $showCount ?></div>
                        <div class="quick-stat-label" id="statInvoicesLabel"><?= $showInvLabel ?></div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon">📊</div>
                        <div class="quick-stat-value" id="statAvg"><?= formatCurrency($showAvg) ?></div>
                        <div class="quick-stat-label">Avg. Sale Value</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon">👥</div>
                        <div class="quick-stat-value" id="statCustomers"><?= $showCustomers ?></div>
                        <div class="quick-stat-label">Customers Served</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="action-buttons">
                            <?php if (hasPermission('create_invoice')): ?>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php?action=new" class="action-btn">
                                <span class="action-btn-icon">🧾</span>
                                <span class="action-btn-text">New Invoice</span>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('view_products')): ?>
                            <a href="<?= BASE_PATH ?>/pages/products.php" class="action-btn">
                                <span class="action-btn-icon">📦</span>
                                <span class="action-btn-text">Check Stock</span>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('create_customers')): ?>
                            <a href="<?= BASE_PATH ?>/pages/customers.php?action=new" class="action-btn">
                                <span class="action-btn-icon">👤</span>
                                <span class="action-btn-text">Add Customer</span>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission('process_returns')): ?>
                            <a href="<?= BASE_PATH ?>/pages/sales-returns.php?action=new" class="action-btn">
                                <span class="action-btn-icon">↩️</span>
                                <span class="action-btn-text">Process Return</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Product Quick Search -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🔍 Quick Product Lookup</h3>
                        </div>
                        <div class="card-body">
                            <div class="product-search">
                                <input type="text" id="productSearch" class="form-control" 
                                       placeholder="Search by name, SKU, or barcode...">
                            </div>
                            <div class="product-list" id="productList">
                                <?php if (empty($availableProducts)): ?>
                                    <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                        No products available
                                    </p>
                                <?php else: ?>
                                    <?php foreach ($availableProducts as $product): ?>
                                    <div class="product-item <?= $product['stock_quantity'] <= $product['min_stock_level'] ? 'low-stock' : '' ?>"
                                         data-name="<?= strtolower(htmlspecialchars($product['name'])) ?>"
                                         data-sku="<?= strtolower($product['sku'] ?? '') ?>"
                                         data-barcode="<?= strtolower($product['barcode'] ?? '') ?>">
                                        <div>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-secondary);">
                                                SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>
                                                <?php if ($product['category_name']): ?>
                                                • <?= htmlspecialchars($product['category_name']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 700; color: var(--color-primary);">
                                                ₹<?= number_format($product['selling_price'], 2) ?>
                                            </div>
                                            <div style="font-size: 11px; color: <?= $product['stock_quantity'] <= $product['min_stock_level'] ? '#f59e0b' : 'var(--text-secondary)' ?>;">
                                                <?= $product['stock_quantity'] ?> in stock
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- My Recent Sales -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">📋 My Recent Sales</h3>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php?user=<?= $userId ?>" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 380px; overflow-y: auto;">
                            <?php if (empty($myRecentSales)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No sales yet today. Start selling! 🚀
                                </p>
                            <?php else: ?>
                                <?php foreach ($myRecentSales as $sale): ?>
                                <div class="recent-sale">
                                    <div>
                                        <div style="font-weight: 600;">
                                            <code><?= htmlspecialchars($sale['invoice_number'] ?? 'INV-' . $sale['id']) ?></code>
                                        </div>
                                        <div style="font-size: 12px; color: var(--text-secondary);">
                                            <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?>
                                            • <?= date('h:i A', strtotime($sale['created_at'])) ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 700; color: var(--color-success);">
                                            <?= formatCurrency($sale['total_amount']) ?>
                                        </div>
                                        <span class="badge badge-<?= $sale['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($sale['payment_status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Performance & Alerts -->
                <div class="grid grid-cols-3 gap-6 mb-6">
                    <!-- Weekly Trend Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📈 My Weekly Trend</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div id="trendChart" style="height: 100%;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                                <div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">This Week</div>
                                    <div style="font-size: 18px; font-weight: 700;"><?= formatCurrency($myWeeklySales['total']) ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 12px; color: var(--text-secondary);">This Month</div>
                                    <div style="font-size: 18px; font-weight: 700;"><?= formatCurrency($myMonthlySales['total']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Ranking -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏆 Team Leaderboard</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teamStats)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 30px 0;">
                                    No team data available
                                </p>
                            <?php else: ?>
                                <?php foreach ($teamStats as $index => $member): ?>
                                <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-light); <?= $member['username'] == $userName ? 'background: #f0fdf4; margin: 0 -16px; padding-left: 16px; padding-right: 16px;' : '' ?>">
                                    <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $index == 0 ? '#fbbf24' : ($index == 1 ? '#9ca3af' : ($index == 2 ? '#f97316' : 'var(--bg-tertiary)')) ?>; color: <?= $index < 3 ? 'white' : 'var(--text-secondary)' ?>; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; margin-right: 10px;">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: <?= $member['username'] == $userName ? '700' : '500' ?>;">
                                            <?= htmlspecialchars($member['full_name'] ?? $member['username']) ?>
                                            <?= $member['username'] == $userName ? '(You)' : '' ?>
                                        </div>
                                    </div>
                                    <div style="font-weight: 600; color: var(--color-success);">
                                        <?= formatCurrency($member['total_sales']) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div style="text-align: center; margin-top: 16px;">
                                    <span class="rank-badge <?= $myRank == 1 ? 'gold' : ($myRank == 2 ? 'silver' : ($myRank == 3 ? 'bronze' : 'default')) ?>">
                                        <?= $myRank == 1 ? '🥇' : ($myRank == 2 ? '🥈' : ($myRank == 3 ? '🥉' : '🏅')) ?>
                                        You're #<?= $myRank ?> this month!
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚠️ Low Stock Alert</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($lowStockProducts)): ?>
                                <div class="alert-item info">
                                    <span>✅</span>
                                    <span>All products are well stocked!</span>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">
                                    Inform customers about availability:
                                </p>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <div class="alert-item">
                                    <span style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></span>
                                    <span style="margin-left: auto; font-weight: 600; color: #92400e;">
                                        Only <?= $product['stock_quantity'] ?> left
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if ($commissionPercent > 0): ?>
                            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                                <div style="font-size: 12px; color: var(--text-secondary);">💵 Commission Earned (This Month)</div>
                                <div style="font-size: 20px; font-weight: 700; color: var(--color-success); margin-top: 4px;">
                                    <?= formatCurrency($commissionEarned) ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-tertiary);">
                                    <?= $commissionPercent ?>% of <?= formatCurrency($myMonthlySales['total']) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products Sold Today -->
                <?php if (!empty($topProductsToday)): ?>
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">🔥 Top Products I Sold Today</h3>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Quantity Sold</th>
                                    <th>Total Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProductsToday as $index => $product): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                                    <td><?= $product['qty_sold'] ?> units</td>
                                    <td><strong style="color: var(--color-success);"><?= formatCurrency($product['total_value']) ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Discount & Permission Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">ℹ️ My Permissions</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                            <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
                                <div style="font-size: 12px; color: var(--text-secondary);">Max Discount Allowed</div>
                                <div style="font-size: 20px; font-weight: 700;"><?= $maxDiscountPercent ?>%</div>
                            </div>
                            <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
                                <div style="font-size: 12px; color: var(--text-secondary);">Commission Rate</div>
                                <div style="font-size: 20px; font-weight: 700;"><?= $commissionPercent ?>%</div>
                            </div>
                            <div style="padding: 12px 16px; background: var(--bg-secondary); border-radius: 8px;">
                                <div style="font-size: 12px; color: var(--text-secondary);">Daily Target</div>
                                <div style="font-size: 20px; font-weight: 700;"><?= formatCurrency($salesTarget) ?></div>
                            </div>
                            <div style="padding: 12px 16px; background: #f0fdf4; border-radius: 8px; flex: 1;">
                                <div style="font-size: 12px; color: #166534;">💡 Tip of the Day</div>
                                <div style="font-size: 14px; color: #166534; margin-top: 4px;">
                                    <?php
                                    $tips = [
                                        "Always greet customers with a smile! 😊",
                                        "Suggest complementary products to increase sale value.",
                                        "Remember to inform about ongoing promotions.",
                                        "Check stock before promising availability.",
                                        "Collect customer contact for future offers."
                                    ];
                                    echo $tips[array_rand($tips)];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
<script>// Self-executing function so it works on direct load and AJAX load
function initSalesChart() {
        // Product search functionality
        document.getElementById('productSearch')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.product-item');
            
            items.forEach(item => {
                const name = item.getAttribute('data-name') || '';
                const sku = item.getAttribute('data-sku') || '';
                const barcode = item.getAttribute('data-barcode') || '';
                
                if (name.includes(searchTerm) || sku.includes(searchTerm) || barcode.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function drawChart() {
            if (window.salesExecutiveChart) window.salesExecutiveChart.destroy();
            const trendEl = document.getElementById('trendChart');
            if (trendEl) {
                const trendData = <?= json_encode($trendValues) ?>;
                const hasData = trendData.some(v => v > 0);
                
                if (hasData) {
                    var options = {
                        series: [{
                            name: 'Sales (₹)',
                            data: trendData
                        }],
                        chart: {
                            type: 'bar',
                            height: 200,
                            toolbar: { show: false }
                        },
                        colors: ['rgba(79, 130, 213, 0.7)'],
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                horizontal: false,
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            show: true,
                            width: 1,
                            colors: ['rgb(79, 130, 213)']
                        },
                        xaxis: {
                            categories: <?= json_encode($trendLabels) ?>,
                            labels: {
                                hideOverlappingLabels: true,
                                rotate: -45,
                                style: {
                                    fontSize: '10px'
                                }
                            },
                            tickAmount: 10
                        },
                        yaxis: {
                            labels: {
                                formatter: function (value) {
                                    if (value >= 1000) return '₹' + (value/1000).toFixed(0) + 'K';
                                    return '₹' + value;
                                }
                            }
                        },
                        grid: {
                            borderColor: 'rgba(0,0,0,0.05)'
                        },
                        tooltip: {
                            y: {
                                formatter: function (value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    };
                    window.salesExecutiveChart = new ApexCharts(trendEl, options);
                    window.salesExecutiveChart.render();
                } else {
                    trendEl.innerHTML = '<div style="text-align:center;padding:60px;color:var(--text-secondary);"><p>No sales data available for the last 30 days</p></div>';
                }
            }
        }
        var raf = window.requestAnimationFrame || function(f){setTimeout(f,16);};
        raf(function(){ raf(drawChart); });
    }

        if (typeof ApexCharts === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
            script.onload = initSalesChart;
            document.head.appendChild(script);
        } else {
            initSalesChart();
        }</script>
</main>
        </div>
    </div>

    
</body>
</html>
