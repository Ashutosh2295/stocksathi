<?php
require '_includes/config.php';
require '_includes/database.php';

$files = [
    'pages/dashboards/admin.php',
    'pages/dashboards/general.php',
    'pages/dashboards/sales.php',
    'pages/dashboards/super-admin.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Check if already modified
    if (strpos($content, '$orgFilter =') !== false) {
        echo "Skipping $file, already patched.\n";
        continue;
    }
    
    // Add org filter variable initialization
    // Specifically target the admin.php, sales.php, general.php which define $userId = Session::getUserId();
    if (strpos($content, '$userId = Session::getUserId();') !== false) {
        $content = str_replace(
            '$userId = Session::getUserId();',
            '$userId = Session::getUserId();' . "\n" .
            '$orgId = Session::getOrganizationId();' . "\n" .
            '$orgFilter = $orgId ? "organization_id = " . intval($orgId) . " AND " : "";' . "\n" .
            '$orgWhere = $orgId ? "WHERE organization_id = " . intval($orgId) . " " : "";',
            $content
        );
    } else {
        echo "Warning: No \$userId = Session::getUserId(); found in $file. Skipping org variable injection.\n";
        continue;
    }
    
    // Now replace the WHERE clauses in SQL.
    // Instead of complex preg_replace that might break, we use a simple replacement
    // for standard patterns found in these exact files.
    
    // 1. SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE (
    $content = str_replace('WHERE (payment_status', 'WHERE {$orgFilter} (payment_status', $content);
    $content = str_replace("WHERE (payment_status = 'paid' OR payment_status = 'partial') AND status != 'cancelled'", "WHERE {\$orgFilter} (payment_status = 'paid' OR payment_status = 'partial') AND status != 'cancelled'", $content);
    
    // 2. WHERE status = 
    $content = str_replace('WHERE status =', 'WHERE {$orgFilter} status =', $content);
    
    // 3. WHERE MONTH
    $content = str_replace('WHERE MONTH', 'WHERE {$orgFilter} MONTH', $content);
    
    // 4. FROM products WHERE
    $content = str_replace('WHERE stock_quantity', 'WHERE {$orgFilter} stock_quantity', $content);
    
    // 5. WHERE DATE
    $content = str_replace('WHERE DATE(', 'WHERE {$orgFilter} DATE(', $content);
    
    // 6. WHERE YEARWEEK
    $content = str_replace('WHERE YEARWEEK(', 'WHERE {$orgFilter} YEARWEEK(', $content);
    
    // 7. FROM products (no where clause)
    $content = str_replace("FROM products\"['total']", "FROM products {\$orgWhere}\"['total']", $content);
    
    // 8. Top Products Query
    $content = str_replace("WHERE i.status !=", "WHERE {\$orgFilter} i.status !=", $content);
    
    // 9. Top Sales Executives
    $content = str_replace("WHERE u.role IN", "WHERE {\$orgFilter} u.role IN", $content);
    
    // 10. FROM invoices i WHERE
    $content = str_replace("WHERE i.status", "WHERE {\$orgFilter} i.status", $content);
    
    // 11. Activity logs
    if (strpos($file, 'admin.php') !== false) {
        $content = str_replace(
            "FROM activity_logs al\n            LEFT JOIN",
            "FROM activity_logs al\n            LEFT JOIN users u ON al.user_id = u.id\n            WHERE {\$orgFilter} 1=1\n            ",
            $content
        );
    }
    
    file_put_contents($file, $content);
    echo "Successfully patched $file\n";
}
echo "Done!\n";
