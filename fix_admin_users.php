<?php
require '_includes/config.php';
require '_includes/database.php';
$db = Database::getInstance();

$users = $db->query("SELECT * FROM users");
foreach($users as $u) {
    if (in_array($u['role'], ['admin', 'super_admin'])) {
        $emp = $db->queryOne("SELECT id FROM employees WHERE user_id = ?", [$u['id']]);
        if(!$emp) {
            $code = 'EMP-' . str_pad($u['id'], 3, '0', STR_PAD_LEFT);
            try {
                $db->execute("INSERT INTO employees (user_id, employee_code, first_name, last_name, email, phone, join_date, status) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'active')", 
                [$u['id'], $code, $u['username'], 'Admin', $u['email'] ?? 'admin@example.com', '0000000000']);
                echo "Created employee for user ID " . $u['id'] . " (" . $u['username'] . ")\n";
            } catch (Exception $e) {
                echo "Error for user " . $u['id'] . ": " . $e->getMessage() . "\n";
            }
        } else {
            echo "User ID " . $u['id'] . " already linked to employee ID " . $emp['id'] . "\n";
        }
    }
}
echo "Done.\n";
