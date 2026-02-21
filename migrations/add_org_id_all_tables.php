<?php
/**
 * Migration: Add organization_id to ALL tables that need it
 * Run this once to ensure every table has multi-tenancy support
 */

require_once __DIR__ . '/../_includes/database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$tables_needing_org_id = [
    'stock_logs',
    'departments',
    'attendance',
    'leave_requests',
    'activity_logs',
    'notifications',
    'sales_returns',
    'promotions',
    'quotations',
    'expenses',
    'employees',
    'customers',
    'suppliers',
    'brands',
    'categories',
    'stores',
    'warehouses',
    'users',
    'invoices',
    'products',
    'quotation_items',
    'sales_return_items',
];

$results = [];

foreach ($tables_needing_org_id as $table) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE 'organization_id'");
        if ($check->rowCount() === 0) {
            $conn->exec("ALTER TABLE `{$table}` ADD COLUMN `organization_id` INT(11) DEFAULT NULL AFTER `id`");
            $conn->exec("ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_org_id` (`organization_id`)");
            $results[] = "ADDED organization_id to `{$table}`";
        } else {
            $results[] = "OK - `{$table}` already has organization_id";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "doesn't exist") !== false || strpos($e->getMessage(), '1146') !== false) {
            $results[] = "SKIP - Table `{$table}` does not exist";
        } else {
            $results[] = "ERROR on `{$table}`: " . $e->getMessage();
        }
    }
}

// Backfill organization_id from related records where possible
$backfill_queries = [
    "UPDATE stock_logs sl INNER JOIN products p ON sl.product_id = p.id SET sl.organization_id = p.organization_id WHERE sl.organization_id IS NULL AND p.organization_id IS NOT NULL",
    "UPDATE stock_logs sl INNER JOIN users u ON sl.created_by = u.id SET sl.organization_id = u.organization_id WHERE sl.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE attendance a INNER JOIN employees e ON a.employee_id = e.id INNER JOIN users u ON e.user_id = u.id SET a.organization_id = u.organization_id WHERE a.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE leave_requests lr INNER JOIN employees e ON lr.employee_id = e.id INNER JOIN users u ON e.user_id = u.id SET lr.organization_id = u.organization_id WHERE lr.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE activity_logs al INNER JOIN users u ON al.user_id = u.id SET al.organization_id = u.organization_id WHERE al.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE notifications n INNER JOIN users u ON n.user_id = u.id SET n.organization_id = u.organization_id WHERE n.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE departments d INNER JOIN employees e ON d.id = e.department_id INNER JOIN users u ON e.user_id = u.id SET d.organization_id = u.organization_id WHERE d.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE employees e INNER JOIN users u ON e.user_id = u.id SET e.organization_id = u.organization_id WHERE e.organization_id IS NULL AND u.organization_id IS NOT NULL",
    "UPDATE sales_returns sr INNER JOIN invoices i ON sr.invoice_id = i.id SET sr.organization_id = i.organization_id WHERE sr.organization_id IS NULL AND i.organization_id IS NOT NULL",
    "UPDATE quotations q INNER JOIN customers c ON q.customer_id = c.id SET q.organization_id = c.organization_id WHERE q.organization_id IS NULL AND c.organization_id IS NOT NULL",
];

foreach ($backfill_queries as $sql) {
    try {
        $affected = $conn->exec($sql);
        if ($affected > 0) {
            $results[] = "BACKFILLED {$affected} rows: " . substr($sql, 0, 80) . "...";
        }
    } catch (PDOException $e) {
        $results[] = "BACKFILL SKIP: " . $e->getMessage();
    }
}

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Migration: Add organization_id to all tables</h2><pre>";
foreach ($results as $r) {
    echo htmlspecialchars($r) . "\n";
}
echo "</pre><p><strong>Done!</strong> <a href='/stocksathi/index.php'>Back to dashboard</a></p>";
