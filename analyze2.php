<?php
$files = glob("pages/*.php");
foreach($files as $file) {
    if(is_dir($file)) continue;
    $content = file_get_contents($file);
    
    // Simple checks for common list queries that lack organization filter
    preg_match_all('/"SELECT [^"]+ FROM (expenses|roles|departments|permissions|promotions|stock_adjustments|sales_returns)(?:\s+\w+)?\s*(?:LEFT JOIN[^"]+)*\s*(ORDER|GROUP)/i', $content, $matches);
    
    if (!empty($matches[0])) {
        echo "Found potential unprotected SELECT in $file:\n";
        foreach ($matches[0] as $match) {
            echo "  $match\n";
        }
    }
}
echo "Analysis 2 done.\n";
