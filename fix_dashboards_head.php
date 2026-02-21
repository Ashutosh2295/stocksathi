<?php
$dashboardsDir = 'c:/xampp_new/htdocs/stocksathi/pages/dashboards';

$files = scandir($dashboardsDir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $path = $dashboardsDir . '/' . $file;
        $content = file_get_contents($path);
        
        // Remove Chart.js from <head> or anywhere outside <main
        $chartScriptHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';
        
        $hasReplaced = false;
        if (strpos($content, $chartScriptHead) !== false) {
            $content = str_replace($chartScriptHead, '', $content);
            $hasReplaced = true;
        }

        // Search for <main class="content"> and inject Chart.js right after if it has canvas tags
        if (strpos($content, '<canvas') !== false && strpos($content, '<main class="content">') !== false) {
            if (strpos($content, $chartScriptHead) === false) { // Avoid duplicates
               $content = str_replace('<main class="content">', "<main class=\"content\">\n    <!-- Chart.js must be inside main for PJAX -->\n    " . $chartScriptHead, $content);
               $hasReplaced = true;
            }
        }
        
        if ($file === 'sales-executive.php') {
            // Fix salesTarget logic
            $lookFor = "\$salesTarget = (isset(\$userInfo['daily_sales_target']) && \$userInfo['daily_sales_target'] !== null && \$userInfo['daily_sales_target'] !== '') \n    ? (float)\$userInfo['daily_sales_target'] : 10000;";
            $replaceWith = "\$salesTarget = (!empty(\$userInfo['daily_sales_target'])) ? (float)\$userInfo['daily_sales_target'] : 10000;";
            if (strpos($content, $lookFor) !== false) {
                $content = str_replace($lookFor, $replaceWith, $content);
                $hasReplaced = true;
            }
            // Add a simpler replacement just in case
            $lookForAlt = '(isset($userInfo[\'daily_sales_target\']) && $userInfo[\'daily_sales_target\'] !== null && $userInfo[\'daily_sales_target\'] !== \'\')';
            $replaceWithAlt = '(!empty($userInfo[\'daily_sales_target\']))';
            if (strpos($content, $lookForAlt) !== false) {
                $content = str_replace($lookForAlt, $replaceWithAlt, $content);
                $hasReplaced = true;
            }
        }
        
        if ($hasReplaced) {
            file_put_contents($path, $content);
            echo "Fixed $file\n";
        }
    }
}
?>
