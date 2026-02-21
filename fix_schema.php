<?php
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

try {
    $db = Database::getInstance();
    echo "Starting schema fix...\n";

    // 1. Fix Promotions Table
    echo "Checking 'promotions' table...\n";
    try {
        $cols = $db->query("SHOW COLUMNS FROM promotions LIKE 'created_by'");
        if (empty($cols)) {
            echo "Adding 'created_by' column to 'promotions'...\n";
            $db->execute("ALTER TABLE promotions ADD COLUMN created_by INT(11) NULL AFTER status");
            echo "Success.\n";
        } else {
            echo "Column 'created_by' already exists.\n";
        }
    } catch (Exception $e) {
        echo "Error checking promotions: " . $e->getMessage() . "\n";
    }

    // 2. Fix Suppliers Table
    echo "\nChecking 'suppliers' table...\n";
    try {
        $cols = $db->query("SHOW COLUMNS FROM suppliers LIKE 'contact_person'");
        if (empty($cols)) {
            echo "Adding 'contact_person' column to 'suppliers'...\n";
            $db->execute("ALTER TABLE suppliers ADD COLUMN contact_person VARCHAR(100) NULL AFTER phone");
            echo "Success.\n";
        } else {
            echo "Column 'contact_person' already exists.\n";
        }
    } catch (Exception $e) {
        echo "Error checking suppliers: " . $e->getMessage() . "\n";
    }
    
    // 3. Create Organization Settings Table
    echo "\nChecking 'organization_settings' table...\n";
    try {
        $tables = $db->query("SHOW TABLES LIKE 'organization_settings'");
        if (empty($tables)) {
             echo "Creating 'organization_settings' table...\n";
            $sql = "CREATE TABLE IF NOT EXISTS organization_settings (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                organization_id INT(11) NOT NULL,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT,
                setting_group VARCHAR(50) DEFAULT 'general',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_org_setting (organization_id, setting_key)
            )";
            $db->execute($sql);
            echo "Success.\n";
        } else {
            echo "Table 'organization_settings' already exists.\n";
        }
    } catch (Exception $e) {
         echo "Error creating organization_settings: " . $e->getMessage() . "\n";
    }

    echo "\nSchema fix completed.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
