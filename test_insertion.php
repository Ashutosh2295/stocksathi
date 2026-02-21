<?php
require_once '_includes/config.php';
require_once '_includes/database.php';
require_once '_includes/Session.php';

// Mock session setting as if issue@gmail.com logged in
Session::start();
Session::setUser(78, 'issue', 'super_admin', 78);

$db = Database::getInstance();

// Insert a product as user issue
try {
    $db->execute("INSERT INTO categories (name, description, status) VALUES ('Test Cat', 'Dynamic org filter test', 'active')");
    $categoryId = $db->lastInsertId();
    echo "Category inserted with ID: $categoryId\n";
    
    // Read it back
    $cat = $db->queryOne("SELECT * FROM categories WHERE id = $categoryId");
    print_r($cat);
    
    // Delete it 
    $db->execute("DELETE FROM categories WHERE id = $categoryId");
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
