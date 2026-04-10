<?php
/**
 * Logout System
 * Destroys session, clears cookies, and redirects to login
 */

require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/Session.php';

Session::start();

// Clear user data and destroy session
Session::clearUser();
Session::destroy();

// Clear session cookie (must be done before redirect)
$cookieParams = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);

// Clear other auth cookies
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$cookiePath = $basePath ?: '/';
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, $cookiePath);
}
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, $cookiePath);
}

// Redirect to login
header('Location: ' . (defined('BASE_PATH') ? BASE_PATH : '') . '/pages/login.php');
exit;
