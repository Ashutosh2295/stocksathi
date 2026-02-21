<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully!<br>";
} else {
    echo "OPcache not enabled.<br>";
}

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "Cached scripts: " . ($status['opcache_statistics']['num_cached_scripts'] ?? 'N/A') . "<br>";
}

echo "<br>Done. <a href='/stocksathi/pages/invoice-form.php'>Go to Invoice Form</a>";
