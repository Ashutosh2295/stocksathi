<?php
/**
 * Setup HR Role + Permissions (non-destructive)
 * Run once: http://localhost/stocksathi/migrations/setup_hr_role.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'stocksathi';
$username = 'root';
$password = '';

echo "<!DOCTYPE html><html><head><title>Setup HR Role - Stocksathi</title>";
echo "<style>body{font-family:system-ui,sans-serif;max-width:860px;margin:40px auto;padding:20px;} .ok{color:#166534;} .err{color:#991b1b;} code{background:#f3f4f6;padding:2px 6px;border-radius:6px;}</style>";
echo "</head><body><h1>Setup HR Role + Permissions</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='ok'>Database connected.</p>";

    // Ensure RBAC tables exist (minimal)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL UNIQUE,
            module VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_module (module),
            KEY idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            role_id INT(11) NOT NULL,
            permission_id INT(11) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_role_permission (role_id, permission_id),
            KEY idx_role_id (role_id),
            KEY idx_permission_id (permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Ensure role exists
    $pdo->exec("
        INSERT INTO roles (name, display_name, description, permissions)
        VALUES ('hr', 'HR Manager', 'Human Resources: employees, attendance, leave', '{\"hr\": true}')
        ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), description=VALUES(description)
    ");
    echo "<p class='ok'>Ensured role: <code>hr</code></p>";

    // Ensure permissions exist
    $pdo->exec("
        INSERT IGNORE INTO permissions (name, module, action, description) VALUES
        ('view_hr_dashboard', 'hrm', 'view', 'View HR dashboard'),
        ('view_employees', 'hrm', 'view', 'View employees'),
        ('manage_employees', 'hrm', 'edit', 'Manage employees'),
        ('view_attendance', 'hrm', 'view', 'View attendance'),
        ('manage_attendance', 'hrm', 'edit', 'Manage attendance'),
        ('view_leave', 'hrm', 'view', 'View leave requests'),
        ('manage_leave', 'hrm', 'edit', 'Manage leave requests'),
        ('approve_leave', 'hrm', 'approve', 'Approve/reject leave requests'),
        ('view_leave_requests', 'hrm', 'view', 'View leave requests (alias)')
    ");
    echo "<p class='ok'>Ensured HR permissions.</p>";

    // Assign permissions to HR role
    $hrRoleId = (int)$pdo->query("SELECT id FROM roles WHERE name = 'hr' LIMIT 1")->fetchColumn();
    if ($hrRoleId <= 0) throw new Exception("Role 'hr' not found");

    $permIds = $pdo->query("
        SELECT id FROM permissions
        WHERE name IN (
            'view_hr_dashboard','view_employees','manage_employees','view_attendance','manage_attendance',
            'view_leave','manage_leave','approve_leave','view_leave_requests'
        )
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($permIds as $pid) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        $stmt->execute([$hrRoleId, (int)$pid]);
    }
    echo "<p class='ok'>Assigned permissions to <code>hr</code> role.</p>";

    $base = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/migrations/') !== false)
        ? preg_replace('#/migrations/.*#', '', $_SERVER['REQUEST_URI']) : '/stocksathi';

    echo "<p class='ok'><strong>Done.</strong> Next:</p>";
    echo "<ol>";
    echo "<li>Go to <a href='" . htmlspecialchars($base) . "/pages/users.php'>Users</a> and set role = <code>hr</code> for your HR user.</li>";
    echo "<li>Open <a href='" . htmlspecialchars($base) . "/pages/dashboards/hr.php'>HR Dashboard</a> and click <strong>Create/Link my Employee Profile</strong> if needed.</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p class='err'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";

