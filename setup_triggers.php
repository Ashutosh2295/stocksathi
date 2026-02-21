<?php
require '_includes/config.php';
// Database is likely included via config.php in this environment
$db = Database::getInstance();
$conn = $db->getConnection();

$tables = [
    'products',
    'customers',
    'suppliers',
    'invoices',
    'quotations',
    'expenses',
    'warehouses',
    'stores',
    'categories',
    'brands',
    'employees',
    'departments',
    'sales_returns',
    'stock_logs',
    'invoice_items',
    'quotation_items',
    'activity_logs'
];

foreach ($tables as $table) {
    echo "Creating trigger for $table... ";
    
    // Check if table exists
    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() == 0) {
        echo "Table does not exist. Skipping.\n";
        continue;
    }
    
    // Check if organization_id exists
    $stmt = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'organization_id'");
    if ($stmt->rowCount() == 0) {
        // Add organization_id column if it doesn't exist
        try {
            $conn->exec("ALTER TABLE `$table` ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`");
            echo "Added organization_id. ";
        } catch (Exception $e) {
            echo "organization_id missing and could not add. Skipping.\n";
            continue;
        }
    }
    
    // Drop existing trigger
    $triggerName = "trg_{$table}_org_id";
    try {
        $conn->exec("DROP TRIGGER IF EXISTS `$triggerName`");
        
        // Create new trigger
        $sql = "
        CREATE TRIGGER `$triggerName` BEFORE INSERT ON `$table`
        FOR EACH ROW
        BEGIN
            IF NEW.organization_id IS NULL AND @current_org_id IS NOT NULL THEN
                SET NEW.organization_id = @current_org_id;
            END IF;
        END;
        ";
        $conn->exec($sql);
        echo "Trigger created successfully.\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "All multi-tenant database triggers created successfully!\n";
