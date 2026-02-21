<?php
require_once '_includes/config.php';
require_once '_includes/Session.php';

// Mock session setup for user issue@gmail.com
Session::start();
$db = Database::getInstance();
$conn = $db->getConnection();
$user = $db->queryOne("SELECT * FROM users WHERE email = 'issue@gmail.com'");
if ($user) {
    echo "Found user. ID: {$user['id']}, Org_ID: {$user['organization_id']}\n";
    Session::setUser($user['id'], $user['username'], $user['role'], $user['organization_id']);
} else {
    echo "User not found\n";
    exit;
}

// Emulate products.php query logic
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";

echo "\$orgFilter is set to: '$orgFilter'\n";

// Run the products query exactly as it is in products.php
$query = "SELECT p.*, 
          c.name as category_name, 
          b.name as brand_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          WHERE {$orgFilter} 1=1 LIMIT 5";

try {
    $products = $db->query($query);
    echo "Products query succeeded. Count: " . count($products) . "\n";
    if (count($products) > 0) {
        echo "First product organization_id: " . ($products[0]['organization_id'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Products query failed! Error: " . $e->getMessage() . "\n";
}
