<?php
/**
 * Session Guard - Protect pages from unauthorized access
 * Include this at the top of any protected page
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/PermissionMiddleware.php';
require_once __DIR__ . '/RoleManager.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    // Store the intended destination
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header('Location: ' . LOGIN_PAGE);
    exit();
}

// Regenerate session ID periodically for security
if (!Session::has('last_regeneration')) {
    Session::regenerate();
    Session::set('last_regeneration', time());
} elseif (time() - Session::get('last_regeneration') > 300) { // Every 5 minutes
    Session::regenerate();
    Session::set('last_regeneration', time());
}

// Friendly error when a required table is missing (e.g. "doesn't exist in engine" - MySQL 1932)
set_exception_handler(function (Throwable $e) {
    $msg = $e->getMessage();
    if (strpos($msg, "doesn't exist") !== false || strpos($msg, '42S02') !== false || strpos($msg, '1146') !== false) {
        header('HTTP/1.1 503 Service Unavailable');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Type: text/html; charset=utf-8');
        $base = defined('BASE_PATH') ? BASE_PATH : '/stocksathi';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database setup required</title>';
        echo '<style>body{font-family:system-ui,sans-serif;max-width:520px;margin:60px auto;padding:24px;} a{color:#2563eb;} .btn{display:inline-block;margin-top:12px;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:8px;} .btn:hover{opacity:.9;}</style></head><body>';
        echo '<h1>Database setup required</h1><p>One or more required database tables are missing or need to be repaired.</p>';
        echo '<p><a href="' . htmlspecialchars($base) . '/migrations/fix_missing_tables.php" class="btn">Fix missing tables</a></p>';
        echo '<p><a href="' . htmlspecialchars($base) . '/index.php">Back to dashboard</a></p></body></html>';
        exit;
    }
    // Not a missing-table error: use default behavior (PHP will show fatal)
    throw $e;
});
