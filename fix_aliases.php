<?php
$files = [
    'products.php' => 'p',
    'categories.php' => '', // No alias in main query
    'brands.php' => '', 
    'stores.php' => '',
    'warehouses.php' => '',
    'suppliers.php' => '',
    'customers.php' => '',
    'expenses.php' => '',
    'invoices.php' => 'i',
    'quotations.php' => 'q',
    'sales-returns.php' => 'sr',
    'stock-logs.php' => 'sl',
    'stock-adjustments.php' => 'sa',
    'promotions.php' => 'p', // Might not exist
    'activity-logs.php' => 'al',
    'departments.php' => '',
    'employees.php' => 'e',
    'reports.php' => '',
];

foreach ($files as $file => $alias) {
    if (!file_exists("pages/" . $file)) continue;
    
    $content = file_get_contents("pages/" . $file);
    
    // Fix $orgFilter definition to use alias
    $prefix = $alias ? $alias . "." : "";
    $newOrgFilter = '$orgFilter = $orgIdPatch ? " ' . $prefix . 'organization_id = " . intval($orgIdPatch) . " AND " : "";';
    $newOrgWhere = '$orgWhere = $orgIdPatch ? " WHERE ' . $prefix . 'organization_id = " . intval($orgIdPatch) . " " : "";';
    
    // Fix the dynamic definitions
    $content = preg_replace('/\$orgFilter = \$orgIdPatch \? " organization_id = " \. intval\(\$orgIdPatch\) \. " AND " : "";/', $newOrgFilter, $content);
    $content = preg_replace('/\$orgWhere = \$orgIdPatch \? " WHERE organization_id = " \. intval\(\$orgIdPatch\) \. " " : "";/', $newOrgWhere, $content);
    
    file_put_contents("pages/" . $file, $content);
    echo "Fixed alias in $file using prefix '$prefix'\n";
}
