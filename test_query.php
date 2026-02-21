<?php
require_once '_includes/config.php';
require_once '_includes/database.php';
require_once '_includes/Session.php';

Session::start();
Session::setUser(78, 'issue', 'super_admin', 78);

$db = Database::getInstance();
$query = "SELECT p.*, c.name as category_name, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.organization_id = 78 AND 1=1";
echo "Base Query: $query\n";

$countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
echo "Count Query: $countQuery\n";

try {
    $res = $db->queryOne($countQuery);
    print_r($res);
    echo "Success!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
