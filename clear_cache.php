<?php
/**
 * Clear PHP OpCache
 * Run this if you're seeing old code errors
 */
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OpCache cleared successfully!<br>";
} else {
    echo "⚠️ OpCache not enabled<br>";
}

echo "Please restart Apache in XAMPP Control Panel to ensure all changes are loaded.<br>";
echo "<a href='pages/warehouses.php'>Go to Warehouses Page</a>";
?>
