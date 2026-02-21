<?php
require_once '_includes/config.php';
require_once '_includes/database.php';
require_once '_includes/Session.php';

Session::start();
Session::setUser(78, 'issue', 'super_admin', 78);

$db = Database::getInstance();

try {
    $db->execute("INSERT INTO categories (name, description, status) VALUES ('Dynamic Test Cat 3', 'Dynamic org filter test 3', 'active')");
    $id = $db->lastInsertId();
    $cat = $db->queryOne("SELECT * FROM categories WHERE id = $id");
    echo json_encode($cat);
    
    $db->execute("DELETE FROM categories WHERE id = $id");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
