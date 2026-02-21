<?php
$files = glob("pages/*.php");
$issues = [];
foreach($files as $file) {
    if(is_dir($file)) continue;
    $content = file_get_contents($file);
    // Find any line containing WHERE and a JOIN syntax that looks suspicious
    if(stripos($content, 'JOIN') !== false) {
        preg_match_all('/(?:SELECT|UPDATE|DELETE)[^;]+WHERE[^;]+(?!organization_id)/is', $content, $matches);
        // We just want to execute a dry run of all queries in the application actually. 
        // But since we can't easily, let's just use regex to manually find 'WHERE {$orgFilter}' or similar.
    }
}
echo "Analysis done.\n";
