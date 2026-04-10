<?php
/**
 * Daily Cron Script for Email Summaries & Notifications
 * Run this daily at end-of-day (e.g., 11:30 PM). 
 */

// Since this might run from CLI, assume document root logic
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

try {
    $db = Database::getInstance();
    
    // 1. Gather Daily Sales
    $salesToday = $db->queryOne("SELECT COUNT(*) as count, SUM(total_amount) as total FROM invoices WHERE DATE(created_at) = CURDATE()");
    $salesCount = $salesToday['count'] ?? 0;
    $salesTotal = $salesToday['total'] ?? 0;
    $salesTotalFormatted = number_format($salesTotal, 2);

    // 2. Gather Top Selling Products Today
    $topProducts = $db->query("
        SELECT p.name, SUM(ii.quantity) as qty_sold, SUM(ii.subtotal) as total_sold
        FROM invoice_items ii
        JOIN invoices i ON ii.invoice_id = i.id
        JOIN products p ON ii.product_id = p.id
        WHERE DATE(i.created_at) = CURDATE()
        GROUP BY p.id, p.name
        ORDER BY qty_sold DESC
        LIMIT 5
    ");
    
    // 3. User Report
    $usersResult = $db->queryOne("
        SELECT 
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users
    ");
    $totalUsers = $usersResult['total_users'] ?? 0;
    $newUsers = $usersResult['new_users'] ?? 0;

    // 4. Attendance
    $attendanceResult = $db->queryOne("
        SELECT 
            (SELECT COUNT(*) FROM employees WHERE status IN ('active', 'on_leave')) as total_employees,
            (SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status != 'absent') as present_today
    ");
    $totalEmployees = $attendanceResult['total_employees'] ?? 0;
    $presentToday = $attendanceResult['present_today'] ?? 0;
    
    // 5. Gather Low Stock Alerts
    $lowStockProducts = $db->query("SELECT name, stock_quantity, min_stock_level FROM products WHERE stock_quantity <= min_stock_level AND status = 'active' LIMIT 10");
    
    // 6. Pending Leave Requests
    $pendingLeaves = $db->queryOne("SELECT COUNT(*) as cnt FROM leave_requests WHERE status = 'pending'");
    $leaveCount = $pendingLeaves['cnt'] ?? 0;
    
    $title = "Daily Final Summary - " . date("M d, Y");

    // Fetch Global Settings
    $settingsRaw = $db->query("SELECT `key`, `value` FROM settings");
    $settings = [];
    foreach ($settingsRaw as $row) {
        $settings[$row['key']] = $row['value'];
    }
    
    $companyName = $settings['company_name'] ?? 'StockSathi';
    $senderEmail = $settings['company_email'] ?? 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'stocksathi.com');
    // Using a reliable sender address domain if testing locally, use settings email as reply-to.
    $fromEmail = "noreply@" . ($_SERVER['HTTP_HOST'] ?? 'stocksathi.com');

    // Build Plain Text for Notifications
    $notifMessage = "Your StockSathi Daily Summary:\n";
    $notifMessage .= "🛒 Sales: $salesCount invoices totaling ₹" . $salesTotalFormatted . "\n";
    $notifMessage .= "👥 Users: $newUsers new signups today.\n";
    $notifMessage .= "👤 Attendance: $presentToday/$totalEmployees present.\n";
    if (count($lowStockProducts) > 0) {
        $notifMessage .= "⚠️ Low Stock: " . count($lowStockProducts) . " products need attention.\n";
    }

    // Build Beautiful HTML Email Body
    $currentYear = date("Y");
    
    // Low Stock Table
    $stockItemsHtml = '';
    if (count($lowStockProducts) > 0) {
        $stockItemsHtml .= "<div style='margin-bottom: 20px;'><h3 style='color: #ef4444; margin-bottom: 10px; font-size: 16px;'>⚠️ Low Stock Alerts</h3>
        <table width='100%' cellpadding='8' cellspacing='0' style='border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;'>
            <thead>
                <tr style='background: #f9fafb; text-align: left; border-bottom: 1px solid #e5e7eb; color: #374151; font-size: 14px;'>
                    <th>Product Name</th>
                    <th>Current Stock</th>
                    <th>Min Level</th>
                </tr>
            </thead>
            <tbody>";
        foreach ($lowStockProducts as $p) {
            $stockItemsHtml .= "<tr style='border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #4b5563;'>
                <td>" . htmlspecialchars($p['name']) . "</td>
                <td style='color: #ef4444; font-weight: bold;'>" . $p['stock_quantity'] . "</td>
                <td>" . $p['min_stock_level'] . "</td>
            </tr>";
        }
        $stockItemsHtml .= "</tbody></table></div>";
    }

    // Top Products Sold
    $topProductsHtml = '';
    if (count($topProducts) > 0) {
        $topProductsHtml .= "<div style='margin-bottom: 25px;'><h3 style='color: #111827; margin-bottom: 10px; font-size: 16px;'>🏆 Top Products Sold Today</h3>
        <table width='100%' cellpadding='8' cellspacing='0' style='border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;'>
            <thead>
                <tr style='background: #f9fafb; text-align: left; border-bottom: 1px solid #e5e7eb; color: #374151; font-size: 14px;'>
                    <th>Product Name</th>
                    <th>Qty Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>";
        foreach ($topProducts as $tp) {
            $topProductsHtml .= "<tr style='border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #4b5563;'>
                <td>" . htmlspecialchars($tp['name']) . "</td>
                <td style='color: #10b981; font-weight: bold;'>" . $tp['qty_sold'] . "</td>
                <td>₹" . number_format($tp['total_sold'], 2) . "</td>
            </tr>";
        }
        $topProductsHtml .= "</tbody></table></div>";
    } else {
         $topProductsHtml .= "<div style='margin-bottom: 25px;'><h3 style='color: #111827; margin-bottom: 10px; font-size: 16px;'>🏆 Top Products Sold Today</h3><p style='color: #6b7280; font-size: 14px; padding: 15px; background: #f8fafc; border-radius: 8px;'>No sales recorded today.</p></div>";
    }

    $hrAlertHtml = '';
    if ($leaveCount > 0) {
        $hrAlertHtml = "<div style='background: #fef3c7; border-left: 4px solid #f59e0b; color: #92400e; padding: 15px; border-radius: 0 8px 8px 0; font-size: 14px; margin-bottom: 20px;'>
            ⏳ <strong>HR Needs Attention:</strong> You have <strong>$leaveCount pending leave requests</strong> waiting for approval.
        </div>";
    }

    $emailHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; color: #1f2937; }
    .email-container { max-width: 650px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .email-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff; padding: 35px 25px; text-align: center; }
    .email-header h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; }
    .email-header p { margin: 10px 0 0 0; opacity: 0.9; font-size: 15px; }
    .email-body { padding: 35px; }
    .metrics-grid { display: table; width: 100%; border-spacing: 12px; margin-left: -12px; margin-right: -12px; margin-bottom: 25px; table-layout: fixed; }
    .metric-card { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px; text-align: center; }
    .metric-val { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
    .metric-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .email-footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    .btn { display: inline-block; padding: 14px 28px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; margin-top: 15px; font-family: inherit; }
</style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{$companyName}</h1>
            <p>Daily Operations Report &bull; {$title}</p>
        </div>
        
        <div class="email-body">
            <h2 style="font-size: 20px; margin-top: 0; margin-bottom: 15px; color: #111827;">Hello Team,</h2>
            <p style="font-size: 15px; line-height: 1.6; color: #475569; margin-bottom: 25px;">Here is the comprehensive summary of today's business operations across all modules.</p>
            
            <table class="metrics-grid">
                <tr>
                    <td class="metric-card">
                        <div class="metric-val" style="color: #3b82f6;">{$salesCount}</div>
                        <div class="metric-label">Invoices Created</div>
                    </td>
                    <td class="metric-card">
                        <div class="metric-val" style="color: #10b981;">₹{$salesTotalFormatted}</div>
                        <div class="metric-label">Total Revenue</div>
                    </td>
                </tr>
            </table>

            <table class="metrics-grid">
                <tr>
                    <td class="metric-card">
                        <div class="metric-val" style="color: #8b5cf6;">{$newUsers}</div>
                        <div class="metric-label">New Users Today</div>
                        <div style="font-size: 11px; margin-top: 4px; color: #94a3b8;">({$totalUsers} Total)</div>
                    </td>
                    <td class="metric-card">
                        <div class="metric-val" style="color: #f59e0b;">{$presentToday}/{$totalEmployees}</div>
                        <div class="metric-label">Present Employees</div>
                        <div style="font-size: 11px; margin-top: 4px; color: #94a3b8;">Attendance</div>
                    </td>
                </tr>
            </table>
            
            {$topProductsHtml}
            {$stockItemsHtml}
            {$hrAlertHtml}

            <div style="text-align: center; margin-top: 40px;">
                <a href="http://{$_SERVER['HTTP_HOST']}/stocksathi/index.php" class="btn">View Full Dashboard</a>
            </div>
        </div>
        
        <div class="email-footer">
            &copy; {$currentYear} {$companyName}. All rights reserved.<br>
            This is an automated system report. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
HTML;
    
    // Send to Admins
    $admins = $db->query("SELECT id, email FROM users WHERE role IN ('admin', 'super_admin') AND status = 'active'");
    
    $notificationType = ($salesCount == 0 && count($lowStockProducts) == 0 && $leaveCount == 0) ? 'info' : 'success';
    if(count($lowStockProducts) > 0) $notificationType = 'warning';

    foreach ($admins as $admin) {
        // Create In-App Notification (Plain text summary)
        $db->execute(
            "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)",
            [$admin['id'], $title, $notifMessage, $notificationType]
        );
        
        // Attempt to send email
        $to = $admin['email'] ?? $senderEmail;
        $subject = "{$companyName}: " . $title;
        
        // Headers for HTML Email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$companyName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$senderEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Using @ block to prevent PHP warnings on local setups without mail server
        @mail($to, $subject, $emailHtml, $headers);
    }
    
    echo "Summary generated and emails dispatched to " . count($admins) . " admin(s).\n";

} catch (Exception $e) {
    error_log("Error running cron: " . $e->getMessage());
    echo "Error running cron: " . $e->getMessage() . "\n";
}

