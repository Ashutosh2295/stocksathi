<?php
/**
 * Super Admin Dashboard
 * Full business overview with complete access
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Require super admin role
if (Session::getUserRole() !== 'super_admin' && Session::getUserRole() !== 'admin') {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$roleManager = new RoleManager();

// Get current organization ID
$orgId = Session::getOrganizationId();

// Get dashboard statistics
try {
    // Financial Overview
    // Total Revenue - include all paid/partial invoices
    $totalRevenue = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE (payment_status = 'paid' OR payment_status = 'partial') AND status != 'cancelled' AND organization_id = ?", [$orgId])['total'];
    
    // Total Expenses - include all approved expenses
    $totalExpenses = $db->queryOne("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE (status = 'approved' OR status = 'paid') AND organization_id = ?", [$orgId])['total'];
    $netProfit = $totalRevenue - $totalExpenses;
    
    // GST Summary
    $gstCollected = $db->queryOne("SELECT COALESCE(SUM(tax_amount), 0) as total FROM invoices WHERE MONTH(invoice_date) = MONTH(CURRENT_DATE()) AND organization_id = ?", [$orgId])['total'];
    
    // Inventory Stats
    $totalProducts = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE organization_id = ?", [$orgId])['count'];
    $lowStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity <= min_stock_level AND organization_id = ?", [$orgId])['count'];
    $stockValue = $db->queryOne("SELECT COALESCE(SUM(purchase_price * stock_quantity), 0) as total FROM products WHERE organization_id = ?", [$orgId])['total'];
    
    //Sales Stats
    $todaySales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE DATE(invoice_date) = CURDATE() AND organization_id = ?", [$orgId])['total'];
    $monthSales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND organization_id = ?", [$orgId])['total'];
    
    // Customer Stats
    $totalCustomers = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE status = 'active' AND organization_id = ?", [$orgId])['count'];
    $pendingPayments = $db->queryOne("SELECT COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as total FROM invoices WHERE (payment_status = 'unpaid' OR payment_status = 'partial') AND status != 'cancelled' AND organization_id = ?", [$orgId])['total'];
    
    // Employee Performance
    $activeEmployees = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND organization_id = ?", [$orgId])['count'];
    
    // Top Products
    $topProducts = $db->query("
        SELECT p.name, SUM(ii.quantity) as total_sold, SUM(ii.line_total) as revenue
        FROM invoice_items ii
        INNER JOIN products p ON ii.product_id = p.id
        INNER JOIN invoices i ON ii.invoice_id = i.id
        WHERE i.organization_id = ? AND i.status != 'cancelled' AND (i.payment_status = 'paid' OR i.payment_status = 'partial')
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ", [$orgId]);
    
    // Sales trend (last 30 days) for chart
    $salesTrend = $db->query("
        SELECT DATE(invoice_date) as date, COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status != 'cancelled' AND organization_id = ?
        GROUP BY DATE(invoice_date)
        ORDER BY date ASC
    ", [$orgId]);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $totalRevenue = $totalExpenses = $netProfit = $gstCollected = 0;
    $totalProducts = $lowStockCount = $todaySales = $monthSales = 0;
    $totalCustomers = $pendingPayments = $activeEmployees = 0;
    $stockValue = 0;
    $topProducts = [];
    $salesTrend = [];
}

// Helper function
function formatCurrency($amount) {
    if ($amount >= 10000000) {
        return '₹' . number_format($amount / 10000000, 2) . 'Cr';
    } elseif ($amount >= 100000) {
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
    <title>Super Admin Dashboard - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <!-- ApexCharts + Theme - load in head so ready before chart init -->
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
                <!-- Page Header -->
                <div class="content-header">
                    <div>
                        <h1 class="content-title">🎯 Super Admin Dashboard</h1>
                        <p style="color: var(--text-secondary);">Complete business overview & analytics</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-success" onclick="location.href='<?= BASE_PATH ?>/pages/roles.php'">
                            🔐 Manage Roles
                        </button>
                        <button class="btn btn-primary" onclick="location.href='<?= BASE_PATH ?>/pages/users.php'">
                            👥 Manage Users
                        </button>
                    </div>
                </div>

                <!-- Financial Overview -->
                <div style="background: linear-gradient(135deg, #3a63a5 0%, #4f82d5 100%); padding: 24px; border-radius: 10px; color: white; margin-bottom: 24px; box-shadow: 0 8px 32px rgba(79, 130, 213, 0.3);">
                    <h2 style="margin: 0 0 16px 0; font-size: 20px;">💰 Financial Overview</h2>
                    <div class="grid grid-cols-4 gap-4">
                        <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px;">
                            <div style="font-size: 13px; opacity: 0.9;">Total Revenue</div>
                            <div style="font-size: 24px; font-weight: bold; margin-top: 8px;"><?= formatCurrency($totalRevenue) ?></div>
                        </div>
                        <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px;">
                            <div style="font-size: 13px; opacity: 0.9;">Total Expenses</div>
                            <div style="font-size: 24px; font-weight: bold; margin-top: 8px;"><?= formatCurrency($totalExpenses) ?></div>
                        </div>
                        <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px;">
                            <div style="font-size: 13px; opacity: 0.9;">Net Profit</div>
                            <div style="font-size: 24px; font-weight: bold; margin-top: 8px; color: <?= $netProfit >= 0 ? '#4ade80' : '#f87171' ?>"><?= formatCurrency(abs($netProfit)) ?></div>
                        </div>
                        <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px;">
                            <div style="font-size: 13px; opacity: 0.9;">GST (This Month)</div>
                            <div style="font-size: 24px; font-weight: bold; margin-top: 8px;"><?= formatCurrency($gstCollected) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-4 gap-6 mb-6">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">📦</div>
                        <div class="kpi-label">Total Products</div>
                        <div class="kpi-value"><?= number_format($totalProducts) ?></div>
                        <div class="kpi-change <?= $lowStockCount > 0 ? 'negative' : 'positive' ?>">
                            <?= $lowStockCount ?> low stock
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon success">💵</div>
                        <div class="kpi-label">Today's Sales</div>
                        <div class="kpi-value"><?= formatCurrency($todaySales) ?></div>
                        <div class="kpi-change positive">Month: <?= formatCurrency($monthSales) ?></div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon warning">👥</div>
                        <div class="kpi-label">Active Customers</div>
                        <div class="kpi-value"><?= number_format($totalCustomers) ?></div>
                        <div class="kpi-change negative">Pending: <?= formatCurrency($pendingPayments) ?></div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon info">🏪</div>
                        <div class="kpi-label">Stock Value</div>
                        <div class="kpi-value"><?= formatCurrency($stockValue) ?></div>
                        <div class="kpi-change positive"><?= $activeEmployees ?> employees</div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">📈 Sales Trend (Last 7 Days)</h3>
                    </div>
                    <div class="card-body" style="position:relative;height:280px;">
                        <div id="salesTrendChart" style="height: 100%;"></div>
                    </div>
                </div>

                <!-- Top Products & Quick Actions -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Top Products -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏆 Top Selling Products</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topProducts)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No sales data available
                                </p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= number_format($product['total_sold']) ?></td>
                                                <td>₹<?= number_format($product['revenue'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚡ Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-primary" style="width: 100%; justify-content: flex-start;">
                                    🧾 Create New Invoice
                                </a>
                                <a href="<?= BASE_PATH ?>/pages/products.php" class="btn btn-success" style="width: 100%; justify-content: flex-start;">
                                    📦 Add New Product
                                </a>
                                <a href="<?= BASE_PATH ?>/pages/stock-in.php" class="btn btn-info" style="width: 100%; justify-content: flex-start;">
                                    📥 Stock In Entry
                                </a>
                                <a href="<?= BASE_PATH ?>/pages/expenses.php" class="btn btn-warning" style="width: 100%; justify-content: flex-start;">
                                    💰 Record Expense
                                </a>
                                <a href="<?= BASE_PATH ?>/pages/reports.php" class="btn btn-secondary" style="width: 100%; justify-content: flex-start;">
                                    📊 Reports & Analytics
                                </a>
                                <a href="<?= BASE_PATH ?>/pages/users.php" class="btn btn-outline" style="width: 100%; justify-content: flex-start;">
                                    👤 Manage Users & Roles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role-Based Access Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🔐 Role-Based Access Control (RBAC)</h3>
                    </div>
                    <div class="card-body">
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--color-success); padding: 16px; border-radius: 8px; margin-bottom: 16px;">
                            <strong style="color: var(--color-success);">✅ RBAC System Active!</strong>
                            <p style="margin: 8px 0 0 0; color: var(--color-success);">
                                You are logged in as <strong style="color: var(--text-primary);">Super Admin</strong> with full system access and all permissions.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-4 gap-4">
                            <div style="padding: 12px; border: 1px solid var(--border-light); border-radius: 6px; background: var(--bg-surface);">
                                <div style="font-weight: 600; margin-bottom: 4px; color: var(--text-primary);">👑 Super Admin</div>
                                <div style="font-size: 13px; color: var(--text-secondary);">Full access to all features</div>
                            </div>
                            <div style="padding: 12px; border: 1px solid var(--border-light); border-radius: 8px; background: var(--bg-surface);">
                                <div style="font-weight: 600; margin-bottom: 4px; color: var(--text-primary);">🏪 Store Manager</div>
                                <div style="font-size: 13px; color: var(--text-secondary);">Daily operations & sales</div>
                            </div>
                            <div style="padding: 12px; border: 1px solid var(--border-light); border-radius: 8px; background: var(--bg-surface);">
                                <div style="font-weight: 600; margin-bottom: 4px; color: var(--text-primary);">💼 Accountant</div>
                                <div style="font-size: 13px; color: var(--text-secondary);">Finance & GST compliance</div>
                            </div>
                            <div style="padding: 12px; border: 1px solid var(--border-light); border-radius: 8px; background: var(--bg-surface);">
                                <div style="font-weight: 600; margin-bottom: 4px; color: var(--text-primary);">🛒 Sales Executive</div>
                                <div style="font-size: 13px; color: var(--text-secondary);">Billing & customer service</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
    function initSuperAdminChart() {
        if (typeof ApexCharts === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
            script.onload = initSuperAdminChart;
            document.head.appendChild(script);
            return;
        }
        function draw() {
            if (window.superAdminChart) window.superAdminChart.destroy();
            const canvas = document.getElementById('salesTrendChart');
            if (!canvas) return;
            <?php
            $dateLabels = [];
            $dateValues = [];
            for ($i = 29; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $dateLabels[] = date('d M', strtotime($d));
                $v = 0;
                if (!empty($salesTrend)) {
                    foreach ($salesTrend as $row) {
                        if (isset($row['date']) && $row['date'] === $d) { $v = (float)($row['total'] ?? 0); break; }
                    }
                }
                $dateValues[] = $v;
            }
            ?>
            const labels = <?= json_encode($dateLabels) ?>;
            const data = <?= json_encode($dateValues) ?>;
            const hasData = data.some(val => val > 0);
            
            if (hasData) {
                var options = {
                    series: [{
                        name: 'Sales (₹)',
                        data: data
                    }],
                    chart: {
                        type: 'area',
                        height: 280,
                        toolbar: { show: false }
                    },
                    colors: ['#4f82d5'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.4,
                            opacityTo: 0.05,
                            stops: [0, 100]
                        }
                    },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 3 },
                    xaxis: {
                        categories: labels,
                        labels: {
                            hideOverlappingLabels: true,
                            rotate: -45,
                            style: { fontSize: '10px' }
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

                window.superAdminChart = new ApexCharts(canvas, options);
                window.superAdminChart.render();
            } else {
                canvas.parentElement.innerHTML = '<div style="text-align:center;padding:60px;color:var(--text-secondary);"><p>No sales data available for the last 7 days</p></div>';
            }
        }
        window.requestAnimationFrame ? window.requestAnimationFrame(() => window.requestAnimationFrame(draw)) : setTimeout(draw, 16);
    }
    initSuperAdminChart();
    </script>
</body>
</html>
