<?php
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('pages/'));

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    if ($file->getExtension() !== 'php') continue;
    
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    
    // We only patch files that have the $orgFilter defined by our last patch
    if (strpos($content, '$orgFilter =') !== false) {
        
        $originalContent = $content;
        
        // Comprehensive string replacements for WHERE clauses
        $replacements = [
            'WHERE 1=1' => 'WHERE {$orgFilter} 1=1',
            'WHERE id =' => 'WHERE {$orgFilter} id =',
            'WHERE id =' => 'WHERE {$orgFilter} id =',
            'WHERE p.id =' => 'WHERE {$orgFilter} p.id =',
            'WHERE invoice_number =' => 'WHERE {$orgFilter} invoice_number =',
            'WHERE product_id =' => 'WHERE {$orgFilter} product_id =',
            'WHERE ii.product_id =' => 'WHERE {$orgFilter} ii.product_id =',
            'WHERE category_id =' => 'WHERE {$orgFilter} category_id =',
            'WHERE brand_id =' => 'WHERE {$orgFilter} brand_id =',
            'WHERE role IN' => 'WHERE {$orgFilter} role IN',
            'WHERE role =' => 'WHERE {$orgFilter} role =',
            'WHERE type =' => 'WHERE {$orgFilter} type =',
            'WHERE al.id =' => 'WHERE {$orgFilter} al.id =',
            'WHERE i.id =' => 'WHERE {$orgFilter} i.id =',
            'WHERE p.id =' => 'WHERE {$orgFilter} p.id =',
            'WHERE c.id =' => 'WHERE {$orgFilter} c.id =',
            'WHERE b.id =' => 'WHERE {$orgFilter} b.id =',
            'WHERE u.id =' => 'WHERE {$orgFilter} u.id =',
            'WHERE ii.invoice_id =' => 'WHERE {$orgFilter} ii.invoice_id =',
            'WHERE e.id =' => 'WHERE {$orgFilter} e.id =',
            'WHERE email =' => 'WHERE {$orgFilter} email ='
        ];
        
        foreach ($replacements as $search => $replace) {
            // we only replace if it doesn't already have $orgFilter preceding it
            // (preg_replace is safer to avoid double patching)
            $searchRegex = '/' . preg_quote($search, '/') . '/';
            $content = preg_replace_callback($searchRegex, function($matches) use ($replace, $search) {
                return $replace;
            }, $content);
        }
        
        // Clean up any double {$orgFilter} or {$orgWhere}
        $content = str_replace('WHERE {$orgFilter} {$orgFilter}', 'WHERE {$orgFilter}', $content);
        $content = str_replace('WHERE {$orgFilter} {$orgWhere}', 'WHERE {$orgFilter}', $content);
        $content = str_replace('WHERE {$orgWhere} {$orgFilter}', 'WHERE {$orgFilter}', $content);
        
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "Deep Patched: $filePath\n";
        }
    }
}
echo "All modules deeply patched!\n";
