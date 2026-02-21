<?php
/**
 * Store Manager Dashboard
 * Comprehensive store operations overview
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Require store manager or higher role
$allowedRoles = ['super_admin', 'admin', 'store_manager'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$userId = Session::getUserId();
$userName = Session::getUserName();

// Get user's assigned store info
$userInfo = $db->queryOne("SELECT * FROM users WHERE {$orgFilter} id = ?", [$userId]);
$assignedStoreId = $userInfo['assigned_store_id'] ?? null;
$assignedWarehouseId = $userInfo['assigned_warehouse_id'] ?? null;

// Get store details if assigned
$storeInfo = null;
if ($assignedStoreId) {
    $storeInfo = $db->queryOne("SELECT * FROM stores WHERE {$orgFilter} id = ?", [$assignedStoreId]);
}

try {
    // === TODAY'S STORE PERFORMANCE ===
    $todaySales = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
        FROM invoices 
        WHERE {$orgFilter} DATE(invoice_date) = CURDATE()
        AND status != 'cancelled'
    ");
    
    // Yesterday's sales for comparison
    $yesterdaySales = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE {$orgFilter} DATE(invoice_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        AND status != 'cancelled'
    ")['total'];
    
    $salesGrowth = $yesterdaySales > 0 ? (($todaySales['total'] - $yesterdaySales) / $yesterdaySales) * 100 : 0;
    
    // This week's sales
    $weekSales = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
        FROM invoices 
        WHERE YEARWEEK(invoice_date) = YEARWEEK(CURDATE())
        AND status != 'cancelled'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ");
    
    // This month's sales
    $monthSales = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
        FROM invoices 
        WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE()) 
        AND YEAR(invoice_date) = YEAR(CURDATE())
        AND status != 'cancelled'
    ");
    
    // === INVENTORY OVERVIEW ===
    $totalProducts = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} status = 'active'")['count'];
    $lowStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} stock_quantity > 0 AND stock_quantity <= min_stock_level")['count'];
    $outOfStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} stock_quantity = 0 AND status = 'active'")['count'];
    $stockValue = $db->queryOne("SELECT COALESCE(SUM(purchase_price * stock_quantity), 0) as total FROM products {$orgWhere}")['total'];
    
    // === CUSTOMER INSIGHTS ===
    $totalCustomers = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE {$orgFilter} status = 'active'")['count'];
    $customersToday = $db->queryOne("SELECT COUNT(DISTINCT customer_id) as count FROM invoices WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND status != 'cancelled'")['count'];
    $pendingPayments = $db->queryOne("SELECT COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as total FROM invoices WHERE {$orgFilter} (payment_status = 'unpaid' OR payment_status = 'partial') AND status != 'cancelled'")['total'];
    
    // === TEAM PERFORMANCE ===
    $teamPerformance = $db->query("
        SELECT u.id, u.full_name, u.username, u.role,
               COALESCE(SUM(i.total_amount), 0) as today_sales,
               COUNT(i.id) as today_invoices
        FROM users u
        LEFT JOIN invoices i ON u.id = i.created_by AND DATE(i.invoice_date) = CURDATE() AND i.status != 'cancelled'
        WHERE " . ($orgIdPatch ? "u.organization_id = " . intval($orgIdPatch) . " AND " : "") . "u.role IN ('sales_executive', 'store_manager') AND u.status = 'active'
        GROUP BY u.id, u.full_name, u.username, u.role
        ORDER BY today_sales DESC
        LIMIT 10
    ");
    
    // === LOW STOCK PRODUCTS ===
    $lowStockProducts = $db->query("
        SELECT p.id, p.name, p.sku, p.stock_quantity, p.min_stock_level, p.reorder_level,
               c.name as category_name, b.name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE " . ($orgIdPatch ? "p.organization_id = " . intval($orgIdPatch) . " AND " : "") . "p.stock_quantity > 0 AND p.stock_quantity <= p.min_stock_level
        ORDER BY p.stock_quantity ASC
        LIMIT 10
    ");
    
    // === OUT OF STOCK PRODUCTS ===
    $outOfStockProducts = $db->query("
        SELECT p.id, p.name, p.sku, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE " . ($orgIdPatch ? "p.organization_id = " . intval($orgIdPatch) . " AND " : "") . "p.stock_quantity = 0 AND p.status = 'active'
        ORDER BY p.name ASC
        LIMIT 5
    ");
    
    // === RECENT INVOICES ===
    $recentInvoices = $db->query("
        SELECT i.*, c.name as customer_name, u.full_name as created_by_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE " . ($orgIdPatch ? "i.organization_id = " . intval($orgIdPatch) : "1=1") . "
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    
    // === PENDING ACTIONS ===
    $pendingReturns = $db->queryOne("SELECT COUNT(*) as count FROM sales_returns WHERE {$orgFilter} status = 'pending'")['count'];
    $pendingQuotations = $db->queryOne("SELECT COUNT(*) as count FROM quotations WHERE {$orgFilter} status = 'sent'")['count'];
    
    // === SALES BY HOUR (Today) ===
    $salesByHour = $db->query("
        SELECT HOUR(created_at) as hour, COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE {$orgFilter} DATE(invoice_date) = CURDATE()
        AND status != 'cancelled'
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    
    // Prepare hourly data
    $hourlyLabels = [];
    $hourlyValues = [];
    for ($h = 9; $h <= 21; $h++) {
        $hourlyLabels[] = sprintf('%02d:00', $h);
        $found = false;
        foreach ($salesByHour as $row) {
            if ($row['hour'] == $h) {
                $hourlyValues[] = (float)$row['total'];
                $found = true;
                break;
            }
        }
        if (!$found) $hourlyValues[] = 0;
    }
    
    // === TOP SELLING PRODUCTS TODAY ===
    $topProductsToday = $db->query("
        SELECT p.name, SUM(ii.quantity) as qty_sold, SUM(ii.line_total) as revenue
        FROM invoice_items ii
        INNER JOIN invoices i ON ii.invoice_id = i.id
        INNER JOIN products p ON ii.product_id = p.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " DATE(i.invoice_date) = CURDATE()
        AND i.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY qty_sold DESC
        LIMIT 5
    ");
    
    // === PAYMENT METHOD BREAKDOWN ===
    $paymentBreakdown = $db->query("
        SELECT payment_method, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE {$orgFilter} DATE(invoice_date) = CURDATE()
        AND status != 'cancelled'
        GROUP BY payment_method
    ");

} catch (Exception $e) {
    error_log("Store Manager Dashboard error: " . $e->getMessage());
    $todaySales = ['total' => 0, 'count' => 0];
    $yesterdaySales = 0;
    $salesGrowth = 0;
    $weekSales = ['total' => 0, 'count' => 0];
    $monthSales = ['total' => 0, 'count' => 0];
    $totalProducts = $lowStockCount = $outOfStockCount = 0;
    $stockValue = $totalCustomers = $customersToday = $pendingPayments = 0;
    $teamPerformance = $lowStockProducts = $outOfStockProducts = [];
    $recentInvoices = $topProductsToday = $paymentBreakdown = [];
    $pendingReturns = $pendingQuotations = 0;
    $hourlyLabels = [];
    $hourlyValues = [];
}

function formatCurrency($amount) {
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . 'L';
    } elseif ($amount >= 1000) {
        return '₹' . number_format($amount / 1000, 2) . 'K';
    }
    return '₹' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Manager Dashboard - Stocksathi</title>
    <meta name="description" content="Store Manager Dashboard - Complete store operations overview">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
    
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../../_includes/header.php'; ?>
            
            <main class="content">
    <!-- Chart.js must be inside main for PJAX -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
        .store-header {
            background: linear-gradient(135deg, #3a63a5 0%, #4f82d5 50%, #4f82d5 100%);
            padding: 28px; border-radius: 10px; color: white; margin-bottom: 24px;
            position: relative; overflow: hidden;
            border: none; box-shadow: 0 8px 32px rgba(79, 130, 213, 0.3);
        }
        .store-header::before {
            content: ''; position: absolute; top: -50%; right: -10%;
            width: 250px; height: 250px; background: rgba(255,255,255,0.1);
            border-radius: 50%; pointer-events: none;
        }
        
        .metric-cards {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;
            margin-top: 20px;
        }
        .metric-card {
            background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        .metric-card-label { font-size: 12px; opacity: 0.9; }
        .metric-card-value { font-size: 24px; font-weight: 700; margin-top: 4px; }
        .metric-card-change { font-size: 11px; margin-top: 4px; }
        .metric-card-change.positive { color: #4ade80; }
        .metric-card-change.negative { color: #fbbf24; }
        
        .stats-row {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
            margin-bottom: 24px;
        }
        .stat-box {
            background: white; border-radius: 8px; padding: 20px;
            border: 1px solid var(--border-light);
            transition: box-shadow 0.2s ease;
        }
        .stat-box {
            background: #FFFFFF;
            border: 1px solid #E5E9F0;
            box-shadow: 0 2px 8px rgba(79, 130, 213, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        .stat-box:hover { 
            box-shadow: 0 8px 24px rgba(79, 130, 213, 0.15); 
            border-color: #4f82d5;
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 12px;
        }
        .stat-icon.primary { background: #E8EDF5; }
        .stat-icon.success { background: #dcfce7; }
        .stat-icon.warning { background: #fef3c7; }
        .stat-icon.danger { background: #fee2e2; }
        
        .team-member {
            display: flex; align-items: center; padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }
        .team-member:last-child { border-bottom: none; }
        .team-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #6B9BC7, #8BB3D4);
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 600; margin-right: 12px;
        }
        
        .stock-alert {
            display: flex; align-items: center; padding: 10px 12px;
            background: #fef3c7; border-radius: 8px; margin-bottom: 8px;
            border-left: 4px solid #f59e0b;
        }
        .stock-alert.critical {
            background: #fee2e2; border-left-color: #ef4444;
        }
        
        .chart-container { position: relative; height: 250px; }
        
        .quick-actions {
            display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px;
        }
        .quick-action {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 16px 8px; background: white; border: 1px solid var(--border-light);
            border-radius: 8px; text-decoration: none; color: var(--text-primary);
            transition: all 0.2s ease; font-size: 12px;
        }
        .quick-action:hover {
            border-color: var(--color-primary);
            background: var(--bg-tertiary);
        }
        .quick-action-icon { font-size: 24px; }
        
        @media (max-width: 1200px) {
            .metric-cards { grid-template-columns: repeat(2, 1fr); }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .metric-cards { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
                <!-- Store Header -->
                <div class="store-header">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1;">
                        <div>
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700;">🏪 Store Manager Dashboard</h1>
                            <p style="margin: 8px 0 0 0; opacity: 0.9;">
                                <?= $storeInfo ? htmlspecialchars($storeInfo['name']) : 'All Stores' ?> • <?= date('l, F j, Y') ?>
                            </p>
                        </div>
                        <div>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php?action=new" class="btn" style="background: white; color: var(--color-primary); font-weight: 600;">
                                ➕ New Sale
                            </a>
                        </div>
                    </div>
                    
                    <div class="metric-cards">
                        <div class="metric-card">
                            <div class="metric-card-label">💰 Today's Sales</div>
                            <div class="metric-card-value"><?= formatCurrency($todaySales['total']) ?></div>
                            <div class="metric-card-change <?= $salesGrowth >= 0 ? 'positive' : 'negative' ?>">
                                <?= $salesGrowth >= 0 ? '↑' : '↓' ?> <?= number_format(abs($salesGrowth), 1) ?>% vs yesterday
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-card-label">🧾 Today's Invoices</div>
                            <div class="metric-card-value"><?= $todaySales['count'] ?></div>
                            <div class="metric-card-change positive"><?= $customersToday ?> customers served</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-card-label">📅 This Week</div>
                            <div class="metric-card-value"><?= formatCurrency($weekSales['total']) ?></div>
                            <div class="metric-card-change positive"><?= $weekSales['count'] ?> invoices</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-card-label">📆 This Month</div>
                            <div class="metric-card-value"><?= formatCurrency($monthSales['total']) ?></div>
                            <div class="metric-card-change positive"><?= $monthSales['count'] ?> invoices</div>
                        </div>
                    </div>
                </div>

                <!-- Inventory & Customer Stats -->
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-icon primary">📦</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Total Products</div>
                        <div style="font-size: 24px; font-weight: 700;"><?= number_format($totalProducts) ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon warning">⚠️</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Low Stock Items</div>
                        <div style="font-size: 24px; font-weight: 700; color: #f59e0b;"><?= $lowStockCount ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon success">👥</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Active Customers</div>
                        <div style="font-size: 24px; font-weight: 700;"><?= number_format($totalCustomers) ?></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon danger">💳</div>
                        <div style="font-size: 12px; color: var(--text-secondary);">Pending Payments</div>
                        <div style="font-size: 24px; font-weight: 700; color: #ef4444;"><?= formatCurrency($pendingPayments) ?></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="<?= BASE_PATH ?>/pages/invoices.php" class="quick-action">
                                <span class="quick-action-icon">🧾</span>
                                <span>New Invoice</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/stock-in.php" class="quick-action">
                                <span class="quick-action-icon">📥</span>
                                <span>Stock In</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/stock-adjustments.php" class="quick-action">
                                <span class="quick-action-icon">🔧</span>
                                <span>Adjust Stock</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/customers.php" class="quick-action">
                                <span class="quick-action-icon">👤</span>
                                <span>Add Customer</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/sales-returns.php" class="quick-action">
                                <span class="quick-action-icon">↩️</span>
                                <span>Returns</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/reports.php" class="quick-action">
                                <span class="quick-action-icon">📊</span>
                                <span>Reports</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pending Alerts -->
                <?php if ($pendingReturns > 0 || $pendingQuotations > 0 || $lowStockCount > 5): ?>
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">🔔 Requires Attention</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($pendingReturns > 0): ?>
                        <div class="stock-alert">
                            <span style="margin-right: 12px;">↩️</span>
                            <span style="flex: 1;"><strong><?= $pendingReturns ?></strong> sales returns pending approval</span>
                            <a href="<?= BASE_PATH ?>/pages/sales-returns.php?status=pending" class="btn btn-sm btn-warning">Review</a>
                        </div>
                        <?php endif; ?>
                        <?php if ($pendingQuotations > 0): ?>
                        <div class="stock-alert">
                            <span style="margin-right: 12px;">📋</span>
                            <span style="flex: 1;"><strong><?= $pendingQuotations ?></strong> quotations awaiting response</span>
                            <a href="<?= BASE_PATH ?>/pages/quotations.php?status=sent" class="btn btn-sm btn-warning">Follow Up</a>
                        </div>
                        <?php endif; ?>
                        <?php if ($lowStockCount > 5): ?>
                        <div class="stock-alert critical">
                            <span style="margin-right: 12px;">⚠️</span>
                            <span style="flex: 1;"><strong><?= $lowStockCount ?></strong> products running low on stock</span>
                            <a href="<?= BASE_PATH ?>/pages/products.php?filter=low_stock" class="btn btn-sm btn-danger">Reorder</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts Row -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Hourly Sales Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📈 Today's Sales by Hour</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Team Performance -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">👥 Team Performance Today</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($teamPerformance)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No team data available
                                </p>
                            <?php else: ?>
                                <?php foreach ($teamPerformance as $index => $member): ?>
                                <div class="team-member">
                                    <div class="team-avatar"><?= strtoupper(substr($member['full_name'] ?? $member['username'], 0, 2)) ?></div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($member['full_name'] ?? $member['username']) ?></div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">
                                            <?= $member['today_invoices'] ?> invoices today
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 700; color: var(--color-success);">
                                            <?= formatCurrency($member['today_sales']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Stock Alerts & Top Products -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Low Stock Alerts -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">⚠️ Low Stock Products</h3>
                            <a href="<?= BASE_PATH ?>/pages/products.php?filter=low_stock" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                            <?php if (empty($lowStockProducts)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    ✅ All products are well stocked!
                                </p>
                            <?php else: ?>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <div class="stock-alert <?= $product['stock_quantity'] <= 5 ? 'critical' : '' ?>">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">
                                            SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 700; color: <?= $product['stock_quantity'] <= 5 ? '#ef4444' : '#f59e0b' ?>;">
                                            <?= $product['stock_quantity'] ?> left
                                        </div>
                                                <div style="font-size: 10px; color: var(--text-tertiary);">
                                                    Min: <?= htmlspecialchars($product['min_stock_level']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">📋 Recent Invoices</h3>
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentInvoices)): ?>
                            <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                No invoices yet
                            </p>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentInvoices as $invoice): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($invoice['invoice_number']) ?></code></td>
                                        <td><?= htmlspecialchars($invoice['customer_name'] ?? 'Walk-in') ?></td>
                                        <td><strong><?= formatCurrency($invoice['total_amount']) ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?= $invoice['payment_status'] == 'paid' ? 'success' : ($invoice['payment_status'] == 'partial' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($invoice['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($invoice['created_by_name'] ?? 'System') ?></td>
                                        <td><?= date('h:i A', strtotime($invoice['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            
<script>
    function initStoreManagerChart() {
        if (typeof Chart === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = initStoreManagerChart;
            document.head.appendChild(script);
            return;
        }
        function draw() {
        if (window.storeManagerHourlyChart instanceof Chart) window.storeManagerHourlyChart.destroy();
        const canvas = document.getElementById('hourlyChart');
        if (canvas) {
            window.storeManagerHourlyChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($hourlyLabels) ?>,
                    datasets: [{
                        label: 'Sales (₹)',
                        data: <?= json_encode($hourlyValues) ?>,
                        backgroundColor: 'rgba(79, 130, 213, 0.7)',
                        borderColor: 'rgb(79, 130, 213)',
                        borderWidth: 2,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: value => {
                                    if (value >= 1000) return '₹' + (value/1000).toFixed(0) + 'K';
                                    return '₹' + value;
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
            if (window.storeManagerHourlyChart && window.storeManagerHourlyChart.resize) window.storeManagerHourlyChart.resize();
        }
        }
        var raf = window.requestAnimationFrame || function(f){setTimeout(f,16);};
        raf(function(){ raf(draw); });
    }
    initStoreManagerChart();
    </script>
</main>
        </div>
    </div>

    
</body>
</html>
