<?php
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

try {
    $db = Database::getInstance();
    
    // 1. Add payment_mode_id column
    // Check if column exists first to avoid error (though try-catch works too, checking is cleaner)
    try {
        $db->execute("ALTER TABLE invoices ADD COLUMN payment_mode_id INT(11) NULL AFTER paid_amount");
        echo "[SUCCESS] Added payment_mode_id column.\n";
    } catch (Exception $e) {
        // If error contains "Duplicate column", it's fine
        echo "[INFO] Maybe column payment_mode_id already exists or error: " . $e->getMessage() . "\n";
    }
    
    // 2. Modify payment_status to VARCHAR to allow 'pending'
    try {
        $db->execute("ALTER TABLE invoices MODIFY COLUMN payment_status VARCHAR(50) DEFAULT 'pending'");
        echo "[SUCCESS] Modified payment_status to VARCHAR and set default to 'pending'.\n";
    } catch (Exception $e) {
        echo "[ERROR] modifying payment_status: " . $e->getMessage() . "\n";
    }

    // 3. Modify status to VARCHAR to allow 'finalized'
    try {
        $db->execute("ALTER TABLE invoices MODIFY COLUMN status VARCHAR(50) DEFAULT 'draft'");
        echo "[SUCCESS] Modified status to VARCHAR and set default to 'draft'.\n";
    } catch (Exception $e) {
        echo "[ERROR] modifying status: " . $e->getMessage() . "\n";
    }

    echo "Database repair script finished.";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage();
}
