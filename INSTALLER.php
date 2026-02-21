<?php
/**
 * ========================================================
 * STOCKSATHI - ONE-CLICK INSTALLER
 * ========================================================
 * Version: 2.0
 * This single file will completely set up StockSathi
 * on any system with XAMPP/LAMP/WAMP
 * ========================================================
 */

// Configuration
define('INSTALLER_VERSION', '2.0');
define('APP_NAME', 'StockSathi');
define('DB_NAME', 'stocksathi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_HOST', 'localhost');

// ========================================================
// HELPER FUNCTIONS (Must be defined before use)
// ========================================================

function handlePostRequest() {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'next_step':
            $_SESSION['installer_step']++;
            break;
            
        case 'prev_step':
            $_SESSION['installer_step']--;
            if ($_SESSION['installer_step'] < 1) {
                $_SESSION['installer_step'] = 1;
            }
            break;
            
        case 'refresh':
            // Just refresh the page
            break;
                
        case 'test_database':
            testDatabaseConnection();
            break;
            
        case 'install':
            performInstallation();
            break;
    }
    
    // Ensure session is written before redirect
    session_write_close();
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function performSystemChecks() {
    $checks = [];
    
    // PHP Version
    $checks[] = [
        'name' => 'PHP Version (>= 7.4)',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'message' => 'Current version: ' . PHP_VERSION
    ];
    
    // PDO Extension
    $checks[] = [
        'name' => 'PDO Extension',
        'status' => extension_loaded('pdo'),
        'message' => 'PDO extension is required for database operations'
    ];
    
    // PDO MySQL Driver
    $checks[] = [
        'name' => 'PDO MySQL Driver',
        'status' => extension_loaded('pdo_mysql'),
        'message' => 'PDO MySQL driver is required'
    ];
    
    // File Permissions
    $writable = is_writable(__DIR__);
    $checks[] = [
        'name' => 'Directory Writable',
        'status' => $writable,
        'message' => $writable ? 'Directory is writable' : 'Directory must be writable'
    ];
    
    // MySQL Connection
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $checks[] = [
            'name' => 'MySQL Connection',
            'status' => true,
            'message' => 'Successfully connected to MySQL'
        ];
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'MySQL Connection',
            'status' => false,
            'message' => 'Could not connect: ' . $e->getMessage()
        ];
    }
    
    return $checks;
}

