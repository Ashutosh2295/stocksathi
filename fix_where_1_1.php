<?php
$files = [
    'products.php' => 'p',
    'warehouses.php' => '',
    'stores.php' => '',
    'invoices.php' => 'i',
    'activity-logs.php' => 'al',
];

foreach ($files as $file => $alias) {
    if (!file_exists("pages/" . $file)) continue;
    
    $content = file_get_contents("pages/" . $file);
    
    $prefix = $alias ? $alias . "." : "";
    $replacement = '" . ($orgIdPatch ? " WHERE ' . $prefix . 'organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1")';
    
    $content = str_replace('WHERE {$orgFilter} 1=1"', $replacement, $content);
    
    // Wait, the original in products was: `          WHERE {$orgFilter} 1=1";` -> `          " . ($orgIdPatch...);`
    file_put_contents("pages/" . $file, $content);
    echo "Fixed ambiguous WHERE in $file using prefix '$prefix'\n";
}
