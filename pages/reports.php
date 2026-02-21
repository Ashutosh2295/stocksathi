<?php
/**
 * Reports & Analytics Page - PHP Version
 * Uses real database queries for all reports
 */
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Role-based access: admin, super_admin, and accountant can view reports
$userRole = Session::getUserRole();
if (!in_array($userRole, ['super_admin', 'admin', 'accountant'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Handle Export Requests (must be before any HTML output)
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $reportType = $_GET['report_type'] ?? 'sales';
    
    if ($exportType === 'excel' || $exportType === 'csv') {
        // Export to Excel/CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="report_' . $reportType . '_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if ($reportType === 'sales') {
            // Sales Report
            $query = "SELECT i.invoice_number, c.name as customer_name, i.invoice_date, i.total_amount, i.payment_status 
                     FROM invoices i
                     LEFT JOIN customers c ON i.customer_id = c.id
                     WHERE i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'
                     ORDER BY i.invoice_date DESC";
            $data = $db->query($query, [$startDate, $endDate]);
            
            fputcsv($output, ['Invoice Number', 'Customer', 'Date', 'Amount', 'Status']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['invoice_number'] ?? 'INV-' . $row['id'],
                    $row['customer_name'] ?? '-',
                    $row['invoice_date'],
                    number_format((float)$row['total_amount'], 2),
                    ucfirst($row['payment_status'])
                ]);
            }
        } elseif ($reportType === 'inventory') {
            // Inventory Report
            $query = "SELECT p.name, p.sku, p.stock_quantity, p.min_stock_level, p.purchase_price, p.selling_price
                     FROM products p
                     WHERE {$orgFilter} p.status = 'active'
                     ORDER BY p.name";
            $data = $db->query($query);
            
            fputcsv($output, ['Product Name', 'SKU', 'Stock', 'Min Level', 'Purchase Price', 'Selling Price']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['name'],
                    $row['sku'] ?? '-',
                    $row['stock_quantity'],
                    $row['min_stock_level'],
                    number_format((float)$row['purchase_price'], 2),
                    number_format((float)$row['selling_price'], 2)
                ]);
            }
        } elseif ($reportType === 'expense') {
            // Expense Report (expenses use category varchar, description)
            $query = "SELECT e.expense_number, e.category, e.description, e.amount, e.expense_date, e.vendor, e.status
                     FROM expenses e
                     WHERE e.expense_date BETWEEN ? AND ?
                     ORDER BY e.expense_date DESC";
            $data = $db->query($query, [$startDate, $endDate]);
            
            fputcsv($output, ['Expense Number', 'Category', 'Description', 'Vendor', 'Amount', 'Date', 'Status']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['expense_number'] ?? 'EXP-' . ($row['id'] ?? ''),
                    $row['category'] ?? '-',
                    $row['description'] ?? '-',
                    $row['vendor'] ?? '-',
                    number_format((float)($row['amount'] ?? 0), 2),
                    $row['expense_date'] ?? '',
                    ucfirst($row['status'] ?? '')
                ]);
            }
        } elseif ($reportType === 'profit') {
            // Profit & Loss Report
            $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices 
                           WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'";
            $revenue = $db->queryOne($revenueQuery, [$startDate, $endDate]);
            
            $costQuery = "SELECT COALESCE(SUM(ii.quantity * p.purchase_price), 0) as total 
                         FROM invoice_items ii
                         INNER JOIN products p ON ii.product_id = p.id
                         INNER JOIN invoices i ON ii.invoice_id = i.id
                         WHERE i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'";
            $cost = $db->queryOne($costQuery, [$startDate, $endDate]);
            
            $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                           WHERE expense_date BETWEEN ? AND ? AND status = 'approved'";
            $expense = $db->queryOne($expenseQuery, [$startDate, $endDate]);
            
            $totalRevenue = (float)($revenue['total'] ?? 0);
            $totalCost = (float)($cost['total'] ?? 0);
            $totalExpenses = (float)($expense['total'] ?? 0);
            $grossProfit = $totalRevenue - $totalCost;
            $netProfit = $totalRevenue - $totalCost - $totalExpenses;
            
            fputcsv($output, ['Item', 'Amount']);
            fputcsv($output, ['Total Revenue', number_format($totalRevenue, 2)]);
            fputcsv($output, ['Cost of Goods Sold', number_format($totalCost, 2)]);
            fputcsv($output, ['Gross Profit', number_format($grossProfit, 2)]);
            fputcsv($output, ['Operating Expenses', number_format($totalExpenses, 2)]);
            fputcsv($output, ['Net Profit', number_format($netProfit, 2)]);
            fputcsv($output, ['Profit Margin %', number_format(($totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0), 2)]);
        } elseif ($reportType === 'customer') {
            // Customer Report
            $query = "SELECT c.name, c.email, c.phone, c.city, COUNT(DISTINCT i.id) as total_invoices, SUM(i.total_amount) as total_spent
                     FROM customers c
                     LEFT JOIN invoices i ON c.id = i.customer_id AND i.invoice_date BETWEEN ? AND ?
                     WHERE {$orgFilter} c.status = 'active'
                     GROUP BY c.id
                     ORDER BY total_spent DESC";
            $data = $db->query($query, [$startDate, $endDate]);
            
            fputcsv($output, ['Customer Name', 'Email', 'Phone', 'City', 'Total Invoices', 'Total Spent']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['name'],
                    $row['email'] ?? '-',
                    $row['phone'] ?? '-',
                    $row['city'] ?? '-',
                    $row['total_invoices'],
                    number_format((float)($row['total_spent'] ?? 0), 2)
                ]);
            }
        }
        
        fclose($output);
        exit;
    } elseif ($exportType === 'pdf') {
        // For PDF, we'll generate HTML that can be printed or converted
        // This is a simple implementation - you can enhance with TCPDF or FPDF later
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $reportType = $_GET['report_type'] ?? 'sales';
        
        // Redirect to print-friendly view
        header('Location: ' . $_SERVER['PHP_SELF'] . '?print=1&' . http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'report_type' => $reportType]));
        exit;
    }
}

