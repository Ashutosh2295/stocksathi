<?php
$files = glob("pages/*.php");
foreach ($files as $file) {
    if (is_dir($file)) continue;
    $content = file_get_contents($file);
    if (strpos($content, 'JOIN') !== false && strpos($content, '{$orgFilter}') !== false) {
        // Is it inside a query that has JOIN? Yes.
        echo "Check $file for ambiguous orgFilter\n";
    }
}
