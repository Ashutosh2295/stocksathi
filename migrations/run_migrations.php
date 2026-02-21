<?php
/**
 * Database Migration Runner - Stocksathi
 * Run this to execute database migrations
 * Access via: http://localhost/stocksathi/migrations/run_migrations.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Database Migrations - Stocksathi</title>";
echo "<style>
body { font-family: 'Segoe UI', system-ui, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.container { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
h1 { color: #667eea; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.success { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; padding: 12px 16px; border-radius: 10px; margin: 10px 0; border-left: 4px solid #28a745; }
.error { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; padding: 12px 16px; border-radius: 10px; margin: 10px 0; border-left: 4px solid #dc3545; }
.info { background: linear-gradient(135deg, #e7f3ff 0%, #cce5ff 100%); color: #004085; padding: 12px 16px; border-radius: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
.warning { background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); color: #856404; padding: 12px 16px; border-radius: 10px; margin: 10px 0; border-left: 4px solid #ffc107; }
a.btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; margin-top: 16px; transition: transform 0.2s, box-shadow 0.2s; }
a.btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
.code-block { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; overflow-x: auto; font-family: 'Consolas', monospace; font-size: 13px; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h1>🔄 Database Migrations</h1>";

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'stocksathi';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✅ Database connection successful</div>";
    
    // Check if stock_logs table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'stock_logs'")->fetch();
    
    if (!$tableCheck) {
        echo "<div class='info'>📋 Creating stock_logs table...</div>";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `stock_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `product_id` int(11) NOT NULL,
              `type` enum('in','out','adjustment','transfer') NOT NULL,
              `quantity` int(11) NOT NULL,
              `reference_type` varchar(50) DEFAULT NULL,
              `reference_id` int(11) DEFAULT NULL,
              `warehouse_id` int(11) DEFAULT NULL,
              `store_id` int(11) DEFAULT NULL,
              `from_location_id` int(11) DEFAULT NULL,
              `to_location_id` int(11) DEFAULT NULL,
              `notes` text DEFAULT NULL,
              `created_by` int(11) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `product_id` (`product_id`),
              KEY `type` (`type`),
              KEY `warehouse_id` (`warehouse_id`),
              KEY `store_id` (`store_id`),
              KEY `created_by` (`created_by`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "<div class='success'>✅ stock_logs table created successfully!</div>";
    } else {
        echo "<div class='warning'>⚠️ stock_logs table already exists</div>";
    }
    
    // Check sales_returns table for refund_status column
    $columnCheck = $pdo->query("SHOW COLUMNS FROM sales_returns LIKE 'refund_status'")->fetch();
    
    if (!$columnCheck) {
        echo "<div class='info'>📋 Adding refund_status column to sales_returns...</div>";
        
        $pdo->exec("
            ALTER TABLE `sales_returns` 
            ADD COLUMN `refund_status` enum('pending','processing','completed') DEFAULT 'pending' AFTER `status`
        ");
        
        // Update existing records
        $pdo->exec("
            UPDATE `sales_returns` SET `refund_status` = CASE 
              WHEN `status` = 'refunded' THEN 'completed'
              WHEN `status` = 'approved' THEN 'processing'
              ELSE 'pending'
            END
        ");
        
        echo "<div class='success'>✅ refund_status column added to sales_returns!</div>";
    } else {
        echo "<div class='warning'>⚠️ refund_status column already exists in sales_returns</div>";
    }
    
    // Check for created_by column in sales_returns
    $createdByCheck = $pdo->query("SHOW COLUMNS FROM sales_returns LIKE 'created_by'")->fetch();
    
    if (!$createdByCheck) {
        echo "<div class='info'>📋 Adding created_by column to sales_returns...</div>";
        
        $pdo->exec("
            ALTER TABLE `sales_returns` 
            ADD COLUMN `created_by` int(11) DEFAULT NULL
        ");
        
        echo "<div class='success'>✅ created_by column added to sales_returns!</div>";
    }
    
    // Verify table structure
    echo "<h2>📊 Table Structure Verification</h2>";
    
    $tables = ['stock_logs', 'sales_returns', 'invoices', 'products'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<div class='info'>📋 <strong>$table</strong>: $count records</div>";
    }
    
    echo "<div class='success' style='margin-top: 20px;'><strong>🎉 All Migrations Complete!</strong><br>Your database is now up to date.</div>";
    
    echo "<a href='../pages/stock-in.php' class='btn'>Go to Stock In →</a>";
    echo " <a href='../index.php' class='btn'>Go to Dashboard →</a>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Make sure MySQL is running and the 'stocksathi' database exists.</div>";
    
    echo "<div class='code-block'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</div>";
}

echo "</div></body></html>";
?>
