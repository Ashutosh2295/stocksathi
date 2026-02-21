<?php
$files = [
    'c:/xampp_new/htdocs/stocksathi/pages/dashboards/sales-executive.php',
    'c:/xampp_new/htdocs/stocksathi/pages/dashboards/admin.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Dynamic Chart Loading Fix for PJAX
    $search = '(function init';
    // If we haven't already wrapped it
    if (strpos($content, "if (typeof Chart === 'undefined')") === false) {
        // We'll replace the IIFE definition and execution with a checking loader
        // Note: The specific function names are initSalesChart, initAdminCharts, etc.
        // It's easier to just do a string replacement on the start and end of the script block.
        
        // Find the last <script> block and replace its contents
        $scriptStart = strrpos($content, '<script>');
        $scriptEnd = strrpos($content, '</script>');
        
        if ($scriptStart !== false && $scriptEnd !== false) {
            $scriptContent = substr($content, $scriptStart + 8, $scriptEnd - ($scriptStart + 8));
            
            // Extract the function name from `(function functionName() {`
            if (preg_match('/\(function\s+([a-zA-Z0-9_]+)\s*\(\)\s*\{/', $scriptContent, $matches)) {
                $funcName = $matches[1];
                
                // Remove the IIFE wrapper
                $scriptContent = preg_replace('/^\s*\(\s*function\s+[a-zA-Z0-9_]+\s*\(\)\s*\{/ms', "function {$funcName}() {", $scriptContent, 1);
                
                // Remove the closing `})();`
                $scriptContent = preg_replace('/\}\)\(\);\s*$/', "}", trim($scriptContent), 1);
                
                // Add the loader
                $newScriptContent = $scriptContent . "\n\n        if (typeof Chart === 'undefined') {\n            const script = document.createElement('script');\n            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';\n            script.onload = {$funcName};\n            document.head.appendChild(script);\n        } else {\n            {$funcName}();\n        }";
                
                $content = substr_replace($content, $newScriptContent, $scriptStart + 8, $scriptEnd - ($scriptStart + 8));
                file_put_contents($file, $content);
                echo "Fixed $file\n";
            }
        }
    }
}
?>