function testDatabaseConnection() {
    $host = $_POST['db_host'] ?? DB_HOST;
    $user = $_POST['db_user'] ?? DB_USER;
    $pass = $_POST['db_pass'] ?? DB_PASS;
    $name = $_POST['db_name'] ?? DB_NAME;
    
    try {
        $pdo = new PDO("mysql:host=$host", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $_SESSION['success'][] = "Database connection successful!";
        $_SESSION['db_config'] = [
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'name' => $name
        ];
        $_SESSION['installer_step'] = 4;
    } catch (PDOException $e) {
        $_SESSION['errors'][] = "Database connection failed: " . $e->getMessage();
    }
}

function performInstallation() {
    try {
        $config = $_SESSION['db_config'] ?? [
            'host' => DB_HOST,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'name' => DB_NAME
        ];
        
        // Read SQL file
        $sqlFile = __DIR__ . '/stocksathi_complete.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Connect to MySQL
        $pdo = new PDO(
            "mysql:host={$config['host']}",
            $config['user'],
            $config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Execute SQL
        $pdo->exec($sql);
        
        // Create config file
        createConfigFile($config);
        
        $_SESSION['success'][] = "Database installed successfully!";
        $_SESSION['success'][] = "Configuration file created!";
        $_SESSION['installer_step'] = 5;
        
    } catch (Exception $e) {
        $_SESSION['errors'][] = "Installation failed: " . $e->getMessage();
    }
}

function createConfigFile($config) {
    $configContent = <<<PHP
<?php
/**
 * Database Configuration
 * Auto-generated by StockSathi Installer
 */

define('DB_HOST', '{$config['host']}');
define('DB_NAME', '{$config['name']}');
define('DB_USER', '{$config['user']}');
define('DB_PASS', '{$config['pass']}');

// Database Connection
try {
    \$pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException \$e) {
    die("Database connection failed: " . \$e->getMessage());
}
PHP;

    $configFile = __DIR__ . '/_includes/database.php';
    file_put_contents($configFile, $configContent);
}

function getCurrentBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return $protocol . '://' . $host . $path;
}

// Start session for installer state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize installer state
if (!isset($_SESSION['installer_step'])) {
    $_SESSION['installer_step'] = 1;
    $_SESSION['errors'] = [];
    $_SESSION['success'] = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePostRequest();
    // Session is closed and redirected in handlePostRequest, so code below won't execute
}

// Get current step
$currentStep = $_SESSION['installer_step'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Installation Wizard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .installer-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }

        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .installer-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .installer-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .progress-bar {
            background: rgba(255, 255, 255, 0.2);
            height: 8px;
            border-radius: 4px;
            margin-top: 30px;
            overflow: hidden;
        }

        .progress-fill {
            background: white;
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .installer-body {
            padding: 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: #667eea;
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 12px;
            color: #666;
            text-align: center;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        .content-section {
            margin-bottom: 30px;
        }

        .content-section h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box.success {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        .info-box.warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .info-box.error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }

        .check-list {
            list-style: none;
            margin: 20px 0;
        }

        .check-list li {
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .check-list li:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group small {
            display: block;
            margin-top: 4px;
            color: #666;
            font-size: 12px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 20px 0;
        }

        .credential-box {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: #666;
        }

        .credential-value {
            font-family: 'Courier New', monospace;
            color: #333;
            background: white;
            padding: 4px 12px;
            border-radius: 4px;
        }

        .icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        .success-animation {
            text-align: center;
            padding: 40px 0;
        }

        .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-block;
            stroke-width: 3;
            stroke: #10b981;
            stroke-miterlimit: 10;
            box-shadow: inset 0px 0px 0px #10b981;
            animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
        }

        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 3;
            stroke-miterlimit: 10;
            stroke: #10b981;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {
            0%, 100% {
                transform: none;
            }
            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #10b981;
            }
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <div class="installer-header">
            <h1><?= APP_NAME ?></h1>
            <p>Installation Wizard v<?= INSTALLER_VERSION ?></p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= ($currentStep / 5) * 100 ?>%"></div>
            </div>
        </div>

        <div class="installer-body">
            <div class="step-indicator">
                <?php
                $steps = [
                    1 => 'Welcome',
                    2 => 'Requirements',
                    3 => 'Database',
                    4 => 'Install',
                    5 => 'Complete'
                ];
                
                foreach ($steps as $num => $label) {
                    $class = '';
                    if ($num < $currentStep) $class = 'completed';
                    if ($num == $currentStep) $class = 'active';
                    echo "<div class='step $class'>";
                    echo "<div class='step-number'>$num</div>";
                    echo "<div class='step-label'>$label</div>";
                    echo "</div>";
                }
                ?>
            </div>

            <?php
            // Display errors
            if (!empty($_SESSION['errors'])) {
                echo '<div class="info-box error">';
                echo '<strong>⚠️ Errors:</strong><ul style="margin-top: 10px;">';
                foreach ($_SESSION['errors'] as $error) {
                    echo "<li>$error</li>";
                }
                echo '</ul></div>';
                $_SESSION['errors'] = [];
            }

            // Display success messages
            if (!empty($_SESSION['success'])) {
                echo '<div class="info-box success">';
                echo '<strong>✅ Success:</strong><ul style="margin-top: 10px;">';
                foreach ($_SESSION['success'] as $success) {
                    echo "<li>$success</li>";
                }
                echo '</ul></div>';
                $_SESSION['success'] = [];
            }

            // Render current step
            switch ($currentStep) {
                case 1:
                    renderWelcomeStep();
                    break;
                case 2:
                    renderRequirementsStep();
                    break;
                case 3:
                    renderDatabaseStep();
                    break;
                case 4:
                    renderInstallStep();
                    break;
                case 5:
                    renderCompleteStep();
                    break;
            }
            ?>
        </div>
    </div>

    <script>
        function showLoading(button) {
            // Use setTimeout to allow form submission to start first
            setTimeout(function() {
                button.disabled = true;
                button.innerHTML = '<span class="loading"></span> Processing...';
            }, 10);
        }
    </script>
</body>
</html>

<?php

// ========================================================
// STEP RENDERING FUNCTIONS
// ========================================================

function renderWelcomeStep() {
    ?>
    <div class="content-section">
        <h2>🎉 Welcome to <?= APP_NAME ?> Installation</h2>
        
        <div class="info-box">
            <strong>About StockSathi:</strong>
            <p style="margin-top: 10px;">
                StockSathi is a comprehensive inventory management system designed for modern businesses. 
                This installer will set up everything you need to get started in just a few clicks.
            </p>
        </div>

        <div class="info-box warning">
            <strong>⚠️ Before You Begin:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Make sure XAMPP/WAMP/LAMP is installed</li>
                <li>Ensure MySQL/MariaDB service is running</li>
                <li>Have your database credentials ready</li>
                <li>This installation will create a new database named '<strong><?= DB_NAME ?></strong>'</li>
            </ul>
        </div>

        <div class="info-box success">
            <strong>✨ What You'll Get:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>38 Database tables with complete schema</li>
                <li>Sample data for testing (10 products, 5 customers, etc.)</li>
                <li>Role-based access control (RBAC)</li>
                <li>Pre-configured admin account</li>
                <li>All 33+ modules ready to use</li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="next_step">
            <div class="button-group">
                <button type="submit" class="btn btn-primary" onclick="showLoading(this)">
                    Get Started →
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderRequirementsStep() {
    $checks = performSystemChecks();
    $allPassed = !in_array(false, array_column($checks, 'status'));
    ?>
    <div class="content-section">
        <h2>🔍 System Requirements Check</h2>
        
        <div class="info-box">
            <p>Checking your system to ensure all requirements are met...</p>
        </div>

        <ul class="check-list">
            <?php foreach ($checks as $check): ?>
                <li>
                    <span><?= $check['name'] ?></span>
                    <span class="status-badge <?= $check['status'] ? 'success' : 'error' ?>">
                        <?= $check['status'] ? '✓ Passed' : '✗ Failed' ?>
                    </span>
                </li>
                <?php if (!$check['status'] && isset($check['message'])): ?>
                    <div style="color: #ef4444; font-size: 12px; margin-top: 4px;">
                        <?= $check['message'] ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>

        <?php if (!$allPassed): ?>
            <div class="info-box error">
                <strong>⚠️ Some requirements are not met!</strong>
                <p style="margin-top: 10px;">
                    Please fix the issues above before proceeding with the installation.
                </p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="<?= $allPassed ? 'next_step' : 'refresh' ?>">
            <div class="button-group">
                <button type="submit" name="action" value="prev_step" class="btn btn-secondary">
                    ← Back
                </button>
                <button type="submit" class="btn btn-primary" <?= !$allPassed ? 'disabled' : '' ?> onclick="showLoading(this)">
                    <?= $allPassed ? 'Continue →' : 'Refresh Checks' ?>
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderDatabaseStep() {
    ?>
    <div class="content-section">
        <h2>🗄️ Database Configuration</h2>
        
        <div class="info-box">
            <p>Enter your database credentials. The installer will create a new database named '<strong><?= DB_NAME ?></strong>'.</p>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="test_database">
            
            <div class="form-group">
                <label for="db_host">Database Host</label>
                <input type="text" id="db_host" name="db_host" value="<?= DB_HOST ?>" required>
                <small>Usually 'localhost' for local development</small>
            </div>

            <div class="form-group">
                <label for="db_user">Database Username</label>
                <input type="text" id="db_user" name="db_user" value="<?= DB_USER ?>" required>
                <small>Default is 'root' for XAMPP</small>
            </div>

            <div class="form-group">
                <label for="db_pass">Database Password</label>
                <input type="password" id="db_pass" name="db_pass" value="<?= DB_PASS ?>">
                <small>Leave empty if no password (default for XAMPP)</small>
            </div>

            <div class="form-group">
                <label for="db_name">Database Name</label>
                <input type="text" id="db_name" name="db_name" value="<?= DB_NAME ?>" required>
                <small>A new database will be created with this name</small>
            </div>

            <div class="button-group">
                <button type="submit" name="action" value="prev_step" class="btn btn-secondary">
                    ← Back
                </button>
                <button type="submit" class="btn btn-primary" onclick="showLoading(this)">
                    Test Connection →
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderInstallStep() {
    ?>
    <div class="content-section">
        <h2>⚙️ Ready to Install</h2>
        
        <div class="info-box success">
            <strong>✅ All checks passed!</strong>
            <p style="margin-top: 10px;">
                Your system is ready for installation. Click the button below to begin the installation process.
            </p>
        </div>

        <div class="info-box">
            <strong>📦 What will be installed:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>Database: <strong><?= DB_NAME ?></strong></li>
                <li>38 tables with complete schema</li>
                <li>Sample data (products, customers, invoices, etc.)</li>
                <li>Admin user account</li>
                <li>System settings and configurations</li>
            </ul>
        </div>

        <div class="info-box warning">
            <strong>⚠️ Important:</strong>
            <p style="margin-top: 10px;">
                If a database named '<?= DB_NAME ?>' already exists, it will be dropped and recreated. 
                Make sure you have a backup if needed.
            </p>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="install">
            <div class="button-group">
                <button type="submit" name="action" value="prev_step" class="btn btn-secondary">
                    ← Back
                </button>
                <button type="submit" class="btn btn-success" onclick="showLoading(this)">
                    🚀 Install Now
                </button>
            </div>
        </form>
    </div>
    <?php
}

function renderCompleteStep() {
    $baseUrl = getCurrentBaseUrl();
    ?>
    <div class="content-section">
        <div class="success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>

        <h2 style="text-align: center; color: #10b981; margin-bottom: 30px;">
            🎉 Installation Complete!
        </h2>
        
        <div class="info-box success">
            <strong>✅ StockSathi has been successfully installed!</strong>
            <p style="margin-top: 10px;">
                Your inventory management system is now ready to use. Below are your login credentials and important links.
            </p>
        </div>

        <div class="credential-box">
            <h3 style="margin-bottom: 15px; color: #667eea;">🔐 Admin Login Credentials</h3>
            <div class="credential-item">
                <span class="credential-label">Email:</span>
                <span class="credential-value">admin@stocksathi.com</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Password:</span>
                <span class="credential-value">admin123</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Role:</span>
                <span class="credential-value">Administrator</span>
            </div>
        </div>

        <div class="info-box warning">
            <strong>🔒 Security Reminder:</strong>
            <p style="margin-top: 10px;">
                Please change the default password after your first login for security purposes.
            </p>
        </div>

        <div class="info-box">
            <strong>📚 Quick Links:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li><strong>Login Page:</strong> <a href="<?= $baseUrl ?>/pages/login.php" target="_blank"><?= $baseUrl ?>/pages/login.php</a></li>
                <li><strong>Dashboard:</strong> <a href="<?= $baseUrl ?>/index.php" target="_blank"><?= $baseUrl ?>/index.php</a></li>
                <li><strong>Documentation:</strong> <a href="<?= $baseUrl ?>/README.md" target="_blank">README.md</a></li>
            </ul>
        </div>

        <div class="info-box">
            <strong>📊 Sample Data Included:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <li>10 Products (Electronics, Clothing, etc.)</li>
                <li>5 Customers with realistic data</li>
                <li>4 Suppliers with payment terms</li>
                <li>3 Sample invoices</li>
                <li>3 Warehouses and 3 Stores</li>
                <li>3 Employees with departments</li>
            </ul>
        </div>

        <div class="button-group">
            <a href="<?= $baseUrl ?>/pages/login.php" class="btn btn-success" style="flex: 1;">
                🚀 Go to Login Page
            </a>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p style="color: #666; font-size: 14px;">
                Thank you for choosing <?= APP_NAME ?>! 💙<br>
                <small>Version <?= INSTALLER_VERSION ?> - Production Ready</small>
            </p>
        </div>
    </div>
    <?php
}


