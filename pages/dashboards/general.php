<?php
/**
 * General Dashboard - Fallback for other roles
 * Stocksathi Inventory System
 */

require_once __DIR__ . '/../../_includes/session_guard.php'; // Correct path to includes
require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Get dashboard statistics using direct MySQL queries
try {
    // Total Products Count
    $totalProducts = $db->queryOne("SELECT COUNT(*) as count FROM products {$orgWhere}\")['count'];
    
    // Stock Value (sum of purchase_price * stock_quantity)
    $stockValueResult = $db->queryOne("SELECT SUM(purchase_price * stock_quantity) as total FROM products WHERE stock_quantity > 0");
    $stockValue = $stockValueResult['total'] ?? 0;
    
    // Low Stock Alerts (stock_quantity <= min_stock_level and stock_quantity > 0)
    $lowStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0 AND stock_quantity <= min_stock_level")['count'];
    
    // Out of Stock Count
    $outOfStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0")['count'];
    
    // Get sales data for chart (last 7 months)
    try {
        $salesQuery = "SELECT 
            DATE_FORMAT(invoice_date, '%Y-%m') as month,
            SUM(total_amount) as total
            FROM invoices 
            WHERE invoice_date >= DATE_SUB(NOW(), INTERVAL 7 MONTH) AND status != 'cancelled'
            GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
            ORDER BY month ASC";
        $salesData = $db->query($salesQuery);
        
        // Prepare sales chart data - fill in missing months
        $salesLabels = [];
        $salesValues = [];
        $dataMap = [];
        foreach ($salesData as $row) {
            $dataMap[$row['month']] = (float)($row['total'] ?? 0);
        }
        
        // Fill last 7 months
        for ($i = 6; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $salesLabels[] = date('M', strtotime($month . '-01'));
            $salesValues[] = $dataMap[$month] ?? 0;
        }
    } catch (Exception $e) {
        $salesLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
        $salesValues = [0, 0, 0, 0, 0, 0, 0];
        error_log("Sales chart error: " . $e->getMessage());
    }
    
    // Stock Distribution
    $inStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity > min_stock_level")['count'];
    $stockDistribution = [
        'in_stock' => $inStockCount,
        'low_stock' => $lowStockCount,
        'out_of_stock' => $outOfStockCount
    ];
    
    // Recent Activity (last 10 activities)
    $activityQuery = "SELECT 
        al.*,
        u.full_name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10";
    $recentActivities = $db->query($activityQuery);
    
} catch (Exception $e) {
    // Set default values if queries fail
    $totalProducts = 0;
    $stockValue = 0;
    $lowStockCount = 0;
    $outOfStockCount = 0;
    $salesLabels = [];
    $salesValues = [];
    $stockDistribution = ['in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
    $recentActivities = [];
    error_log("Dashboard Error: " . $e->getMessage());
}

// Format stock value
function formatCurrency($amount) {
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 1) . 'L';
    } elseif ($amount >= 1000) {
        return '₹' . number_format($amount / 1000, 1) . 'K';
    }
    return '₹' . number_format($amount);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stocksathi</title>
    
    <!-- Corrected Paths for CSS -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/design-system.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/components.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/nav-dropdown.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
    <!-- Chart.js -->
    
</head>

<body>
    <div class="app-container">
        <?php include __DIR__ . '/../../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../../_includes/header.php'; ?>
            
            <main class="content">
    <!-- Chart.js must be inside main for PJAX -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                <!-- Page Header -->
                <div class="content-header">
                    <nav class="breadcrumb">
                        <span class="breadcrumb-item">Home</span>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Dashboard</span>
                    </nav>
                    <h1 class="content-title">Dashboard</h1>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-4 gap-6 mb-6">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">
                            <?php include __DIR__ . '/../../assets/icons/utility/kpi-products.svg'; ?>
                        </div>
                        <div class="kpi-label">Total Products</div>
                        <div class="kpi-value"><?= number_format($totalProducts) ?></div>
                        <div class="kpi-change positive">
                            <span>↑</span>
                            <span>All products</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon success">
                            <?php include __DIR__ . '/../../assets/icons/utility/kpi-money.svg'; ?>
                        </div>
                        <div class="kpi-label">Stock Value</div>
                        <div class="kpi-value"><?= formatCurrency($stockValue) ?></div>
                        <div class="kpi-change positive">
                            <span>↑</span>
                            <span>Total inventory value</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon warning">
                            <?php include __DIR__ . '/../../assets/icons/utility/kpi-warning.svg'; ?>
                        </div>
                        <div class="kpi-label">Low Stock Alerts</div>
                        <div class="kpi-value"><?= $lowStockCount ?></div>
                        <div class="kpi-change <?= $lowStockCount > 0 ? 'negative' : 'positive' ?>">
                            <span><?= $lowStockCount > 0 ? '↑' : '✓' ?></span>
                            <span><?= $lowStockCount > 0 ? 'Needs attention' : 'All good' ?></span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-icon danger">
                            <?php include __DIR__ . '/../../assets/icons/utility/kpi-clock.svg'; ?>
                        </div>
                        <div class="kpi-label">Out of Stock</div>
                        <div class="kpi-value"><?= $outOfStockCount ?></div>
                        <div class="kpi-change <?= $outOfStockCount > 0 ? 'negative' : 'positive' ?>">
                            <span><?= $outOfStockCount > 0 ? '↓' : '✓' ?></span>
                            <span><?= $outOfStockCount > 0 ? 'Immediate action' : 'All stocked' ?></span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Sales Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📈 Sales Analytics</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Stock Distribution Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🥧 Stock Distribution</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="stockChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Recent Activity</h3>
                        <a href="<?= BASE_PATH ?>/pages/activity-logs.php" class="btn btn-ghost btn-sm">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Activity</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentActivities)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px;">
                                            No recent activities
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td><?= date('Y-m-d H:i', strtotime($activity['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($activity['action'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></td>
                                            <td><?= htmlspecialchars($activity['module'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge badge-success">Success</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
<script>
        function initGeneralDashboardChart() {
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                script.onload = initGeneralDashboardChart;
                document.head.appendChild(script);
                return;
            }
            function draw() {
            if (window.generalSalesChart instanceof Chart) window.generalSalesChart.destroy();
            if (window.generalStockChart instanceof Chart) window.generalStockChart.destroy();

            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                window.generalSalesChart = new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($salesLabels ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']) ?>,
                        datasets: [{
                            label: 'Sales (₹)',
                            data: <?= json_encode($salesValues ?: [0, 0, 0, 0, 0, 0, 0]) ?>,
                        borderColor: '#1565C0', /* var(--color-primary) */
                        backgroundColor: 'rgba(66, 165, 245, 0.1)', /* var(--color-primary-light) alpha */
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#1565C0',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: { font: { size: 12, weight: '600' } }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        let value = context.parsed.y;
                                        if (value >= 100000) return '₹' + (value/100000).toFixed(2) + 'L';
                                        if (value >= 1000) return '₹' + (value/1000).toFixed(2) + 'K';
                                        return '₹' + value.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 100000) return '₹' + (value/100000).toFixed(1) + 'L';
                                        if (value >= 1000) return '₹' + (value/1000).toFixed(1) + 'K';
                                        return '₹' + value;
                                    }
                                }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
                if (window.generalSalesChart && window.generalSalesChart.resize) window.generalSalesChart.resize();
            }

            // Stock Distribution Chart
            const stockCtx = document.getElementById('stockChart');
            if (stockCtx) {
                window.generalStockChart = new Chart(stockCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                        datasets: [{
                            data: [
                                <?= $stockDistribution['in_stock'] ?? 0 ?>,
                                <?= $stockDistribution['low_stock'] ?? 0 ?>,
                                <?= $stockDistribution['out_of_stock'] ?? 0 ?>
                            ],
                            backgroundColor: [
                                'rgb(16, 185, 129)',  // Success green
                                'rgb(251, 191, 36)',   // Warning yellow
                                'rgb(239, 68, 68)'     // Danger red
                            ],
                            borderWidth: 0,
                            hoverBorderWidth: 2,
                            hoverBorderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: { 
                                    padding: 15, 
                                    usePointStyle: true,
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.8)',
                                padding: 12
                            }
                        },
                        cutout: '65%'
                    }
                });
                if (window.generalStockChart && window.generalStockChart.resize) window.generalStockChart.resize();
            }
            }
            var raf = window.requestAnimationFrame || function(f){setTimeout(f,16);};
            raf(function(){ raf(draw); });
        }
        initGeneralDashboardChart();
    </script>
</main>
        </div>
    </div>

    <!-- Initialize Charts with PHP Data -->
    
</body>
</html>
