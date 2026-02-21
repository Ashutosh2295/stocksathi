<?php
/**
 * Global Configuration for Frontend Pages
 */

// Base paths - Dynamic resolution for different hosting environments (Localhost vs InfinityFree)
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$rootDir = str_replace('\\', '/', dirname(__DIR__));
$basePath = str_replace($docRoot, '', $rootDir);
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
define('BASE_PATH', $basePath);
define('ASSETS_PATH', BASE_PATH . '/assets');
define('CSS_PATH', BASE_PATH . '/css');
define('JS_PATH', BASE_PATH . '/js');
define('API_PATH', BASE_PATH . '/api');

// Page paths
define('LOGIN_PAGE', BASE_PATH . '/pages/login.php');
define('DASHBOARD_PAGE', BASE_PATH . '/index.php');
define('SALES_DASHBOARD_PAGE', BASE_PATH . '/sales/dashboard.php');
define('SUPER_ADMIN_DASHBOARD_PAGE', BASE_PATH . '/super-admin/dashboard.php');

// App settings
define('APP_NAME', 'Stocksathi');
define('APP_VERSION', '2.0');
define('ITEMS_PER_PAGE', 20);

// Include session helper
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/PermissionMiddleware.php';
require_once __DIR__ . '/AuthHelper.php';
require_once __DIR__ . '/RoleManager.php';
require_once __DIR__ . '/OrganizationHelper.php';
