<?php
/**
 * Master Owner Dashboard
 * Standalone UI. Accessible only via direct URL.
 * Professional Light Theme
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

// Verify Owner Level Access (Ensure only super_admin can see this)
if (Session::getUserRole() !== 'super_admin') {
    die("<div style='background:#f8fafc;color:#0f172a;height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;'><h2>⛔ Access Denied</h2></div>");
}

$db = Database::getInstance();

// --- HANDLE POST ACTIONS (User Fixes) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = intval($_POST['user_id'] ?? 0);
    
    try {
        if ($action === 'activate' && $targetUserId > 0) {
            $db->execute("UPDATE users SET status = 'active' WHERE id = ?", [$targetUserId]);
            Session::setFlash("User ID $targetUserId activated successfully.", 'success');
        } elseif ($action === 'inactivate' && $targetUserId > 0) {
            $db->execute("UPDATE users SET status = 'inactive' WHERE id = ?", [$targetUserId]);
            Session::setFlash("User ID $targetUserId deactivated.", 'warning');
        } elseif ($action === 'reset_pass' && $targetUserId > 0) {
            $hashedPass = password_hash('Stocksathi@123', PASSWORD_DEFAULT);
            $db->execute("UPDATE users SET password = ? WHERE id = ?", [$hashedPass, $targetUserId]);
            Session::setFlash("User ID $targetUserId password reset to 'Stocksathi@123'.", 'success');
        }
    } catch(Exception $e) {
        Session::setFlash("Error: " . $e->getMessage(), 'error');
    }
    header("Location: owner.php");
    exit;
}

// --- OVERALL SYSTEM STATISTICS ---
try {
    $counts = [];
    $counts['organizations'] = $db->queryOne("SELECT COUNT(*) as c FROM organizations")['c'] ?? 0;
    $counts['users_total'] = $db->queryOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0;
    $counts['invoices'] = $db->queryOne("SELECT COUNT(*) as c FROM invoices")['c'] ?? 0;
    $counts['invoice_value'] = $db->queryOne("SELECT COALESCE(SUM(total_amount), 0) as sm FROM invoices WHERE status != 'cancelled'")['sm'] ?? 0;
    
    // Charts Data
    // 1. Sales Trend
    $salesDataSQL = $db->query("SELECT DATE(invoice_date) as d, SUM(total_amount) as t FROM invoices GROUP BY DATE(invoice_date) ORDER BY d DESC LIMIT 14");
    $salesData = []; foreach($salesDataSQL as $row) { $salesData[$row['d']] = $row['t']; }
    
    // 2. Expenses Trend
    $expDataSQL = $db->query("SELECT DATE(expense_date) as d, SUM(amount) as t FROM expenses WHERE status IN ('approved', 'paid') GROUP BY DATE(expense_date) ORDER BY d DESC LIMIT 14");
    $expData = []; foreach($expDataSQL as $row) { $expData[$row['d']] = $row['t']; }
    
    // 3. User Roles Dist
    $rolesSQL = $db->query("SELECT role, COUNT(*) as c FROM users GROUP BY role");
    
    // 4. Detailed Users List (All Users for granular control)
    $allUsers = $db->query("
        SELECT u.id, u.username, u.email, u.full_name, u.role, u.status, u.phone, u.created_at, o.name as org_name 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.id 
        ORDER BY u.created_at DESC
    ");

    // 5. Organizations List with their user count
    $orgDetails = $db->query("
        SELECT o.id, o.name, o.email, o.phone, o.status, o.created_at, COUNT(u.id) as user_count
        FROM organizations o
        LEFT JOIN users u ON o.id = u.organization_id
        GROUP BY o.id, o.name, o.email, o.phone, o.status, o.created_at
        ORDER BY o.created_at DESC
    ");
    
} catch(Exception $e) {
    die("Data aggregation error: " . $e->getMessage());
}

$flash = Session::getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Console - StockSathi</title>
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        
        /* Top Navigation Bar */
        .master-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .master-logo {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .exit-btn {
            background: transparent;
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 16px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .exit-btn:hover { background: #f8fafc; color: var(--text-main); }
        
        /* Container */
        .console-container { padding: 40px; max-width: 1600px; margin: 0 auto; }
        
        /* Flash message */
        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        
        /* Quick Metrics row */
        .metric-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        
        .m-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .m-label { font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600; }
        .m-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .m-icon.blue { background: #eff6ff; color: #3b82f6; }
        .m-icon.green { background: #ecfdf5; color: #10b981; }
        .m-icon.purple { background: #faf5ff; color: #a855f7; }
        .m-icon.orange { background: #fff7ed; color: #f97316; }
        
        .m-value { font-size: 32px; font-weight: 700; color: var(--text-main); }
        .m-value.money { color: var(--success); }
        
        /* Grid Layouts */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
        @media (max-width: 1024px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
        
        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .panel-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px; }
        .panel-title { font-size: 16px; font-weight: 600; color: var(--text-main); margin: 0; }
        
        /* Table Styles */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 12px 16px; border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 600; background: #f8fafc; }
        td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        tr:hover td { background: #f8fafc; }
        tr:last-child td { border-bottom: none; }
        
        .user-meta { display: flex; flex-direction: column; gap: 4px; }
        .user-name { font-weight: 600; color: var(--text-main); }
        .user-email { font-size: 12px; color: var(--text-muted); }
        
        /* Badges & Buttons */
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        
        .btn-group { display: flex; gap: 6px; }
        .btn { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid; transition: all 0.2s; white-space: nowrap; }
        .btn-outline-success { background: transparent; border-color: var(--success); color: var(--success); }
        .btn-outline-success:hover { background: var(--success); color: white; }
        .btn-outline-danger { background: transparent; border-color: var(--danger); color: var(--danger); }
        .btn-outline-danger:hover { background: var(--danger); color: white; }
        .btn-outline-warning { background: transparent; border-color: var(--warning); color: #b45309; }
        .btn-outline-warning:hover { background: var(--warning); color: white; border-color: var(--warning); }
    </style>
</head>
<body>
    
    <nav class="master-nav">
        <div class="master-logo">
            <img src="<?= BASE_PATH ?>/assets/images/logo.png" alt="Stocksathi" style="width: 36px; height: 36px; object-fit: contain; padding: 4px; background: white; border-radius: 8px;">
            Stocksathi Owner Console
        </div>
        <div>
            <a href="<?= BASE_PATH ?>/index.php" class="exit-btn">Return to Workspace</a>
        </div>
    </nav>
    
    <div class="console-container">
        
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'warning' ? 'warning' : 'danger') ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Quick Metrics -->
        <div class="metric-row">
            <div class="metric-card">
                <div class="m-header">
                    <div class="m-label">Total Revenue</div>
                    <div class="m-icon green">₹</div>
                </div>
                <div class="m-value money">₹<?= number_format($counts['invoice_value'], 2) ?></div>
            </div>
            <div class="metric-card">
                <div class="m-header">
                    <div class="m-label">Total Invoices</div>
                    <div class="m-icon blue">🧾</div>
                </div>
                <div class="m-value"><?= number_format($counts['invoices']) ?></div>
            </div>
            <div class="metric-card">
                <div class="m-header">
                    <div class="m-label">All System Users</div>
                    <div class="m-icon purple">👥</div>
                </div>
                <div class="m-value"><?= number_format($counts['users_total']) ?></div>
            </div>
            <div class="metric-card">
                <div class="m-header">
                    <div class="m-label">Organizations Registered</div>
                    <div class="m-icon orange">🏢</div>
                </div>
                <div class="m-value"><?= number_format($counts['organizations']) ?></div>
            </div>
        </div>

        <!-- Upper Charts -->
        <div class="grid-2">
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">Financial Trend (Last 14 Days)</h3>
                </div>
                <div id="financialChart" style="height: 300px;"></div>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">User Roles Distribution</h3>
                </div>
                <div id="roleChart" style="height: 300px; display:flex; align-items:center; justify-content:center;"></div>
            </div>
        </div>

        <!-- Organizations Table -->
        <div class="panel" style="margin-bottom: 30px;">
            <div class="panel-header">
                <h3 class="panel-title">Registered Organizations</h3>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Organization Name</th>
                            <th>Contact Info</th>
                            <th>Total Users</th>
                            <th>Joined Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orgDetails as $org): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars($org['name']) ?></td>
                                <td>
                                    <div class="user-meta">
                                        <span class="user-email">✉️ <?= htmlspecialchars($org['email'] ?: 'N/A') ?></span>
                                        <span class="user-email">📞 <?= htmlspecialchars($org['phone'] ?: 'N/A') ?></span>
                                    </div>
                                </td>
                                <td><span style="font-weight: 600; color: var(--primary);"><?= $org['user_count'] ?></span> users</td>
                                <td><?= date('M d, Y', strtotime($org['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $org['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars($org['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Comprehensive User Management Table -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Comprehensive User Directory & Management</h3>
            </div>
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>User Profile</th>
                            <th>Organization</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th align="right">Management Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($allUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="user-meta">
                                        <span class="user-name"><?= htmlspecialchars($u['username']) ?></span>
                                        <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                                        <?php if($u['phone']): ?>
                                            <span class="user-email" style="font-size: 11px;">📞 <?= htmlspecialchars($u['phone']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="color: var(--text-muted); font-weight: 500;">
                                    <?= htmlspecialchars($u['org_name'] ?: 'System Account') ?>
                                </td>
                                <td><span class="badge badge-gray"><?= htmlspecialchars($u['role']) ?></span></td>
                                <td>
                                    <span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars($u['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px; color: var(--text-muted);"><?= date('M d', strtotime($u['created_at'])) ?></td>
                                <td align="right">
                                    <form method="POST" style="display: flex; justify-content: flex-end; gap: 8px;">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if($u['status'] !== 'active'): ?>
                                            <button type="submit" name="action" value="activate" class="btn btn-outline-success" title="Activate Account">Activate</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="inactivate" class="btn btn-outline-danger" title="Suspend Account" onclick="return confirm('Suspend user <?= htmlspecialchars($u['username']) ?>?');">Suspend</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="reset_pass" class="btn btn-outline-warning" title="Reset password to Stocksathi@123" onclick="return confirm('Reset password for <?= htmlspecialchars($u['username']) ?> to default?');">Reset Pass</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <!-- Analytics Init -->
    <?php
    // Prepare Data for JS Charts
    // 1. Financial Chart array builder (using active dates so the chart doesn't look empty)
    $activeDates = array_unique(array_merge(array_keys($salesData), array_keys($expData)));
    rsort($activeDates);
    $activeDates = array_slice($activeDates, 0, 14); // Take latest 14 active dates
    sort($activeDates); // Chronological order
    
    $finLabels = [];
    $salesSeries = [];
    $expSeries = [];
    
    // If no data globally, fall back to today to prevent empty chart error
    if (empty($activeDates)) {
        $activeDates = [date('Y-m-d')];
    }

    foreach ($activeDates as $d) {
        $finLabels[] = date('d M', strtotime($d));
        $salesSeries[] = (float)($salesData[$d] ?? 0);
        $expSeries[] = (float)($expData[$d] ?? 0);
    }
    
    // 2. Roles
    $rLabels = []; $rValues = [];
    foreach($rolesSQL as $r) { $rLabels[] = strtoupper($r['role']); $rValues[] = (int)$r['c']; }
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const commonOptions = {
            chart: { background: 'transparent', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4']
        };

        // 1. Financial Trajectory (Area Chart)
        var finOptions = {
            ...commonOptions,
            series: [{ name: 'System Revenue', data: <?= json_encode($salesSeries) ?> },
                     { name: 'System Expenses', data: <?= json_encode($expSeries) ?> }],
            chart: { type: 'area', height: 300, toolbar: { show: false } },
            colors: ['#10b981', '#ef4444'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { 
                categories: <?= json_encode($finLabels) ?>,
                axisBorder: { show: false }, 
                axisTicks: { show: false },
                labels: { style: { colors: '#64748b' } }
            },
            yaxis: { 
                labels: { 
                    style: { colors: '#64748b' },
                    formatter: (value) => { return value > 1000 ? '₹' + (value/1000).toFixed(1) + 'k' : '₹' + value; }
                } 
            },
            grid: { borderColor: "#f1f5f9", strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right' }
        };
        new ApexCharts(document.querySelector("#financialChart"), finOptions).render();

        // 2. Roles Donut
        var roleOptions = {
            ...commonOptions,
            series: <?= json_encode($rValues) ?>,
            labels: <?= json_encode($rLabels) ?>,
            chart: { type: 'donut', height: 300 },
            plotOptions: {
                pie: { donut: { size: '65%', 
                    labels: { show: true, name: { color: '#64748b' }, value: { color: '#0f172a', fontSize: '20px', fontWeight: 600 },
                    total: { show: true, color: '#64748b', label: 'TOTAL USERS' } } 
                }}
            },
            dataLabels: { enabled: false },
            legend: { position: 'right' },
            stroke: { width: 0 }
        };
        new ApexCharts(document.querySelector("#roleChart"), roleOptions).render();

    });
    </script>
</body>
</html>