// Get date range filters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'sales';
$isPrint = isset($_GET['print']) && $_GET['print'] == '1';

// Calculate summary statistics
try {
    // Total Revenue (from invoices)
    $revenueQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices 
                     WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'";
    $revenue = $db->queryOne($revenueQuery, [$startDate, $endDate]);
    $totalRevenue = (float)($revenue['total'] ?? 0);
    
    // Total Cost (from purchase prices in invoice items)
    $costQuery = "SELECT COALESCE(SUM(ii.quantity * p.purchase_price), 0) as total 
                  FROM invoice_items ii
                  INNER JOIN products p ON ii.product_id = p.id
                  INNER JOIN invoices i ON ii.invoice_id = i.id
                  WHERE i.invoice_date BETWEEN ? AND ? AND i.status != 'cancelled'";
    $cost = $db->queryOne($costQuery, [$startDate, $endDate]);
    $totalCost = (float)($cost['total'] ?? 0);
    
    // Total Expenses
    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE expense_date BETWEEN ? AND ? AND status = 'approved'";
    $expense = $db->queryOne($expenseQuery, [$startDate, $endDate]);
    $totalExpenses = (float)($expense['total'] ?? 0);
    
    // Net Profit
    $netProfit = $totalRevenue - $totalCost - $totalExpenses;
    $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;
    
    // Inventory Summary
    $inventoryQuery = "SELECT 
                       COUNT(*) as total_products,
                       SUM(CASE WHEN stock_quantity > min_stock_level THEN 1 ELSE 0 END) as in_stock,
                       SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock,
                       SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
                       FROM products WHERE {$orgFilter} status = 'active'";
    $inventory = $db->queryOne($inventoryQuery);
    
    // Customer Summary
    $customerQuery = "SELECT 
                      COUNT(*) as total_customers,
                      COUNT(DISTINCT i.customer_id) as active_this_month
                      FROM customers c
                      LEFT JOIN invoices i ON c.id = i.customer_id AND DATE(i.invoice_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      WHERE {$orgFilter} c.status = 'active'";
    $customerStats = $db->queryOne($customerQuery);
    
    // Average Order Value
    $avgOrderQuery = "SELECT COALESCE(AVG(total_amount), 0) as avg_order FROM invoices 
                      WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'";
    $avgOrder = $db->queryOne($avgOrderQuery, [$startDate, $endDate]);
    $avgOrderValue = (float)($avgOrder['avg_order'] ?? 0);
    
    // Top Selling Products (This Month)
    $topProductsQuery = "SELECT 
                         p.name as product_name,
                         c.name as category_name,
                         SUM(ii.quantity) as units_sold,
                         SUM(ii.line_total) as revenue,
                         SUM(ii.quantity * (ii.unit_price - p.purchase_price)) as profit
                         FROM invoice_items ii
                         INNER JOIN products p ON ii.product_id = p.id
                         LEFT JOIN categories c ON p.category_id = c.id
                         INNER JOIN invoices i ON ii.invoice_id = i.id
                         WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                         AND i.status != 'cancelled'
                         GROUP BY p.id, p.name, c.name
                         ORDER BY units_sold DESC
                         LIMIT 10";
    $topProducts = $db->query($topProductsQuery);
    
    // Sales Trend (Last 30 Days - Weekly)
    $salesTrendQuery = "SELECT 
                       DATE_FORMAT(invoice_date, '%Y-%u') as week,
                       SUM(total_amount) as weekly_sales
                       FROM invoices
                       WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                       AND status != 'cancelled'
                       GROUP BY DATE_FORMAT(invoice_date, '%Y-%u')
                       ORDER BY week ASC
                       LIMIT 4";
    $salesTrend = $db->query($salesTrendQuery);
    
} catch (Exception $e) {
    error_log("Reports query error: " . $e->getMessage());
    $totalRevenue = 0;
    $totalCost = 0;
    $totalExpenses = 0;
    $netProfit = 0;
    $profitMargin = 0;
    $inventory = ['total_products' => 0, 'in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0];
    $customerStats = ['total_customers' => 0, 'active_this_month' => 0];
    $avgOrderValue = 0;
    $topProducts = [];
    $salesTrend = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php if ($isPrint): ?>
    <style>
        @media print {
            .sidebar, .header, .content-header .flex, .btn, nav.breadcrumb { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 20px !important; }
            body { background: white !important; }
        }
    </style>
    <?php endif; ?>
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Reports</span>
                    </nav>
                    <h1 class="content-title">Reports & Analytics</h1>
                </div>

                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">Generate Report</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="mb-4">
                            <div class="grid grid-cols-4 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Report Type</label>
                                    <select name="report_type" class="form-control">
                                        <option value="sales" <?= $reportType === 'sales' ? 'selected' : '' ?>>Sales Report</option>
                                        <option value="inventory" <?= $reportType === 'inventory' ? 'selected' : '' ?>>Inventory Report</option>
                                        <option value="expense" <?= $reportType === 'expense' ? 'selected' : '' ?>>Expense Report</option>
                                        <option value="profit" <?= $reportType === 'profit' ? 'selected' : '' ?>>Profit & Loss</option>
                                        <option value="customer" <?= $reportType === 'customer' ? 'selected' : '' ?>>Customer Report</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-full">Generate</button>
                                </div>
                            </div>
                        </form>
                        <div class="flex gap-3">
                            <a href="?export=excel&<?= http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'report_type' => $reportType]) ?>" class="btn btn-outline">📊 Export Excel</a>
                            <a href="?export=pdf&<?= http_build_query(['start_date' => $startDate, 'end_date' => $endDate, 'report_type' => $reportType]) ?>" class="btn btn-outline" target="_blank">📄 Export PDF</a>
                            <button class="btn btn-outline" onclick="window.print()">🖨️ Print</button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales Trend (Last 30 Days)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesTrendChart" height="250"></canvas>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Profit Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-secondary">Total Revenue</span>
                                    <span class="font-semibold text-lg">₹<?= number_format($totalRevenue, 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-secondary">Total Cost</span>
                                    <span class="font-semibold text-lg text-danger">₹<?= number_format($totalCost, 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center mb-2 pb-3"
                                    style="border-bottom: 2px solid var(--border-medium);">
                                    <span class="text-secondary">Operating Expenses</span>
                                    <span class="font-semibold text-lg text-danger">₹<?= number_format($totalExpenses, 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center mt-4">
                                    <span class="font-bold text-lg">Net Profit</span>
                                    <span class="font-bold text-2xl <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>">₹<?= number_format($netProfit, 2) ?></span>
                                </div>
                            </div>
                            <div class="alert alert-<?= $profitMargin >= 20 ? 'success' : ($profitMargin >= 10 ? 'info' : 'warning') ?>">
                                <strong>Profit Margin:</strong> <?= number_format($profitMargin, 2) ?>% 
                                <?php if ($profitMargin >= 20): ?>
                                    - Excellent performance!
                                <?php elseif ($profitMargin >= 10): ?>
                                    - Good performance
                                <?php else: ?>
                                    - Needs improvement
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">Top Selling Products (This Month)</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No sales data available for this period
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $rank = 1;
                                    $badges = ['🥇', '🥈', '🥉'];
                                    foreach ($topProducts as $product): 
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($rank <= 3): ?>
                                                    <span class="badge badge-<?= $rank === 1 ? 'warning' : 'secondary' ?>"><?= $badges[$rank - 1] ?> <?= $rank ?></span>
                                                <?php else: ?>
                                                    <?= $rank ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                                            <td><?= (int)$product['units_sold'] ?></td>
                                            <td>₹<?= number_format((float)$product['revenue'], 2) ?></td>
                                            <td class="text-success font-semibold">₹<?= number_format((float)$product['profit'], 2) ?></td>
                                        </tr>
                                    <?php 
                                        $rank++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Inventory Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">Total Products</span>
                                <span class="font-semibold"><?= (int)($inventory['total_products'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">In Stock</span>
                                <span class="font-semibold text-success"><?= (int)($inventory['in_stock'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">Low Stock</span>
                                <span class="font-semibold text-warning"><?= (int)($inventory['low_stock'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-secondary">Out of Stock</span>
                                <span class="font-semibold text-danger"><?= (int)($inventory['out_of_stock'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Customer Insights</h3>
                        </div>
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">Total Customers</span>
                                <span class="font-semibold"><?= (int)($customerStats['total_customers'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">Active This Month</span>
                                <span class="font-semibold text-success"><?= (int)($customerStats['active_this_month'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-secondary">New Customers</span>
                                <span class="font-semibold text-primary"><?= (int)($customerStats['active_this_month'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-secondary">Avg. Order Value</span>
                                <span class="font-semibold">₹<?= number_format($avgOrderValue, 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Quick Links</h3>
                        </div>
                        <div class="card-body">
                            <a href="?report_type=sales&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-outline w-full mb-3">📊 Sales Report</a>
                            <a href="?report_type=inventory&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-outline w-full mb-3">📦 Inventory Report</a>
                            <a href="?report_type=expense&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-outline w-full mb-3">💰 Expense Report</a>
                            <a href="?report_type=profit&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-outline w-full">📈 Profit & Loss</a>
                        </div>
                    </div>
                </div>

                <script>
                    // Initialize charts when page loads or via AJAX
                    (function initReportsChart() {
                        if (typeof Chart !== 'undefined') {
                            if (window.reportsSalesChart instanceof Chart) {
                                window.reportsSalesChart.destroy();
                            }
                            const ctx = document.getElementById('salesTrendChart');
                            if (ctx) {
                                const salesData = <?= json_encode(array_column($salesTrend, 'weekly_sales')) ?>;
                                const weekLabels = <?= json_encode(array_map(function($w) { return 'Week ' . substr($w, -1); }, array_column($salesTrend, 'week'))) ?>;
                                
                                window.reportsSalesChart = new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: weekLabels.length > 0 ? weekLabels : ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                                        datasets: [{
                                            label: 'Sales (₹)',
                                            data: salesData.length > 0 ? salesData : [0, 0, 0, 0],
                                            borderColor: 'rgb(15, 118, 110)',
                                            backgroundColor: 'rgba(15, 118, 110, 0.1)',
                                            tension: 0.4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top'
                                            }
                                        },
                                        scales: {
                                            y: {
                                                beginAtZero: true
                                            }
                                        }
                                    }
                                });
                            }
                        }
                    })();

                    // Export functions are handled via links in the form
                </script>
            </main>
        </div>
    </div>

    <!-- Scripts (charts.js excluded - it would overwrite sales trend with fake data; this page uses inline chart) -->
    <script src="<?= JS_PATH ?>/api-client.js"></script>
    <script src="<?= JS_PATH ?>/app.js"></script>
</body>
</html>
