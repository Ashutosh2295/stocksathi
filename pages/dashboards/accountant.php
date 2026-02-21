<?php
/**
 * Accountant Dashboard
 * Financial overview with GST compliance features
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Require accountant role (or super_admin/admin)
$allowedRoles = ['super_admin', 'admin', 'accountant'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Get dashboard statistics
try {
    // Financial Overview - This Month
    $currentMonth = date('Y-m');
    $currentMonthStart = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');
    
    // Total Revenue This Month
    $totalRevenueThisMonth = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM invoices 
        WHERE DATE_FORMAT(invoice_date, '%Y-%m') = ? 
        AND payment_status = 'paid'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ", [$currentMonth])['total'];
    
    // Total Expenses This Month
    $totalExpensesThisMonth = $db->queryOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE DATE_FORMAT(expense_date, '%Y-%m') = ? 
        AND status = 'approved'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ", [$currentMonth])['total'];
    
    // Net Profit This Month
    $netProfitThisMonth = $totalRevenueThisMonth - $totalExpensesThisMonth;
    
    // GST Collected This Month (18% of taxable amount)
    $gstCollected = $db->queryOne("
        SELECT COALESCE(SUM(tax_amount), 0) as total 
        FROM invoices 
        WHERE DATE_FORMAT(invoice_date, '%Y-%m') = ?
        AND status != 'cancelled'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ", [$currentMonth])['total'];
    
    // Outstanding Receivables
    $outstandingReceivables = $db->queryOne("
        SELECT COALESCE(SUM(balance_amount), 0) as total 
        FROM invoices 
        WHERE payment_status IN ('unpaid', 'partial', 'overdue')" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ")['total'];
    
    // Overdue Invoices Count
    $overdueInvoicesCount = $db->queryOne("
        SELECT COUNT(*) as count 
        FROM invoices 
        WHERE due_date < CURDATE() 
        AND payment_status IN ('unpaid', 'partial')" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
    ")['count'];
    
    // Pending Expenses (awaiting approval)
    $pendingExpenses = $db->queryOne("
        SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
        FROM expenses 
        WHERE {$orgFilter} status = 'pending'
    ");
    
    // Monthly Revenue Trend (Last 6 months)
    $revenueQuery = "
        SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month,
               COALESCE(SUM(total_amount), 0) as revenue
        FROM invoices 
        WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND payment_status = 'paid'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
        GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
        ORDER BY month ASC
    ";
    $revenueTrend = $db->query($revenueQuery);
    
    // Monthly Expenses Trend
    $expenseQuery = "
        SELECT DATE_FORMAT(expense_date, '%Y-%m') as month,
               COALESCE(SUM(amount), 0) as expenses
        FROM expenses 
        WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        AND status = 'approved'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
        GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
        ORDER BY month ASC
    ";
    $expenseTrend = $db->query($expenseQuery);
    
    // Prepare chart data
    $monthLabels = [];
    $revenueData = [];
    $expenseData = [];
    $revenueMap = [];
    $expenseMap = [];
    
    foreach ($revenueTrend as $row) {
        $revenueMap[$row['month']] = $row['revenue'];
    }
    foreach ($expenseTrend as $row) {
        $expenseMap[$row['month']] = $row['expenses'];
    }
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthLabels[] = date('M', strtotime($month . '-01'));
        $revenueData[] = $revenueMap[$month] ?? 0;
        $expenseData[] = $expenseMap[$month] ?? 0;
    }
    
    // Expense Breakdown by Category
    $expenseBreakdown = $db->query("
        SELECT category, COALESCE(SUM(amount), 0) as total
        FROM expenses 
        WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
        AND status = 'approved'" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . "
        GROUP BY category
        ORDER BY total DESC
        LIMIT 5
    ", [$currentMonth]);
    
    // Recent Transactions (combined invoices and expenses)
    $recentInvoices = $db->query("
        SELECT 
            'invoice' as type,
            invoice_number as reference,
            c.name as party_name,
            total_amount as amount,
            invoice_date as date,
            payment_status as status
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE " . ($orgIdPatch ? "i.organization_id = " . intval($orgIdPatch) : "1=1") . "
        ORDER BY invoice_date DESC
        LIMIT 5
    ");
    
    $recentExpenses = $db->query("
        SELECT 
            'expense' as type,
            expense_number as reference,
            vendor as party_name,
            amount,
            expense_date as date,
            status
        FROM expenses
        WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) : "1=1") . "
        ORDER BY expense_date DESC
        LIMIT 5
    ");
    
    // Customer Balances (Top 5 with highest outstanding)
    $customerBalances = $db->query("
        SELECT name, outstanding_balance 
        FROM customers 
        WHERE {$orgFilter} outstanding_balance > 0 
        ORDER BY outstanding_balance DESC 
        LIMIT 5
    ");
    
    // Supplier Balances  
    $supplierBalances = $db->query("
        SELECT name, outstanding_balance 
        FROM suppliers 
        WHERE {$orgFilter} outstanding_balance > 0 
        ORDER BY outstanding_balance DESC 
        LIMIT 5
    ");

} catch (Exception $e) {
    error_log("Accountant Dashboard error: " . $e->getMessage());
    $totalRevenueThisMonth = $totalExpensesThisMonth = $netProfitThisMonth = 0;
    $gstCollected = $outstandingReceivables = $overdueInvoicesCount = 0;
    $pendingExpenses = ['count' => 0, 'total' => 0];
    $monthLabels = $revenueData = $expenseData = [];
    $expenseBreakdown = $recentInvoices = $recentExpenses = [];
    $customerBalances = $supplierBalances = [];
}

// Helper function
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
    <title>Accountant Dashboard - Stocksathi</title>
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
        .accountant-header {
            background: linear-gradient(135deg, #3a63a5 0%, #4f82d5 50%, #4f82d5 100%);
            padding: 24px;
            border-radius: 10px;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(79, 130, 213, 0.3);
        }
        
        .accountant-header h2 {
            margin: 0 0 16px 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .finance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        
        .finance-box {
            background: rgba(255,255,255,0.15);
            padding: 16px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .finance-box .label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .finance-box .value {
            font-size: 28px;
            font-weight: bold;
        }
        
        .finance-box .sub-value {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
        }
        
        .positive { color: #4ade80; }
        .negative { color: #f87171; }
        .warning { color: #fbbf24; }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid var(--border-light);
        }
        
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        
        .stat-card .icon.purple { background: rgba(79, 130, 213, 0.1); color: #4f82d5; }
        .stat-card .icon.orange { background: rgba(249, 115, 22, 0.1); }
        .stat-card .icon.red { background: rgba(239, 68, 68, 0.1); }
        .stat-card .icon.blue { background: rgba(59, 130, 246, 0.1); }
        
        .transaction-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-light);
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .transaction-type {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .transaction-type.income {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .transaction-type.expense {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .balance-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .balance-fill {
            height: 100%;
            background: linear-gradient(90deg, #4f82d5, #93c5fd);
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .finance-grid, .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
                <div class="content-header">
                    <div>
                        <h1 class="content-title">💰 Accountant Dashboard</h1>
                        <p class="text-secondary">Financial overview for <?= date('F Y') ?></p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="btn btn-ghost" onclick="window.location.href='<?= BASE_PATH ?>/pages/reports.php'">
                            📊 View Reports
                        </button>
                        <button class="btn btn-primary" onclick="window.location.href='<?= BASE_PATH ?>/pages/expenses.php'">
                            ➕ Add Expense
                        </button>
                    </div>
                </div>

                <!-- Financial Overview Header -->
                <div class="accountant-header">
                    <h2>📈 Financial Overview - <?= date('F Y') ?></h2>
                    <div class="finance-grid">
                        <div class="finance-box">
                            <div class="label">Total Revenue</div>
                            <div class="value positive"><?= formatCurrency($totalRevenueThisMonth) ?></div>
                            <div class="sub-value">This month</div>
                        </div>
                        <div class="finance-box">
                            <div class="label">Total Expenses</div>
                            <div class="value negative"><?= formatCurrency($totalExpensesThisMonth) ?></div>
                            <div class="sub-value">This month</div>
                        </div>
                        <div class="finance-box">
                            <div class="label">Net Profit</div>
                            <div class="value <?= $netProfitThisMonth >= 0 ? 'positive' : 'negative' ?>">
                                <?= formatCurrency(abs($netProfitThisMonth)) ?>
                                <?= $netProfitThisMonth < 0 ? ' (Loss)' : '' ?>
                            </div>
                            <div class="sub-value">Net income</div>
                        </div>
                        <div class="finance-box">
                            <div class="label">GST Collected</div>
                            <div class="value"><?= formatCurrency($gstCollected) ?></div>
                            <div class="sub-value">Tax liability</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="icon purple">📋</div>
                        <div class="text-secondary" style="font-size: 14px;">Outstanding Receivables</div>
                        <div style="font-size: 24px; font-weight: bold; margin-top: 8px;"><?= formatCurrency($outstandingReceivables) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="icon orange">⚠️</div>
                        <div class="text-secondary" style="font-size: 14px;">Overdue Invoices</div>
                        <div style="font-size: 24px; font-weight: bold; margin-top: 8px; color: #f97316;"><?= $overdueInvoicesCount ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="icon red">🕐</div>
                        <div class="text-secondary" style="font-size: 14px;">Pending Expenses</div>
                        <div style="font-size: 24px; font-weight: bold; margin-top: 8px;"><?= $pendingExpenses['count'] ?></div>
                        <div class="text-secondary" style="font-size: 12px;">Worth <?= formatCurrency($pendingExpenses['total']) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="icon blue">📊</div>
                        <div class="text-secondary" style="font-size: 14px;">GST Tax Rate</div>
                        <div style="font-size: 24px; font-weight: bold; margin-top: 8px;">18%</div>
                        <div class="text-secondary" style="font-size: 12px;">Standard rate</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Revenue vs Expenses Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📈 Revenue vs Expenses (6 Months)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueExpenseChart" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Expense Breakdown -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Expense Breakdown - <?= date('F') ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expenseBreakdown)): ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    No expenses recorded this month
                                </div>
                            <?php else: ?>
                                <?php 
                                $totalExpense = array_sum(array_column($expenseBreakdown, 'total'));
                                foreach ($expenseBreakdown as $expense): 
                                    $percentage = $totalExpense > 0 ? ($expense['total'] / $totalExpense) * 100 : 0;
                                ?>
                                <div style="margin-bottom: 16px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                        <span><?= htmlspecialchars($expense['category']) ?></span>
                                        <span style="font-weight: 600;"><?= formatCurrency($expense['total']) ?></span>
                                    </div>
                                    <div class="balance-bar">
                                        <div class="balance-fill" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="grid grid-cols-3 gap-6">
                    <!-- Recent Transactions -->
                    <div class="card" style="grid-column: span 2;">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="card-title">📝 Recent Transactions</h3>
                            <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost btn-sm">View All</a>
                        </div>
                        <div class="card-body transaction-list">
                            <?php 
                            $allTransactions = array_merge($recentInvoices, $recentExpenses);
                            usort($allTransactions, function($a, $b) {
                                return strtotime($b['date']) - strtotime($a['date']);
                            });
                            $allTransactions = array_slice($allTransactions, 0, 8);
                            
                            if (empty($allTransactions)): ?>
                                <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                    No recent transactions
                                </div>
                            <?php else: ?>
                                <?php foreach ($allTransactions as $trans): ?>
                                <div class="transaction-item">
                                    <div class="transaction-info">
                                        <div class="transaction-type <?= $trans['type'] === 'invoice' ? 'income' : 'expense' ?>">
                                            <?= $trans['type'] === 'invoice' ? '💵' : '💸' ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500;"><?= htmlspecialchars($trans['reference']) ?></div>
                                            <div class="text-secondary" style="font-size: 12px;">
                                                <?= htmlspecialchars($trans['party_name'] ?? 'N/A') ?> · <?= date('d M', strtotime($trans['date'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: 600; color: <?= $trans['type'] === 'invoice' ? '#10b981' : '#ef4444' ?>">
                                            <?= $trans['type'] === 'invoice' ? '+' : '-' ?><?= formatCurrency($trans['amount']) ?>
                                        </div>
                                        <span class="badge badge-<?= $trans['status'] === 'paid' || $trans['status'] === 'approved' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($trans['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Customer Balances -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">👥 Top Outstanding Balances</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($customerBalances)): ?>
                                <div style="text-align: center; padding: 20px; color: var(--text-secondary);">
                                    No outstanding balances
                                </div>
                            <?php else: ?>
                                <?php foreach ($customerBalances as $customer): ?>
                                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-light);">
                                    <span><?= htmlspecialchars($customer['name']) ?></span>
                                    <span style="font-weight: 600; color: #ef4444;">
                                        <?= formatCurrency($customer['outstanding_balance']) ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <div style="margin-top: 16px;">
                                <a href="<?= BASE_PATH ?>/pages/customers.php" class="btn btn-ghost btn-sm" style="width: 100%;">
                                    View All Customers →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            


<script>
        function initAccountantChart() {
            if (typeof Chart === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                script.onload = initAccountantChart;
                document.head.appendChild(script);
                return;
            }
            function draw() {
            if (window.accountantChart instanceof Chart) window.accountantChart.destroy();
            const ctx = document.getElementById('revenueExpenseChart');
            if (ctx) {
                window.accountantChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($monthLabels) ?>,
                        datasets: [
                            {
                                label: 'Revenue',
                                data: <?= json_encode($revenueData) ?>,
                                backgroundColor: 'rgba(79, 130, 213, 0.7)',
                                borderColor: 'rgb(79, 130, 213)',
                                borderWidth: 1,
                                borderRadius: 4
                            },
                            {
                                label: 'Expenses',
                                data: <?= json_encode($expenseData) ?>,
                                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 1,
                                borderRadius: 4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let value = context.parsed.y;
                                        if (value >= 100000) return context.dataset.label + ': ₹' + (value/100000).toFixed(2) + 'L';
                                        if (value >= 1000) return context.dataset.label + ': ₹' + (value/1000).toFixed(2) + 'K';
                                        return context.dataset.label + ': ₹' + value.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 100000) return '₹' + (value/100000).toFixed(0) + 'L';
                                        if (value >= 1000) return '₹' + (value/1000).toFixed(0) + 'K';
                                        return '₹' + value;
                                    }
                                }
                            }
                        }
                    }
                });
                if (window.accountantChart && window.accountantChart.resize) window.accountantChart.resize();
            }
            }
            var raf = window.requestAnimationFrame || function(f){setTimeout(f,16);};
            raf(function(){ raf(draw); });
        }
        initAccountantChart();
    </script>
</main>
        </div>
    </div>

    
</body>
</html>
