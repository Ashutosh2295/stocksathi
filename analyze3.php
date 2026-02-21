<?php
$files = glob("pages/*.php");
foreach($files as $file) {
    if(is_dir($file)) continue;
    $content = file_get_contents($file);
    
    // Find SELECT queries that have ORDER BY or GROUP BY but miss an organization filter
    // Let's print out all SELECT queries that lack WHERE... OR if they have WHERE it doesn't contain orgFilter
    
    preg_match_all('/"SELECT\b((?!WHERE).)*?(ORDER BY|GROUP BY)/si', $content, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $match) {
            echo "Possible unprotected query in $file: \n" . substr($match, 0, 100) . "...\n\n";
        }
    }
}
echo "Analysis 3 done.\n";
