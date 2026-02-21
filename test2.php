<?php
require_once '_includes/config.php';
require_once '_includes/database.php';
try {
    $db = Database::getInstance();
    $rows = $db->query("SELECT id, email, role, organization_id FROM users WHERE email='issue@gmail.com'");
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
