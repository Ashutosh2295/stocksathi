<?php
/**
 * Admin Dashboard
 * Comprehensive business overview with actionable insights
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Require admin role or higher
$allowedRoles = ['super_admin', 'admin'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$roleManager = new RoleManager();
$userId = Session::getUserId();
$orgId = Session::getOrganizationId();
$orgFilter = $orgId ? "organization_id = " . intval($orgId) . " AND " : "";
$orgWhere = $orgId ? "WHERE organization_id = " . intval($orgId) . " " : "";

// One-click: insert test data so dashboard shows real data (admin/super_admin only)
if (isset($_GET['seed']) && $_GET['seed'] === '1' && in_array(Session::getUserRole(), ['super_admin', 'admin'])) {
    try {
        $today = date('Y-m-d');
        
        // Ensure some categories and products exist for the chart
        $catCount = $db->queryOne("SELECT COUNT(*) as c FROM categories WHERE {$orgFilter} 1=1")['c'];
        if ($catCount == 0) {
            $categories = ['Electronics', 'Groceries', 'Clothing', 'Services'];
            foreach ($categories as $cat) {
                $db->execute("INSERT INTO categories (name, organization_id) VALUES (?, ?)", [$cat, $orgIdPatch]);
            }
        }
        
        $prodCount = $db->queryOne("SELECT COUNT(*) as c FROM products WHERE {$orgFilter} 1=1")['c'];
        if ($prodCount == 0) {
            $cats = $db->query("SELECT id FROM categories WHERE {$orgFilter} 1=1");
            foreach ($cats as $c) {
                for ($i = 1; $i <= 3; $i++) {
                    $db->execute(
                        "INSERT INTO products (name, category_id, stock_quantity, purchase_price, selling_price, status, organization_id) 
                         VALUES (?, ?, ?, ?, ?, 'active', ?)",
                        ["Product " . rand(100, 999), $c['id'], rand(10, 100), rand(100, 500), rand(600, 1000), $orgIdPatch]
                    );
                }
            }
        }

        // Add 5 sample invoices scattered over last 30 days
        for ($i = 0; $i < 5; $i++) {
            $date = date('Y-m-d', strtotime("-" . rand(0, 30) . " days"));
            $amt = rand(1000, 50000);
            $invNum = 'INV-SEED-' . rand(1000, 9999);
            $db->execute(
                "INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_status, status, created_by, organization_id) 
                 VALUES (?, NULL, ?, ?, ?, 0, 0, ?, ?, 'paid', 'finalized', ?, ?)",
                [$invNum, $date, $date, $amt, $amt, $amt, $userId, $orgIdPatch]
            );
        }
        // Add sample expenses
        for ($i = 0; $i < 3; $i++) {
            $date = date('Y-m-d', strtotime("-" . rand(0, 30) . " days"));
            $amt = rand(500, 10000);
            $expNum = 'EXP-SEED-' . rand(1000, 9999);
            $db->execute(
                "INSERT INTO expenses (expense_number, category, amount, expense_date, status, created_by, organization_id) 
                 VALUES (?, 'Sample Data', ?, ?, 'approved', ?, ?)",
                [$expNum, $amt, $date, $userId, $orgIdPatch]
            );
        }
        Session::setFlash('Sample data (Categories, Products, Invoices, Expenses) seeded successfully.', 'success');
    } catch (Exception $e) {
        Session::setFlash('Could not seed data: ' . $e->getMessage(), 'error');
    }
    header('Location: ' . BASE_PATH . '/pages/dashboards/admin.php');
    exit;
}

// Get dashboard statistics with comprehensive data
try {
    // === FINANCIAL OVERVIEW ===
    // Total Revenue - include all paid/partial invoices
    $totalRevenue = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} (payment_status = 'paid' OR payment_status = 'partial') AND status != 'cancelled'")['total'];
    
    // Total Expenses - include all approved expenses
    $totalExpenses = $db->queryOne("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE {$orgFilter} (status = 'approved' OR status = 'paid')")['total'];
    
    // Monthly Expenses - current month expenses
    $monthlyExpensesQuery = $db->queryOne("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE {$orgFilter} MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE()) AND (status = 'approved' OR status = 'paid')");
    $monthlyExpenses = $monthlyExpensesQuery['total'] ?? 0;
    
    $netProfit = $totalRevenue - $totalExpenses;
    
    // Monthly Revenue - include all invoices from current month, not just paid
    $monthlyRevenueQuery = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE()) AND status != 'cancelled'");
    $monthlyRevenue = $monthlyRevenueQuery['total'] ?? 0;
    
    // Previous month for comparison
    $prevMonthRevenue = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(invoice_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND status != 'cancelled'")['total'];
    
    // Revenue growth percentage
    $revenueGrowth = $prevMonthRevenue > 0 ? (($monthlyRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100 : 0;
    
    // GST Summary
    $gstThisMonth = $db->queryOne("SELECT COALESCE(SUM(tax_amount), 0) as total FROM invoices WHERE {$orgFilter} MONTH(invoice_date) = MONTH(CURDATE())")['total'];
    
    // === SALES ANALYTICS ===
    $todaySalesQuery = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND status != 'cancelled'");
    $todaySales = $todaySalesQuery['total'] ?? 0;
    
    $todayInvoicesQuery = $db->queryOne("SELECT COUNT(*) as count FROM invoices WHERE {$orgFilter} DATE(invoice_date) = CURDATE() AND status != 'cancelled'");
    $todayInvoices = $todayInvoicesQuery['count'] ?? 0;
    $weekSales = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE {$orgFilter} YEARWEEK(invoice_date) = YEARWEEK(CURDATE()) AND status != 'cancelled'")['total'];
    
    // === INVENTORY STATS ===
    $totalProducts = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} status = 'active'")['count'];
    $lowStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} stock_quantity > 0 AND stock_quantity <= min_stock_level")['count'];
    $outOfStockCount = $db->queryOne("SELECT COUNT(*) as count FROM products WHERE {$orgFilter} stock_quantity = 0 AND status = 'active'")['count'];
    $stockValue = $db->queryOne("SELECT COALESCE(SUM(purchase_price * stock_quantity), 0) as total FROM products {$orgWhere}")['total'];
    
    // === CUSTOMER STATS ===
    $totalCustomers = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE {$orgFilter} status = 'active'")['count'];
    $newCustomersThisMonth = $db->queryOne("SELECT COUNT(*) as count FROM customers WHERE {$orgFilter} MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'];
    $totalOutstandingBalance = $db->queryOne("SELECT COALESCE(SUM(outstanding_balance), 0) as total FROM customers WHERE {$orgFilter} status = 'active'")['total'];
    
    // Pending Payments - unpaid invoices (amount outstanding)
    $pendingPaymentsQuery = $db->queryOne("SELECT COALESCE(SUM(total_amount - COALESCE(paid_amount, 0)), 0) as total FROM invoices WHERE {$orgFilter} (payment_status = 'unpaid' OR payment_status = 'partial') AND status != 'cancelled'");
    $pendingPayments = $pendingPaymentsQuery['total'] ?? 0;
    
    // === EMPLOYEE STATS ===
    $activeEmployees = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE {$orgFilter} status = 'active'")['count'];
    $employeesByRole = $db->query("SELECT role, COUNT(*) as count FROM users WHERE {$orgFilter} status = 'active' GROUP BY role");
    
    // === TOP SELLING PRODUCTS ===
    $topProducts = $db->query("
        SELECT p.name, p.sku, SUM(ii.quantity) as total_sold, SUM(ii.line_total) as revenue
        FROM invoice_items ii
        INNER JOIN products p ON ii.product_id = p.id
        INNER JOIN invoices i ON ii.invoice_id = i.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " i.status != 'cancelled' AND (i.payment_status = 'paid' OR i.payment_status = 'partial')
        GROUP BY p.id, p.name, p.sku
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    
    // === TOP PERFORMING SALES EXECUTIVES ===
    $topSalesExecutives = $db->query("
        SELECT u.full_name, u.username, COUNT(i.id) as invoice_count, COALESCE(SUM(i.total_amount), 0) as total_sales
        FROM users u
        LEFT JOIN invoices i ON u.id = i.created_by 
            AND MONTH(i.invoice_date) = MONTH(CURDATE()) 
            AND YEAR(i.invoice_date) = YEAR(CURDATE())
            AND i.status != 'cancelled'
        WHERE " . ($orgIdPatch ? " u.organization_id = " . intval($orgIdPatch) . " AND " : "") . " u.role IN ('sales_executive', 'store_manager') AND u.status = 'active'
        GROUP BY u.id, u.full_name, u.username
        ORDER BY total_sales DESC
        LIMIT 5
    ");
    
    // === RECENT INVOICES ===
    $recentInvoices = $db->query("
        SELECT i.*, c.name as customer_name, u.full_name as created_by_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE " . ($orgIdPatch ? " i.organization_id = " . intval($orgIdPatch) . " AND " : "") . " i.status != 'cancelled'
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    
    // === RECENT ACTIVITY ===
    try {
        $recentActivity = $db->query("
            SELECT al.*, u.full_name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE 1=1" . ($orgIdPatch ? " AND al.organization_id = " . intval($orgIdPatch) : "") . "
            ORDER BY al.created_at DESC
            LIMIT 10
        ");
    } catch (Exception $e) {
        $recentActivity = [];
        error_log("Activity logs error: " . $e->getMessage());
    }
    
    // === PENDING ITEMS ===
    $pendingInvoices = $db->queryOne("SELECT COUNT(*) as count FROM invoices WHERE {$orgFilter} (payment_status = 'unpaid' OR payment_status = 'partial') AND status != 'cancelled'")['count'];
    try {
        $pendingExpenses = $db->queryOne("SELECT COUNT(*) as count FROM expenses WHERE {$orgFilter} status = 'pending'")['count'];
    } catch (Exception $e) {
        $pendingExpenses = 0;
    }
    try {
        $pendingReturns = $db->queryOne("SELECT COUNT(*) as count FROM sales_returns WHERE {$orgFilter} status = 'pending'")['count'];
    } catch (Exception $e) {
        $pendingReturns = 0;
    }
    
    // === SALES TREND (Dynamic Range) ===
    $chartRange = $_GET['range'] ?? '30days';
    $interval = '7 DAY';
    $dateFormat = '%Y-%m-%d';
    $points = 7;
    $useMonthLabels = false;
    
    if ($chartRange === '30days') {
        $interval = '30 DAY';
        $points = 30;
    } elseif ($chartRange === '7days') {
        $interval = '7 DAY';
        $points = 7;
    } elseif ($chartRange === '12months') {
        $interval = '12 MONTH';
        $points = 12;
        $useMonthLabels = true;
    } elseif ($chartRange === 'all') {
        $interval = '120 MONTH'; // 10 years roughly for 'all'
        $points = 24; // show last 24 months
        $useMonthLabels = true;
    }

    $salesTrend = $db->query("
        SELECT " . ($useMonthLabels ? "DATE_FORMAT(invoice_date, '%Y-%m')" : "DATE(invoice_date)") . " as date, 
        COALESCE(SUM(total_amount), 0) as total
        FROM invoices
        WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
        AND status != 'cancelled' AND ({$orgFilter} 1=1)
        GROUP BY 1
        ORDER BY date ASC
    ");
    
    // Fill in missing points
    $dateLabels = [];
    $dateValues = [];
    
    if ($useMonthLabels) {
        for ($i = $points - 1; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $dateLabels[] = date('M Y', strtotime($date . '-01'));
            $found = false;
            foreach ($salesTrend as $row) {
                if ($row['date'] == $date) {
                    $dateValues[] = (float)$row['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) $dateValues[] = 0;
        }
    } else {
        for ($i = ($points - 1); $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dateLabels[] = ($chartRange === '30days') ? date('d M', strtotime($date)) : date('D', strtotime($date));
            $found = false;
            foreach ($salesTrend as $row) {
                if ($row['date'] == $date) {
                    $dateValues[] = (float)$row['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) $dateValues[] = 0;
        }
    }
    
    // === CATEGORY BREAKDOWN ===
    try {
        $categoryBreakdown = $db->query("
            SELECT c.name, COUNT(p.id) as product_count, COALESCE(SUM(p.stock_quantity * p.selling_price), 0) as value
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'" . ($orgIdPatch ? " WHERE c.organization_id = " . intval($orgIdPatch) : "") . "
            GROUP BY c.id, c.name
            HAVING product_count > 0
            ORDER BY value DESC
            LIMIT 5
        ");
    } catch (Exception $e) {
        $categoryBreakdown = [];
        error_log("Category breakdown error: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Admin Dashboard error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // Set default values - ensure ALL variables are initialized
    $totalRevenue = $totalExpenses = $monthlyExpenses = $netProfit = $monthlyRevenue = 0;
    $gstThisMonth = $todaySales = $todayInvoices = $weekSales = 0;
    $totalProducts = $lowStockCount = $outOfStockCount = 0;
    $stockValue = $totalCustomers = $newCustomersThisMonth = 0;
    $totalOutstandingBalance = $pendingPayments = $activeEmployees = 0;
    $revenueGrowth = 0;
    $topProducts = $topSalesExecutives = $recentInvoices = [];
    $recentActivity = $employeesByRole = $categoryBreakdown = [];
    $pendingInvoices = $pendingExpenses = $pendingReturns = 0;
    $dateLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $dateValues = [0, 0, 0, 0, 0, 0, 0];
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

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year(s) ago';
    if ($diff->m > 0) return $diff->m . ' month(s) ago';
    if ($diff->d > 0) return $diff->d . ' day(s) ago';
    if ($diff->h > 0) return $diff->h . ' hour(s) ago';
    if ($diff->i > 0) return $diff->i . ' min ago';
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Stocksathi</title>
    <meta name="description" content="Admin Dashboard for Stocksathi - Comprehensive business overview and analytics">
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
        .dashboard-grid { display: grid; gap: 24px; }
        .dashboard-header { 
            background: linear-gradient(135deg, #3a63a5 0%, #4f82d5 50%, #4f82d5 100%); 
            padding: 32px; border-radius: 10px; color: white; margin-bottom: 24px;
            position: relative; overflow: hidden;
            border: none; box-shadow: 0 8px 32px rgba(79, 130, 213, 0.3);
        }
        
        /* Professional Card Styling */
        .card {
            background: #FFFFFF;
            border: 1px solid #E5E9F0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(79, 130, 213, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
        }
        .card:hover {
            box-shadow: 0 8px 24px rgba(79, 130, 213, 0.12);
            border-color: #4f82d5;
        }
        .dashboard-header::before {
            content: ''; position: absolute; top: -50%; right: -20%; 
            width: 300px; height: 300px; background: rgba(255,255,255,0.1); 
            border-radius: 50%; pointer-events: none;
        }
        .dashboard-header::after {
            content: ''; position: absolute; bottom: -60%; left: -10%; 
            width: 200px; height: 200px; background: rgba(255,255,255,0.05); 
            border-radius: 50%; pointer-events: none;
        }
        .financial-cards { 
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 24px; 
        }
        .financial-card { 
            background: rgba(255,255,255,0.15); padding: 24px; border-radius: 8px;
            backdrop-filter: blur(10px); transition: transform 0.2s ease;
            border: 1px solid rgba(255,255,255,0.2);
            min-height: 120px; display: flex; flex-direction: column; justify-content: space-between;
        }
        .financial-card:hover { 
            transform: translateY(-3px); 
            background: rgba(255,255,255,0.22); 
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .financial-card-label { 
            font-size: 11px; opacity: 0.9; text-transform: uppercase; 
            letter-spacing: 0.8px; font-weight: 600; margin-bottom: 8px;
        }
        .financial-card-value { 
            font-size: 28px; font-weight: 700; margin: 8px 0; 
            line-height: 1.2;
        }
        .financial-card-change { 
            font-size: 11px; margin-top: 8px; display: flex; 
            align-items: center; gap: 4px; font-weight: 500;
        }
        .financial-card-change.positive { color: #D1FAE5; }
        .financial-card-change.negative { color: #FEF3C7; }
        
        .stats-grid { 
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; 
        }
        .stat-card {
            background: #FFFFFF; border-radius: 8px; padding: 24px;
            border: 1px solid #E5E9F0; position: relative;
            box-shadow: 0 2px 8px rgba(79, 130, 213, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.2s ease, transform 0.2s ease, border-color 0.2s ease;
            min-height: 140px; display: flex; flex-direction: column;
        }
        .stat-card:hover { 
            box-shadow: 0 8px 24px rgba(79, 130, 213, 0.15); 
            transform: translateY(-3px); 
            border-color: #4f82d5;
        }
        .stat-card-icon {
            width: 52px; height: 52px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 26px; margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card-icon.primary { background: linear-gradient(135deg, #3a63a5, #4f82d5); color: white; }
        .stat-card-icon.success { background: linear-gradient(135deg, #10B981, #34D399); color: white; }
        .stat-card-icon.warning { background: linear-gradient(135deg, #F59E0B, #FBBF24); color: white; }
        .stat-card-icon.danger { background: linear-gradient(135deg, #EF4444, #F87171); color: white; }
        .stat-card-icon.info { background: linear-gradient(135deg, #3a63a5, #4f82d5); color: white; }
        .stat-card-label { 
            font-size: 13px; color: #64748B; font-weight: 500; 
            text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;
        }
        .stat-card-value { 
            font-size: 28px; font-weight: 700; color: #0F172A; 
            margin: 8px 0; line-height: 1.2;
        }
        .stat-card-meta { 
            font-size: 12px; color: #94A3B8; margin-top: auto; 
            padding-top: 8px;
        }
        
        .quick-actions-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; }
        .quick-action-btn {
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            padding: 16px 8px; background: white; border: 1px solid var(--border-light);
            border-radius: 8px; text-decoration: none; color: var(--text-primary);
            transition: all 0.2s ease; font-size: 12px; text-align: center;
        }
        .quick-action-btn:hover { 
            border-color: var(--color-primary); 
            box-shadow: 0 4px 12px rgba(79, 130, 213, 0.2);
            transform: translateY(-2px);
        }
        .quick-action-icon { font-size: 24px; }
        
        .pending-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; background: #fef3c7; border-radius: 8px;
            margin-bottom: 8px; border-left: 4px solid #f59e0b;
        }
        .pending-item.urgent { background: #fee2e2; border-left-color: #ef4444; }
        
        .chart-container { position: relative; height: 280px; }
        
        .top-list-item {
            display: flex; align-items: center; padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }
        .top-list-item:last-child { border-bottom: none; }
        .top-list-rank {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, #3a63a5, #4f82d5);
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; margin-right: 12px;
        }
        .top-list-rank.gold { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .top-list-rank.silver { background: linear-gradient(135deg, #6b7280, #9ca3af); }
        .top-list-rank.bronze { background: linear-gradient(135deg, #92400e, #b45309); }
        
        .alert-badge {
            position: absolute; top: -8px; right: -8px;
            background: #ef4444; color: white; font-size: 11px; font-weight: 600;
            padding: 2px 8px; border-radius: 10px; min-width: 20px; text-align: center;
        }
        
        @media (max-width: 1200px) {
            .financial-cards { grid-template-columns: repeat(2, 1fr); }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .financial-cards { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
                <?php 
                $flash = Session::getFlash();
                if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> mb-6">
                        <strong><?= $flash['type'] === 'success' ? 'Success!' : 'Error!' ?></strong> <?= htmlspecialchars($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Header with Financial Overview -->
                <div class="dashboard-header mb-6">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1;">
                        <div>
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700;">👑 Admin Dashboard</h1>
                            <p style="margin: 8px 0 0 0; opacity: 0.9; color: white;">Complete business overview • <?= date('l, F j, Y') ?></p>
                        </div>
                        <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                            <a href="?seed=1" class="btn btn-sm btn-ghost" style="border: 1px solid rgba(255,255,255,0.3); color: white;" onclick="return confirm('This will add 5 sample invoices and 3 expenses for testing. Proceed?')">🧪 Seed Sample Data</a>
                            <div id="liveTime" style="font-size: 24px; font-weight: 700;"><?= date('h:i A') ?></div>
                            <div id="lastUpdated" style="font-size: 12px; opacity: 0.8;">Last updated just now</div>
                        </div>
                    </div>
                    
                    <div class="financial-cards">
                        <div class="financial-card">
                            <div class="financial-card-label">💰 Total Revenue</div>
                            <div class="financial-card-value"><?= formatCurrency($totalRevenue) ?></div>
                            <div class="financial-card-change <?= $revenueGrowth >= 0 ? 'positive' : 'negative' ?>">
                                <?= $revenueGrowth >= 0 ? '↑' : '↓' ?> <?= number_format(abs($revenueGrowth), 1) ?>% vs last month
                            </div>
                        </div>
                        <div class="financial-card">
                            <div class="financial-card-label">📊 Monthly Revenue</div>
                            <div class="financial-card-value"><?= formatCurrency($monthlyRevenue) ?></div>
                            <div class="financial-card-change positive">This month's earnings</div>
                        </div>
                        <div class="financial-card">
                            <div class="financial-card-label">📉 Total Expenses</div>
                            <div class="financial-card-value"><?= formatCurrency($monthlyExpenses) ?></div>
                            <div class="financial-card-change negative"><?= $pendingExpenses ?> pending approval</div>
                        </div>
                        <div class="financial-card">
                            <div class="financial-card-label">💵 Net Profit</div>
                            <div class="financial-card-value" style="color: <?= $netProfit >= 0 ? '#4ade80' : '#f87171' ?>"><?= formatCurrency(abs($netProfit)) ?></div>
                            <div class="financial-card-change <?= $netProfit >= 0 ? 'positive' : 'negative' ?>">
                                <?= $netProfit >= 0 ? 'Profitable' : 'Loss' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid mb-6">
                    <div class="stat-card">
                        <div class="stat-card-icon primary">📦</div>
                        <div class="stat-card-label">Total Products</div>
                        <div class="stat-card-value"><?= number_format($totalProducts) ?></div>
                        <div class="stat-card-meta">
                            <?php if ($lowStockCount > 0): ?>
                                <span style="color: #f59e0b;">⚠️ <?= $lowStockCount ?> low stock</span>
                            <?php else: ?>
                                <span style="color: #22c55e;">✓ All stocked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon success">🧾</div>
                        <div class="stat-card-label">Today's Sales</div>
                        <div class="stat-card-value"><?= formatCurrency($todaySales) ?></div>
                        <div class="stat-card-meta"><?= $todayInvoices ?> invoices today</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon info">👥</div>
                        <div class="stat-card-label">Active Customers</div>
                        <div class="stat-card-value"><?= number_format($totalCustomers) ?></div>
                        <div class="stat-card-meta">+<?= $newCustomersThisMonth ?> this month</div>
                    </div>
                    <div class="stat-card" style="position: relative;">
                        <?php if ($pendingInvoices > 0): ?>
                        <span class="alert-badge"><?= $pendingInvoices ?></span>
                        <?php endif; ?>
                        <div class="stat-card-icon warning">⏳</div>
                        <div class="stat-card-label">Pending Payments</div>
                        <div class="stat-card-value"><?= formatCurrency($pendingPayments) ?></div>
                        <div class="stat-card-meta"><?= $pendingInvoices ?> unpaid invoices</div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions-grid">
                            <a href="<?= BASE_PATH ?>/pages/invoices.php" class="quick-action-btn">
                                <span class="quick-action-icon">🧾</span>
                                <span>New Invoice</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/products.php" class="quick-action-btn">
                                <span class="quick-action-icon">📦</span>
                                <span>Add Product</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/customers.php" class="quick-action-btn">
                                <span class="quick-action-icon">👤</span>
                                <span>Add Customer</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/stock-in.php" class="quick-action-btn">
                                <span class="quick-action-icon">📥</span>
                                <span>Stock In</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/expenses.php" class="quick-action-btn">
                                <span class="quick-action-icon">💰</span>
                                <span>Record Expense</span>
                            </a>
                            <a href="<?= BASE_PATH ?>/pages/reports.php" class="quick-action-btn">
                                <span class="quick-action-icon">📊</span>
                                <span>View Reports</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pending Items Alert -->
                <?php if ($pendingExpenses > 0 || $pendingReturns > 0 || $lowStockCount > 0): ?>
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">🔔 Pending Actions Required</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($pendingExpenses > 0): ?>
                        <div class="pending-item">
                            <div>
                                <strong>💰 <?= $pendingExpenses ?> Expenses</strong> awaiting approval
                            </div>
                            <a href="<?= BASE_PATH ?>/pages/expenses.php?status=pending" class="btn btn-sm btn-warning">Review</a>
                        </div>
                        <?php endif; ?>
                        <?php if ($pendingReturns > 0): ?>
                        <div class="pending-item">
                            <div>
                                <strong>↩️ <?= $pendingReturns ?> Sales Returns</strong> need processing
                            </div>
                            <a href="<?= BASE_PATH ?>/pages/sales-returns.php?status=pending" class="btn btn-sm btn-warning">Process</a>
                        </div>
                        <?php endif; ?>
                        <?php if ($lowStockCount > 0): ?>
                        <div class="pending-item urgent">
                            <div>
                                <strong>⚠️ <?= $lowStockCount ?> Products</strong> running low on stock
                            </div>
                            <a href="<?= BASE_PATH ?>/pages/products.php?filter=low_stock" class="btn btn-sm btn-danger">Restock</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Charts Row -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Sales Trend Chart -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">📈 Sales Trend</h3>
                            <div class="range-selector flex gap-2">
                                <a href="?range=7days" class="btn btn-xs <?= $chartRange === '7days' ? 'btn-primary' : 'btn-ghost' ?>">7D</a>
                                <a href="?range=30days" class="btn btn-xs <?= $chartRange === '30days' ? 'btn-primary' : 'btn-ghost' ?>">30D</a>
                                <a href="?range=12months" class="btn btn-xs <?= $chartRange === '12months' ? 'btn-primary' : 'btn-ghost' ?>">12M</a>
                                <a href="?range=all" class="btn btn-xs <?= $chartRange === 'all' ? 'btn-primary' : 'btn-ghost' ?>">All</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div id="salesTrendChart" style="height: 100%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Category Distribution</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div id="categoryChart" style="height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products & Sales Executives -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Top Products -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">🏆 Top Selling Products</h3>
                            <a href="<?= BASE_PATH ?>/pages/products.php" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topProducts)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No sales data available yet
                                </p>
                            <?php else: ?>
                                <?php foreach ($topProducts as $index => $product): ?>
                                <div class="top-list-item">
                                    <div class="top-list-rank <?= $index == 0 ? 'gold' : ($index == 1 ? 'silver' : ($index == 2 ? 'bronze' : '')) ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($product['name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-secondary);">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: var(--color-success);"><?= formatCurrency($product['revenue']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-secondary);"><?= number_format($product['total_sold']) ?> sold</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Sales Executives -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">⭐ Top Sales Executives</h3>
                            <a href="<?= BASE_PATH ?>/pages/users.php" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topSalesExecutives)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No sales data available yet
                                </p>
                            <?php else: ?>
                                <?php foreach ($topSalesExecutives as $index => $exec): ?>
                                <div class="top-list-item">
                                    <div class="top-list-rank <?= $index == 0 ? 'gold' : ($index == 1 ? 'silver' : ($index == 2 ? 'bronze' : '')) ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($exec['full_name'] ?? $exec['username']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-secondary);"><?= $exec['invoice_count'] ?> invoices this month</div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: var(--color-success);"><?= formatCurrency($exec['total_sales']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-secondary);">Total sales</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices & Activity -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Recent Invoices -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">📋 Recent Invoices</h3>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($recentInvoices)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No invoices yet
                                </p>
                            <?php else: ?>
                                <table class="table" style="font-size: 13px;">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">📜 Recent Activity</h3>
                            <a href="<?= BASE_PATH ?>/pages/activity-logs.php" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($recentActivity)): ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">
                                    No activity yet
                                </p>
                            <?php else: ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                <div style="padding: 12px 0; border-bottom: 1px solid var(--border-light);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <div style="font-weight: 500;"><?= htmlspecialchars($activity['action'] ?? 'Action') ?></div>
                                            <div style="font-size: 12px; color: var(--text-secondary);">
                                                by <?= htmlspecialchars($activity['user_name'] ?? 'System') ?> • <?= htmlspecialchars($activity['module'] ?? 'System') ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-tertiary);">
                                            <?= timeAgo($activity['created_at']) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            
<script>
// Self-executing function so it works on direct load and AJAX load
function initAdminPage() {
    function draw() {
        if (window.adminSalesChart) window.adminSalesChart.destroy();
        if (window.adminCategoryChart) window.adminCategoryChart.destroy();
        
        // --- Sales Trend Chart ---
        const salesElement = document.getElementById('salesTrendChart');
        if (salesElement) {
            const rawSalesData = <?= json_encode($dateValues ?: [0, 0, 0, 0, 0, 0, 0]) ?>;
            const hasData = rawSalesData.some(val => val > 0);
            
            if (hasData) {
                var salesOptions = {
                    series: [{
                        name: 'Sales (₹)',
                        data: rawSalesData
                    }],
                    chart: {
                        type: 'area',
                        height: '100%',
                        toolbar: { show: false },
                        parentHeightOffset: 0
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
                        categories: <?= json_encode($dateLabels ?: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']) ?>,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            hideOverlappingLabels: true,
                            rotate: -45,
                            rotateAlways: false,
                            style: { fontSize: '10px' }
                        },
                        tickAmount: <?= $chartRange === '30days' ? 10 : ($chartRange === '12months' ? 12 : ($chartRange === 'all' ? 12 : 'undefined')) ?>
                    },
                    yaxis: {
                        labels: {
                            formatter: function (value) {
                                if (value >= 100000) return '₹' + (value/100000).toFixed(1) + 'L';
                                if (value >= 1000) return '₹' + (value/1000).toFixed(1) + 'K';
                                return '₹' + value;
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(0,0,0,0.05)',
                        strokeDashArray: 4,
                    }
                };

                window.adminSalesChart = new ApexCharts(salesElement, salesOptions);
                window.adminSalesChart.render();
            } else {
                const rangeText = '<?= $chartRange === "30days" ? "last 30 days" : ($chartRange === "12months" ? "last 12 months" : ($chartRange === "all" ? "the entire period" : "last 7 days")) ?>';
                salesElement.parentElement.innerHTML = '<div style="text-align:center;padding:60px;color:var(--text-secondary);"><p>No sales data available for ' + rangeText + '</p></div>';
            }
        }

        // --- Category Chart ---
        const categoryElement = document.getElementById('categoryChart');
        if (categoryElement) {
            const categoryData = <?= json_encode(array_map(function($c) { return ['name' => $c['name'] ?? '', 'value' => (float)($c['value'] ?? 0)]; }, $categoryBreakdown ?: [])) ?>;
            const hasCategoryData = categoryData && categoryData.length > 0 && categoryData.some(c => c.value > 0);
            
            if (hasCategoryData) {
                var categoryOptions = {
                    series: categoryData.map(c => c.value),
                    labels: categoryData.map(c => c.name),
                    chart: {
                        type: 'donut',
                        height: '100%'
                    },
                    colors: ['#4f82d5', '#4ade80', '#f59e0b', '#0ea5e9', '#8b5cf6', '#ec4899'],
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%'
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    legend: {
                        position: 'right',
                        fontSize: '12px',
                        markers: { radius: 12 }
                    },
                    tooltip: {
                        y: {
                            formatter: function (value) {
                                if (value >= 100000) return '₹' + (value/100000).toFixed(2) + 'L';
                                if (value >= 1000) return '₹' + (value/1000).toFixed(2) + 'K';
                                return '₹' + value.toFixed(2);
                            }
                        }
                    }
                };

                window.adminCategoryChart = new ApexCharts(categoryElement, categoryOptions);
                window.adminCategoryChart.render();
            } else {
                categoryElement.parentElement.innerHTML = '<div style="text-align:center;padding:60px;color:var(--text-secondary);"><p>No category data available yet</p></div>';
            }
        }
        
        // --- Live time update ---
        function updateLiveTime() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            
            const timeElement = document.getElementById('liveTime');
            const lastUpdatedElement = document.getElementById('lastUpdated');
            
            if (timeElement) {
                timeElement.textContent = displayHours + ':' + displayMinutes + ' ' + ampm;
            }
            if (lastUpdatedElement) {
                lastUpdatedElement.textContent = 'Last updated at ' + displayHours + ':' + displayMinutes + ' ' + ampm;
            }
        }
        
        updateLiveTime();
        if(window.adminTimeInterval) clearInterval(window.adminTimeInterval);
        window.adminTimeInterval = setInterval(updateLiveTime, 60000); // 1 min

    }

    if (typeof ApexCharts === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
        script.onload = function() {
            window.requestAnimationFrame ? window.requestAnimationFrame(() => window.requestAnimationFrame(draw)) : setTimeout(draw, 16);
        };
        document.head.appendChild(script);
    } else {
        window.requestAnimationFrame ? window.requestAnimationFrame(() => window.requestAnimationFrame(draw)) : setTimeout(draw, 16);
    }
}
    
    initAdminPage();
    </script>
</main>
        </div>
    </div>

    
</body>
</html>
