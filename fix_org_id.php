<?php
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

try {
    $db = Database::getInstance();
    
    // Update invoices with NULL organization_id to 1 (Default Organization)
    $result = $db->execute("UPDATE invoices SET organization_id = 1 WHERE organization_id IS NULL OR organization_id = 0");
    echo "Updated $result invoices with default organization_id (1).\n";
    
    // Also ensure customers have organization_id
    try {
        $db->execute("UPDATE customers SET organization_id = 1 WHERE organization_id IS NULL OR organization_id = 0");
        echo "Updated customers with default organization_id (1).\n";
    } catch (Exception $e) {
        // Ignore if valid
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
