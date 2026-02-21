<?php
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
    
    // SQL Pattern Replacements
    $content = str_replace("WHERE (payment_status = 'paid' OR payment_status = 'partial')", 'WHERE {$orgFilter} (payment_status = \'paid\' OR payment_status = \'partial\')', $content);
    $content = str_replace('WHERE status =', 'WHERE {$orgFilter} status =', $content);
    $content = str_replace('WHERE MONTH', 'WHERE {$orgFilter} MONTH', $content);
    $content = str_replace('WHERE YEARWEEK(', 'WHERE {$orgFilter} YEARWEEK(', $content);
    $content = str_replace('WHERE DATE(', 'WHERE {$orgFilter} DATE(', $content);
    $content = str_replace('WHERE stock_quantity', 'WHERE {$orgFilter} stock_quantity', $content);
    $content = str_replace("FROM products\"['total']", "FROM products {\$orgWhere}\"['total']", $content);
    $content = str_replace("FROM products\"['count']", "FROM products {\$orgWhere}\"['count']", $content);
    $content = str_replace("WHERE i.status !=", "WHERE {\$orgFilter} i.status !=", $content);
    $content = str_replace("WHERE u.role IN", "WHERE {\$orgFilter} u.role IN", $content);
    
    // Fix any double filters created by accident
    $content = str_replace('WHERE {$orgFilter} {$orgFilter}', 'WHERE {$orgFilter}', $content);

    file_put_contents($file, $content);
    echo "Successfully patched $file\n";
}
echo "Done!\n";
