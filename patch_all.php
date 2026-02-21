<?php
require '_includes/config.php';
require '_includes/database.php';

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('pages/'));

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;
    
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    
    // Check if already patched
    if (strpos($content, '$orgFilter =') !== false) {
        continue;
    }
    
    // We only patch files that have Session::getUserId() or similar auth checks
    if (strpos($content, 'Session::getUserId()') !== false || strpos($content, 'Session::getUserRole()') !== false || strpos($content, 'Database::getInstance()') !== false) {
        
        // Find a good insertion point: after Database::getInstance() or $userId = Session::getUserId();
        $target = '$db = Database::getInstance();';
        if (strpos($content, $target) !== false) {
            $content = str_replace(
                $target,
                $target . "\n" .
                '$orgIdPatch = Session::getOrganizationId();' . "\n" .
                '$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";' . "\n" .
                '$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";',
                $content
            );
        } else {
            // Try another target
            $target = '$userId = Session::getUserId();';
            if (strpos($content, $target) !== false) {
                $content = str_replace(
                    $target,
                    $target . "\n" .
                    '$orgIdPatch = Session::getOrganizationId();' . "\n" .
                    '$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";' . "\n" .
                    '$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";',
                    $content
                );
            } else {
                continue; // Skip file if we can't find a safe place to inject variables
            }
        }
        
        // Patch standard queries (WHERE clause exists)
        $content = preg_replace('/WHERE \(/', 'WHERE {$orgFilter} (', $content);
        $content = preg_replace('/WHERE status =/', 'WHERE {$orgFilter} status =', $content);
        $content = preg_replace('/WHERE type =/', 'WHERE {$orgFilter} type =', $content);
        $content = preg_replace('/WHERE DATE\(/', 'WHERE {$orgFilter} DATE(', $content);
        $content = preg_replace('/WHERE MONTH\(/', 'WHERE {$orgFilter} MONTH(', $content);
        
        // Patch queries where there is NO where clause (FROM table_name ORDER BY)
        // Note: we can't do this with simple regex easily because there are too many table names.
        $tables = ['products', 'categories', 'customers', 'suppliers', 'invoices', 'warehouses', 'stores', 'brands', 'employees', 'departments', 'expenses', 'promotions', 'sales_returns', 'stock_logs'];
        
        foreach ($tables as $t) {
            // Example: FROM products ORDER BY
            $content = preg_replace('/FROM '.$t.' ORDER BY/', 'FROM '.$t.' {$orgWhere} ORDER BY', $content);
            $content = preg_replace('/FROM '.$t.' GROUP BY/', 'FROM '.$t.' {$orgWhere} GROUP BY', $content);
            $content = preg_replace('/FROM '.$t.' LIMIT/', 'FROM '.$t.' {$orgWhere} LIMIT', $content);
            $content = preg_replace('/FROM '.$t.'\"/', 'FROM '.$t.' {$orgWhere}\"', $content);
            $content = preg_replace('/FROM '.$t.'\'/', 'FROM '.$t.' {$orgWhere}\'', $content);
        }
        
        // Alias specific replaces (common in JOINs)
        $content = preg_replace('/WHERE (i|p|c|s|u|al|e|w|st)\.status/', 'WHERE {$orgFilter} $1.status', $content);
        $content = str_replace('WHERE p.category_id', 'WHERE {$orgFilter} p.category_id', $content);

        file_put_contents($filePath, $content);
        echo "Patched: $filePath\n";
    }
}
echo "All queries globally patched!\n";
