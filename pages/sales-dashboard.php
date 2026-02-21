<?php
/**
 * Sales Dashboard - Real data from DB
 * Alternative sales overview (sidebar links to dashboards/sales-executive.php)
 */
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

try {
    $totalSales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE status != 'cancelled'")['total'];
    $prevMonthSales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(invoice_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND status != 'cancelled'")['total'];
    $monthSales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE()) AND status != 'cancelled'")['total'];
    $salesGrowth = $prevMonthSales > 0 ? (($monthSales - $prevMonthSales) / $prevMonthSales) * 100 : 0;

    $monthOrders = $db->queryOne("SELECT COUNT(*) as count FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE()) AND status != 'cancelled'")['count'];
    $prevOrders = $db->queryOne("SELECT COUNT(*) as count FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(invoice_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND status != 'cancelled'")['count'];
    $totalOrders = $monthOrders;
    $ordersGrowth = $prevOrders > 0 ? (($monthOrders - $prevOrders) / $prevOrders) * 100 : 0;

    $pendingOrders = $db->queryOne("SELECT COUNT(*) as count FROM invoices WHERE {$orgFilter} (payment_status = 'unpaid' OR payment_status = 'partial') AND status != 'cancelled'")['count'];
    $newCustomers = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE {$orgFilter} MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'];
    $prevNewCustomers = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE {$orgFilter} MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")['count'];
    $customersGrowth = $prevNewCustomers > 0 ? (($newCustomers - $prevNewCustomers) / $prevNewCustomers) * 100 : 0;

    $topProducts = $db->query("
        SELECT p.name, SUM(ii.quantity) as qty
        FROM invoice_items ii
        INNER JOIN products p ON ii.product_id = p.id
        INNER JOIN invoices i ON ii.invoice_id = i.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " i.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY qty DESC
        LIMIT 5
    ");
    $recentInvoices = $db->query("
        SELECT i.id, i.invoice_number, c.name as customer_name, i.invoice_date, i.total_amount, i.payment_status
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " i.status != 'cancelled'
        ORDER BY i.created_at DESC
        LIMIT 15
    ");

    $monthlyRevenue = $db->query("
        SELECT DATE_FORMAT(invoice_date, '%Y-%m') as m, COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) AND status != 'cancelled'
        GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
        ORDER BY m ASC
    ");
    $revMap = [];
    foreach ($monthlyRevenue as $r) $revMap[$r['m']] = (float)$r['total'];
    $labels = [];
    $data = [];
    for ($i = 11; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $labels[] = date('M Y', strtotime($m . '-01'));
        $data[] = $revMap[$m] ?? 0;
    }
} catch (Exception $e) {
    error_log("Sales Dashboard error: " . $e->getMessage());
    $totalSales = $monthSales = $prevMonthSales = 0;
    $salesGrowth = 0;
    $totalOrders = $prevOrders = 0;
    $ordersGrowth = 0;
    $pendingOrders = $newCustomers = $prevNewCustomers = 0;
    $customersGrowth = 0;
    $topProducts = $recentInvoices = [];
    $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $data = array_fill(0, 12, 0);
}

function fmt($n) {
    if ($n >= 100000) return '₹' . number_format($n / 100000, 2) . 'L';
    if ($n >= 1000) return '₹' . number_format($n / 1000, 2) . 'K';
    return '₹' . number_format($n, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            <main class="content">
                <div class="content-header">
                    <h1 class="content-title">Sales Dashboard</h1>
                    <a href="<?= BASE_PATH ?>/pages/invoices.php?action=new" class="btn btn-primary">➕ New Invoice</a>
                </div>

                <div class="grid grid-cols-4 gap-6 mb-6">
                    <div class="kpi-card">
                        <div class="kpi-icon success">💰</div>
                        <div class="kpi-label">Sales This Month</div>
                        <div class="kpi-value"><?= fmt($monthSales) ?></div>
                        <div class="kpi-change <?= $salesGrowth >= 0 ? 'positive' : 'negative' ?>">
                            <span><?= $salesGrowth >= 0 ? '↑' : '↓' ?></span>
                            <span><?= abs(round($salesGrowth, 1)) ?>% from last month</span>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon primary">📝</div>
                        <div class="kpi-label">Orders This Month</div>
                        <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                        <div class="kpi-change <?= $ordersGrowth >= 0 ? 'positive' : 'negative' ?>">
                            <span><?= $ordersGrowth >= 0 ? '↑' : '↓' ?></span>
                            <span><?= abs(round($ordersGrowth, 1)) ?>% from last month</span>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon warning">⏳</div>
                        <div class="kpi-label">Pending Orders</div>
                        <div class="kpi-value"><?= number_format($pendingOrders) ?></div>
                        <div class="kpi-change negative">Needs attention</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon info">👥</div>
                        <div class="kpi-label">New Customers (MoM)</div>
                        <div class="kpi-value"><?= number_format($newCustomers) ?></div>
                        <div class="kpi-change <?= $customersGrowth >= 0 ? 'positive' : 'negative' ?>">
                            <span><?= $customersGrowth >= 0 ? '↑' : '↓' ?></span>
                            <span><?= abs(round($customersGrowth, 1)) ?>% from last month</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">📊 Revenue Overview (12 Months)</h3></div>
                        <div class="card-body" style="position:relative;height:300px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">🎯 Top Products</h3></div>
                        <div class="card-body" style="position:relative;height:300px;">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Recent Invoices</h3>
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost btn-sm">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentInvoices)): ?>
                                    <tr><td colspan="6" style="text-align:center;padding:40px;">No invoices yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentInvoices as $inv): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($inv['invoice_number']) ?></code></td>
                                        <td><?= htmlspecialchars($inv['customer_name'] ?? 'Walk-in') ?></td>
                                        <td><?= date('Y-m-d', strtotime($inv['invoice_date'])) ?></td>
                                        <td><?= fmt($inv['total_amount']) ?></td>
                                        <td><span class="badge badge-<?= $inv['payment_status'] === 'paid' ? 'success' : ($inv['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= ucfirst($inv['payment_status']) ?></span></td>
                                        <td><a href="<?= BASE_PATH ?>/pages/invoice-details.php?id=<?= (int)($inv['id'] ?? 0) ?>" class="btn btn-ghost btn-sm">👁️ View</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?= JS_PATH ?>/api-client.js"></script>
    <script src="<?= JS_PATH ?>/app.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const rev = document.getElementById('revenueChart');
        if (rev) {
            new Chart(rev, {
                type: 'line',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{ label: 'Revenue (₹)', data: <?= json_encode($data) ?>, borderColor: 'rgb(13, 148, 136)', backgroundColor: 'rgba(13, 148, 136, 0.1)', fill: true, tension: 0.4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
            });
        }
        const top = document.getElementById('topProductsChart');
        if (top) {
            const topLabels = <?= json_encode(array_column($topProducts, 'name')) ?>;
            const topData = <?= json_encode(array_column($topProducts, 'qty')) ?>;
            new Chart(top, {
                type: 'bar',
                data: {
                    labels: topLabels.length ? topLabels : ['No data'],
                    datasets: [{ label: 'Units Sold', data: topData.length ? topData : [0], backgroundColor: ['rgba(13, 148, 136, 0.6)', 'rgba(20, 184, 166, 0.6)', 'rgba(45, 212, 191, 0.6)', 'rgba(94, 234, 212, 0.6)', 'rgba(153, 246, 228, 0.6)'], borderColor: '#0d9488', borderWidth: 1, borderRadius: 6 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
            });
        }
    });
    </script>
</body>
</html>
