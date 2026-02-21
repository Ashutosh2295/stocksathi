<?php
/**
 * Database Connection Test
 * Use this to verify MySQL is running and database is accessible
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - Stocksathi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #1a202c;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .test-item {
            background: #f7fafc;
            border-left: 4px solid #cbd5e0;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
        }
        .test-item.success {
            background: #f0fdf4;
            border-left-color: #10b981;
        }
        .test-item.error {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .test-item.warning {
            background: #fffbeb;
            border-left-color: #f59e0b;
        }
        .test-label {
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .test-result {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
        }
        .icon {
            width: 20px;
            height: 20px;
        }
        .success-icon { color: #10b981; }
        .error-icon { color: #ef4444; }
        .warning-icon { color: #f59e0b; }
        .info-icon { color: #3b82f6; }
        .code {
            background: #1a202c;
            color: #10b981;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 8px;
            overflow-x: auto;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Test</h1>
        <p class="subtitle">Checking MySQL connection and database status...</p>

        <?php
        $tests = [];
        
        // Test 1: Check if MySQL extension is loaded
        $tests[] = [
            'label' => 'PHP PDO MySQL Extension',
            'status' => extension_loaded('pdo_mysql') ? 'success' : 'error',
            'message' => extension_loaded('pdo_mysql') 
                ? 'PDO MySQL extension is loaded ✓' 
                : 'PDO MySQL extension is NOT loaded. Please enable it in php.ini'
        ];

        // Test 2: Try to connect to MySQL
        $dbConnected = false;
        $dbError = '';
        try {
            $conn = new PDO("mysql:host=localhost", "root", "");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbConnected = true;
            $tests[] = [
                'label' => 'MySQL Server Connection',
                'status' => 'success',
                'message' => 'Successfully connected to MySQL server on localhost:3306 ✓'
            ];
        } catch(PDOException $e) {
            $dbError = $e->getMessage();
            $tests[] = [
                'label' => 'MySQL Server Connection',
                'status' => 'error',
                'message' => 'Cannot connect to MySQL server',
                'details' => $dbError
            ];
        }

        // Test 3: Check if stocksathi database exists
        if ($dbConnected) {
            try {
                $stmt = $conn->query("SHOW DATABASES LIKE 'stocksathi'");
                $dbExists = $stmt->rowCount() > 0;
                
                if ($dbExists) {
                    $tests[] = [
                        'label' => 'Database "stocksathi"',
                        'status' => 'success',
                        'message' => 'Database "stocksathi" exists ✓'
                    ];
                    
                    // Test 4: Check if users table exists
                    $conn->exec("USE stocksathi");
                    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
                    $tableExists = $stmt->rowCount() > 0;
                    
                    if ($tableExists) {
                        $tests[] = [
                            'label' => 'Table "users"',
                            'status' => 'success',
                            'message' => 'Table "users" exists ✓'
                        ];
                        
                        // Test 5: Count users
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $userCount = $result['count'];
                        
                        $tests[] = [
                            'label' => 'User Count',
                            'status' => $userCount == 0 ? 'warning' : 'success',
                            'message' => $userCount == 0 
                                ? 'No users found. You can register as the first user (Super Admin)!' 
                                : "Found {$userCount} user(s) in the database"
                        ];
                    } else {
                        $tests[] = [
                            'label' => 'Table "users"',
                            'status' => 'error',
                            'message' => 'Table "users" does NOT exist. Please import the database SQL file.'
                        ];
                    }
                } else {
                    $tests[] = [
                        'label' => 'Database "stocksathi"',
                        'status' => 'error',
                        'message' => 'Database "stocksathi" does NOT exist. Please create it and import the SQL file.'
                    ];
                }
            } catch(PDOException $e) {
                $tests[] = [
                    'label' => 'Database Check',
                    'status' => 'error',
                    'message' => 'Error checking database',
                    'details' => $e->getMessage()
                ];
            }
        }

        // Display all test results
        foreach ($tests as $test) {
            $statusClass = $test['status'];
            $iconClass = $test['status'] . '-icon';
            
            $icon = match($test['status']) {
                'success' => '✓',
                'error' => '✗',
                'warning' => '⚠',
                default => 'ℹ'
            };
            
            echo "<div class='test-item {$statusClass}'>";
            echo "<div class='test-label'>";
            echo "<span class='icon {$iconClass}'>{$icon}</span>";
            echo htmlspecialchars($test['label']);
            echo "</div>";
            echo "<div class='test-result'>" . htmlspecialchars($test['message']) . "</div>";
            if (isset($test['details'])) {
                echo "<div class='code'>" . htmlspecialchars($test['details']) . "</div>";
            }
            echo "</div>";
        }
        ?>

        <div class="action-buttons">
            <a href="pages/register.php" class="btn">Go to Registration</a>
            <a href="javascript:location.reload()" class="btn" style="background: #6b7280;">Refresh Test</a>
        </div>

        <?php if (!$dbConnected): ?>
        <div class="test-item error" style="margin-top: 20px;">
            <div class="test-label">
                <span class="icon error-icon">💡</span>
                Quick Fix
            </div>
            <div class="test-result">
                <strong>MySQL is not running!</strong><br><br>
                <strong>To fix this:</strong><br>
                1. Open XAMPP Control Panel<br>
                2. Click "Start" button next to MySQL<br>
                3. Wait for it to show "Running" status<br>
                4. Refresh this page
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
