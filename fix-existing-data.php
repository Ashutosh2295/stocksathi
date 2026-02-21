<?php
/**
 * ONE-CLICK FIX - Restore All Data
 * This will immediately fix your organization_id issue
 */

// Direct database connection (bypass all includes)
$host = 'localhost';
$dbname = 'stocksathi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>ONE-CLICK FIX</title>
        <style>
            body { font-family: Arial; padding: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
            h1 { color: #333; }
            .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
            .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
            .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
            pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .btn { background: #007bff; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 ONE-CLICK FIX - Restoring Your Data</h1>";
    
    $pdo->beginTransaction();
    
    // Step 1: Check organizations
    echo "<div class='info'><strong>Step 1:</strong> Checking organizations...</div>";
    
    $orgCount = $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
    
    if ($orgCount == 0) {
        // Create default organization
        $pdo->exec("INSERT INTO organizations (name, email, phone, address, status, created_at) 
                    VALUES ('Stocksathi Demo', 'demo@stocksathi.com', '9999999999', 'Demo Address', 'active', NOW())");
        $orgId = $pdo->lastInsertId();
        echo "<div class='success'>✅ Created organization with ID: {$orgId}</div>";
    } else {
        $org = $pdo->query("SELECT id, name FROM organizations ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $orgId = $org['id'];
        echo "<div class='success'>✅ Using organization: {$org['name']} (ID: {$orgId})</div>";
    }
    
    // Step 2: Update users
    echo "<div class='info'><strong>Step 2:</strong> Updating users...</div>";
    $stmt = $pdo->prepare("UPDATE users SET organization_id = ? WHERE organization_id IS NULL OR organization_id = 0");
    $stmt->execute([$orgId]);
    $usersUpdated = $stmt->rowCount();
    echo "<div class='success'>✅ Updated {$usersUpdated} users</div>";
    
    // Step 3: Update all tables
    echo "<div class='info'><strong>Step 3:</strong> Updating all data tables...</div>";
    
    $tables = [
        'products', 'customers', 'suppliers', 'invoices', 'quotations', 
        'expenses', 'categories', 'brands', 'warehouses', 'stores',
        'employees', 'departments'
    ];
    
    $totalUpdated = 0;
    foreach ($tables as $table) {
        try {
            // Check if table exists
            $tableExists = $pdo->query("SHOW TABLES LIKE '{$table}'")->rowCount() > 0;
            
            if ($tableExists) {
                // Check if organization_id column exists
                $columnExists = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'organization_id'")->rowCount() > 0;
                
                if ($columnExists) {
                    $stmt = $pdo->prepare("UPDATE `{$table}` SET organization_id = ? WHERE organization_id IS NULL OR organization_id = 0");
                    $stmt->execute([$orgId]);
                    $updated = $stmt->rowCount();
                    $totalUpdated += $updated;
                    
                    if ($updated > 0) {
                        echo "<div class='success'>✅ {$table}: {$updated} records</div>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>⚠️ {$table}: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<div class='success'><strong>✅ Total records updated: {$totalUpdated}</strong></div>";
    
    // Step 4: Verify
    echo "<div class='info'><strong>Step 4:</strong> Verification...</div>";
    
    $stats = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM users WHERE organization_id = ?) as users,
        (SELECT COUNT(*) FROM products WHERE organization_id = ?) as products,
        (SELECT COUNT(*) FROM customers WHERE organization_id = ?) as customers,
        (SELECT COUNT(*) FROM invoices WHERE organization_id = ?) as invoices
    ");
    $stats->execute([$orgId, $orgId, $orgId, $orgId]);
    $data = $stats->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Organization ID: {$orgId}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Users:     {$data['users']}\n";
    echo "Products:  {$data['products']}\n";
    echo "Customers: {$data['customers']}\n";
    echo "Invoices:  {$data['invoices']}\n";
    echo "</pre>";
    
    // Check for NULL records
    $nullCheck = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE organization_id IS NULL) as null_users,
            (SELECT COUNT(*) FROM products WHERE organization_id IS NULL) as null_products,
            (SELECT COUNT(*) FROM customers WHERE organization_id IS NULL) as null_customers
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($nullCheck['null_users'] > 0 || $nullCheck['null_products'] > 0 || $nullCheck['null_customers'] > 0) {
        echo "<div class='error'>⚠️ Warning: Some records still have NULL organization_id:</div>";
        echo "<pre>" . print_r($nullCheck, true) . "</pre>";
    } else {
        echo "<div class='success'>✅ Perfect! No NULL organization_id records found.</div>";
    }
    
    $pdo->commit();
    
    echo "<div class='success'>
        <h2>✅ SUCCESS!</h2>
        <p>All your data has been restored and linked to organization ID: {$orgId}</p>
        <p><strong>Next steps:</strong></p>
        <ol>
            <li>Logout from your current session</li>
            <li>Login again with your credentials</li>
            <li>Check your dashboard - all data should be visible!</li>
        </ol>
    </div>";
    
    echo "<a href='pages/logout.php' class='btn'>Logout Now</a>";
    echo " <a href='pages/login.php' class='btn' style='background: #28a745;'>Go to Login</a>";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div class='error'>
        <h2>❌ ERROR</h2>
        <p>" . $e->getMessage() . "</p>
        <pre>" . $e->getTraceAsString() . "</pre>
    </div>";
    
    echo "<div class='info'>
        <h3>Database Connection Settings:</h3>
        <p>If you see a connection error, update these settings at the top of this file:</p>
        <pre>
\$host = 'localhost';
\$dbname = 'stocksathi';  // Your database name
\$username = 'root';       // Your MySQL username
\$password = '';           // Your MySQL password
        </pre>
    </div>";
}

echo "
        </div>
    </body>
    </html>";

?>
