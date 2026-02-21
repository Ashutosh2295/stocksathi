<?php
/**
 * Add Organization Columns to Existing Tables
 * This script safely adds organization_id to all tables
 */

require_once '_includes/database.php';

function addOrganizationColumn($tableName) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Check if column already exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE 'organization_id'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "Column already exists in {$tableName}"];
        }
        
        // Add the column
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `organization_id` int(11) DEFAULT NULL AFTER `id`";
        $conn->exec($sql);
        
        // Add index
        $sql = "ALTER TABLE `{$tableName}` ADD INDEX `idx_org_{$tableName}` (`organization_id`)";
        $conn->exec($sql);
        
        return ['success' => true, 'message' => "Added organization_id to {$tableName}"];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error on {$tableName}: " . $e->getMessage()];
    }
}

// Tables that need organization_id
$tables = [
    'users',
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
    'departments'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Organization Columns</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Add Organization Columns</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_columns'])) {
    echo "<h2>Processing...</h2>";
    
    foreach ($tables as $table) {
        $result = addOrganizationColumn($table);
        $class = $result['success'] ? 'success' : 'error';
        echo "<div class='result {$class}'>{$result['message']}</div>";
    }
    
    echo "<div class='result info'>✅ Process completed! You can now use the registration system.</div>";
    echo "<p><a href='pages/register.php' class='btn'>Go to Registration</a></p>";
    
} else {
    echo "
        <p>This script will add <code>organization_id</code> column to all necessary tables.</p>
        <p><strong>Tables to be modified:</strong></p>
        <ul>";
    
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    
    echo "</ul>
        <form method='POST'>
            <button type='submit' name='add_columns' class='btn'>Add Organization Columns</button>
        </form>";
}

echo "
    </div>
</body>
</html>";
