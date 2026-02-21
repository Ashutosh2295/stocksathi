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
    
    // 2. Gather Low Stock Alerts
    $lowStockProducts = $db->query("SELECT name, stock_quantity, min_stock_level FROM products WHERE stock_quantity <= min_stock_level AND status = 'active' LIMIT 10");
    
    // 3. Pending Leave Requests
    $pendingLeaves = $db->queryOne("SELECT COUNT(*) as cnt FROM leave_requests WHERE status = 'pending'");
    $leaveCount = $pendingLeaves['cnt'] ?? 0;
    
    // Build Email Body & Notification Message
    $message = "Here is your StockSathi Daily Summary:\n\n";
    $message .= "🛒 Sales Today: $salesCount invoices totaling ₹" . number_format($salesTotal, 2) . "\n";
    
    if (count($lowStockProducts) > 0) {
        $message .= "\n⚠️ Low Stock Alerts:\n";
        foreach ($lowStockProducts as $p) {
            $message .= "- " . $p['name'] . " (In Stock: " . $p['stock_quantity'] . ", Min: " . $p['min_stock_level'] . ")\n";
        }
    } else {
        $message .= "\n✅ Stock Levels: All products are above minimum required levels.\n";
    }
    
    if ($leaveCount > 0) {
        $message .= "\n⏳ HR Alert: You have $leaveCount pending leave requests waiting for approval.\n";
    }
    
    $title = "Daily Summary & Important Alerts - " . date("M d, Y");
    
    // Send to Admins
    $admins = $db->query("SELECT id, email FROM users WHERE role IN ('admin', 'super_admin')");
    
    foreach ($admins as $admin) {
        // Create In-App Notification
        $db->execute(
            "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')",
            [$admin['id'], $title, $message]
        );
        
        // Attempt to send email
        $to = $admin['email'] ?? 'admin@stocksathi.com';
        $subject = "StockSathi: " . $title;
        $headers = "From: noreply@stocksathi.local\r\n";
        $headers .= "Reply-To: noreply@stocksathi.local\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
        
        // Using @ block to prevent PHP warnings on local XAMPP without mail setup
        @mail($to, $subject, $message, $headers);
    }
    
    echo "Summary generated and emails dispatched to " . count($admins) . " admin(s).\n";

} catch (Exception $e) {
    echo "Error running cron: " . $e->getMessage() . "\n";
}
