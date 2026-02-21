<?php
/**
 * Logout System
 * Destroys session and redirects to login
 */

require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/Session.php';

// Initialize session if not started
Session::start();

// Destroy session
Session::destroy();

// Clear cookies if any
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page
header('Location: ' . BASE_PATH . '/pages/login.php');
exit;
