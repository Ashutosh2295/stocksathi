<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Organization System - Stocksathi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .step h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .step p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .code {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #48bb78;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #c6f6d5;
            border-left: 4px solid #48bb78;
            color: #22543d;
        }
        
        .alert-error {
            background: #fed7d7;
            border-left: 4px solid #f56565;
            color: #742a2a;
        }
        
        .alert-info {
            background: #bee3f8;
            border-left: 4px solid #4299e1;
            color: #2c5282;
        }
        
        .checklist {
            list-style: none;
            margin: 15px 0;
        }
        
        .checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        
        .checklist li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #48bb78;
            font-weight: bold;
            font-size: 18px;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏢 Organization System Setup</h1>
        <p class="subtitle">Set up multi-tenancy and organization-based data isolation for Stocksathi</p>
        
        <?php
        require_once '_includes/database.php';
        
        $message = '';
        $messageType = '';
        $step = 1;
        
        // Check if organizations table exists
        $orgsTableExists = false;
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $result = $conn->query("SHOW TABLES LIKE 'organizations'");
            $orgsTableExists = $result->rowCount() > 0;
            if ($orgsTableExists) {
                $step = 2;
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        // Check if organization_id columns exist
        $columnsAdded = false;
        if ($orgsTableExists) {
            try {
                $stmt = $conn->prepare("SHOW COLUMNS FROM `users` LIKE 'organization_id'");
                $stmt->execute();
                $columnsAdded = $stmt->rowCount() > 0;
                if ($columnsAdded) {
                    $step = 3; // Complete
                }
            } catch (Exception $e) {
                // Ignore
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
            try {
                $db = Database::getInstance();
                $conn = $db->getConnection();
                
                // Read migration file
                $migrationFile = __DIR__ . '/migrations/add_organization_support.sql';
                
                if (!file_exists($migrationFile)) {
                    throw new Exception("Migration file not found: {$migrationFile}");
                }
                
                $sql = file_get_contents($migrationFile);
                
                // Execute the SQL
                $conn->exec($sql);
                
                $message = "✅ Step 1 completed! Organizations table created successfully.";
                $messageType = 'success';
                $step = 2;
                
            } catch (Exception $e) {
                $message = "❌ Migration failed: " . $e->getMessage();
                $messageType = 'error';
            }
        }
        ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 3): ?>
            <div class="alert alert-info">
                ✅ Organization system is fully set up! You can now register organizations.
            </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>📋 Setup Process (2 Steps)</h3>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li style="margin-bottom: 8px; <?= $step >= 1 ? 'color: #48bb78; font-weight: bold;' : '' ?>">
                    <?= $orgsTableExists ? '✅' : '⏳' ?> Create organizations table
                </li>
                <li style="margin-bottom: 8px; <?= $step >= 2 ? 'color: #48bb78; font-weight: bold;' : '' ?>">
                    <?= $columnsAdded ? '✅' : '⏳' ?> Add organization_id to existing tables
                </li>
            </ol>
        </div>
        
        <div class="step">
            <h3>📋 What This Setup Does</h3>
            <ul class="checklist">
                <li>Creates organizations table</li>
                <li>Adds organization_id to all major tables</li>
                <li>Sets up foreign key relationships</li>
                <li>Enables multi-tenancy support</li>
                <li>Ensures data isolation between organizations</li>
            </ul>
        </div>
        
        <div class="step">
            <h3>🚀 After Setup</h3>
            <p>Once the migration is complete, you can:</p>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li style="margin-bottom: 8px;">Register your organization and create a super admin account</li>
                <li style="margin-bottom: 8px;">Login with your credentials</li>
                <li style="margin-bottom: 8px;">Access your organization's dashboard</li>
                <li style="margin-bottom: 8px;">Add users to your organization</li>
                <li style="margin-bottom: 8px;">Manage products, customers, invoices, etc.</li>
            </ol>
        </div>
        
        <div class="step">
            <h3>📖 Documentation</h3>
            <p>For detailed information about the organization system, please read:</p>
            <div class="code">ORGANIZATION_SYSTEM_README.md</div>
        </div>
        
        <div class="actions">
            <?php if ($step === 1): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="run_migration" class="btn" 
                            onclick="return confirm('This will create the organizations table. Continue?')">
                        🔧 Step 1: Create Organizations Table
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <a href="add-organization-columns.php" class="btn">
                    🔧 Step 2: Add Organization Columns
                </a>
            <?php else: ?>
                <a href="pages/register.php" class="btn btn-success">
                    ✨ Go to Registration
                </a>
            <?php endif; ?>
            
            <a href="ORGANIZATION_SYSTEM_README.md" class="btn" target="_blank">
                📖 Read Documentation
            </a>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #718096; font-size: 14px;">
            <strong>Note:</strong> Make sure you have a backup of your database before running the migration.
        </div>
    </div>
</body>
</html>
