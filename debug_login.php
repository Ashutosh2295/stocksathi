<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

echo "<h2>New Organization Login Debug</h2><pre>";

$db = Database::getInstance()->getConnection();

// List all users
echo "=== ALL USERS ===\n";
$stmt = $db->query("SELECT id, organization_id, username, email, role, status, full_name, 
                     LENGTH(password) as pw_len, last_login, created_at 
                     FROM users ORDER BY id DESC LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID={$u['id']} | org_id={$u['organization_id']} | {$u['username']} | {$u['email']} | role={$u['role']} | status={$u['status']} | pw_len={$u['pw_len']} | last_login={$u['last_login']} | created={$u['created_at']}\n";
}

echo "\n=== ALL ORGANIZATIONS ===\n";
$stmt = $db->query("SELECT * FROM organizations ORDER BY id DESC LIMIT 10");
$orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($orgs as $o) {
    echo "ID={$o['id']} | {$o['name']} | {$o['email']} | status={$o['status']} | created={$o['created_at']}\n";
}

// Test login for the newest user
if (!empty($users)) {
    $latestUser = $users[0];
    echo "\n=== TESTING LOGIN FOR LATEST USER: {$latestUser['username']} ({$latestUser['email']}) ===\n";
    
    // Check if user status is 'active'
    if ($latestUser['status'] !== 'active') {
        echo "⚠️ USER STATUS IS '{$latestUser['status']}' - NOT 'active'! This could block login.\n";
    } else {
        echo "✅ User status is active\n";
    }
    
    // Check organization status
    if ($latestUser['organization_id']) {
        $orgStmt = $db->prepare("SELECT status FROM organizations WHERE id = ?");
        $orgStmt->execute([$latestUser['organization_id']]);
        $org = $orgStmt->fetch(PDO::FETCH_ASSOC);
        if ($org) {
            echo "Organization status: {$org['status']}\n";
        } else {
            echo "⚠️ Organization NOT FOUND for org_id={$latestUser['organization_id']}\n";
        }
    }
    
    // Check password hash format
    $pwStmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $pwStmt->execute([$latestUser['id']]);
    $pwRow = $pwStmt->fetch(PDO::FETCH_ASSOC);
    $hash = $pwRow['password'];
    echo "Password hash starts with: " . substr($hash, 0, 7) . "...\n";
    echo "Valid bcrypt format: " . (substr($hash, 0, 4) === '$2y$' ? 'YES' : 'NO') . "\n";
}

// Check login redirect logic
echo "\n=== LOGIN REDIRECT MAP ===\n";
$dashboards = [
    'super_admin' => 'dashboards/super-admin.php',
    'admin' => 'dashboards/admin.php',
    'hr' => 'dashboards/hr.php',
    'store_manager' => 'dashboards/store-manager.php',
    'sales_executive' => 'dashboards/sales-executive.php',
    'accountant' => 'dashboards/accountant.php',
    'warehouse_manager' => 'dashboards/store-manager.php',
];
foreach ($dashboards as $role => $url) {
    $fullPath = __DIR__ . '/pages/' . $url;
    $exists = file_exists($fullPath) ? '✅' : '❌';
    echo "{$exists} {$role} => {$url}\n";
}

// Check session state
echo "\n=== CURRENT SESSION ===\n";
Session::start();
echo "user_id: " . (Session::getUserId() ?: 'NOT SET') . "\n";
echo "username: " . (Session::getUserName() ?: 'NOT SET') . "\n";
echo "role: " . (Session::getUserRole() ?: 'NOT SET') . "\n";
echo "organization_id: " . (Session::getOrganizationId() ?: 'NOT SET') . "\n";
echo "logged_in: " . (Session::isLoggedIn() ? 'YES' : 'NO') . "\n";

echo "</pre>";
