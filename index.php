<?php
/**
 * StockSathi - Landing Page
 * Checks system status and redirects accordingly
 */

require_once __DIR__ . '/_includes/database.php';
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/Session.php';

Session::start();

// Check if user is already logged in — redirect to role-appropriate dashboard
if (Session::isLoggedIn()) {
    $role = strtolower(trim((string)(Session::getUserRole() ?? 'user')));
    $dashboards = [
        'super_admin' => 'pages/dashboards/super-admin.php',
        'admin'       => 'pages/dashboards/admin.php',
        'hr'          => 'pages/dashboards/hr.php',
        'store_manager' => 'pages/dashboards/store-manager.php',
        'sales_executive' => 'pages/dashboards/sales-executive.php',
        'accountant'  => 'pages/dashboards/accountant.php',
        'warehouse_manager' => 'pages/dashboards/store-manager.php',
    ];
    $target = $dashboards[$role] ?? 'pages/dashboards/general.php';
    header('Location: ' . (defined('BASE_PATH') ? BASE_PATH . '/' : '') . $target);
    exit;
}

// Check if system is set up
$db = Database::getInstance();
$setupComplete = false;
$hasOrganizations = false;
$hasUsers = false;

try {
    // Check if organizations table exists and has data
    $orgCheck = $db->queryOne("SELECT COUNT(*) as count FROM organizations");
    $hasOrganizations = $orgCheck['count'] > 0;
    
    // Check if users table has data
    $userCheck = $db->queryOne("SELECT COUNT(*) as count FROM users");
    $hasUsers = $userCheck['count'] > 0;
    
    $setupComplete = $hasOrganizations && $hasUsers;
    
} catch (Exception $e) {
    // Tables don't exist, need setup
    $setupComplete = false;
}

// Redirect based on setup status
if (!$setupComplete) {
    // No setup done, go to setup page
    header('Location: setup-organization.php');
    exit;
} else {
    // Setup done, go to landing page
    header('Location: landing1/index.php');
    exit;
}
?>
